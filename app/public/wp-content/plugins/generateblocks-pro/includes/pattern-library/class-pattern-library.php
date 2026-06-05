<?php
/**
 * The Libraries class file.
 *
 * @package GenerateBlocks\Pattern_Library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'GenerateBlocks_Libraries' ) ) :
	/**
	 * Class for handling with libraries.
	 *
	 * @since 1.9.0
	 */
	class GenerateBlocks_Pro_Pattern_Library extends GenerateBlocks_Pro_Singleton {

		/**
		 * The default id.
		 *
		 * @var string The default library id.
		 */
		protected $default_library_id = 'gb_default_pro_library';

		/**
		 * Initialize the class filters.
		 *
		 * @return void
		 */
		public function init() {
			add_filter(
				'generateblocks_pattern_libraries',
				array( $this, 'register_pro_libraries' ),
				5
			);

			add_action( 'wp_after_insert_post', [ $this, 'after_save' ], 10, 2 );
			add_action( 'wp_after_insert_post', [ $this, 'rebuild_trees_after_form_save' ], 20, 2 );
		}

		/**
		 * Register PRO libraries.
		 *
		 * @param array $libraries The registered libraries.
		 *
		 * @return array
		 */
		public function register_pro_libraries( array $libraries ): array {
			$libraries_instance = GenerateBlocks_Libraries::get_instance();

			// Force to always have the PRO library registered.
			if ( ! $libraries_instance->exists( $libraries, $this->default_library_id ) ) {
				$pro_library = $this->get_default();
				$libraries = array_merge( array( $pro_library ), $libraries );
			}

			$collections = $this->get_collections();

			$local_libraries = array_map(
				function( WP_term $term ) use ( $libraries_instance ) {
					return $libraries_instance->create(
						array(
							'id' => $term->slug,
							'name' => $term->name,
							'domain' => get_site_url(),
							'publicKey' => $term->slug,
							'isEnabled' => false,
							'isDefault' => false,
							'isLocal' => true,
						)
					);
				},
				$collections
			);

			return array_merge( $libraries, $local_libraries );
		}

		/**
		 * Returns the list of collections.s
		 *
		 * @return int[]|string|string[]|WP_Error|WP_Term[]
		 */
		protected function get_collections() {
			return get_terms(
				[
					'taxonomy' => 'gblocks_pattern_collections',
					'hide_empty' => true,
				]
			);
		}

		/**
		 * Return the default library.
		 *
		 * @return GenerateBlocks_Library_DTO
		 */
		protected function get_default(): GenerateBlocks_Library_DTO {
			$use_legacy_pattern_library = ! function_exists( 'generateblocks_use_v1_blocks' ) ||
				generateblocks_use_v1_blocks();

			$domain = 'https://patterns.generatepress.com';
			$public_key = 'NPhxc91jLH5yGB4Ni6KryXN6HKKggte0';

			if (
				$use_legacy_pattern_library ||
				apply_filters( 'generateblocks_force_v1_pattern_library', false )
			) {
				$domain = 'https://patterns.generateblocks.com';
				$public_key = 'c4ngBQvKWeqG17W8SAWVJ0FuyD9uCVvq';
			}

			return ( new GenerateBlocks_Library_DTO() )
				->set( 'id', $this->default_library_id )
				->set( 'name', __( 'Pro', 'generateblocks-pro' ) )
				->set( 'domain', $domain )
				->set( 'public_key', $public_key )
				->set( 'is_enabled', true )
				->set( 'is_default', true );
		}

		/**
		 * Determine if a stored GenerateCloud boolean permission is enabled.
		 *
		 * @param mixed $value The stored permission value.
		 *
		 * @return bool
		 */
		private function is_permission_enabled( $value ): bool {
			if ( true === $value || 1 === $value || '1' === $value ) {
				return true;
			}

			if ( is_string( $value ) ) {
				return 'true' === strtolower( $value );
			}

			return false;
		}

		/**
		 * Get validated pattern permissions for a GenerateCloud public key.
		 *
		 * @param mixed $public_key The public key.
		 *
		 * @return array|null
		 */
		public function get_pattern_permissions_by_public_key( $public_key ): ?array {
			if (
				! is_string( $public_key ) ||
				'' === $public_key ||
				! class_exists( 'GenerateCloud\Utils\Functions' )
			) {
				return null;
			}

			$public_key_post = GenerateCloud\Utils\Functions::get_public_key_post( $public_key );

			if ( ! isset( $public_key_post->ID ) ) {
				return null;
			}

			$permissions = get_post_meta( $public_key_post->ID, 'gb_permissions', true );

			if ( ! is_array( $permissions ) ) {
				return null;
			}

			$patterns = $permissions['patterns'] ?? null;

			if ( ! is_array( $patterns ) ) {
				return null;
			}

			if ( ! $this->is_permission_enabled( $patterns['enabled'] ?? false ) ) {
				return null;
			}

			$includes = $patterns['includes'] ?? [];

			if ( ! is_array( $includes ) ) {
				$includes = [];
			}

			$collections = [];

			foreach ( $includes as $collection ) {
				if ( ! is_numeric( $collection ) ) {
					continue;
				}

				$collection = (int) $collection;

				if ( $collection > 0 ) {
					$collections[] = $collection;
				}
			}

			return [
				'name' => is_string( $patterns['name'] ?? '' ) ? $patterns['name'] : '',
				'includes' => array_values( array_unique( $collections ) ),
			];
		}

		/**
		 * Given a key searches for all pattern collections.
		 *
		 * @param mixed $public_key The public key.
		 *
		 * @return array|null
		 */
		public function get_collections_by_public_key( $public_key ) {
			$permissions = $this->get_pattern_permissions_by_public_key( $public_key );

			if ( is_null( $permissions ) ) {
				return null;
			}

			return $permissions['includes'];
		}

		/**
		 * Saves our pattern tree when saving a pattern.
		 *
		 * @param int     $post_id The post ID.
		 * @param WP_Post $post The post object.
		 *
		 * @return void
		 */
		public function after_save( int $post_id, WP_Post $post ): void {
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			// Check if it is an autosave or a revision.
			if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			$post_type = get_post_type( $post_id );

			if ( 'wp_block' !== $post_type ) {
				return;
			}

			$tree = $this->build_tree( $post->post_content, $post_id );
			update_post_meta( $post_id, 'generateblocks_patterns_tree', $tree );

			// Clear our block pattern cache so it can regenerate.
			delete_option( 'generateblocks_block_patterns' );
		}

		/**
		 * Rebuild pattern trees that reference a form whenever the form is saved.
		 *
		 * The cached `preview` HTML inside each `wp_block`'s
		 * `generateblocks_patterns_tree` is generated once at pattern save and
		 * embeds the referenced form's fields/styles at that moment. Form edits
		 * therefore go stale in the inserter until the pattern is re-saved.
		 * Mirror `invalidate_referencing_post_css` so a form save eagerly
		 * regenerates dependent pattern previews.
		 *
		 * @param int     $post_id Saved post ID.
		 * @param WP_Post $post    Saved post.
		 *
		 * @return void
		 */
		public function rebuild_trees_after_form_save( int $post_id, WP_Post $post ): void {
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
				return;
			}

			if ( ! class_exists( 'GenerateBlocks_Pro_Form_Post_Type' ) ) {
				return;
			}

			if ( GenerateBlocks_Pro_Form_Post_Type::POST_TYPE !== $post->post_type ) {
				return;
			}

			$pattern_ids = $this->get_pattern_ids_referencing_form( $post_id );

			if ( empty( $pattern_ids ) ) {
				return;
			}

			$rebuilt = false;

			foreach ( $pattern_ids as $pattern_id ) {
				$pattern = get_post( $pattern_id );

				if ( ! $pattern instanceof WP_Post || 'wp_block' !== $pattern->post_type ) {
					continue;
				}

				$tree = $this->build_tree( $pattern->post_content, $pattern->ID );
				update_post_meta( $pattern->ID, 'generateblocks_patterns_tree', $tree );
				$rebuilt = true;
			}

			if ( $rebuilt ) {
				// Mirror after_save(): the block pattern registration cache
				// embeds preview/scripts/styles from the tree, so it has to
				// regenerate alongside the trees we just rewrote.
				delete_option( 'generateblocks_block_patterns' );
			}
		}

		/**
		 * Find pattern post IDs whose cached tree references the given form.
		 *
		 * The `formRefs` array on each tree item serializes integers as
		 * `i:N;`. A `LIKE` prefilter on that token avoids unserializing every
		 * `generateblocks_patterns_tree` row in `wp_postmeta`; the follow-up
		 * walk discards false positives (e.g. an unrelated integer that
		 * happens to equal the form ID).
		 *
		 * @param int $form_id Form post ID.
		 *
		 * @return array<int>
		 */
		private function get_pattern_ids_referencing_form( int $form_id ): array {
			global $wpdb;

			if ( ! $wpdb || $form_id <= 0 ) {
				return [];
			}

			$like = '%' . $wpdb->esc_like( 'i:' . $form_id . ';' ) . '%';

			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT post_id, meta_value FROM {$wpdb->postmeta}
					WHERE meta_key = %s AND meta_value LIKE %s",
					'generateblocks_patterns_tree',
					$like
				)
			);

			$matches = [];

			foreach ( (array) $rows as $row ) {
				$tree = maybe_unserialize( $row->meta_value ?? '' );

				if ( ! is_array( $tree ) ) {
					continue;
				}

				foreach ( $tree as $pattern ) {
					if ( ! is_array( $pattern ) ) {
						continue;
					}

					$refs = $pattern['formRefs'] ?? [];

					if ( ! is_array( $refs ) ) {
						continue;
					}

					foreach ( $refs as $ref ) {
						if ( (int) $ref === $form_id ) {
							$matches[] = absint( $row->post_id ?? 0 );
							break 2;
						}
					}
				}
			}

			return array_values( array_unique( array_filter( $matches ) ) );
		}

		/**
		 * Enable inline styles.
		 * We need a function so we can remove_action it after.
		 */
		public function enable_inline_styles() {
			return true;
		}

		/**
		 * Extracts global styles from blocks.
		 *
		 * @param array $blocks The parsed blocks on the page.
		 * @param array $data Optional. Additional data to be populated.
		 *
		 * @return void
		 */
		private function extract_global_style_selectors(
			$blocks,
			$data = [
				'selectors' => [],
				'reusableBlockIds' => [],
				'formIds' => [],
			]
		) {
			$global_styles = [];

			if ( ! is_array( $blocks ) || empty( $blocks ) ) {
				return;
			}

			foreach ( $blocks as $index => $block ) {
				if ( ! isset( $block['attrs'] ) || ! isset( $block['blockName'] ) ) {
					continue;
				}

				$block_global_styles = $block['attrs']['globalClasses'] ?? [];

				if ( ! empty( $block_global_styles ) ) {
					foreach ( $block_global_styles as $global_style ) {
						if ( ! in_array( $global_style, $global_styles ) ) {
							$data['selectors'][] = '.' . $global_style;
						}
					}
				}

				if ( 'core/block' === $block['blockName'] ) {
					if ( isset( $block['attrs'] ) && is_array( $block['attrs'] ) ) {
						$atts = $block['attrs'];

						if ( isset( $atts['ref'] ) && ( empty( $data['reusableBlockIds'] ) || ! in_array( $atts['ref'], (array) $data['reusableBlockIds'] ) ) ) {
							$reusable_block = get_post( $atts['ref'] );

							if ( $reusable_block && 'wp_block' === $reusable_block->post_type && 'publish' === $reusable_block->post_status ) {
								$reuse_data_block = parse_blocks( $reusable_block->post_content );

								if ( ! empty( $reuse_data_block ) ) {
									$data['reusableBlockIds'][] = $atts['ref'];
									$data = $this->extract_global_style_selectors( $reuse_data_block, $data );
								}
							}
						}
					}
				}

				if ( 'generateblocks-pro/form-render' === $block['blockName'] ) {
					$form_id = absint( $block['attrs']['formId'] ?? 0 );

					if (
						$form_id > 0 &&
						( empty( $data['formIds'] ) || ! in_array( $form_id, (array) $data['formIds'], true ) ) &&
						class_exists( 'GenerateBlocks_Pro_Form_Post_Type' )
					) {
						$form = get_post( $form_id );

						if (
							$form &&
							GenerateBlocks_Pro_Form_Post_Type::POST_TYPE === $form->post_type &&
							'publish' === $form->post_status
						) {
							$form_blocks = parse_blocks( $form->post_content );

							if ( ! empty( $form_blocks ) ) {
								$data['formIds'][] = $form_id;
								$data = $this->extract_global_style_selectors( $form_blocks, $data );
							}
						}
					}
				}

				if ( isset( $block['innerBlocks'] ) && ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
					$data = $this->extract_global_style_selectors( $block['innerBlocks'], $data );
				}
			}

			return $data;
		}

		/**
		 * Extract form-render references from blocks.
		 *
		 * @param array $blocks The parsed blocks on the page.
		 * @param array $data Optional. Additional data to be populated.
		 *
		 * @return array
		 */
		private function extract_form_refs(
			$blocks,
			$data = [
				'formIds' => [],
				'reusableBlockIds' => [],
			]
		) {
			if ( ! is_array( $blocks ) || empty( $blocks ) ) {
				return $data;
			}

			foreach ( $blocks as $block ) {
				if ( ! isset( $block['attrs'] ) || ! isset( $block['blockName'] ) ) {
					continue;
				}

				if ( 'generateblocks-pro/form-render' === $block['blockName'] ) {
					$form_id = absint( $block['attrs']['formId'] ?? 0 );

					if ( $form_id > 0 && ! in_array( $form_id, (array) $data['formIds'], true ) ) {
						$data['formIds'][] = $form_id;
					}
				}

				if ( 'core/block' === $block['blockName'] ) {
					$atts = is_array( $block['attrs'] ) ? $block['attrs'] : [];
					$ref  = absint( $atts['ref'] ?? 0 );

					if ( $ref > 0 && ! in_array( $ref, (array) $data['reusableBlockIds'], true ) ) {
						$reusable_block = get_post( $ref );

						if ( $reusable_block && 'wp_block' === $reusable_block->post_type && 'publish' === $reusable_block->post_status ) {
							$data['reusableBlockIds'][] = $ref;
							$data = $this->extract_form_refs( parse_blocks( $reusable_block->post_content ), $data );
						}
					}
				}

				if ( isset( $block['innerBlocks'] ) && ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
					$data = $this->extract_form_refs( $block['innerBlocks'], $data );
				}
			}

			return $data;
		}

		/**
		 * Build our pattern tree.
		 *
		 * @param string $post_content The post content from our pattern.
		 * @param int    $post_id The ID of our post.
		 *
		 * @return array
		 */
		public function build_tree( string $post_content, $post_id ): array {
			// Force our previews to print inline styles above each block.
			add_filter( 'generateblocks_do_inline_styles', [ $this, 'enable_inline_styles' ] );

			// Get our full pattern to insert into the editor.
			$preview = do_blocks( $post_content );

			// Get any script URLs that need to be added later.
			$scripts = [];
			$styles  = [];

			if ( strpos( $preview, 'gb-accordion' ) !== false ) {
				$scripts[] = GENERATEBLOCKS_PRO_DIR_URL . 'dist/accordion.js';
				$styles[]  = GENERATEBLOCKS_PRO_DIR_URL . 'dist/accordion-style.css';
			}

			if ( strpos( $preview, 'gb-tabs' ) !== false ) {
				$scripts[] = GENERATEBLOCKS_PRO_DIR_URL . 'dist/tabs.js';
				$styles[]  = GENERATEBLOCKS_PRO_DIR_URL . 'dist/blocks/tabs/tabs.css';
			}

			if ( strpos( $preview, 'gb-menu' ) !== false ) {
				$scripts[] = GENERATEBLOCKS_PRO_DIR_URL . 'dist/classic-menu.js';
				$styles[]  = GENERATEBLOCKS_PRO_DIR_URL . 'dist/classic-menu-style.css';
			}

			if ( strpos( $preview, 'gb-form' ) !== false ) {
				$styles[] = GENERATEBLOCKS_PRO_DIR_URL . 'dist/form-style.css';
			}

			$blocks = parse_blocks( $post_content );
			$global_style_selectors = $this->extract_global_style_selectors( $blocks );
			$form_refs = $this->extract_form_refs( $blocks );

			$patterns[] = [
				'id' => 'pattern-' . $post_id,
				'label' => get_the_title( $post_id ) ?? 'pattern-' . $post_id,
				'pattern' => wp_slash( $post_content ),
				'preview' => $preview,
				'scripts' => apply_filters(
					'generateblocks_pattern_preview_scripts',
					$scripts,
					[
						'preview' => $preview,
						'post_content' => $post_content,
					]
				),
				'styles' => apply_filters(
					'generateblocks_pattern_preview_styles',
					$styles,
					[
						'preview' => $preview,
						'post_content' => $post_content,
					]
				),
				'categories' => wp_get_post_terms( $post_id, 'wp_pattern_category', [ 'fields' => 'ids' ] ),
				'globalStyleSelectors' => $global_style_selectors['selectors'] ?? [],
				'formRefs' => $form_refs['formIds'] ?? [],
			];

			// Cleanup.
			remove_filter( 'generateblocks_do_inline_styles', [ $this, 'enable_inline_styles' ] );

			return $patterns;
		}
	}

	GenerateBlocks_Pro_Pattern_Library::get_instance()->init();
endif;
