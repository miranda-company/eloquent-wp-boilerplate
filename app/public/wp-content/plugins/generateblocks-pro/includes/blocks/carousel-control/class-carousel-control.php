<?php
/**
 * Handles the Carousel Control block.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Carousel Control block class.
 */
class GenerateBlocks_Block_Carousel_Control extends GenerateBlocks_Block {
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
	public static $block_name = 'generateblocks-pro/carousel-control';

	/**
	 * Render the Carousel Control block.
	 *
	 * @param array  $attributes    The block attributes.
	 * @param string $block_content The block content.
	 * @param array  $block         The block.
	 */
	public static function render_block( $attributes, $block_content, $block ) {
		// Add styles to this block if needed.
		$block_content = generateblocks_maybe_add_block_css(
			$block_content,
			[
				'class_name' => __CLASS__,
				'attributes' => $attributes,
				'block_ids' => self::$block_ids,
			]
		);

		// Add default ARIA labels if not present using WP HTML Tag Processor
		// Only available in WordPress 6.2+, skip for older versions.
		if ( class_exists( 'WP_HTML_Tag_Processor' ) ) {
			$control_type = $attributes['controlType'] ?? 'next';
			$has_aria_label = isset( $attributes['htmlAttributes']['aria-label'] ) && ! empty( $attributes['htmlAttributes']['aria-label'] );

			$processor = new \WP_HTML_Tag_Processor( $block_content );

			// Find the first tag (the button/control element).
			if ( $processor->next_tag() ) {
				// Add default ARIA label if not present.
				if ( ! $has_aria_label && ! $processor->get_attribute( 'aria-label' ) ) {
					// Map control types to default ARIA labels.
					$default_labels = [
						'next'       => __( 'Next slide', 'generateblocks-pro' ),
						'previous'   => __( 'Previous slide', 'generateblocks-pro' ),
						'play'       => __( 'Play carousel', 'generateblocks-pro' ),
						'pause'      => __( 'Pause carousel', 'generateblocks-pro' ),
						'play-pause' => __( 'Toggle carousel playback', 'generateblocks-pro' ),
						'first'      => __( 'Go to first slide', 'generateblocks-pro' ),
						'last'       => __( 'Go to last slide', 'generateblocks-pro' ),
					];

					$aria_label = $default_labels[ $control_type ] ?? __( 'Carousel control', 'generateblocks-pro' );
					$processor->set_attribute( 'aria-label', $aria_label );
				}

				// For play-pause controls, ensure aria-pressed attribute exists.
				if ( 'play-pause' === $control_type && ! $processor->get_attribute( 'aria-pressed' ) ) {
					$processor->set_attribute( 'aria-pressed', 'false' );
				}

				$block_content = $processor->get_updated_html();
			}
		}

		return $block_content;
	}
}
