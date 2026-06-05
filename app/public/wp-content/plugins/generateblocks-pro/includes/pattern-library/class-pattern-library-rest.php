<?php
/**
 * The Pattern library rest class file.
 *
 * @package GenerateBlocksPro\Pattern_Library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'GenerateBlocks_Pattern_Library_Rest' ) ) :
	/**
	 * Main class for the Pattern Library Rest functions.
	 *
	 * @since 1.9
	 */
	class GenerateBlocks_Pro_Pattern_Library_Rest extends GenerateBlocks_Pro_Singleton {
		private const ENDPOINT_GLOBAL_STYLE_DATA = 'wp-json/generateblocks-pro/v1/pattern-library/provide-global-style-data';
		private const ENDPOINT_FORM_DATA         = 'wp-json/generateblocks-pro/v1/pattern-library/provide-form-data';
		private const ENDPOINT_LIBRARY_BY_KEY   = 'wp-json/generateblocks-pro/v1/pattern-library/get-library-by-public-key';

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
				'/pattern-library/categories',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list_categories' ),
					'permission_callback' => array( $this, 'can_list_patterns' ),
				)
			);

			register_rest_route(
				$namespace,
				'/pattern-library/patterns',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list_patterns' ),
					'permission_callback' => array( $this, 'can_list_patterns' ),
				)
			);

			register_rest_route(
				$namespace,
				'/pattern-library/get-global-style-data',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_global_style_data' ),
					'permission_callback' => function() {
						return apply_filters(
							'generateblocks_can_view_pattern_library',
							$this->edit_posts()
						);
					},
				)
			);

			register_rest_route(
				$namespace,
				'/pattern-library/provide-global-style-data',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'provide_global_style_data' ),
					'permission_callback' => array( $this, 'can_list_patterns' ),
				)
			);

			register_rest_route(
				$namespace,
				'/pattern-library/provide-form-data',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'provide_form_data' ),
					'permission_callback' => array( $this, 'can_list_patterns' ),
				)
			);

			register_rest_route(
				$namespace,
				'/pattern-library/import-styles',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'import_styles' ),
					'permission_callback' => array( $this, 'can_manage_classes' ),
				)
			);

			register_rest_route(
				$namespace,
				'/pattern-library/import-forms',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'import_forms' ),
					'permission_callback' => array( $this, 'can_import_forms' ),
				)
			);

			register_rest_route(
				$namespace,
				'/pattern-library/get-library-by-public-key',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_library_by_public_key' ),
					'permission_callback' => array( $this, 'has_valid_key' ),
				)
			);

			register_rest_route(
				$namespace,
				'/pattern-library/add-library',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'add_library' ),
					'permission_callback' => array( $this, 'can_manage_classes' ),
				)
			);
		}

		/**
		 * Check to see if we can import components.
		 */
		public function can_manage_classes(): bool {
			return current_user_can( 'manage_options' );
		}

		/**
		 * Check to see if the user can import form dependencies for patterns.
		 *
		 * @param WP_REST_Request $request The request.
		 */
		public function can_import_forms( WP_REST_Request $request ): bool {
			if ( ! $this->forms_are_available() ) {
				return false;
			}

			// Importing a form creates a published `gblocks_pro_form` post,
			// which is a public submission endpoint. Gate behind the same
			// `manage` cap the Forms CPT uses, so this route can't be used
			// to bypass the standard Forms admin permission model.
			$can_import = class_exists( 'GenerateBlocks_Pro_Form_Post_Type' )
				&& GenerateBlocks_Pro_Form_Post_Type::current_user_can( 'manage' );

			/** This filter is documented in includes/functions.php */
			return (bool) apply_filters(
				'generateblocks_pattern_library_can_import_forms',
				$can_import,
				$request
			);
		}

		/**
		 * Check to see if the user can edit this post.
		 */
		public function edit_posts(): bool {
			return current_user_can( 'edit_posts' );
		}

		/**
		 * Check to see if the user can view patterns.
		 *
		 * @param WP_REST_Request $request The request.
		 *
		 * @return bool
		 */
		public function can_list_patterns( WP_REST_Request $request ): bool {
			$is_local = $request->get_param( 'isLocal' );

			if ( $is_local ) {
				$current_host = $_SERVER['HTTP_HOST'] ?? '';
				$request_host = $request->get_header( 'host' );

				return $current_host === $request_host
					? $this->edit_posts()
					: false;
			}

			return $this->has_valid_key( $request );
		}

		/**
		 * Check to see if the user has a valid public key.
		 *
		 * @param WP_REST_Request $request The request.
		 *
		 * @return bool
		 */
		public function has_valid_key( WP_REST_Request $request ): bool {
			$public_key = $request->get_header( 'X-GB-Public-Key' );

			if ( is_null( $public_key ) || ! class_exists( 'GenerateBlocks_Pro_Pattern_Library' ) ) {
				return false;
			}

			return null !== GenerateBlocks_Pro_Pattern_Library::get_instance()
				->get_pattern_permissions_by_public_key( $public_key );
		}

		/**
		 * Sanitize a scalar REST request value.
		 *
		 * @param mixed $value The request value.
		 *
		 * @return string
		 */
		private function sanitize_request_string( $value ): string {
			if ( is_array( $value ) || is_object( $value ) ) {
				return '';
			}

			return sanitize_text_field( (string) $value );
		}

		/**
		 * Return the empty global style data shape expected by the editor.
		 *
		 * @return array
		 */
		private function empty_global_style_data(): array {
			return [
				'css' => '',
				'styles' => [],
			];
		}

		/**
		 * Validate and normalize remote global style data.
		 *
		 * @param mixed $data The decoded response data.
		 *
		 * @return array|false
		 */
		private function normalize_global_style_data( $data ) {
			if ( ! is_array( $data ) ) {
				return false;
			}

			if ( ! isset( $data['css'] ) || ! is_string( $data['css'] ) ) {
				return false;
			}

			if ( isset( $data['styles'] ) && ! is_array( $data['styles'] ) ) {
				return false;
			}

			return [
				'css' => $data['css'],
				'styles' => array_values( $data['styles'] ?? [] ),
			];
		}

		/**
		 * Get a saved remote library from the request id.
		 *
		 * @param WP_REST_Request $request The request.
		 *
		 * @return GenerateBlocks_Library_DTO|null
		 */
		private function get_remote_library_from_request( WP_REST_Request $request ) {
			$id = $this->sanitize_request_string( $request->get_param( 'id' ) );

			if ( '' === $id || ! class_exists( 'GenerateBlocks_Libraries' ) ) {
				return null;
			}

			$library = GenerateBlocks_Libraries::get_instance()->get_one( $id );

			if ( ! $library instanceof GenerateBlocks_Library_DTO ) {
				return null;
			}

			$is_local = (bool) $library->is_local;
			$domain = (string) $library->domain;
			$public_key = (string) $library->public_key;

			if ( $is_local || '' === $domain || '' === $public_key ) {
				return null;
			}

			return $library;
		}

		/**
		 * Validate a remote library endpoint URL.
		 *
		 * @param string $url The endpoint URL.
		 *
		 * @return string
		 */
		private function validate_remote_endpoint_url( string $url ): string {
			$url = esc_url_raw( $url );

			if ( '' === $url ) {
				return '';
			}

			$parts = wp_parse_url( $url );

			if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
				return '';
			}

			if ( ! empty( $parts['user'] ) || ! empty( $parts['pass'] ) ) {
				return '';
			}

			if ( ! in_array( strtolower( $parts['scheme'] ), [ 'http', 'https' ], true ) ) {
				return '';
			}

			return $url;
		}

		/**
		 * Fetch a remote pattern-library endpoint, following redirects manually.
		 *
		 * Provider URLs are saved by administrators. Lower-privileged requests
		 * receive a library id and resolve the URL from stored library data.
		 *
		 * Use wp_safe_remote_get() for WordPress' SSRF URL validation instead of
		 * maintaining our own host/IP deny list. Automatic redirects stay disabled
		 * so each Location hop is reduced back to the provider base and rebuilt
		 * against the known GenerateBlocks endpoint before the next safe request.
		 * This preserves normal provider moves while keeping the final path under
		 * our control. Intentional private/local providers can opt in through the
		 * core HTTP API filters documented for wp_http_validate_url().
		 *
		 * Redirects can move the provider's domain or WordPress subdirectory, but
		 * they do not get to choose the final request path: we always rebuild the
		 * known pattern-library endpoint on the redirected base before issuing the
		 * next hop.
		 *
		 * @param string $base_url      Provider base URL.
		 * @param string $endpoint_path Known endpoint path.
		 * @param array  $args          HTTP request arguments.
		 * @param array  $query_args    Query arguments to append.
		 *
		 * @return array|WP_Error
		 */
		private function fetch_remote_endpoint( string $base_url, string $endpoint_path, array $args = [], array $query_args = [] ) {
			$url = $this->build_remote_endpoint_url( $base_url, $endpoint_path, $query_args );

			if ( '' === $url ) {
				return new WP_Error(
					'generateblocks_pattern_library_invalid_url',
					__( 'Invalid pattern library URL.', 'generateblocks-pro' )
				);
			}

			$args['redirection'] = 0;
			$max_hops            = 5;

			for ( $hop = 0; $hop <= $max_hops; $hop++ ) {
				$response = wp_safe_remote_get( $url, $args );

				if ( is_wp_error( $response ) ) {
					return $response;
				}

				$code = (int) wp_remote_retrieve_response_code( $response );

				if ( $code < 300 || $code >= 400 ) {
					return $response;
				}

				$location = wp_remote_retrieve_header( $response, 'location' );

				if ( is_array( $location ) ) {
					$location = end( $location );
				}

				if ( ! is_string( $location ) || '' === trim( $location ) ) {
					return $response;
				}

				if ( $hop === $max_hops ) {
					return new WP_Error(
						'generateblocks_pattern_library_too_many_redirects',
						__( 'Too many pattern library redirects.', 'generateblocks-pro' )
					);
				}

				$redirect_base_url = $this->derive_remote_base_url_from_redirect( $location, $url, $endpoint_path );

				if ( '' === $redirect_base_url ) {
					return new WP_Error(
						'generateblocks_pattern_library_invalid_redirect',
						__( 'Invalid pattern library redirect.', 'generateblocks-pro' )
					);
				}

				$url = $this->build_remote_endpoint_url( $redirect_base_url, $endpoint_path, $query_args );

				if ( '' === $url ) {
					return new WP_Error(
						'generateblocks_pattern_library_invalid_redirect',
						__( 'Invalid pattern library redirect.', 'generateblocks-pro' )
					);
				}
			}

			return new WP_Error(
				'generateblocks_pattern_library_redirect_loop',
				__( 'Pattern library redirect loop detected.', 'generateblocks-pro' )
			);
		}

		/**
		 * Build a known pattern-library endpoint URL from a provider base URL.
		 *
		 * @param string $base_url      Provider base URL.
		 * @param string $endpoint_path Known endpoint path.
		 * @param array  $query_args    Query arguments to append.
		 *
		 * @return string
		 */
		private function build_remote_endpoint_url( string $base_url, string $endpoint_path, array $query_args = [] ): string {
			$url = $this->validate_remote_endpoint_url(
				trailingslashit( $base_url ) . ltrim( $endpoint_path, '/' )
			);

			if ( '' === $url ) {
				return '';
			}

			if ( ! empty( $query_args ) ) {
				$url = add_query_arg( $query_args, $url );
			}

			return $this->validate_remote_endpoint_url( $url );
		}

		/**
		 * Derive the next provider base URL from a redirect target.
		 *
		 * Redirects may move the provider domain or WordPress subdirectory, but
		 * they do not get to choose the final API path. We only keep the path
		 * prefix when the redirect target already contains the known endpoint.
		 *
		 * @param string $location      Redirect location header.
		 * @param string $current_url   URL that produced the redirect.
		 * @param string $endpoint_path Known endpoint path.
		 *
		 * @return string
		 */
		private function derive_remote_base_url_from_redirect( string $location, string $current_url, string $endpoint_path ): string {
			$redirect_url = $this->validate_remote_endpoint_url(
				WP_Http::make_absolute_url( $location, $current_url )
			);

			if ( '' === $redirect_url ) {
				return '';
			}

			$parts = wp_parse_url( $redirect_url );

			if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
				return '';
			}

			$base_url = strtolower( $parts['scheme'] ) . '://' . $parts['host'];

			if ( isset( $parts['port'] ) ) {
				$base_url .= ':' . (int) $parts['port'];
			}

			$path          = isset( $parts['path'] ) && is_string( $parts['path'] ) ? $parts['path'] : '';
			$endpoint_path = '/' . ltrim( $endpoint_path, '/' );
			$endpoint_pos  = strpos( $path, $endpoint_path );

			if ( false !== $endpoint_pos ) {
				$base_path = substr( $path, 0, $endpoint_pos );
				$base_url .= '/' . trim( $base_path, '/' );
			}

			return rtrim( $base_url, '/' );
		}

		/**
		 * Returns a list of categories.
		 *
		 * @param WP_REST_Request $request The request.
		 *
		 * @return WP_REST_Response
		 */
		public function list_categories( WP_REST_Request $request ): WP_REST_Response {
			$public_key = $request->get_header( 'X-GB-Public-Key' );
			$library_id = $request->get_param( 'libraryId' );

			$instance = GenerateBlocks_Pro_Pattern_Library::get_instance();
			$allowed_collections = $instance->get_collections_by_public_key( $public_key );

			if ( is_null( $allowed_collections ) ) {
				$allowed_collections = get_terms(
					[
						'taxonomy' => 'gblocks_pattern_collections',
						'slug'     => $library_id,
						'fields'   => 'ids',
					]
				);
			}

			if ( empty( $allowed_collections ) || is_wp_error( $allowed_collections ) ) {
				return $this->success( array() );
			}

			// Only categories that are attached to a pattern in one of the
			// allowed collections — keeps category names from leaking across keys.
			$pattern_ids = get_posts(
				array(
					'post_type'      => 'wp_block',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'tax_query'      => array(
						array(
							'taxonomy' => 'gblocks_pattern_collections',
							'field'    => 'term_id',
							'terms'    => $allowed_collections,
						),
					),
				)
			);

			if ( empty( $pattern_ids ) ) {
				return $this->success( array() );
			}

			$term_ids = wp_get_object_terms(
				$pattern_ids,
				'wp_pattern_category',
				array( 'fields' => 'ids' )
			);

			if ( empty( $term_ids ) || is_wp_error( $term_ids ) ) {
				return $this->success( array() );
			}

			$terms = get_terms(
				array(
					'taxonomy' => 'wp_pattern_category',
					'include'  => array_unique( array_map( 'intval', $term_ids ) ),
				)
			);

			if ( is_wp_error( $terms ) ) {
				return $this->success( array() );
			}

			$data = array_map(
				function( WP_Term $term ) {
					return array(
						'id'   => $term->term_id,
						'name' => $term->name,
					);
				},
				$terms
			);

			return $this->success( $data );
		}

		/**
		 * Returns a list of patterns.
		 *
		 * @param WP_REST_Request $request The request.
		 *
		 * @return WP_REST_Response
		 */
		public function list_patterns( WP_REST_Request $request ): WP_REST_Response {
			$public_key = $request->get_header( 'X-GB-Public-Key' );
			$library_id = $request->get_param( 'libraryId' );
			$search = $request->get_param( 'search' );
			$category_id = $request->get_param( 'categoryId' );
			$cat_query = isset( $category_id ) && '' !== $category_id ? array(
				'taxonomy' => 'wp_pattern_category',
				'field'    => 'term_id',
				'terms'    => $category_id,
			) : null;

			$instance = GenerateBlocks_Pro_Pattern_Library::get_instance();

			$allowed_collections = $instance->get_collections_by_public_key( $public_key );

			// If the key is not attached to any collection.
			if ( is_null( $allowed_collections ) ) {
				$allowed_collections = get_terms(
					[
						'taxonomy' => 'gblocks_pattern_collections',
						'slug' => $library_id,
						'fields' => 'ids',
					]
				);

				if ( ! $allowed_collections ) {
					return $this->success( array() );
				}
			}

			// Defensive cast against a non-numeric or negative filter return
			// (a misconfigured `-1` would scan the entire `wp_block` table).
			// Sites that intentionally raise the count above the default
			// keep that exact value — no upper clamp.
			$pattern_count = apply_filters( 'generateblocks_pattern_library_count', 250 );
			$pattern_count = is_numeric( $pattern_count ) ? (int) $pattern_count : 250;
			$pattern_count = max( 1, $pattern_count );

			$posts = get_posts(
				array(
					'post_type'      => 'wp_block',
					// `get_posts()` defaults `post_status` to 'publish' for
					// non-attachment types, but stating it explicitly matches
					// sibling routes (list_categories, provide_global_style_data)
					// and protects against any future override of the default.
					'post_status'    => 'publish',
					'posts_per_page' => $pattern_count, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- Filter is opt-in; result is cached on the consumer.
					's'              => $search,
					'tax_query'      => array(
						$cat_query,
						array(
							'taxonomy' => 'gblocks_pattern_collections',
							'field'    => 'term_id',
							'terms'    => $allowed_collections,
						),
					),
				)
			);

			$data = array_reduce(
				$posts,
				function( $patterns, $post ) use ( $category_id ) {
					$post_patterns = get_post_meta( $post->ID, 'generateblocks_patterns_tree', true );

					// Don't return patterns with no tree.
					if ( ! $post_patterns ) {
						return $patterns;
					}

					// Filter patterns by category.
					$filter_patterns = array_filter(
						$post_patterns,
						function( $pattern ) use ( $category_id ) {
							if ( isset( $category_id ) && '' !== $category_id ) {
								return in_array( $category_id, $pattern['categories'] );
							}

							return true;
						}
					);

					$form_selector_cache = [];

					$filter_patterns = array_map(
						function( $pattern ) use ( &$form_selector_cache ) {
							if ( ! is_array( $pattern ) ) {
								return $pattern;
							}

							$pattern['formRefs'] = array_values(
								array_filter(
									array_map( 'absint', (array) ( $pattern['formRefs'] ?? [] ) )
								)
							);

							if ( empty( $pattern['formRefs'] ) && is_string( $pattern['pattern'] ?? null ) ) {
								$pattern['formRefs'] = $this->collect_form_refs_from_blocks(
									parse_blocks( wp_unslash( $pattern['pattern'] ) )
								);
							}

							if ( ! empty( $pattern['formRefs'] ) ) {
								$global_style_selectors = is_array( $pattern['globalStyleSelectors'] ?? null )
									? $pattern['globalStyleSelectors']
									: [];

								$pattern['globalStyleSelectors'] = array_values(
									array_unique(
										array_filter(
											array_merge(
												$global_style_selectors,
												$this->get_current_form_global_style_selectors( $pattern['formRefs'], $form_selector_cache )
											),
											static function( $selector ) {
												return is_string( $selector ) && '' !== $selector;
											}
										)
									)
								);
							}

							return $pattern;
						},
						$filter_patterns
					);

					return array_merge(
						$patterns,
						$filter_patterns
					);
				},
				[]
			);

			return $this->success( $data );
		}

		/**
		 * Returns a list of required classes from the provider.
		 *
		 * @param WP_REST_Request $request The request.
		 *
		 * @return WP_REST_Response
		 */
		public function get_global_style_data( WP_REST_Request $request ): WP_REST_Response {
			$library = $this->get_remote_library_from_request( $request );

			if ( is_null( $library ) ) {
				return $this->failed( __( 'Invalid pattern library.', 'generateblocks-pro' ) );
			}

			$cache_key = $library->id . '_global-style-data';
			$cache = GenerateBlocks_Libraries::get_cached_data( $cache_key );

			$cache = $this->normalize_global_style_data( $cache );

			if ( false !== $cache ) {
				return $this->success( $cache );
			}

			$endpoint = $this->build_remote_endpoint_url(
				(string) $library->domain,
				self::ENDPOINT_GLOBAL_STYLE_DATA
			);

			if ( '' === $endpoint ) {
				return $this->failed( __( 'Invalid pattern library domain.', 'generateblocks-pro' ) );
			}

			$response = $this->fetch_remote_endpoint(
				(string) $library->domain,
				self::ENDPOINT_GLOBAL_STYLE_DATA,
				[
					'headers' => array(
						'X-GB-Public-Key' => sanitize_text_field( $library->public_key ),
					),
					'timeout' => 15,
				]
			);

			if ( is_wp_error( $response ) ) {
				return $this->failed( __( 'Unable to request required classes.', 'generateblocks-pro' ) );
			}

			$response_code = (int) wp_remote_retrieve_response_code( $response );

			if ( 200 !== $response_code ) {
				return $this->failed( __( 'Unable to request required classes.', 'generateblocks-pro' ) );
			}

			$body = wp_remote_retrieve_body( $response );
			$body = json_decode( $body, true );

			if ( ! is_array( $body ) ) {
				return $this->failed( __( 'Invalid required classes response.', 'generateblocks-pro' ) );
			}

			$data = $this->normalize_global_style_data( $body['response']['data'] ?? null );

			if ( false === $data ) {
				return $this->failed( __( 'Invalid required classes response.', 'generateblocks-pro' ) );
			}

			// Cache our data.
			GenerateBlocks_Libraries::set_cached_data( $data, $cache_key );

			return $this->success( $data );
		}

		/**
		 * Returns an array of class data.
		 * This function is called on the providers server.
		 *
		 * @param WP_REST_Request $request The request.
		 *
		 * @return WP_REST_Response
		 */
		public function provide_global_style_data( WP_REST_Request $request ): WP_REST_Response {
			$data = $this->empty_global_style_data();
			$public_key = $this->sanitize_request_string( $request->get_header( 'X-GB-Public-Key' ) );
			$permissions = GenerateBlocks_Pro_Pattern_Library::get_instance()
				->get_pattern_permissions_by_public_key( $public_key );

			if ( is_null( $permissions ) ) {
				return $this->success( $data );
			}

			$selectors = $this->get_global_style_selectors_by_collections( $permissions['includes'] );

			if ( empty( $selectors ) ) {
				return $this->success( $data );
			}

			$selector_map = array_fill_keys( $selectors, true );
			$style_count = apply_filters( 'generateblocks_styles_posts_per_page', 5000 );
			$style_count = is_numeric( $style_count ) ? (int) $style_count : 5000;
			$style_count = max( 1, $style_count );

			$posts = get_posts(
				[
					'post_type'      => 'gblocks_styles',
					'post_status'    => 'publish',
					'posts_per_page' => $style_count, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- Defensive cast above; filter is opt-in.
					'order'          => 'ASC',
					'orderby'        => 'menu_order',
				]
			);

			foreach ( $posts as $post ) {
				$class_name = get_post_meta( $post->ID, 'gb_style_selector', true );
				$styles = get_post_meta( $post->ID, 'gb_style_data', true );
				$css = get_post_meta( $post->ID, 'gb_style_css', true );

				if ( ! is_string( $class_name ) || empty( $selector_map[ $class_name ] ) ) {
					continue;
				}

				if ( ! is_array( $styles ) ) {
					$styles = [];
				}

				if ( ! is_string( $css ) ) {
					$css = '';
				}

				$data['css'] .= $css;
				$data['styles'][] = [
					'title' => $class_name,
					'className' => $class_name,
					'styles' => wp_json_encode( $styles ),
					'css' => $css,
				];
			}

			return $this->success( $data );
		}

		/**
		 * Returns safe form data referenced by allowed patterns.
		 *
		 * @param WP_REST_Request $request The request.
		 *
		 * @return WP_REST_Response
		 */
		public function provide_form_data( WP_REST_Request $request ): WP_REST_Response {
			$data = [ 'forms' => [] ];

			if ( ! $this->forms_are_available() ) {
				return $this->success( $data );
			}

			$form_ids = $this->validate_form_ids_payload( $request->get_param( 'formIds' ) );

			if ( is_wp_error( $form_ids ) || empty( $form_ids ) ) {
				return $this->success( $data );
			}

			$public_key = $this->sanitize_request_string( $request->get_header( 'X-GB-Public-Key' ) );
			$permissions = GenerateBlocks_Pro_Pattern_Library::get_instance()
				->get_pattern_permissions_by_public_key( $public_key );

			if ( is_null( $permissions ) ) {
				return $this->success( $data );
			}

			$allowed_form_ids = $this->get_form_refs_by_collections( $permissions['includes'] );

			if ( empty( $allowed_form_ids ) ) {
				return $this->success( $data );
			}

			$allowed_form_ids = array_fill_keys( $allowed_form_ids, true );

			foreach ( $form_ids as $form_id ) {
				if ( empty( $allowed_form_ids[ $form_id ] ) ) {
					continue;
				}

				$form = get_post( $form_id );

				if (
					! $form ||
					GenerateBlocks_Pro_Form_Post_Type::POST_TYPE !== $form->post_type ||
					'publish' !== $form->post_status
				) {
					continue;
				}

				$meta = get_post_meta( $form_id, GenerateBlocks_Pro_Form_Post_Type::META_KEY, true );
				$config = is_array( $meta ) && is_array( $meta['config'] ?? null ) ? $meta['config'] : [];

				$safe_config = $this->normalize_import_form_config( $config );

				if ( is_wp_error( $safe_config ) ) {
					continue;
				}

				$data['forms'][] = [
					'sourceId' => $form_id,
					'title'    => sanitize_text_field( $form->post_title ),
					'content'  => (string) $form->post_content,
					'config'   => $safe_config,
				];
			}

			return $this->success( $data );
		}

		/**
		 * Import remote forms needed by selected patterns.
		 *
		 * @param WP_REST_Request $request The request.
		 *
		 * @return WP_REST_Response
		 */
		public function import_forms( WP_REST_Request $request ): WP_REST_Response {
			if ( ! $this->forms_are_available() ) {
				return $this->failed( __( 'Forms are not available on this site.', 'generateblocks-pro' ) );
			}

			$form_ids = $this->validate_form_ids_payload( $request->get_param( 'formIds' ) );

			if ( is_wp_error( $form_ids ) ) {
				return $this->error( 400, $form_ids->get_error_message() );
			}

			if ( empty( $form_ids ) ) {
				return $this->success(
					[
						'idMap'    => [],
						'imported' => [],
					]
				);
			}

			$library = $this->get_remote_library_from_request( $request );

			if ( is_null( $library ) ) {
				return $this->failed( __( 'Invalid pattern library.', 'generateblocks-pro' ) );
			}

			$response = $this->fetch_remote_endpoint(
				(string) $library->domain,
				self::ENDPOINT_FORM_DATA,
				[
					'headers' => [
						'X-GB-Public-Key' => sanitize_text_field( $library->public_key ),
					],
					'timeout' => 15,
				],
				[
					'formIds' => implode( ',', $form_ids ),
				]
			);

			if ( is_wp_error( $response ) ) {
				return $this->failed( __( 'Unable to request required forms.', 'generateblocks-pro' ) );
			}

			if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				return $this->failed( __( 'Unable to request required forms.', 'generateblocks-pro' ) );
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! is_array( $body ) ) {
				return $this->failed( __( 'Invalid required forms response.', 'generateblocks-pro' ) );
			}

			$forms = $this->normalize_import_forms_payload( $body['response']['data'] ?? null, $form_ids );

			if ( is_wp_error( $forms ) ) {
				return $this->error( 400, $forms->get_error_message() );
			}

			$id_map = [];
			$imported = [];

			foreach ( $form_ids as $source_id ) {
				if ( ! isset( $forms[ $source_id ] ) ) {
					$this->rollback_imported_forms( $imported );

					return $this->failed( __( 'A required form was not available from the pattern library.', 'generateblocks-pro' ) );
				}

				$local_id = $this->insert_imported_form( $forms[ $source_id ] );

				if ( is_wp_error( $local_id ) ) {
					$this->rollback_imported_forms( $imported );

					return $this->error( 400, $local_id->get_error_message() );
				}

				$id_map[ $source_id ] = $local_id;
				$imported[] = $local_id;
			}

			return $this->success(
				[
					'idMap'    => $id_map,
					'imported' => $imported,
				]
			);
		}

		/**
		 * Check whether the Forms system is available.
		 *
		 * @return bool
		 */
		private function forms_are_available(): bool {
			return class_exists( 'GenerateBlocks_Pro_Form_Post_Type' ) &&
				class_exists( 'GenerateBlocks_Pro_Form_Rest' );
		}

		/**
		 * Validate form IDs from REST input.
		 *
		 * @param mixed $form_ids Raw form IDs.
		 *
		 * @return array|WP_Error
		 */
		private function validate_form_ids_payload( $form_ids ) {
			if ( is_string( $form_ids ) ) {
				$form_ids = explode( ',', $form_ids );
			}

			if ( ! is_array( $form_ids ) ) {
				return new WP_Error(
					'invalid_form_ids_payload',
					__( 'Invalid form IDs payload.', 'generateblocks-pro' )
				);
			}

			$form_ids = array_values(
				array_filter(
					array_unique(
						array_map( 'absint', $form_ids )
					)
				)
			);
			sort( $form_ids, SORT_NUMERIC );

			$max_forms = apply_filters( 'generateblocks_pattern_library_max_form_imports', 50 );
			$max_forms = is_numeric( $max_forms ) ? max( 1, (int) $max_forms ) : 50;

			if ( count( $form_ids ) > $max_forms ) {
				return new WP_Error(
					'too_many_form_ids',
					__( 'Too many forms requested.', 'generateblocks-pro' )
				);
			}

			return $form_ids;
		}

		/**
		 * Validate and normalize remote form data.
		 *
		 * @param mixed $data Requested data.
		 * @param array $requested_ids Requested source form IDs.
		 *
		 * @return array|WP_Error
		 */
		private function normalize_import_forms_payload( $data, array $requested_ids ) {
			if ( ! is_array( $data ) || ! is_array( $data['forms'] ?? null ) ) {
				return new WP_Error(
					'invalid_forms_payload',
					__( 'Invalid forms payload.', 'generateblocks-pro' )
				);
			}

			$requested_ids = array_fill_keys( $requested_ids, true );
			$forms = [];

			foreach ( $data['forms'] as $form ) {
				if (
					! is_array( $form ) ||
					! is_string( $form['content'] ?? null ) ||
					! is_array( $form['config'] ?? null )
				) {
					return new WP_Error(
						'invalid_form_payload',
						__( 'Invalid form item.', 'generateblocks-pro' )
					);
				}

				$source_id = absint( $form['sourceId'] ?? 0 );

				if ( $source_id <= 0 || empty( $requested_ids[ $source_id ] ) ) {
					continue;
				}

				$safe_config = $this->normalize_import_form_config( $form['config'] );

				if ( is_wp_error( $safe_config ) ) {
					return $safe_config;
				}

				$forms[ $source_id ] = [
					'sourceId' => $source_id,
					'title'    => sanitize_text_field( $form['title'] ?? '' ),
					'content'  => $form['content'],
					'config'   => $safe_config,
				];
			}

			return $forms;
		}

		/**
		 * Normalize form config for safe library transport.
		 *
		 * @param mixed $config Raw form config.
		 *
		 * @return array|WP_Error
		 */
		private function normalize_import_form_config( $config ) {
			$config = is_array( $config ) ? $config : [];
			$as_text = static function( $key ) use ( $config ) {
				return isset( $config[ $key ] ) && is_scalar( $config[ $key ] )
					? (string) $config[ $key ]
					: '';
			};

			$raw_actions = [];
			$actions = [];
			$allowed_actions = [ 'email', 'email-signup', 'confirmation-email' ];

			foreach ( (array) ( $config['actions'] ?? [] ) as $action ) {
				$action = is_string( $action ) ? sanitize_key( $action ) : '';

				if ( '' === $action || in_array( $action, $raw_actions, true ) ) {
					continue;
				}

				$raw_actions[] = $action;

				if ( in_array( $action, $allowed_actions, true ) ) {
					$actions[] = $action;
				}
			}

			$unsupported_actions = array_values( array_diff( $raw_actions, $allowed_actions ) );

			if ( ! empty( $unsupported_actions ) ) {
				return new WP_Error(
					'gb_form_import_unsupported_action',
					__( 'Imported forms include an unsupported action.', 'generateblocks-pro' )
				);
			}

			$form_type = sanitize_key( $as_text( 'formType' ) );
			$form_type = in_array( $form_type, [ 'contact', 'email-signup', 'contact-email-signup' ], true ) ? $form_type : '';

			if ( empty( $actions ) ) {
				$actions = 'email-signup' === $form_type ? [ 'email-signup' ] : [ 'email' ];
			}

			$settings = [];
			$source_settings = is_array( $config['actionSettings'] ?? null ) ? $config['actionSettings'] : [];
			$email_signup_settings = is_array( $source_settings['email-signup'] ?? null )
				? $source_settings['email-signup']
				: [];

			if ( in_array( 'email-signup', $actions, true ) ) {
				$settings['email-signup'] = [
					'emailField' => sanitize_key( $email_signup_settings['emailField'] ?? '' ),
					'nameField'  => sanitize_key( $email_signup_settings['nameField'] ?? '' ),
				];
			}

			$normalized = [
				'actions'                       => array_values( array_unique( $actions ) ),
				'actionSettings'                => $settings,
				'formType'                      => $form_type,
				'emailTo'                       => '',
				'emailSubject'                  => sanitize_text_field( $as_text( 'emailSubject' ) ),
				'emailReplyToField'             => sanitize_key( $as_text( 'emailReplyToField' ) ),
				'confirmationEmailField'        => sanitize_key( $as_text( 'confirmationEmailField' ) ),
				'confirmationEmailSubject'      => sanitize_text_field( $as_text( 'confirmationEmailSubject' ) ),
				'confirmationEmailBody'         => sanitize_textarea_field( $as_text( 'confirmationEmailBody' ) ),
				'confirmationEmailReplyToEmail' => '',
				'confirmationEmailReplyToName'  => sanitize_text_field( $as_text( 'confirmationEmailReplyToName' ) ),
				'useTurnstile'                  => false,
				'successMessage'                => sanitize_text_field( $as_text( 'successMessage' ) ),
				'errorMessage'                  => sanitize_text_field( $as_text( 'errorMessage' ) ),
				'redirectUrl'                   => '',
			];

			$meta = GenerateBlocks_Pro_Form_Post_Type::sanitize_meta( [ 'config' => $normalized ] );
			$safe_config = $meta['config'] ?? $normalized;

			// This is a transport payload, not final live form meta. Provider and
			// destination IDs are intentionally stripped above, so preserve the
			// vetted action list after live-form sanitization removes provider-less
			// email-signup actions.
			$safe_config['actions'] = $normalized['actions'];

			return $safe_config;
		}

		/**
		 * Get form IDs referenced by allowed pattern collections.
		 *
		 * @param array $collections The allowed collection term IDs.
		 *
		 * @return array
		 */
		private function get_form_refs_by_collections( array $collections ): array {
			if ( empty( $collections ) ) {
				return [];
			}

			$pattern_count = apply_filters( 'generateblocks_pattern_library_count', 250 );
			$pattern_count = is_numeric( $pattern_count ) ? (int) $pattern_count : 250;
			$pattern_count = max( 1, $pattern_count );

			$posts = get_posts(
				[
					'post_type'      => 'wp_block',
					'post_status'    => 'publish',
					'posts_per_page' => $pattern_count, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- Defensive cast above; filter is opt-in.
					'tax_query'      => [
						[
							'taxonomy' => 'gblocks_pattern_collections',
							'field'    => 'term_id',
							'terms'    => $collections,
						],
					],
				]
			);

			if ( ! is_array( $posts ) ) {
				return [];
			}

			$form_ids = [];

			foreach ( $posts as $post ) {
				if ( ! isset( $post->ID ) ) {
					continue;
				}

				$patterns = get_post_meta( $post->ID, 'generateblocks_patterns_tree', true );

				if ( ! is_array( $patterns ) ) {
					continue;
				}

				foreach ( $patterns as $pattern ) {
					if ( ! is_array( $pattern ) ) {
						continue;
					}

					$refs = array_values(
						array_filter(
							array_map( 'absint', (array) ( $pattern['formRefs'] ?? [] ) )
						)
					);

					if ( empty( $refs ) && is_string( $pattern['pattern'] ?? null ) ) {
						$refs = $this->collect_form_refs_from_blocks( parse_blocks( wp_unslash( $pattern['pattern'] ) ) );
					}

					foreach ( $refs as $ref ) {
						$form_ids[] = $ref;
					}
				}
			}

			return array_values( array_unique( $form_ids ) );
		}

		/**
		 * Collect form-render references from parsed blocks.
		 *
		 * @param array $blocks Parsed blocks.
		 * @param array $data Accumulator.
		 * @param array $reusable_block_ids Reusable block IDs already followed.
		 *
		 * @return array
		 */
		private function collect_form_refs_from_blocks( $blocks, array $data = [], array $reusable_block_ids = [] ): array {
			if ( ! is_array( $blocks ) ) {
				return $data;
			}

			foreach ( $blocks as $block ) {
				if ( ! is_array( $block ) ) {
					continue;
				}

				if ( 'generateblocks-pro/form-render' === ( $block['blockName'] ?? '' ) ) {
					$form_id = absint( $block['attrs']['formId'] ?? 0 );

					if ( $form_id > 0 ) {
						$data[] = $form_id;
					}
				}

				if ( 'core/block' === ( $block['blockName'] ?? '' ) ) {
					$ref = absint( $block['attrs']['ref'] ?? 0 );

					if ( $ref > 0 && ! in_array( $ref, $reusable_block_ids, true ) ) {
						$reusable_block = get_post( $ref );

						if ( $reusable_block && 'wp_block' === $reusable_block->post_type && 'publish' === $reusable_block->post_status ) {
							$reusable_block_ids[] = $ref;
							$data = $this->collect_form_refs_from_blocks(
								parse_blocks( $reusable_block->post_content ),
								$data,
								$reusable_block_ids
							);
						}
					}
				}

				if ( ! empty( $block['innerBlocks'] ) ) {
					$data = $this->collect_form_refs_from_blocks( $block['innerBlocks'], $data, $reusable_block_ids );
				}
			}

			return array_values( array_unique( $data ) );
		}

		/**
		 * Delete forms created by a failed dependency import batch.
		 *
		 * Existing matched forms are never passed here. We only roll back posts
		 * created in the current request, so a partial remote response cannot
		 * leave published orphan forms behind.
		 *
		 * @param array $form_ids Imported local form IDs.
		 *
		 * @return void
		 */
		private function rollback_imported_forms( array $form_ids ): void {
			foreach ( $form_ids as $form_id ) {
				$form_id = absint( $form_id );

				if ( $form_id > 0 ) {
					wp_delete_post( $form_id, true );
				}
			}
		}

		/**
		 * Insert a new local form from imported data.
		 *
		 * @param array $form Form data.
		 *
		 * @return int|WP_Error
		 */
		private function insert_imported_form( array $form ) {
			$schema_check = $this->validate_imported_form_schema( $form['content'] );

			if ( is_wp_error( $schema_check ) ) {
				return $schema_check;
			}

			$content = GenerateBlocks_Pro_Form_Rest::regenerate_block_unique_ids( $form['content'] );
			$title = '' !== $form['title']
				? $form['title']
				: __( 'Imported Form', 'generateblocks-pro' );

			$post_id = wp_insert_post(
				wp_slash(
					[
						'post_type'    => GenerateBlocks_Pro_Form_Post_Type::POST_TYPE,
						'post_status'  => 'publish',
						'post_title'   => $title,
						'post_content' => $content,
					]
				),
				true
			);

			if ( is_wp_error( $post_id ) ) {
				return $post_id;
			}

			if ( ! $post_id ) {
				return new WP_Error(
					'gb_form_import_failed',
					__( 'Could not import form.', 'generateblocks-pro' )
				);
			}

			$meta = GenerateBlocks_Pro_Form_Post_Type::sanitize_meta( [ 'config' => $form['config'] ] );

			if ( false === update_post_meta( $post_id, GenerateBlocks_Pro_Form_Post_Type::META_KEY, $meta ) ) {
				wp_delete_post( $post_id, true );

				return new WP_Error(
					'gb_form_import_meta_failed',
					__( 'Could not store imported form data.', 'generateblocks-pro' )
				);
			}

			return (int) $post_id;
		}

		/**
		 * Validate imported form content before creating a local form.
		 *
		 * @param string $content Form post content.
		 *
		 * @return true|WP_Error
		 */
		private function validate_imported_form_schema( string $content ) {
			if ( ! class_exists( 'GenerateBlocks_Pro_Form_Schema_Builder' ) ) {
				return true;
			}

			$schema = GenerateBlocks_Pro_Form_Schema_Builder::get_instance()
				->build_schema_from_content(
					$content,
					[ 'require_field_names' => true ]
				);

			if ( is_wp_error( $schema ) ) {
				return $schema;
			}

			if ( is_array( $schema ) && empty( $schema ) ) {
				return new WP_Error(
					'gb_form_no_fields',
					__( 'Imported form has no fields.', 'generateblocks-pro' )
				);
			}

			$disallowed_block = $this->find_disallowed_imported_form_block( parse_blocks( $content ) );

			if ( is_wp_error( $disallowed_block ) ) {
				return $disallowed_block;
			}

			return true;
		}

		/**
		 * Find block types that should not be accepted from remote form imports.
		 *
		 * Normal save filters still handle ordinary block content based on the
		 * current user's capabilities. These blocks are different: their purpose
		 * is arbitrary markup or shortcode execution, so remote form dependencies
		 * should not import them implicitly.
		 *
		 * @param array $blocks Parsed blocks.
		 *
		 * @return WP_Error|null
		 */
		private function find_disallowed_imported_form_block( $blocks ) {
			if ( ! is_array( $blocks ) ) {
				return null;
			}

			$disallowed_blocks = [
				'core/html',
				'core/shortcode',
			];

			foreach ( $blocks as $block ) {
				if ( ! is_array( $block ) ) {
					continue;
				}

				if ( in_array( $block['blockName'] ?? '', $disallowed_blocks, true ) ) {
					return new WP_Error(
						'gb_form_import_disallowed_block',
						__( 'Imported forms cannot include Custom HTML or Shortcode blocks.', 'generateblocks-pro' )
					);
				}

				if ( ! empty( $block['innerBlocks'] ) ) {
					$disallowed_block = $this->find_disallowed_imported_form_block( $block['innerBlocks'] );

					if ( is_wp_error( $disallowed_block ) ) {
						return $disallowed_block;
					}
				}
			}

			return null;
		}

		/**
		 * Get global style selectors referenced by allowed pattern collections.
		 *
		 * @param array $collections The allowed collection term IDs.
		 *
		 * @return array
		 */
		private function get_global_style_selectors_by_collections( array $collections ): array {
			if ( empty( $collections ) ) {
				return [];
			}

			// Defensive cast — see list_patterns() for rationale. No upper
			// clamp so sites that raise the filter intentionally keep their
			// exact value.
			$pattern_count = apply_filters( 'generateblocks_pattern_library_count', 250 );
			$pattern_count = is_numeric( $pattern_count ) ? (int) $pattern_count : 250;
			$pattern_count = max( 1, $pattern_count );

			$posts = get_posts(
				[
					'post_type'      => 'wp_block',
					'post_status'    => 'publish',
					'posts_per_page' => $pattern_count, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- Defensive cast above; filter is opt-in.
					'tax_query'      => [
						[
							'taxonomy' => 'gblocks_pattern_collections',
							'field'    => 'term_id',
							'terms'    => $collections,
						],
					],
				]
			);

			if ( ! is_array( $posts ) ) {
				return [];
			}

			$selectors = [];
			$form_selector_cache = [];

			foreach ( $posts as $post ) {
				if ( ! isset( $post->ID ) ) {
					continue;
				}

				$patterns = get_post_meta( $post->ID, 'generateblocks_patterns_tree', true );

				if ( ! is_array( $patterns ) ) {
					continue;
				}

				foreach ( $patterns as $pattern ) {
					if ( ! is_array( $pattern ) ) {
						continue;
					}

					if ( is_array( $pattern['globalStyleSelectors'] ?? null ) ) {
						foreach ( $pattern['globalStyleSelectors'] as $selector ) {
							if ( is_string( $selector ) && '' !== $selector ) {
								$selectors[] = $selector;
							}
						}
					}

					$form_refs = array_values(
						array_filter(
							array_map( 'absint', (array) ( $pattern['formRefs'] ?? [] ) )
						)
					);

					if ( empty( $form_refs ) && is_string( $pattern['pattern'] ?? null ) ) {
						$form_refs = $this->collect_form_refs_from_blocks(
							parse_blocks( wp_unslash( $pattern['pattern'] ) )
						);
					}

					if ( ! empty( $form_refs ) ) {
						$selectors = array_merge(
							$selectors,
							$this->get_current_form_global_style_selectors( $form_refs, $form_selector_cache )
						);
					}
				}
			}

			return array_values( array_unique( $selectors ) );
		}

		/**
		 * Get current global style selectors from referenced forms.
		 *
		 * @param array $form_refs Form post IDs.
		 * @param array $form_selector_cache Cached selectors by form ID.
		 *
		 * @return array
		 */
		private function get_current_form_global_style_selectors( array $form_refs, array &$form_selector_cache ): array {
			if ( ! class_exists( 'GenerateBlocks_Pro_Form_Post_Type' ) ) {
				return [];
			}

			$selectors = [];

			foreach ( $form_refs as $form_ref ) {
				$form_id = absint( $form_ref );

				if ( $form_id <= 0 ) {
					continue;
				}

				if ( ! array_key_exists( $form_id, $form_selector_cache ) ) {
					$form_selector_cache[ $form_id ] = [];
					$form = get_post( $form_id );

					if (
						$form &&
						GenerateBlocks_Pro_Form_Post_Type::POST_TYPE === $form->post_type &&
						'publish' === $form->post_status
					) {
						$form_selector_cache[ $form_id ] = $this->collect_global_style_selectors_from_blocks(
							parse_blocks( $form->post_content )
						);
					}
				}

				$selectors = array_merge( $selectors, $form_selector_cache[ $form_id ] );
			}

			return array_values( array_unique( $selectors ) );
		}

		/**
		 * Collect global style selectors from parsed blocks.
		 *
		 * @param array $blocks Parsed blocks.
		 * @param array $data Accumulator.
		 * @param array $reusable_block_ids Reusable block IDs already followed.
		 *
		 * @return array
		 */
		private function collect_global_style_selectors_from_blocks( $blocks, array $data = [], array $reusable_block_ids = [] ): array {
			if ( ! is_array( $blocks ) ) {
				return $data;
			}

			foreach ( $blocks as $block ) {
				if ( ! is_array( $block ) ) {
					continue;
				}

				$global_classes = is_array( $block['attrs']['globalClasses'] ?? null )
					? $block['attrs']['globalClasses']
					: [];

				foreach ( $global_classes as $global_class ) {
					if ( is_string( $global_class ) && '' !== $global_class ) {
						$data[] = '.' === $global_class[0] ? $global_class : '.' . $global_class;
					}
				}

				if ( 'core/block' === ( $block['blockName'] ?? '' ) ) {
					$ref = absint( $block['attrs']['ref'] ?? 0 );

					if ( $ref > 0 && ! in_array( $ref, $reusable_block_ids, true ) ) {
						$reusable_block = get_post( $ref );

						if ( $reusable_block && 'wp_block' === $reusable_block->post_type && 'publish' === $reusable_block->post_status ) {
							$reusable_block_ids[] = $ref;
							$data = $this->collect_global_style_selectors_from_blocks(
								parse_blocks( $reusable_block->post_content ),
								$data,
								$reusable_block_ids
							);
						}
					}
				}

				if ( ! empty( $block['innerBlocks'] ) ) {
					$data = $this->collect_global_style_selectors_from_blocks( $block['innerBlocks'], $data, $reusable_block_ids );
				}
			}

			return array_values( array_unique( $data ) );
		}

		/**
		 * Get the next menu order for a new style
		 *
		 * @return int The new menu_order to use.
		 */
		public function get_next_style_menu_order() {
			$styles     = GenerateBlocks_Pro_Styles::get_styles();
			$last_style = end( $styles );

			if ( $last_style ) {
				return ( $last_style['menu_order'] ?? 0 ) + 1;
			}

			return 0;
		}

		/**
		 * Import a set of global classes.
		 *
		 * @param WP_REST_Request $request The request.
		 *
		 * @return WP_REST_Response
		 */
		public function import_styles( WP_REST_Request $request ): WP_REST_Response {
			$styles = $this->validate_import_styles_payload( $request->get_param( 'styles' ) );

			if ( is_wp_error( $styles ) ) {
				return $this->error( 400, $styles->get_error_message() );
			}

			$response = [
				'imported' => [],
				'existing' => [],
			];

			foreach ( $styles as $style ) {
				$existing_class = new WP_Query(
					[
						'post_type'      => 'gblocks_styles',
						'posts_per_page' => 1,
						'post_status'    => 'any',
						'meta_query'     => [
							[
								'key'     => 'gb_style_selector',
								'value'   => $style['className'],
								'compare' => '=',
							],
						],
					]
				);

				$existing_post_id = false;

				if ( ! empty( $existing_class->found_posts ) ) {
					$existing_class = $existing_class->posts[0];

					$response['existing'][] = [
						'id'       => $existing_class->ID,
						'selector' => get_post_meta( $existing_class->ID, 'gb_style_selector', true ),
						'styles'   => get_post_meta( $existing_class->ID, 'gb_style_data', true ),
					];

					// Bail out if we already have this class published.
					if ( 'publish' === $existing_class->post_status ) {
						continue;
					}

					$existing_post_id = $existing_class->ID;
				}

				if ( $existing_post_id ) {
					$post_id = wp_update_post(
						[
							'ID' => $existing_post_id,
							'post_status' => 'publish',
							'menu_order'  => $this->get_next_style_menu_order(),
						]
					);
				} else {
					$post_id = wp_insert_post(
						[
							'post_type'   => 'gblocks_styles',
							'post_status' => 'publish',
							'post_title'  => $style['title'],
							'menu_order'  => $this->get_next_style_menu_order(),
							'meta_input'  => [
								'gb_style_selector' => $style['className'],
								'gb_style_data'     => $style['styles'],
								'gb_style_css'      => $style['css'],
							],
						]
					);

				}

				if ( is_wp_error( $post_id ) ) {
					return $this->error(
						500,
						sprintf(
							/* translators: %1$s: name of the component */
							__( 'Could not import class %1$s', 'generateblocks-pro' ),
							$style['title']
						)
					);
				}

				$response['imported'][] = get_post_meta( $post_id, 'gb_style_selector', true );
			}

			return $this->success( $response );
		}

		/**
		 * Validate and normalize incoming global style import data.
		 *
		 * @param mixed $styles Raw request value.
		 *
		 * @return array|WP_Error
		 */
		private function validate_import_styles_payload( $styles ) {
			if ( ! is_array( $styles ) ) {
				return new WP_Error(
					'invalid_styles_payload',
					__( 'Invalid global styles payload.', 'generateblocks-pro' )
				);
			}

			$validated = [];

			foreach ( $styles as $style ) {
				if (
					! is_array( $style ) ||
					! isset( $style['className'], $style['title'], $style['styles'], $style['css'] ) ||
					! is_string( $style['className'] ) ||
					! is_string( $style['title'] ) ||
					! is_string( $style['styles'] ) ||
					! is_string( $style['css'] )
				) {
					return new WP_Error(
						'invalid_style_payload',
						__( 'Invalid global style item.', 'generateblocks-pro' )
					);
				}

				$class_name = trim( $style['className'] );
				$title      = trim( $style['title'] );
				$style_data = json_decode( $style['styles'], true );

				if ( '' === $class_name || '' === $title || ! is_array( $style_data ) ) {
					return new WP_Error(
						'invalid_style_payload',
						__( 'Invalid global style item.', 'generateblocks-pro' )
					);
				}

				$validated[] = [
					'className' => $class_name,
					'title'     => $title,
					'styles'    => $style_data,
					'css'       => $style['css'],
				];
			}

			return $validated;
		}

		/**
		 * Returns some library data when given a public key.
		 * This function is called on the providers server.
		 *
		 * @param WP_REST_Request $request The request.
		 *
		 * @return WP_REST_Response
		 */
		public function get_library_by_public_key( WP_REST_Request $request ): WP_REST_Response {
			$public_key = $this->sanitize_request_string( $request->get_param( 'publicKey' ) );
			$header_key = $this->sanitize_request_string( $request->get_header( 'X-GB-Public-Key' ) );

			if ( '' === $public_key ) {
				$public_key = $header_key;
			}

			if ( '' !== $header_key && $public_key !== $header_key ) {
				return $this->failed( __( 'No library found.', 'generateblocks-pro' ) );
			}

			$permissions = GenerateBlocks_Pro_Pattern_Library::get_instance()
				->get_pattern_permissions_by_public_key( $public_key );

			if ( is_null( $permissions ) ) {
				return $this->failed( __( 'No library found.', 'generateblocks-pro' ) );
			}

			$library_name = sanitize_text_field( $permissions['name'] );

			$data = [
				'name' => $library_name,
			];

			return $this->success( $data );
		}

		/**
		 * Adds a new library when given data.
		 * Gets the name of the library from the provider.
		 *
		 * @param WP_REST_Request $request The request.
		 *
		 * @return WP_REST_Response
		 */
		public function add_library( WP_REST_Request $request ): WP_REST_Response {
			$data = $request->get_param( 'data' );
			$public_key = $this->sanitize_request_string( $request->get_param( 'publicKey' ) );
			$domain = esc_url_raw( $this->sanitize_request_string( $request->get_param( 'domain' ) ) );

			if ( ! is_array( $data ) ) {
				return $this->failed( __( 'Invalid library data.', 'generateblocks-pro' ) );
			}

			if ( ! $public_key ) {
				return $this->failed( __( 'No public key provided.', 'generateblocks-pro' ) );
			}

			if ( ! $domain ) {
				return $this->failed( __( 'No domain provided.', 'generateblocks-pro' ) );
			}

			$endpoint = $this->build_remote_endpoint_url(
				$domain,
				self::ENDPOINT_LIBRARY_BY_KEY,
				[
					'publicKey' => $public_key,
				]
			);

			if ( '' === $endpoint ) {
				return $this->failed( __( 'Invalid domain provided.', 'generateblocks-pro' ) );
			}

			$response = $this->fetch_remote_endpoint(
				$domain,
				self::ENDPOINT_LIBRARY_BY_KEY,
				[
					'headers' => array(
						'X-GB-Public-Key' => $public_key,
					),
					'timeout' => 15,
				],
				[
					'publicKey' => $public_key,
				]
			);

			if ( is_wp_error( $response ) ) {
				return $this->failed( $response->get_error_message() );
			}

			if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				return $this->failed( wp_remote_retrieve_response_message( $response ) );
			}

			$body = wp_remote_retrieve_body( $response );
			$body = json_decode( $body, true );
			$response_data = is_array( $body ) ? $body['response']['data'] ?? null : null;
			$name = is_array( $response_data ) && is_string( $response_data['name'] ?? null ) ? $response_data['name'] : '';

			if ( true !== ( $body['success'] ?? false ) || empty( $name ) ) {
				return $this->failed( __( 'Unable to get library name.', 'generateblocks-pro' ) );
			}

			$id = $this->sanitize_request_string( $data['id'] ?? '' );

			// Bound id to a slug/uuid character set so a caller can't inject
			// option-key-corrupting characters or ids that collide after
			// sanitization. Format is permissive enough to accept uuid-v4
			// ('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx') and short slugs alike.
			if ( '' === $id || ! preg_match( '/^[A-Za-z0-9_-]{1,64}$/', $id ) ) {
				return $this->failed( __( 'Invalid library data.', 'generateblocks-pro' ) );
			}

			$libraries = get_option( 'generateblocks_pattern_libraries', [] );

			if ( ! is_array( $libraries ) ) {
				$libraries = [];
			}

			/**
			 * Filters the maximum number of pattern libraries that can be stored.
			 *
			 * Caps the autoloaded `generateblocks_pattern_libraries` option so a
			 * runaway client loop or buggy UI cannot balloon it. Manage_options is
			 * still the primary gate; this is a belt-and-braces ceiling.
			 *
			 * @param int $max_libraries Default 50.
			 */
			$max_libraries = (int) apply_filters( 'generateblocks_pattern_library_max_libraries', 50 );

			if ( count( $libraries ) >= $max_libraries ) {
				return $this->failed(
					sprintf(
						/* translators: %d: maximum number of pattern libraries. */
						__( 'You have reached the maximum of %d pattern libraries.', 'generateblocks-pro' ),
						$max_libraries
					)
				);
			}

			foreach ( $libraries as $existing ) {
				if ( is_array( $existing ) && isset( $existing['id'] ) && $existing['id'] === $id ) {
					return $this->failed( __( 'A library with this id already exists.', 'generateblocks-pro' ) );
				}
			}

			$sanitized_data = [
				'id' => $id,
				'name' => sanitize_text_field( $name ),
				'domain' => $domain,
				'publicKey' => $public_key,
				'isEnabled' => isset( $data['isEnabled'] ) ? (bool) $data['isEnabled'] : true,
				'isDefault' => false,
				'isLocal' => false,
			];

			$libraries[] = $sanitized_data;
			update_option( 'generateblocks_pattern_libraries', $libraries );

			return $this->success( $sanitized_data );
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
		private function error( int $code, string $message ): WP_REST_Response {
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

	GenerateBlocks_Pro_Pattern_Library_Rest::get_instance()->init();

endif;
