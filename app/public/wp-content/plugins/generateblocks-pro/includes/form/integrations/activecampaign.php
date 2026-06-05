<?php
/**
 * ActiveCampaign form action.
 *
 * Subscribes form submissions to an ActiveCampaign list.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ActiveCampaign form action class.
 */
class GenerateBlocks_Pro_Form_Action_ActiveCampaign {

	/**
	 * Register the provider with the registry.
	 */
	public static function init() {
		generateblocks_pro_register_form_integration(
			[
				'id'                 => 'activecampaign',
				'label'              => __( 'ActiveCampaign', 'generateblocks-pro' ),
				'type'               => 'email',
				'connection'         => [
					'type'          => 'api_key',
					'fields'        => [
						[
							'key'         => 'api_url',
							'label'       => __( 'API URL', 'generateblocks-pro' ),
							'type'        => 'url',
							'placeholder' => 'https://youraccount.api-us1.com',
							'help'        => __( 'Paste the account-specific API URL from ActiveCampaign under Settings > Developer. It should look like https://youraccount.api-us1.com.', 'generateblocks-pro' ),
							'required'    => true,
							'define'      => 'GENERATEBLOCKS_PRO_ACTIVECAMPAIGN_API_URL',
						],
						[
							'key'      => 'api_key',
							'label'    => __( 'API Key', 'generateblocks-pro' ),
							'type'     => 'password',
							'help'     => __( 'Find your API key in ActiveCampaign under Settings > Developer.', 'generateblocks-pro' ),
							'required' => true,
							'define'   => 'GENERATEBLOCKS_PRO_ACTIVECAMPAIGN_API_KEY',
						],
					],
					'test_callback' => function ( $settings ) {
						return self::test_connection(
							$settings['api_key'] ?? '',
							$settings['api_url'] ?? ''
						);
					},
				],
				'destination'        => [
					'label'            => __( 'List', 'generateblocks-pro' ),
					'empty_label'      => __( '- Select -', 'generateblocks-pro' ),
					'refresh_label'    => __( 'Refresh', 'generateblocks-pro' ),
					'required'         => true,
					'options_callback' => function () {
						return self::fetch_lists( self::get_api_key(), self::get_api_url() );
					},
				],
				'field_map'          => [
					'label'            => __( 'Fields', 'generateblocks-pro' ),
					'empty_label'      => __( 'Don\'t map', 'generateblocks-pro' ),
					'refresh_label'    => __( 'Refresh fields', 'generateblocks-pro' ),
					'options_callback' => function () {
						return self::fetch_fields( self::get_api_key(), self::get_api_url() );
					},
				],
				'subscribe_callback' => [ __CLASS__, 'subscribe' ],
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
		unset( $context );

		$api_key = self::get_api_key();
		$api_url = self::get_api_url();

		if ( empty( $api_key ) || empty( $api_url ) ) {
			return new WP_Error( 'no_connection', 'ActiveCampaign API URL and key are not configured.' );
		}

		$list_id = sanitize_text_field( $settings['destinationId'] ?? '' );

		if ( empty( $list_id ) ) {
			return new WP_Error( 'no_list', 'ActiveCampaign list not selected.' );
		}

		if ( ! preg_match( '/^\d{1,20}$/', $list_id ) ) {
			return new WP_Error( 'invalid_list', 'Invalid ActiveCampaign list ID.' );
		}

		$email_field = $settings['emailField'] ?? 'email';
		$email       = $sanitized_data[ $email_field ]['value'] ?? '';

		if ( empty( $email ) || ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', 'Valid email required for ActiveCampaign.' );
		}

		$contact = [
			'email' => $email,
		];

		$field_values = [];
		$field_map    = $settings['fieldMap'] ?? [];

		foreach ( $field_map as $form_field => $activecampaign_field ) {
			$activecampaign_field = sanitize_text_field( $activecampaign_field );

			if ( empty( $activecampaign_field ) || ! isset( $sanitized_data[ $form_field ]['value'] ) ) {
				continue;
			}

			$value = GenerateBlocks_Pro_Form_Http::normalize_field_value( $sanitized_data[ $form_field ]['value'] );

			if ( '' === $value ) {
				continue;
			}

			if ( in_array( $activecampaign_field, [ 'firstName', 'lastName', 'phone' ], true ) ) {
				$contact[ $activecampaign_field ] = $value;
			} elseif ( preg_match( '/^\d{1,20}$/', $activecampaign_field ) ) {
				$field_values[] = [
					'field' => $activecampaign_field,
					'value' => $value,
				];
			}
		}

		if ( ! empty( $field_values ) ) {
			$contact['fieldValues'] = $field_values;
		}

		$sync = self::request(
			'POST',
			'/contact/sync',
			$api_key,
			$api_url,
			[
				'contact' => $contact,
			]
		);

		if ( is_wp_error( $sync ) ) {
			return $sync;
		}

		$contact_id = $sync['contact']['id'] ?? 0;

		if ( ! preg_match( '/^\d{1,20}$/', (string) $contact_id ) ) {
			return new WP_Error( 'activecampaign_error', 'ActiveCampaign: contact sync returned no contact ID.' );
		}

		return self::ensure_list_membership( $api_key, $api_url, (string) $contact_id, $list_id );
	}

	/**
	 * Get the stored API key.
	 *
	 * @return string
	 */
	public static function get_api_key() {
		return GenerateBlocks_Pro_Form_Integration_Rest::get_api_key_for( 'activecampaign' );
	}

	/**
	 * Get the stored API URL.
	 *
	 * @return string
	 */
	public static function get_api_url() {
		return GenerateBlocks_Pro_Form_Integration_Rest::get_connection_setting_for( 'activecampaign', 'api_url' );
	}

	/**
	 * Fetch lists from ActiveCampaign.
	 *
	 * @param string $api_key The API key.
	 * @param string $api_url The account API URL.
	 * @return array|WP_Error
	 */
	public static function fetch_lists( $api_key, $api_url ) {
		$response = self::request( 'GET', '/lists?limit=100', $api_key, $api_url );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['lists'] ) || ! is_array( $response['lists'] ) ) {
			return [];
		}

		return array_map(
			function ( $list ) {
				return [
					'id'   => $list['id'] ?? '',
					'name' => $list['name'] ?? '',
				];
			},
			$response['lists']
		);
	}

	/**
	 * Fetch standard and custom contact fields from ActiveCampaign.
	 *
	 * @param string $api_key The API key.
	 * @param string $api_url The account API URL.
	 * @return array|WP_Error
	 */
	public static function fetch_fields( $api_key, $api_url ) {
		$options = [
			[
				'value' => 'firstName',
				'label' => __( 'First name', 'generateblocks-pro' ),
			],
			[
				'value' => 'lastName',
				'label' => __( 'Last name', 'generateblocks-pro' ),
			],
			[
				'value' => 'phone',
				'label' => __( 'Phone', 'generateblocks-pro' ),
			],
		];

		$response = self::request( 'GET', '/fields?limit=100', $api_key, $api_url );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['fields'] ) || ! is_array( $response['fields'] ) ) {
			return $options;
		}

		foreach ( $response['fields'] as $field ) {
			$id    = $field['id'] ?? '';
			$title = $field['title'] ?? '';

			if ( ! preg_match( '/^\d{1,20}$/', (string) $id ) || '' === $title ) {
				continue;
			}

			$options[] = [
				'value' => (string) $id,
				'label' => $title,
			];
		}

		return $options;
	}

	/**
	 * Test the API connection.
	 *
	 * @param string $api_key The API key.
	 * @param string $api_url The account API URL.
	 * @return true|WP_Error
	 */
	public static function test_connection( $api_key, $api_url ) {
		$response = self::request( 'GET', '/users/me', $api_key, $api_url );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'connection_failed', $response->get_error_message() );
		}

		return true;
	}

	/**
	 * Subscribe a contact to the selected list without re-subscribing opt-outs.
	 *
	 * @param string $api_key    The API key.
	 * @param string $api_url    The account API URL.
	 * @param string $contact_id ActiveCampaign contact ID.
	 * @param string $list_id    ActiveCampaign list ID.
	 * @return true|WP_Error
	 */
	private static function ensure_list_membership( $api_key, $api_url, $contact_id, $list_id ) {
		$memberships = self::request( 'GET', '/contacts/' . $contact_id . '/contactLists', $api_key, $api_url );

		if ( is_wp_error( $memberships ) ) {
			return $memberships;
		}

		foreach ( $memberships['contactLists'] ?? [] as $membership ) {
			if ( (string) ( $membership['list'] ?? '' ) !== $list_id ) {
				continue;
			}

			if ( '1' === (string) ( $membership['status'] ?? '' ) ) {
				return true;
			}

			return new WP_Error( 'activecampaign_unsubscribed', 'ActiveCampaign: contact is not active on this list.' );
		}

		$subscribe = self::request(
			'POST',
			'/contactLists',
			$api_key,
			$api_url,
			[
				'contactList' => [
					'list'    => $list_id,
					'contact' => $contact_id,
					'status'  => 1,
				],
			]
		);

		if ( is_wp_error( $subscribe ) ) {
			return $subscribe;
		}

		return true;
	}

	/**
	 * Shared ActiveCampaign request helper.
	 *
	 * @param string     $method  HTTP method.
	 * @param string     $path    API path.
	 * @param string     $api_key API key.
	 * @param string     $api_url Account API URL.
	 * @param array|null $body    Optional JSON body.
	 * @return array|WP_Error
	 */
	private static function request( $method, $path, $api_key, $api_url, $body = null ) {
		$base = self::normalize_api_url( $api_url );

		if ( '' === $base || '' === trim( (string) $api_key ) ) {
			return new WP_Error( 'connection_missing', 'ActiveCampaign API URL and key are required.' );
		}

		$args = [
			'method'  => $method,
			'timeout' => GenerateBlocks_Pro_Form_Http::DEFAULT_TIMEOUT,
			'headers' => [
				'Api-Token'    => $api_key,
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json',
			],
		];

		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_safe_remote_request( $base . '/' . ltrim( $path, '/' ), $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$error = GenerateBlocks_Pro_Form_Http::response_error( $response, 'ActiveCampaign' );

		if ( $error ) {
			return $error;
		}

		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );

		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Normalize an ActiveCampaign account URL.
	 *
	 * @param string $api_url Raw URL.
	 * @return string Base API URL, including /api/3.
	 */
	private static function normalize_api_url( $api_url ) {
		$api_url = trim( (string) $api_url );

		if ( '' === $api_url || ! preg_match( '#^https://#i', $api_url ) ) {
			return '';
		}

		$api_url = rtrim( $api_url, "/ \t\n\r\0\x0B" );
		$parts   = wp_parse_url( $api_url );

		if (
			! is_array( $parts )
			|| empty( $parts['host'] )
			|| ! empty( $parts['user'] )
			|| ! empty( $parts['pass'] )
			|| ! empty( $parts['query'] )
			|| ! empty( $parts['fragment'] )
		) {
			return '';
		}

		$path = $parts['path'] ?? '';

		if ( ! in_array( $path, [ '', '/', '/api/3', '/api/3/' ], true ) ) {
			return '';
		}

		$base = 'https://' . $parts['host'];

		if ( ! empty( $parts['port'] ) ) {
			$base .= ':' . (int) $parts['port'];
		}

		if ( ! filter_var( $base, FILTER_VALIDATE_URL ) ) {
			return '';
		}

		return $base . '/api/3';
	}

}
