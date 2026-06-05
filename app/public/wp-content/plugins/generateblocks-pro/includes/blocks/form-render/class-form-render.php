<?php
/**
 * Form Render block.
 *
 * Page-side reference. Carries `formId` (a `gblocks_form` post ID) plus
 * styling attributes; everything else (fields, actions, email, Turnstile,
 * success/error messages) lives on the form post itself.
 *
 * Render delegates to the shared render helper so editor preview and
 * public output stay byte-for-byte equivalent.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form Render block class.
 */
class GenerateBlocks_Block_Form_Render extends GenerateBlocks_Block {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	public static $block_name = 'generateblocks-pro/form-render';

	/**
	 * Render the Form Render block.
	 *
	 * @param array    $attributes    The block attributes.
	 * @param string   $block_content The block content.
	 * @param WP_Block $block         The block instance.
	 * @return string The modified block content.
	 */
	public static function render_block( $attributes, $block_content, $block ) {
		unset( $block_content );

		$form_id = absint( $attributes['formId'] ?? 0 );

		if ( ! $form_id ) {
			return '';
		}

		$host_post_id = $block->context['postId'] ?? get_the_ID();
		$host_post_id = $host_post_id ? absint( $host_post_id ) : 0;

		$rendered = GenerateBlocks_Pro_Form_Render::render( $form_id, 'public', [ 'host_post_id' => $host_post_id ] );

		if ( is_wp_error( $rendered ) ) {
			// Public render failure — emit nothing rather than leaking error
			// detail. Admin users see a placeholder hint to help debugging.
			if ( current_user_can( GenerateBlocks_Pro_Form_Post_Type::get_capability( 'manage' ) ) ) {
				return sprintf(
					'<div class="gb-form gb-form--missing">%s</div>',
					esc_html__( 'Form not available. Make sure the referenced form is published.', 'generateblocks-pro' )
				);
			}

			return '';
		}

		GenerateBlocks_Pro_Form_Render::enqueue_public_assets();

		return $rendered;
	}

	/**
	 * Enqueue block assets.
	 */
	public static function enqueue_assets() {
		GenerateBlocks_Pro_Form_Render::enqueue_public_assets();
	}
}
