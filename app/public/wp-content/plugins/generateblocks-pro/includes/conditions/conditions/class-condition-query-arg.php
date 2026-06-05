<?php
/**
 * Query Argument Condition
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Query argument condition evaluator.
 */
class GenerateBlocks_Pro_Condition_Query_Arg extends GenerateBlocks_Pro_Condition_Abstract {
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
		// Parse the parameter name and comparison value using standardized method.
		$parsed = $this->parse_meta_field( $rule, $value );
		$param_name = $parsed['field_name'];
		$comparison_value = $parsed['comparison_value'];

		if ( empty( $param_name ) ) {
			return false;
		}

		switch ( $operator ) {
			case 'exists':
				return $this->query_param_exists( $param_name );

			case 'not_exists':
				return ! $this->query_param_exists( $param_name );

			case 'has_value':
				return $this->query_param_has_value( $param_name );

			case 'no_value':
				return ! $this->query_param_has_value( $param_name );

			case 'equals':
				$param_value = $this->get_query_param_raw( $param_name );
				// For arrays, check if comparison value is in the array.
				if ( is_array( $param_value ) ) {
					return in_array( $comparison_value, $param_value, true );
				}
				return null !== $param_value && $param_value === $comparison_value;

			case 'contains':
				$param_value = $this->get_query_param_raw( $param_name );
				// String operations only work on strings, return false for arrays.
				if ( ! is_string( $param_value ) ) {
					return false;
				}
				return false !== strpos( $param_value, $comparison_value );

			case 'not_contains':
				$param_value = $this->get_query_param_raw( $param_name );
				// String operations only work on strings, return true for arrays (they don't "contain" the string).
				if ( ! is_string( $param_value ) ) {
					return true;
				}
				return false === strpos( $param_value, $comparison_value );

			case 'starts_with':
				$param_value = $this->get_query_param_raw( $param_name );
				// String operations only work on strings, return false for arrays.
				if ( ! is_string( $param_value ) ) {
					return false;
				}
				return 0 === strpos( $param_value, $comparison_value );

			case 'ends_with':
				$param_value = $this->get_query_param_raw( $param_name );
				// String operations only work on strings, return false for arrays.
				if ( ! is_string( $param_value ) ) {
					return false;
				}
				return substr( $param_value, -strlen( $comparison_value ) ) === $comparison_value;

			default:
				return false;
		}
	}

	/**
	 * Get raw query parameter value without sanitization to preserve arrays.
	 *
	 * @param string $param_name Parameter name.
	 * @return mixed|null
	 */
	private function get_query_param_raw( $param_name ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET[ $param_name ] ) ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$value = wp_unslash( $_GET[ $param_name ] );

		// Sanitize based on type.
		if ( is_array( $value ) ) {
			return array_map( 'sanitize_text_field', $value );
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Get available rules for this condition type.
	 *
	 * @return array
	 */
	public function get_rules() {
		$rules = [
			'custom' => __( 'Custom Parameter', 'generateblocks-pro' ),
		];

		// Allow filtering to add predefined parameters.
		return apply_filters( 'generateblocks_query_arg_rules', $rules );
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

		return [
			'needs_value' => true,
			'value_type'  => 'text',
		];
	}

	/**
	 * Get operators available for a specific query arg rule.
	 *
	 * @param string $rule The rule key.
	 * @return array Array of operator keys.
	 */
	public function get_operators_for_rule( $rule ) {
		// All query args support the same text operators.
		return [ 'exists', 'not_exists', 'has_value', 'no_value', 'equals', 'contains', 'not_contains', 'starts_with', 'ends_with' ];
	}
}
