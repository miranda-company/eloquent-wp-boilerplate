<?php
/**
 * Device Condition
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Device condition evaluator.
 */
class GenerateBlocks_Pro_Condition_Device extends GenerateBlocks_Pro_Condition_Abstract {
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
		$is_match = false;

		if ( ! function_exists( 'wp_is_mobile' ) ) {
			return false;
		}

		switch ( $rule ) {
			case 'mobile':
				$is_match = wp_is_mobile() && ! $this->is_tablet();
				break;

			case 'tablet':
				$is_match = $this->is_tablet();
				break;

			case 'desktop':
				$is_match = ! wp_is_mobile();
				break;

			case 'ios':
				$is_match = $this->is_ios();
				break;

			case 'android':
				$is_match = $this->is_android();
				break;
		}

		return 'is_not' === $operator ? ! $is_match : $is_match;
	}

	/**
	 * Basic tablet detection.
	 *
	 * @return bool
	 */
	private function is_tablet() {
		$user_agent = $this->get_server_var( 'HTTP_USER_AGENT' );
		return (bool) preg_match( '/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $user_agent );
	}

	/**
	 * Check if device is iOS.
	 *
	 * @return bool
	 */
	private function is_ios() {
		$user_agent = $this->get_server_var( 'HTTP_USER_AGENT' );
		return (bool) preg_match( '/(iPhone|iPod|iPad)/i', $user_agent );
	}

	/**
	 * Check if device is Android.
	 *
	 * @return bool
	 */
	private function is_android() {
		$user_agent = $this->get_server_var( 'HTTP_USER_AGENT' );
		return (bool) preg_match( '/Android/i', $user_agent );
	}

	/**
	 * Get available rules for this condition type.
	 *
	 * @return array
	 */
	public function get_rules() {
		$rules = [
			'mobile'  => __( 'Mobile', 'generateblocks-pro' ),
			'tablet'  => __( 'Tablet', 'generateblocks-pro' ),
			'desktop' => __( 'Desktop', 'generateblocks-pro' ),
			'ios'     => __( 'iOS', 'generateblocks-pro' ),
			'android' => __( 'Android', 'generateblocks-pro' ),
		];

		return apply_filters( 'generateblocks_device_rules', $rules );
	}

	/**
	 * Get metadata for a specific rule.
	 *
	 * @param string $rule The rule key.
	 * @return array
	 */
	public function get_rule_metadata( $rule ) {
		return [
			'needs_value' => false,
			'value_type'  => 'none',
		];
	}
}
