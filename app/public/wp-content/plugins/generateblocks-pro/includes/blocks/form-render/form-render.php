<?php
/**
 * Form Render block loader.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-form-render.php';

add_filter( 'block_editor_settings_all', 'generateblocks_pro_form_block_editor_settings', 20 );
/**
 * Add block editor settings for the form blocks.
 *
 * @param array $settings The block editor settings.
 */
function generateblocks_pro_form_block_editor_settings( $settings ) {
	$blocks_to_reset = [
		'.editor-styles-wrapper .wp-block-generateblocks-pro-form-render',
		'.editor-styles-wrapper .wp-block-generateblocks-pro-form',
		'.editor-styles-wrapper .wp-block-generateblocks-pro-form-field',
		'.editor-styles-wrapper .wp-block-generateblocks-pro-form-field-label',
		'.editor-styles-wrapper .wp-block-generateblocks-pro-form-field-control',
	];
	$css = implode( ',', $blocks_to_reset ) . ' {max-width:unset;margin:0}';
	$settings['styles'][] = [ 'css' => $css ];

	return $settings;
}
