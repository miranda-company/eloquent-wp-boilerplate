<?php
/**
 * Conditions Post Type Registration
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GenerateBlocks_Pro_Conditions_Post_Type
 */
class GenerateBlocks_Pro_Conditions_Post_Type {
	/**
	 * Instance.
	 *
	 * @access private
	 * @var object Instance
	 */
	private static $instance;

	/**
	 * Initiator.
	 *
	 * @return object initialized object of class.
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'init', [ $this, 'register_taxonomy' ] );
		add_action( 'init', [ $this, 'register_post_meta' ] );
	}

	/**
	 * Build the register_post_type() args.
	 *
	 * Capabilities are pinned to the 'manage' context of the conditions
	 * capability filter so WP core REST writes (/wp/v2/gblocks-conditions)
	 * require the same capability as the custom /advanced-conditions/v1/* routes.
	 * Without this, any edit_posts user (Author+) could create published
	 * condition posts via the core REST endpoint and bypass the 2.4.0
	 * manage_options hardening.
	 *
	 * @return array
	 */
	public function get_conditions_cpt_args() {
		$labels = [
			'name'               => __( 'Conditions', 'generateblocks-pro' ),
			'singular_name'      => __( 'Condition', 'generateblocks-pro' ),
			'menu_name'          => __( 'Conditions', 'generateblocks-pro' ),
			'add_new'            => __( 'Add New', 'generateblocks-pro' ),
			'add_new_item'       => __( 'Add New Condition', 'generateblocks-pro' ),
			'edit_item'          => __( 'Edit Condition', 'generateblocks-pro' ),
			'new_item'           => __( 'New Condition', 'generateblocks-pro' ),
			'view_item'          => __( 'View Condition', 'generateblocks-pro' ),
			'search_items'       => __( 'Search Conditions', 'generateblocks-pro' ),
			'not_found'          => __( 'No conditions found.', 'generateblocks-pro' ),
			'not_found_in_trash' => __( 'No conditions found in Trash.', 'generateblocks-pro' ),
		];

		$manage_cap = GenerateBlocks_Pro_Conditions::get_conditions_capability( 'manage' );
		$use_cap    = GenerateBlocks_Pro_Conditions::get_conditions_capability( 'use' );

		return [
			'labels'              => $labels,
			'public'              => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			// Override only primitive capabilities — leaving meta caps
			// (edit_post/read_post/delete_post) at their defaults so WP's
			// map_meta_cap() resolves them via these primitives. Do not map
			// meta caps directly to a global cap like 'manage_options': WP's
			// _post_type_meta_capabilities() would then register that global
			// cap in $post_type_meta_caps and recursive-rewrite it to a meta
			// cap with no post ID, which always resolves to do_not_allow.
			'capabilities'        => [
				'edit_posts'             => $manage_cap,
				'edit_others_posts'      => $manage_cap,
				'delete_posts'           => $manage_cap,
				'delete_others_posts'    => $manage_cap,
				'delete_private_posts'   => $manage_cap,
				'delete_published_posts' => $manage_cap,
				'edit_private_posts'     => $manage_cap,
				'edit_published_posts'   => $manage_cap,
				'publish_posts'          => $manage_cap,
				'read_private_posts'     => $manage_cap,
				'create_posts'           => $manage_cap,
				'read'                   => $use_cap,
			],
			'show_in_rest'        => true,
			'rest_base'           => 'gblocks-conditions',
			'supports'            => [ 'title' ],
		];
	}

	/**
	 * Register the conditions post type.
	 */
	public function register_post_type() {
		register_post_type( 'gblocks_condition', $this->get_conditions_cpt_args() );
	}

	/**
	 * Build the register_taxonomy() args for the condition category taxonomy.
	 *
	 * The manage_terms/edit_terms/delete_terms caps are pinned to the manage context so
	 * WP core REST writes on /wp/v2/condition-categories (see WP_REST_Terms_Controller)
	 * require the same capability as the custom conditions routes. assign_terms
	 * stays at the use context so edit_posts users can still read the category
	 * list from the block editor UI.
	 *
	 * @return array
	 */
	public function get_conditions_taxonomy_args() {
		$labels = [
			'name'              => __( 'Condition Categories', 'generateblocks-pro' ),
			'singular_name'     => __( 'Condition Category', 'generateblocks-pro' ),
			'search_items'      => __( 'Search Categories', 'generateblocks-pro' ),
			'all_items'         => __( 'All Categories', 'generateblocks-pro' ),
			'parent_item'       => __( 'Parent Category', 'generateblocks-pro' ),
			'parent_item_colon' => __( 'Parent Category:', 'generateblocks-pro' ),
			'edit_item'         => __( 'Edit Category', 'generateblocks-pro' ),
			'update_item'       => __( 'Update Category', 'generateblocks-pro' ),
			'add_new_item'      => __( 'Add New Category', 'generateblocks-pro' ),
			'new_item_name'     => __( 'New Category Name', 'generateblocks-pro' ),
			'menu_name'         => __( 'Categories', 'generateblocks-pro' ),
		];

		$manage_cap = GenerateBlocks_Pro_Conditions::get_conditions_capability( 'manage' );
		$use_cap    = GenerateBlocks_Pro_Conditions::get_conditions_capability( 'use' );

		return [
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => false,
			'show_ui'           => false,
			'show_in_menu'      => false,
			'show_admin_column' => false,
			'show_in_nav_menus' => false,
			'show_tagcloud'     => false,
			'show_in_rest'      => true,
			'rest_base'         => 'condition-categories',
			'capabilities'      => [
				'manage_terms' => $manage_cap,
				'edit_terms'   => $manage_cap,
				'delete_terms' => $manage_cap,
				'assign_terms' => $use_cap,
			],
		];
	}

	/**
	 * Register the condition category taxonomy.
	 */
	public function register_taxonomy() {
		register_taxonomy( 'gblocks_condition_cat', [ 'gblocks_condition' ], $this->get_conditions_taxonomy_args() );
	}

	/**
	 * Build the register_post_meta() args for _gb_conditions.
	 *
	 * The sanitize callback must be callable by is_callable() — register_meta
	 * silently skips filter registration when that check fails. The previous
	 * [class-string, instance-method] form failed is_callable() and caused
	 * _gb_conditions meta to be stored without sanitization.
	 *
	 * auth_callback checks the manage capability so the REST meta endpoint
	 * matches the CPT-level capability requirements.
	 *
	 * @return array
	 */
	public function get_conditions_meta_args() {
		return [
			'single'            => true,
			'type'              => 'object',
			'auth_callback'     => static function() {
				return GenerateBlocks_Pro_Conditions::current_user_can_use_conditions( 'manage' );
			},
			'sanitize_callback' => [ GenerateBlocks_Pro_Conditions::get_instance(), 'sanitize_conditions' ],
			'show_in_rest'      => [
				'schema' => [
					'type'       => 'object',
					'properties' => [
						'logic'  => [
							'type' => 'string',
							'enum' => [ 'AND', 'OR' ],
						],
						'groups' => [
							'type'  => 'array',
							'items' => [
								'type'       => 'object',
								'properties' => [
									'logic'      => [
										'type' => 'string',
										'enum' => [ 'AND', 'OR' ],
									],
									'conditions' => [
										'type'  => 'array',
										'items' => [
											'type'       => 'object',
											'properties' => [
												'type'     => [ 'type' => 'string' ],
												'rule'     => [ 'type' => 'string' ],
												'operator' => [ 'type' => 'string' ],
												'value' => [ 'type' => 'string' ],
											],
										],
									],
								],
							],
						],
					],
				],
			],
		];
	}

	/**
	 * Register post meta for conditions.
	 */
	public function register_post_meta() {
		register_post_meta( 'gblocks_condition', '_gb_conditions', $this->get_conditions_meta_args() );

		register_rest_field(
			'gblocks_condition',
			'gbConditions',
			[
				'get_callback'    => function( $data ) {
					$conditions = get_post_meta( $data['id'], '_gb_conditions', true );
					return $conditions ? $conditions : [
						'logic' => 'OR',
						'groups' => [],
					];
				},
				'update_callback' => function( $value, $post ) {
					if ( ! GenerateBlocks_Pro_Conditions::current_user_can_use_conditions( 'manage' ) ) {
						return new \WP_Error(
							'rest_cannot_update',
							__( 'Sorry, you are not allowed to edit conditions.', 'generateblocks-pro' ),
							[ 'status' => rest_authorization_required_code() ]
						);
					}

					$sanitized = GenerateBlocks_Pro_Conditions::get_instance()->sanitize_conditions( $value );
					update_post_meta( $post->ID, '_gb_conditions', $sanitized );
				},
				'schema'          => [
					'type'       => 'object',
					'properties' => [
						'logic'  => [
							'type' => 'string',
							'enum' => [ 'AND', 'OR' ],
						],
						'groups' => [
							'type'  => 'array',
							'items' => [
								'type'       => 'object',
								'properties' => [
									'logic'      => [
										'type' => 'string',
										'enum' => [ 'AND', 'OR' ],
									],
									'conditions' => [
										'type'  => 'array',
										'items' => [
											'type'       => 'object',
											'properties' => [
												'type'     => [ 'type' => 'string' ],
												'rule'     => [ 'type' => 'string' ],
												'operator' => [ 'type' => 'string' ],
												'value'    => [ 'type' => 'string' ],
											],
										],
									],
								],
							],
						],
					],
				],
			]
		);
	}
}

GenerateBlocks_Pro_Conditions_Post_Type::get_instance();
