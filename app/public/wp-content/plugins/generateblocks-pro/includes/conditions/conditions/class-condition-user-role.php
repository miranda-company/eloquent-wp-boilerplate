<?php
/**
 * User Role Condition
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * User role condition evaluator.
 */
class GenerateBlocks_Pro_Condition_User_Role extends GenerateBlocks_Pro_Condition_Abstract {
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
		// Handle multi-value operators for roles and capabilities only.
		if ( $this->is_multi_value_operator( $operator ) && $this->rule_supports_multi_select( $rule ) ) {
			return $this->evaluate_multi_value_generic(
				$operator,
				$value,
				function( $check_value ) {
					return $this->check_single_user_role( $check_value );
				}
			);
		}

		$is_match = false;

		switch ( $rule ) {
			case 'general:logged_in':
				$is_match = is_user_logged_in();
				break;

			case 'general:logged_out':
				$is_match = ! is_user_logged_in();
				break;

			default:
				// Check specific role or capability.
				$user = wp_get_current_user();
				if ( $user && $user->exists() ) {
					// Check if it's a capability (prefixed with 'cap:').
					if ( 0 === strpos( $rule, 'cap:' ) ) {
						// Remove prefix.
						$capability = substr( $rule, 4 );
						$is_match = user_can( $user, $capability );
					} else {
						// It's a role.
						$is_match = in_array( $rule, (array) $user->roles, true );
					}
				}
				break;
		}

		return 'is_not' === $operator ? ! $is_match : $is_match;
	}

	/**
	 * Check if current user matches a single role/capability.
	 *
	 * @param string $check_value The role or capability to check.
	 * @return bool
	 */
	private function check_single_user_role( $check_value ) {
		$user = wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			// For logged out users, only check against general rules.
			return 'general:logged_out' === $check_value;
		}

		switch ( $check_value ) {
			case 'general:logged_in':
				return true; // User is logged in if we reach this point.

			case 'general:logged_out':
				return false; // User is logged in.

			default:
				// Check specific role or capability.
				if ( 0 === strpos( $check_value, 'cap:' ) ) {
					// Remove prefix.
					$capability = substr( $check_value, 4 );
					return user_can( $user, $capability );
				} else {
					// It's a role.
					$user_roles = (array) $user->roles;
					return in_array( $check_value, $user_roles, true );
				}
		}
	}

	/**
	 * Get available rules for this condition type.
	 *
	 * @return array
	 */
	public function get_rules() {
		$rules = [
			'general:logged_in'  => __( 'Logged In', 'generateblocks-pro' ),
			'general:logged_out' => __( 'Logged Out', 'generateblocks-pro' ),
		];

		// Ensure get_editable_roles() is available.
		if ( ! function_exists( 'get_editable_roles' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}

		// Add WordPress roles.
		if ( function_exists( 'get_editable_roles' ) ) {
			$roles = get_editable_roles();
			foreach ( $roles as $slug => $data ) {
				$rules[ $slug ] = translate_user_role( $data['name'] );
			}
		}

		// Add capabilities - keep the most commonly used ones.
		$capabilities = [
			'edit_posts'             => __( 'Can Edit Posts', 'generateblocks-pro' ),
			'edit_pages'             => __( 'Can Edit Pages', 'generateblocks-pro' ),
			'edit_published_posts'   => __( 'Can Edit Published Posts', 'generateblocks-pro' ),
			'edit_others_posts'      => __( 'Can Edit Others Posts', 'generateblocks-pro' ),
			'publish_posts'          => __( 'Can Publish Posts', 'generateblocks-pro' ),
			'manage_options'         => __( 'Can Manage Options', 'generateblocks-pro' ),
			'moderate_comments'      => __( 'Can Moderate Comments', 'generateblocks-pro' ),
		];

		// Capabilities are prefixed with 'cap:' to distinguish from roles.
		foreach ( $capabilities as $cap => $label ) {
			$rules[ 'cap:' . $cap ] = $label;
		}

		return apply_filters( 'generateblocks_user_role_rules', $rules );
	}

	/**
	 * Get metadata for a specific rule.
	 *
	 * @param string $rule The rule key.
	 * @return array
	 */
	public function get_rule_metadata( $rule ) {
		return [
			'needs_value'     => false,
			'value_type'      => 'none',
			'supports_multi'  => false,
		];
	}

	/**
	 * Get operators available for a specific user role rule.
	 *
	 * @param string $rule The rule key.
	 * @return array Array of operator keys.
	 */
	public function get_operators_for_rule( $rule ) {
		// General status rules - simple is/is_not only.
		if ( in_array( $rule, [ 'general:logged_in', 'general:logged_out' ], true ) ) {
			return [ 'is', 'is_not' ];
		}

		// For individual roles and capabilities, support multi-select if UI supports it.
		if ( $this->rule_supports_multi_select( $rule ) ) {
			return [ 'is', 'is_not', 'includes_any', 'excludes_any' ];
		}

		// Fallback to simple operators.
		return [ 'is', 'is_not' ];
	}
}
