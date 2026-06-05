<?php
/**
 * Modal Iframe Context Handler
 *
 * Handles enqueuing the iframe context script and adding modal context detection.
 *
 * @package GenerateBlocks Pro
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GenerateBlocks_Pro_Overlay_Iframe_Context
 *
 * This class manages the context for modals when they are opened in an iframe.
 * It enqueues the necessary scripts and styles, adds body classes, and provides utility methods
 * for checking modal context and generating modal edit URLs.
 */
class GenerateBlocks_Pro_Overlay_Iframe_Context {

	/**
	 * Initialize the class.
	 */
	public function __construct() {
		// Don't initialize if overlays are disabled.
		if ( ! generateblocks_pro_overlays_enabled() ) {
			return;
		}

		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_iframe_context_script' ) );
	}

	/**
	 * Enqueue iframe context script when editing overlays.
	 */
	public function enqueue_iframe_context_script() {
		// Only in admin area.
		if ( ! is_admin() ) {
			return;
		}

		// Check if we're on a post edit screen.
		$current_screen = get_current_screen();
		if ( ! $current_screen || ! in_array( $current_screen->base, array( 'post', 'post-new' ), true ) ) {
			return;
		}

		// Check if we're editing a overlay.
		global $post;
		if ( ! $post || 'gblocks_overlay' !== $post->post_type ) {
			return;
		}

		// Check if we're in overlay iframe context.
		if ( isset( $_GET['gb_overlay_context'] ) && '1' === $_GET['gb_overlay_context'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$assets = generateblocks_pro_get_enqueue_assets( 'gb-overlay-iframe-context' );
			// Enqueue the iframe context script.
			wp_enqueue_script(
				'gb-overlay-iframe-context',
				GENERATEBLOCKS_PRO_DIR_URL . 'dist/overlay-iframe-context.js', // Use built file.
				$assets['dependencies'],
				$assets['version'],
				true
			);

			// Enqueue the iframe context styles.
			wp_enqueue_style(
				'gb-overlay-iframe-context',
				GENERATEBLOCKS_PRO_DIR_URL . 'dist/overlay-iframe-context.css',
				array(),
				filemtime( GENERATEBLOCKS_PRO_DIR . 'dist/overlay-iframe-context.css' ) // Use built file.
			);

			// Add admin body class for overlay context.
			add_filter( 'admin_body_class', array( $this, 'add_overlay_context_body_class' ) );
		}
	}

	/**
	 * Add overlay context body class.
	 *
	 * @param string $classes Existing body classes.
	 * @return string Modified body classes.
	 */
	public function add_overlay_context_body_class( $classes ) {
		return $classes . ' gb-overlay-context';
	}
}

// Initialize the class.
new GenerateBlocks_Pro_Overlay_Iframe_Context();
