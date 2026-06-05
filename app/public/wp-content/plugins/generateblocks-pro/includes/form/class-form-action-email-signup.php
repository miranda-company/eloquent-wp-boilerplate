<?php
/**
 * Email signup form action.
 *
 * Routes a submission to the selected email marketing provider.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email signup action class.
 */
class GenerateBlocks_Pro_Form_Action_Email_Signup {

	/**
	 * Registered action name.
	 */
	const ACTION = 'email-signup';

	/**
	 * Register the action with the action registry.
	 */
	public static function init() {
		GenerateBlocks_Pro_Form_Action_Registry::register( self::ACTION, [ __CLASS__, 'handle' ] );
	}

	/**
	 * Handle an email signup submission.
	 *
	 * @param array $sanitized_data The sanitized form data.
	 * @param array $form_settings  The form block settings.
	 * @param array $context        Request context.
	 * @return true|WP_Error
	 */
	public static function handle( $sanitized_data, $form_settings, $context = [] ) {
		$settings   = self::normalize_settings( self::get_settings( $form_settings ) );
		$validation = self::validate_settings( $settings );

		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$submission_check = self::validate_submission( $sanitized_data, $settings );

		if ( is_wp_error( $submission_check ) ) {
			return $submission_check;
		}

		$provider = GenerateBlocks_Pro_Form_Integration_Registry::get_provider( $settings['provider'] );
		$callback = $provider['subscribe_callback'] ?? null;

		if ( ! is_callable( $callback ) ) {
			return new WP_Error( 'email_signup_unavailable', 'Selected email signup provider is unavailable.' );
		}

		return call_user_func( $callback, $sanitized_data, $settings, $context );
	}

	/**
	 * Extract email-signup settings from form config.
	 *
	 * @param array $form_settings Form settings.
	 * @return array
	 */
	private static function get_settings( $form_settings ) {
		return isset( $form_settings['actionSettings'][ self::ACTION ] ) && is_array( $form_settings['actionSettings'][ self::ACTION ] )
			? $form_settings['actionSettings'][ self::ACTION ]
			: [];
	}

	/**
	 * Normalize email-signup settings before validation/dispatch.
	 *
	 * @param array $settings Email signup settings.
	 * @return array
	 */
	private static function normalize_settings( $settings ) {
		$settings = is_array( $settings ) ? $settings : [];

		$settings['provider'] = isset( $settings['provider'] ) ? sanitize_key( $settings['provider'] ) : '';

		if ( isset( $settings['emailField'] ) && ( is_array( $settings['emailField'] ) || is_object( $settings['emailField'] ) ) ) {
			$settings['emailField'] = '';
		}

		$email_field = isset( $settings['emailField'] ) ? sanitize_key( $settings['emailField'] ) : '';

		$settings['emailField'] = '' !== $email_field ? $email_field : 'email';

		if ( isset( $settings['nameField'] ) && ( is_array( $settings['nameField'] ) || is_object( $settings['nameField'] ) ) ) {
			$settings['nameField'] = '';
		}

		$settings['nameField'] = isset( $settings['nameField'] ) ? sanitize_key( $settings['nameField'] ) : '';

		return $settings;
	}

	/**
	 * Validate the provider-level email signup settings.
	 *
	 * Provider callbacks still validate provider-specific ID shapes and field
	 * maps before making external requests.
	 *
	 * @param array      $settings Email signup settings.
	 * @param array|null $schema   Optional form field schema list.
	 * @return true|WP_Error
	 */
	public static function validate_settings( $settings, $schema = null ) {
		$settings    = self::normalize_settings( $settings );
		$provider_id = $settings['provider'];

		if ( '' === $provider_id ) {
			return new WP_Error(
				'gb_form_email_signup_provider_missing',
				__( 'Email signup service is required.', 'generateblocks-pro' ),
				[ 'status' => 400 ]
			);
		}

		$provider = GenerateBlocks_Pro_Form_Integration_Registry::get_provider( $provider_id );

		if ( ! $provider || 'email' !== $provider['type'] ) {
			return new WP_Error(
				'gb_form_email_signup_provider_invalid',
				__( 'Selected email signup service is unavailable.', 'generateblocks-pro' ),
				[ 'status' => 400 ]
			);
		}

		if ( ! is_callable( $provider['subscribe_callback'] ?? null ) ) {
			return new WP_Error(
				'gb_form_email_signup_provider_unavailable',
				__( 'Selected email signup service cannot process submissions.', 'generateblocks-pro' ),
				[ 'status' => 400 ]
			);
		}

		$connection_check = self::validate_provider_connection( $provider_id, $provider );

		if ( is_wp_error( $connection_check ) ) {
			return $connection_check;
		}

		$destination = $settings['destinationId'] ?? '';

		if (
			! empty( $provider['destination']['required'] )
			&& ( is_array( $destination ) || is_object( $destination ) || '' === trim( (string) $destination ) )
		) {
			return new WP_Error(
				'gb_form_email_signup_destination_missing',
				sprintf(
					/* translators: %s: destination label, e.g. Audience/List/Form. */
					__( 'Email signup %s is required.', 'generateblocks-pro' ),
					$provider['destination']['label'] ? strtolower( $provider['destination']['label'] ) : __( 'destination', 'generateblocks-pro' )
				),
				[ 'status' => 400 ]
			);
		}

		$secondary_destination = $settings['secondaryDestinationId'] ?? '';

		if (
			! empty( $provider['secondary_destination']['required'] )
			&& ( is_array( $secondary_destination ) || is_object( $secondary_destination ) || '' === trim( (string) $secondary_destination ) )
		) {
			return new WP_Error(
				'gb_form_email_signup_secondary_destination_missing',
				sprintf(
					/* translators: %s: secondary destination label, e.g. Tag. */
					__( 'Email signup %s is required.', 'generateblocks-pro' ),
					$provider['secondary_destination']['label'] ? strtolower( $provider['secondary_destination']['label'] ) : __( 'secondary destination', 'generateblocks-pro' )
				),
				[ 'status' => 400 ]
			);
		}

		if ( is_array( $schema ) ) {
			$email_field = $settings['emailField'];
			$field       = self::find_schema_field( $schema, $email_field );

			if ( ! $field ) {
				return new WP_Error(
					'gb_form_email_signup_email_field_missing',
					sprintf(
						/* translators: %s: email field name. */
						__( 'Email signup email field "%s" does not exist.', 'generateblocks-pro' ),
						$email_field
					),
					[ 'status' => 400 ]
				);
			}

			$field_type = $field['type'] ?? '';

			if ( ! in_array( $field_type, [ 'email', 'text' ], true ) ) {
				return new WP_Error(
					'gb_form_email_signup_email_field_invalid',
					sprintf(
						/* translators: %s: email field name. */
						__( 'Email signup email field "%s" must be an email or text field.', 'generateblocks-pro' ),
						$email_field
					),
					[ 'status' => 400 ]
				);
			}

			if ( ! empty( $provider['supports_name_field'] ) && '' !== $settings['nameField'] ) {
				$name_field = self::find_schema_field( $schema, $settings['nameField'] );

				if ( ! $name_field ) {
					return new WP_Error(
						'gb_form_email_signup_name_field_missing',
						sprintf(
							/* translators: %s: name field name. */
							__( 'Email signup name field "%s" does not exist.', 'generateblocks-pro' ),
							$settings['nameField']
						),
						[ 'status' => 400 ]
					);
				}

				if ( 'text' !== ( $name_field['type'] ?? '' ) ) {
					return new WP_Error(
						'gb_form_email_signup_name_field_invalid',
						sprintf(
							/* translators: %s: name field name. */
							__( 'Email signup name field "%s" must be a text field.', 'generateblocks-pro' ),
							$settings['nameField']
						),
						[ 'status' => 400 ]
					);
				}
			}
		}

		return true;
	}

	/**
	 * Validate the submitted email signup data before provider dispatch.
	 *
	 * @param array $sanitized_data The sanitized form data.
	 * @param array $settings       Email signup settings.
	 * @return true|WP_Error
	 */
	public static function validate_submission( $sanitized_data, $settings ) {
		$settings    = self::normalize_settings( $settings );
		$email_field = $settings['emailField'];

		if ( ! isset( $sanitized_data[ $email_field ] ) ) {
			return new WP_Error(
				'gb_form_email_signup_email_missing',
				__( 'Email signup requires a valid email address.', 'generateblocks-pro' ),
				[ 'status' => 400 ]
			);
		}

		$email = (string) ( $sanitized_data[ $email_field ]['value'] ?? '' );

		if ( ! is_email( $email ) ) {
			return new WP_Error(
				'gb_form_email_signup_email_invalid',
				__( 'Email signup requires a valid email address.', 'generateblocks-pro' ),
				[ 'status' => 400 ]
			);
		}

		return true;
	}

	/**
	 * Validate that the selected provider is connected before publishing or
	 * dispatching. Provider-specific handlers still validate request payloads.
	 *
	 * @param string $provider_id Provider ID.
	 * @param array  $provider    Provider definition.
	 * @return true|WP_Error
	 */
	private static function validate_provider_connection( $provider_id, $provider ) {
		$connection = $provider['connection'] ?? [];

		if ( empty( $connection ) || 'none' === ( $connection['type'] ?? 'none' ) ) {
			return true;
		}

		if ( is_callable( $connection['connected_callback'] ?? null ) ) {
			$result = call_user_func( $connection['connected_callback'], $provider_id );

			if ( is_wp_error( $result ) ) {
				return new WP_Error(
					'gb_form_email_signup_provider_not_connected',
					$result->get_error_message(),
					[ 'status' => 400 ]
				);
			}

			if ( ! $result ) {
				return new WP_Error(
					'gb_form_email_signup_provider_not_connected',
					__( 'Selected email signup service is not connected.', 'generateblocks-pro' ),
					[ 'status' => 400 ]
				);
			}
		}

		foreach ( $connection['fields'] ?? [] as $field ) {
			if ( empty( $field['required'] ) ) {
				continue;
			}

			$value = GenerateBlocks_Pro_Form_Integration_Rest::get_connection_setting_for( $provider_id, $field['key'] );

			if ( '' === trim( (string) $value ) ) {
				return new WP_Error(
					'gb_form_email_signup_provider_not_connected',
					sprintf(
						/* translators: %s: connection setting label */
						__( 'Email signup service is not connected. %s is required.', 'generateblocks-pro' ),
						$field['label']
					),
					[ 'status' => 400 ]
				);
			}
		}

		return true;
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
