<?php
/**
 * Handle the carousel block.
 *
 * @package GenerateBlocks Pro
 */

// Include our files.
require_once 'class-carousel.php';

add_filter( 'block_editor_settings_all', 'generateblocks_pro_carousel_block_editor_settings', 20 );
/**
 * Add block editor settings for the carousel block.
 *
 * @param array $settings The block editor settings.
 */
function generateblocks_pro_carousel_block_editor_settings( $settings ) {
	$blocks_to_reset = [
		'.editor-styles-wrapper .wp-block-generateblocks-pro-carousel',
		'.editor-styles-wrapper .wp-block-generateblocks-pro-carousel-pagination',
		'.editor-styles-wrapper .wp-block-generateblocks-pro-carousel-items',
		'.editor-styles-wrapper .wp-block-generateblocks-pro-carousel-item',
		'.editor-styles-wrapper .wp-block-generateblocks-pro-carousel-control',
	];
	$css = implode( ',', $blocks_to_reset ) . ' {max-width:unset;margin:0}';
	$settings['styles'][] = [ 'css' => $css ];

	return $settings;
}

add_filter( 'generateblocks_block_css', 'generateblocks_pro_carousel_block_css', 10, 2 );
/**
 * Add block CSS for the carousel block.
 *
 * @param string $css    The CSS to add.
 * @param array  $block  The block data.
 *
 * @return string
 */
function generateblocks_pro_carousel_block_css( $css, $block ) {
	if ( ! isset( $block['attributes']['uniqueId'] ) ) {
		return $css;
	}

	$block_name = $block['block_name'] ?? '';

	if ( 'generateblocks-pro/carousel' !== $block_name ) {
		return $css;
	}

	$selector    = '.gb-carousel-' . $block['attributes']['uniqueId'];
	$init_at_raw = $block['attributes']['htmlAttributes']['data-init-at'] ?? '';
	$init_at     = '';

	if ( is_string( $init_at_raw ) ) {
		$init_at_raw = trim( $init_at_raw );

		if ( '' !== $init_at_raw && false === strpbrk( $init_at_raw, '{};()/*' ) ) {
			$init_at = $init_at_raw;

			if ( is_numeric( $init_at ) ) {
				$init_at .= 'px';
			}
		}
	}

	if ( $init_at ) {
		$css_rules = [
			$selector . ':not([data-gb-carousel-initialized="true"]):not([data-gb-carousel-pending="true"]) .gb-carousel-items' => [
				'position' => 'relative',
				'width' => '100%',
				'height' => '100%',
				'z-index' => '1',
				'display' => 'flex',
				'overflow-x' => 'hidden',
				'gap' => 'var(--gb-carousel-slide-gap, 0)',
			],
			$selector . ':not([data-gb-carousel-initialized="true"]) .gb-carousel-items > .gb-carousel-item' => [
				'flex' => '0 0 calc((100% - var(--gb-carousel-slide-gap, 0px) * (var(--gb-carousel-slides-per-view, 1) - 1)) / var(--gb-carousel-slides-per-view, 1))',
				'width' => '100%',
				'height' => '100%',
				'position' => 'relative',
				'transition-property' => 'transform',
				'display' => 'block',
				'user-select' => 'none',
			],
		];
		$media_query_css = generateblocks_pro_build_css_from_array( $css_rules );
		$css .= "@media (max-width: {$init_at}) {{$media_query_css}}";

		$not_at_rule_css_rules = [
			$selector . ':not([data-gb-carousel-initialized="true"]) .gb-carousel-control' => [
				'display' => 'none',
			],
			$selector . ':not([data-gb-carousel-initialized="true"]) .gb-carousel-pagination' => [
				'display' => 'none',
			],
		];

		$not_at_rule_css = generateblocks_pro_build_css_from_array( $not_at_rule_css_rules );
		$css .= "@media (width > {$init_at}) {{$not_at_rule_css}}";
	}

	$grid_rows = $block['attributes']['htmlAttributes']['data-grid-rows'] ?? '';

	if ( $grid_rows ) {
		$slides_per_view = $block['attributes']['htmlAttributes']['data-slides-per-view'] ?? '1';
		$total_items     = $grid_rows * $slides_per_view;
		$selected_items  = $total_items + 1;

		$css_rules = [
			$selector . ':not([data-gb-carousel-initialized="true"]):not([data-gb-carousel-pending="true"]) > .gb-carousel-items > .gb-carousel-item:nth-child(n+' . $selected_items . ')' => [
				'display' => 'none',
			],
		];

		$built_css = generateblocks_pro_build_css_from_array( $css_rules );

		if ( $init_at ) {
			$css .= "@media (max-width: {$init_at}) {{$built_css}}";
		} else {
			$css .= $built_css;
		}
	}

	return $css;
}
