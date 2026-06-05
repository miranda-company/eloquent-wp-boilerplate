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
 * Class GeneratePress_Pro_Site_Template
 */
class GenerateBlocks_Pro_Overlay {
	/**
	 * All found templates.
	 *
	 * @since 2.5.0
	 * @var boolean If this post has a parent.
	 */
	public $templates = [];

	/**
	 * Instance.
	 *
	 * @access private
	 * @var object Instance
	 */
	private static $instance = null;

	/**
	 * Constructor.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Try to add active templates.
	 *
	 * @since 2.5.0
	 *
	 * @param int $post_id The post ID.
	 */
	public function add_overlay( $post_id ) {
		if ( ! $post_id ) {
			return;
		}

		$display_condition  = get_post_meta( $post_id, '_gb_overlay_display_condition', true );
		$display_conditions = [];

		if ( $display_condition ) {
			// Check if the condition post exists and is published.
			$condition_post = get_post( $display_condition );

			if ( $condition_post && 'publish' === $condition_post->post_status ) {
				$display_conditions = get_post_meta( $display_condition, '_gb_conditions', true ) ?? [];
			}
		}

		$current_post_id = get_the_ID();

		if ( ! $current_post_id && is_admin() ) {
			$current_post_id = $_GET['post'] ?? null; // phpcs:ignore -- Input var okay.
		}

		$show = true;

		if ( ! empty( $display_conditions ) ) {
			$show             = GenerateBlocks_Pro_Conditions::show( $display_conditions );
			$invert_condition = GenerateBlocks_Pro_Overlays::get_overlay_meta( $post_id, '_gb_overlay_display_condition_invert' );

			// If invert is enabled, flip the result.
			if ( $invert_condition ) {
				$show = ! $show;
			}
		}

		if ( $show ) {
			$this->templates[] = [
				'post_id'    => $post_id,
				'conditions' => $display_conditions,
			];
		}
	}

	/**
	 * Initialize the templates.
	 *
	 * @return void
	 */
	public function init() {
		if ( empty( $this->templates ) ) {
			return;
		}

		$overlays = $this->templates;

		if ( ! empty( $overlays ) ) {
			wp_enqueue_script(
				'generateblocks-overlay',
				GENERATEBLOCKS_PRO_DIR_URL . 'dist/overlay.js',
				[],
				GENERATEBLOCKS_PRO_VERSION,
				true
			);

			wp_enqueue_style(
				'generateblocks-overlay',
				GENERATEBLOCKS_PRO_DIR_URL . 'dist/overlay.css',
				[],
				GENERATEBLOCKS_PRO_VERSION
			);

			foreach ( $overlays as $overlay ) {
				$custom_part_template = get_post( $overlay['post_id'] );
				$custom_part_output   = $this->get_overlay( $custom_part_template );

				if ( empty( $overlay['conditions'] ) ) {
					$this->add_generateblocks_content( $custom_part_template );
				} else {
					$dynamic_css_priority = apply_filters( 'generateblocks_dynamic_css_priority', 25 );
					$inline_css_priority  = is_numeric( $dynamic_css_priority ) ? $dynamic_css_priority + 10 : 999;

					add_action(
						'wp_enqueue_scripts',
						function() use ( $custom_part_template ) {
							if ( function_exists( 'generateblocks_pro_get_css_from_content' ) ) {
								$overlay_css = generateblocks_pro_get_css_from_content( $custom_part_template->post_content );

								if ( $overlay_css ) {
									wp_add_inline_style( 'generateblocks', $overlay_css );
								}
							}
						},
						$inline_css_priority
					);
				}

				$this->add_overlay_to_memory( 'overlay', $overlay['post_id'] );

				// Get all meta at once (more readable, same performance).
				$meta = GenerateBlocks_Pro_Overlays::get_all_overlay_meta( $overlay['post_id'] );

				$overlay_output = function() use ( $custom_part_output, $overlay, $meta ) {
					$overlay_content = $custom_part_output;
					$overlay_id      = $overlay['post_id'] ? 'gb-overlay-' . $overlay['post_id'] : wp_unique_id( 'gb-overlay-' );

					// Extract what we need.
					$overlay_type       = $meta['_gb_overlay_type'];
					$trigger_type       = $meta['_gb_overlay_trigger_type'];
					$backdrop           = $meta['_gb_overlay_backdrop'];
					$backdrop_color     = $meta['_gb_overlay_backdrop_color'];
					$backdrop_blur      = $meta['_gb_overlay_backdrop_blur'];
					$placement          = $meta['_gb_overlay_placement'];
					$animation_in       = $meta['_gb_overlay_animation_in'];
					$animation_out      = $meta['_gb_overlay_animation_out'];
					$animation_duration = $meta['_gb_overlay_animation_duration'];
					$animation_target   = $meta['_gb_overlay_animation_target'];
					$animation_distance = $meta['_gb_overlay_animation_distance'];
					$custom_event       = $meta['_gb_overlay_custom_event'];
					$hide_if_cookies_disabled = $meta['_gb_overlay_hide_if_cookies_disabled'];
					$position_to_parent = $meta['_gb_overlay_position_to_parent'];
					$hover_buffer = $meta['_gb_overlay_hover_buffer'];

					// Conditional meta based on overlay type.
					$scroll_percent     = 'standard' === $overlay_type ? $meta['_gb_overlay_scroll_percent'] : false;
					$time_delay         = 'standard' === $overlay_type ? $meta['_gb_overlay_time_delay'] : false;
					$allow_cookie       = 'standard' === $overlay_type && ( 'exit-intent' === $trigger_type || 'time' === $trigger_type || 'scroll' === $trigger_type || 'custom' === $trigger_type );
					$cookie_duration    = $allow_cookie ? $meta['_gb_overlay_cookie_duration'] : false;

					// Standard overlay specific options.
					$close_on_esc           = 'standard' === $overlay_type ? $meta['_gb_overlay_close_on_esc'] : true;
					$close_on_click_outside = 'standard' === $overlay_type ? $meta['_gb_overlay_close_on_click_outside'] : true;
					$disable_page_scroll    = 'standard' === $overlay_type ? $meta['_gb_overlay_disable_page_scroll'] : false;
					$position               = 'standard' === $overlay_type ? $meta['_gb_overlay_position'] : '';

					// Width mode applies to all overlay types.
					$width_mode = $meta['_gb_overlay_width_mode'];

					// Add a filter to allow lazy load for specific overlays.
					$should_allow_lazy_load = apply_filters(
						'generateblocks_pro_overlay_allow_lazy_load',
						false,
						$overlay['post_id'],
						$meta
					);

					if ( ! $should_allow_lazy_load && ( 'anchored' === $overlay_type || 'mega-menu' === $overlay_type ) ) {
						// Use WP_HTML_Tag_Processor to add loading attributes to images.
						if ( class_exists( 'WP_HTML_Tag_Processor' ) ) {
							$processor = new WP_HTML_Tag_Processor( $overlay_content );

							// Process all img tags.
							while ( $processor->next_tag( 'img' ) ) {
								// Add fetchpriority if not present.
								if ( null === $processor->get_attribute( 'fetchpriority' ) ) {
									$processor->set_attribute( 'fetchpriority', 'high' );
								}

								// Set loading to eager to prevent lazy loading.
								$processor->set_attribute( 'loading', 'eager' );
							}

							$overlay_content = $processor->get_updated_html();
						}
					}

					ob_start();
					include plugin_dir_path( __FILE__ ) . 'overlay-template.php';
					$overlay_output = ob_get_clean();
					echo $overlay_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output safe.
				};

				$overlay_type = $meta['_gb_overlay_type'];

				if ( 'mega-menu' === $overlay_type ) {
					$action_id = 'gb-mega-menu-' . $overlay['post_id'];
					add_action( $action_id, $overlay_output );
				} else {
					add_action( 'wp_footer', $overlay_output );
				}
			}
		}
	}

	/**
	 * Get the template content.
	 *
	 * @since 2.5.0
	 *
	 * @param object $template The template object.
	 */
	public function get_overlay( $template ) {
		if ( ! function_exists( 'do_blocks' ) ) {
			return;
		}

		if ( ! $template || 'gblocks_overlay' !== $template->post_type ) {
			return '';
		}

		if ( 'publish' !== $template->post_status || ! empty( $template->post_password ) ) {
			return '';
		}

		$template_content = $template->post_content;

		// Handle embeds for block elements.
		global $wp_embed;

		if ( is_object( $wp_embed ) && method_exists( $wp_embed, 'autoembed' ) ) {
			$template_content = $wp_embed->autoembed( $template_content );
		}

		return apply_filters(
			'generateblocks_overlay_panel',
			do_blocks( $template_content ),
			$template->ID ?? null
		);
	}

	/**
	 * Add the template content to the generateblocks_do_content filter.
	 *
	 * @since 2.5.0
	 *
	 * @param object $template The template object.
	 */
	private function add_generateblocks_content( $template ) {
		add_filter(
			'generateblocks_do_content',
			function( $content ) use ( $template ) {
				return $content . $template->post_content;
			}
		);
	}

	/**
	 * Add a template to the memory.
	 *
	 * @since 2.5.0
	 *
	 * @param string $type The template type.
	 * @param int    $id The template ID.
	 */
	private function add_overlay_to_memory( $type, $id ) {
		global $gb_overlays;

		$gb_overlays[ $id ] = array(
			'type' => $type,
			'id' => $id,
		);
	}
}
