<?php
/**
 * Form integration provider registry.
 *
 * Stores provider definitions for fixed form integrations. Provider IDs are
 * settings of a form action, not form actions themselves.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integration registry class.
 */
class GenerateBlocks_Pro_Form_Integration_Registry {

	/**
	 * Registered provider definitions.
	 *
	 * @var array<string, array>
	 */
	private static $integrations = [];

	/**
	 * Registered API keys.
	 *
	 * @var array<string, string>
	 */
	private static $keys = [];

	/**
	 * Register an integration provider.
	 *
	 * @param array $args Provider definition.
	 * @return bool True when registered.
	 */
	public static function register( $args ) {
		if ( ! is_array( $args ) ) {
			return false;
		}

		$id = isset( $args['id'] ) ? sanitize_key( $args['id'] ) : '';

		if ( '' === $id || empty( $args['label'] ) ) {
			return false;
		}

		self::$integrations[ $id ] = [
			'id'                         => $id,
			'label'                      => sanitize_text_field( (string) $args['label'] ),
			'type'                       => isset( $args['type'] ) ? sanitize_key( $args['type'] ) : 'email',
			'help'                       => isset( $args['help'] ) ? sanitize_text_field( (string) $args['help'] ) : '',
			'connection'                 => self::normalize_connection( $args['connection'] ?? [] ),
			'destination'                => self::normalize_destination( $args['destination'] ?? [] ),
			'secondary_destination'      => self::normalize_destination( $args['secondary_destination'] ?? [], false ),
			'field_map'                  => self::normalize_field_map( $args['field_map'] ?? [] ),
			'supports_name_field'        => ! empty( $args['supports_name_field'] ),
			'supports_double_optin'      => ! empty( $args['supports_double_optin'] ),
			'default_double_optin'       => ! array_key_exists( 'default_double_optin', $args ) || ! empty( $args['default_double_optin'] ),
			'subscribe_callback'         => isset( $args['subscribe_callback'] ) && is_callable( $args['subscribe_callback'] )
				? $args['subscribe_callback']
				: null,
		];

		if ( method_exists( 'GenerateBlocks_Pro_Form_Integration_Rest', 'clear_service_cache' ) ) {
			GenerateBlocks_Pro_Form_Integration_Rest::clear_service_cache( $id );
		}

		return true;
	}

	/**
	 * Normalize a connection definition.
	 *
	 * @param array $connection Raw connection definition.
	 * @return array
	 */
	private static function normalize_connection( $connection ) {
		$connection = is_array( $connection ) ? $connection : [];
		$type       = isset( $connection['type'] ) ? sanitize_key( $connection['type'] ) : 'none';
		$fields     = isset( $connection['fields'] ) && is_array( $connection['fields'] )
			? $connection['fields']
			: [];

		if ( 'api_key' === $type && empty( $fields ) ) {
			$fields = [
				[
					'key'      => 'api_key',
					'label'    => __( 'API Key', 'generateblocks-pro' ),
					'type'     => 'password',
					'required' => true,
				],
			];
		}

		return [
			'type'               => $type,
			'fields'             => self::normalize_connection_fields( $fields ),
			'test_callback'      => isset( $connection['test_callback'] ) && is_callable( $connection['test_callback'] )
				? $connection['test_callback']
				: null,
			'connected_callback' => isset( $connection['connected_callback'] ) && is_callable( $connection['connected_callback'] )
				? $connection['connected_callback']
				: null,
		];
	}

	/**
	 * Normalize connection fields.
	 *
	 * @param array $fields Raw fields.
	 * @return array
	 */
	private static function normalize_connection_fields( $fields ) {
		$out = [];

		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$key = isset( $field['key'] ) ? preg_replace( '/[^A-Za-z0-9_]/', '', (string) $field['key'] ) : '';

			if ( '' === $key || empty( $field['label'] ) ) {
				continue;
			}

			$out[] = [
				'key'           => $key,
				'label'         => sanitize_text_field( (string) $field['label'] ),
				'type'          => isset( $field['type'] ) ? sanitize_key( $field['type'] ) : 'text',
				'help'          => isset( $field['help'] ) ? sanitize_text_field( (string) $field['help'] ) : '',
				'placeholder'   => isset( $field['placeholder'] ) ? sanitize_text_field( (string) $field['placeholder'] ) : '',
				'required'      => ! array_key_exists( 'required', $field ) || ! empty( $field['required'] ),
				'test_required' => array_key_exists( 'test_required', $field )
					? ! empty( $field['test_required'] )
					: ( ! array_key_exists( 'required', $field ) || ! empty( $field['required'] ) ),
				'define'        => isset( $field['define'] ) ? preg_replace( '/[^A-Z0-9_]/', '', (string) $field['define'] ) : '',
				'public'        => ! empty( $field['public'] ),
			];
		}

		return $out;
	}

	/**
	 * Normalize the provider destination definition.
	 *
	 * @param array $destination Raw definition.
	 * @param bool  $default_required Whether the destination is required.
	 * @return array
	 */
	private static function normalize_destination( $destination, $default_required = true ) {
		$destination = is_array( $destination ) ? $destination : [];

		$has_destination = ! empty( $destination['label'] ) || isset( $destination['options_callback'] );

		return [
			'label'            => isset( $destination['label'] ) ? sanitize_text_field( (string) $destination['label'] ) : '',
			'empty_label'      => isset( $destination['empty_label'] ) ? sanitize_text_field( (string) $destination['empty_label'] ) : __( '- Select -', 'generateblocks-pro' ),
			'refresh_label'    => isset( $destination['refresh_label'] ) ? sanitize_text_field( (string) $destination['refresh_label'] ) : __( 'Refresh', 'generateblocks-pro' ),
			'required'         => array_key_exists( 'required', $destination ) ? ! empty( $destination['required'] ) : ( $default_required && $has_destination ),
			'options_callback' => isset( $destination['options_callback'] ) && is_callable( $destination['options_callback'] )
				? $destination['options_callback']
				: null,
		];
	}

	/**
	 * Normalize field mapping support.
	 *
	 * @param array $field_map Raw definition.
	 * @return array
	 */
	private static function normalize_field_map( $field_map ) {
		$field_map = is_array( $field_map ) ? $field_map : [];
		$callback  = isset( $field_map['options_callback'] ) && is_callable( $field_map['options_callback'] )
			? $field_map['options_callback']
			: null;

		return [
			'enabled'                => ! empty( $field_map['enabled'] ) || is_callable( $callback ),
			'label'                  => isset( $field_map['label'] ) ? sanitize_text_field( (string) $field_map['label'] ) : __( 'Fields', 'generateblocks-pro' ),
			'empty_label'            => isset( $field_map['empty_label'] ) ? sanitize_text_field( (string) $field_map['empty_label'] ) : __( 'Don\'t map', 'generateblocks-pro' ),
			'refresh_label'          => isset( $field_map['refresh_label'] ) ? sanitize_text_field( (string) $field_map['refresh_label'] ) : __( 'Refresh fields', 'generateblocks-pro' ),
			'depends_on_destination' => ! empty( $field_map['depends_on_destination'] ),
			'options_callback'       => $callback,
		];
	}

	/**
	 * Normalize options into { label, value } rows.
	 *
	 * @param mixed $options Raw options.
	 * @return array
	 */
	public static function normalize_options( $options ) {
		if ( ! is_array( $options ) ) {
			return [];
		}

		$out = [];

		foreach ( $options as $option ) {
			if ( is_array( $option ) ) {
				$value = $option['value'] ?? ( $option['id'] ?? ( $option['tag'] ?? '' ) );
				$label = $option['label'] ?? ( $option['name'] ?? ( $option['tag'] ?? $value ) );
				$count = $option['count'] ?? null;
			} else {
				$value = $option;
				$label = $option;
				$count = null;
			}

			if ( is_array( $value ) || is_object( $value ) || is_array( $label ) || is_object( $label ) ) {
				continue;
			}

			$row = [
				'value' => sanitize_text_field( (string) $value ),
				'label' => sanitize_text_field( (string) $label ),
			];

			if ( null !== $count && is_numeric( $count ) ) {
				$row['count'] = (int) $count;
			}

			$out[] = $row;
		}

		return $out;
	}

	/**
	 * Get a registered provider definition.
	 *
	 * @param string $id Provider ID.
	 * @return array|null
	 */
	public static function get_provider( $id ) {
		return self::$integrations[ sanitize_key( $id ) ] ?? null;
	}

	/**
	 * Backward-compatible internal alias for provider lookups.
	 *
	 * @param string $id Provider ID.
	 * @return array|null
	 */
	public static function get_integration( $id ) {
		return self::get_provider( $id );
	}

	/**
	 * Get all registered provider definitions.
	 *
	 * @return array
	 */
	public static function get_all() {
		return self::$integrations;
	}

	/**
	 * Get public provider definitions for the editor.
	 *
	 * Callback references, define names, and other server-only data are omitted.
	 *
	 * @return array
	 */
	public static function get_public_definitions() {
		return array_values(
			array_map(
				[ __CLASS__, 'to_public_definition' ],
				self::$integrations
			)
		);
	}

	/**
	 * Convert a provider definition into the public editor shape.
	 *
	 * @param array $integration Provider definition.
	 * @return array
	 */
	private static function to_public_definition( $integration ) {
		return [
			'id'                   => $integration['id'],
			'label'                => $integration['label'],
			'type'                 => $integration['type'],
			'help'                 => $integration['help'],
			'connection'           => [
				'type'   => $integration['connection']['type'],
				'fields' => array_map(
					function ( $field ) {
						return [
							'key'          => $field['key'],
							'label'        => $field['label'],
							'type'         => $field['type'],
							'help'         => $field['help'],
							'placeholder'  => $field['placeholder'],
							'required'     => $field['required'],
							'testRequired' => $field['test_required'],
						];
					},
					$integration['connection']['fields']
				),
			],
			'destination'          => [
				'label'          => $integration['destination']['label'],
				'emptyLabel'     => $integration['destination']['empty_label'],
				'refreshLabel'   => $integration['destination']['refresh_label'],
				'required'       => $integration['destination']['required'],
				'hasOptions'     => is_callable( $integration['destination']['options_callback'] ),
			],
			'secondaryDestination' => [
				'label'          => $integration['secondary_destination']['label'],
				'emptyLabel'     => $integration['secondary_destination']['empty_label'],
				'refreshLabel'   => $integration['secondary_destination']['refresh_label'],
				'required'       => $integration['secondary_destination']['required'],
				'hasOptions'     => is_callable( $integration['secondary_destination']['options_callback'] ),
			],
			'fieldMap'             => [
				'enabled'                => $integration['field_map']['enabled'],
				'label'                  => $integration['field_map']['label'],
				'emptyLabel'             => $integration['field_map']['empty_label'],
				'refreshLabel'           => $integration['field_map']['refresh_label'],
				'dependsOnDestination'    => $integration['field_map']['depends_on_destination'],
				'hasOptions'             => is_callable( $integration['field_map']['options_callback'] ),
			],
			'supportsNameField'    => $integration['supports_name_field'],
			'supportsDoubleOptin'  => $integration['supports_double_optin'],
			'defaultDoubleOptin'   => $integration['default_double_optin'],
			'canSubscribe'         => is_callable( $integration['subscribe_callback'] ),
		];
	}

	/**
	 * Register an API key for a service.
	 *
	 * @param string $service The service identifier.
	 * @param string $api_key The API key.
	 */
	public static function set( $service, $api_key ) {
		$service = sanitize_key( $service );

		self::$keys[ $service ] = $api_key;

		if ( method_exists( 'GenerateBlocks_Pro_Form_Integration_Rest', 'clear_service_cache' ) ) {
			GenerateBlocks_Pro_Form_Integration_Rest::clear_service_cache( $service );
		}
	}

	/**
	 * Get a registered API key.
	 *
	 * @param string $service The service identifier.
	 * @return string The API key, or empty string if not registered.
	 */
	public static function get( $service ) {
		return self::$keys[ sanitize_key( $service ) ] ?? '';
	}
}

/**
 * Register an email integration API key.
 *
 * Call this inside the 'generateblocks_form_register_email_integrations' hook:
 *
 *   add_action( 'generateblocks_form_register_email_integrations', function() {
 *       generateblocks_pro_set_email_integration( 'mailchimp', 'your-api-key' );
 *   } );
 *
 * @param string $service The service identifier.
 * @param string $api_key The API key.
 */
function generateblocks_pro_set_email_integration( $service, $api_key ) {
	GenerateBlocks_Pro_Form_Integration_Registry::set( $service, $api_key );
}

/**
 * Register a form integration provider.
 *
 * @param array $args Provider definition.
 * @return bool True when registered.
 */
function generateblocks_pro_register_form_integration( $args ) {
	return GenerateBlocks_Pro_Form_Integration_Registry::register( $args );
}
