<?php
/**
 * ConvertKit form action.
 *
 * Subscribes form submissions to a ConvertKit form or tag.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ConvertKit form action class.
 */
class GenerateBlocks_Pro_Form_Action_ConvertKit {

	/**
	 * API base URL for v4. Kit v4 API keys authenticate via the
	 * X-Kit-Api-Key header; Bearer is reserved for OAuth tokens.
	 *
	 * @var string
	 */
	const API_BASE = 'https://api.kit.com/v4';

	/**
	 * Register the provider with the registry.
	 */
	public static function init() {
		generateblocks_pro_register_form_integration(
			[
				'id'                  => 'convertkit',
				'label'               => __( 'Kit (ConvertKit)', 'generateblocks-pro' ),
				'type'                => 'email',
				'connection'          => [
					'type'          => 'api_key',
					'fields'        => [
						[
							'key'      => 'api_key',
							'label'    => __( 'API Key', 'generateblocks-pro' ),
							'type'     => 'password',
							'help'     => __( 'Find your v4 API key in Kit under Settings > Developer.', 'generateblocks-pro' ),
							'required' => true,
							'define'   => 'GENERATEBLOCKS_PRO_CONVERTKIT_API_KEY',
						],
					],
					'test_callback' => function ( $settings ) {
						return self::test_connection( $settings['api_key'] ?? '' );
					},
				],
				'destination'         => [
					'label'            => __( 'Form', 'generateblocks-pro' ),
					'empty_label'      => __( '- Select -', 'generateblocks-pro' ),
					'refresh_label'    => __( 'Refresh', 'generateblocks-pro' ),
					'required'         => true,
					'options_callback' => function () {
						return self::fetch_forms( self::get_api_key() );
					},
				],
				'field_map'           => [
					'label'            => __( 'Fields', 'generateblocks-pro' ),
					'empty_label'      => __( 'Don\'t map', 'generateblocks-pro' ),
					'refresh_label'    => __( 'Refresh fields', 'generateblocks-pro' ),
					'options_callback' => function () {
						return self::fetch_custom_fields( self::get_api_key() );
					},
				],
				'subscribe_callback'  => [ __CLASS__, 'subscribe' ],
			]
		);
	}

	/**
	 * Subscribe using the generic email-signup settings shape.
	 *
	 * @param array $sanitized_data The sanitized form data.
	 * @param array $settings       Email signup settings.
	 * @param array $context        Request context.
	 * @return true|WP_Error
	 */
	public static function subscribe( $sanitized_data, $settings, $context = [] ) {
		$api_key = self::get_api_key();

		if ( empty( $api_key ) ) {
			return new WP_Error( 'no_api_key', 'ConvertKit API key not configured.' );
		}

		$form_id = sanitize_text_field( (string) ( $settings['destinationId'] ?? '' ) );

		if ( '' === $form_id ) {
			return new WP_Error( 'no_form', 'ConvertKit form not selected.' );
		}

		if ( ! preg_match( '/^\d{1,20}$/', $form_id ) ) {
			return new WP_Error( 'invalid_form', 'Invalid ConvertKit form ID.' );
		}

		$email_field = $settings['emailField'] ?? 'email';
		$email       = $sanitized_data[ $email_field ]['value'] ?? '';

		if ( empty( $email ) || ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', 'Valid email required for ConvertKit.' );
		}

		$body = [
			'email_address' => $email,
		];

		// Map subscriber fields.
		$field_map = $settings['fieldMap'] ?? [];
		$fields    = [];

		foreach ( $field_map as $form_field => $ck_field ) {
			$ck_field = sanitize_text_field( $ck_field );

			if ( empty( $ck_field ) || ! preg_match( '/^[A-Za-z0-9_]{1,100}$/', $ck_field ) || ! isset( $sanitized_data[ $form_field ]['value'] ) ) {
				continue;
			}

			$value = GenerateBlocks_Pro_Form_Http::normalize_field_value( $sanitized_data[ $form_field ]['value'] );

			if ( '' !== $value ) {
				if ( 'first_name' === $ck_field ) {
					$body['first_name'] = $value;
				} else {
					$fields[ $ck_field ] = $value;
				}
			}
		}

		if ( ! empty( $fields ) ) {
			$body['fields'] = $fields;
		}

		// Kit v4 keeps subscriber profile updates separate from form association:
		// first create/update the subscriber, then add that subscriber to the
		// selected form with the required referrer payload.
		$create = self::request( 'POST', '/subscribers', $api_key, $body );

		if ( is_wp_error( $create ) ) {
			return $create;
		}

		$subscriber_id = $create['subscriber']['id'] ?? 0;

		if ( ! is_scalar( $subscriber_id ) || ! preg_match( '/^\d{1,20}$/', (string) $subscriber_id ) ) {
			return new WP_Error( 'convertkit_error', 'ConvertKit: subscriber creation returned invalid id.' );
		}

		$assoc = self::request(
			'POST',
			'/forms/' . $form_id . '/subscribers/' . (string) $subscriber_id,
			$api_key,
			[
				'referrer' => self::get_referrer( $context ),
			]
		);

		if ( is_wp_error( $assoc ) ) {
			return $assoc;
		}

		return true;
	}

	/**
	 * Get a safe referrer value for Kit form association.
	 *
	 * @param array $context Request context.
	 * @return string
	 */
	private static function get_referrer( $context ) {
		$page_url = isset( $context['page_url'] ) && is_scalar( $context['page_url'] )
			? trim( (string) $context['page_url'] )
			: '';

		return filter_var( $page_url, FILTER_VALIDATE_URL ) ? $page_url : '';
	}

	/**
	 * Shared Kit v4 request helper.
	 *
	 * Normalizes auth, error parsing, and logging across Kit endpoints.
	 *
	 * @param string     $method HTTP method (GET/POST).
	 * @param string     $path   Path beneath API_BASE (e.g. /subscribers).
	 * @param string     $key    API key.
	 * @param array|null $body   Optional JSON body.
	 * @return array|WP_Error Decoded JSON body on success, WP_Error on failure.
	 */
	private static function request( $method, $path, $key, $body = null ) {
		$endpoint = self::API_BASE . $path;

		$args = [
			'method'  => $method,
			'timeout' => GenerateBlocks_Pro_Form_Http::DEFAULT_TIMEOUT,
			'headers' => [
				'X-Kit-Api-Key' => $key,
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
			],
		];

		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_safe_remote_request( $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status   = wp_remote_retrieve_response_code( $response );
		$raw_body = wp_remote_retrieve_body( $response );
		$decoded  = json_decode( $raw_body, true );

		if ( $status >= 200 && $status < 300 ) {
			return is_array( $decoded ) ? $decoded : [];
		}

		$detail = '';

		if ( is_array( $decoded ) ) {
			if ( ! empty( $decoded['errors'] ) && is_array( $decoded['errors'] ) ) {
				$detail = implode( '; ', array_filter( array_map( 'strval', $decoded['errors'] ) ) );
			} elseif ( ! empty( $decoded['error'] ) && is_string( $decoded['error'] ) ) {
				$detail = $decoded['error'];
			} elseif ( ! empty( $decoded['message'] ) && is_string( $decoded['message'] ) ) {
				$detail = $decoded['message'];
			}
		}

		if ( '' === $detail ) {
			$detail = 'HTTP ' . $status;
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// Log status + parsed error detail only. Raw body would echo the
			// submitted email back into the PHP error log on every auth/validation
			// failure.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'GenerateBlocks Form ConvertKit ' . $method . ' ' . $path . ' (status ' . $status . '): ' . sanitize_text_field( $detail ) );
		}

		return new WP_Error( 'convertkit_error', 'ConvertKit: ' . sanitize_text_field( $detail ) );
	}

	/**
	 * Get the stored API key.
	 *
	 * @return string
	 */
	public static function get_api_key() {
		return GenerateBlocks_Pro_Form_Integration_Rest::get_api_key_for( 'convertkit' );
	}

	/**
	 * Fetch forms from ConvertKit.
	 *
	 * @param string $api_key The API key.
	 * @return array|WP_Error
	 */
	public static function fetch_forms( $api_key ) {
		$body = self::request( 'GET', '/forms', $api_key );

		if ( is_wp_error( $body ) ) {
			return $body;
		}

		if ( empty( $body['forms'] ) || ! is_array( $body['forms'] ) ) {
			return [];
		}

		// Drop Kit's auto-created system forms ("Creator Network", "Creator
		// Profile", and similar variants). They are surfaced by /v4/forms but
		// cannot be used as subscribe targets. Substring match on the name so
		// minor wording changes ("Creator Network Recommendations" vs just
		// "Creator Network") still catch.
		$blocked_name_fragments = [ 'creator network', 'creator profile' ];

		$forms = array_filter(
			$body['forms'],
			function ( $form ) use ( $blocked_name_fragments ) {
				$name = strtolower( (string) ( $form['name'] ?? '' ) );

				foreach ( $blocked_name_fragments as $fragment ) {
					if ( false !== strpos( $name, $fragment ) ) {
						return false;
					}
				}

				return true;
			}
		);

		return array_values(
			array_map(
				function ( $form ) {
					return [
						'id'   => $form['id'],
						'name' => $form['name'],
					];
				},
				$forms
			)
		);
	}

	/**
	 * Fetch custom fields from ConvertKit.
	 *
	 * @param string $api_key The API key.
	 * @return array|WP_Error
	 */
	public static function fetch_custom_fields( $api_key ) {
		$response = self::request( 'GET', '/custom_fields?per_page=1000', $api_key );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$options = [
			[
				'value' => 'first_name',
				'label' => __( 'First name (first_name)', 'generateblocks-pro' ),
			],
		];

		if ( empty( $response['custom_fields'] ) || ! is_array( $response['custom_fields'] ) ) {
			return $options;
		}

		foreach ( $response['custom_fields'] as $field ) {
			$key   = sanitize_text_field( $field['key'] ?? '' );
			$label = sanitize_text_field( $field['label'] ?? $key );

			if ( '' === $key || 'first_name' === $key || ! preg_match( '/^[A-Za-z0-9_]{1,100}$/', $key ) ) {
				continue;
			}

			$options[] = [
				'value' => $key,
				'label' => $label . ' (' . $key . ')',
			];
		}

		return $options;
	}

	/**
	 * Test the API connection.
	 *
	 * @param string $api_key The API key.
	 * @return true|WP_Error
	 */
	public static function test_connection( $api_key ) {
		$response = wp_safe_remote_get(
			self::API_BASE . '/account',
			[
				'timeout' => GenerateBlocks_Pro_Form_Http::DEFAULT_TIMEOUT,
				'headers' => [
					'X-Kit-Api-Key' => $api_key,
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'connection_failed', 'Could not connect to ConvertKit. Check your API key.' );
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'auth_failed', 'Invalid Kit API key. Kit v4 API keys are required — legacy v3 "API Secret" values will not authenticate.' );
		}

		return true;
	}

}
