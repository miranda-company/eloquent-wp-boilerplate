<?php
/**
 * The Advanced Conditions class file.
 *
 * @package GenerateBlocksTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Main class for advanced conditions system.
 *
 * @since 1.0.0
 */
class GenerateBlocks_Pro_Conditions extends GenerateBlocks_Pro_Singleton {
	/**
	 * Initialize the conditions system.
	 */
	public function init() {
		// Register core condition types early for REST API, but after init for translations.
		add_action( 'init', [ $this, 'register_core_conditions' ], 0 );

		// Allow third-party registrations after core.
		add_action(
			'init',
			function() {
				do_action( 'generateblocks_register_conditions' );
			},
			10
		);
	}

	/**
	 * Get the capability required for conditions.
	 *
	 * @since 2.4.0
	 *
	 * Breaking changes in 2.4.0:
	 * - Previously all operations required only 'edit_posts' capability
	 * - Now 'manage' context (create/edit/delete) requires 'manage_options' by default
	 * - Use the 'generateblocks_conditions_capability' filter to customize
	 *
	 * @param string $context The context: 'use' (select existing) or 'manage' (create/edit/dashboard).
	 * @return string The capability required.
	 */
	public static function get_conditions_capability( $context = 'use' ) {
		// Default capabilities.
		if ( 'manage' === $context ) {
			// Can create/edit/access dashboard - default to manage_options.
			$capability = 'manage_options';
		} else {
			// Can select/use existing conditions - anyone who can edit posts.
			$capability = 'edit_posts';
		}

		/**
		 * Filter the capability required for conditions.
		 *
		 * @since 2.4.0
		 * @param string $capability The capability required.
		 * @param string $context The context: 'use' or 'manage'.
		 */
		return apply_filters( 'generateblocks_conditions_capability', $capability, $context );
	}

	/**
	 * Check if current user can use conditions.
	 *
	 * @since 2.4.0
	 * @param string $context The context: 'use' (select existing) or 'manage' (create/edit/dashboard).
	 * @return bool
	 */
	public static function current_user_can_use_conditions( $context = 'use' ) {
		$capability = self::get_conditions_capability( $context );
		return current_user_can( $capability );
	}

	/**
	 * Register core condition types.
	 */
	public function register_core_conditions() {
		// Check if already registered to avoid duplicates.
		if ( ! empty( GenerateBlocks_Pro_Conditions_Registry::get_all() ) ) {
			return;
		}

		GenerateBlocks_Pro_Conditions_Registry::register_core_types();
	}

	/**
	 * Sanitize conditions data.
	 *
	 * @param array $meta_value The conditions array.
	 * @return array
	 */
	public function sanitize_conditions( $meta_value ) {
		if ( ! is_array( $meta_value ) ) {
			return [
				'logic' => 'OR',
				'groups' => [],
			];
		}

		$sanitized = [
			'logic'  => in_array( $meta_value['logic'] ?? 'OR', [ 'AND', 'OR' ], true ) ? $meta_value['logic'] : 'OR',
			'groups' => [],
		];

		if ( isset( $meta_value['groups'] ) && is_array( $meta_value['groups'] ) ) {
			foreach ( $meta_value['groups'] as $group ) {
				if ( ! is_array( $group ) ) {
					continue;
				}

				$sanitized_group = [
					'logic'      => in_array( $group['logic'] ?? 'AND', [ 'AND', 'OR' ], true ) ? $group['logic'] : 'AND',
					'conditions' => [],
				];

				if ( isset( $group['conditions'] ) && is_array( $group['conditions'] ) ) {
					foreach ( $group['conditions'] as $condition ) {
						if ( ! is_array( $condition ) ) {
							continue;
						}

						$sanitized_condition = [
							'type'     => sanitize_text_field( $condition['type'] ?? '' ),
							'rule'     => sanitize_text_field( $condition['rule'] ?? '' ),
							'operator' => sanitize_text_field( $condition['operator'] ?? '' ),
							'value'    => $this->sanitize_condition_value( $condition ),
						];

						$sanitized_group['conditions'][] = $sanitized_condition;
					}
				}

				$sanitized['groups'][] = $sanitized_group;
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize condition value - handles custom field two-part values and arrays
	 *
	 * @param array $condition The condition array.
	 * @return string|array
	 */
	private function sanitize_condition_value( $condition ) {
		$value = $condition['value'] ?? '';

		if ( empty( $value ) ) {
			return '';
		}

		// Handle array values for multi-select operators.
		if ( is_array( $value ) ) {
			// Filter out empty values to improve data quality.
			return array_filter( array_map( 'sanitize_text_field', $value ) );
		}

		// Try to decode JSON array.
		$decoded = json_decode( $value, true );
		if ( is_array( $decoded ) ) {
			// Filter out empty values to improve data quality.
			return wp_json_encode( array_filter( array_map( 'sanitize_text_field', $decoded ) ) );
		}

		// Extended condition types support.
		$is_custom_field = ( 'custom' === ( $condition['rule'] ?? '' ) ) &&
		in_array( $condition['type'] ?? '', [ 'post_meta', 'user_meta', 'query_arg', 'referrer', 'cookie', 'options' ], true );

		if ( $is_custom_field && false !== strpos( $value, '|' ) ) {
			$parts = explode( '|', $value, 2 );
			$field_name = sanitize_text_field( $parts[0] );
			$comparison_value = sanitize_text_field( $parts[1] ?? '' );

			// Rebuild the value with sanitized parts.
			return $field_name . '|' . $comparison_value;
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Get all available condition types.
	 *
	 * @return array
	 */
	public static function get_condition_types() {
		// Ensure conditions are registered if called early.
		if ( empty( GenerateBlocks_Pro_Conditions_Registry::get_all() ) && did_action( 'init' ) ) {
			self::get_instance()->register_core_conditions();
		}

		$types = GenerateBlocks_Pro_Conditions_Registry::get_all();

		// Format for backward compatibility.
		$formatted = [];
		foreach ( $types as $key => $type ) {
			$formatted[ $key ] = [
				'label'     => $type['label'],
				'class'     => $type['class'],
				'operators' => $type['operators'],
			];
		}

		return apply_filters( 'generateblocks_condition_types', $formatted );
	}

	/**
	 * Get rules for a specific condition type.
	 *
	 * @param string $type The condition type.
	 * @return array
	 */
	public static function get_condition_rules( $type = '' ) {
		// Ensure conditions are registered if called early.
		if ( empty( GenerateBlocks_Pro_Conditions_Registry::get_all() ) && did_action( 'init' ) ) {
			self::get_instance()->register_core_conditions();
		}

		$instance = GenerateBlocks_Pro_Conditions_Registry::get_instance( $type );

		if ( ! $instance ) {
			return [];
		}

		$rules = $instance->get_rules();

		return apply_filters( 'generateblocks_condition_rules', $rules, $type );
	}

	/**
	 * Get location rules (from your existing system).
	 *
	 * @return array
	 */
	private static function get_location_rules() {
		$rules = [
			'general:site'       => __( 'Entire Site', 'generateblocks-pro' ),
			'general:front_page' => __( 'Front Page', 'generateblocks-pro' ),
			'general:blog'       => __( 'Blog', 'generateblocks-pro' ),
			'general:singular'   => __( 'All Singular', 'generateblocks-pro' ),
			'general:archive'    => __( 'All Archives', 'generateblocks-pro' ),
			'general:author'     => __( 'Author Archives', 'generateblocks-pro' ),
			'general:date'       => __( 'Date Archives', 'generateblocks-pro' ),
			'general:search'     => __( 'Search Results', 'generateblocks-pro' ),
			'general:404'        => __( '404 Template', 'generateblocks-pro' ),
		];

		// Add post types.
		$post_types = get_post_types( [ 'public' => true ], 'objects' );
		foreach ( $post_types as $post_type_slug => $post_type ) {
			$rules[ 'post:' . $post_type_slug ] = $post_type->labels->singular_name;
			if ( $post_type->has_archive ) {
				// translators: %s is the singular name of the post type.
				$rules[ 'archive:' . $post_type_slug ] = sprintf( __( '%s Archive', 'generateblocks-pro' ), $post_type->labels->singular_name );
			}
		}

		return $rules;
	}

	/**
	 * Get user role rules.
	 *
	 * @return array
	 */
	private static function get_user_role_rules() {
		$rules = [
			'general:all'        => __( 'All Users', 'generateblocks-pro' ),
			'general:logged_in'  => __( 'Logged In', 'generateblocks-pro' ),
			'general:logged_out' => __( 'Logged Out', 'generateblocks-pro' ),
		];

		if ( function_exists( 'get_editable_roles' ) ) {
			$roles = get_editable_roles();
			foreach ( $roles as $slug => $data ) {
				$rules[ $slug ] = $data['name'];
			}
		}

		return $rules;
	}

	/**
	 * Get metadata for a specific rule.
	 *
	 * @param string $type The condition type.
	 * @param string $rule The rule key.
	 * @return array
	 */
	public static function get_rule_metadata( $type, $rule ) {
		// Ensure conditions are registered if called early.
		if ( empty( GenerateBlocks_Pro_Conditions_Registry::get_all() ) && did_action( 'init' ) ) {
			self::get_instance()->register_core_conditions();
		}

		$instance = GenerateBlocks_Pro_Conditions_Registry::get_instance( $type );

		if ( ! $instance ) {
			return [
				'needs_value'     => true,
				'value_type'      => 'text',
				'supports_multi'  => false,
			];
		}

		$metadata = $instance->get_rule_metadata( $rule );

		return apply_filters( 'generateblocks_rule_metadata', $metadata, $type, $rule );
	}

	/**
	 * Main function to determine if conditions are met.
	 *
	 * @param array $conditions The conditions array.
	 * @param array $context    Optional context data (e.g., post_id for loop context).
	 * @return bool
	 */
	public static function show( $conditions, $context = [] ) {
		if ( empty( $conditions ) || empty( $conditions['groups'] ) ) {
			return true;
		}

		$group_results = [];

		foreach ( $conditions['groups'] as $group ) {
			if ( empty( $group['conditions'] ) ) {
				$group_results[] = true;
				continue;
			}

			$condition_results = [];

			foreach ( $group['conditions'] as $condition ) {
				$condition_results[] = self::evaluate_single_condition( $condition, $context );
			}

			// Apply group logic (AND/OR within group).
			if ( 'OR' === $group['logic'] ) {
				$group_results[] = in_array( true, $condition_results, true );
			} else {
				$group_results[] = ! in_array( false, $condition_results, true );
			}
		}

		// Apply top-level logic to combine group results.
		$top_logic = $conditions['logic'] ?? 'OR';

		if ( 'AND' === $top_logic ) {
			return ! in_array( false, $group_results, true );
		} else {
			return in_array( true, $group_results, true );
		}
	}

	/**
	 * Evaluate a single condition.
	 *
	 * @param array $condition The condition to evaluate.
	 * @param array $context   Optional context data (e.g., post_id for loop context).
	 * @return bool
	 */
	private static function evaluate_single_condition( $condition, $context = [] ) {
		// Ensure conditions are registered.
		if ( empty( GenerateBlocks_Pro_Conditions_Registry::get_all() ) && did_action( 'init' ) ) {
			self::get_instance()->register_core_conditions();
		}

		if ( empty( $condition['type'] ) || empty( $condition['rule'] ) || empty( $condition['operator'] ) ) {
			return false;
		}

		return GenerateBlocks_Pro_Conditions_Registry::evaluate(
			$condition['type'],
			$condition['rule'],
			$condition['operator'],
			$condition['value'] ?? '',
			$context // Pass context through to conditions.
		);
	}
}

// Initialize the singleton instance.
GenerateBlocks_Pro_Conditions::get_instance()->init();
