<?php
/**
 * This file displays our block elements on the site.
 *
 * @package GP Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access, please.
}

/**
 * Class GeneratePress_Pro_Site_Editor
 */
class GenerateBlocks_Pro_Overlays extends GenerateBlocks_Pro_Singleton {
	/**
	 * Initialize the class filters.
	 *
	 * @return void
	 */
	public function init() {
		// Don't initialize if overlays are disabled.
		if ( ! generateblocks_pro_overlays_enabled() ) {
			return;
		}

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ), 8 );
		add_filter( 'block_editor_settings_all', array( $this, 'editor_css' ), 15 );
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar' ), 100 );
		add_filter( 'generateblocks_dashboard_tabs', [ $this, 'dashboard_tab' ] );
		add_filter( 'generateblocks_dashboard_screens', [ $this, 'dashboard_screen' ] );
		add_action( 'init', [ $this, 'register_post_meta' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest' ] );
		add_action( 'save_post_gblocks_overlay', [ $this, 'save_post' ] );
		add_action( 'delete_post', [ $this, 'delete_post' ] );
		add_action( 'transition_post_status', [ $this, 'transition_post_status' ], 10, 3 );
	}

	/**
	 * Get default values for overlay meta fields.
	 *
	 * @return array Default values.
	 */
	public static function get_meta_defaults() {
		return array(
			'_gb_overlay_display_condition' => '',
			'_gb_overlay_display_condition_invert' => false,
			'_gb_overlay_trigger_type' => 'click',
			'_gb_overlay_type' => 'standard',
			'_gb_overlay_placement' => 'bottom-start',
			'_gb_overlay_backdrop' => true,
			'_gb_overlay_backdrop_color' => 'rgba(0, 0, 0, 0.5)',
			'_gb_overlay_backdrop_blur' => '',
			'_gb_overlay_animation_in' => '',
			'_gb_overlay_animation_out' => '',
			'_gb_overlay_animation_duration' => '',
			'_gb_overlay_animation_target' => '',
			'_gb_overlay_animation_distance' => '',
			'_gb_overlay_scroll_percent' => '',
			'_gb_overlay_time_delay' => '',
			'_gb_overlay_cookie_duration' => '',
			'_gb_overlay_close_on_esc' => true,
			'_gb_overlay_close_on_click_outside' => true,
			'_gb_overlay_disable_page_scroll' => false,
			'_gb_overlay_position' => 'center',
			'_gb_overlay_custom_event' => '',
			'_gb_overlay_hide_if_cookies_disabled' => false,
			'_gb_overlay_position_to_parent' => '',
			'_gb_overlay_hover_buffer' => '20',
			'_gb_overlay_width_mode' => '',
		);
	}

	/**
	 * Register our post meta.
	 */
	public function register_post_meta() {
		$defaults = self::get_meta_defaults();

		$meta_fields = array(
			'_gb_overlay_display_condition' => array(
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'_gb_overlay_display_condition_invert' => array(
				'type' => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
			'_gb_overlay_trigger_type' => array(
				'type' => 'string',
				'sanitize_callback' => function ( $value ) {
					$allowed = [ 'click', 'hover', 'both', 'exit-intent', 'scroll', 'time', 'custom' ];
					return in_array( $value, $allowed ) ? $value : 'click';
				},
			),
			'_gb_overlay_type' => array(
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'_gb_overlay_placement' => array(
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'_gb_overlay_backdrop' => array(
				'type' => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
			'_gb_overlay_backdrop_color' => array(
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'_gb_overlay_backdrop_blur' => array(
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'_gb_overlay_animation_in' => array(
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'_gb_overlay_animation_out' => array(
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'_gb_overlay_animation_duration' => array(
				'type' => 'string',
				'sanitize_callback' => function ( $value ) {
					return '' === $value ? '' : absint( $value );
				},
			),
			'_gb_overlay_animation_target' => array(
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'_gb_overlay_animation_distance' => array(
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'_gb_overlay_scroll_percent' => array(
				'type' => 'string',
				'sanitize_callback' => function ( $value ) {
					return '' === $value ? '' : max( 0, min( 100, absint( $value ) ) );
				},
			),
			'_gb_overlay_time_delay' => array(
				'type' => 'string',
				'sanitize_callback' => function ( $value ) {
					return '' === $value ? '' : absint( $value );
				},
			),
			'_gb_overlay_cookie_duration' => array(
				'type' => 'string',
				'sanitize_callback' => function ( $value ) {
					return '' === $value ? '' : absint( $value );
				},
			),
			'_gb_overlay_close_on_esc' => array(
				'type' => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
			'_gb_overlay_close_on_click_outside' => array(
				'type' => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
			'_gb_overlay_disable_page_scroll' => array(
				'type' => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
			'_gb_overlay_position' => array(
				'type' => 'string',
				'sanitize_callback' => function ( $value ) {
					$allowed = [ 'center', 'top-left', 'top-center', 'top-right', 'center-left', 'center-right', 'bottom-left', 'bottom-center', 'bottom-right' ];
					return in_array( $value, $allowed ) ? $value : 'center';
				},
			),
			'_gb_overlay_custom_event' => array(
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'_gb_overlay_hide_if_cookies_disabled' => array(
				'type' => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
			'_gb_overlay_position_to_parent' => array(
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'_gb_overlay_hover_buffer' => array(
				'type' => 'string',
				'sanitize_callback' => function ( $value ) {
					return '' === $value ? '' : max( 0, min( 100, absint( $value ) ) );
				},
			),
			'_gb_overlay_width_mode' => array(
				'type' => 'string',
				'sanitize_callback' => function ( $value ) {
					$allowed = [ '', 'full' ];
					return in_array( $value, $allowed ) ? $value : '';
				},
			),
		);

		foreach ( $meta_fields as $key => $args ) {
			register_post_meta(
				'gblocks_overlay',
				$key,
				array(
					'single' => true,
					'type' => $args['type'],
					'auth_callback' => '__return_true',
					'sanitize_callback' => $args['sanitize_callback'],
					'show_in_rest' => true,
					'default' => $defaults[ $key ],
				)
			);
		}
	}

	/**
	 * Get overlay meta with proper defaults.
	 * This uses WordPress's built-in caching, so multiple calls are efficient.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Meta key.
	 * @return mixed Meta value or default.
	 */
	public static function get_overlay_meta( $post_id, $meta_key ) {
		$defaults = self::get_meta_defaults();

		// Check if meta exists in database.
		$meta_exists = metadata_exists( 'post', $post_id, $meta_key );

		if ( ! $meta_exists && isset( $defaults[ $meta_key ] ) ) {
			// Meta doesn't exist, return default.
			return $defaults[ $meta_key ];
		}

		// Meta exists, get the value.
		$value = get_post_meta( $post_id, $meta_key, true );

		// For boolean fields, properly convert WordPress storage to boolean.
		// WordPress stores: true as '1', false as '' (empty string).
		if ( isset( $defaults[ $meta_key ] ) && is_bool( $defaults[ $meta_key ] ) ) {
			return '1' === $value;
		}

		// For string/number fields, if empty and has default, return default.
		if ( '' === $value && isset( $defaults[ $meta_key ] ) && ! is_bool( $defaults[ $meta_key ] ) ) {
			return $defaults[ $meta_key ];
		}

		return $value;
	}

	/**
	 * Get all overlay meta at once for better performance.
	 * WordPress caches all meta after first query, but this can be more convenient.
	 *
	 * @param int $post_id Post ID.
	 * @return array All overlay meta with defaults applied.
	 */
	public static function get_all_overlay_meta( $post_id ) {
		$defaults = self::get_meta_defaults();
		$meta = array();

		foreach ( $defaults as $key => $default ) {
			$meta[ $key ] = self::get_overlay_meta( $post_id, $key );
		}

		return $meta;
	}

	/**
	 * Register the REST field for the admin edit URL.
	 *
	 * @return void
	 */
	public function register_rest() {
		register_rest_field(
			'gblocks_overlay',
			'admin_edit_url',
			array(
				'get_callback'    => function ( $post_object ) {
					if ( current_user_can( 'edit_post', $post_object['id'] ) ) {
						return get_edit_post_link( $post_object['id'], 'raw' );
					}

					return null;
				},
				'schema'          => array(
					'description' => 'Admin URL to edit the post.',
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
				),
			)
		);

		register_rest_field(
			'gblocks_overlay',
			'trigger_info',
			array(
				'get_callback'    => function ( $post_object ) {
					$trigger_type = self::get_overlay_meta( $post_object['id'], '_gb_overlay_trigger_type' );

					// Only return data for interactive overlays.
					if ( ! in_array( $trigger_type, array( 'click', 'hover', 'both' ), true ) ) {
						return null;
					}

					return array(
						'trigger_type' => $trigger_type,
						'is_interactive' => true,
					);
				},
				'schema'          => array(
					'description' => 'Overlay trigger information for block editor.',
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'trigger_type' => array(
							'type' => 'string',
							'enum' => array( 'click', 'hover', 'both' ),
						),
						'is_interactive' => array(
							'type' => 'boolean',
						),
					),
				),
			)
		);

		add_filter( 'rest_gblocks_overlay_collection_params', array( $this, 'increase_overlay_collection_limit' ), 10, 1 );
	}

	/**
	 * Increase the per_page limit for overlay collections.
	 * This is the proper WordPress way to handle this.
	 *
	 * @param array $params Collection parameters.
	 * @return array Modified parameters.
	 */
	public function increase_overlay_collection_limit( $params ) {
		// Increase limit to reasonable amount (not unlimited).
		if ( isset( $params['per_page'] ) ) {
			$params['per_page']['maximum'] = 200; // Reasonable limit for overlays.
			$params['per_page']['default'] = 100; // Keep default reasonable.
		}

		return $params;
	}

	/**
	 * Get the capability required for overlays.
	 *
	 * @since 2.4.0
	 * @param string $context The context: 'use' (select existing) or 'manage' (create/edit/dashboard).
	 * @return string The capability required.
	 */
	public static function get_overlays_capability( $context = 'use' ) {
		// Default capabilities.
		if ( 'manage' === $context ) {
			// Can create/edit/access dashboard - default to manage_options.
			$capability = 'manage_options';
		} else {
			// Can select/use existing overlays - anyone who can edit posts.
			$capability = 'edit_posts';
		}

		/**
		 * Filter the capability required for overlays.
		 *
		 * @since 2.4.0
		 * @param string $capability The capability required.
		 * @param string $context The context: 'use' or 'manage'.
		 */
		return apply_filters( 'generateblocks_overlays_capability', $capability, $context );
	}

	/**
	 * Check if current user can use overlays.
	 *
	 * @since 2.4.0
	 * @param string $context The context: 'use' (select existing) or 'manage' (create/edit/dashboard).
	 * @return bool
	 */
	public static function current_user_can_use_overlays( $context = 'use' ) {
		$capability = self::get_overlays_capability( $context );
		return current_user_can( $capability );
	}

	/**
	 * Add the Site Editor menu item.
	 *
	 * @return void
	 */
	public function admin_menu() {
		// Get the required capability.
		$capability = self::get_overlays_capability( 'manage' );

		// Only add menu if user has permission.
		if ( ! current_user_can( $capability ) ) {
			return;
		}

		add_submenu_page(
			'generateblocks',
			__( 'Overlay Panels', 'generateblocks-pro' ),
			__( 'Overlay Panels', 'generateblocks-pro' ),
			$capability,
			'generateblocks-overlay-panels',
			array( $this, 'render_page' ),
			4
		);
	}

	/**
	 * Render the Site Editor page.
	 *
	 * @return void
	 */
	public function render_page() {
		// Double-check permission before rendering.
		if ( ! self::current_user_can_use_overlays( 'manage' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'generateblocks-pro' ) );
		}
		?>
		<div class="wrap gblocks-dashboard-wrap">
			<div class="generateblocks-settings-area generateblocks-overlay-panels-area">
				<div id="gblocks-overlays"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Enqueue the admin assets.
	 *
	 * @return void
	 */
	public function enqueue_admin_assets() {
		$screen = get_current_screen();

		if ( 'generateblocks_page_generateblocks-overlay-panels' !== $screen->id ) {
			return;
		}

		$assets = generateblocks_pro_get_enqueue_assets( 'overlay-dashboard' );

		wp_enqueue_script(
			'gb-overlay-dashboard',
			GENERATEBLOCKS_PRO_DIR_URL . 'dist/overlay-dashboard.js',
			$assets['dependencies'],
			$assets['version'],
			true
		);

		wp_enqueue_style(
			'gb-overlay-dashboard',
			GENERATEBLOCKS_PRO_DIR_URL . 'dist/overlay-dashboard.css',
			array( 'wp-components', 'generateblocks-pro-dashboard-table' ),
			GENERATEBLOCKS_PRO_VERSION
		);

		wp_localize_script(
			'gb-overlay-dashboard',
			'gbOverlaysDashboard',
			array(
				'newOverlayUrl' => admin_url( 'post-new.php?post_type=gblocks_overlay' ),
			)
		);
	}

	/**
	 * Enqueue the block editor assets.
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets() {
		if ( 'gblocks_overlay' !== get_post_type() ) {
			return;
		}

		$assets = generateblocks_pro_get_enqueue_assets( 'overlay-editor' );

		wp_enqueue_script(
			'gb-overlay-editor',
			GENERATEBLOCKS_PRO_DIR_URL . 'dist/overlay-editor.js',
			$assets['dependencies'],
			$assets['version'],
			true
		);

		wp_enqueue_style(
			'gb-overlay-editor',
			GENERATEBLOCKS_PRO_DIR_URL . 'dist/overlay-editor.css',
			array( 'wp-components' ),
			GENERATEBLOCKS_PRO_VERSION
		);

		wp_localize_script(
			'gb-overlay-editor',
			'gbOverlayDefaults',
			self::get_meta_defaults()
		);
	}

	/**
	 * This resets the `max-width`, `margin-left`, and `margin-right` properties for our blocks in the editor.
	 * We have to do this as most themes use `.wp-block` to set a `max-width` and auto margins.
	 *
	 * We used to do this directly in the block CSS if those block attributes didn't exist, but this allows us
	 * to overwrite the reset in the `block_editor_settings_all` filter with a later priority.
	 *
	 * @param array $editor_settings The existing editor settings.
	 */
	public function editor_css( $editor_settings ) {
		if ( 'gblocks_overlay' === get_post_type() ) {
			$post_id = get_the_ID();
			$width_mode = self::get_overlay_meta( $post_id, '_gb_overlay_width_mode' );

			$css = '.is-root-container {max-width: calc(100% - 20px);margin-left: auto;margin-right:auto;}';
			$css .= '.editor-styles-wrapper {--content-width: 100% !important;}';

			// Add width mode specific styles.
			if ( 'full' === $width_mode ) {
				$css .= '.is-root-container {width: 100%;}';
			} else {
				$css .= '.is-root-container {width: max-content;min-width: 300px;}';
			}

			$editor_settings['styles'][] = [ 'css' => $css ];
		}

		return $editor_settings;
	}

	/**
	 * Add the Elementd admin bar item.
	 *
	 * @since 2.0.0
	 */
	public function add_admin_bar() {
		if ( ! self::current_user_can_use_overlays( 'manage' ) ) {
			return;
		}

		global $wp_admin_bar;
		global $gb_overlays;

		$title = __( 'Overlay Panels', 'generateblocks-pro' );
		$count = ! empty( $gb_overlays ) ? count( $gb_overlays ) : 0;

		// Prevent "Entire Site" Elements from being counted on non-edit pages in the admin.
		if ( is_admin() && function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();

			if ( ! isset( $screen->is_block_editor ) || ! $screen->is_block_editor ) {
				$count = 0;
			}

			if ( 'edit' !== $screen->parent_base ) {
				$count = 0;
			}
		}

		if ( $count > 0 ) {
			$title = sprintf(
				/* translators: Active Element count. */
				__( 'Overlay Panels (%s)', 'generateblocks-pro' ),
				$count
			);
		}

		$wp_admin_bar->add_menu(
			array(
				'id' => 'gb_overlays-menu',
				'title' => $title,
				'href' => esc_url( admin_url( 'edit.php?post_type=gblocks_overlay' ) ),
			)
		);

		if ( ! empty( $gb_overlays ) ) {
			// Prevent "Entire Site" Elements from being counted on non-edit pages in the admin.
			if ( is_admin() && function_exists( 'get_current_screen' ) ) {
				$screen = get_current_screen();

				if ( ! isset( $screen->is_block_editor ) || ! $screen->is_block_editor ) {
					return;
				}

				if ( 'edit' !== $screen->parent_base ) {
					return;
				}
			}

			foreach ( (array) $gb_overlays as $key => $data ) {
				$wp_admin_bar->add_menu(
					array(
						'id' => 'template-' . absint( $data['id'] ),
						'parent' => 'gb_overlays-menu',
						'title' => get_the_title( $data['id'] ),
						'href' => get_edit_post_link( $data['id'] ),
					)
				);
			}
		}
	}

	/**
	 * Add the Site Editor tab to our Dashboard tabs.
	 *
	 * @param array $tabs Existing tabs.
	 * @return array New tabs.
	 */
	public function dashboard_tab( $tabs ) {
		$screen = get_current_screen();

		$tabs['Overlay Panels'] = array(
			'name' => __( 'Overlay Panels', 'generateblocks-pro' ),
			'url' => admin_url( 'admin.php?page=generateblocks-overlay-panels' ),
			'class' => 'generateblocks_page_generateblocks-overlay-panels' === $screen->id ? 'active' : '',
		);

		return $tabs;
	}

	/**
	 * Add the Site Editor tab to our Dashboard screens.
	 *
	 * @param array $screens Existing screens.
	 * @return array New screens.
	 */
	public function dashboard_screen( $screens ) {
		$screens[] = 'generateblocks_page_generateblocks-overlay-panels';

		return $screens;
	}

	/**
	 * Get all overlays.
	 *
	 * @return array
	 */
	public static function get_overlays() {
		// Return empty array if overlays are disabled.
		if ( ! generateblocks_pro_overlays_enabled() ) {
			return array();
		}

		$overlays = get_posts(
			array(
				'post_type'      => 'gblocks_overlay',
				'posts_per_page' => 500, // phpcs:ignore -- Limit to 500 for performance.
				'post_status'    => 'publish',
			)
		);

		if ( empty( $overlays ) ) {
			return array();
		}

		return array_map(
			function ( $overlay ) {
				return array(
					'title' => $overlay->post_title,
					'id'    => $overlay->ID,
					'type'  => get_post_meta( $overlay->ID, '_gb_overlay_type', true ),
				);
			},
			$overlays
		);
	}

	/**
	 * Save post callback for overlays.
	 *
	 * This is used to clear the dynamic CSS cache when a overlay is saved.
	 *
	 * @param int $post_id The post ID being saved.
	 */
	public function save_post( $post_id ) {
		// If this is an autosave, do nothing.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// If this is not a overlay post type, do nothing.
		if ( get_post_type( $post_id ) !== 'gblocks_overlay' ) {
			return;
		}

		update_option( 'generateblocks_dynamic_css_posts', [] );

		// Clear overlay cache when any overlay is saved.
		generateblocks_pro_clear_overlay_cache();
	}

	/**
	 * Delete post callback for overlays.
	 *
	 * @param int $post_id The post ID being deleted.
	 */
	public function delete_post( $post_id ) {
		if ( get_post_type( $post_id ) !== 'gblocks_overlay' ) {
			return;
		}

		// Clear overlay cache when any overlay is deleted.
		generateblocks_pro_clear_overlay_cache();
	}

	/**
	 * Transition post status callback for overlays.
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post       Post object.
	 */
	public function transition_post_status( $new_status, $old_status, $post ) {
		if ( 'gblocks_overlay' !== $post->post_type ) {
			return;
		}

		// Clear cache when overlay status changes.
		if ( $new_status !== $old_status ) {
			generateblocks_pro_clear_overlay_cache();
		}
	}
}

GenerateBlocks_Pro_Overlays::get_instance()->init();
