<?php
/**
 * Referrer Condition
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Referrer condition evaluator.
 */
class GenerateBlocks_Pro_Condition_Referrer extends GenerateBlocks_Pro_Condition_Abstract {
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
		$referrer = $this->get_server_var( 'HTTP_REFERER' );

		// Handle direct traffic (no referrer).
		if ( 'direct' === $rule ) {
			$has_referrer = ! empty( $referrer );
			return 'is_not' === $operator ? $has_referrer : ! $has_referrer;
		}

		// Handle custom referrer.
		if ( 'custom' === $rule ) {
			// Handle has_value/no_value operators first.
			if ( 'has_value' === $operator ) {
				return $this->referrer_has_value();
			}
			if ( 'no_value' === $operator ) {
				return ! $this->referrer_has_value();
			}

			$parsed = $this->parse_custom_value( $value );
			$comparison_value = $parsed['comparison_value'] ? $parsed['comparison_value'] : $parsed['field_name'];

			if ( empty( $comparison_value ) ) {
				return false;
			}

			$is_match = false;

			switch ( $operator ) {
				case 'contains':
					$is_match = false !== strpos( $referrer, $comparison_value );
					break;

				case 'not_contains':
					$is_match = false === strpos( $referrer, $comparison_value );
					break;

				case 'equals':
					$is_match = $referrer === $comparison_value;
					break;

				case 'starts_with':
					$is_match = 0 === strpos( $referrer, $comparison_value );
					break;

				case 'ends_with':
					$is_match = substr( $referrer, -strlen( $comparison_value ) ) === $comparison_value;
					break;
			}

			return $is_match;
		}

		return false;
	}

	/**
	 * Get available rules for this condition type.
	 *
	 * @return array
	 */
	public function get_rules() {
		$rules = [
			'direct' => __( 'Direct Traffic (No Referrer)', 'generateblocks-pro' ),
			'custom' => __( 'Custom Referrer', 'generateblocks-pro' ),
		];

		return apply_filters( 'generateblocks_referrer_rules', $rules );
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

		if ( 'direct' === $rule ) {
			return [
				'needs_value' => false,
				'value_type'  => 'none',
			];
		}

		return $this->get_default_rule_metadata();
	}

	/**
	 * Get operators available for a specific referrer rule.
	 *
	 * @param string $rule The rule key.
	 * @return array Array of operator keys.
	 */
	public function get_operators_for_rule( $rule ) {
		if ( 'direct' === $rule ) {
			// Direct traffic only supports is/is_not.
			return [ 'is', 'is_not' ];
		}

		if ( 'custom' === $rule ) {
			// Custom referrer supports all text comparison operators including not_contains.
			return [ 'has_value', 'no_value', 'contains', 'not_contains', 'equals', 'starts_with', 'ends_with' ];
		}

		return [ 'contains', 'not_contains', 'equals', 'starts_with' ];
	}
}
