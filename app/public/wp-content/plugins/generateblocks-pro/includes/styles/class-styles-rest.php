<?php
/**
 * The Global Classes rest class file.
 *
 * @package GenerateBlocksPro\Global_Classes_Rest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once GENERATEBLOCKS_PRO_DIR . 'includes/utils/trait-usage-search.php';

/**
 * Main class for the Global Classes Rest functions.
 *
 * @since 1.9
 */
class GenerateBlocks_Pro_Styles_Rest extends GenerateBlocks_Pro_Singleton {

	use GenerateBlocks_Pro_Usage_Search;

	/**
	 * Initialize all hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$namespace = 'generateblocks-pro/v1';

		register_rest_route(
			$namespace,
			'/global-classes/check_class_name',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'check_class_name' ),
				'permission_callback' => array( $this, 'can_create' ),
			)
		);

		register_rest_route(
			$namespace,
			'/global-classes/get',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_global_styles' ),
				'permission_callback' => array( $this, 'can_create' ),
			)
		);

		register_rest_route(
			$namespace,
			'/global-classes/get_css',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_global_class_css' ),
				'permission_callback' => array( $this, 'can_create' ),
			)
		);

		register_rest_route(
			$namespace,
			'/global-classes/get_styles',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_global_class_styles' ),
				'permission_callback' => array( $this, 'can_create' ),
			)
		);

		register_rest_route(
			$namespace,
			'/global-styles/update_menu_order',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_menu_order' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);

		register_rest_route(
			$namespace,
			'/global-classes/(?P<id>\d+)/usage',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_global_style_usage' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => array(
					'id'       => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'limit'    => array(
						'required'          => false,
						'type'              => 'integer',
						'default'           => 50,
						'sanitize_callback' => 'absint',
					),
					'refresh'  => array(
						'required'          => false,
						'type'              => 'boolean',
						'default'           => false,
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
				),
			)
		);
	}

	/**
	 * Manage options permission callback.
	 *
	 * @return bool
	 */
	public function can_create(): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Permission callback for endpoints that require elevated management access.
	 *
	 * @return bool
	 */
	public function can_manage(): bool {
		return GenerateBlocks_Pro_Styles::can_manage_styles();
	}

	/**
	 * Check if a class name already exists.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_REST_Response
	 */
	public function check_class_name( WP_REST_Request $request ): WP_REST_RESPONSE {
		$class_name = $request->get_param( 'className' );

		if ( empty( $class_name ) ) {
			return $this->failed( __( 'Style name cannot be empty', 'generateblocks-pro' ) );
		}

		$existing_class = new WP_Query(
			[
				'post_type'      => 'gblocks_styles',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'meta_query'     => [
					[
						'key'     => 'gb_style_selector',
						'value'   => $class_name,
						'compare' => '=',
					],
				],
			]
		);

		if ( ! empty( $existing_class->found_posts ) ) {
			return $this->failed( __( 'Style name already exists', 'generateblocks-pro' ) );
		}

		return $this->success( [ 'success' ] );
	}

	/**
	 * Returns a list of active or inactive classes.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_global_styles( WP_REST_Request $request ): WP_REST_Response {
		$status = $request->get_param( 'status' ) ?? 'publish';
		$custom_args = [
			'post_status' => $status,
		];

		$styles = GenerateBlocks_Pro_Styles::get_styles( $custom_args );

		$class_data = [
			'styles' => $styles,
		];

		return $this->success( $class_data );
	}

	/**
	 * Returns the CSS for the complete set of global classes or a specific class if specified.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_global_class_css( WP_REST_Request $request ): WP_REST_Response {
		$class_name = $request->get_param( 'className' );

		if ( $class_name ) {
			$class = GenerateBlocks_Pro_Styles::get_class_by_name( $class_name );

			if ( ! isset( $class->ID ) ) {
				return $this->failed( 'No CSS found.' );
			}

			$css = get_post_meta( $class->ID, 'gb_style_css', true );
		} else {
			$css = GenerateBlocks_Pro_Styles::get_styles_css();
		}

		return $this->success( [ $css ] );
	}

	/**
	 * Returns the styles for a specific class name.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_global_class_styles( WP_REST_Request $request ): WP_REST_Response {
		$class_name = $request->get_param( 'globalClass' );
		$class = GenerateBlocks_Pro_Styles::get_class_by_name( $class_name );

		if ( ! isset( $class ) ) {
			return $this->failed( __( 'Style does not exist', 'generateblocks-pro' ) );
		}

		$post_id = $class->ID;
		$styles = get_post_meta( $post_id, 'gb_style_data', true );

		return $this->success(
			[
				'postId' => $post_id,
				'styles' => $styles ?? [],
			]
		);
	}

	/**
	 * Handles updating global style menu order
	 *
	 * @param WP_REST_Request $request WP Request object.
	 * @return WP_REST_RESPONSE;
	 */
	public function update_menu_order( WP_REST_Request $request ): WP_REST_Response {
		$order = $request->get_param( 'order' );
		if ( empty( $order ) || ! is_array( $order ) ) {
			return $this->failed( __( 'Order parameter is invalid.', 'generateblocks-pro' ) );
		}

		global $wpdb;
		$sql = '';
		$rows_affected = 0;

		foreach ( $order as $index => $post_id ) {
			$query = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->prefix}posts SET menu_order = %d WHERE ID = %d;",
					[
						$index,
						$post_id,
					]
				)
			);

			if ( false === $query ) {
				return $this->failed( __( 'Failed to update menu order.', 'generateblocks-pro' ) );
			} else {
				$rows_affected += $query;
			}
		}

		$wpdb->flush();

		// Flush the cache after the operation completes.
		GenerateBlocks_Pro_Enqueue_Styles::get_instance()->build_css();

		return $this->success(
			[
				'message'       => __( 'Menu order updated', 'generateblocks-pro' ),
				'rows_affected' => $rows_affected,
			]
		);
	}

	/**
	 * Get global style usage across the site.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function get_global_style_usage( WP_REST_Request $request ): WP_REST_Response {
		$style_id = $request->get_param( 'id' );
		$limit = $request->get_param( 'limit' );
		$refresh = $request->get_param( 'refresh' );

		if ( empty( $style_id ) ) {
			return $this->failed( __( 'Style ID is required.', 'generateblocks-pro' ) );
		}

		// Verify the style exists and get the class name.
		$style_post = get_post( $style_id );
		if ( ! $style_post || 'gblocks_styles' !== $style_post->post_type ) {
			return $this->failed( __( 'Style not found.', 'generateblocks-pro' ) );
		}

		$class_name = get_post_meta( $style_id, 'gb_style_selector', true );
		if ( empty( $class_name ) ) {
			return $this->failed( __( 'Style class name not found.', 'generateblocks-pro' ) );
		}

		// Check cache unless refresh is requested.
		$cache_key = sprintf( 'gb_style_usage_%d_limit%d', $style_id, $limit );
		if ( ! $refresh ) {
			$cached_data = $this->get_usage_cache( $cache_key );
			if ( false !== $cached_data ) {
				$cached_data['cached'] = true;
				$cached_data['cached_at'] = $cached_data['cached_at'] ?? time();
				return $this->success( $cached_data );
			}
		}

		$usage_data = $this->search_global_style_usage( $class_name, $limit );

		// Cache for 15 minutes (900 seconds).
		// Note: Cache invalidation is intentionally manual (via refresh button in UI).
		// Automatic invalidation on post save/delete was deemed too complex and risky
		// due to the need to clear caches across all styles when any post changes.
		// Users can click refresh to get updated results when needed.
		$usage_data['cached_at'] = time();
		$this->set_usage_cache( $cache_key, $usage_data, 900 );

		$usage_data['cached'] = false;

		return $this->success( $usage_data );
	}

	/**
	 * Search for global style usage across different data sources.
	 *
	 * @param string $class_name The class name to search for.
	 * @param int    $limit      Maximum total results to return (default: 50).
	 * @return array
	 */
	private function search_global_style_usage( string $class_name, int $limit = 50 ): array {
		$all_usage = array();
		$total_found = 0;
		$has_more = false;
		$limitations = array();
		$has_limitations = false;

		// Define search handlers - easily extensible for future use cases.
		$search_handlers = array(
			'style_usage' => array(
				'method' => 'search_global_style_attribute_usage',
				'label'  => __( 'Style Usage', 'generateblocks-pro' ),
			),
		);

		// Apply filters to allow other plugins/themes to add search handlers.
		$search_handlers = apply_filters( 'generateblocks_global_style_usage_handlers', $search_handlers, $class_name );

		foreach ( $search_handlers as $handler_key => $handler_config ) {
			// Stop processing if we've found enough results.
			if ( $total_found >= $limit ) {
				$has_more = true;
				break;
			}

			if ( ! method_exists( $this, $handler_config['method'] ) ) {
				continue;
			}

			// Calculate how many more results we need.
			$remaining = $limit - $total_found;
			$handler_results = $this->{$handler_config['method']}( $class_name, $remaining );

			if (
				! empty( $handler_results['items'] )
				|| ! empty( $handler_results['has_more'] )
				|| ! empty( $handler_results['limited'] )
			) {
				// Take only what we need.
				$items = array_slice( $handler_results['items'], 0, $remaining );

				$all_usage[ $handler_key ] = array(
					'label'    => $handler_config['label'],
					'items'    => $items,
					'has_more' => ! empty( $handler_results['has_more'] ) || ( count( $handler_results['items'] ) > $remaining ),
				);
				$total_found += count( $items );

				// If any handler has more results, set the overall has_more flag.
				if ( ! empty( $handler_results['has_more'] ) ) {
					$has_more = true;
				}

				if ( ! empty( $handler_results['limited'] ) ) {
					$has_limitations = true;
				}

				if ( ! empty( $handler_results['limits'] ) && is_array( $handler_results['limits'] ) ) {
					foreach ( $handler_results['limits'] as $limit_key => $limit_data ) {
						if ( ! isset( $limitations[ $limit_key ] ) ) {
							$limitations[ $limit_key ] = $limit_data;
							continue;
						}

						$current = $limitations[ $limit_key ];

						if ( isset( $limit_data['max_posts'] ) ) {
							$current['max_posts'] = isset( $current['max_posts'] )
								? max( $current['max_posts'], $limit_data['max_posts'] )
								: $limit_data['max_posts'];
						}

						if ( isset( $limit_data['scanned_posts'] ) ) {
							$current['scanned_posts'] = isset( $current['scanned_posts'] )
								? max( $current['scanned_posts'], $limit_data['scanned_posts'] )
								: $limit_data['scanned_posts'];
						}

						if ( isset( $limit_data['total_posts'] ) ) {
							$current['total_posts'] = isset( $current['total_posts'] )
								? max( $current['total_posts'], $limit_data['total_posts'] )
								: $limit_data['total_posts'];
						}

						$limitations[ $limit_key ] = $current;
					}
				}
			}
		}

		return array(
			'usage'       => $all_usage,
			'total'       => $total_found,
			'has_more'    => $has_more,
			'limited'     => $has_limitations,
			'limitations' => $limitations,
		);
	}

	/**
	 * Search for blocks using this style via the globalClasses attribute.
	 *
	 * @param string $class_name The class name to search for.
	 * @param int    $limit      Maximum results to return.
	 * @return array
	 */
	private function search_global_style_attribute_usage( string $class_name, int $limit ): array {
		global $wpdb;

		// Validate and sanitize inputs.
		$limit = absint( $limit );

		if ( empty( $class_name ) ) {
			return array(
				'items'    => array(),
				'has_more' => false,
			);
		}

		// Get post types that support the block editor.
		$post_types = get_post_types_by_support( 'editor' );
		$excluded_types = array( 'revision', 'attachment', 'nav_menu_item' );
		$post_types = array_diff( $post_types, $excluded_types );
		$post_types = apply_filters( 'generateblocks_usage_search_post_types', $post_types, 'styles' );

		if ( empty( $post_types ) ) {
			return array(
				'items'    => array(),
				'has_more' => false,
			);
		}

		$post_statuses = array( 'publish', 'private', 'draft' );

		// Strip leading dot if present - HTML class attributes don't have dots.
		$class_name = ltrim( $class_name, '.' );

		// Search in the rendered HTML (class="...") instead of the JSON.
		// This avoids unicode encoding issues and is much simpler and more reliable.
		// Pattern: class="...class-name..." (with word boundaries for accuracy).
		$search_pattern = '%class="%' . $wpdb->esc_like( $class_name ) . '%"%';

		// Query one more than requested to determine if there are more results.
		$query_limit = $limit + 1;

		// Cap the number of posts scanned (prevents slow queries on large sites).
		// Users can adjust this via filter if needed.
		$max_posts_to_scan = apply_filters( 'generateblocks_usage_search_max_posts', 5000 );

		$post_types_placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
		$status_placeholders     = implode( ', ', array_fill( 0, count( $post_statuses ), '%s' ) );

		// Determine dataset size so we can surface limitations to the UI.
		$total_posts_sql = sprintf(
			"SELECT COUNT(ID)
			FROM {$wpdb->posts}
			WHERE post_status IN (%s)
			AND post_type IN (%s)",
			$status_placeholders,
			$post_types_placeholders
		);
		$total_posts_query = $wpdb->prepare(
			$total_posts_sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			array_merge( $post_statuses, $post_types )
		);
		$total_posts   = absint( $wpdb->get_var( $total_posts_query ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$scanned_posts = min( $total_posts, $max_posts_to_scan );
		$limited_scan  = $total_posts > $max_posts_to_scan;

		// Two-phase query: First get recent posts, then search within them.
		// This prevents full table scans on large sites.
		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$sql = sprintf(
			"SELECT ID, post_title, post_type, post_status, post_content
			FROM (
				SELECT ID, post_title, post_type, post_status, post_content, post_modified
				FROM {$wpdb->posts}
				WHERE post_status IN ('publish', 'private', 'draft')
				AND post_type IN (%s)
				ORDER BY post_modified DESC
				LIMIT %%d
			) AS recent_posts
			WHERE post_content LIKE %%s
			LIMIT %%d",
			$post_types_placeholders
		);
		$results_query = $wpdb->prepare(
			$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			array_merge( $post_types, array( $max_posts_to_scan, $search_pattern, $query_limit ) )
		);
		// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		$results = $wpdb->get_results( $results_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Check if there are more results.
		$has_more = count( $results ) > $limit;

		// Remove the extra result if we got it.
		if ( $has_more ) {
			array_pop( $results );
		}

		$formatted_results = array();
		foreach ( $results as $result ) {
			// Extract class attribute values so we can inspect individual tokens.
			preg_match_all( '/class=(["\'])([^"\']+)\1/i', $result->post_content, $class_attr_matches );

			$match_count = 0;
			if ( ! empty( $class_attr_matches[2] ) ) {
				foreach ( $class_attr_matches[2] as $class_attr ) {
					$tokens = preg_split( '/\s+/', trim( $class_attr ) );

					if ( empty( $tokens ) ) {
						continue;
					}

					foreach ( $tokens as $token ) {
						if ( $class_name === $token ) {
							$match_count++;
						}
					}
				}
			}

			if ( 0 === $match_count ) {
				continue;
			}

			$post_type_object = get_post_type_object( $result->post_type );
			$post_type_label = $post_type_object ? $post_type_object->labels->singular_name : $result->post_type;

			// Check if post type is publicly viewable.
			$is_public = $post_type_object && ( $post_type_object->public || $post_type_object->publicly_queryable );

			// translators: %1$s: Post type label, %2$d: Post ID.
			$fallback_title = $result->post_title ? $result->post_title : sprintf( __( '%1$s #%2$d', 'generateblocks-pro' ), $post_type_label, $result->ID );

			$formatted_results[] = array(
				'id'          => absint( $result->ID ),
				'title'       => $fallback_title,
				'type'        => $result->post_type,
				'type_label'  => $post_type_label,
				'status'      => $result->post_status,
				'edit_url'    => get_edit_post_link( $result->ID, 'raw' ),
				'view_url'    => ( 'publish' === $result->post_status && $is_public ) ? get_permalink( $result->ID ) : null,
				'usage_type'  => 'global_style_usage',
				'block_count' => $match_count,
			);
		}

		return array(
			'items'    => $formatted_results,
			'has_more' => $has_more || $limited_scan,
			'limited'  => $limited_scan,
			'limits'   => $limited_scan
				? array(
					'recent_post_scan' => array(
						'max_posts'     => $max_posts_to_scan,
						'scanned_posts' => $scanned_posts,
						'total_posts'   => $total_posts,
					),
				)
				: array(),
		);
	}

	/**
	 * Returns a success response.
	 *
	 * @param array $data The data.
	 *
	 * @return WP_REST_Response
	 */
	private function success( array $data ): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'success'  => true,
				'response' => array(
					'data' => $data,
				),
			),
			200
		);
	}

	/**
	 * Returns a success response.
	 *
	 * @param string $message The error message.
	 *
	 * @return WP_REST_Response
	 */
	private function failed( string $message ): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'success'  => false,
				'response' => $message,
			),
			200
		);
	}

	/**
	 * Returns a error response.
	 *
	 * @param int    $code Error code.
	 * @param string $message Error message.
	 *
	 * @return WP_REST_Response
	 */
	private function error( int $code, string $message = '' ): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'error'      => true,
				'success'    => false,
				'error_code' => $code,
				'response'   => $message,
			),
			$code
		);
	}
}

GenerateBlocks_Pro_Styles_Rest::get_instance()->init();
