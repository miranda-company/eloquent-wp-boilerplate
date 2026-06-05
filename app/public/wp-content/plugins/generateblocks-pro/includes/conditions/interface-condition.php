<?php
/**
 * Condition Interface
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface for condition evaluators.
 */
interface GenerateBlocks_Pro_Condition_Interface {
	/**
	 * Evaluate the condition.
	 *
	 * @param string $rule     The condition rule.
	 * @param string $operator The condition operator.
	 * @param mixed  $value    The value to check against.
	 * @param array  $context  Additional context data.
	 * @return bool
	 */
	public function evaluate( $rule, $operator, $value, $context = [] );

	/**
	 * Get available rules for this condition type.
	 *
	 * @return array Array of rule_key => rule_label pairs.
	 */
	public function get_rules();

	/**
	 * Get metadata for a specific rule.
	 *
	 * @param string $rule The rule key.
	 * @return array Rule metadata.
	 */
	public function get_rule_metadata( $rule );

	/**
	 * Get operators available for a specific rule.
	 * This method should be public since the REST API calls it directly.
	 *
	 * @param string $rule The rule key.
	 * @return array Array of operator keys.
	 */
	public function get_operators_for_rule( $rule );

	/**
	 * Sanitize the condition value.
	 *
	 * @param mixed  $value The value to sanitize.
	 * @param string $rule  The rule being used.
	 * @return mixed
	 */
	public function sanitize_value( $value, $rule );
}
