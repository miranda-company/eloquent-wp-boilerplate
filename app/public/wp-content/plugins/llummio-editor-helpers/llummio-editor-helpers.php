<?php
/**
 * Plugin Name: Llummio Editor Helpers
 * Description: Lightweight editor helpers for Llummio blueprint sites, including SEO fields and wireframe preview controls.
 * Version: 0.1.0
 * Author: Llummio
 * Text Domain: llummio-editor-helpers
 */

defined( 'ABSPATH' ) || exit;

const LLUMMIO_EDITOR_HELPERS_TITLE_KEY       = '_llummio_seo_title';
const LLUMMIO_EDITOR_HELPERS_DESCRIPTION_KEY = '_llummio_seo_description';
const LLUMMIO_EDITOR_HELPERS_NOINDEX_KEY     = '_llummio_seo_noindex';
const LLUMMIO_EDITOR_HELPERS_NOFOLLOW_KEY    = '_llummio_seo_nofollow';
const LLUMMIO_EDITOR_HELPERS_VERSION         = '0.1.0';

/**
 * Register SEO metadata for public editable post types.
 */
function llummio_editor_helpers_register_meta() {
	foreach ( llummio_editor_helpers_post_types() as $post_type ) {
		register_post_meta(
			$post_type,
			LLUMMIO_EDITOR_HELPERS_TITLE_KEY,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => 'llummio_editor_helpers_can_edit_meta',
			)
		);

		register_post_meta(
			$post_type,
			LLUMMIO_EDITOR_HELPERS_DESCRIPTION_KEY,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_textarea_field',
				'auth_callback'     => 'llummio_editor_helpers_can_edit_meta',
			)
		);

		foreach ( array( LLUMMIO_EDITOR_HELPERS_NOINDEX_KEY, LLUMMIO_EDITOR_HELPERS_NOFOLLOW_KEY ) as $meta_key ) {
			register_post_meta(
				$post_type,
				$meta_key,
				array(
					'type'              => 'boolean',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'rest_sanitize_boolean',
					'auth_callback'     => 'llummio_editor_helpers_can_edit_meta',
				)
			);
		}
	}
}

add_action( 'init', 'llummio_editor_helpers_register_meta' );

/**
 * Load the block editor sidebar controls.
 */
function llummio_editor_helpers_enqueue_editor_assets() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen || 'post' !== $screen->base || ! in_array( $screen->post_type, llummio_editor_helpers_post_types(), true ) ) {
		return;
	}

	$script_path = plugin_dir_path( __FILE__ ) . 'assets/editor-sidebar.js';
	$style_path  = plugin_dir_path( __FILE__ ) . 'assets/editor-sidebar.css';
	$version     = file_exists( $script_path ) ? (string) filemtime( $script_path ) : LLUMMIO_EDITOR_HELPERS_VERSION;

	wp_enqueue_script(
		'llummio-editor-helpers-editor-sidebar',
		plugin_dir_url( __FILE__ ) . 'assets/editor-sidebar.js',
		array( 'wp-components', 'wp-data', 'wp-edit-post', 'wp-editor', 'wp-element', 'wp-i18n', 'wp-plugins' ),
		$version,
		true
	);

	if ( file_exists( $style_path ) ) {
		wp_enqueue_style(
			'llummio-editor-helpers-editor-sidebar',
			plugin_dir_url( __FILE__ ) . 'assets/editor-sidebar.css',
			array( 'wp-components' ),
			(string) filemtime( $style_path )
		);
	}
}

add_action( 'enqueue_block_editor_assets', 'llummio_editor_helpers_enqueue_editor_assets' );

/**
 * Return the post types where SEO fields should appear.
 */
function llummio_editor_helpers_post_types() {
	$post_types = get_post_types(
		array(
			'public'  => true,
			'show_ui' => true,
		),
		'names'
	);

	unset( $post_types['attachment'] );

	return apply_filters( 'llummio_editor_helpers_post_types', array_values( $post_types ) );
}

/**
 * Check whether the current user can edit SEO fields.
 *
 * @param bool   $allowed Whether the user can edit.
 * @param string $meta_key Meta key.
 * @param int    $post_id  Post ID.
 * @param int    $user_id  User ID.
 */
function llummio_editor_helpers_can_edit_meta( $allowed, $meta_key, $post_id, $user_id = 0 ) {
	return user_can( $user_id ? $user_id : get_current_user_id(), 'edit_post', $post_id );
}

/**
 * Remove empty SEO values after the editor saves metadata.
 *
 * @param int $post_id Post ID.
 */
function llummio_editor_helpers_delete_empty_meta( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || ! in_array( get_post_type( $post_id ), llummio_editor_helpers_post_types(), true ) ) {
		return;
	}

	foreach ( array( LLUMMIO_EDITOR_HELPERS_TITLE_KEY, LLUMMIO_EDITOR_HELPERS_DESCRIPTION_KEY ) as $meta_key ) {
		if ( '' === get_post_meta( $post_id, $meta_key, true ) ) {
			delete_post_meta( $post_id, $meta_key );
		}
	}

	foreach ( array( LLUMMIO_EDITOR_HELPERS_NOINDEX_KEY, LLUMMIO_EDITOR_HELPERS_NOFOLLOW_KEY ) as $meta_key ) {
		if ( ! llummio_editor_helpers_get_boolean_meta( $post_id, $meta_key ) ) {
			delete_post_meta( $post_id, $meta_key );
		}
	}
}

add_action( 'save_post', 'llummio_editor_helpers_delete_empty_meta', 20 );

/**
 * Make sure themes can use WordPress-managed document titles.
 */
function llummio_editor_helpers_add_title_support() {
	add_theme_support( 'title-tag' );
}

add_action( 'after_setup_theme', 'llummio_editor_helpers_add_title_support' );

/**
 * Override the frontend document title for singular content.
 *
 * @param string $title Existing document title.
 */
function llummio_editor_helpers_document_title( $title ) {
	if ( llummio_editor_helpers_should_skip_frontend_output() || ! is_singular() ) {
		return $title;
	}

	$seo_title = llummio_editor_helpers_get_meta( get_queried_object_id(), LLUMMIO_EDITOR_HELPERS_TITLE_KEY );

	return '' !== $seo_title ? llummio_editor_helpers_replace_title_tokens( $seo_title ) : $title;
}

add_filter( 'pre_get_document_title', 'llummio_editor_helpers_document_title' );

/**
 * Replace supported SEO title tokens.
 *
 * @param string $title SEO title.
 */
function llummio_editor_helpers_replace_title_tokens( $title ) {
	$separator = apply_filters( 'document_title_separator', '-' );
	$site_name = get_bloginfo( 'name' );

	$title = str_replace(
		array( '%sep%', '%sitename%' ),
		array( $separator, $site_name ),
		$title
	);

	return trim( preg_replace( '/\s+/', ' ', $title ) );
}

/**
 * Output the frontend meta description for singular content.
 */
function llummio_editor_helpers_meta_description() {
	if ( llummio_editor_helpers_should_skip_frontend_output() || ! is_singular() ) {
		return;
	}

	$description = llummio_editor_helpers_get_meta( get_queried_object_id(), LLUMMIO_EDITOR_HELPERS_DESCRIPTION_KEY );

	if ( '' === $description ) {
		return;
	}

	printf(
		'<meta name="description" content="%s" />' . "\n",
		esc_attr( $description )
	);
}

add_action( 'wp_head', 'llummio_editor_helpers_meta_description', 1 );

/**
 * Output page-level robots controls for singular content.
 */
function llummio_editor_helpers_robots_meta() {
	if ( llummio_editor_helpers_should_skip_frontend_output() || ! is_singular() ) {
		return;
	}

	$post_id = get_queried_object_id();
	$rules   = array();

	if ( llummio_editor_helpers_get_boolean_meta( $post_id, LLUMMIO_EDITOR_HELPERS_NOINDEX_KEY ) ) {
		$rules[] = 'noindex';
	}

	if ( llummio_editor_helpers_get_boolean_meta( $post_id, LLUMMIO_EDITOR_HELPERS_NOFOLLOW_KEY ) ) {
		$rules[] = 'nofollow';
	}

	if ( empty( $rules ) ) {
		return;
	}

	printf(
		'<meta name="robots" content="%s" />' . "\n",
		esc_attr( implode( ', ', $rules ) )
	);
}

add_action( 'wp_head', 'llummio_editor_helpers_robots_meta', 1 );

/**
 * Return a trimmed SEO field value.
 *
 * @param int    $post_id  Post ID.
 * @param string $meta_key Meta key.
 */
function llummio_editor_helpers_get_meta( $post_id, $meta_key ) {
	if ( ! $post_id ) {
		return '';
	}

	return trim( (string) get_post_meta( $post_id, $meta_key, true ) );
}

/**
 * Return a boolean SEO field value.
 *
 * @param int    $post_id  Post ID.
 * @param string $meta_key Meta key.
 */
function llummio_editor_helpers_get_boolean_meta( $post_id, $meta_key ) {
	if ( ! $post_id ) {
		return false;
	}

	return (bool) get_post_meta( $post_id, $meta_key, true );
}

/**
 * Avoid duplicate frontend SEO output when a full SEO plugin is active.
 */
function llummio_editor_helpers_should_skip_frontend_output() {
	$known_seo_plugin_active = defined( 'RANK_MATH_VERSION' ) || defined( 'WPSEO_VERSION' ) || defined( 'AIOSEO_VERSION' );

	return (bool) apply_filters( 'llummio_editor_helpers_skip_frontend_output', $known_seo_plugin_active );
}
