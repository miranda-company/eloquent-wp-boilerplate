<?php
/**
 * Form block.
 *
 * Authoring container that lives inside `gblocks_form` post content. Renders
 * its inner form-field blocks with our standard wrapper class. Runtime
 * action/method/security attributes are added by class-form-render.php while
 * a saved form post is being rendered.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form block class.
 */
class GenerateBlocks_Block_Form extends GenerateBlocks_Block {

	/**
	 * Keep track of all blocks of this type on the page.
	 *
	 * @var array
	 */
	protected static $block_ids = [];

	/**
	 * Block name.
	 *
	 * @var string
	 */
	public static $block_name = 'generateblocks-pro/form';

	/**
	 * Render the Form block.
	 *
	 * @param array    $attributes    The block attributes.
	 * @param string   $block_content The block content.
	 * @param WP_Block $block         The block instance.
	 * @return string The modified block content.
	 */
	public static function render_block( $attributes, $block_content, $block ) {
		unset( $block );

		$block_content = generateblocks_maybe_add_block_css(
			$block_content,
			[
				'class_name' => __CLASS__,
				'attributes' => $attributes,
				'block_ids'  => self::$block_ids,
			]
		);

		if ( class_exists( 'GenerateBlocks_Pro_Form_Render' ) ) {
			return GenerateBlocks_Pro_Form_Render::prepare_form_block_output( $block_content );
		}

		return $block_content;
	}
}
