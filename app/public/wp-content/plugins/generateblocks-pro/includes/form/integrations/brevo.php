<?php
/**
 * Brevo form action.
 *
 * Subscribes form submissions to a Brevo list.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Brevo form action class.
 */
class GenerateBlocks_Pro_Form_Action_Brevo {

	/**
	 * API base URL.
	 *
	 * @var string
	 */
	const API_BASE = 'https://api.brevo.com/v3';

	/**
	 * Register the provider with the registry.
	 */
	public static function init() {
		generateblocks_pro_register_form_integration(
			[
				'id'                 => 'brevo',
				'label'              => __( 'Brevo', 'generateblocks-pro' ),
				'type'               => 'email',
				'connection'         => [
					'type'          => 'api_key',
					'fields'        => [
						[
							'key'      => 'api_key',
							'label'    => __( 'API Key', 'generateblocks-pro' ),
							'type'     => 'password',
							'help'     => __( 'Find your API key in Brevo under SMTP & API.', 'generateblocks-pro' ),
							'required' => true,
							'define'   => 'GENERATEBLOCKS_PRO_BREVO_API_KEY',
						],
					],
					'test_callback' => function ( $settings ) {
						return self::test_connection( $settings['api_key'] ?? '' );
					},
				],
				'destination'        => [
					'label'            => __( 'List', 'generateblocks-pro' ),
					'empty_label'      => __( '- Select -', 'generateblocks-pro' ),
					'refresh_label'    => __( 'Refresh', 'generateblocks-pro' ),
					'required'         => true,
					'options_callback' => function () {
						return self::fetch_lists( self::get_api_key() );
					},
				],
				'field_map'          => [
					'label'            => __( 'Attributes', 'generateblocks-pro' ),
					'empty_label'      => __( 'Don\'t map', 'generateblocks-pro' ),
					'refresh_label'    => __( 'Refresh attributes', 'generateblocks-pro' ),
					'options_callback' => function () {
						return self::fetch_attributes( self::get_api_key() );
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

		if ( empty( $api_key ) ) {
			return new WP_Error( 'no_api_key', 'Brevo API key not configured.' );
		}

		$list_id_raw = sanitize_text_field( (string) ( $settings['destinationId'] ?? '' ) );

		if ( '' === $list_id_raw ) {
			return new WP_Error( 'no_list', 'Brevo list not selected.' );
		}

		if ( ! preg_match( '/^\d{1,18}$/', $list_id_raw ) ) {
			return new WP_Error( 'invalid_list', 'Invalid Brevo list ID.' );
		}

		$list_id = (int) $list_id_raw;

		$email_field = $settings['emailField'] ?? 'email';
		$email       = $sanitized_data[ $email_field ]['value'] ?? '';

		if ( empty( $email ) || ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', 'Valid email required for Brevo.' );
		}

		$existing_contact = self::fetch_contact_by_email( $email, $api_key );

		if ( is_wp_error( $existing_contact ) ) {
			$error_data = $existing_contact->get_error_data();
			$status     = is_array( $error_data ) && isset( $error_data['status'] )
				? (int) $error_data['status']
				: 0;

			if ( 404 !== $status ) {
				return $existing_contact;
			}
		} elseif ( self::contact_is_unsubscribed( $existing_contact, $list_id ) ) {
			return true;
		}

		$attributes = [];
		$field_map  = $settings['fieldMap'] ?? [];

		foreach ( $field_map as $form_field => $attribute ) {
			$attribute = strtoupper( sanitize_text_field( $attribute ) );

			if ( empty( $attribute ) || ! preg_match( '/^[A-Z0-9_]{1,64}$/', $attribute ) || ! isset( $sanitized_data[ $form_field ]['value'] ) ) {
				continue;
			}

			$value = GenerateBlocks_Pro_Form_Http::normalize_field_value( $sanitized_data[ $form_field ]['value'] );

			if ( '' !== $value ) {
				$attributes[ $attribute ] = $value;
			}
		}

		$body = [
			'email'         => $email,
			'listIds'       => [ $list_id ],
			'updateEnabled' => true,
		];

		if ( ! empty( $attributes ) ) {
			$body['attributes'] = $attributes;
		}

		$response = self::request( 'POST', '/contacts', $api_key, $body );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * Fetch an existing contact by email.
	 *
	 * @param string $email   Contact email address.
	 * @param string $api_key Brevo API key.
	 * @return array|WP_Error
	 */
	private static function fetch_contact_by_email( $email, $api_key ) {
		return self::request(
			'GET',
			'/contacts/' . rawurlencode( $email ) . '?identifierType=email_id',
			$api_key
		);
	}

	/**
	 * Check whether a Brevo contact should not be re-subscribed.
	 *
	 * @param array $contact Contact data from Brevo.
	 * @param int   $list_id Destination list ID.
	 * @return bool
	 */
	private static function contact_is_unsubscribed( $contact, $list_id ) {
		if ( ! is_array( $contact ) ) {
			return false;
		}

		if ( self::is_truthy( $contact['emailBlacklisted'] ?? false ) ) {
			return true;
		}

		$list_unsubscribed = $contact['listUnsubscribed'] ?? [];

		if ( ! is_array( $list_unsubscribed ) ) {
			return false;
		}

		foreach ( $list_unsubscribed as $unsubscribed_list_id ) {
			if ( (int) $unsubscribed_list_id === $list_id ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Interpret common truthy values returned by APIs or stored test fixtures.
	 *
	 * @param mixed $value Value to test.
	 * @return bool
	 */
	private static function is_truthy( $value ) {
		return true === $value || 1 === $value || '1' === $value || 'true' === $value;
	}

	/**
	 * Get the stored API key.
	 *
	 * @return string
	 */
	public static function get_api_key() {
		return GenerateBlocks_Pro_Form_Integration_Rest::get_api_key_for( 'brevo' );
	}

	/**
	 * Fetch contact lists from Brevo.
	 *
	 * @param string $api_key The API key.
	 * @return array|WP_Error
	 */
	public static function fetch_lists( $api_key ) {
		$limit  = 50;
		$offset = 0;
		$lists  = [];
		$more   = true;

		while ( $more ) {
			$response = self::request(
				'GET',
				'/contacts/lists?limit=' . $limit . '&offset=' . $offset . '&sort=asc',
				$api_key
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			if ( empty( $response['lists'] ) || ! is_array( $response['lists'] ) ) {
				break;
			}

			$lists  = array_merge( $lists, $response['lists'] );
			$count  = isset( $response['count'] ) ? (int) $response['count'] : count( $lists );
			$offset = $offset + $limit;
			$more   = count( $lists ) < $count && $offset < 1000;
		}

		return array_map(
			function ( $list ) {
				return [
					'id'    => $list['id'] ?? '',
					'name'  => $list['name'] ?? '',
					'count' => $list['uniqueSubscribers'] ?? null,
				];
			},
			$lists
		);
	}

	/**
	 * Fetch contact attributes from Brevo.
	 *
	 * @param string $api_key The API key.
	 * @return array|WP_Error
	 */
	public static function fetch_attributes( $api_key ) {
		$response = self::request( 'GET', '/contacts/attributes', $api_key );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['attributes'] ) || ! is_array( $response['attributes'] ) ) {
			return [];
		}

		$options = [];

		foreach ( $response['attributes'] as $attribute ) {
			$name     = strtoupper( sanitize_text_field( $attribute['name'] ?? '' ) );
			$category = sanitize_key( $attribute['category'] ?? '' );

			if ( '' === $name || 'EMAIL' === $name || ! in_array( $category, [ 'normal', 'category', 'transactional' ], true ) ) {
				continue;
			}

			$options[] = [
				'value' => $name,
				'label' => $name,
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
		$response = self::request( 'GET', '/account', $api_key );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'connection_failed', $response->get_error_message() );
		}

		return true;
	}

	/**
	 * Shared Brevo request helper.
	 *
	 * @param string     $method  HTTP method.
	 * @param string     $path    API path.
	 * @param string     $api_key API key.
	 * @param array|null $body    Optional JSON body.
	 * @return array|WP_Error
	 */
	private static function request( $method, $path, $api_key, $body = null ) {
		if ( '' === trim( (string) $api_key ) ) {
			return new WP_Error( 'connection_missing', 'Brevo API key is required.' );
		}

		$args = [
			'method'  => $method,
			'timeout' => GenerateBlocks_Pro_Form_Http::DEFAULT_TIMEOUT,
			'headers' => [
				'api-key'      => $api_key,
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json',
			],
		];

		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_safe_remote_request( self::API_BASE . $path, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$error = GenerateBlocks_Pro_Form_Http::response_error( $response, 'Brevo' );

		if ( $error ) {
			return $error;
		}

		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );

		return is_array( $decoded ) ? $decoded : [];
	}

}
