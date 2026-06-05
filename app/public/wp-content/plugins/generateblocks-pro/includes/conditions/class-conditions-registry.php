<?php
/**
 * Conditions Registry
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registry for condition types.
 */
class GenerateBlocks_Pro_Conditions_Registry {
	/**
	 * Registered condition types.
	 *
	 * @var array
	 */
	private static $condition_types = [];

	/**
	 * Condition instances cache.
	 *
	 * @var array
	 */
	private static $instances = [];

	/**
	 * Evaluation results cache (per-request).
	 *
	 * @var array
	 */
	private static $evaluation_cache = [];

	/**
	 * Register a condition type.
	 *
	 * @param string $type      Condition type identifier.
	 * @param array  $args      Condition arguments.
	 * @param string $classname Condition evaluator class name.
	 * @return bool
	 */
	public static function register( $type, $args, $classname ) {
		if ( empty( $type ) || empty( $classname ) ) {
			return false;
		}

		if ( ! class_exists( $classname ) ) {
			return false;
		}

		// Ensure the class implements our interface.
		$interfaces = class_implements( $classname );
		if ( ! isset( $interfaces['GenerateBlocks_Pro_Condition_Interface'] ) ) {
			return false;
		}

		$defaults = [
			'label'     => '',
			'operators' => [],
			'priority'  => 10,
		];

		$args = wp_parse_args( $args, $defaults );

		self::$condition_types[ $type ] = [
			'label'     => $args['label'],
			'class'     => $classname,
			'operators' => $args['operators'],
			'priority'  => $args['priority'],
		];

		return true;
	}

	/**
	 * Unregister a condition type.
	 *
	 * @param string $type Condition type identifier.
	 * @return bool
	 */
	public static function unregister( $type ) {
		if ( ! isset( self::$condition_types[ $type ] ) ) {
			return false;
		}

		unset( self::$condition_types[ $type ] );

		if ( isset( self::$instances[ $type ] ) ) {
			unset( self::$instances[ $type ] );
		}

		return true;
	}

	/**
	 * Get all registered condition types.
	 *
	 * @return array
	 */
	public static function get_all() {
		// Sort by priority.
		uasort(
			self::$condition_types,
			function( $a, $b ) {
				return $a['priority'] <=> $b['priority'];
			}
		);

		return self::$condition_types;
	}

	/**
	 * Get a specific condition type.
	 *
	 * @param string $type Condition type identifier.
	 * @return array|null
	 */
	public static function get( $type ) {
		return isset( self::$condition_types[ $type ] ) ? self::$condition_types[ $type ] : null;
	}

	/**
	 * Get condition instance.
	 *
	 * @param string $type Condition type identifier.
	 * @return GenerateBlocks_Pro_Condition_Interface|null
	 */
	public static function get_instance( $type ) {
		if ( ! isset( self::$condition_types[ $type ] ) ) {
			return null;
		}

		if ( ! isset( self::$instances[ $type ] ) ) {
			$classname = self::$condition_types[ $type ]['class'];
			self::$instances[ $type ] = new $classname();
		}

		return self::$instances[ $type ];
	}

	/**
	 * Evaluate a condition.
	 *
	 * @param string $type     Condition type.
	 * @param string $rule     Condition rule.
	 * @param string $operator Condition operator.
	 * @param mixed  $value    Condition value.
	 * @param array  $context  Additional context.
	 * @return bool
	 */
	public static function evaluate( $type, $rule, $operator, $value, $context = [] ) {
		$instance = self::get_instance( $type );

		if ( ! $instance ) {
			return false;
		}

		// Cache key for performance - per-request only using class property.
		$cache_data = wp_json_encode( [ $type, $rule, $operator, $value, $context ] );
		$cache_key = md5( $cache_data );

		if ( isset( self::$evaluation_cache[ $cache_key ] ) ) {
			return self::$evaluation_cache[ $cache_key ];
		}

		$result = $instance->evaluate( $rule, $operator, $value, $context );

		// Cache for current request only using class property.
		self::$evaluation_cache[ $cache_key ] = $result;

		return $result;
	}

	/**
	 * Clear the evaluation cache.
	 * Used primarily for testing purposes to reset state between tests.
	 *
	 * @return void
	 */
	public static function clear_evaluation_cache() {
		self::$evaluation_cache = [];
	}

	/**
	 * Register core condition types.
	 */
	public static function register_core_types() {
		// Location.
		self::register(
			'location',
			[
				'label'     => __( 'Location', 'generateblocks-pro' ),
				'operators' => [ 'is', 'is_not', 'includes_any', 'includes_all', 'excludes_any', 'excludes_all' ],
				'priority'  => 10,
			],
			'GenerateBlocks_Pro_Condition_Location'
		);

		// Query Parameter.
		self::register(
			'query_arg',
			[
				'label'     => __( 'Query Parameter', 'generateblocks-pro' ),
				'operators' => [ 'exists', 'not_exists', 'equals', 'contains', 'not_contains', 'starts_with', 'ends_with' ],
				'priority'  => 20,
			],
			'GenerateBlocks_Pro_Condition_Query_Arg'
		);

		// User Role.
		self::register(
			'user_role',
			[
				'label'     => __( 'User Role', 'generateblocks-pro' ),
				'operators' => [ 'is', 'is_not', 'includes_any', 'includes_all' ],
				'priority'  => 30,
			],
			'GenerateBlocks_Pro_Condition_User_Role'
		);

		// Date & Time.
		self::register(
			'date_time',
			[
				'label'     => __( 'Date & Time', 'generateblocks-pro' ),
				'operators' => [ 'before', 'after', 'between', 'on' ],
				'priority'  => 40,
			],
			'GenerateBlocks_Pro_Condition_Date_Time'
		);

		// Device.
		self::register(
			'device',
			[
				'label'     => __( 'Device', 'generateblocks-pro' ),
				'operators' => [ 'is', 'is_not' ],
				'priority'  => 50,
			],
			'GenerateBlocks_Pro_Condition_Device'
		);

		// Referrer.
		self::register(
			'referrer',
			[
				'label'     => __( 'Referrer', 'generateblocks-pro' ),
				'operators' => [ 'is', 'is_not', 'contains', 'not_contains', 'equals', 'starts_with', 'ends_with' ],
				'priority'  => 60,
			],
			'GenerateBlocks_Pro_Condition_Referrer'
		);

		// Post Meta.
		self::register(
			'post_meta',
			[
				'label'     => __( 'Post Meta', 'generateblocks-pro' ),
				'operators' => [ 'exists', 'not_exists', 'equals', 'contains', 'not_contains', 'greater_than', 'less_than', 'includes_any', 'includes_all' ],
				'priority'  => 70,
			],
			'GenerateBlocks_Pro_Condition_Post_Meta'
		);

		// User Meta.
		self::register(
			'user_meta',
			[
				'label'     => __( 'User Meta', 'generateblocks-pro' ),
				'operators' => [ 'exists', 'not_exists', 'equals', 'contains', 'not_contains', 'greater_than', 'less_than', 'includes_any', 'includes_all' ],
				'priority'  => 80,
			],
			'GenerateBlocks_Pro_Condition_User_Meta'
		);

		// Cookie.
		self::register(
			'cookie',
			[
				'label'     => __( 'Cookie', 'generateblocks-pro' ),
				'operators' => [ 'exists', 'not_exists', 'equals', 'contains', 'not_contains', 'starts_with', 'ends_with' ],
				'priority'  => 90,
			],
			'GenerateBlocks_Pro_Condition_Cookie'
		);

		// Language.
		self::register(
			'language',
			[
				'label'     => __( 'Language', 'generateblocks-pro' ),
				'operators' => [ 'is', 'is_not' ],
				'priority'  => 95,
			],
			'GenerateBlocks_Pro_Condition_Language'
		);

		// Options.
		self::register(
			'options',
			[
				'label'     => __( 'Site Options', 'generateblocks-pro' ),
				'operators' => [ 'exists', 'not_exists', 'equals', 'contains', 'not_contains', 'greater_than', 'less_than', 'includes_any', 'includes_all' ],
				'priority'  => 100,
			],
			'GenerateBlocks_Pro_Condition_Options'
		);

		// Author.
		self::register(
			'author',
			[
				'label'     => __( 'Author', 'generateblocks-pro' ),
				'operators' => [ 'is', 'is_not', 'exists', 'not_exists', 'equals', 'contains', 'not_contains', 'includes_any', 'excludes_any' ],
				'priority'  => 110,
			],
			'GenerateBlocks_Pro_Condition_Author'
		);
	}

	/**
	 * Reset registry for testing purposes only.
	 *
	 * @since 2.2.0
	 */
	public static function reset_for_testing() {
		// Only allow in testing environment.
		if ( ! defined( 'GB_TESTING' ) || ! GB_TESTING ) {
			return;
		}

		self::$condition_types = [];
		self::$instances = [];
	}
}
