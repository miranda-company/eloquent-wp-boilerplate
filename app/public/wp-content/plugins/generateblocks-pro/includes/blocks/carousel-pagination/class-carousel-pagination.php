<?php
/**
 * Handles the Carousel Pagination block.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Carousel Pagination block class.
 */
class GenerateBlocks_Block_Carousel_Pagination extends GenerateBlocks_Block {
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
	public static $block_name = 'generateblocks-pro/carousel-pagination';

	/**
	 * Render the Carousel Pagination block.
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

		$to_replace      = '<span class="gb-carousel-pagination-content"></span>';
		$pagination_type = $block->context['generateblocks/carousel/htmlAttributes']['data-pagination-type'] ?? 'bullets';
		$replacement     = '';

		if ( 'fraction' === $pagination_type ) {
			$replacement = '<span class="gb-carousel-current">1</span> / <span class="gb-carousel-total">1</span>';
		}

		if ( 'bullets' === $pagination_type ) {
			$replacement = '<span class="gb-carousel-dot" style="display: inline-block !important;"></span>';
		}

		if ( $replacement ) {
			$block_content = str_replace( $to_replace, $replacement, $block_content );
		}

		// Add default ARIA attributes if not present using WP HTML Tag Processor
		// Only available in WordPress 6.2+, skip for older versions.
		if ( class_exists( 'WP_HTML_Tag_Processor' ) ) {
			$has_aria_label = isset( $attributes['htmlAttributes']['aria-label'] ) && ! empty( $attributes['htmlAttributes']['aria-label'] );

			$processor = new \WP_HTML_Tag_Processor( $block_content );

			// Find the first tag (the pagination container).
			if ( $processor->next_tag() ) {
				// Add default ARIA label if not present.
				if ( ! $has_aria_label && ! $processor->get_attribute( 'aria-label' ) ) {
					// Map pagination types to default ARIA labels.
					$default_labels = [
						'bullets'     => __( 'Carousel pagination', 'generateblocks-pro' ),
						'fraction'    => __( 'Carousel slide counter', 'generateblocks-pro' ),
						'progressbar' => __( 'Carousel progress', 'generateblocks-pro' ),
					];

					$aria_label = $default_labels[ $pagination_type ] ?? __( 'Carousel pagination', 'generateblocks-pro' );
					$processor->set_attribute( 'aria-label', $aria_label );
				}

				// Add role attribute for bullets pagination if not present.
				if ( 'bullets' === $pagination_type && ! $processor->get_attribute( 'role' ) ) {
					$processor->set_attribute( 'role', 'navigation' );
				}

				$block_content = $processor->get_updated_html();
			}
		}

		return $block_content;
	}
}
