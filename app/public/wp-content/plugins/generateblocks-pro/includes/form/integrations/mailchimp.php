<?php
/**
 * Mailchimp form action.
 *
 * Subscribes form submissions to a Mailchimp audience.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mailchimp form action class.
 */
class GenerateBlocks_Pro_Form_Action_Mailchimp {

	/**
	 * Register the provider with the registry.
	 */
	public static function init() {
		generateblocks_pro_register_form_integration(
			[
				'id'                    => 'mailchimp',
				'label'                 => __( 'Mailchimp', 'generateblocks-pro' ),
				'type'                  => 'email',
				'connection'            => [
					'type'          => 'api_key',
					'fields'        => [
						[
							'key'      => 'api_key',
							'label'    => __( 'API Key', 'generateblocks-pro' ),
							'type'     => 'password',
							'help'     => __( 'Find your API key in Mailchimp under Account > Extras > API Keys.', 'generateblocks-pro' ),
							'required' => true,
							'define'   => 'GENERATEBLOCKS_PRO_MAILCHIMP_API_KEY',
						],
					],
					'test_callback' => function ( $settings ) {
						return self::test_connection( $settings['api_key'] ?? '' );
					},
				],
				'destination'           => [
					'label'            => __( 'Audience', 'generateblocks-pro' ),
					'empty_label'      => __( '- Select -', 'generateblocks-pro' ),
					'refresh_label'    => __( 'Refresh', 'generateblocks-pro' ),
					'required'         => true,
					'options_callback' => function () {
						return self::fetch_audiences( self::get_api_key() );
					},
				],
				'field_map'             => [
					'label'                  => __( 'Merge fields', 'generateblocks-pro' ),
					'empty_label'            => __( 'Don\'t map', 'generateblocks-pro' ),
					'refresh_label'          => __( 'Refresh fields', 'generateblocks-pro' ),
					'depends_on_destination' => true,
					'options_callback'       => function ( $settings ) {
						$audience_id = sanitize_text_field( $settings['destinationId'] ?? '' );

						if ( empty( $audience_id ) ) {
							return [];
						}

						$fields = self::fetch_merge_fields( self::get_api_key(), $audience_id );

						if ( is_wp_error( $fields ) ) {
							return $fields;
						}

						return array_map(
							function ( $field ) {
								$tag = $field['tag'] ?? '';

								return [
									'value' => $tag,
									'label' => ( $field['name'] ?? $tag ) . ' (' . $tag . ')',
								];
							},
							$fields
						);
					},
				],
				'supports_double_optin' => true,
				'subscribe_callback'    => [ __CLASS__, 'subscribe' ],
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
			return new WP_Error( 'no_api_key', 'Mailchimp API key not configured.' );
		}

		$audience_id = sanitize_text_field( $settings['destinationId'] ?? '' );

		if ( empty( $audience_id ) ) {
			return new WP_Error( 'no_audience', 'Mailchimp audience not selected.' );
		}

		// Mailchimp audience IDs are 10-char hex strings. Strict shape match
		// catches admin typos before the API call rather than letting them
		// surface as opaque "404 not found" failures from Mailchimp.
		if ( ! preg_match( '/^[a-f0-9]{10}$/i', $audience_id ) ) {
			return new WP_Error( 'invalid_audience', 'Invalid Mailchimp audience ID.' );
		}

		$email_field = $settings['emailField'] ?? 'email';
		$email       = $sanitized_data[ $email_field ]['value'] ?? '';

		if ( empty( $email ) || ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', 'Valid email required for Mailchimp.' );
		}

		// Build merge fields from field mapping.
		$merge_fields = [];
		$field_map    = $settings['fieldMap'] ?? [];

		foreach ( $field_map as $form_field => $merge_tag ) {
			$merge_tag = sanitize_text_field( $merge_tag );

			// Mailchimp merge tags are uppercase alphanumeric + underscores (e.g. FNAME).
			if ( ! empty( $merge_tag ) && preg_match( '/^[A-Z0-9_]{1,10}$/', $merge_tag ) && isset( $sanitized_data[ $form_field ]['value'] ) ) {
				$value = GenerateBlocks_Pro_Form_Http::normalize_field_value( $sanitized_data[ $form_field ]['value'] );

				if ( '' !== $value ) {
					$merge_fields[ $merge_tag ] = $value;
				}
			}
		}

		$dc              = self::get_data_center( $api_key );
		$subscriber_hash = md5( strtolower( $email ) );
		$endpoint        = 'https://' . $dc . '.api.mailchimp.com/3.0/lists/' . $audience_id . '/members/' . $subscriber_hash;

		$double_optin = $settings['doubleOptin'] ?? true;

		// Mailchimp's PUT "Add or update list member" is idempotent:
		// - new subscriber  → created with status = status_if_new
		// - existing member → status left alone, merge_fields updated
		// This matters because the previous POST endpoint silently dropped
		// merge-field changes for returning contacts (Mailchimp returned
		// "Member Exists" which we treated as success).
		//
		// We deliberately omit `status`; sending it would re-subscribe users
		// who had unsubscribed, which is both rude and a CAN-SPAM risk.
		$body = [
			'email_address' => $email,
			'status_if_new' => $double_optin ? 'pending' : 'subscribed',
		];

		if ( ! empty( $merge_fields ) ) {
			$body['merge_fields'] = $merge_fields;
		}

		$response = wp_safe_remote_request(
			$endpoint,
			[
				'method'  => 'PUT',
				'timeout' => GenerateBlocks_Pro_Form_Http::DEFAULT_TIMEOUT,
				'headers' => [
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				],
				'body'    => wp_json_encode( $body ),
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		// PUT returns 200 for both add and update. 400 with title "Member In
		// Compliance State" means the address previously unsubscribed — the
		// right behavior is to not silently re-subscribe them.
		if ( 200 !== $status_code ) {
			$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
			$error_detail  = $response_body['detail'] ?? 'Unknown error';

			return new WP_Error( 'mailchimp_error', 'Mailchimp: ' . sanitize_text_field( $error_detail ) );
		}

		return true;
	}

	/**
	 * Get the stored API key.
	 *
	 * @return string
	 */
	public static function get_api_key() {
		return GenerateBlocks_Pro_Form_Integration_Rest::get_api_key_for( 'mailchimp' );
	}

	/**
	 * Extract the data center from a Mailchimp API key.
	 *
	 * @param string $api_key The API key (format: key-dc).
	 * @return string The data center identifier.
	 */
	private static function get_data_center( $api_key ) {
		// Mailchimp keys are formatted as "key-dc" where dc is after the last dash.
		$dash_pos = strrpos( $api_key, '-' );

		if ( false === $dash_pos ) {
			return 'us1';
		}

		$dc = substr( $api_key, $dash_pos + 1 );
		$dc = $dc ? $dc : 'us1';

		// Validate data center format to prevent SSRF via crafted API keys.
		if ( ! preg_match( '/^[a-z]{2}\d+$/i', $dc ) ) {
			return 'us1';
		}

		return $dc;
	}

	/**
	 * Fetch audiences (lists) from Mailchimp.
	 *
	 * @param string $api_key The API key.
	 * @return array|WP_Error
	 */
	public static function fetch_audiences( $api_key ) {
		$dc       = self::get_data_center( $api_key );
		$endpoint = 'https://' . $dc . '.api.mailchimp.com/3.0/lists?count=100&fields=lists.id,lists.name,lists.stats.member_count';

		$response = wp_safe_remote_get(
			$endpoint,
			[
				'timeout' => GenerateBlocks_Pro_Form_Http::DEFAULT_TIMEOUT,
				'headers' => [
					'Authorization' => 'Bearer ' . $api_key,
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$error = GenerateBlocks_Pro_Form_Http::response_error( $response, 'Mailchimp' );

		if ( $error ) {
			return $error;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['lists'] ) ) {
			return [];
		}

		return array_map(
			function ( $list ) {
				return [
					'id'    => $list['id'],
					'name'  => $list['name'],
					'count' => $list['stats']['member_count'] ?? 0,
				];
			},
			$body['lists']
		);
	}

	/**
	 * Fetch merge fields for an audience.
	 *
	 * @param string $api_key     The API key.
	 * @param string $audience_id The audience ID.
	 * @return array|WP_Error
	 */
	public static function fetch_merge_fields( $api_key, $audience_id ) {
		$audience_id = sanitize_text_field( $audience_id );

		if ( ! preg_match( '/^[a-f0-9]{10}$/i', $audience_id ) ) {
			return new WP_Error( 'invalid_audience', 'Invalid audience ID.' );
		}

		$dc       = self::get_data_center( $api_key );
		$endpoint = 'https://' . $dc . '.api.mailchimp.com/3.0/lists/' . $audience_id . '/merge-fields?count=100';

		$response = wp_safe_remote_get(
			$endpoint,
			[
				'timeout' => GenerateBlocks_Pro_Form_Http::DEFAULT_TIMEOUT,
				'headers' => [
					'Authorization' => 'Bearer ' . $api_key,
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$error = GenerateBlocks_Pro_Form_Http::response_error( $response, 'Mailchimp' );

		if ( $error ) {
			return $error;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['merge_fields'] ) ) {
			return [];
		}

		return array_map(
			function ( $field ) {
				return [
					'tag'  => $field['tag'],
					'name' => $field['name'],
					'type' => $field['type'],
				];
			},
			$body['merge_fields']
		);
	}

	/**
	 * Test the API connection.
	 *
	 * @param string $api_key The API key.
	 * @return true|WP_Error
	 */
	public static function test_connection( $api_key ) {
		$dc       = self::get_data_center( $api_key );
		$endpoint = 'https://' . $dc . '.api.mailchimp.com/3.0/ping';

		$response = wp_safe_remote_get(
			$endpoint,
			[
				'timeout' => GenerateBlocks_Pro_Form_Http::DEFAULT_TIMEOUT,
				'headers' => [
					'Authorization' => 'Bearer ' . $api_key,
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'connection_failed', 'Could not reach Mailchimp.' );
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'auth_failed', 'Invalid Mailchimp API key.' );
		}

		return true;
	}
}
