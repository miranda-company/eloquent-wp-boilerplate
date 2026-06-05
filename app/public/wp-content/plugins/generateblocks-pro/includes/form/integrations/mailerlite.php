<?php
/**
 * MailerLite form action.
 *
 * Subscribes form submissions to a MailerLite group.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MailerLite form action class.
 */
class GenerateBlocks_Pro_Form_Action_MailerLite {

	/**
	 * API base URL.
	 *
	 * @var string
	 */
	const API_BASE = 'https://connect.mailerlite.com/api';

	/**
	 * Shared request args for MailerLite.
	 *
	 * MailerLite's edge (Cloudflare) rejects WP's default `WordPress/X.Y; <url>`
	 * User-Agent on some endpoints (notably /groups) with a 403 HTML page, while
	 * others (/subscribers) pass. Set an explicit identifying UA so every call
	 * gets through consistently.
	 *
	 * @param string $api_key API key.
	 * @param array  $extra_headers Additional headers to merge.
	 * @return array
	 */
	private static function request_args( $api_key, $extra_headers = [] ) {
		return [
			'timeout'    => GenerateBlocks_Pro_Form_Http::DEFAULT_TIMEOUT,
			'user-agent' => 'GenerateBlocks-Pro/' . ( defined( 'GENERATEBLOCKS_PRO_VERSION' ) ? GENERATEBLOCKS_PRO_VERSION : '0.0.0' ),
			'headers'    => array_merge(
				[
					'Authorization' => 'Bearer ' . $api_key,
					'Accept'        => 'application/json',
				],
				$extra_headers
			),
		];
	}

	/**
	 * Register the provider with the registry.
	 */
	public static function init() {
		generateblocks_pro_register_form_integration(
			[
				'id'                 => 'mailerlite',
				'label'              => __( 'MailerLite', 'generateblocks-pro' ),
				'type'               => 'email',
				'connection'         => [
					'type'          => 'api_key',
					'fields'        => [
						[
							'key'      => 'api_key',
							'label'    => __( 'API Key', 'generateblocks-pro' ),
							'type'     => 'password',
							'help'     => __( 'Find your API token in MailerLite under Integrations > API.', 'generateblocks-pro' ),
							'required' => true,
							'define'   => 'GENERATEBLOCKS_PRO_MAILERLITE_API_KEY',
						],
					],
					'test_callback' => function ( $settings ) {
						return self::test_connection( $settings['api_key'] ?? '' );
					},
				],
				'destination'        => [
					'label'            => __( 'Group', 'generateblocks-pro' ),
					'empty_label'      => __( '- Select -', 'generateblocks-pro' ),
					'refresh_label'    => __( 'Refresh', 'generateblocks-pro' ),
					'required'         => true,
					'options_callback' => function () {
						return self::fetch_groups( self::get_api_key() );
					},
				],
				'field_map'          => [
					'label'            => __( 'Fields', 'generateblocks-pro' ),
					'empty_label'      => __( 'Don\'t map', 'generateblocks-pro' ),
					'refresh_label'    => __( 'Refresh fields', 'generateblocks-pro' ),
					'options_callback' => function () {
						return self::fetch_fields( self::get_api_key() );
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
			return new WP_Error( 'no_api_key', 'MailerLite API key not configured.' );
		}

		$group_id = sanitize_text_field( $settings['destinationId'] ?? '' );

		if ( empty( $group_id ) ) {
			return new WP_Error( 'no_group', 'MailerLite group not selected.' );
		}

		// MailerLite group IDs are numeric strings — validate to prevent path manipulation.
		if ( ! preg_match( '/^\d{1,20}$/', $group_id ) ) {
			return new WP_Error( 'invalid_group', 'Invalid MailerLite group ID.' );
		}

		$email_field = $settings['emailField'] ?? 'email';
		$email       = $sanitized_data[ $email_field ]['value'] ?? '';

		if ( empty( $email ) || ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', 'Valid email required for MailerLite.' );
		}

		$body = [
			'email' => $email,
		];

		// Map name fields.
		$field_map = $settings['fieldMap'] ?? [];
		$fields    = [];

		foreach ( $field_map as $form_field => $ml_field ) {
			$ml_field = sanitize_text_field( $ml_field );

			if ( empty( $ml_field ) || ! preg_match( '/^[A-Za-z0-9_]{1,64}$/', $ml_field ) || ! isset( $sanitized_data[ $form_field ]['value'] ) ) {
				continue;
			}

			$value = GenerateBlocks_Pro_Form_Http::normalize_field_value( $sanitized_data[ $form_field ]['value'] );

			if ( '' !== $value ) {
				$fields[ $ml_field ] = $value;
			}
		}

		if ( ! empty( $fields ) ) {
			$body['fields'] = $fields;
		}

		$body['groups'] = [ $group_id ];

		$response = wp_safe_remote_post(
			self::API_BASE . '/subscribers',
			array_merge(
				self::request_args( $api_key, [ 'Content-Type' => 'application/json' ] ),
				[ 'body' => wp_json_encode( $body ) ]
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		// 200 = updated existing, 201 = new subscriber.
		if ( ! in_array( $status_code, [ 200, 201 ], true ) ) {
			$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

			return new WP_Error(
				'mailerlite_error',
				'MailerLite: ' . sanitize_text_field( $response_body['message'] ?? 'Unknown error' )
			);
		}

		return true;
	}

	/**
	 * Get the stored API key.
	 *
	 * @return string
	 */
	public static function get_api_key() {
		return GenerateBlocks_Pro_Form_Integration_Rest::get_api_key_for( 'mailerlite' );
	}

	/**
	 * Fetch groups from MailerLite.
	 *
	 * @param string $api_key The API key.
	 * @return array|WP_Error
	 */
	public static function fetch_groups( $api_key ) {
		$response = wp_safe_remote_get(
			self::API_BASE . '/groups?limit=100&sort=name',
			self::request_args( $api_key )
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$error = GenerateBlocks_Pro_Form_Http::response_error( $response, 'MailerLite' );

		if ( $error ) {
			return $error;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['data'] ) ) {
			return [];
		}

		return array_map(
			function ( $group ) {
				return [
					'id'    => $group['id'],
					'name'  => $group['name'],
					'count' => $group['active_count'] ?? 0,
				];
			},
			$body['data']
		);
	}

	/**
	 * Fetch fields from MailerLite.
	 *
	 * @param string $api_key The API key.
	 * @return array|WP_Error
	 */
	public static function fetch_fields( $api_key ) {
		$response = wp_safe_remote_get(
			self::API_BASE . '/fields?limit=100&sort=name',
			self::request_args( $api_key )
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$error = GenerateBlocks_Pro_Form_Http::response_error( $response, 'MailerLite' );

		if ( $error ) {
			return $error;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['data'] ) || ! is_array( $body['data'] ) ) {
			return [];
		}

		$options = [];

		foreach ( $body['data'] as $field ) {
			$key  = sanitize_text_field( $field['key'] ?? '' );
			$name = sanitize_text_field( $field['name'] ?? $key );

			if ( '' === $key || 'email' === $key || ! preg_match( '/^[A-Za-z0-9_]{1,64}$/', $key ) ) {
				continue;
			}

			$options[] = [
				'value' => $key,
				'label' => $name . ' (' . $key . ')',
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
			self::API_BASE . '/subscribers?limit=0',
			self::request_args( $api_key )
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'connection_failed', 'Could not connect to MailerLite. Check your API key.' );
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'auth_failed', 'Invalid MailerLite API key.' );
		}

		return true;
	}

}
