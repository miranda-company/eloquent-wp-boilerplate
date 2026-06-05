<?php
/**
 * Form field sanitization.
 *
 * Every form value passes through this class. No exceptions.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form sanitizer class.
 */
class GenerateBlocks_Pro_Form_Sanitizer {

	/**
	 * Allowed field types and their sanitization callbacks.
	 *
	 * @var array
	 */
	private static $type_sanitizers = [
		'text'           => 'sanitize_text_field',
		'textarea'       => 'sanitize_textarea_field',
		'email'          => 'sanitize_email',
		'url'            => 'esc_url_raw',
		'tel'            => [ __CLASS__, 'sanitize_tel' ],
		'number'         => [ __CLASS__, 'sanitize_number' ],
		'hidden'         => 'sanitize_text_field',
		'select'         => 'sanitize_text_field',
		'checkbox'       => 'sanitize_text_field',
		'radio'          => 'sanitize_text_field',
		'checkbox-group' => 'sanitize_text_field',
	];

	/**
	 * Maximum allowed length for any single field value.
	 *
	 * @var int
	 */
	const MAX_FIELD_LENGTH = 10000;

	/**
	 * Sanitize a single field value based on its registered type.
	 *
	 * @param string $value      The raw field value.
	 * @param string $field_type The field type (text, email, url, tel, number, hidden).
	 * @return string Sanitized value.
	 */
	public static function sanitize_field( $value, $field_type ) {
		// Force string.
		$value = (string) $value;

		// Strip null bytes.
		$value = str_replace( "\0", '', $value );

		// Enforce max length. mbstring is near-universal on modern PHP but
		// we fall back to byte substr so a missing extension doesn't fatal.
		$value = function_exists( 'mb_substr' )
			? mb_substr( $value, 0, self::MAX_FIELD_LENGTH )
			: substr( $value, 0, self::MAX_FIELD_LENGTH );

		/**
		 * Filter the sanitizers for form field types.
		 *
		 * Allows third-party developers to register custom field type sanitizers.
		 *
		 * @since 2.6.0
		 * @param array $type_sanitizers Field type => sanitizer callback map.
		 */
		$type_sanitizers = apply_filters( 'generateblocks_form_type_sanitizers', self::$type_sanitizers );

		// Get the sanitizer for this type, default to sanitize_text_field.
		$sanitizer = $type_sanitizers[ $field_type ] ?? 'sanitize_text_field';

		if ( is_callable( $sanitizer ) ) {
			return call_user_func( $sanitizer, $value );
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Sanitize all fields from a submission against the registered schema.
	 *
	 * Iterates over the SCHEMA (server-derived), not the user input.
	 * Unknown fields are silently dropped.
	 *
	 * @param array $raw_data     The raw submitted data (field_name => value).
	 * @param array $field_schema The registered field schema (field_name => ['type' => ..., 'required' => ...]).
	 * @return array|WP_Error Sanitized data array or WP_Error on validation failure.
	 */
	public static function sanitize_submission( $raw_data, $field_schema ) {
		$sanitized = [];
		$errors    = [];

		foreach ( $field_schema as $field_name => $field_config ) {
			$type     = $field_config['type'] ?? 'text';
			$required = $field_config['required'] ?? false;
			$label    = $field_config['label'] ?? $field_name;
			$value    = $raw_data[ $field_name ] ?? '';

			// Check required fields.
			$is_empty = is_array( $value ) ? empty( $value ) : '' === trim( (string) $value );

			if ( $required && $is_empty ) {
				$errors[] = $field_name;
				continue;
			}

			// Checkbox-group: value arrives as an array from FormData's getAll().
			// Sanitize each item individually, validate against allowed options, then join.
			if ( 'checkbox-group' === $type ) {
				$raw_values     = is_array( $value ) ? $value : ( '' !== $value ? [ $value ] : [] );
				$allowed_values = $field_config['allowedValues'] ?? [];
				$clean_parts    = [];

				foreach ( $raw_values as $raw_item ) {
					$clean_item = self::sanitize_field( (string) $raw_item, $type );

					if ( '' === $clean_item ) {
						continue;
					}

					if ( ! empty( $allowed_values ) && ! in_array( $clean_item, $allowed_values, true ) ) {
						continue; // Skip invalid options silently.
					}

					$clean_parts[] = $clean_item;
				}

				$clean_value = implode( ', ', $clean_parts );

				if ( $required && '' === $clean_value ) {
					$errors[] = $field_name;
					continue;
				}

				$sanitized[ $field_name ] = [
					'value' => $clean_value,
					'label' => sanitize_text_field( $label ),
					'type'  => $type,
				];

				continue;
			}

			// Reject array-shaped values for scalar field types. Only
			// checkbox-group expects an array (handled above); for every
			// other type a non-scalar arrives only via tampered submissions.
			// Casting an array to string would raise a PHP warning and
			// persist the literal "Array" — fail cleanly instead.
			if ( ! is_scalar( $value ) && null !== $value ) {
				if ( $required ) {
					$errors[] = $field_name;
					continue;
				}

				$value = '';
			}

			// Sanitize the value.
			$clean_value = self::sanitize_field( $value, $type );

			// Required + invalid typed value: sanitize_email/sanitize_number/
			// esc_url_raw return '' for malformed input. The pre-sanitize
			// emptiness check above only catches values that were empty as
			// submitted, so a required number field with "abc" or a required
			// email with "notanemail" would otherwise persist as an empty
			// string. Treat post-sanitize empty as the same failure mode.
			if ( $required && '' === trim( (string) $clean_value ) ) {
				$errors[] = $field_name;
				continue;
			}

			// Validate select and radio values against allowed options.
			if ( ( 'select' === $type || 'radio' === $type ) && '' !== $clean_value ) {
				$allowed_values = $field_config['allowedValues'] ?? [];

				if ( ! in_array( $clean_value, $allowed_values, true ) ) {
					$errors[] = $field_name;
					continue;
				}
			}

			// Validate checkbox values.
			if ( 'checkbox' === $type && '' !== $clean_value ) {
				$checked_value = $field_config['checkedValue'] ?? 'yes';

				if ( $clean_value !== $checked_value ) {
					// Tampered value — reject if required, otherwise blank it.
					if ( $required ) {
						$errors[] = $field_name;
						continue;
					}

					$clean_value = '';
				}
			}

			$sanitized[ $field_name ] = [
				'value' => $clean_value,
				'label' => sanitize_text_field( $label ),
				'type'  => $type,
			];
		}

		if ( ! empty( $errors ) ) {
			return new WP_Error(
				'missing_required_fields',
				__( 'Required fields are missing.', 'generateblocks-pro' ),
				[ 'fields' => $errors ]
			);
		}

		$mismatched = self::validate_matching_fields( $sanitized, $field_schema );

		if ( ! empty( $mismatched['fields'] ) ) {
			return new WP_Error(
				'mismatched_fields',
				__( 'Fields do not match.', 'generateblocks-pro' ),
				[
					'fields'         => $mismatched['fields'],
					'public_message' => $mismatched['public_message'],
				]
			);
		}

		return $sanitized;
	}

	/**
	 * Validate same-value field match rules after sanitization.
	 *
	 * @param array $sanitized    Sanitized field data.
	 * @param array $field_schema Schema map.
	 * @return array Mismatched field names and public message.
	 */
	private static function validate_matching_fields( $sanitized, $field_schema ) {
		$errors         = [];
		$public_message = '';

		foreach ( $field_schema as $field_name => $field_config ) {
			$matches_field = sanitize_key( $field_config['matchesField'] ?? '' );

			if (
				'' === $matches_field ||
				$field_name === $matches_field ||
				! isset( $field_schema[ $matches_field ] ) ||
				! isset( $sanitized[ $field_name ], $sanitized[ $matches_field ] )
			) {
				continue;
			}

			$value        = (string) ( $sanitized[ $field_name ]['value'] ?? '' );
			$target_value = (string) ( $sanitized[ $matches_field ]['value'] ?? '' );

			if ( $value !== $target_value ) {
				$errors[] = $field_name;

				if ( '' === $public_message ) {
					$public_message = self::get_match_public_message(
						$field_name,
						$matches_field,
						$sanitized,
						$field_schema
					);
				}
			}
		}

		return [
			'fields'         => $errors,
			'public_message' => '' !== $public_message
				? $public_message
				: __( 'The matching fields must have the same value.', 'generateblocks-pro' ),
		];
	}

	/**
	 * Build the public message for a failed match rule.
	 *
	 * @param string $field_name    Source field name.
	 * @param string $matches_field Target field name.
	 * @param array  $sanitized     Sanitized field data.
	 * @param array  $field_schema  Schema map.
	 * @return string Public-facing message.
	 */
	private static function get_match_public_message( $field_name, $matches_field, $sanitized, $field_schema ) {
		$field_label  = self::get_field_label( $field_name, $sanitized, $field_schema );
		$target_label = self::get_field_label( $matches_field, $sanitized, $field_schema );

		if ( '' !== $field_label && '' !== $target_label ) {
			return sprintf(
				/* translators: %1$s: source field label, %2$s: target field label. */
				__( 'Please make sure "%1$s" matches "%2$s".', 'generateblocks-pro' ),
				$field_label,
				$target_label
			);
		}

		if ( '' !== $target_label ) {
			return sprintf(
				/* translators: %s: target field label. */
				__( 'This field must match "%s".', 'generateblocks-pro' ),
				$target_label
			);
		}

		return __( 'The matching fields must have the same value.', 'generateblocks-pro' );
	}

	/**
	 * Get a sanitized display label for a schema field.
	 *
	 * @param string $field_name   Field name.
	 * @param array  $sanitized    Sanitized field data.
	 * @param array  $field_schema Schema map.
	 * @return string Field label, or an empty string when unavailable.
	 */
	private static function get_field_label( $field_name, $sanitized, $field_schema ) {
		$label = $sanitized[ $field_name ]['label'] ?? $field_schema[ $field_name ]['label'] ?? '';
		$label = trim( sanitize_text_field( (string) $label ) );

		return $label;
	}

	/**
	 * Sanitize a value for use in email headers.
	 *
	 * Strips newlines and carriage returns to prevent header injection.
	 *
	 * @param string $value The raw value.
	 * @return string Sanitized value safe for email headers.
	 */
	public static function sanitize_email_header( $value ) {
		// Strip all variations of newlines that could be used for header injection.
		$value = preg_replace( '/[\r\n\t]/', '', $value );
		$value = str_replace( [ '%0a', '%0d', '%0A', '%0D' ], '', $value );

		return sanitize_text_field( $value );
	}

	/**
	 * Sanitize a telephone number.
	 *
	 * @param string $value The raw value.
	 * @return string Sanitized telephone number.
	 */
	public static function sanitize_tel( $value ) {
		return preg_replace( '/[^\d\s\+\-\(\)\.]/', '', $value );
	}

	/**
	 * Sanitize a number value.
	 *
	 * Rejects non-numeric input (returns '') instead of coercing to 0 —
	 * storing "0" for "abc" silently corrupts downstream data.
	 *
	 * @param string $value The raw value.
	 * @return string Sanitized number as string, or '' if not numeric.
	 */
	public static function sanitize_number( $value ) {
		if ( '' === $value ) {
			return '';
		}

		if ( ! is_numeric( $value ) ) {
			return '';
		}

		$num = floatval( $value );

		if ( is_infinite( $num ) || is_nan( $num ) ) {
			return '';
		}

		return (string) $num;
	}
}
