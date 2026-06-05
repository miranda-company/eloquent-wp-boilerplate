<?php
/**
 * Handles the Content block.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Content block class.
 */
class GenerateBlocks_Block_Classic_Menu extends GenerateBlocks_Block {
	/**
	 * Keep track of all blocks of this type on the page.
	 *
	 * @var array $block_ids The current block id.
	 */
	protected static $block_ids = [];

	/**
	 * Store our block name.
	 *
	 * @var string $block_name The block name.
	 */
	public static $block_name = 'generateblocks-pro/classic-menu';

	/**
	 * Cache for mega menu display checks.
	 *
	 * @var array
	 */
	private static $mega_menu_cache = [];

	/**
	 * Check if a mega menu overlay should display based on conditions.
	 *
	 * @param int $overlay_id The overlay post ID.
	 * @return bool Whether the overlay should display.
	 */
	private static function should_display_mega_menu( $overlay_id ) {
		// Return false if overlays are disabled.
		if ( ! generateblocks_pro_overlays_enabled() ) {
			return false;
		}

		if ( ! $overlay_id ) {
			return false;
		}

		// Check cache first.
		if ( isset( self::$mega_menu_cache[ $overlay_id ] ) ) {
			return self::$mega_menu_cache[ $overlay_id ];
		}

		// Check if post exists and is published.
		$overlay_post = get_post( $overlay_id );
		if ( ! $overlay_post || 'publish' !== $overlay_post->post_status ) {
			self::$mega_menu_cache[ $overlay_id ] = false;
			return false;
		}

		$overlay_post_content = do_blocks( $overlay_post->post_content ) ?? '';

		if ( empty( $overlay_post_content ) ) {
			self::$mega_menu_cache[ $overlay_id ] = false;
			return false;
		}

		// Check display conditions.
		$display_condition  = get_post_meta( $overlay_id, '_gb_overlay_display_condition', true );
		$display_conditions = [];

		if ( $display_condition ) {
			// Check if the condition post exists and is published.
			$condition_post = get_post( $display_condition );

			if ( $condition_post && 'publish' === $condition_post->post_status ) {
				$display_conditions = get_post_meta( $display_condition, '_gb_conditions', true ) ?? [];
			}
		}

		$show = true;

		if ( ! empty( $display_conditions ) ) {
			$show             = GenerateBlocks_Pro_Conditions::show( $display_conditions );
			$invert_condition = GenerateBlocks_Pro_Overlays::get_overlay_meta( $overlay_id, '_gb_overlay_display_condition_invert' );

			// If invert is enabled, flip the result.
			if ( $invert_condition ) {
				$show = ! $show;
			}
		}

		// Cache the result.
		self::$mega_menu_cache[ $overlay_id ] = $show;

		return $show;
	}

	/**
	 * Render the Element block.
	 *
	 * @param array  $attributes    The block attributes.
	 * @param string $block_content The block content.
	 * @param array  $block         The block.
	 */
	public static function render_block( $attributes, $block_content, $block ) {
		// Clear mega menu cache for fresh checks.
		self::$mega_menu_cache = [];

		// Add styles to this block if needed.
		$block_content = generateblocks_maybe_add_block_css(
			$block_content,
			[
				'class_name' => __CLASS__,
				'attributes' => $attributes,
				'block_ids' => self::$block_ids,
			]
		);

		$selected_menu = $attributes['menu'] ?? '';
		$unique_id = $attributes['uniqueId'] ?? '';

		if ( ! $selected_menu || ! $unique_id ) {
			return;
		}

		if ( isset( $block->context['generateblocks-pro/subMenuType'] ) ) {
			$sub_menu_type = $block->context['generateblocks-pro/subMenuType'];
		} elseif ( isset( $_GET['subMenuType'] ) ) { // phpcs:ignore -- No processing of data.
			$sub_menu_type = esc_attr( $_GET['subMenuType'] ); // phpcs:ignore -- No processing of data.
		} else {
			$sub_menu_type = 'hover';
		}

		$classes = [
			'gb-menu',
			'gb-menu--base',
			'gb-menu-' . $unique_id,
			'gb-menu--' . $sub_menu_type,
		];

		if ( ! empty( $attributes['globalClasses'] ) ) {
			$classes = array_merge( $classes, $attributes['globalClasses'] );
		}

		if ( ! empty( $attributes['className'] ) ) {
			$classes[] = $attributes['className'];
		}

		$class = implode( ' ', $classes );

		$add_menu_item_classes = function( $classes, $menu_item ) use ( $sub_menu_type, $unique_id ) {
			$mega_menu = get_post_meta( $menu_item->ID, '_gb_mega_menu', true );

			if ( $mega_menu && self::should_display_mega_menu( $mega_menu ) ) {
				$classes[] = 'menu-item-has-gb-mega-menu';
				$classes[] = 'menu-item-has-children';
			}

			$classes[] = 'gb-menu-item';
			$new_unique_id = substr_replace( $unique_id, 'mi', 0, 2 );
			$classes[] = 'gb-menu-item-' . $new_unique_id;

			// Escape classes.
			$classes = array_map( 'esc_attr', $classes );

			return $classes;
		};

		$add_dropdown_icon = function( $title, $menu_item ) use ( $sub_menu_type ) {
			$mega_menu      = get_post_meta( $menu_item->ID, '_gb_mega_menu', true );
			$has_children   = in_array( 'menu-item-has-children', $menu_item->classes, true );
			$show_mega_menu = $mega_menu && self::should_display_mega_menu( $mega_menu );

			if ( $show_mega_menu || $has_children ) {
				$modal_id       = $show_mega_menu ? 'gb-overlay-' . $mega_menu : '';
				$arrow_icon     = '<svg class="gb-submenu-toggle-icon" viewBox="0 0 330 512" aria-hidden="true" width="1em" height="1em" fill="currentColor"><path d="M305.913 197.085c0 2.266-1.133 4.815-2.833 6.514L171.087 335.593c-1.7 1.7-4.249 2.832-6.515 2.832s-4.815-1.133-6.515-2.832L26.064 203.599c-1.7-1.7-2.832-4.248-2.832-6.514s1.132-4.816 2.832-6.515l14.162-14.163c1.7-1.699 3.966-2.832 6.515-2.832 2.266 0 4.815 1.133 6.515 2.832l111.316 111.317 111.316-111.317c1.7-1.699 4.249-2.832 6.515-2.832s4.815 1.133 6.515 2.832l14.162 14.163c1.7 1.7 2.833 4.249 2.833 6.515z"></path></svg>';

				$submenu_button = sprintf(
					'<span class="gb-submenu-toggle" aria-label="%3$s" role="button" aria-expanded="false" aria-haspopup="menu" tabindex="0"%2$s>%1$s</span>',
					$arrow_icon,
					$modal_id ? ' data-gb-overlay="' . esc_attr( $modal_id ) . '" data-gb-overlay-trigger-type="click" aria-controls="' . esc_attr( $modal_id ) . '"' : '',
					sprintf(
						/* translators: %s: Menu item title. */
						esc_attr__( '%s Sub-Menu', 'generateblocks-pro' ),
						esc_attr( wp_strip_all_tags( $title ) )
					)
				);

				if ( 'click' === $sub_menu_type ) {
					return $title . $arrow_icon;
				}

				return $title . $submenu_button;
			}

			return $title;
		};

		$add_link_atts = function ( $atts, $menu_item ) use ( $sub_menu_type ) {
			$class         = $atts['class'] ?? '';
			$class         .= ' gb-menu-link';
			$class         = trim( $class );
			$atts['class'] = esc_attr( $class );
			$disable_links = isset( $_GET['disableLinks'] ); // phpcs:ignore -- No processing of data.

			if ( $disable_links ) {
				$atts['onClick'] = 'event.preventDefault();';
			}

			if ( 'hover' === $sub_menu_type || 'click' === $sub_menu_type ) {
				$mega_menu = get_post_meta( $menu_item->ID, '_gb_mega_menu', true );
				$show_mega_menu = $mega_menu && self::should_display_mega_menu( $mega_menu );

				if ( $show_mega_menu ) {
					$modal_id = 'gb-overlay-' . $mega_menu;

					// Add directive and modal element target.
					$atts['data-gb-overlay'] = $modal_id;

					if ( 'hover' === $sub_menu_type ) {
						$atts['data-gb-overlay-trigger-type'] = 'hover';
					}

					$atts['aria-controls'] = $modal_id;
				}

				if ( $show_mega_menu || in_array( 'menu-item-has-children', $menu_item->classes, true ) ) {
					if ( 'click' === $sub_menu_type ) {
						$atts['role']          = 'button';
						$atts['aria-expanded'] = 'false';
						$atts['aria-label']    = sprintf(
							/* translators: %s: Menu item title. */
							esc_attr__( '%s Sub-Menu', 'generateblocks-pro' ),
							esc_attr( wp_strip_all_tags( $menu_item->title ?? '' ) )
						);
					}

					$atts['aria-haspopup'] = 'menu';
				}
			}

			return $atts;
		};

		$add_sub_menu_attributes = function( $atts, $args ) use ( $unique_id ) {
			$new_unique_id = substr_replace( $unique_id, 'sm', 0, 2 );
			$atts['class'] = 'sub-menu gb-sub-menu gb-sub-menu-' . $new_unique_id;

			return $atts;
		};

		$add_mega_menu = function( $item_output, $item ) {
			$item_id = $item->ID;
			$mega_menu = get_post_meta( $item_id, '_gb_mega_menu', true );

			if ( ! $mega_menu || ! self::should_display_mega_menu( $mega_menu ) ) {
				return $item_output;
			}

			$action_id = 'gb-mega-menu-' . $mega_menu;
			ob_start();
			do_action( $action_id, $item );
			$mega_menu_content = ob_get_clean();
			return $item_output . $mega_menu_content;
		};

		add_filter( 'nav_menu_css_class', $add_menu_item_classes, 10, 2 );
		add_filter( 'nav_menu_submenu_attributes', $add_sub_menu_attributes, 10, 2 );
		add_filter( 'nav_menu_item_title', $add_dropdown_icon, 10, 2 );
		add_filter( 'nav_menu_link_attributes', $add_link_atts, 10, 2 );
		add_filter( 'walker_nav_menu_start_el', $add_mega_menu, 10, 2 );

		ob_start();

		wp_nav_menu(
			[
				'menu'            => $selected_menu,
				'container'       => '',
				'container_class' => '',
				'menu_class'      => $class,
				'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
				'fallback_cb'     => false,
			]
		);

		remove_filter( 'nav_menu_css_class', $add_menu_item_classes, 10, 2 );
		remove_filter( 'nav_menu_submenu_attributes', $add_sub_menu_attributes, 10, 2 );
		remove_filter( 'nav_menu_item_title', $add_dropdown_icon, 10, 2 );
		remove_filter( 'nav_menu_link_attributes', $add_link_atts, 10, 2 );
		remove_filter( 'walker_nav_menu_start_el', $add_mega_menu, 10, 2 );

		do_action( 'generateblocks_pro_after_menu_block', $attributes, is_admin() );

		$block_content .= ob_get_clean();

		if ( ! wp_style_is( 'generateblocks-classic-menu', 'enqueued' ) ) {
			self::enqueue_style();
		}

		if ( ! wp_script_is( 'generateblocks-classic-menu', 'enqueued' ) ) {
			self::enqueue_scripts();
		}

		return $block_content;
	}

	/**
	 * Enqueue block styles.
	 */
	private static function enqueue_style() {
		wp_enqueue_style(
			'generateblocks-classic-menu',
			GENERATEBLOCKS_PRO_DIR_URL . 'dist/classic-menu-style.css',
			[],
			GENERATEBLOCKS_PRO_VERSION
		);
	}

	/**
	 * Enqueue block scripts.
	 */
	private static function enqueue_scripts() {
		wp_enqueue_script(
			'generateblocks-classic-menu',
			GENERATEBLOCKS_PRO_DIR_URL . 'dist/classic-menu.js',
			[],
			GENERATEBLOCKS_PRO_VERSION,
			true
		);
	}

	/**
	 * Enqueue block assets.
	 */
	public static function enqueue_assets() {
		self::enqueue_scripts();
		self::enqueue_style();
	}
}
