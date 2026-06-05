<?php
/**
 * Cookie Condition
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cookie condition evaluator.
 */
class GenerateBlocks_Pro_Condition_Cookie extends GenerateBlocks_Pro_Condition_Abstract {
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
		// Parse the cookie name and comparison value using standardized method.
		$parsed = $this->parse_meta_field( $rule, $value );
		$cookie_name = $parsed['field_name'];
		$comparison_value = $parsed['comparison_value'];

		if ( empty( $cookie_name ) ) {
			return false;
		}

		switch ( $operator ) {
			case 'exists':
				return $this->cookie_exists( $cookie_name );

			case 'not_exists':
				return ! $this->cookie_exists( $cookie_name );

			case 'has_value':
				return $this->cookie_has_value( $cookie_name );

			case 'no_value':
				return ! $this->cookie_has_value( $cookie_name );

			case 'equals':
				$cookie_value = $this->get_cookie_value( $cookie_name );
				return null !== $cookie_value && $cookie_value === $comparison_value;

			case 'contains':
				$cookie_value = $this->get_cookie_value( $cookie_name );
				return null !== $cookie_value && false !== strpos( $cookie_value, $comparison_value );

			case 'not_contains':
				$cookie_value = $this->get_cookie_value( $cookie_name );
				return null === $cookie_value || false === strpos( $cookie_value, $comparison_value );

			case 'starts_with':
				$cookie_value = $this->get_cookie_value( $cookie_name );
				return null !== $cookie_value && 0 === strpos( $cookie_value, $comparison_value );

			case 'ends_with':
				$cookie_value = $this->get_cookie_value( $cookie_name );
				return null !== $cookie_value && substr( $cookie_value, -strlen( $comparison_value ) ) === $comparison_value;

			default:
				return false;
		}
	}

	/**
	 * Get available rules for this condition type.
	 *
	 * @return array
	 */
	public function get_rules() {
		$rules = [
			'custom' => __( 'Custom Cookie', 'generateblocks-pro' ),
		];

		// Add common cookie rules that might be useful.
		$common_cookies = [
			'wordpress_logged_in' => __( 'WordPress Logged In Cookie', 'generateblocks-pro' ),
			'comment_author'      => __( 'Comment Author Cookie', 'generateblocks-pro' ),
		];

		$rules = array_merge( $rules, $common_cookies );

		// Allow filtering to add predefined cookies.
		return apply_filters( 'generateblocks_cookie_rules', $rules );
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
				'needs_value' => true,
				'value_type'  => 'custom_field',
			];
		}

		// WordPress cookies typically need existence checks only.
		if ( in_array( $rule, [ 'wordpress_logged_in', 'comment_author' ], true ) ) {
			return [
				'needs_value' => false,
				'value_type'  => 'none',
			];
		}

		return [
			'needs_value' => true,
			'value_type'  => 'text',
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

		return sanitize_text_field( $value );
	}

	/**
	 * Get operators available for a specific cookie rule.
	 *
	 * @param string $rule The rule key.
	 * @return array Array of operator keys.
	 */
	public function get_operators_for_rule( $rule ) {
		// WordPress cookies typically only need existence checks.
		if ( in_array( $rule, [ 'wordpress_logged_in', 'comment_author' ], true ) ) {
			return [ 'exists', 'not_exists', 'has_value', 'no_value' ];
		}

		// Custom cookies support all text operators including not_contains.
		return [ 'exists', 'not_exists', 'has_value', 'no_value', 'equals', 'contains', 'not_contains', 'starts_with', 'ends_with' ];
	}
}
