<?php
/**
 * REST API functionality for overlays.
 *
 * @package GenerateBlocks Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access, please.
}

/**
 * Class GenerateBlocks_Pro_Overlay_Rest
 */
class GenerateBlocks_Pro_Overlay_Rest extends GenerateBlocks_Pro_Singleton {
	/**
	 * Initialize class.
	 */
	public function init() {
		// Don't initialize if overlays are disabled.
		if ( ! generateblocks_pro_overlays_enabled() ) {
			return;
		}

		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_filter( 'rest_prepare_gblocks_overlay', [ $this, 'add_category_to_response' ], 10, 3 );
		add_action( 'rest_insert_gblocks_overlay', [ $this, 'handle_category_on_save' ], 10, 3 );
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes() {
		register_rest_route(
			'generateblocks-pro/overlays/v1',
			'/get_modal_categories/',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_overlay_categories' ],
					'permission_callback' => [ $this, 'permissions_check' ],
				],
			]
		);

		register_rest_route(
			'generateblocks-pro/overlays/v1',
			'/manage_category/',
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'create_category' ],
					'permission_callback' => [ $this, 'permissions_check' ],
					'args'                => [
						'name' => [
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						],
					],
				],
			]
		);

		register_rest_route(
			'generateblocks-pro/overlays/v1',
			'/overlays/',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_overlays' ],
					'permission_callback' => [ $this, 'permissions_check' ],
				],
			]
		);

		register_rest_route(
			'generateblocks-pro/overlays/v1',
			'/manage_category/(?P<id>\d+)',
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'update_category' ],
					'permission_callback' => [ $this, 'permissions_check' ],
					'args'                => [
						'id'   => [
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						],
						'name' => [
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						],
					],
				],
				[
					'methods'             => 'DELETE',
					'callback'            => [ $this, 'delete_category' ],
					'permission_callback' => [ $this, 'permissions_check' ],
					'args'                => [
						'id' => [
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						],
					],
				],
			]
		);
	}

	/**
	 * Check if a given request has access to manage categories.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return bool
	 */
	public function permissions_check( $request ) {
		// For modifying operations, check manage permission.
		$request_method = $_SERVER['REQUEST_METHOD'] ?? '';

		if ( in_array( $request_method, [ 'POST', 'PUT', 'PATCH', 'DELETE' ], true ) ) {
			// Creating, updating, or deleting requires manage permission.
			return GenerateBlocks_Pro_Overlays::current_user_can_use_overlays( 'manage' );
		}

		// Reading/listing only requires use permission.
		return GenerateBlocks_Pro_Overlays::current_user_can_use_overlays( 'use' );
	}

	/**
	 * Get all overlays.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_overlays( $request ) {
		$args = [
			'post_type'      => 'gblocks_overlay',
			'posts_per_page' => 100,
			'post_status'    => 'any',
			'orderby'        => 'title',
			'order'          => 'ASC',
		];

		$posts = get_posts( $args );
		$overlays = [];

		foreach ( $posts as $post ) {
			$category_terms = wp_get_post_terms( $post->ID, 'gblocks_overlay_cat', [ 'fields' => 'ids' ] );
			$overlay_type = get_post_meta( $post->ID, '_gb_overlay_type', true );
			$display_condition = get_post_meta( $post->ID, '_gb_overlay_display_condition', true );
			$display_condition_invert = get_post_meta( $post->ID, '_gb_overlay_display_condition_invert', true );

			$overlays[] = [
				'id'                 => $post->ID,
				'title'              => [ 'rendered' => $post->post_title ],
				'status'             => $post->post_status,
				'gblocks_overlay_cat'  => ! empty( $category_terms ) ? $category_terms : [],
				'overlay_type'         => ! empty( $overlay_type ) ? $overlay_type : 'standard',
				'display_condition'  => ! empty( $display_condition ) ? $display_condition : '',
				'display_condition_invert' => ! empty( $display_condition_invert ) ? $display_condition_invert : false,
			];
		}

		return new WP_REST_Response(
			[
				'success'  => true,
				'response' => [
					'modals' => $overlays,
				],
			],
			200
		);
	}

	/**
	 * Get all overlay categories.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_overlay_categories( $request ) {
		$terms = get_terms(
			[
				'taxonomy'   => 'gblocks_overlay_cat',
				'hide_empty' => false,
			]
		);

		if ( is_wp_error( $terms ) ) {
			return $terms;
		}

		// Add count for each category.
		$categories = [];
		foreach ( $terms as $term ) {
			$categories[] = [
				'id'    => $term->term_id,
				'name'  => $term->name,
				'slug'  => $term->slug,
				'count' => $term->count,
			];
		}

		return new WP_REST_Response(
			[
				'success'    => true,
				'categories' => $categories,
			],
			200
		);
	}

	/**
	 * Create a new category.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_category( $request ) {
		$name = $request->get_param( 'name' );

		$term = wp_insert_term( $name, 'gblocks_overlay_cat' );

		if ( is_wp_error( $term ) ) {
			return new WP_Error(
				'create_failed',
				__( 'Failed to create category.', 'generateblocks-pro' ),
				[ 'status' => 400 ]
			);
		}

		$created_term = get_term( $term['term_id'], 'gblocks_overlay_cat' );

		return new WP_REST_Response(
			[
				'success'  => true,
				'category' => [
					'id'    => $created_term->term_id,
					'name'  => $created_term->name,
					'slug'  => $created_term->slug,
					'count' => 0,
				],
			],
			201
		);
	}

	/**
	 * Update a category.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_category( $request ) {
		$id   = $request->get_param( 'id' );
		$name = $request->get_param( 'name' );

		$term = wp_update_term(
			$id,
			'gblocks_overlay_cat',
			[
				'name' => $name,
			]
		);

		if ( is_wp_error( $term ) ) {
			return new WP_Error(
				'update_failed',
				__( 'Failed to update category.', 'generateblocks-pro' ),
				[ 'status' => 400 ]
			);
		}

		$updated_term = get_term( $id, 'gblocks_overlay_cat' );

		return new WP_REST_Response(
			[
				'success'  => true,
				'category' => [
					'id'    => $updated_term->term_id,
					'name'  => $updated_term->name,
					'slug'  => $updated_term->slug,
					'count' => $updated_term->count,
				],
			],
			200
		);
	}

	/**
	 * Delete a category.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_category( $request ) {
		$id = $request->get_param( 'id' );

		// Check if category is in use.
		$term = get_term( $id, 'gblocks_overlay_cat' );
		if ( is_wp_error( $term ) ) {
			return new WP_Error(
				'term_not_found',
				__( 'Category not found.', 'generateblocks-pro' ),
				[ 'status' => 404 ]
			);
		}

		$result = wp_delete_term( $id, 'gblocks_overlay_cat' );

		if ( is_wp_error( $result ) ) {
			return new WP_Error(
				'delete_failed',
				__( 'Failed to delete category.', 'generateblocks-pro' ),
				[ 'status' => 400 ]
			);
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Category deleted successfully.', 'generateblocks-pro' ),
			],
			200
		);
	}

	/**
	 * Add category to REST response.
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_Post          $post     Post object.
	 * @param WP_REST_Request  $request  Request object.
	 * @return WP_REST_Response
	 */
	public function add_category_to_response( $response, $post, $request ) {
		$category_terms = wp_get_post_terms( $post->ID, 'gblocks_overlay_cat', [ 'fields' => 'ids' ] );
		$response->data['gblocks_overlay_cat'] = ! empty( $category_terms ) ? $category_terms : [];

		// Add overlay type to response.
		$overlay_type = get_post_meta( $post->ID, '_gb_overlay_type', true );
		$response->data['overlay_type'] = ! empty( $overlay_type ) ? $overlay_type : 'standard';

		// Add display condition to response.
		$display_condition = get_post_meta( $post->ID, '_gb_overlay_display_condition', true );
		$response->data['display_condition'] = ! empty( $display_condition ) ? $display_condition : '';

		// Add display condition invert to response.
		$display_condition_invert = get_post_meta( $post->ID, '_gb_overlay_display_condition_invert', true );
		$response->data['display_condition_invert'] = ! empty( $display_condition_invert ) ? $display_condition_invert : false;

		return $response;
	}

	/**
	 * Handle category assignment when saving overlay.
	 *
	 * @param WP_Post         $post     Inserted or updated post object.
	 * @param WP_REST_Request $request  Request object.
	 * @param bool            $creating True when creating a post, false when updating.
	 */
	public function handle_category_on_save( $post, $request, $creating ) {
		if ( isset( $request['gblocks_overlay_cat'] ) ) {
			$categories = $request['gblocks_overlay_cat'];
			if ( ! is_array( $categories ) ) {
				$categories = [ $categories ];
			}

			// Convert to integers and filter out empty values.
			$categories = array_filter( array_map( 'intval', $categories ) );
			wp_set_post_terms( $post->ID, $categories, 'gblocks_overlay_cat' );
		}
	}
}

GenerateBlocks_Pro_Overlay_Rest::get_instance()->init();
