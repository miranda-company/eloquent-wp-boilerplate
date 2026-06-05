<?php
/**
 * Advanced Conditions Rest API functions
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once GENERATEBLOCKS_PRO_DIR . 'includes/utils/trait-usage-search.php';

/**
 * Class GenerateBlocks_Pro_Conditions_REST
 */
class GenerateBlocks_Pro_Conditions_REST extends WP_REST_Controller {

	use GenerateBlocks_Pro_Usage_Search;
	/**
	 * Instance.
	 *
	 * @access private
	 * @var object Instance
	 */
	private static $instance;

	/**
	 * Namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'generateblocks-pro/advanced-conditions/v';

	/**
	 * Version.
	 *
	 * @var string
	 */
	protected $version = '1';

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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register rest routes.
	 */
	public function register_routes() {
		$namespace = $this->namespace . $this->version;

		register_rest_route(
			$namespace,
			'/get_condition_types/',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_condition_types' ],
				'permission_callback' => [ $this, 'edit_posts_permission' ],
			]
		);

		register_rest_route(
			$namespace,
			'/get_condition_categories/',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_condition_categories' ],
				'permission_callback' => [ $this, 'edit_posts_permission' ],
			]
		);

		register_rest_route(
			$namespace,
			'/manage_category/',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_category' ],
				'permission_callback' => [ $this, 'manage_permission' ],
			]
		);

		register_rest_route(
			$namespace,
			'/manage_category/(?P<id>[\d]+)',
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_category' ],
				'permission_callback' => [ $this, 'manage_permission' ],
			]
		);

		register_rest_route(
			$namespace,
			'/manage_category/(?P<id>[\d]+)',
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_category' ],
				'permission_callback' => [ $this, 'manage_permission' ],
			]
		);

		register_rest_route(
			$namespace,
			'/get_condition_rules/',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_condition_rules' ],
				'permission_callback' => [ $this, 'edit_posts_permission' ],
				'args'                => [
					'type' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		register_rest_route(
			$namespace,
			'/get_condition_operators/',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_condition_operators' ],
				'permission_callback' => [ $this, 'edit_posts_permission' ],
				'args'                => [
					'type' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'rule' => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		register_rest_route(
			$namespace,
			'/search_posts/',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'search_posts' ],
				'permission_callback' => [ $this, 'edit_posts_permission' ],
				'args'                => [
					'post_type' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'search' => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'id' => [
						'required'          => false,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		register_rest_route(
			$namespace,
			'/search_terms/',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'search_terms' ],
				'permission_callback' => [ $this, 'edit_posts_permission' ],
				'args'                => [
					'taxonomy' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'search' => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'id' => [
						'required'          => false,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		register_rest_route(
			$namespace,
			'/validate_conditions/',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'validate_conditions' ],
				'permission_callback' => [ $this, 'edit_posts_permission' ],
				'args'                => [
					'conditions' => [
						'required' => true,
						'type'     => 'object',
					],
				],
			]
		);

		register_rest_route(
			$namespace,
			'/get_rule_metadata/',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_rule_metadata' ],
				'permission_callback' => [ $this, 'edit_posts_permission' ],
				'args'                => [
					'type' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'rule' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		register_rest_route(
			$namespace,
			'/batch_object_titles/',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'batch_object_titles' ],
				'permission_callback' => [ $this, 'edit_posts_permission' ],
				'args'                => [
					'requests' => [
						'required' => true,
						'type'     => 'array',
					],
				],
			]
		);

		register_rest_route(
			$namespace,
			'/conditions/',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_conditions' ],
					'permission_callback' => [ $this, 'edit_posts_permission' ],
					'args'                => [
						'page' => [
							'required'          => false,
							'type'              => 'integer',
							'default'           => 1,
							'sanitize_callback' => 'absint',
						],
						'per_page' => [
							'required'          => false,
							'type'              => 'integer',
							'default'           => 10,
							'sanitize_callback' => 'absint',
						],
					],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_condition' ],
					'permission_callback' => [ $this, 'edit_posts_permission' ],
					'args'                => [
						'title' => [
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						],
						'conditions' => [
							'required' => false,
							'type'     => 'object',
							'default'  => [
								'logic' => 'OR',
								'groups' => [],
							],
						],
						'status' => [
							'required'          => false,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => 'publish',
							'enum'              => [ 'publish', 'draft' ],
						],
						'category' => [
							'required' => false,
							'type'     => [ 'array', 'string' ],
						],
					],
				],
			]
		);

		register_rest_route(
			$namespace,
			'/conditions/(?P<id>\d+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_condition' ],
					'permission_callback' => [ $this, 'edit_posts_permission' ],
					'args'                => [
						'id' => [
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						],
					],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_condition' ],
					'permission_callback' => [ $this, 'edit_posts_permission' ],
					'args'                => [
						'id' => [
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						],
						'title' => [
							'required'          => false,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						],
						'conditions' => [
							'required' => false,
							'type'     => 'object',
						],
						'status' => [
							'required'          => false,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						],
					],
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_condition' ],
					'permission_callback' => [ $this, 'edit_posts_permission' ],
					'args'                => [
						'id'    => [
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						],
						'force' => [
							'required' => false,
							'type'     => 'boolean',
							'default'  => false,
						],
					],
				],
			]
		);

		register_rest_route(
			$namespace,
			'/search_hierarchical_posts/',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'search_hierarchical_posts' ],
				'permission_callback' => [ $this, 'edit_posts_permission' ],
				'args'                => [
					'search' => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'id' => [
						'required'          => false,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		register_rest_route(
			$namespace,
			'/get_day_options/',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_day_options' ],
				'permission_callback' => [ $this, 'edit_posts_permission' ],
			]
		);

		register_rest_route(
			$namespace,
			'/conditions/(?P<id>\d+)/usage',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_condition_usage' ],
				'permission_callback' => [ $this, 'manage_permission' ],
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'limit' => [
						'required'          => false,
						'type'              => 'integer',
						'default'           => 50,
						'sanitize_callback' => 'absint',
					],
					'refresh' => [
						'required'          => false,
						'type'              => 'boolean',
						'default'           => false,
						'sanitize_callback' => 'rest_sanitize_boolean',
					],
				],
			]
		);

		register_rest_route(
			$namespace,
			'/search_users/',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'search_users' ],
				'permission_callback' => [ $this, 'edit_posts_permission' ],
				'args'                => [
					'search' => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'id' => [
						'required'          => false,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
				],
			]
		);
	}

	/**
	 * Batch object titles endpoint
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function batch_object_titles( WP_REST_Request $request ) {
		$requests = $request->get_param( 'requests' );

		if ( empty( $requests ) || ! is_array( $requests ) ) {
			return $this->error( 'invalid_requests', __( 'Invalid requests parameter.', 'generateblocks-pro' ) );
		}

		$results = [];

		// Group requests by type for efficient batch processing.
		$grouped_requests = [
			'posts' => [],
			'terms' => [],
			'hierarchical_posts' => [],
			'days' => [],
		];

		foreach ( $requests as $request_item ) {
			if ( ! isset( $request_item['type'], $request_item['rule'], $request_item['value'] ) ) {
				continue;
			}

			$type = sanitize_text_field( $request_item['type'] );
			$rule = sanitize_text_field( $request_item['rule'] );
			$value = sanitize_text_field( $request_item['value'] );
			$cache_key = "{$type}_{$rule}_{$value}";

			// Handle day of week specially.
			if ( 'date_time' === $type && 'day_of_week' === $rule ) {
				$grouped_requests['days'][] = [
					'cache_key' => $cache_key,
					'value' => $value,
				];
				continue;
			}

			// Handle hierarchical posts.
			if ( in_array( $rule, [ 'child_of', 'parent_of' ], true ) ) {
				$grouped_requests['hierarchical_posts'][] = [
					'cache_key' => $cache_key,
					'value' => $value,
				];
				continue;
			}

			// Handle post types.
			if ( 0 === strpos( $rule, 'post:' ) ) {
				$post_type = str_replace( 'post:', '', $rule );
				$grouped_requests['posts'][] = [
					'cache_key' => $cache_key,
					'post_type' => $post_type,
					'value' => $value,
				];
				continue;
			}

			// Handle taxonomies.
			if ( 0 === strpos( $rule, 'taxonomy:' ) || 0 === strpos( $rule, 'post_terms:' ) ) {
				$taxonomy = str_replace( [ 'taxonomy:', 'post_terms:' ], '', $rule );
				$grouped_requests['terms'][] = [
					'cache_key' => $cache_key,
					'taxonomy' => $taxonomy,
					'value' => $value,
				];
				continue;
			}
		}

		// Process days.
		foreach ( $grouped_requests['days'] as $day_request ) {
			$results[ $day_request['cache_key'] ] = $this->get_day_name( $day_request['value'] );
		}

		// Process posts in batches.
		if ( ! empty( $grouped_requests['posts'] ) ) {
			$post_results = $this->batch_load_post_titles( $grouped_requests['posts'] );
			$results = array_merge( $results, $post_results );
		}

		// Process terms in batches.
		if ( ! empty( $grouped_requests['terms'] ) ) {
			$term_results = $this->batch_load_term_names( $grouped_requests['terms'] );
			$results = array_merge( $results, $term_results );
		}

		// Process hierarchical posts in batches.
		if ( ! empty( $grouped_requests['hierarchical_posts'] ) ) {
			$hierarchical_results = $this->batch_load_hierarchical_post_titles( $grouped_requests['hierarchical_posts'] );
			$results = array_merge( $results, $hierarchical_results );
		}

		return $this->success( $results );
	}

	/**
	 * Batch load post titles
	 *
	 * @param array $requests Post requests.
	 * @return array
	 */
	private function batch_load_post_titles( $requests ) {
		$results = [];
		$posts_by_type = [];

		// Group by post type.
		foreach ( $requests as $request ) {
			$post_type = $request['post_type'];
			if ( ! isset( $posts_by_type[ $post_type ] ) ) {
				$posts_by_type[ $post_type ] = [];
			}
			$posts_by_type[ $post_type ][] = $request;
		}

		// Query each post type.
		foreach ( $posts_by_type as $post_type => $type_requests ) {
			$post_ids = array_column( $type_requests, 'value' );
			$post_ids = array_map( 'intval', $post_ids );
			$post_ids = array_unique( $post_ids );

			if ( empty( $post_ids ) ) {
				continue;
			}

			$posts = get_posts(
				[
					'post_type' => $post_type,
					'post__in' => $post_ids,
					'post_status' => [ 'publish', 'private', 'draft' ],
					'posts_per_page' => -1,
				]
			);

			$posts_by_id = [];
			foreach ( $posts as $post ) {
				$posts_by_id[ $post->ID ] = $post;
			}

			// Map results back to cache keys.
			foreach ( $type_requests as $request ) {
				$post_id = intval( $request['value'] );
				if ( isset( $posts_by_id[ $post_id ] ) ) {
					$post = $posts_by_id[ $post_id ];
					$title = $post->post_title ? $post->post_title : sprintf(
						/* translators: %1$s: post type, %2$d: post ID */
						__( '%1$s #%2$d', 'generateblocks-pro' ),
						get_post_type_object( $post_type )->labels->singular_name,
						$post->ID
					);
					$results[ $request['cache_key'] ] = $title;
				} else {
					$results[ $request['cache_key'] ] = "ID: {$post_id}";
				}
			}
		}

		return $results;
	}

	/**
	 * Batch load term names
	 *
	 * @param array $requests Term requests.
	 * @return array
	 */
	private function batch_load_term_names( $requests ) {
		$results = [];
		$terms_by_taxonomy = [];

		// Group by taxonomy.
		foreach ( $requests as $request ) {
			$taxonomy = $request['taxonomy'];
			if ( ! isset( $terms_by_taxonomy[ $taxonomy ] ) ) {
				$terms_by_taxonomy[ $taxonomy ] = [];
			}
			$terms_by_taxonomy[ $taxonomy ][] = $request;
		}

		// Query each taxonomy.
		foreach ( $terms_by_taxonomy as $taxonomy => $taxonomy_requests ) {
			$term_ids = array_column( $taxonomy_requests, 'value' );
			$term_ids = array_map( 'intval', $term_ids );
			$term_ids = array_unique( $term_ids );

			if ( empty( $term_ids ) ) {
				continue;
			}

			$terms = get_terms(
				[
					'taxonomy' => $taxonomy,
					'include' => $term_ids,
					'hide_empty' => false,
				]
			);

			if ( is_wp_error( $terms ) ) {
				continue;
			}

			$terms_by_id = [];
			foreach ( $terms as $term ) {
				$terms_by_id[ $term->term_id ] = $term;
			}

			// Map results back to cache keys.
			foreach ( $taxonomy_requests as $request ) {
				$term_id = intval( $request['value'] );
				if ( isset( $terms_by_id[ $term_id ] ) ) {
					$results[ $request['cache_key'] ] = $terms_by_id[ $term_id ]->name;
				} else {
					$results[ $request['cache_key'] ] = "ID: {$term_id}";
				}
			}
		}

		return $results;
	}

	/**
	 * Batch load hierarchical post titles
	 *
	 * @param array $requests Hierarchical post requests.
	 * @return array
	 */
	private function batch_load_hierarchical_post_titles( $requests ) {
		$results = [];
		$post_ids = array_column( $requests, 'value' );
		$post_ids = array_map( 'intval', $post_ids );
		$post_ids = array_unique( $post_ids );

		if ( empty( $post_ids ) ) {
			return $results;
		}

		// Get hierarchical post types.
		$hierarchical_types = get_post_types(
			[
				'public' => true,
				'hierarchical' => true,
			],
			'names'
		);

		if ( empty( $hierarchical_types ) ) {
			return $results;
		}

		$posts = get_posts(
			[
				'post_type' => array_values( $hierarchical_types ),
				'post__in' => $post_ids,
				'post_status' => [ 'publish', 'private' ],
				'posts_per_page' => -1,
			]
		);

		$posts_by_id = [];
		foreach ( $posts as $post ) {
			$ancestors = get_post_ancestors( $post->ID );
			$hierarchy_indicator = str_repeat( '— ', count( $ancestors ) );
			$title = $hierarchy_indicator . ( $post->post_title ? $post->post_title : sprintf(
				/* translators: %1$s: post type, %2$d: post ID */
				__( '%1$s #%2$d', 'generateblocks-pro' ),
				get_post_type_object( $post->post_type )->labels->singular_name,
				$post->ID
			) );
			$posts_by_id[ $post->ID ] = $title;
		}

		// Map results back to cache keys.
		foreach ( $requests as $request ) {
			$post_id = intval( $request['value'] );
			if ( isset( $posts_by_id[ $post_id ] ) ) {
				// Strip hierarchy indicators for display.
				$clean_title = preg_replace( '/^(—\s*)+/', '', $posts_by_id[ $post_id ] );
				$results[ $request['cache_key'] ] = $clean_title;
			} else {
				$results[ $request['cache_key'] ] = "ID: {$post_id}";
			}
		}

		return $results;
	}

	/**
	 * Get day name
	 *
	 * @param string $day_number Day number.
	 * @return string
	 */
	private function get_day_name( $day_number ) {
		$days = [
			'1' => __( 'Monday', 'generateblocks-pro' ),
			'2' => __( 'Tuesday', 'generateblocks-pro' ),
			'3' => __( 'Wednesday', 'generateblocks-pro' ),
			'4' => __( 'Thursday', 'generateblocks-pro' ),
			'5' => __( 'Friday', 'generateblocks-pro' ),
			'6' => __( 'Saturday', 'generateblocks-pro' ),
			'7' => __( 'Sunday', 'generateblocks-pro' ),
		];

		return $days[ $day_number ] ?? $day_number;
	}

	/**
	 * Enhanced but safe permission callback.
	 *
	 * @return bool
	 */
	public function edit_posts_permission() {
		// For modifying operations, check manage permission.
		$request_method = $_SERVER['REQUEST_METHOD'] ?? '';

		if ( in_array( $request_method, [ 'POST', 'PUT', 'PATCH', 'DELETE' ], true ) ) {
			// Creating, updating, or deleting requires manage permission.
			return GenerateBlocks_Pro_Conditions::current_user_can_use_conditions( 'manage' );
		}

		// Reading/listing only requires use permission.
		return GenerateBlocks_Pro_Conditions::current_user_can_use_conditions( 'use' );
	}

	/**
	 * Permission callback for category management operations.
	 * Categories should only be manageable by administrators.
	 *
	 * @return bool
	 */
	public function manage_permission() {
		return GenerateBlocks_Pro_Conditions::current_user_can_use_conditions( 'manage' );
	}

	/**
	 * Verify nonce for state-changing operations.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	private function verify_nonce( WP_REST_Request $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'rest_cookie_invalid_nonce',
				__( 'Cookie check failed.', 'generateblocks-pro' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Get all condition types.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function get_condition_types( WP_REST_Request $request ) {
		$types = GenerateBlocks_Pro_Conditions::get_condition_types();

		$formatted_types = [];
		foreach ( $types as $key => $type ) {
			$formatted_types[ $key ] = [
				'label'     => $type['label'],
				'operators' => $type['operators'],
			];
		}

		return $this->success( $formatted_types );
	}

	/**
	 * Get all condition categories.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function get_condition_categories( WP_REST_Request $request ) {
		$categories = get_terms(
			[
				'taxonomy'   => 'gblocks_condition_cat',
				'hide_empty' => false,
			]
		);

		if ( is_wp_error( $categories ) ) {
			return $this->error( 'taxonomy_error', __( 'Could not retrieve categories.', 'generateblocks-pro' ) );
		}

		$formatted_categories = [];
		foreach ( $categories as $category ) {
			$formatted_categories[ $category->slug ] = [
				'slug'  => $category->slug,
				'name'  => $category->name,
				'count' => $category->count,
			];
		}

		return $this->success( $formatted_categories );
	}

	/**
	 * Get rules for a specific condition type.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function get_condition_rules( WP_REST_Request $request ) {
		$type = $request->get_param( 'type' );

		if ( empty( $type ) ) {
			return $this->error( 'missing_type', __( 'Condition type is required.', 'generateblocks-pro' ) );
		}

		$rules = GenerateBlocks_Pro_Conditions::get_condition_rules( $type );

		return $this->success( $rules );
	}

	/**
	 * Get operators for a specific condition type.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function get_condition_operators( WP_REST_Request $request ) {
		$type = $request->get_param( 'type' );
		$rule = $request->get_param( 'rule' );

		if ( empty( $type ) ) {
			return $this->error( 'missing_type', __( 'Condition type is required.', 'generateblocks-pro' ) );
		}

		$operators = [];

		// Get the condition instance to check for rule-specific operators.
		$instance = GenerateBlocks_Pro_Conditions_Registry::get_instance( $type );

		if ( $instance ) {
			// Try to get rule-specific operators if rule is provided and method exists.
			if ( ! empty( $rule ) && method_exists( $instance, 'get_operators_for_rule' ) ) {
				$operators = $instance->get_operators_for_rule( $rule );
			}
		}

		// Fallback to all operators for the type if no rule-specific operators found.
		if ( empty( $operators ) ) {
			$types = GenerateBlocks_Pro_Conditions::get_condition_types();
			if ( isset( $types[ $type ] ) ) {
				$operators = $types[ $type ]['operators'];
			}
		}

		if ( empty( $operators ) ) {
			return $this->error( 'no_operators', __( 'No operators found for this condition type.', 'generateblocks-pro' ) );
		}

		$formatted_operators = [];
		foreach ( $operators as $operator ) {
			$formatted_operators[ $operator ] = $this->get_operator_label( $operator );
		}

		return $this->success( $formatted_operators );
	}

	/**
	 * Search posts for a specific post type.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function search_posts( WP_REST_Request $request ) {
		$post_type = $request->get_param( 'post_type' );
		$search    = $request->get_param( 'search' );
		$id        = $request->get_param( 'id' );

		if ( empty( $post_type ) ) {
			return $this->error( 'missing_post_type', __( 'Post type is required.', 'generateblocks-pro' ) );
		}

		// Handle ID search.
		if ( ! empty( $id ) ) {
			$post = get_post( $id );
			if ( $post && $post_type === $post->post_type ) {
				// Exclude the Posts Page from page results.
				if ( 'page' === $post_type ) {
					$page_for_posts = get_option( 'page_for_posts' );
					if ( $page_for_posts && intval( $page_for_posts ) === $post->ID ) {
						return $this->success( [] );
					}
				}

				$formatted_posts = [
					[
						'id'    => $post->ID,
						'title' => $post->post_title ? $post->post_title : sprintf(
							/* translators: %1$s: post type, %2$d: post ID */
							__( '%1$s #%2$d', 'generateblocks-pro' ),
							get_post_type_object( $post_type )->labels->singular_name,
							$post->ID
						),
					],
				];
				return $this->success( $formatted_posts );
			} else {
				return $this->success( [] );
			}
		}

		$args = [
			'post_type'      => $post_type,
			'post_status'    => [ 'publish', 'private', 'draft' ],
			'posts_per_page' => empty( $search ) ? 10 : 50, // phpcs:ignore -- WordPress.WP.PostsPerPage.posts_per_page
			'orderby'        => empty( $search ) ? 'date' : 'title',
			'order'          => empty( $search ) ? 'DESC' : 'ASC',
		];

		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		// Exclude the Posts Page from page results.
		if ( 'page' === $post_type ) {
			$page_for_posts = get_option( 'page_for_posts' );
			if ( $page_for_posts ) {
				$args['post__not_in'] = [ $page_for_posts ];
			}
		}

		$posts = get_posts( $args );

		$formatted_posts = [];
		foreach ( $posts as $post ) {
			$formatted_posts[] = [
				'id'    => $post->ID,
				// translators: %1$s is the post type singular name, %2$d is the post ID.
				'title' => $post->post_title ? $post->post_title : sprintf( __( '%1$s #%2$d', 'generateblocks-pro' ), get_post_type_object( $post_type )->labels->singular_name, $post->ID ),
			];
		}

		return $this->success( $formatted_posts );
	}

	/**
	 * Search terms for a specific taxonomy.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function search_terms( WP_REST_Request $request ) {
		$taxonomy = $request->get_param( 'taxonomy' );
		$search   = $request->get_param( 'search' );
		$id       = $request->get_param( 'id' );

		if ( empty( $taxonomy ) ) {
			return $this->error( 'missing_taxonomy', __( 'Taxonomy is required.', 'generateblocks-pro' ) );
		}

		// Handle ID search.
		if ( ! empty( $id ) ) {
			$term = get_term( $id, $taxonomy );
			if ( ! is_wp_error( $term ) && $term ) {
				$formatted_terms = [
					[
						'id'   => $term->term_id,
						'name' => $term->name,
					],
				];
				return $this->success( $formatted_terms );
			} else {
				return $this->success( [] );
			}
		}

		$args = [
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'number'     => empty( $search ) ? 10 : 50, // Recent items vs search results.
			'orderby'    => empty( $search ) ? 'count' : 'name',
			'order'      => empty( $search ) ? 'DESC' : 'ASC',
		];

		if ( ! empty( $search ) ) {
			$args['search'] = $search;
		}

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) ) {
			return $this->error( 'terms_error', $terms->get_error_message() );
		}

		$formatted_terms = [];
		foreach ( $terms as $term ) {
			$formatted_terms[] = [
				'id'   => $term->term_id,
				'name' => $term->name,
			];
		}

		return $this->success( $formatted_terms );
	}

	/**
	 * Validate conditions structure.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function validate_conditions( WP_REST_Request $request ) {
		$conditions = $request->get_param( 'conditions' );

		$validation_result = $this->validate_conditions_structure( $conditions );

		if ( true === $validation_result ) {
			return $this->success( [ 'valid' => true ] );
		}

		return $this->error( 'validation_failed', $validation_result );
	}

	/**
	 * Get metadata for a specific rule.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function get_rule_metadata( WP_REST_Request $request ) {
		$type = $request->get_param( 'type' );
		$rule = $request->get_param( 'rule' );

		if ( empty( $type ) || empty( $rule ) ) {
			return $this->error( 'missing_parameters', __( 'Both type and rule parameters are required.', 'generateblocks-pro' ) );
		}

		$metadata = GenerateBlocks_Pro_Conditions::get_rule_metadata( $type, $rule );

		return $this->success( $metadata );
	}

	/**
	 * Get all conditions.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function get_conditions( WP_REST_Request $request ) {
		$page     = $request->get_param( 'page' );
		$per_page = $request->get_param( 'per_page' );

		$args = [
			'post_type'      => 'gblocks_condition',
			'post_status'    => [ 'publish', 'draft' ],
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		];

		$query = new WP_Query( $args );
		$posts = $query->posts;

		$conditions = [];
		foreach ( $posts as $post ) {
			$conditions[] = $this->format_condition_response( $post );
		}

		$response = [
			'conditions' => $conditions,
			'total'      => $query->found_posts,
			'pages'      => $query->max_num_pages,
		];

		return $this->success( $response );
	}

	/**
	 * Get a single condition.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function get_condition( WP_REST_Request $request ) {
		$id = $request->get_param( 'id' );

		$post = get_post( $id );

		if ( ! $post || 'gblocks_condition' !== $post->post_type ) {
			return $this->error( 'condition_not_found', __( 'Condition not found.', 'generateblocks-pro' ) );
		}

		return $this->success( $this->format_condition_response( $post ) );
	}

	/**
	 * Create a new condition.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function create_condition( WP_REST_Request $request ) {
		$nonce_check = $this->verify_nonce( $request );
		if ( is_wp_error( $nonce_check ) ) {
			return $nonce_check;
		}

		$title      = $request->get_param( 'title' );
		$conditions = $request->get_param( 'conditions' );
		$category   = $request->get_param( 'category' );
		$status     = $request->get_param( 'status' );

		if ( empty( $title ) ) {
			return $this->error( 'missing_title', __( 'Title is required.', 'generateblocks-pro' ) );
		}

		// Validate status.
		if ( ! empty( $status ) && ! in_array( $status, [ 'publish', 'draft' ], true ) ) {
			$status = 'publish';
		}

		$post_data = [
			'post_title'   => $title,
			'post_type'    => 'gblocks_condition',
			'post_status'  => $status ? $status : 'publish',
			'post_content' => '',
		];

		$post_id = wp_insert_post( $post_data );

		if ( is_wp_error( $post_id ) ) {
			return $this->error( 'creation_failed', $post_id->get_error_message() );
		}

		// Set categories if provided.
		if ( ! empty( $category ) ) {
			// Handle both single category (string) and multiple categories (array) for future compatibility
			// Currently UI only supports single category selection, but backend stores as array
			// for potential future multi-category support without data migration.
			$categories = is_array( $category ) ? $category : [ $category ];
			$term_ids = [];

			foreach ( $categories as $cat_slug ) {
				if ( empty( $cat_slug ) ) {
					continue;
				}

				// Check if the term exists, if not create it.
				$term = get_term_by( 'slug', $cat_slug, 'gblocks_condition_cat' );
				if ( ! $term ) {
					// Create the term if it doesn't exist.
					$term_result = wp_insert_term( $cat_slug, 'gblocks_condition_cat', [ 'slug' => $cat_slug ] );
					if ( ! is_wp_error( $term_result ) ) {
						$term_ids[] = $term_result['term_id'];
					} else {
						// If creation fails, try to get by name.
						$term = get_term_by( 'name', $cat_slug, 'gblocks_condition_cat' );
						if ( $term ) {
							$term_ids[] = $term->term_id;
						}
					}
				} else {
					$term_ids[] = $term->term_id;
				}
			}

			if ( ! empty( $term_ids ) ) {
				wp_set_post_terms( $post_id, $term_ids, 'gblocks_condition_cat' );
			}
		}

		// Use the main class sanitization method.
		$conditions_instance = GenerateBlocks_Pro_Conditions::get_instance();
		$sanitized_conditions = $conditions_instance->sanitize_conditions( $conditions );
		update_post_meta( $post_id, '_gb_conditions', $sanitized_conditions );

		$post = get_post( $post_id );
		return $this->success( $this->format_condition_response( $post ) );
	}

	/**
	 * Update an existing condition.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function update_condition( WP_REST_Request $request ) {
		$nonce_check = $this->verify_nonce( $request );
		if ( is_wp_error( $nonce_check ) ) {
			return $nonce_check;
		}

		$id         = $request->get_param( 'id' );
		$title      = $request->get_param( 'title' );
		$conditions = $request->get_param( 'conditions' );
		$status     = $request->get_param( 'status' );
		$category   = $request->get_param( 'category' );

		$post = get_post( $id );

		if ( ! $post || 'gblocks_condition' !== $post->post_type ) {
			return $this->error( 'condition_not_found', __( 'Condition not found.', 'generateblocks-pro' ) );
		}

		$update_data = [ 'ID' => $id ];

		if ( ! empty( $title ) ) {
			$update_data['post_title'] = $title;
		}

		if ( ! empty( $status ) && in_array( $status, [ 'publish', 'draft' ], true ) ) {
			$update_data['post_status'] = $status;
		}

		if ( ! empty( $update_data ) && 1 < count( $update_data ) ) {
			$result = wp_update_post( $update_data );
			if ( is_wp_error( $result ) ) {
				return $this->error( 'update_failed', $result->get_error_message() );
			}
		}

		// Update categories if provided.
		if ( isset( $category ) ) {
			if ( ! empty( $category ) ) {
				// Handle both single category (string) and multiple categories (array) for future compatibility
				// Currently UI only supports single category selection, but backend stores as array
				// for potential future multi-category support without data migration.
				$categories = is_array( $category ) ? $category : [ $category ];
				$term_ids = [];

				foreach ( $categories as $cat_slug ) {
					if ( empty( $cat_slug ) ) {
						continue;
					}

					// Check if the term exists, if not create it.
					$term = get_term_by( 'slug', $cat_slug, 'gblocks_condition_cat' );
					if ( ! $term ) {
						// Create the term if it doesn't exist.
						$term_result = wp_insert_term( $cat_slug, 'gblocks_condition_cat', [ 'slug' => $cat_slug ] );
						if ( ! is_wp_error( $term_result ) ) {
							$term_ids[] = $term_result['term_id'];
						} else {
							// If creation fails, try to get by name.
							$term = get_term_by( 'name', $cat_slug, 'gblocks_condition_cat' );
							if ( $term ) {
								$term_ids[] = $term->term_id;
							}
						}
					} else {
						$term_ids[] = $term->term_id;
					}
				}

				wp_set_post_terms( $id, $term_ids, 'gblocks_condition_cat' );
			} else {
				// Clear all categories.
				wp_set_post_terms( $id, [], 'gblocks_condition_cat' );
			}
		}

		if ( ! empty( $conditions ) ) {
			// Use the main class sanitization method.
			$conditions_instance = GenerateBlocks_Pro_Conditions::get_instance();
			$sanitized_conditions = $conditions_instance->sanitize_conditions( $conditions );
			update_post_meta( $id, '_gb_conditions', $sanitized_conditions );
		}

		$updated_post = get_post( $id );
		return $this->success( $this->format_condition_response( $updated_post ) );
	}

	/**
	 * Delete a condition.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function delete_condition( WP_REST_Request $request ) {
		$nonce_check = $this->verify_nonce( $request );
		if ( is_wp_error( $nonce_check ) ) {
			return $nonce_check;
		}

		$id    = $request->get_param( 'id' );
		$force = (bool) $request->get_param( 'force' );

		$post = get_post( $id );

		if ( ! $post || 'gblocks_condition' !== $post->post_type ) {
			return $this->error( 'condition_not_found', __( 'Condition not found.', 'generateblocks-pro' ) );
		}

		// Block deletion of a condition that is still in use unless the caller
		// explicitly confirmed with force=true. Prevents silent content leaks
		// (blocks using a trashed condition render unconditionally on the
		// frontend, bypassing the rule).
		//
		// The /usage REST endpoint is intentionally capped at 5000 recent
		// posts for dashboard performance. At delete time we raise the cap
		// via a scoped filter so we are less likely to miss a reference in
		// older content. Sites can override further with the standard
		// 'generateblocks_usage_search_max_posts' filter.
		if ( ! $force ) {
			$raise_scan_cap = static function( $max ) {
				$current = is_numeric( $max ) ? (int) $max : 5000;
				return max( $current, 50000 );
			};
			add_filter( 'generateblocks_usage_search_max_posts', $raise_scan_cap, 99 );

			try {
				$usage = $this->search_condition_usage( $id, 50 );
			} finally {
				remove_filter( 'generateblocks_usage_search_max_posts', $raise_scan_cap, 99 );
			}

			$usage_found = ! empty( $usage['total'] );
			$usage_scan_incomplete = ! empty( $usage['limited'] ) || ! empty( $usage['has_more'] );

			if ( $usage_found || $usage_scan_incomplete ) {
				$this->clear_usage_caches( 'gb_condition_usage_' . $id . '_' );
				return new WP_Error(
					'condition_in_use',
					__( 'This condition is still in use, or usage could not be fully verified. Resend the request with force=true to confirm deletion.', 'generateblocks-pro' ),
					[
						'status' => 409,
						'usage'  => $usage,
					]
				);
			}
		}

		$result = wp_delete_post( $id, true );

		if ( ! $result ) {
			return $this->error( 'deletion_failed', __( 'Failed to delete condition.', 'generateblocks-pro' ) );
		}

		$this->clear_usage_caches( 'gb_condition_usage_' . $id . '_' );

		return $this->success( [ 'deleted' => true ] );
	}

	/**
	 * Format condition response.
	 *
	 * @param WP_Post $post The post object.
	 * @return array
	 */
	private function format_condition_response( $post ) {
		$conditions = get_post_meta( $post->ID, '_gb_conditions', true );
		if ( ! $conditions ) {
			$conditions = [
				'logic' => 'OR',
				'groups' => [],
			];
		}

		// Get category terms.
		$category_terms = wp_get_post_terms( $post->ID, 'gblocks_condition_cat', [ 'fields' => 'ids' ] );
		$category_terms = is_wp_error( $category_terms ) ? [] : $category_terms;

		return [
			'id'                     => $post->ID,
			'title'                  => [
				'rendered' => $post->post_title,
			],
			'status'                 => $post->post_status,
			'date'                   => $post->post_date,
			'modified'               => $post->post_modified,
			'gbConditions'           => $conditions,
			'gblocks_condition_cat'  => $category_terms,
		];
	}

	/**
	 * Validate the structure of conditions.
	 *
	 * @param array $conditions The conditions to validate.
	 * @return bool|string True if valid, error message if invalid.
	 */
	private function validate_conditions_structure( $conditions ) {
		if ( ! is_array( $conditions ) ) {
			return __( 'Conditions must be an array.', 'generateblocks-pro' );
		}

		if ( ! isset( $conditions['logic'] ) || ! in_array( $conditions['logic'], [ 'AND', 'OR' ], true ) ) {
			return __( 'Invalid or missing logic operator.', 'generateblocks-pro' );
		}

		if ( ! isset( $conditions['groups'] ) || ! is_array( $conditions['groups'] ) ) {
			return __( 'Groups must be an array.', 'generateblocks-pro' );
		}

		$available_types = array_keys( GenerateBlocks_Pro_Conditions::get_condition_types() );

		foreach ( $conditions['groups'] as $group_index => $group ) {
			if ( ! is_array( $group ) ) {
				// translators: %d is the group index.
				return sprintf( __( 'Group %d must be an array.', 'generateblocks-pro' ), $group_index );
			}

			if ( ! isset( $group['logic'] ) || ! in_array( $group['logic'], [ 'AND', 'OR' ], true ) ) {
				// translators: %d is the group index.
				return sprintf( __( 'Invalid or missing logic operator for group %d.', 'generateblocks-pro' ), $group_index );
			}

			if ( ! isset( $group['conditions'] ) || ! is_array( $group['conditions'] ) ) {
				// translators: %d is the group index.
				return sprintf( __( 'Conditions for group %d must be an array.', 'generateblocks-pro' ), $group_index );
			}

			foreach ( $group['conditions'] as $condition_index => $condition ) {
				if ( ! is_array( $condition ) ) {
					// translators: %1$d is the condition index, %2$d is the group index.
					return sprintf( __( 'Condition %1$d in group %2$d must be an array.', 'generateblocks-pro' ), $condition_index, $group_index );
				}

				$required_fields = [ 'type', 'rule', 'operator' ];
				foreach ( $required_fields as $field ) {
					if ( ! isset( $condition[ $field ] ) ) {
						// translators: %1$s is the missing field, %2$d is the condition index, %3$d is the group index.
						return sprintf( __( 'Missing required field "%1$s" in condition %2$d of group %3$d.', 'generateblocks-pro' ), $field, $condition_index, $group_index );
					}
				}

				if ( ! in_array( $condition['type'], $available_types, true ) ) {
					// translators: %1$s is the invalid condition type, %2$d is the condition index, %3$d is the group index.
					return sprintf( __( 'Invalid condition type "%1$s" in condition %2$d of group %3$d.', 'generateblocks-pro' ), $condition['type'], $condition_index, $group_index );
				}
			}
		}

		return true;
	}

	/**
	 * Get human-readable label for an operator.
	 *
	 * @param string $operator The operator.
	 * @return string
	 */
	private function get_operator_label( $operator ) {
		$labels = [
			'is'           => __( 'Is', 'generateblocks-pro' ),
			'is_not'       => __( 'Is Not', 'generateblocks-pro' ),
			'exists'       => __( 'Exists', 'generateblocks-pro' ),
			'not_exists'   => __( 'Does Not Exist', 'generateblocks-pro' ),
			'has_value'    => __( 'Has Value', 'generateblocks-pro' ),
			'no_value'     => __( 'Has No Value', 'generateblocks-pro' ),
			'equals'       => __( 'Equals', 'generateblocks-pro' ),
			'contains'     => __( 'Contains', 'generateblocks-pro' ),
			'not_contains' => __( 'Does Not Contain', 'generateblocks-pro' ),
			'starts_with'  => __( 'Starts With', 'generateblocks-pro' ),
			'ends_with'    => __( 'Ends With', 'generateblocks-pro' ),
			'before'       => __( 'Before', 'generateblocks-pro' ),
			'after'        => __( 'After', 'generateblocks-pro' ),
			'between'      => __( 'Between', 'generateblocks-pro' ),
			'on'           => __( 'On', 'generateblocks-pro' ),
			'greater_than' => __( 'Greater Than', 'generateblocks-pro' ),
			'less_than'    => __( 'Less Than', 'generateblocks-pro' ),
			'includes_any' => __( 'Includes Any', 'generateblocks-pro' ),
			'includes_all' => __( 'Includes All', 'generateblocks-pro' ),
			'excludes_any' => __( 'Excludes Any', 'generateblocks-pro' ),
			'excludes_all' => __( 'Excludes All', 'generateblocks-pro' ),
		];

		return $labels[ $operator ] ?? ucfirst( str_replace( '_', ' ', $operator ) );
	}

	/**
	 * Search hierarchical posts (pages and hierarchical custom post types).
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function search_hierarchical_posts( WP_REST_Request $request ) {
		$search = $request->get_param( 'search' );
		$id     = $request->get_param( 'id' );

		// Get all hierarchical post types.
		$hierarchical_types = [];
		$post_types = get_post_types(
			[
				'public' => true,
				'hierarchical' => true,
			],
			'names'
		);

		if ( empty( $post_types ) ) {
			return $this->success( [] );
		}

		// Handle ID search.
		if ( ! empty( $id ) ) {
			$post = get_post( $id );
			if ( $post && in_array( $post->post_type, $post_types, true ) ) {
				$ancestors = get_post_ancestors( $post->ID );
				$hierarchy_indicator = str_repeat( '— ', count( $ancestors ) );
				$title = $hierarchy_indicator . ( $post->post_title ? $post->post_title : sprintf(
					/* translators: %1$s: post type, %2$d: post ID */
					__( '%1$s #%2$d', 'generateblocks-pro' ),
					get_post_type_object( $post->post_type )->labels->singular_name,
					$post->ID
				) );

				$formatted_posts = [
					[
						'id'    => $post->ID,
						'title' => $title,
					],
				];
				return $this->success( $formatted_posts );
			} else {
				return $this->success( [] );
			}
		}

		$args = [
			'post_type'      => array_values( $post_types ),
			'post_status'    => [ 'publish', 'private' ],
			'posts_per_page' => empty( $search ) ? 10 : 50, // phpcs:ignore -- WordPress.WP.PostsPerPage.posts_per_page
			'orderby'        => empty( $search ) ? 'date' : 'menu_order title',
			'order'          => empty( $search ) ? 'DESC' : 'ASC',
		];

		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		$posts = get_posts( $args );

		$formatted_posts = [];
		foreach ( $posts as $post ) {
			$ancestors = get_post_ancestors( $post->ID );
			$hierarchy_indicator = str_repeat( '— ', count( $ancestors ) );

			$formatted_posts[] = [
				'id'    => $post->ID,
				'title' => $hierarchy_indicator . ( $post->post_title ? $post->post_title : sprintf(
					/* translators: %1$s: post type singular name, %2$d: post ID */
					__( '%1$s #%2$d', 'generateblocks-pro' ),
					get_post_type_object( $post->post_type )->labels->singular_name,
					$post->ID
				) ),
			];
		}

		// Sort to maintain hierarchy when showing recent items.
		if ( ! empty( $search ) ) {
			// For search results, sort to maintain hierarchy.
			usort(
				$formatted_posts,
				function( $a, $b ) {
					return strnatcasecmp( $a['title'], $b['title'] );
				}
			);
		}

		return $this->success( $formatted_posts );
	}

	/**
	 * Get day options for day_of_week rule.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function get_day_options( WP_REST_Request $request ) {
		$instance = GenerateBlocks_Pro_Conditions_Registry::get_instance( 'date_time' );

		if ( ! $instance || ! method_exists( $instance, 'get_day_options' ) ) {
			return $this->error( 'not_available', __( 'Day options not available.', 'generateblocks-pro' ) );
		}

		$options = $instance->get_day_options();

		return $this->success( $options );
	}

	/**
	 * Get condition usage across the site.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function get_condition_usage( WP_REST_Request $request ) {
		$condition_id = $request->get_param( 'id' );
		$limit = $request->get_param( 'limit' );
		$refresh = $request->get_param( 'refresh' );

		if ( empty( $condition_id ) ) {
			return $this->error( 'missing_condition_id', __( 'Condition ID is required.', 'generateblocks-pro' ) );
		}

		// Verify the condition exists.
		$condition_post = get_post( $condition_id );
		if ( ! $condition_post || 'gblocks_condition' !== $condition_post->post_type ) {
			return $this->error( 'condition_not_found', __( 'Condition not found.', 'generateblocks-pro' ) );
		}

		// Check cache unless refresh is requested.
		$cache_key = sprintf( 'gb_condition_usage_%d_limit%d', $condition_id, $limit );
		if ( ! $refresh ) {
			$cached_data = $this->get_usage_cache( $cache_key );
			if ( false !== $cached_data ) {
				$cached_data['cached'] = true;
				$cached_data['cached_at'] = $cached_data['cached_at'] ?? time();
				return $this->success( $cached_data );
			}
		}

		$usage_data = $this->search_condition_usage( $condition_id, $limit );

		// Cache for 15 minutes (900 seconds).
		// Note: Cache invalidation is intentionally manual (via refresh button in UI).
		// Automatic invalidation on post save/delete was deemed too complex and risky
		// due to the need to clear caches across all conditions when any post changes.
		// Users can click refresh to get updated results when needed.
		$usage_data['cached_at'] = time();
		$this->set_usage_cache( $cache_key, $usage_data, 900 );

		$usage_data['cached'] = false;

		return $this->success( $usage_data );
	}

	/**
	 * Search for condition usage across different data sources.
	 *
	 * @param int $condition_id The condition ID to search for.
	 * @param int $limit        Maximum results per handler to return (default: 50).
	 * @return array
	 */
	private function search_condition_usage( $condition_id, $limit = 50 ) {
		$all_usage = [];
		$total_found = 0;
		$has_more = false;
		$limitations = [];
		$has_limitations = false;

		$limit = max( 1, absint( $limit ) );

		// Define search handlers - easily extensible for future use cases.
		$search_handlers = [
			'overlay_display_conditions' => [
				'method' => 'search_overlay_display_conditions',
				'label'  => __( 'Overlay Panel Display Conditions', 'generateblocks-pro' ),
			],
		];

		// Apply filters to allow other plugins/themes to add search handlers.
		$search_handlers = apply_filters( 'generateblocks_condition_usage_handlers', $search_handlers, $condition_id );

		foreach ( $search_handlers as $handler_key => $handler_config ) {
			if ( ! method_exists( $this, $handler_config['method'] ) ) {
				continue;
			}

			$handler_results = $this->{$handler_config['method']}( $condition_id, $limit );

			if ( empty( $handler_results['items'] ) && empty( $handler_results['has_more'] ) && empty( $handler_results['limited'] ) ) {
				continue;
			}

			$items = array_slice( $handler_results['items'], 0, $limit );

			$all_usage[ $handler_key ] = [
				'label'    => $handler_config['label'],
				'items'    => $items,
				'has_more' => ! empty( $handler_results['has_more'] ),
			];
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

					// Merge limitation metadata, keeping the highest values where appropriate.
					$existing = $limitations[ $limit_key ];

					if ( isset( $limit_data['max_posts'] ) ) {
						$existing['max_posts'] = isset( $existing['max_posts'] )
							? max( $existing['max_posts'], $limit_data['max_posts'] )
							: $limit_data['max_posts'];
					}

					if ( isset( $limit_data['scanned_posts'] ) ) {
						$existing['scanned_posts'] = isset( $existing['scanned_posts'] )
							? max( $existing['scanned_posts'], $limit_data['scanned_posts'] )
							: $limit_data['scanned_posts'];
					}

					if ( isset( $limit_data['total_posts'] ) ) {
						$existing['total_posts'] = isset( $existing['total_posts'] )
							? max( $existing['total_posts'], $limit_data['total_posts'] )
							: $limit_data['total_posts'];
					}

					$limitations[ $limit_key ] = $existing;
				}
			}
		}

		return [
			'usage'       => $all_usage,
			'total'       => $total_found,
			'has_more'    => $has_more,
			'limited'     => $has_limitations,
			'limitations' => $limitations,
		];
	}

	/**
	 * Build a fail-closed result for usage scans that could not be verified.
	 *
	 * @return array
	 */
	private function usage_search_failed_result() {
		return [
			'items'    => [],
			'has_more' => true,
			'limited'  => true,
			'limits'   => [
				'usage_scan_failed' => [],
			],
		];
	}

	/**
	 * Search for posts using this condition in overlay panel display conditions.
	 *
	 * @param int $condition_id The condition ID.
	 * @param int $limit        Maximum results to return.
	 * @return array
	 */
	private function search_overlay_display_conditions( $condition_id, $limit ) {
		global $wpdb;

		// Validate and sanitize inputs.
		$condition_id = absint( $condition_id );
		$limit = absint( $limit );

		if ( ! $condition_id ) {
			return [
				'items'    => [],
				'has_more' => false,
			];
		}

		// Query one more than requested to determine if there are more results.
		$query_limit = $limit + 1;

		$results_query = $wpdb->prepare(
			"SELECT DISTINCT p.ID, p.post_title, p.post_type, p.post_status
		FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
		WHERE pm.meta_key = %s
		AND pm.meta_value = %s
		AND p.post_status IN ('publish', 'private', 'draft')
		ORDER BY p.post_modified DESC
		LIMIT %d",
			'_gb_overlay_display_condition',
			$condition_id,
			$query_limit
		);

		$results = $wpdb->get_results( $results_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( ! empty( $wpdb->last_error ) || ! is_array( $results ) ) {
			return $this->usage_search_failed_result();
		}

		// Check if there are more results.
		$has_more = count( $results ) > $limit;

		// Remove the extra result if we got it.
		if ( $has_more ) {
			array_pop( $results );
		}

		$formatted_results = [];
		foreach ( $results as $result ) {
			$post_type_object = get_post_type_object( $result->post_type );
			$post_type_label = $post_type_object ? $post_type_object->labels->singular_name : $result->post_type;

			// Check if post type is publicly viewable.
			$is_public = $post_type_object && ( $post_type_object->public || $post_type_object->publicly_queryable );

			// translators: %1$s: Post type label, %2$d: Post ID.
			$fallback_title = $result->post_title ? $result->post_title : sprintf( __( '%1$s #%2$d', 'generateblocks-pro' ), $post_type_label, $result->ID );

			$formatted_results[] = [
				'id'          => absint( $result->ID ),
				'title'       => $fallback_title,
				'type'        => $result->post_type,
				'type_label'  => $post_type_label,
				'status'      => $result->post_status,
				'edit_url'    => get_edit_post_link( $result->ID, 'raw' ),
				'view_url'    => ( 'publish' === $result->post_status && $is_public ) ? get_permalink( $result->ID ) : null,
				'usage_type'  => 'overlay_display_condition',
			];
		}

		return [
			'items'    => $formatted_results,
			'has_more' => $has_more,
			'limited'  => false,
			'limits'   => [],
		];
	}

	/**
	 * Search for blocks using this condition.
	 *
	 * @param int $condition_id The condition ID.
	 * @param int $limit        Maximum results to return.
	 * @return array
	 */
	private function search_block_conditions_usage( $condition_id, $limit ) {
		global $wpdb;

		// Validate and sanitize inputs.
		$condition_id = absint( $condition_id );
		$limit = absint( $limit );

		if ( ! $condition_id ) {
			return [
				'items'    => [],
				'has_more' => false,
			];
		}

		// Get post types that support the block editor.
		$post_types = get_post_types_by_support( 'editor' );
		$excluded_types = [ 'revision', 'attachment', 'nav_menu_item' ];
		$post_types = array_diff( $post_types, $excluded_types );
		$post_types = apply_filters( 'generateblocks_usage_search_post_types', $post_types, 'conditions' );

		if ( empty( $post_types ) ) {
			return [
				'items'    => [],
				'has_more' => false,
			];
		}

		$post_statuses = [ 'publish', 'private', 'draft' ];

		// Search for the condition ID in post content as a block attribute.
		// We're looking for gbBlockCondition attribute with the condition ID.
		$search_string = '"gbBlockCondition":"' . absint( $condition_id ) . '"';
		$search_pattern = '%' . $wpdb->esc_like( $search_string ) . '%';

		// Query one more than requested to determine if there are more results.
		$query_limit = $limit + 1;

		// Cap the number of posts scanned (prevents slow queries on large sites).
		// Users can adjust this via filter if needed.
		$max_posts_to_scan = apply_filters( 'generateblocks_usage_search_max_posts', 5000 );

		$post_types_placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
		$status_placeholders = implode( ', ', array_fill( 0, count( $post_statuses ), '%s' ) );

		// Determine overall dataset size so we can inform users when the scan is limited.
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
		$total_posts = $wpdb->get_var( $total_posts_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( ! empty( $wpdb->last_error ) ) {
			return $this->usage_search_failed_result();
		}

		$total_posts = absint( $total_posts );
		$scanned_posts = min( $total_posts, $max_posts_to_scan );
		$limited_scan = $total_posts > $max_posts_to_scan;

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
			array_merge( $post_types, [ $max_posts_to_scan, $search_pattern, $query_limit ] )
		);
		// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		$results = $wpdb->get_results( $results_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( ! empty( $wpdb->last_error ) || ! is_array( $results ) ) {
			return $this->usage_search_failed_result();
		}

		// Check if there are more results.
		$has_more = count( $results ) > $limit;

		// Remove the extra result if we got it.
		if ( $has_more ) {
			array_pop( $results );
		}

		$formatted_results = [];
		foreach ( $results as $result ) {
			$post_type_object = get_post_type_object( $result->post_type );
			$post_type_label = $post_type_object ? $post_type_object->labels->singular_name : $result->post_type;

			// Check if post type is publicly viewable.
			$is_public = $post_type_object && ( $post_type_object->public || $post_type_object->publicly_queryable );

			// Count how many blocks in this post use the condition.
			$block_count = substr_count( $result->post_content, '"gbBlockCondition":"' . $condition_id . '"' );

			// translators: %1$s: Post type label, %2$d: Post ID.
			$fallback_title = $result->post_title ? $result->post_title : sprintf( __( '%1$s #%2$d', 'generateblocks-pro' ), $post_type_label, $result->ID );

			$formatted_results[] = [
				'id'          => absint( $result->ID ),
				'title'       => $fallback_title,
				'type'        => $result->post_type,
				'type_label'  => $post_type_label,
				'status'      => $result->post_status,
				'edit_url'    => get_edit_post_link( $result->ID, 'raw' ),
				'view_url'    => ( 'publish' === $result->post_status && $is_public ) ? get_permalink( $result->ID ) : null,
				'usage_type'  => 'block_conditions',
				'block_count' => $block_count,
			];
		}

		return [
			'items'    => $formatted_results,
			'has_more' => $has_more || $limited_scan,
			'limited'  => $limited_scan,
			'limits'   => $limited_scan
				? [
					'recent_post_scan' => [
						'max_posts'      => $max_posts_to_scan,
						'scanned_posts'  => $scanned_posts,
						'total_posts'    => $total_posts,
					],
				]
				: [],
		];
	}

	/**
	 * Search for menu items using this condition.
	 *
	 * @param int $condition_id The condition ID.
	 * @param int $limit        Maximum results to return.
	 * @return array
	 */
	private function search_menu_item_conditions_usage( $condition_id, $limit ) {
		global $wpdb;

		// Validate and sanitize inputs.
		$condition_id = absint( $condition_id );
		$limit = absint( $limit );

		if ( ! $condition_id ) {
			return [
				'items'    => [],
				'has_more' => false,
			];
		}

		// Query one more than requested to determine if there are more results.
		$query_limit = $limit + 1;

		$results_query = $wpdb->prepare(
			"SELECT DISTINCT p.ID, p.post_title, pm2.meta_value as menu_id
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_menu_item_menu_item_parent'
			WHERE pm.meta_key = %s
			AND pm.meta_value = %s
			AND p.post_type = 'nav_menu_item'
			ORDER BY p.menu_order ASC
			LIMIT %d",
			'_gb_menu_condition',
			$condition_id,
			$query_limit
		);

		$results = $wpdb->get_results( $results_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( ! empty( $wpdb->last_error ) || ! is_array( $results ) ) {
			return $this->usage_search_failed_result();
		}

		// Check if there are more results.
		$has_more = count( $results ) > $limit;

		// Remove the extra result if we got it.
		if ( $has_more ) {
			array_pop( $results );
		}

		$formatted_results = [];
		foreach ( $results as $result ) {
			// Get the menu this item belongs to.
			$menu_object = wp_get_object_terms( $result->ID, 'nav_menu' );
			$menu_name = ! empty( $menu_object ) && ! is_wp_error( $menu_object )
				? $menu_object[0]->name
				: __( 'Unknown Menu', 'generateblocks-pro' );

			// Format the title.
			$item_title = $result->post_title ? $result->post_title : __( 'Menu Item', 'generateblocks-pro' );

			// translators: %1$s: Menu item title, %2$s: Menu name.
			$full_title = sprintf( __( '%1$s (in %2$s)', 'generateblocks-pro' ), $item_title, $menu_name );

			$formatted_results[] = [
				'id'          => absint( $result->ID ),
				'title'       => $full_title,
				'type'        => 'nav_menu_item',
				'type_label'  => __( 'Menu Item', 'generateblocks-pro' ),
				'status'      => 'publish',
				'edit_url'    => admin_url( 'nav-menus.php' ),
				'view_url'    => null, // Menu items don't have direct view URLs.
				'usage_type'  => 'menu_item_conditions',
			];
		}

		return [
			'items'    => $formatted_results,
			'has_more' => $has_more,
			'limited'  => false,
			'limits'   => [],
		];
	}

	/**
	 * Search users for author conditions.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function search_users( WP_REST_Request $request ) {
		$search = $request->get_param( 'search' );
		$id     = $request->get_param( 'id' );

		// Handle ID search.
		if ( ! empty( $id ) ) {
			$user = get_user_by( 'ID', $id );
			if ( $user ) {
				$formatted_users = [
					[
						'id'   => $user->ID,
						'name' => $user->display_name ? $user->display_name : $user->user_login,
					],
				];
				return $this->success( $formatted_users );
			} else {
				return $this->success( [] );
			}
		}

		$args = [
			'fields'  => [ 'ID', 'display_name', 'user_login', 'user_email' ],
			'number'  => empty( $search ) ? 10 : 50,
			'orderby' => empty( $search ) ? 'registered' : 'display_name',
			'order'   => empty( $search ) ? 'DESC' : 'ASC',
		];

		if ( ! empty( $search ) ) {
			$args['search'] = '*' . esc_attr( $search ) . '*';
			$args['search_columns'] = [ 'user_login', 'user_email', 'display_name' ];
		}

		// Only get users who have published posts (actual authors).
		$args['has_published_posts'] = true;

		$users = get_users( $args );

		$formatted_users = [];
		foreach ( $users as $user ) {
			$display_name = $user->display_name ? $user->display_name : $user->user_login;

			$formatted_users[] = [
				'id'   => $user->ID,
				'name' => $display_name,
				'title' => $display_name,
			];
		}

		return $this->success( $formatted_users );
	}

	/**
	 * Success rest.
	 *
	 * @param mixed $response response data.
	 * @return mixed
	 */
	public function success( $response ) {
		return new WP_REST_Response(
			array(
				'success'  => true,
				'response' => $response,
			),
			200
		);
	}

	/**
	 * Failed rest.
	 *
	 * @param mixed $response response data.
	 * @return mixed
	 */
	public function failed( $response ) {
		return new WP_REST_Response(
			array(
				'success'  => false,
				'response' => $response,
			),
			200
		);
	}

	/**
	 * Create a new category.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function create_category( WP_REST_Request $request ) {
		$nonce_check = $this->verify_nonce( $request );
		if ( is_wp_error( $nonce_check ) ) {
			return $nonce_check;
		}

		$name = $request->get_param( 'name' );
		$slug = $request->get_param( 'slug' );

		if ( empty( $name ) ) {
			return $this->error( 'missing_name', __( 'Category name is required.', 'generateblocks-pro' ) );
		}

		$term_data = [ 'name' => $name ];
		if ( ! empty( $slug ) ) {
			$term_data['slug'] = $slug;
		}

		$term = wp_insert_term( $name, 'gblocks_condition_cat', $term_data );

		if ( is_wp_error( $term ) ) {
			return $this->error( 'creation_failed', $term->get_error_message() );
		}

		$created_term = get_term( $term['term_id'], 'gblocks_condition_cat' );
		return $this->success(
			[
				'id' => $created_term->term_id,
				'name' => $created_term->name,
				'slug' => $created_term->slug,
				'count' => $created_term->count,
			]
		);
	}

	/**
	 * Update a category.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function update_category( WP_REST_Request $request ) {
		$nonce_check = $this->verify_nonce( $request );
		if ( is_wp_error( $nonce_check ) ) {
			return $nonce_check;
		}

		$id = $request->get_param( 'id' );
		$name = $request->get_param( 'name' );

		if ( empty( $name ) ) {
			return $this->error( 'missing_name', __( 'Category name is required.', 'generateblocks-pro' ) );
		}

		$term = wp_update_term(
			$id,
			'gblocks_condition_cat',
			[
				'name' => $name,
			]
		);

		if ( is_wp_error( $term ) ) {
			return $this->error( 'update_failed', $term->get_error_message() );
		}

		$updated_term = get_term( $term['term_id'], 'gblocks_condition_cat' );
		return $this->success(
			[
				'id' => $updated_term->term_id,
				'name' => $updated_term->name,
				'slug' => $updated_term->slug,
				'count' => $updated_term->count,
			]
		);
	}

	/**
	 * Delete a category.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function delete_category( WP_REST_Request $request ) {
		$nonce_check = $this->verify_nonce( $request );
		if ( is_wp_error( $nonce_check ) ) {
			return $nonce_check;
		}

		$id = $request->get_param( 'id' );

		$term = get_term( $id, 'gblocks_condition_cat' );
		if ( is_wp_error( $term ) || ! $term ) {
			return $this->error( 'term_not_found', __( 'Category not found.', 'generateblocks-pro' ) );
		}

		$result = wp_delete_term( $id, 'gblocks_condition_cat' );

		if ( is_wp_error( $result ) ) {
			return $this->error( 'deletion_failed', $result->get_error_message() );
		}

		if ( ! $result ) {
			return $this->error( 'deletion_failed', __( 'Category could not be deleted.', 'generateblocks-pro' ) );
		}

		return $this->success( [ 'deleted' => true ] );
	}

	/**
	 * Error rest.
	 *
	 * @param mixed $code     error code.
	 * @param mixed $response response data.
	 * @return mixed
	 */
	public function error( $code, $response ) {
		return new WP_REST_Response(
			array(
				'error'      => true,
				'success'    => false,
				'error_code' => $code,
				'response'   => $response,
			),
			400
		);
	}
}

GenerateBlocks_Pro_Conditions_REST::get_instance();
