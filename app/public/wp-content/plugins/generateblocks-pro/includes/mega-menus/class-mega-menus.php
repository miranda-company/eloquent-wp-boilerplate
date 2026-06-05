<?php
/**
 * Mega Menu Handling
 *
 * @package GenerateBlocks Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The Local templates class.
 */
class GenerateBlocks_Pro_Mega_Menus extends GenerateBlocks_Pro_Singleton {
	/**
	 * Initiate class.
	 */
	public function init() {
		add_action( 'wp_nav_menu_item_custom_fields', [ $this, 'add_mega_menu_field' ], 10, 5 );
		add_action( 'wp_update_nav_menu_item', [ $this, 'mega_menu_update' ], 10, 2 );
		add_action( 'init', [ $this, 'register_post_meta' ] );
	}

	/**
	 * Register the post meta.
	 */
	public function register_post_meta() {
		register_post_meta(
			'nav_menu_item',
			'_gb_mega_menu',
			array(
				'show_in_rest' => true,
				'single'       => true,
				'type'         => 'number',
			)
		);

		register_post_meta(
			'nav_menu_item',
			'_gb_mega_menu_anchor',
			array(
				'show_in_rest' => true,
				'single'       => true,
				'type'         => 'string',
			)
		);
	}

	/**
	 * Add mega menu field to menu items.
	 *
	 * @param string        $item_id           Menu item ID as a numeric string.
	 * @param WP_Post       $menu_item         Menu item data object.
	 * @param int           $depth             Depth of menu item. Used for padding.
	 * @param stdClass|null $args              An object of menu item arguments.
	 * @param int           $current_object_id Nav menu ID.
	 */
	public function add_mega_menu_field( $item_id, $menu_item, $depth, $args, $current_object_id ) {
		ob_start();
		include GENERATEBLOCKS_PRO_DIR . 'includes/mega-menus/mega-menu-field.php';
		echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in the file
	}

	/**
	 * Save the menu item meta
	 *
	 * @param int $menu_id The menu ID.
	 * @param int $menu_item_id The menu item ID.
	 * @return mixed;
	 */
	public function mega_menu_update( $menu_id, $menu_item_id ) {

		// Verify this came from our screen and with proper authorization.
		if (
			! isset( $_POST['menu-item-mega-menu-nonce'] )
			|| ! wp_verify_nonce( $_POST['menu-item-mega-menu-nonce'], 'update-mega-menu' )
		) {
			return $menu_id;
		}

		$selected = $_POST['gb-mega-menu'][ $menu_item_id ] ?? null;
		$anchor   = $_POST['gb-mega-menu-anchor'][ $menu_item_id ] ?? '';

		if ( $selected ) {
			$sanitized_data = sanitize_text_field( $selected );
			update_post_meta( $menu_item_id, '_gb_mega_menu', $sanitized_data );
		} else {
			delete_post_meta( $menu_item_id, '_gb_mega_menu' );
		}

		if ( $anchor ) {
			$sanitized_anchor = sanitize_text_field( $anchor );
			update_post_meta( $menu_item_id, '_gb_mega_menu_anchor', $sanitized_anchor );
		} else {
			delete_post_meta( $menu_item_id, '_gb_mega_menu_anchor' );
		}
	}
}

GenerateBlocks_Pro_Mega_Menus::get_instance()->init();
