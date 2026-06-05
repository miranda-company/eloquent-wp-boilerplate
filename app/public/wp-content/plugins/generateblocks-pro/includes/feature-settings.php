<?php
/**
 * Feature settings integration for Pro features.
 *
 * @package GenerateBlocks Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_filter( 'generateblocks_option_defaults', 'generateblocks_pro_add_feature_defaults' );
/**
 * Add feature settings to GenerateBlocks option defaults.
 *
 * @param array $defaults The existing defaults.
 * @return array Modified defaults.
 */
function generateblocks_pro_add_feature_defaults( $defaults ) {
	$defaults['enable_overlay_panels'] = true;
	$defaults['enable_block_conditions'] = true;
	$defaults['enable_forms'] = false;
	return $defaults;
}

add_filter( 'generateblocks_option_sanitize_callbacks', 'generateblocks_pro_add_feature_sanitize' );
/**
 * Add sanitization callbacks for feature settings.
 *
 * @param array $callbacks The existing callbacks.
 * @return array Modified callbacks.
 */
function generateblocks_pro_add_feature_sanitize( $callbacks ) {
	$callbacks['enable_overlay_panels'] = 'rest_sanitize_boolean';
	$callbacks['enable_block_conditions'] = 'rest_sanitize_boolean';
	$callbacks['enable_forms'] = 'rest_sanitize_boolean';
	return $callbacks;
}
