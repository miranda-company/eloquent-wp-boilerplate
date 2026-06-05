<?php
/**
 * Warn admins if GenerateBlocks is missing.
 **/
function llummio_blueprint_generateblocks_notice() {
	// Only show this to users who can actually manage plugins.
	if ( ! is_admin() || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	// Make sure the plugin functions are available.
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	// GenerateBlocks main plugin file.
	$generateblocks_plugin = 'generateblocks/plugin.php';

	// Stop here if GenerateBlocks is already active.
	if ( is_plugin_active( $generateblocks_plugin ) ) {
		return;
	}

	$message = sprintf(
		__(
			'This theme requires <strong>GenerateBlocks</strong> to render the header and footer correctly. Please install and activate it from the <a href="%s">Plugins screen</a>.',
			'llummio-blueprint'
		),
		esc_url( admin_url( 'plugins.php' ) )
	);

	printf(
		'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
		wp_kses_post( $message )
	);
}

add_action( 'admin_notices', 'llummio_blueprint_generateblocks_notice' );

/**
 * Warn blueprint editors when block template changes are still stored in the database.
 */
function llummio_blueprint_editor_changes_notice() {
	if ( ! is_admin() || ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	if ( function_exists( 'wp_get_environment_type' ) && 'local' !== wp_get_environment_type() ) {
		return;
	}

	if ( ! function_exists( 'get_block_templates' ) ) {
		return;
	}

	$custom_templates      = llummio_blueprint_count_custom_block_templates( 'wp_template' );
	$custom_template_parts = llummio_blueprint_count_custom_block_templates( 'wp_template_part' );
	$total_customizations  = $custom_templates + $custom_template_parts;

	if ( 0 === $total_customizations ) {
		return;
	}

	$message = sprintf(
		__(
			'This blueprint has %1$d template change(s) saved in the database. Before committing, open the <a href="%2$s">Site Editor</a> and use <strong>Create Block Theme > Save Changes to Theme</strong> so header, footer, pattern, and style changes are written to the theme files.',
			'llummio-blueprint'
		),
		(int) $total_customizations,
		esc_url( admin_url( 'site-editor.php' ) )
	);

	printf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		wp_kses_post( $message )
	);
}

add_action( 'admin_notices', 'llummio_blueprint_editor_changes_notice' );

/**
 * Count user-edited block templates or template parts for the active theme.
 *
 * @param string $template_type Either wp_template or wp_template_part.
 */
function llummio_blueprint_count_custom_block_templates( $template_type ) {
	$count     = 0;
	$templates = get_block_templates( array(), $template_type );

	foreach ( $templates as $template ) {
		if ( isset( $template->source ) && 'custom' === $template->source ) {
			$count++;
		}
	}

	return $count;
}


/**
 * Enqueue styles
 **/
function llummio_blueprint_enqueue_styles() {
	$version = wp_get_theme()->get( 'Version' );

	// Enqueue the main stylesheet.
	wp_enqueue_style(
		'llummio-blueprint-style',
		get_stylesheet_uri(),
		array(),
		$version
	);
}

add_action( 'wp_enqueue_scripts', 'llummio_blueprint_enqueue_styles' );


/**
 * Loads child theme style into the WP editor
 **/
function llummio_blueprint_add_editor_style() {
	add_editor_style( 'style.css' );
}

add_action( 'after_setup_theme', 'llummio_blueprint_add_editor_style' );

/**
 * Add animation files (js)
 */
function llummio_blueprint_enqueue_page_scripts() {
	$version = wp_get_theme()->get( 'Version' );

	// GSAP core
	wp_enqueue_script(
		'gsap-js',
		get_theme_file_uri( 'assets/vendor/gsap/gsap.min.js' ),
		array(),
		filemtime( get_theme_file_path( 'assets/vendor/gsap/gsap.min.js' ) ),
		true
	);

	// GSAP ScrollTrigger
	wp_enqueue_script(
		'gsap-scrolltrigger',
		get_theme_file_uri( 'assets/vendor/gsap/ScrollTrigger.min.js' ),
		array( 'gsap-js' ),
		filemtime( get_theme_file_path( 'assets/vendor/gsap/ScrollTrigger.min.js' ) ),
		true
	);

	// Generic animations
	wp_enqueue_script(
		'llummio-blueprint-generic',
		get_theme_file_uri( 'assets/js/generic.js' ),
		array( 'gsap-js', 'gsap-scrolltrigger' ),
		filemtime( get_theme_file_path( 'assets/js/generic.js' ) ),
		true
	);

	// Homepage-specific animations
	if ( is_front_page() ) {
		wp_enqueue_script(
			'llummio-blueprint-homepage',
			get_theme_file_uri( 'assets/js/homepage.js' ),
			array( 'gsap-js', 'gsap-scrolltrigger', 'llummio-blueprint-generic' ),
			filemtime( get_theme_file_path( 'assets/js/homepage.js' ) ),
			true
		);
	}
}

add_action( 'wp_enqueue_scripts', 'llummio_blueprint_enqueue_page_scripts' );

/**
 * Add classic navigation menu support to the theme (GenerateBlocks can be used to create the header and footer, but this allows users to use the built-in menu system if they prefer).
 **/
add_action( 'after_setup_theme', function() {
	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'llummio-blueprint' ),
			'footer'  => __( 'Footer Menu', 'llummio-blueprint' ),
		)
	);
} );
