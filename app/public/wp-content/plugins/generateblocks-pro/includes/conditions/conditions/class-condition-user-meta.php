<?php
/**
 * User Meta Condition
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * User meta condition evaluator.
 */
class GenerateBlocks_Pro_Condition_User_Meta extends GenerateBlocks_Pro_Condition_Abstract {
	/**
	 * Evaluate the condition.
	 *
	 * @param string $rule     The condition rule.
	 * @param string $operator The condition operator.
	 * @param mixed  $value    The value to check against.
	 * @param array  $context  Additional context data.
	 * @return bool
	 */
	public function evaluate( $rule, $operator, $value, $context = [] ) {
		// User must be logged in for user meta conditions.
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		// Parse the meta key and comparison value using standardized method.
		$parsed = $this->parse_meta_field( $rule, $value );
		$meta_key = $parsed['field_name'];
		$comparison_value = $parsed['comparison_value'];

		if ( empty( $meta_key ) ) {
			return false;
		}

		// Handle existence operators using standardized method.
		if ( in_array( $operator, [ 'exists', 'not_exists' ], true ) ) {
			return $this->evaluate_meta_existence( 'user', $user_id, $meta_key, $operator );
		}

		// Handle has_value/no_value operators.
		if ( 'has_value' === $operator ) {
			return $this->evaluate_meta_has_value( 'user', $user_id, $meta_key );
		}
		if ( 'no_value' === $operator ) {
			return ! $this->evaluate_meta_has_value( 'user', $user_id, $meta_key );
		}

		// Handle all other operators using standardized method.
		return $this->evaluate_meta_value( 'user', $user_id, $meta_key, $operator, $comparison_value );
	}

	/**
	 * Get available rules for this condition type.
	 *
	 * @return array
	 */
	public function get_rules() {
		$rules = [
			'custom' => __( 'Custom Meta Key', 'generateblocks-pro' ),
		];

		// Add common user meta keys.
		$common_keys = [
			'first_name'              => __( 'First Name', 'generateblocks-pro' ),
			'last_name'               => __( 'Last Name', 'generateblocks-pro' ),
			'nickname'                => __( 'Nickname', 'generateblocks-pro' ),
			'description'             => __( 'Biographical Info', 'generateblocks-pro' ),
			'show_admin_bar_front'    => __( 'Show Admin Bar', 'generateblocks-pro' ),
		];

		$rules = array_merge( $rules, $common_keys );

		// Allow themes/plugins to add their meta keys.
		return apply_filters( 'generateblocks_user_meta_rules', $rules );
	}

	/**
	 * Get metadata for a specific rule.
	 *
	 * @param string $rule The rule key.
	 * @return array
	 */
	public function get_rule_metadata( $rule ) {
		if ( 'custom' === $rule ) {
			return [
				'needs_value'     => true,
				'value_type'      => 'custom_field',
				'supports_multi'  => true,
			];
		}

		// Show admin bar is a boolean stored as string.
		if ( 'show_admin_bar_front' === $rule ) {
			return [
				'needs_value'     => false,
				'value_type'      => 'none',
				'supports_multi'  => false,
			];
		}

		return [
			'needs_value'     => true,
			'value_type'      => 'text',
			'supports_multi'  => true,
		];
	}

	/**
	 * Sanitize the condition value.
	 *
	 * @param mixed  $value The value to sanitize.
	 * @param string $rule  The rule being used.
	 * @return mixed
	 */
	public function sanitize_value( $value, $rule ) {
		if ( 'custom' === $rule ) {
			return $this->sanitize_custom_value( $value );
		}

		// Handle array values for multi-select.
		if ( is_array( $value ) ) {
			return array_map( 'sanitize_text_field', $value );
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Get operators available for a specific user meta rule.
	 *
	 * @param string $rule The rule key.
	 * @return array Array of operator keys.
	 */
	public function get_operators_for_rule( $rule ) {
		// Show admin bar only needs existence checks.
		if ( 'show_admin_bar_front' === $rule ) {
			return [ 'exists', 'not_exists', 'has_value', 'no_value' ];
		}

		// Text fields - no greater_than/less_than as per feedback.
		if ( in_array( $rule, [ 'first_name', 'last_name', 'nickname', 'description' ], true ) ) {
			if ( $this->rule_supports_multi_select( $rule ) ) {
				return [ 'exists', 'not_exists', 'has_value', 'no_value', 'equals', 'contains', 'not_contains', 'includes_any', 'includes_all', 'excludes_any', 'excludes_all' ];
			} else {
				return [ 'exists', 'not_exists', 'has_value', 'no_value', 'equals', 'contains', 'not_contains' ];
			}
		}

		// Custom meta key supports all operators.
		if ( $this->rule_supports_multi_select( $rule ) ) {
			return [ 'exists', 'not_exists', 'has_value', 'no_value', 'equals', 'contains', 'not_contains', 'greater_than', 'less_than', 'includes_any', 'includes_all', 'excludes_any', 'excludes_all' ];
		} else {
			return [ 'exists', 'not_exists', 'has_value', 'no_value', 'equals', 'contains', 'not_contains', 'greater_than', 'less_than' ];
		}
	}
}
