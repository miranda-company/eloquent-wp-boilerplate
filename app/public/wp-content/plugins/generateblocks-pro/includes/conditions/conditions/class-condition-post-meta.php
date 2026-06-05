<?php
/**
 * Post Meta Condition
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post meta condition evaluator.
 */
class GenerateBlocks_Pro_Condition_Post_Meta extends GenerateBlocks_Pro_Condition_Abstract {
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
		// Use context if provided, otherwise get current post ID.
		$post_id = ! empty( $context['post_id'] ) ? $context['post_id'] : get_the_ID();

		if ( ! $post_id ) {
			return false;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
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
			return $this->evaluate_meta_existence( 'post', $post->ID, $meta_key, $operator );
		}

		// Handle has_value/no_value operators.
		if ( 'has_value' === $operator ) {
			return $this->evaluate_meta_has_value( 'post', $post->ID, $meta_key );
		}
		if ( 'no_value' === $operator ) {
			return ! $this->evaluate_meta_has_value( 'post', $post->ID, $meta_key );
		}

		// Handle all other operators using standardized method.
		return $this->evaluate_meta_value( 'post', $post->ID, $meta_key, $operator, $comparison_value );
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

		// Add common meta keys.
		$common_keys = [
			'_thumbnail_id'    => __( 'Has Featured Image', 'generateblocks-pro' ),
			'_wp_page_template' => __( 'Page Template', 'generateblocks-pro' ),
		];

		$rules = array_merge( $rules, $common_keys );

		// Allow themes/plugins to add their meta keys.
		return apply_filters( 'generateblocks_post_meta_rules', $rules );
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

		// Special handling for specific meta keys.
		if ( '_thumbnail_id' === $rule ) {
			return [
				'needs_value'     => false,
				'value_type'      => 'none',
				'supports_multi'  => false,
			];
		}

		// Page template is text-based.
		if ( '_wp_page_template' === $rule ) {
			return [
				'needs_value'     => true,
				'value_type'      => 'text',
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
	 * Get operators available for a specific post meta rule.
	 *
	 * @param string $rule The rule key.
	 * @return array Array of operator keys.
	 */
	public function get_operators_for_rule( $rule ) {
		// Special case for thumbnail - only existence checks.
		if ( '_thumbnail_id' === $rule ) {
			return [ 'exists', 'not_exists', 'has_value', 'no_value' ];
		}

		// Page template - only text operations, no greater_than/less_than.
		if ( '_wp_page_template' === $rule ) {
			if ( $this->rule_supports_multi_select( $rule ) ) {
				return [ 'exists', 'not_exists', 'has_value', 'no_value', 'equals', 'contains', 'not_contains', 'includes_any', 'includes_all', 'excludes_any', 'excludes_all' ];
			} else {
				return [ 'exists', 'not_exists', 'has_value', 'no_value', 'equals', 'contains', 'not_contains' ];
			}
		}

		// Custom meta key supports all operators including numeric.
		if ( $this->rule_supports_multi_select( $rule ) ) {
			return [ 'exists', 'not_exists', 'has_value', 'no_value', 'equals', 'contains', 'not_contains', 'greater_than', 'less_than', 'includes_any', 'includes_all', 'excludes_any', 'excludes_all' ];
		} else {
			return [ 'exists', 'not_exists', 'has_value', 'no_value', 'equals', 'contains', 'not_contains', 'greater_than', 'less_than' ];
		}
	}
}
