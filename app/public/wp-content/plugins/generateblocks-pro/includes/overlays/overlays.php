<?php
/**
 * The main site editor module file.
 *
 * @package GeneratePress Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access, please.
}

$overlays_dir = plugin_dir_path( __FILE__ );
require_once $overlays_dir . 'class-overlays.php';
require_once $overlays_dir . 'class-overlay.php';
require_once $overlays_dir . 'class-overlay-post-type.php';
require_once $overlays_dir . 'class-overlay-iframe-context.php';
require_once $overlays_dir . 'class-overlay-rest.php';

/**
 * Get our templates.
 *
 * @param array $custom_args Custom arguments for the query.
 */
function generateblocks_pro_get_overlays( $custom_args = [] ) {
	// Return empty array if overlays are disabled.
	if ( ! generateblocks_pro_overlays_enabled() ) {
		return array();
	}

	$args = array(
		'post_type'        => 'gblocks_overlay',
		'no_found_rows'    => true,
		'post_status'      => 'publish',
		'numberposts'      => 500, // phpcs:ignore
		'fields'           => 'ids',
		'suppress_filters' => false,
		'order'            => 'ASC',
	);

	$args = array_merge( $args, $custom_args );

	// Prevent Polylang from altering the query.
	if ( function_exists( 'pll_get_post_language' ) ) {
		$args['lang'] = '';
	}

	$posts = get_posts( $args );

	return $posts;
}

/**
 * Clear all overlay transient caches.
 *
 * @since 1.0.0
 */
function generateblocks_pro_clear_overlay_cache() {
	delete_option( 'generateblocks_active_overlays' );
}

add_action( 'wp', 'generateblocks_pro_do_overlays' );
/**
 * Execute our Overlay Panels.
 *
 * @since 2.3.0
 */
function generateblocks_pro_do_overlays() {
	// Don't execute overlays if the feature is disabled.
	if ( ! generateblocks_pro_overlays_enabled() ) {
		return;
	}

	$cached_overlays = get_option( 'generateblocks_active_overlays', false );

	if ( false !== $cached_overlays ) {
		$posts = $cached_overlays;
	} else {
		$posts = generateblocks_pro_get_overlays();
		update_option( 'generateblocks_active_overlays', $posts );
	}

	$instance = GenerateBlocks_Pro_Overlay::get_instance();

	foreach ( $posts as $post_id ) {
		$post_id = apply_filters( 'generateblocks_overlay_post_id', $post_id );
		$instance->add_overlay( $post_id );
	}

	// Get all of the active templates.
	$instance->init();
}

add_filter( 'generateblocks_overlay_panel', 'generateblocks_pro_overlay_content_filters' );
/**
 * Apply content filters to our overlay panels.
 *
 * @since 2.3.0
 * @param string $content The overlay panel content.
 */
function generateblocks_pro_overlay_content_filters( $content ) {
	$content = shortcode_unautop( $content );
	$content = do_shortcode( $content );

	if ( function_exists( 'wp_filter_content_tags' ) ) {
		$content = wp_filter_content_tags( $content );
	} elseif ( function_exists( 'wp_make_content_images_responsive' ) ) {
		$content = wp_make_content_images_responsive( $content );
	}

	return $content;
}
