<?php
/**
 * Location Condition
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Location condition evaluator.
 */
class GenerateBlocks_Pro_Condition_Location extends GenerateBlocks_Pro_Condition_Abstract {
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
		$current_location = $this->get_current_location();

		// Handle parent/child relationships first (they have their own multi-value handling).
		if ( in_array( $rule, [ 'child_of', 'parent_of' ], true ) ) {
			return $this->evaluate_hierarchy( $rule, $operator, $value, $context );
		}

		// Handle post taxonomy terms.
		if ( 0 === strpos( $rule, 'post_terms:' ) ) {
			return $this->evaluate_post_terms( $rule, $operator, $value, $context );
		}

		// Handle multi-value operators for non-hierarchical rules.
		if ( $this->is_multi_value_operator( $operator ) ) {
			return $this->evaluate_multi_value_generic(
				$operator,
				$value,
				function( $check_value ) use ( $rule, $current_location ) {
					return $this->check_single_location_match( $rule, $check_value, $current_location );
				}
			);
		}

		$is_match = false;

		// Check if current location matches the rule.
		if ( in_array( $rule, $current_location['location'], true ) ) {
			if ( empty( $value ) ) {
				$is_match = true;
			} else {
				// Check specific object (post ID, term ID, etc.).
				$is_match = ( $current_location['object'] == $value ); // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			}
		}

		// Special cases - use true original query to check page context, not loop context.
		if ( ! $is_match ) {
			global $wp_the_query;
			switch ( $rule ) {
				case 'general:site':
					$is_match = true;
					break;
				case 'general:singular':
					$is_match = $wp_the_query->is_singular ?? is_singular();
					break;
				case 'general:archive':
					$is_match = $wp_the_query->is_archive ?? is_archive();
					break;
			}
		}

		return 'is_not' === $operator ? ! $is_match : $is_match;
	}

	/**
	 * Check if a single location value matches the current location.
	 *
	 * @param string $rule            The condition rule.
	 * @param mixed  $check_value     The value to check.
	 * @param array  $current_location Current location data.
	 * @return bool
	 */
	private function check_single_location_match( $rule, $check_value, $current_location ) {
		// Check if current location rule matches.
		if ( in_array( $rule, $current_location['location'], true ) ) {
			if ( empty( $check_value ) ) {
				return true;
			} else {
				// Check specific object (post ID, term ID, etc.).
				return ( $current_location['object'] == $check_value ); // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			}
		}

		// Special cases for general rules - use true original query.
		global $wp_the_query;
		switch ( $rule ) {
			case 'general:site':
				return true;
			case 'general:singular':
				return $wp_the_query->is_singular ?? is_singular();
			case 'general:archive':
				return $wp_the_query->is_archive ?? is_archive();
		}

		return false;
	}

	/**
	 * Evaluate post taxonomy terms condition.
	 *
	 * @param string $rule     The condition rule.
	 * @param string $operator The condition operator.
	 * @param mixed  $value    The value to check against.
	 * @param array  $context  Additional context data.
	 * @return bool
	 */
	private function evaluate_post_terms( $rule, $operator, $value, $context = [] ) {
		// Use context if provided, otherwise get current post ID.
		$post_id = ! empty( $context['post_id'] ) ? $context['post_id'] : get_the_ID();

		if ( ! $post_id ) {
			return false;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}

		// Extract taxonomy from rule.
		$taxonomy = str_replace( 'post_terms:', '', $rule );
		if ( empty( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {
			return false;
		}

		// Get post terms.
		$post_terms = get_the_terms( $post->ID, $taxonomy );
		if ( is_wp_error( $post_terms ) ) {
			return false;
		}

		// Convert to array of term IDs.
		$post_term_ids = [];
		if ( $post_terms && is_array( $post_terms ) ) {
			$post_term_ids = wp_list_pluck( $post_terms, 'term_id' );
			$post_term_ids = array_map( 'strval', $post_term_ids ); // Convert to strings for comparison.
		}

		// Handle multi-value operators.
		if ( $this->is_multi_value_operator( $operator ) ) {
			return $this->evaluate_multi_value_array( $operator, $post_term_ids, $value );
		}

		// For single value operators.
		if ( empty( $value ) ) {
			// No specific term - just check if post has any terms.
			return 'is_not' === $operator ? empty( $post_term_ids ) : ! empty( $post_term_ids );
		}

		$has_term = in_array( strval( $value ), $post_term_ids, true );
		return 'is_not' === $operator ? ! $has_term : $has_term;
	}

	/**
	 * Evaluate hierarchical relationships.
	 *
	 * @param string $rule     The condition rule.
	 * @param string $operator The condition operator.
	 * @param mixed  $value    The value to check against.
	 * @param array  $context  Additional context data.
	 * @return bool
	 */
	private function evaluate_hierarchy( $rule, $operator, $value, $context = [] ) {
		// Use context if provided, otherwise get current post ID.
		$post_id = ! empty( $context['post_id'] ) ? $context['post_id'] : get_the_ID();

		if ( ! $post_id ) {
			return false;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}

		// Check if post type is hierarchical.
		$post_type_object = get_post_type_object( $post->post_type );
		if ( ! $post_type_object || ! $post_type_object->hierarchical ) {
			return false;
		}

		// Handle empty value - check for any parent/child relationship.
		if ( empty( $value ) ) {
			return $this->check_any_hierarchy( $rule, $operator, $post );
		}

		// Handle multi-value for hierarchy.
		if ( $this->is_multi_value_operator( $operator ) ) {
			return $this->evaluate_multi_value_generic(
				$operator,
				$value,
				function( $check_value ) use ( $rule, $post ) {
					return $this->check_single_hierarchy( $rule, $check_value, $post );
				}
			);
		}

		$is_match = $this->check_single_hierarchy( $rule, $value, $post );
		return 'is_not' === $operator ? ! $is_match : $is_match;
	}

	/**
	 * Check single hierarchy relationship.
	 *
	 * @param string  $rule  The hierarchy rule.
	 * @param mixed   $value The value to check.
	 * @param WP_Post $post  The post object to check.
	 * @return bool
	 */
	private function check_single_hierarchy( $rule, $value, $post ) {

		if ( 'child_of' === $rule ) {
			// Check if current post is a child of the specified post.
			$ancestors = get_post_ancestors( $post->ID );
			return in_array( intval( $value ), $ancestors, true );
		} elseif ( 'parent_of' === $rule ) {
			// Check if current post is a parent of the specified post.
			$target_post = get_post( intval( $value ) );
			if ( $target_post ) {
				$target_ancestors = get_post_ancestors( $target_post->ID );
				return in_array( $post->ID, $target_ancestors, true );
			}
		}

		return false;
	}

	/**
	 * Check if post has any hierarchical relationship.
	 *
	 * @param string  $rule     The hierarchy rule.
	 * @param string  $operator The condition operator.
	 * @param WP_Post $post     The post object to check.
	 * @return bool
	 */
	private function check_any_hierarchy( $rule, $operator, $post ) {

		if ( 'child_of' === $rule ) {
			// Check if current post has any parent.
			$has_parent = 0 < $post->post_parent;
			return 'is_not' === $operator ? ! $has_parent : $has_parent;
		} elseif ( 'parent_of' === $rule ) {
			// Check if current post has any children.
			$children = get_children(
				array(
					'post_parent' => $post->ID,
					'post_type'   => $post->post_type,
					'numberposts' => 1,
					'post_status' => array( 'publish', 'private', 'draft' ),
				)
			);
			$has_children = ! empty( $children );
			return 'is_not' === $operator ? ! $has_children : $has_children;
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
			'general:site'       => __( 'Entire Site', 'generateblocks-pro' ),
			'general:front_page' => __( 'Front Page', 'generateblocks-pro' ),
			'general:blog'       => __( 'Blog', 'generateblocks-pro' ),
			'general:singular'   => __( 'All Singular', 'generateblocks-pro' ),
			'general:archive'    => __( 'All Archives', 'generateblocks-pro' ),
			'general:author'     => __( 'Author Archives', 'generateblocks-pro' ),
			'general:date'       => __( 'Date Archives', 'generateblocks-pro' ),
			'general:search'     => __( 'Search Results', 'generateblocks-pro' ),
			'general:no_results' => __( 'No Results', 'generateblocks-pro' ),
			'general:404'        => __( '404 Template', 'generateblocks-pro' ),
		];

		// Add hierarchical relationships.
		$rules['child_of'] = __( 'Child of', 'generateblocks-pro' );
		$rules['parent_of'] = __( 'Parent of', 'generateblocks-pro' );

		// Add post types.
		$post_types = get_post_types( [ 'public' => true ], 'objects' );
		foreach ( $post_types as $post_type_slug => $post_type ) {
			$rules[ 'post:' . $post_type_slug ] = $post_type->labels->singular_name;
			if ( $post_type->has_archive ) {
				$rules[ 'archive:' . $post_type_slug ] = sprintf(
				/* translators: %s: post type singular name */
					__( '%s Archive', 'generateblocks-pro' ),
					$post_type->labels->singular_name
				);
			}
		}

		// Add taxonomies for archive pages.
		$taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );
		foreach ( $taxonomies as $taxonomy_slug => $taxonomy ) {
			$rules[ 'taxonomy:' . $taxonomy_slug ] = sprintf(
			/* translators: %s: taxonomy singular name */
				__( '%s Archive', 'generateblocks-pro' ),
				$taxonomy->labels->singular_name
			);
		}

		// Add post taxonomy terms - for checking if current post has specific terms.
		foreach ( $taxonomies as $taxonomy_slug => $taxonomy ) {
			// Use name if available, fallback to singular_name for tests.
			$taxonomy_name = isset( $taxonomy->labels->name ) ? $taxonomy->labels->name : $taxonomy->labels->singular_name;

			$rules[ 'post_terms:' . $taxonomy_slug ] = sprintf(
			/* translators: %s: taxonomy name */
				__( 'Post %s', 'generateblocks-pro' ),
				$taxonomy_name
			);
		}

		return apply_filters( 'generateblocks_location_rules', $rules );
	}

	/**
	 * Get metadata for a specific rule.
	 *
	 * @param string $rule The rule key.
	 * @return array
	 */
	public function get_rule_metadata( $rule ) {
		$general_location_rules = array(
			'general:site',
			'general:front_page',
			'general:blog',
			'general:singular',
			'general:archive',
			'general:author',
			'general:date',
			'general:search',
			'general:no_results',
			'general:404',
		);

		if ( in_array( $rule, $general_location_rules, true ) ) {
			return array(
				'needs_value'     => false,
				'value_type'      => 'none',
				'supports_multi'  => false,
			);
		}

		// Parent/child relationships need object selection but allow empty values.
		if ( in_array( $rule, array( 'child_of', 'parent_of' ), true ) ) {
			return array(
				'needs_value'     => false,
				'value_type'      => 'hierarchical_object_selector',
				'supports_multi'  => true,
			);
		}

		// Archive rules typically don't need values.
		if ( 0 === strpos( $rule, 'archive:' ) ) {
			return array(
				'needs_value'     => false,
				'value_type'      => 'none',
				'supports_multi'  => false,
			);
		}

		// Post taxonomy terms need term selection.
		if ( 0 === strpos( $rule, 'post_terms:' ) ) {
			return array(
				'needs_value'     => false,
				'value_type'      => 'object_selector',
				'supports_multi'  => true,
			);
		}

		// Post and taxonomy rules can optionally have specific object selection.
		if ( 0 === strpos( $rule, 'post:' ) || 0 === strpos( $rule, 'taxonomy:' ) ) {
			return array(
				'needs_value'     => false,
				'value_type'      => 'object_selector',
				'supports_multi'  => true,
			);
		}

		return $this->get_default_rule_metadata();
	}

	/**
	 * Get current location data.
	 *
	 * @return array
	 */
	private function get_current_location() {
		global $wp_the_query;

		$location = [];
		$object   = null;

		// Always use the true original query to determine location, not the current loop item.
		// This ensures Location conditions check "where you are" not "what you're looking at".
		// Using $wp_the_query is bulletproof against query_posts() and other query manipulations.
		// Falls back to global functions if $wp_the_query is not available.
		if ( $wp_the_query->is_front_page ?? is_front_page() ) {
			$location[] = 'general:front_page';

			// When "Your homepage displays" is set to "Your latest posts",
			// both is_front_page() and is_home() are true.
			if ( $wp_the_query->is_home ?? is_home() ) {
				$location[] = 'general:blog';
			}

			if ( $wp_the_query->is_page ?? is_page() ) {
				$location[] = 'post:page';
				$location[] = 'general:singular';

				// Get the front page ID for specific page targeting.
				$front_page_id = get_option( 'page_on_front' );
				if ( $front_page_id ) {
					$object = $front_page_id;
				}
			}
		} elseif ( $wp_the_query->is_home ?? is_home() ) {
			$location[] = 'general:blog';
		} elseif ( $wp_the_query->is_singular ?? is_singular() ) {
			$location[] = 'general:singular';
			$queried_object = isset( $wp_the_query ) ? $wp_the_query->get_queried_object() : get_queried_object();
			if ( $queried_object && isset( $queried_object->post_type ) ) {
				$location[] = 'post:' . $queried_object->post_type;
				$object     = $queried_object->ID;
			}
		} elseif ( $wp_the_query->is_archive ?? is_archive() ) {
			$location[] = 'general:archive';
			if ( ( $wp_the_query->is_category ?? is_category() ) || ( $wp_the_query->is_tag ?? is_tag() ) || ( $wp_the_query->is_tax ?? is_tax() ) ) {
				$queried_object = isset( $wp_the_query ) ? $wp_the_query->get_queried_object() : get_queried_object();
				if ( $queried_object && isset( $queried_object->taxonomy ) ) {
					$location[] = 'taxonomy:' . $queried_object->taxonomy;
					$object     = $queried_object->term_id;
				}
			} elseif ( $wp_the_query->is_post_type_archive ?? is_post_type_archive() ) {
				$post_type = isset( $wp_the_query ) ? $wp_the_query->get( 'post_type' ) : get_query_var( 'post_type' );
				if ( $post_type ) {
					$location[] = 'archive:' . $post_type;
				}
			} elseif ( $wp_the_query->is_author ?? is_author() ) {
				$location[] = 'general:author';
			} elseif ( $wp_the_query->is_date ?? is_date() ) {
				$location[] = 'general:date';
			}
		} elseif ( $wp_the_query->is_search ?? is_search() ) {
			$location[] = 'general:search';

			// Check if search has no results.
			if ( ( $wp_the_query->found_posts ?? 0 ) === 0 ) {
				$location[] = 'general:no_results';
			}
		} elseif ( $wp_the_query->is_404 ?? is_404() ) {
			$location[] = 'general:404';
		}

		// Always include site.
		$location[] = 'general:site';

		return [
			'location' => $location,
			'object'   => $object,
		];
	}

	/**
	 * Get operators available for a specific location rule.
	 *
	 * @param string $rule The rule key.
	 * @return array Array of operator keys.
	 */
	public function get_operators_for_rule( $rule ) {
		// General location rules - only basic operators (you're either there or not).
		$general_rules = array(
			'general:site',
			'general:front_page',
			'general:blog',
			'general:singular',
			'general:archive',
			'general:author',
			'general:date',
			'general:search',
			'general:no_results',
			'general:404',
		);

		if ( in_array( $rule, $general_rules, true ) ) {
			return array( 'is', 'is_not' );
		}

		// Archive rules - only basic operators.
		if ( 0 === strpos( $rule, 'archive:' ) ) {
			return array( 'is', 'is_not' );
		}

		// Hierarchical relationships - simplified operators (no includes_all/excludes_all).
		if ( in_array( $rule, array( 'child_of', 'parent_of' ), true ) ) {
			return array( 'is', 'is_not', 'includes_any', 'excludes_any' );
		}

		// Post taxonomy terms - full multi-value support.
		if ( 0 === strpos( $rule, 'post_terms:' ) ) {
			return array( 'is', 'is_not', 'includes_any', 'includes_all', 'excludes_any', 'excludes_all' );
		}

		// For all other rules, check if multi-select is actually supported.
		if ( $this->rule_supports_multi_select( $rule ) ) {
			// Multi-select interface available.
			if ( 0 === strpos( $rule, 'post:' ) ) {
				// Posts: can select multiple but can't be on all simultaneously.
				return array( 'is', 'is_not', 'includes_any', 'excludes_any' );
			}
			// Taxonomies: full support.
			return array( 'is', 'is_not', 'includes_any', 'includes_all', 'excludes_any', 'excludes_all' );
		} else {
			// Single input only: no any/all operators.
			return array( 'is', 'is_not' );
		}
	}
}
