<?php
/**
 * Options Condition
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Options condition evaluator.
 */
class GenerateBlocks_Pro_Condition_Options extends GenerateBlocks_Pro_Condition_Abstract {
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
		// Parse the option name and comparison value using standardized method.
		$parsed = $this->parse_meta_field( $rule, $value );
		$option_name = $parsed['field_name'];
		$comparison_value = $parsed['comparison_value'];

		if ( empty( $option_name ) ) {
			return false;
		}

		// Handle existence operators using standardized method.
		if ( in_array( $operator, [ 'exists', 'not_exists' ], true ) ) {
			return $this->evaluate_meta_existence( 'option', 0, $option_name, $operator );
		}

		// Handle has_value/no_value operators.
		if ( 'has_value' === $operator ) {
			return $this->evaluate_meta_has_value( 'option', 0, $option_name );
		}
		if ( 'no_value' === $operator ) {
			return ! $this->evaluate_meta_has_value( 'option', 0, $option_name );
		}

		// Handle all other operators using standardized method.
		return $this->evaluate_meta_value( 'option', 0, $option_name, $operator, $comparison_value );
	}

	/**
	 * Get available rules for this condition type.
	 *
	 * @return array
	 */
	public function get_rules() {
		$rules = [
			'custom' => __( 'Custom Option', 'generateblocks-pro' ),
		];

		// Add common WordPress options.
		$common_options = [
			'blogname'               => __( 'Site Title', 'generateblocks-pro' ),
			'blogdescription'        => __( 'Site Tagline', 'generateblocks-pro' ),
			'timezone_string'        => __( 'Timezone', 'generateblocks-pro' ),
			'date_format'            => __( 'Date Format', 'generateblocks-pro' ),
			'time_format'            => __( 'Time Format', 'generateblocks-pro' ),
		];

		$rules = array_merge( $rules, $common_options );

		// Allow themes/plugins to add their options.
		return apply_filters( 'generateblocks_options_rules', $rules );
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

		// Boolean options need special handling.
		$boolean_options = [
			'users_can_register',
			'comment_registration',
			'close_comments_for_old_posts',
			'comment_moderation',
			'moderation_notify',
			'comments_notify',
		];

		if ( in_array( $rule, $boolean_options, true ) ) {
			return [
				'needs_value'     => false,
				'value_type'      => 'none',
				'supports_multi'  => false,
			];
		}

		// Numeric options.
		$numeric_options = [
			'posts_per_page',
			'comments_per_page',
			'start_of_week',
		];

		if ( in_array( $rule, $numeric_options, true ) ) {
			return [
				'needs_value'     => true,
				'value_type'      => 'number',
				'supports_multi'  => true,
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
	 * Get operators available for a specific option rule.
	 *
	 * @param string $rule The rule key.
	 * @return array Array of operator keys.
	 */
	public function get_operators_for_rule( $rule ) {
		// Boolean options only need existence checks.
		$boolean_options = [
			'users_can_register',
			'comment_registration',
			'close_comments_for_old_posts',
			'comment_moderation',
			'moderation_notify',
			'comments_notify',
		];

		if ( in_array( $rule, $boolean_options, true ) ) {
			return [ 'exists', 'not_exists', 'has_value', 'no_value' ];
		}

		// Numeric options support all operators.
		$numeric_options = [
			'posts_per_page',
			'comments_per_page',
			'start_of_week',
		];

		if ( in_array( $rule, $numeric_options, true ) ) {
			if ( $this->rule_supports_multi_select( $rule ) ) {
				return [ 'exists', 'not_exists', 'has_value', 'no_value', 'equals', 'greater_than', 'less_than', 'includes_any', 'includes_all', 'excludes_any', 'excludes_all' ];
			} else {
				return [ 'exists', 'not_exists', 'has_value', 'no_value', 'equals', 'greater_than', 'less_than' ];
			}
		}

		// Text options support text operators.
		if ( $this->rule_supports_multi_select( $rule ) ) {
			return [ 'exists', 'not_exists', 'has_value', 'no_value', 'equals', 'contains', 'not_contains', 'includes_any', 'includes_all', 'excludes_any', 'excludes_all' ];
		} else {
			return [ 'exists', 'not_exists', 'has_value', 'no_value', 'equals', 'contains', 'not_contains' ];
		}
	}
}
