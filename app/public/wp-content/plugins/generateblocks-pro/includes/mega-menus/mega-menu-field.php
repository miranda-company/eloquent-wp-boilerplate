<?php
/**
 * Available variables
 *
 * @param string        $item_id           Menu item ID as a numeric string.
 * @param WP_Post       $menu_item         Menu item data object.
 * @param int           $depth             Depth of menu item. Used for padding.
 * @param stdClass|null $args              An object of menu item arguments.
 * @param int           $current_object_id Nav menu ID.
 *
 * @package category
 */

$mega_menus = GenerateBlocks_Pro_Overlays::get_overlays();
// Filter by `mega-menu` type.
$mega_menus = array_filter(
	$mega_menus,
	function( $mega_menu ) {
		return isset( $mega_menu['type'] ) && 'mega-menu' === $mega_menu['type'];
	}
);
?>
<div class="description description-wide" style="margin-top: 10px;">
	<?php wp_nonce_field( 'update-mega-menu', 'menu-item-mega-menu-nonce' ); ?>
	<?php if ( ! empty( $mega_menus ) ) : ?>
		<p class="description description-wide">
			<label for="gb-mega-menu-<?php echo esc_attr( $item_id ); ?>">
				<?php esc_html_e( 'Mega Menu', 'generateblocks-pro' ); ?>
				<br />
				<select
					id="gb-mega-menu-<?php echo esc_attr( $item_id ); ?>"
					class="widefat gb-mega-menu-field--select"
					name="gb-mega-menu[<?php echo esc_attr( $item_id ); ?>]"
				>
					<option value=""><?php esc_html_e( 'Select Mega Menu', 'generateblocks-pro' ); ?></option>
					<?php foreach ( $mega_menus as $mega_menu ) : ?>
						<option
							value="<?php echo esc_attr( $mega_menu['id'] ); ?>"
							<?php selected( get_post_meta( $item_id, '_gb_mega_menu', true ), $mega_menu['id'] ); ?>
						>
							<?php echo esc_html( $mega_menu['title'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>
			<span style="font-size: 11px;"><?php esc_html_e( 'Mega Menus must be used with the GenerateBlocks Navigation block.', 'generateblocks-pro' ); ?></span>
		</p>
	<?php endif; ?>
</div>
