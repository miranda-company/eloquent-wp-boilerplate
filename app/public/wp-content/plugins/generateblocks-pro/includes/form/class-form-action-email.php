<?php
/**
 * Email form action.
 *
 * Sends form submissions via wp_mail(). Never uses user data in the From
 * header.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email form action class.
 */
class GenerateBlocks_Pro_Form_Action_Email {

	/**
	 * Hard cap on recipient count per submission. emailTo is admin-controlled
	 * (manage_options to write form meta), so submitters can't reach this. The
	 * cap protects against misconfiguration — pasting a giant CSV — amplifying
	 * outbound mail per submission.
	 */
	const MAX_RECIPIENTS = 10;

	/**
	 * Register the email action with the registry.
	 */
	public static function init() {
		GenerateBlocks_Pro_Form_Action_Registry::register( 'email', [ __CLASS__, 'send' ] );
	}

	/**
	 * Validate the email action settings before delivery starts.
	 *
	 * @param array $form_settings The form block settings.
	 * @return true|WP_Error
	 */
	public static function validate_settings( $form_settings ) {
		$to = self::parse_recipients(
			! empty( $form_settings['emailTo'] ) ? $form_settings['emailTo'] : get_option( 'admin_email' )
		);

		if ( empty( $to ) ) {
			return new WP_Error( 'invalid_recipient', 'Invalid email recipient.' );
		}

		return true;
	}

	/**
	 * Send the form submission via email.
	 *
	 * @param array $sanitized_data The sanitized form data (field_name => ['value' => ..., 'label' => ..., 'type' => ...]).
	 * @param array $form_settings  The form block settings.
	 * @param array $context        Request context (post_id, page_url).
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public static function send( $sanitized_data, $form_settings, $context = [] ) {
		$validation = self::validate_settings( $form_settings );

		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Determine recipients — supports comma-separated addresses.
		$to = self::parse_recipients(
			! empty( $form_settings['emailTo'] ) ? $form_settings['emailTo'] : get_option( 'admin_email' )
		);

		// Build subject with site name for context.
		$site_name = get_bloginfo( 'name' );

		if ( ! empty( $form_settings['emailSubject'] ) ) {
			$subject = $form_settings['emailSubject'];
		} else {
			$subject = sprintf(
				/* translators: %s: site name */
				__( '[%s] New Form Submission', 'generateblocks-pro' ),
				$site_name
			);
		}

		$subject = GenerateBlocks_Pro_Form_Sanitizer::sanitize_email_header( $subject );

		/**
		 * Filter whether admin notification emails should use the built-in HTML template.
		 *
		 * @since 2.6.0
		 * @param bool  $send_html      Whether to send HTML. Default true.
		 * @param array $sanitized_data The sanitized form data.
		 * @param array $form_settings  The form block settings.
		 * @param array $context        Request context.
		 */
		$send_html = (bool) apply_filters(
			'generateblocks_form_email_use_html',
			true,
			$sanitized_data,
			$form_settings,
			$context
		);

		// Build headers. Never set From from user data.
		$headers = [
			$send_html
				? 'Content-Type: text/html; charset=UTF-8'
				: 'Content-Type: text/plain; charset=UTF-8',
		];

		// Reply-To from a designated form field.
		if ( ! empty( $form_settings['emailReplyToField'] ) ) {
			$reply_to_field = sanitize_key( $form_settings['emailReplyToField'] );
			$reply_to_data  = $sanitized_data[ $reply_to_field ] ?? null;

			if ( is_array( $reply_to_data ) ) {
				$reply_to = sanitize_email( (string) ( $reply_to_data['value'] ?? '' ) );

				if ( is_email( $reply_to ) ) {
					$reply_to = GenerateBlocks_Pro_Form_Sanitizer::sanitize_email_header( $reply_to );
					$headers[] = 'Reply-To: ' . $reply_to;
				}
			}
		}

		$body = $send_html
			? self::build_html_body( $sanitized_data, $context, $site_name )
			: self::build_text_body( $sanitized_data, $context, $site_name );

		/**
		 * Filter the email arguments before sending.
		 *
		 * @since 2.6.0
		 * @param array $email_args {
		 *     Email arguments.
		 *
		 *     @type array  $to      Recipient email addresses.
		 *     @type string $subject Email subject line.
		 *     @type string $body    Email body content.
		 *     @type array  $headers Email headers.
		 * }
		 * @param array $sanitized_data The sanitized form data.
		 * @param array $form_settings  The form block settings.
		 * @param array $context        Request context.
		 */
		$email_args = apply_filters(
			'generateblocks_form_email_args',
			[
				'to'      => $to,
				'subject' => $subject,
				'body'    => $body,
				'headers' => $headers,
			],
			$sanitized_data,
			$form_settings,
			$context
		);

		// Re-validate filter output: defensive against third-party callbacks
		// returning values with embedded newlines (header injection) or
		// reshaping the array entirely.
		if ( ! is_array( $email_args ) ) {
			$email_args = [];
		}

		$safe_to = self::sanitize_filter_recipients( $email_args['to'] ?? [] );

		if ( empty( $safe_to ) ) {
			return new WP_Error( 'email_no_recipients', 'No valid email recipient.' );
		}

		$raw_subject  = $email_args['subject'] ?? '';
		$safe_subject = GenerateBlocks_Pro_Form_Sanitizer::sanitize_email_header(
			is_string( $raw_subject ) ? $raw_subject : ''
		);
		$safe_headers = self::sanitize_filter_headers( $email_args['headers'] ?? [] );
		$raw_body     = $email_args['body'] ?? '';
		$safe_body    = is_string( $raw_body ) ? $raw_body : '';

		$sent = self::send_mail( $safe_to, $safe_subject, $safe_body, $safe_headers );

		if ( ! $sent ) {
			return new WP_Error( 'email_failed', 'Failed to send email.' );
		}

		return true;
	}

	/**
	 * Re-validate `to` returned by a filter callback. Accepts either a
	 * comma-separated string or an array of addresses.
	 *
	 * @param mixed $value The filtered `to` value.
	 * @return array Valid email addresses.
	 */
	private static function sanitize_filter_recipients( $value ) {
		if ( is_string( $value ) ) {
			return self::parse_recipients( $value );
		}

		if ( ! is_array( $value ) ) {
			return [];
		}

		$valid = [];

		foreach ( $value as $address ) {
			if ( ! is_string( $address ) ) {
				continue;
			}

			$address = sanitize_email( $address );

			if ( is_email( $address ) ) {
				$valid[] = $address;

				if ( count( $valid ) >= self::MAX_RECIPIENTS ) {
					break;
				}
			}
		}

		return $valid;
	}

	/**
	 * Re-validate the `headers` array returned by a filter callback.
	 * Drops anything that's not a string and strips header-injection chars.
	 *
	 * Note: we only strip CR/LF/tab and their percent-encoded variants here.
	 * Running sanitize_text_field() would mangle valid display-name email
	 * syntax like "Reply-To: Name <a@b.com>" (strip_tags eats the <...>).
	 *
	 * @param mixed $headers The filtered headers value.
	 * @return array Sanitized headers.
	 */
	private static function sanitize_filter_headers( $headers ) {
		if ( ! is_array( $headers ) ) {
			return [];
		}

		$safe = [];

		foreach ( $headers as $name => $header ) {
			if ( is_string( $name ) && '' !== $name ) {
				$header = $name . ': ' . ( is_scalar( $header ) ? (string) $header : '' );
			}

			if ( ! is_string( $header ) ) {
				continue;
			}

			$header = preg_replace( '/[\r\n\t]/', '', $header );
			$header = str_replace( [ '%0a', '%0d', '%0A', '%0D' ], '', $header );
			$header = trim( $header );

			if ( '' !== $header ) {
				$safe[] = $header;
			}
		}

		return $safe;
	}

	/**
	 * Send the email while forcing the content type parsed from our headers.
	 *
	 * WordPress applies the global wp_mail_content_type filter after parsing
	 * the headers passed to wp_mail(). Keep this message's MIME type scoped to
	 * the final sanitized headers so site-level filters cannot make HTML render
	 * as plain text or plain text render as HTML.
	 *
	 * @param array  $to      Recipient email addresses.
	 * @param string $subject Email subject.
	 * @param string $body    Email body.
	 * @param array  $headers Email headers.
	 * @return bool Whether the email was sent.
	 */
	private static function send_mail( $to, $subject, $body, $headers ) {
		$content_type = self::get_content_type_from_headers( $headers );

		if ( '' === $content_type ) {
			return wp_mail( $to, $subject, $body, $headers );
		}

		$content_type_filter = static function () use ( $content_type ) {
			return $content_type;
		};

		add_filter( 'wp_mail_content_type', $content_type_filter, 999 );

		try {
			return wp_mail( $to, $subject, $body, $headers );
		} finally {
			remove_filter( 'wp_mail_content_type', $content_type_filter, 999 );
		}
	}

	/**
	 * Extract a supported MIME content type from headers.
	 *
	 * @param array $headers Email headers.
	 * @return string Supported content type, or an empty string.
	 */
	private static function get_content_type_from_headers( $headers ) {
		$content_type = '';

		foreach ( $headers as $header ) {
			if ( ! is_string( $header ) || 0 !== stripos( $header, 'Content-Type:' ) ) {
				continue;
			}

			$value = trim( substr( $header, strlen( 'Content-Type:' ) ) );
			$type  = strtolower( trim( explode( ';', $value, 2 )[0] ) );

			if ( in_array( $type, [ 'text/html', 'text/plain' ], true ) ) {
				$content_type = $type;
			} elseif ( false !== strpos( $value, ';' ) || '' !== $type ) {
				$content_type = '';
			}
		}

		return $content_type;
	}

	/**
	 * Parse and validate a comma-separated list of email recipients.
	 *
	 * @param string $raw Comma-separated email addresses.
	 * @return array Valid email addresses, or empty array if none valid.
	 */
	public static function parse_recipients( $raw ) {
		$addresses = array_map( 'trim', explode( ',', $raw ) );
		$valid     = [];

		foreach ( $addresses as $address ) {
			$address = sanitize_email( $address );

			if ( is_email( $address ) ) {
				$valid[] = $address;

				if ( count( $valid ) >= self::MAX_RECIPIENTS ) {
					break;
				}
			}
		}

		return $valid;
	}

	/**
	 * Build the email body as HTML.
	 *
	 * @param array  $sanitized_data The sanitized form data.
	 * @param array  $context        Request context.
	 * @param string $site_name      The site name.
	 * @return string The formatted email body.
	 */
	private static function build_html_body( $sanitized_data, $context, $site_name ) {
		$fields = [];
		$rows   = '';

		foreach ( $sanitized_data as $field_name => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$label = sanitize_text_field( $field['label'] ?? $field_name );
			$value = wp_strip_all_tags( (string) ( $field['value'] ?? '' ), false );

			if ( '' === $value ) {
				continue;
			}

			$fields[] = [
				'label' => $label,
				'value' => $value,
				'type'  => (string) ( $field['type'] ?? 'text' ),
			];
		}

		$field_count = count( $fields );

		foreach ( $fields as $index => $field ) {
			$rows .= self::build_html_field_row(
				$field['label'],
				$field['value'],
				$field['type'],
				$index === $field_count - 1
			);
		}

		if ( '' === $rows ) {
			$rows = '<tr><td style="padding:20px 0;color:#666666;font-family:Arial,sans-serif;font-size:14px;line-height:1.6;">'
				. esc_html__( 'No field values were submitted.', 'generateblocks-pro' )
				. '</td></tr>';
		}

		$meta_rows = self::build_html_meta_rows( $context, $site_name );

		return '<table role="presentation" width="100%" cellpadding="24" cellspacing="0" border="0" bgcolor="#f5f5f5">'
			. '<tr><td align="center">'
			. '<table role="presentation" width="640" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff">'
			. '<tr><td style="padding:10px 36px 24px 36px;">'
			. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">'
			. $rows
			. '</table>'
			. '</td></tr>'
			. ( '' !== $meta_rows
				? '<tr><td bgcolor="#fafafa" style="border-top:1px solid #eeeeee;padding:18px 36px;">'
					. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">'
					. $meta_rows
					. '</table>'
					. '</td></tr>'
				: '' )
			. '</table>'
			. '</td></tr>'
			. '</table>';
	}

	/**
	 * Build one field row for the HTML email body.
	 *
	 * @param string $label Field label.
	 * @param string $value Field value.
	 * @param string $type  Field type.
	 * @param bool   $last  Whether this is the last field row.
	 * @return string Field row HTML.
	 */
	private static function build_html_field_row( $label, $value, $type, $last ) {
		$border = $last ? '' : 'border-bottom:1px solid #eeeeee;';

		return '<tr><td style="padding:16px 0 4px 0;color:#767676;font-family:Arial,sans-serif;font-size:12px;">'
			. esc_html( self::format_label( $label ) )
			. '</td></tr>'
			. '<tr><td style="' . $border . 'color:#1f1f1f;font-family:Arial,sans-serif;font-size:15px;line-height:1.6;padding:0 0 16px 0;">'
			. self::format_html_value( $value, $type )
			. '</td></tr>';
	}

	/**
	 * Format a submitted value for HTML email output.
	 *
	 * @param string $value Field value.
	 * @param string $type  Field type.
	 * @return string HTML-safe value.
	 */
	private static function format_html_value( $value, $type ) {
		$value = str_replace( [ "\r\n", "\r" ], "\n", $value );

		if ( 'email' === $type ) {
			$email = sanitize_email( $value );

			if ( is_email( $email ) ) {
				return '<a href="' . esc_url( 'mailto:' . $email ) . '" style="color:#1a5fb4;">'
					. esc_html( $value )
					. '</a>';
			}
		}

		if ( 'url' === $type ) {
			$url = esc_url( $value );

			if ( '' !== $url ) {
				return '<a href="' . $url . '" style="color:#1a5fb4;">'
					. esc_html( $value )
					. '</a>';
			}
		}

		return nl2br( esc_html( $value ), false );
	}

	/**
	 * Build metadata rows for the HTML email footer.
	 *
	 * @param array  $context   Request context.
	 * @param string $site_name Site name.
	 * @return string Metadata rows HTML.
	 */
	private static function build_html_meta_rows( $context, $site_name ) {
		$rows = '';

		if ( '' !== $site_name ) {
			$rows .= self::build_html_meta_row( __( 'Site', 'generateblocks-pro' ), esc_html( $site_name ) );
		}

		$form_title = self::get_form_title( $context );

		if ( '' !== $form_title ) {
			$rows .= self::build_html_meta_row( __( 'Form', 'generateblocks-pro' ), esc_html( $form_title ) );
		}

		if ( ! empty( $context['page_url'] ) ) {
			$url = esc_url( $context['page_url'] );

			if ( '' !== $url ) {
				$rows .= self::build_html_meta_row(
					__( 'Page', 'generateblocks-pro' ),
					'<a href="' . $url . '" style="color:#1a5fb4;">' . esc_html( $context['page_url'] ) . '</a>'
				);
			}
		}

		$rows .= self::build_html_meta_row(
			__( 'Submitted', 'generateblocks-pro' ),
			esc_html( wp_date( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ) ) )
		);

		return $rows;
	}

	/**
	 * Build one metadata row for the HTML email footer.
	 *
	 * @param string $label Metadata label.
	 * @param string $value Metadata value HTML.
	 * @return string Metadata row HTML.
	 */
	private static function build_html_meta_row( $label, $value ) {
		return '<tr>'
			. '<td valign="top" style="color:#767676;font-family:Arial,sans-serif;font-size:12px;line-height:1.5;padding:3px 14px 3px 0;">'
			. esc_html( self::format_label( $label ) )
			. '</td>'
			. '<td valign="top" style="color:#444444;font-family:Arial,sans-serif;font-size:13px;line-height:1.5;padding:3px 0;">'
			. $value
			. '</td>'
			. '</tr>';
	}

	/**
	 * Format labels without depending on CSS text-transform support.
	 *
	 * @param string $label Label text.
	 * @return string Uppercase label.
	 */
	private static function format_label( $label ) {
		return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $label ) : strtoupper( $label );
	}

	/**
	 * Get the form title from the request context.
	 *
	 * @param array $context Request context.
	 * @return string Form title.
	 */
	private static function get_form_title( $context ) {
		$form_id = isset( $context['form_id'] ) ? (int) $context['form_id'] : 0;

		if ( $form_id <= 0 ) {
			return '';
		}

		return sanitize_text_field( get_the_title( $form_id ) );
	}

	/**
	 * Build the email body as plain text.
	 *
	 * Uses uppercase labels on their own line for a clear scan pattern.
	 * Long values and multi-line content (textareas) flow naturally.
	 * The email client handles all rendering — fonts, colors, dark mode.
	 *
	 * @param array  $sanitized_data The sanitized form data.
	 * @param array  $context        Request context.
	 * @param string $site_name      The site name.
	 * @return string The formatted email body.
	 */
	private static function build_text_body( $sanitized_data, $context, $site_name ) {
		$lines = [];

		foreach ( $sanitized_data as $field_name => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$label = sanitize_text_field( $field['label'] ?? $field_name );
			$value = wp_strip_all_tags( (string) ( $field['value'] ?? '' ) );

			if ( '' === $value ) {
				continue;
			}

			$lines[] = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $label ) : strtoupper( $label );
			$lines[] = $value;
			$lines[] = '';
		}

		// Divider before metadata.
		$lines[] = str_repeat( "\xe2\x94\x80", 40 );

		// Metadata on compact lines.
		$meta = [ $site_name ];

		if ( ! empty( $context['page_url'] ) ) {
			$meta[] = $context['page_url'];
		}

		$meta[] = wp_date( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ) );

		$lines[] = implode( " \xc2\xb7 ", $meta );

		return implode( "\n", $lines );
	}
}
