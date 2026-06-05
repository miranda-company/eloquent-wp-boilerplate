<?php
/**
 * REST endpoints for form integrations.
 *
 * Exposes registered provider metadata, connection management, and provider
 * option lists to the form editor. Connection details stay server-side.
 *
 * API key storage strategy:
 *
 * Keys are stored as plain text in the options table, protected by the
 * forms-manage capability on save/delete endpoints. We intentionally do not
 * encrypt these keys because any encryption key must live on the same server.
 * The real security boundary is capability access to these endpoints.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form integration REST class.
 */
class GenerateBlocks_Pro_Form_Integration_Rest extends GenerateBlocks_Pro_Singleton {

	/**
	 * Per-request cache for resolved connection settings.
	 *
	 * @var array<string, string>
	 */
	private static $connection_cache = [];

	/**
	 * Initialize routes.
	 */
	public function init() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes() {
		$namespace = 'generateblocks-pro/v1';

		register_rest_route(
			$namespace,
			'/integrations/(?P<service>[a-z0-9_-]+)',
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'save_settings' ],
				'permission_callback' => [ $this, 'admin_permission' ],
				'args'                => [
					'service'  => [
						'required'          => true,
						'validate_callback' => [ $this, 'validate_service' ],
					],
					'settings' => [
						'required' => false,
						'type'     => 'object',
					],
				],
			]
		);

		register_rest_route(
			$namespace,
			'/integrations/(?P<service>[a-z0-9_-]+)',
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_settings' ],
				'permission_callback' => [ $this, 'admin_permission' ],
				'args'                => [
					'service' => [
						'required'          => true,
						'validate_callback' => [ $this, 'validate_service' ],
					],
				],
			]
		);

		register_rest_route(
			$namespace,
			'/integrations/(?P<service>[a-z0-9_-]+)/test',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'test_connection' ],
				'permission_callback' => [ $this, 'admin_permission' ],
				'args'                => [
					'service'  => [
						'required'          => true,
						'validate_callback' => [ $this, 'validate_service' ],
					],
					'settings' => [
						'required' => false,
						'type'     => 'object',
					],
				],
			]
		);

		register_rest_route(
			$namespace,
			'/integrations/(?P<service>[a-z0-9_-]+)/destinations',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'fetch_destinations' ],
				'permission_callback' => [ $this, 'admin_permission' ],
				'args'                => [
					'service' => [
						'required'          => true,
						'validate_callback' => [ $this, 'validate_service' ],
					],
					'nocache' => [
						'required' => false,
						'type'     => 'boolean',
					],
				],
			]
		);

		register_rest_route(
			$namespace,
			'/integrations/(?P<service>[a-z0-9_-]+)/secondary-destinations',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'fetch_secondary_destinations' ],
				'permission_callback' => [ $this, 'admin_permission' ],
				'args'                => [
					'service' => [
						'required'          => true,
						'validate_callback' => [ $this, 'validate_service' ],
					],
					'nocache' => [
						'required' => false,
						'type'     => 'boolean',
					],
				],
			]
		);

		register_rest_route(
			$namespace,
			'/integrations/(?P<service>[a-z0-9_-]+)/fields',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'fetch_fields' ],
				'permission_callback' => [ $this, 'admin_permission' ],
				'args'                => [
					'service' => [
						'required'          => true,
						'validate_callback' => [ $this, 'validate_service' ],
					],
					'nocache' => [
						'required' => false,
						'type'     => 'boolean',
					],
				],
			]
		);

		register_rest_route(
			$namespace,
			'/integrations',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_integrations' ],
				'permission_callback' => [ $this, 'admin_permission' ],
			]
		);
	}

	/**
	 * Save provider connection settings.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response
	 */
	public function save_settings( WP_REST_Request $request ) {
		$service  = $request->get_param( 'service' );
		$settings = $this->sanitize_connection_settings(
			$service,
			$request->get_param( 'settings' )
		);
		$effective_settings = $this->get_effective_connection_settings( $service, $settings );
		$validation         = $this->validate_connection_settings( $service, $effective_settings );

		if ( is_wp_error( $validation ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => $validation->get_error_message(),
				],
				200
			);
		}

		$integrations = get_option( 'generateblocks_pro_form_integrations', [] );
		$integrations = is_array( $integrations ) ? $integrations : [];
		$current      = isset( $integrations[ $service ] ) && is_array( $integrations[ $service ] )
			? $integrations[ $service ]
			: [];
		$provider     = GenerateBlocks_Pro_Form_Integration_Registry::get_provider( $service );
		$connection   = $provider['connection'] ?? [];

		foreach ( $connection['fields'] ?? [] as $field ) {
			$key = $field['key'];

			if (
				array_key_exists( $key, $settings )
				&& ! in_array( $this->get_connection_field_source( $service, $field ), [ 'define', 'code' ], true )
			) {
				$current[ $key ] = $settings[ $key ];
			}
		}

		$integrations[ $service ] = $current;

		update_option( 'generateblocks_pro_form_integrations', $integrations, false );

		self::clear_service_cache( $service );

		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * Delete provider connection settings.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response
	 */
	public function delete_settings( WP_REST_Request $request ) {
		$service      = $request->get_param( 'service' );
		$integrations = get_option( 'generateblocks_pro_form_integrations', [] );
		$integrations = is_array( $integrations ) ? $integrations : [];

		unset( $integrations[ $service ] );

		update_option( 'generateblocks_pro_form_integrations', $integrations, false );

		self::clear_service_cache( $service );

		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * Test a service connection with provided settings.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response
	 */
	public function test_connection( WP_REST_Request $request ) {
		$service  = $request->get_param( 'service' );
		$settings = $this->sanitize_connection_settings(
			$service,
			$request->get_param( 'settings' )
		);
		$effective_settings = $this->get_effective_connection_settings( $service, $settings );
		$validation         = $this->validate_connection_settings( $service, $effective_settings, true );

		if ( is_wp_error( $validation ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => $validation->get_error_message(),
				],
				200
			);
		}

		$result = $this->test_service( $service, $effective_settings );

		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => $result->get_error_message(),
				],
				200
			);
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Connected successfully.', 'generateblocks-pro' ),
			],
			200
		);
	}

	/**
	 * Fetch primary destination options for a provider.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response
	 */
	public function fetch_destinations( WP_REST_Request $request ) {
		return $this->fetch_provider_options( $request, 'destination' );
	}

	/**
	 * Fetch secondary destination options for a provider.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response
	 */
	public function fetch_secondary_destinations( WP_REST_Request $request ) {
		return $this->fetch_provider_options( $request, 'secondary_destination' );
	}

	/**
	 * Fetch field-map options for a provider.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response
	 */
	public function fetch_fields( WP_REST_Request $request ) {
		return $this->fetch_provider_options( $request, 'field_map' );
	}

	/**
	 * Fetch options from a provider callback.
	 *
	 * @param WP_REST_Request $request The request.
	 * @param string          $key     Provider option key.
	 * @return WP_REST_Response
	 */
	private function fetch_provider_options( WP_REST_Request $request, $key ) {
		$service  = $request->get_param( 'service' );
		$provider = GenerateBlocks_Pro_Form_Integration_Registry::get_provider( $service );
		$config   = $provider[ $key ] ?? [];
		$callback = $config['options_callback'] ?? null;

		if ( ! is_callable( $callback ) ) {
			return new WP_REST_Response(
				[
					'success' => true,
					'options' => [],
				],
				200
			);
		}

		$connection_ready = $this->connection_ready_for_options( $service );

		if ( is_wp_error( $connection_ready ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => $connection_ready->get_error_message(),
				],
				200
			);
		}

		if ( ! $connection_ready ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'Integration is not connected.', 'generateblocks-pro' ),
				],
				200
			);
		}

		$settings  = $this->get_option_request_settings( $request, $service );
		$cache_key = 'gb_form_options_' . $service . '_' . $key . '_' . md5(
			wp_json_encode( [ self::connection_cache_hash( $service ), $settings ] )
		);
		$nocache = (bool) $request->get_param( 'nocache' );
		$cached  = $nocache ? false : get_transient( $cache_key );

		if ( false !== $cached ) {
			return new WP_REST_Response(
				[
					'success' => true,
					'options' => $cached,
				],
				200
			);
		}

		$options = call_user_func( $callback, $settings, $request, $service );

		if ( is_wp_error( $options ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => $options->get_error_message(),
				],
				200
			);
		}

		$options = GenerateBlocks_Pro_Form_Integration_Registry::normalize_options( $options );

		set_transient( $cache_key, $options, 5 * MINUTE_IN_SECONDS );

		return new WP_REST_Response(
			[
				'success' => true,
				'options' => $options,
			],
			200
		);
	}

	/**
	 * Get public integration definitions and connection state.
	 *
	 * @return WP_REST_Response
	 */
	public function get_integrations() {
		$connected = [];

		foreach ( GenerateBlocks_Pro_Form_Integration_Registry::get_all() as $service => $provider ) {
			$connected[ $service ] = $this->get_connection_state( $service, $provider );
		}

		return new WP_REST_Response(
			[
				'success'      => true,
				'integrations' => GenerateBlocks_Pro_Form_Integration_Registry::get_public_definitions(),
				'connected'    => $connected,
			],
			200
		);
	}

	/**
	 * Get the API key for a service.
	 *
	 * @param string $service The service name.
	 * @return string The API key, or empty string if not configured.
	 */
	public static function get_api_key_for( $service ) {
		return self::get_connection_setting_for( $service, 'api_key' );
	}

	/**
	 * Get the site key for a service.
	 *
	 * @param string $service The service name.
	 * @return string The site key, or empty string if not configured.
	 */
	public static function get_site_key_for( $service ) {
		return self::get_connection_setting_for( $service, 'site_key' );
	}

	/**
	 * Get a connection setting for a service.
	 *
	 * Priority chain:
	 * 1. wp-config.php define registered on the field.
	 * 2. In-memory registered key for api_key.
	 * 3. Database option saved via admin UI.
	 *
	 * @param string $service Service ID.
	 * @param string $key     Setting key.
	 * @return string
	 */
	public static function get_connection_setting_for( $service, $key ) {
		$service   = sanitize_key( $service );
		$key       = preg_replace( '/[^A-Za-z0-9_]/', '', (string) $key );
		$cache_key = $service . ':' . $key;

		if ( array_key_exists( $cache_key, self::$connection_cache ) ) {
			return self::$connection_cache[ $cache_key ];
		}

		$value = self::resolve_connection_setting( $service, $key );

		self::$connection_cache[ $cache_key ] = $value;

		return $value;
	}

	/**
	 * Resolve a connection setting without cache.
	 *
	 * @param string $service Service ID.
	 * @param string $key     Setting key.
	 * @return string
	 */
	private static function resolve_connection_setting( $service, $key ) {
		$provider   = GenerateBlocks_Pro_Form_Integration_Registry::get_provider( $service );
		$connection = $provider['connection'] ?? [];
		$define     = '';

		foreach ( $connection['fields'] ?? [] as $field ) {
			if ( $field['key'] === $key ) {
				$define = $field['define'];
				break;
			}
		}

		if ( $define && defined( $define ) ) {
			$value = (string) constant( $define );

			if ( '' !== trim( $value ) ) {
				return $value;
			}
		}

		if ( 'api_key' === $key ) {
			$registered = GenerateBlocks_Pro_Form_Integration_Registry::get( $service );

			if ( ! empty( $registered ) ) {
				return (string) $registered;
			}
		}

		$integrations = get_option( 'generateblocks_pro_form_integrations', [] );
		$integrations = is_array( $integrations ) ? $integrations : [];

		return isset( $integrations[ $service ][ $key ] )
			? (string) $integrations[ $service ][ $key ]
			: '';
	}

	/**
	 * Sanitize connection settings against registered connection fields.
	 *
	 * @param string $service  Service ID.
	 * @param array  $settings Raw settings.
	 * @return array
	 */
	private function sanitize_connection_settings( $service, $settings ) {
		if ( ! is_array( $settings ) ) {
			return [];
		}

		$provider = GenerateBlocks_Pro_Form_Integration_Registry::get_provider( $service );
		$fields   = $provider['connection']['fields'] ?? [];
		$out      = [];

		foreach ( $fields as $field ) {
			$key = $field['key'];

			if ( isset( $settings[ $key ] ) && ! is_array( $settings[ $key ] ) && ! is_object( $settings[ $key ] ) ) {
				$value = sanitize_text_field( (string) $settings[ $key ] );

				if ( '' !== trim( $value ) ) {
					$out[ $key ] = $value;
				}
			}
		}

		return $out;
	}

	/**
	 * Merge submitted settings with currently resolved settings.
	 *
	 * Blank modal fields mean "leave the existing code/DB value alone".
	 *
	 * @param string $service  Service ID.
	 * @param array  $settings Sanitized submitted settings.
	 * @return array
	 */
	private function get_effective_connection_settings( $service, $settings ) {
		$provider = GenerateBlocks_Pro_Form_Integration_Registry::get_provider( $service );
		$fields   = $provider['connection']['fields'] ?? [];
		$out      = [];

		foreach ( $fields as $field ) {
			$key = $field['key'];

			$out[ $key ] = array_key_exists( $key, $settings )
				? $settings[ $key ]
				: self::get_connection_setting_for( $service, $key );
		}

		return $out;
	}

	/**
	 * Validate sanitized connection settings against required fields.
	 *
	 * @param string $service  Service ID.
	 * @param array  $settings Sanitized settings.
	 * @param bool   $for_test Whether this validation is for a connection test.
	 * @return true|WP_Error
	 */
	private function validate_connection_settings( $service, $settings, $for_test = false ) {
		$provider = GenerateBlocks_Pro_Form_Integration_Registry::get_provider( $service );
		$fields   = $provider['connection']['fields'] ?? [];

		foreach ( $fields as $field ) {
			$required_key = $for_test ? 'test_required' : 'required';

			if ( empty( $field[ $required_key ] ) ) {
				continue;
			}

			$key   = $field['key'];
			$value = isset( $settings[ $key ] ) ? trim( (string) $settings[ $key ] ) : '';

			if ( '' === $value ) {
				return new WP_Error(
					'missing_connection_setting',
					sprintf(
						/* translators: %s: connection setting label */
						__( '%s is required.', 'generateblocks-pro' ),
						$field['label']
					)
				);
			}
		}

		return true;
	}

	/**
	 * Build sanitized settings from option route query params.
	 *
	 * @param WP_REST_Request $request The request.
	 * @param string          $service Service ID.
	 * @return array
	 */
	private function get_option_request_settings( WP_REST_Request $request, $service ) {
		$provider = GenerateBlocks_Pro_Form_Integration_Registry::get_provider( $service );
		$params   = $request->get_params();
		$allowed  = [
			'destinationId'          => true,
			'secondaryDestinationId' => true,
		];
		$out      = [];

		foreach ( $provider['connection']['fields'] ?? [] as $field ) {
			$allowed[ $field['key'] ] = true;
		}

		foreach ( $params as $key => $value ) {
			$key = (string) $key;

			if ( ! isset( $allowed[ $key ] ) || is_array( $value ) || is_object( $value ) ) {
				continue;
			}

			$out[ $key ] = sanitize_text_field( (string) $value );
		}

		ksort( $out );

		return $out;
	}

	/**
	 * Test a specific provider connection.
	 *
	 * @param string $service  Service ID.
	 * @param array  $settings Connection settings.
	 * @return true|WP_Error
	 */
	private function test_service( $service, $settings ) {
		$provider = GenerateBlocks_Pro_Form_Integration_Registry::get_provider( $service );
		$callback = $provider['connection']['test_callback'] ?? null;

		if ( ! is_callable( $callback ) ) {
			return true;
		}

		return call_user_func( $callback, $settings, $service );
	}

	/**
	 * Determine whether a provider is connected enough to fetch options.
	 *
	 * @param string $service Service ID.
	 * @return bool|WP_Error
	 */
	private function connection_ready_for_options( $service ) {
		$provider   = GenerateBlocks_Pro_Form_Integration_Registry::get_provider( $service );
		$connection = $provider['connection'] ?? [];

		if ( empty( $connection ) || 'none' === $connection['type'] ) {
			return true;
		}

		if ( is_callable( $connection['connected_callback'] ) ) {
			return call_user_func( $connection['connected_callback'], $service );
		}

		foreach ( $connection['fields'] ?? [] as $field ) {
			if ( ! empty( $field['required'] ) && '' === self::get_connection_setting_for( $service, $field['key'] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get connection state for the editor.
	 *
	 * @param string $service  Service ID.
	 * @param array  $provider Provider definition.
	 * @return array
	 */
	private function get_connection_state( $service, $provider ) {
		$connection      = $provider['connection'] ?? [];
		$callback_result = null;
		$source          = '';
		$fields          = [];
		$public_fields   = [];
		$connected       = true;

		if ( empty( $connection ) || 'none' === $connection['type'] ) {
			return [
				'connected' => true,
				'source'    => '',
				'fields'    => [],
			];
		}

		if ( is_callable( $connection['connected_callback'] ) ) {
			$callback_result = call_user_func( $connection['connected_callback'], $service );
			$connected       = ! is_wp_error( $callback_result ) && (bool) $callback_result;
		}

		foreach ( $connection['fields'] ?? [] as $field ) {
			$value       = self::get_connection_setting_for( $service, $field['key'] );
			$field_key   = $field['key'];
			$field_state = [
				'configured' => '' !== $value,
				'source'     => $this->get_connection_field_source( $service, $field ),
			];

			$fields[ $field_key ] = $field_state;

			if ( ! empty( $field['public'] ) && '' !== $value ) {
				$public_fields[ $field_key ] = $value;
			}

			if ( '' === $source && '' !== $field_state['source'] ) {
				$source = $field_state['source'];
			}

			if ( ! empty( $field['required'] ) && '' === $value ) {
				$connected = false;
			}
		}

		$state = [
			'connected' => $connected,
			'source'    => $source,
			'fields'    => $fields,
		];

		if ( is_wp_error( $callback_result ) ) {
			$state['message'] = $callback_result->get_error_message();
		}

		if ( ! empty( $public_fields ) ) {
			$state['publicFields'] = $public_fields;
		}

		return $state;
	}

	/**
	 * Get the source for a connection field.
	 *
	 * @param string $service Service ID.
	 * @param array  $field   Connection field.
	 * @return string
	 */
	private function get_connection_field_source( $service, $field ) {
		$define = $field['define'];

		if ( $define && defined( $define ) && '' !== trim( (string) constant( $define ) ) ) {
			return 'define';
		}

		if ( 'api_key' === $field['key'] && ! empty( GenerateBlocks_Pro_Form_Integration_Registry::get( $service ) ) ) {
			return 'code';
		}

		$integrations = get_option( 'generateblocks_pro_form_integrations', [] );
		$integrations = is_array( $integrations ) ? $integrations : [];

		if ( ! empty( $integrations[ $service ][ $field['key'] ] ) ) {
			return 'db';
		}

		return '';
	}

	/**
	 * Build a hash of current connection settings for option-cache keys.
	 *
	 * @param string $service Service ID.
	 * @return string
	 */
	private static function connection_cache_hash( $service ) {
		$provider = GenerateBlocks_Pro_Form_Integration_Registry::get_provider( $service );
		$values   = [];

		foreach ( $provider['connection']['fields'] ?? [] as $field ) {
			$values[ $field['key'] ] = self::get_connection_setting_for( $service, $field['key'] );
		}

		return md5( wp_json_encode( $values ) );
	}

	/**
	 * Clear the per-request connection cache for a service.
	 *
	 * @param string $service Service ID.
	 */
	public static function clear_service_cache( $service ) {
		$service = sanitize_key( $service );

		foreach ( array_keys( self::$connection_cache ) as $key ) {
			if ( 0 === strpos( $key, $service . ':' ) ) {
				unset( self::$connection_cache[ $key ] );
			}
		}
	}

	/**
	 * Validate that the service parameter is registered.
	 *
	 * @param string $value The service name.
	 * @return bool
	 */
	public function validate_service( $value ) {
		return (bool) GenerateBlocks_Pro_Form_Integration_Registry::get_provider( $value );
	}

	/**
	 * Permission check for all integration routes.
	 *
	 * @return bool
	 */
	public function admin_permission() {
		return current_user_can( 'manage_options' );
	}
}
