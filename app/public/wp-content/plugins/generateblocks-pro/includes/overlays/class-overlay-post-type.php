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
 * Class GeneratePress_Pro_Template_Post_Type
 */
class GenerateBlocks_Pro_Overlay_Post_Type extends GenerateBlocks_Pro_Singleton {
	/**
	 * Initialize class.
	 */
	public function init() {
		// Don't initialize if overlays are disabled.
		if ( ! generateblocks_pro_overlays_enabled() ) {
			return;
		}

		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'init', [ $this, 'register_taxonomy' ] );
		add_action( 'admin_init', [ $this, 'redirect_admin_view' ] );
	}

	/**
	 * Register our custom post type.
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => _x( 'Overlay Panels', 'post type general name', 'generateblocks-pro' ),
			'singular_name'      => _x( 'Overlay Panel', 'post type singular name', 'generateblocks-pro' ),
			'menu_name'          => _x( 'Overlay Panels', 'admin menu', 'generateblocks-pro' ),
			'name_admin_bar'     => _x( 'Overlay Panel', 'add new on admin bar', 'generateblocks-pro' ),
			'add_new'            => _x( 'Add New', 'overlay', 'generateblocks-pro' ),
			'add_new_item'       => __( 'Add New Overlay Panel', 'generateblocks-pro' ),
			'new_item'           => __( 'New Overlay Panel', 'generateblocks-pro' ),
			'edit_item'          => __( 'Edit Overlay Panel', 'generateblocks-pro' ),
			'view_item'          => __( 'View Overlay Panel', 'generateblocks-pro' ),
			'all_items'          => __( 'All Overlay Panels', 'generateblocks-pro' ),
			'search_items'       => __( 'Search Overlay Panels', 'generateblocks-pro' ),
			'parent_item_colon'  => __( 'Parent Overlay Panels:', 'generateblocks-pro' ),
			'not_found'          => __( 'No overlay panels found.', 'generateblocks-pro' ),
			'not_found_in_trash' => __( 'No overlay panels found in Trash.', 'generateblocks-pro' ),
		);

		// Get the capability for managing overlays.
		$manage_cap = GenerateBlocks_Pro_Overlays::get_overlays_capability( 'manage' );

		$args = array(
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'custom-fields', 'revisions' ),
			'hierarchical'        => false,
			'public'              => false,
			'publicly_queryable'  => false,
			'has_archive'         => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'exclude_from_search' => true,
			'show_in_nav_menus'   => false,
			'rewrite'             => false,
			'show_in_admin_bar'   => false,
			'show_in_rest'        => true,
			'can_export'          => true,
			'capability_type'     => 'post',
			'capabilities'        => array(
				'create_posts' => $manage_cap,
			),
			'map_meta_cap'        => true,
		);

		register_post_type( 'gblocks_overlay', $args );
	}

	/**
	 * Register taxonomy for overlays.
	 */
	public function register_taxonomy() {
		$labels = array(
			'name'              => _x( 'Categories', 'taxonomy general name', 'generateblocks-pro' ),
			'singular_name'     => _x( 'Category', 'taxonomy singular name', 'generateblocks-pro' ),
			'search_items'      => __( 'Search Categories', 'generateblocks-pro' ),
			'all_items'         => __( 'All Categories', 'generateblocks-pro' ),
			'parent_item'       => __( 'Parent Category', 'generateblocks-pro' ),
			'parent_item_colon' => __( 'Parent Category:', 'generateblocks-pro' ),
			'edit_item'         => __( 'Edit Category', 'generateblocks-pro' ),
			'update_item'       => __( 'Update Category', 'generateblocks-pro' ),
			'add_new_item'      => __( 'Add New Category', 'generateblocks-pro' ),
			'new_item_name'     => __( 'New Category Name', 'generateblocks-pro' ),
			'menu_name'         => __( 'Categories', 'generateblocks-pro' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'public'            => false,
			'show_ui'           => false,
			'show_in_menu'      => false,
			'show_admin_column' => false,
			'query_var'         => false,
			'rewrite'           => false,
			'show_in_rest'      => true,
			'rest_base'         => 'overlay-categories',
		);

		register_taxonomy( 'gblocks_overlay_cat', array( 'gblocks_overlay' ), $args );
	}

	/**
	 * Redirect to site editor when viewing site templates.
	 */
	public function redirect_admin_view() {
		global $pagenow;

		if ( 'edit.php' === $pagenow && isset( $_GET['post_type'] ) && 'gblocks_overlay' === $_GET['post_type'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_safe_redirect( admin_url( 'admin.php?page=generateblocks-overlay-panels' ) );
			exit;
		}
	}
}

GenerateBlocks_Pro_Overlay_Post_Type::get_instance()->init();
