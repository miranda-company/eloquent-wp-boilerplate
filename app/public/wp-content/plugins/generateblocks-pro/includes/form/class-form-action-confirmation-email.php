<?php
/**
 * Confirmation email form action.
 *
 * Sends an auto-reply to the person who submitted the form.
 * Plain text only. Never uses user data in the From header.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Confirmation email form action class.
 */
class GenerateBlocks_Pro_Form_Action_Confirmation_Email {

	/**
	 * Register the action with the registry.
	 */
	public static function init() {
		GenerateBlocks_Pro_Form_Action_Registry::register( 'confirmation-email', [ __CLASS__, 'send' ] );
	}

	/**
	 * Validate confirmation email settings before publishing or delivery.
	 *
	 * @param array      $form_settings The form block settings.
	 * @param array|null $schema        Optional derived field schema list.
	 * @return true|WP_Error
	 */
	public static function validate_settings( $form_settings, $schema = null ) {
		if ( is_array( $schema ) ) {
			$email_field = self::get_email_field( $form_settings );

			if ( '' === $email_field ) {
				return new WP_Error(
					'gb_form_confirmation_email_field_missing',
					__( 'Confirmation email field is required.', 'generateblocks-pro' ),
					[ 'status' => 400 ]
				);
			}

			$field = self::find_schema_field( $schema, $email_field );

			if ( ! $field ) {
				return new WP_Error(
					'gb_form_confirmation_email_field_missing',
					sprintf(
						/* translators: %s: email field name. */
						__( 'Confirmation email field "%s" does not exist.', 'generateblocks-pro' ),
						$email_field
					),
					[ 'status' => 400 ]
				);
			}

			$field_type = $field['type'] ?? '';

			if ( ! in_array( $field_type, [ 'email', 'text' ], true ) ) {
				return new WP_Error(
					'gb_form_confirmation_email_field_invalid',
					sprintf(
						/* translators: %s: email field name. */
						__( 'Confirmation email field "%s" must be an email or text field.', 'generateblocks-pro' ),
						$email_field
					),
					[ 'status' => 400 ]
				);
			}
		}

		return true;
	}

	/**
	 * Validate the submitted confirmation email recipient before delivery.
	 *
	 * @param array $sanitized_data The sanitized form data.
	 * @param array $form_settings  The form block settings.
	 * @return true|WP_Error
	 */
	public static function validate_submission( $sanitized_data, $form_settings ) {
		$email_field = self::get_email_field( $form_settings );

		if ( '' === $email_field ) {
			return new WP_Error(
				'missing_email_field',
				'Confirmation email field is not configured.',
				[ 'status' => 400 ]
			);
		}

		// is_array catches both missing key and shape mutation by a
		// generateblocks_form_validate_submission filter that returned
		// a flat scalar instead of the [value, label, type] array.
		$field = $sanitized_data[ $email_field ] ?? null;

		if ( ! is_array( $field ) ) {
			return new WP_Error(
				'missing_email_field',
				'Confirmation email field not found in submission.',
				[ 'status' => 400 ]
			);
		}

		$to = sanitize_email( (string) ( $field['value'] ?? '' ) );

		if ( ! is_email( $to ) ) {
			return new WP_Error(
				'invalid_recipient',
				'Invalid recipient email for confirmation.',
				[ 'status' => 400 ]
			);
		}

		return true;
	}

	/**
	 * Send a confirmation email to the submitter.
	 *
	 * @param array $sanitized_data The sanitized form data (field_name => ['value' => ..., 'label' => ..., 'type' => ...]).
	 * @param array $form_settings  The form block settings.
	 * @param array $context        Request context (post_id, page_url).
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public static function send( $sanitized_data, $form_settings, $context = [] ) {
		$validation = self::validate_submission( $sanitized_data, $form_settings );

		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$email_field = self::get_email_field( $form_settings );
		$to_field    = $sanitized_data[ $email_field ] ?? null;
		$to          = sanitize_email( is_array( $to_field ) ? (string) ( $to_field['value'] ?? '' ) : '' );

		// Build subject with merge tag support.
		$raw_subject = $form_settings['confirmationEmailSubject'] ?? '';

		if ( empty( $raw_subject ) ) {
			$raw_subject = sprintf(
				/* translators: %s: site name */
				__( 'Thank you for contacting %s', 'generateblocks-pro' ),
				get_bloginfo( 'name' )
			);
		}

		$subject = GenerateBlocks_Pro_Form_Sanitizer::sanitize_email_header(
			self::replace_merge_tags( $raw_subject, $sanitized_data )
		);

		// Build body with merge tag support.
		$raw_body = $form_settings['confirmationEmailBody'] ?? '';

		if ( empty( $raw_body ) ) {
			$raw_body = __( 'Thank you for your submission. We will get back to you soon.', 'generateblocks-pro' );
		}

		$body = self::replace_merge_tags( $raw_body, $sanitized_data );

		// Plain text only. Never set From from user data.
		$headers = [ 'Content-Type: text/plain; charset=UTF-8' ];

		$reply_to_header = self::build_reply_to_header(
			$form_settings['confirmationEmailReplyToEmail'] ?? '',
			$form_settings['confirmationEmailReplyToName'] ?? ''
		);

		if ( '' !== $reply_to_header ) {
			$headers[] = $reply_to_header;
		}

		/**
		 * Filter the confirmation email arguments before sending.
		 *
		 * @since 2.6.0
		 * @param array $email_args {
		 *     Email arguments.
		 *
		 *     @type string $to      Recipient email address.
		 *     @type string $subject Email subject line.
		 *     @type string $body    Email body content.
		 *     @type array  $headers Email headers.
		 * }
		 * @param array $sanitized_data The sanitized form data.
		 * @param array $form_settings  The form block settings.
		 * @param array $context        Request context.
		 */
		$email_args = apply_filters(
			'generateblocks_form_confirmation_email_args',
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

		$raw_to = $email_args['to'] ?? '';
		// Two-pass sanitization: strip CR/LF/tab + percent-encoded variants
		// before sanitize_email() so a filter that returned an address with
		// embedded newlines can't leak into wp_mail's `To` header. Belt and
		// suspenders — sanitize_email() rejects most malformed addresses
		// already, but this matches how `subject` and `headers` are
		// hardened above so the contract is symmetric.
		$safe_to      = is_string( $raw_to )
			? sanitize_email( GenerateBlocks_Pro_Form_Sanitizer::sanitize_email_header( $raw_to ) )
			: '';
		$raw_subject  = $email_args['subject'] ?? '';
		$safe_subject = GenerateBlocks_Pro_Form_Sanitizer::sanitize_email_header(
			is_string( $raw_subject ) ? $raw_subject : ''
		);
		$safe_headers = self::sanitize_filter_headers( $email_args['headers'] ?? [] );
		$raw_body     = $email_args['body'] ?? '';
		$safe_body    = is_string( $raw_body ) ? $raw_body : '';

		if ( ! is_email( $safe_to ) ) {
			return new WP_Error( 'confirmation_email_invalid_to', 'No valid email recipient.' );
		}

		$sent = wp_mail(
			$safe_to,
			$safe_subject,
			$safe_body,
			$safe_headers
		);

		if ( ! $sent ) {
			return new WP_Error( 'confirmation_email_failed', 'Failed to send confirmation email.' );
		}

		return true;
	}

	/**
	 * Re-validate the `headers` array returned by a filter callback.
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

		foreach ( $headers as $header ) {
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
	 * Build a safe Reply-To header from static confirmation email settings.
	 *
	 * @param mixed $raw_email Configured reply-to email.
	 * @param mixed $raw_name  Configured reply-to display name.
	 * @return string Header line, or empty string when no valid email exists.
	 */
	private static function build_reply_to_header( $raw_email, $raw_name ) {
		$email = is_scalar( $raw_email ) ? sanitize_email( (string) $raw_email ) : '';

		if ( ! is_email( $email ) ) {
			return '';
		}

		$email = GenerateBlocks_Pro_Form_Sanitizer::sanitize_email_header( $email );
		$name  = is_scalar( $raw_name ) ? sanitize_text_field( (string) $raw_name ) : '';
		$name  = GenerateBlocks_Pro_Form_Sanitizer::sanitize_email_header( $name );
		$name  = trim( str_replace( [ '<', '>', '"', ',', ':', '\\' ], '', $name ) );

		if ( '' === $name ) {
			return 'Reply-To: ' . $email;
		}

		return 'Reply-To: ' . $name . ' <' . $email . '>';
	}

	/**
	 * Replace {field:name} merge tags with sanitized field values.
	 *
	 * Uses sanitize_textarea_field() for textarea fields to preserve newlines,
	 * and sanitize_text_field() for everything else.
	 *
	 * @param string $text           The text containing merge tags.
	 * @param array  $sanitized_data The sanitized form data.
	 * @return string Text with merge tags replaced.
	 */
	public static function replace_merge_tags( $text, $sanitized_data ) {
		return preg_replace_callback(
			'/\{field:([a-z0-9_-]+)\}/i',
			function ( $matches ) use ( $sanitized_data ) {
				$field_name = sanitize_key( $matches[1] );
				$field      = $sanitized_data[ $field_name ] ?? null;

				if ( is_array( $field ) ) {
					$value = (string) ( $field['value'] ?? '' );
					$type  = $field['type'] ?? 'text';

					// Preserve newlines for textarea fields in the email body.
					return 'textarea' === $type
						? sanitize_textarea_field( $value )
						: sanitize_text_field( $value );
				}

				// Unknown tag — leave as-is for debugging.
				return $matches[0];
			},
			$text
		);
	}

	/**
	 * Get the configured recipient field.
	 *
	 * @param array $form_settings The form block settings.
	 * @return string
	 */
	private static function get_email_field( $form_settings ) {
		if (
			isset( $form_settings['confirmationEmailField'] )
			&& ( is_array( $form_settings['confirmationEmailField'] ) || is_object( $form_settings['confirmationEmailField'] ) )
		) {
			return '';
		}

		return isset( $form_settings['confirmationEmailField'] )
			? sanitize_key( $form_settings['confirmationEmailField'] )
			: '';
	}

	/**
	 * Find a field in the derived schema by name.
	 *
	 * @param array  $schema Field schema list.
	 * @param string $name   Field name.
	 * @return array|null
	 */
	private static function find_schema_field( $schema, $name ) {
		foreach ( $schema as $field ) {
			if ( is_array( $field ) && ( $field['name'] ?? '' ) === $name ) {
				return $field;
			}
		}

		return null;
	}
}
