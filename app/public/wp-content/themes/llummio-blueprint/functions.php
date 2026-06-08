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
 * Render a committed blueprint logo until a site-specific logo is configured.
 *
 * The header and footer use the core Site Logo block so client projects can
 * replace the logo from Appearance > Editor without editing theme files.
 *
 * @param string $block_content Rendered site-logo block markup.
 * @param array  $block         Parsed block data.
 */
function llummio_blueprint_site_logo_fallback( $block_content, $block ) {
	if ( ! empty( trim( $block_content ) ) || has_custom_logo() ) {
		return $block_content;
	}

	$logo_path = get_theme_file_path( 'assets/images/logo-llummio.svg' );

	if ( ! file_exists( $logo_path ) ) {
		return $block_content;
	}

	$logo_url  = get_theme_file_uri( 'assets/images/logo-llummio.svg' );
	$site_name = get_bloginfo( 'name' );
	$width     = isset( $block['attrs']['width'] ) ? absint( $block['attrs']['width'] ) : 112;

	if ( 0 === $width ) {
		$width = 112;
	}

	$image = sprintf(
		'<img class="custom-logo" src="%1$s" alt="%2$s" width="%3$d" style="height:auto;" decoding="async" />',
		esc_url( $logo_url ),
		esc_attr( sprintf( __( '%s logo', 'llummio-blueprint' ), $site_name ) ),
		(int) $width
	);

	$link = sprintf(
		'<a href="%1$s" class="custom-logo-link" rel="home">%2$s</a>',
		esc_url( home_url( '/' ) ),
		$image
	);

	return sprintf(
		'<div class="wp-block-site-logo">%s</div>',
		$link
	);
}

add_filter( 'render_block_core/site-logo', 'llummio_blueprint_site_logo_fallback', 10, 2 );

/**
 * Disable WordPress comments for lean service-business sites.
 */
function llummio_blueprint_disable_comments_support() {
	foreach ( get_post_types() as $post_type ) {
		remove_post_type_support( $post_type, 'comments' );
		remove_post_type_support( $post_type, 'trackbacks' );
	}
}

add_action( 'init', 'llummio_blueprint_disable_comments_support', 100 );

/**
 * Keep comments and pingbacks closed on the front end.
 *
 * @param bool $open Whether comments or pingbacks are open.
 */
function llummio_blueprint_close_comments( $open ) {
	return false;
}

add_filter( 'comments_open', 'llummio_blueprint_close_comments', 20 );
add_filter( 'pings_open', 'llummio_blueprint_close_comments', 20 );

/**
 * Store new or updated content with comments and pingbacks closed.
 *
 * @param array $data Sanitized post data before it is saved.
 */
function llummio_blueprint_force_comments_closed_on_save( $data ) {
	if ( ! empty( $data['post_type'] ) ) {
		$data['comment_status'] = 'closed';
		$data['ping_status']     = 'closed';
	}

	return $data;
}

add_filter( 'wp_insert_post_data', 'llummio_blueprint_force_comments_closed_on_save', 20 );

/**
 * Remove comment management surfaces from the admin area.
 */
function llummio_blueprint_hide_comments_admin_menu() {
	remove_menu_page( 'edit-comments.php' );
}

add_action( 'admin_menu', 'llummio_blueprint_hide_comments_admin_menu', 999 );

/**
 * Redirect direct visits to the comments admin screen.
 */
function llummio_blueprint_redirect_comments_admin_screen() {
	global $pagenow;

	if ( 'edit-comments.php' === $pagenow ) {
		wp_safe_redirect( admin_url() );
		exit;
	}
}

add_action( 'admin_init', 'llummio_blueprint_redirect_comments_admin_screen' );

/**
 * Remove the comments shortcut from the admin toolbar.
 *
 * @param WP_Admin_Bar $wp_admin_bar Admin toolbar instance.
 */
function llummio_blueprint_remove_comments_admin_bar_node( $wp_admin_bar ) {
	$wp_admin_bar->remove_node( 'comments' );
}

add_action( 'admin_bar_menu', 'llummio_blueprint_remove_comments_admin_bar_node', 999 );

/**
 * Remove the comments column from post and page list tables.
 *
 * @param array $columns Admin list table columns.
 */
function llummio_blueprint_remove_comments_column( $columns ) {
	unset( $columns['comments'] );

	return $columns;
}

add_filter( 'manage_posts_columns', 'llummio_blueprint_remove_comments_column' );
add_filter( 'manage_pages_columns', 'llummio_blueprint_remove_comments_column' );


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
 * Add theme scripts.
 */
function llummio_blueprint_enqueue_page_scripts() {
	wp_enqueue_script(
		'llummio-blueprint-generic',
		get_theme_file_uri( 'assets/js/generic.js' ),
		array(),
		filemtime( get_theme_file_path( 'assets/js/generic.js' ) ),
		true
	);

	if ( ! llummio_blueprint_page_needs_gsap() ) {
		return;
	}

	wp_enqueue_script(
		'gsap-js',
		get_theme_file_uri( 'assets/vendor/gsap/gsap.min.js' ),
		array(),
		filemtime( get_theme_file_path( 'assets/vendor/gsap/gsap.min.js' ) ),
		true
	);

	$animation_dependencies = array( 'gsap-js' );

	if ( llummio_blueprint_page_needs_scrolltrigger() ) {
		$scrolltrigger_path = get_theme_file_path( 'assets/vendor/gsap/ScrollTrigger.min.js' );

		if ( file_exists( $scrolltrigger_path ) ) {
			wp_enqueue_script(
				'gsap-scrolltrigger',
				get_theme_file_uri( 'assets/vendor/gsap/ScrollTrigger.min.js' ),
				array( 'gsap-js' ),
				filemtime( $scrolltrigger_path ),
				true
			);

			$animation_dependencies[] = 'gsap-scrolltrigger';
		}
	}

	if ( llummio_blueprint_page_needs_splittext() ) {
		$splittext_path = get_theme_file_path( 'assets/vendor/gsap/SplitText.min.js' );

		if ( file_exists( $splittext_path ) ) {
			wp_enqueue_script(
				'gsap-splittext',
				get_theme_file_uri( 'assets/vendor/gsap/SplitText.min.js' ),
				array( 'gsap-js' ),
				filemtime( $splittext_path ),
				true
			);

			$animation_dependencies[] = 'gsap-splittext';
		}
	}

	wp_enqueue_script(
		'llummio-blueprint-animations',
		get_theme_file_uri( 'assets/js/animations.js' ),
		$animation_dependencies,
		filemtime( get_theme_file_path( 'assets/js/animations.js' ) ),
		true
	);
}

add_action( 'wp_enqueue_scripts', 'llummio_blueprint_enqueue_page_scripts' );

/**
 * Check whether the current page opted into GSAP animations.
 */
function llummio_blueprint_page_needs_gsap() {
	if ( ! is_singular() ) {
		return false;
	}

	$post = get_queried_object();

	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	$needs_gsap = '1' === get_post_meta( $post->ID, '_llummio_load_gsap', true );

	return (bool) apply_filters(
		'llummio_blueprint_page_needs_gsap',
		$needs_gsap,
		$post
	);
}

/**
 * Check whether the current page opted into GSAP ScrollTrigger.
 */
function llummio_blueprint_page_needs_scrolltrigger() {
	if ( ! is_singular() ) {
		return false;
	}

	$post = get_queried_object();

	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	$needs_scrolltrigger = '1' === get_post_meta( $post->ID, '_llummio_load_scrolltrigger', true );

	return (bool) apply_filters(
		'llummio_blueprint_page_needs_scrolltrigger',
		$needs_scrolltrigger,
		$post
	);
}

/**
 * Check whether the current page opted into GSAP SplitText.
 */
function llummio_blueprint_page_needs_splittext() {
	if ( ! is_singular() ) {
		return false;
	}

	$post = get_queried_object();

	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	$needs_splittext = '1' === get_post_meta( $post->ID, '_llummio_load_splittext', true );

	return (bool) apply_filters(
		'llummio_blueprint_page_needs_splittext',
		$needs_splittext,
		$post
	);
}

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
