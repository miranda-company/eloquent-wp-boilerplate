<?php
/**
 * Handles the Carousel block.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Carousel block class.
 */
class GenerateBlocks_Block_Carousel extends GenerateBlocks_Block {
	/**
	 * Keep track of all blocks of this type on the page.
	 *
	 * @var array $block_ids The current block id.
	 */
	protected static $block_ids = [];

	/**
	 * Store our block name.
	 *
	 * @var string $block_name The block name.
	 */
	public static $block_name = 'generateblocks-pro/carousel';

	/**
	 * Render the Carousel block.
	 *
	 * @param array  $attributes    The block attributes.
	 * @param string $block_content The block content.
	 * @param array  $block         The block.
	 */
	public static function render_block( $attributes, $block_content, $block ) {
		// Enqueue frontend scripts and styles.
		self::enqueue_scripts();
		self::enqueue_styles();

		// Add styles to this block if needed.
		$block_content = generateblocks_maybe_add_block_css(
			$block_content,
			[
				'class_name' => __CLASS__,
				'attributes' => $attributes,
				'block_ids' => self::$block_ids,
			]
		);

		return $block_content;
	}

	/**
	 * Enqueue frontend scripts for the carousel.
	 */
	private static function enqueue_scripts() {
		wp_enqueue_script(
			'generateblocks-carousel',
			GENERATEBLOCKS_PRO_DIR_URL . 'dist/carousel.js',
			[],
			GENERATEBLOCKS_PRO_VERSION,
			true
		);
	}

	/**
	 * Enqueue frontend styles for the carousel.
	 */
	private static function enqueue_styles() {
		wp_enqueue_style(
			'generateblocks-carousel',
			GENERATEBLOCKS_PRO_DIR_URL . 'dist/carousel-style.css',
			[],
			GENERATEBLOCKS_PRO_VERSION
		);
	}

	/**
	 * Enqueue block assets.
	 */
	public static function enqueue_assets() {
		self::enqueue_scripts();
		self::enqueue_styles();
	}
}
