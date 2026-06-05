<?php
/**
 * Author Condition
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Author condition evaluator.
 */
class GenerateBlocks_Pro_Condition_Author extends GenerateBlocks_Pro_Condition_Abstract {
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
		$author_id = null;

		// Use context if provided, otherwise get current post.
		$post_id = ! empty( $context['post_id'] ) ? $context['post_id'] : get_the_ID();
		if ( $post_id ) {
			$post = get_post( $post_id );
			if ( $post ) {
				$author_id = $post->post_author;
			}
		} elseif ( is_author() ) {
			// Special case for author archive pages.
			$author_id = get_queried_object_id();
		}

		if ( ! $author_id ) {
			return false;
		}

		// Handle author meta custom fields.
		if ( 'author_meta' === $rule ) {
			$parsed = $this->parse_meta_field( $rule, $value );
			$meta_key = $parsed['field_name'];
			$comparison_value = $parsed['comparison_value'];

			if ( empty( $meta_key ) ) {
				return false;
			}

			// Handle existence operators using standardized method.
			if ( in_array( $operator, [ 'exists', 'not_exists' ], true ) ) {
				return $this->evaluate_meta_existence( 'user', $author_id, $meta_key, $operator );
			}

			// Handle other operators using standardized method.
			return $this->evaluate_meta_value( 'user', $author_id, $meta_key, $operator, $comparison_value );
		}

		// Handle multi-value operators for author selection.
		if ( $this->is_multi_value_operator( $operator ) ) {
			return $this->evaluate_multi_value_generic(
				$operator,
				$value,
				function( $check_value ) use ( $rule, $author_id ) {
					return $this->check_single_author_match( $rule, $check_value, $author_id );
				}
			);
		}

		$is_match = false;

		switch ( $rule ) {
			case 'author_name':
				// Check author display name or login.
				if ( empty( $value ) ) {
					return false;
				}

				$author = get_userdata( $author_id );
				if ( $author ) {
					$is_match = ( $author->display_name === $value ) || ( $author->user_login === $value );
				}
				break;

			case 'author_id':
				if ( empty( $value ) || '' === $value ) {
					return false;
				}

				// Check specific author ID.
				$is_match = ( $author_id == $value ); // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
				break;

			case 'first_name':
			case 'last_name':
			case 'nickname':
			case 'description':
				// Check specific user meta fields using standardized method.
				if ( in_array( $operator, [ 'exists', 'not_exists' ], true ) ) {
					return $this->evaluate_meta_existence( 'user', $author_id, $rule, $operator );
				}

				return $this->evaluate_meta_value( 'user', $author_id, $rule, $operator, $value );
		}

		return 'is_not' === $operator ? ! $is_match : $is_match;
	}

	/**
	 * Check if a single author value matches the current author.
	 *
	 * @param string $rule      The condition rule.
	 * @param mixed  $value     The value to check.
	 * @param int    $author_id Current author ID.
	 * @return bool
	 */
	private function check_single_author_match( $rule, $value, $author_id ) {
		switch ( $rule ) {
			case 'author_id':
				if ( empty( $value ) || '' === $value ) {
					return false;
				}

				return ( $author_id == $value ); // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison

			case 'author_name':
				if ( empty( $value ) || '' === $value ) {
					return false;
				}

				$author = get_userdata( $author_id );
				if ( $author ) {
					return ( $author->display_name === $value ) || ( $author->user_login === $value );
				}
				break;
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
			'author_id'    => __( 'Author ID', 'generateblocks-pro' ),
			'author_name'  => __( 'Author Name', 'generateblocks-pro' ),
			'author_meta'  => __( 'Author Meta Key', 'generateblocks-pro' ),
		];

		// Add common author meta fields.
		$author_fields = [
			'first_name'  => __( 'First Name', 'generateblocks-pro' ),
			'last_name'   => __( 'Last Name', 'generateblocks-pro' ),
			'nickname'    => __( 'Nickname', 'generateblocks-pro' ),
			'description' => __( 'Biographical Info', 'generateblocks-pro' ),
		];

		$rules = array_merge( $rules, $author_fields );

		return apply_filters( 'generateblocks_author_rules', $rules );
	}

	/**
	 * Get metadata for a specific rule.
	 *
	 * @param string $rule The rule key.
	 * @return array
	 */
	public function get_rule_metadata( $rule ) {
		if ( 'author_meta' === $rule ) {
			return [
				'needs_value'     => true,
				'value_type'      => 'custom_field',
				'supports_multi'  => true,
			];
		}

		if ( 'author_id' === $rule ) {
			return [
				'needs_value'     => true,
				'value_type'      => 'object_selector',
				'supports_multi'  => true,
			];
		}

		// Text fields don't need greater_than/less_than.
		return [
			'needs_value'     => true,
			'value_type'      => 'text',
			'supports_multi'  => false,
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
		if ( 'author_meta' === $rule ) {
			return $this->sanitize_custom_value( $value );
		}

		// Handle array values for multi-select.
		if ( is_array( $value ) ) {
			return array_map( 'sanitize_text_field', $value );
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Get operators available for a specific author rule.
	 *
	 * @param string $rule The rule key.
	 * @return array Array of operator keys.
	 */
	public function get_operators_for_rule( $rule ) {
		// Author meta supports all operators.
		if ( 'author_meta' === $rule ) {
			return [ 'exists', 'not_exists', 'equals', 'contains', 'not_contains', 'includes_any', 'includes_all', 'excludes_any', 'excludes_all' ];
		}

		// Author ID supports multi-select.
		if ( 'author_id' === $rule ) {
			return [ 'is', 'is_not', 'includes_any', 'excludes_any' ];
		}

		// Text fields - no greater_than/less_than.
		if ( in_array( $rule, [ 'author_name', 'first_name', 'last_name', 'nickname', 'description' ], true ) ) {
			return [ 'exists', 'not_exists', 'equals', 'contains', 'not_contains' ];
		}

		return [ 'is', 'is_not' ];
	}
}
