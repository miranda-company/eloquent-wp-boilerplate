<?php
/**
 * Handle plugin functions.
 *
 * @package GenerateBlocks Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Build CSS from an array of rules.
 *
 * @param array $rules Array of CSS rules where key is selector and value is properties array.
 * @return string CSS string.
 */
function generateblocks_pro_build_css_from_array( $rules ) {
	$css = '';

	foreach ( $rules as $selector => $properties ) {
		$css .= $selector . '{';
		foreach ( $properties as $property => $value ) {
			$css .= $property . ':' . $value . ';';
		}
		$css .= '}';
	}

	return $css;
}

/**
 * Get our effect selectors.
 *
 * @since 1.0.0
 * @param array  $effect_data The data.
 * @param array  $settings The settings.
 * @param string $selector The current selector.
 * @param int    $key The current key.
 * @return array
 */
function generateblocks_pro_get_effect_selector( $effect_data, $settings, $selector, $key ) {
	$state = '';
	$device = '';
	$backgroundType = '';

	if ( ! empty( $effect_data[ $key ]['state'] ) && 'normal' !== $effect_data[ $key ]['state'] ) {
		$state = $effect_data[ $key ]['state'];
	}

	if ( ! empty( $effect_data[ $key ]['device'] ) && 'all' !== $effect_data[ $key ]['device'] ) {
		$device = $effect_data[ $key ]['device'];
	}

	if ( isset( $effect_data[ $key ]['type'] ) ) {
		if ( 'background' === $effect_data[ $key ]['type'] ) {
			$backgroundType = 'background';
		} elseif ( 'gradient' === $effect_data[ $key ]['type'] ) {
			$backgroundType = 'gradient';
		}
	}

	$element = 'element' . $backgroundType . $state . $device;

	if ( ! empty( $effect_data[ $key ]['target'] ) && 'self' !== $effect_data[ $key ]['target'] ) {
		$element = $effect_data[ $key ]['target'] . $backgroundType . $state . $device;

		if ( 'customSelector' === $effect_data[ $key ]['target'] && ! empty( $effect_data[ $key ]['customSelector'] ) ) {
			$element = $effect_data[ $key ]['customSelector'] . $backgroundType . $state . $device;
		}
	}

	if ( 'hover' === $state ) {
		$state = ':hover';
	}

	$effectSelector = $selector . $state;

	if ( isset( $effect_data[ $key ]['target'] ) && 'self' !== $effect_data[ $key ]['target'] ) {
		if ( 'innerContainer' === $effect_data[ $key ]['target'] ) {
			$blockVersion = ! empty( $settings['blockVersion'] ) ? $settings['blockVersion'] : 1;
			$useInnerContainer = $blockVersion < 3 || ! empty( $settings['useInnerContainer'] );

			if ( ! $useInnerContainer ) {
				$effectSelector = '.gb-container-' . $settings['uniqueId'] . $state . ' > .gb-container';
			} else {
				$effectSelector = '.gb-container-' . $settings['uniqueId'] . $state . ' > .gb-inside-container';
			}
		}

		if ( 'backgroundImage' === $effect_data[ $key ]['target'] ) {
			$effectSelector = $selector . $state . ':before';
		}

		if ( 'icon' === $effect_data[ $key ]['target'] ) {
			$effectSelector = $selector . $state . ' .gb-icon';
		}

		if ( 'accordionContent' === $effect_data[ $key ]['target'] ) {
			$effectSelector = $selector . $state . ' > .gb-accordion__content';
		}

		if ( 'customSelector' === $effect_data[ $key ]['target'] ) {
			$effectSelector = $selector . $state . ' ' . $effect_data[ $key ]['customSelector'];
		}

		if ( 'pseudo-element' === $effect_data[ $key ]['target'] ) {
			$effectSelector = $selector . $state . ':before';

			if ( isset( $effect_data[ $key ]['direction'] ) ) {
				$effectSelector = $selector . $state . ':after';
			}
		}
	}

	return array(
		'element' => $element,
		'selector' => $effectSelector,
	);
}

/**
 * Get our transform data.
 *
 * @since 1.0.0
 * @param array  $settings  The block settings.
 * @param string $selector The selector we're using.
 */
function generateblocks_pro_get_transforms( $settings, $selector ) {
	$transformData = array();

	if ( $settings['useTransform'] && $settings['transforms'] ) {
		foreach ( $settings['transforms'] as $key => $value ) {
			$selectorData = generateblocks_pro_get_effect_selector( $settings['transforms'], $settings, $selector, $key );
			$element = $selectorData['element'];
			$effectSelector = $selectorData['selector'];

			$transformData[ $element ]['selector'] = $effectSelector;
			$transformData[ $element ]['state'] = ! empty( $value['state'] ) ? $value['state'] : 'normal';
			$transformData[ $element ]['device'] = ! empty( $value['device'] ) ? $value['device'] : 'all';

			if ( 'translate' === $value['type'] ) {
				$translateX = 0;
				$translateY = 0;

				if ( isset( $value['translateX'] ) && '' !== $value['translateX'] ) {
					$translateX = (float) $value['translateX'] . 'px';
				}

				if ( isset( $value['translateY'] ) && '' !== $value['translateY'] ) {
					$translateY = (float) $value['translateY'] . 'px';
				}

				if ( $translateX || $translateY ) {
					$transformData[ $element ]['transforms'][] = 'translate3d(' . $translateX . ',' . $translateY . ',0)';
				}
			}

			if ( 'rotate' === $value['type'] ) {
				if ( isset( $value['rotate'] ) && '' !== $value['rotate'] ) {
					$transformData[ $element ]['transforms'][] = 'rotate(' . (float) $value['rotate'] . 'deg)';
				}
			}

			if ( 'skew' === $value['type'] ) {
				if ( isset( $value['skewX'] ) && '' !== $value['skewX'] ) {
					$transformData[ $element ]['transforms'][] = 'skewX(' . (float) $value['skewX'] . 'deg)';
				}

				if ( isset( $value['skewY'] ) && '' !== $value['skewY'] ) {
					$transformData[ $element ]['transforms'][] = 'skewY(' . (float) $value['skewY'] . 'deg)';
				}
			}

			if ( 'scale' === $value['type'] ) {
				if ( isset( $value['scale'] ) && '' !== $value['scale'] ) {
					$transformData[ $element ]['transforms'][] = 'scale(' . (float) $value['scale'] . ')';
					$transformData[ $element ]['transforms'][] = 'perspective(1000px)'; // Activate GPU.
				}
			}
		}
	}

	return $transformData;
}

/**
 * Get our CSS filter data.
 *
 * @since 1.0.0
 * @param array  $settings  The block settings.
 * @param string $selector The selector we're using.
 */
function generateblocks_pro_get_css_filters( $settings, $selector ) {
	$filterData = array();

	if ( $settings['useFilter'] && $settings['filters'] ) {
		foreach ( $settings['filters'] as $key => $value ) {
			$selectorData = generateblocks_pro_get_effect_selector( $settings['filters'], $settings, $selector, $key );
			$element = $selectorData['element'];
			$effectSelector = $selectorData['selector'];

			$filterData[ $element ]['selector'] = $effectSelector;
			$filterData[ $element ]['state'] = ! empty( $value['state'] ) ? $value['state'] : 'normal';
			$filterData[ $element ]['device'] = ! empty( $value['device'] ) ? $value['device'] : 'all';

			if ( 'blur' === $value['type'] ) {
				if ( isset( $value['blur'] ) && '' !== $value['blur'] ) {
					$filterData[ $element ]['filters'][] = 'blur(' . (float) $value['blur'] . 'px)';
				}
			}

			if ( 'brightness' === $value['type'] ) {
				if ( isset( $value['brightness'] ) && '' !== $value['brightness'] ) {
					$filterData[ $element ]['filters'][] = 'brightness(' . (float) $value['brightness'] . '%)';
				}
			}

			if ( 'contrast' === $value['type'] ) {
				if ( isset( $value['contrast'] ) && '' !== $value['contrast'] ) {
					$filterData[ $element ]['filters'][] = 'contrast(' . (float) $value['contrast'] . '%)';
				}
			}

			if ( 'grayscale' === $value['type'] ) {
				if ( isset( $value['grayscale'] ) && '' !== $value['grayscale'] ) {
					$filterData[ $element ]['filters'][] = 'grayscale(' . (float) $value['grayscale'] . '%)';
				}
			}

			if ( 'hue-rotate' === $value['type'] ) {
				if ( isset( $value['hueRotate'] ) && '' !== $value['hueRotate'] ) {
					$filterData[ $element ]['filters'][] = 'hue-rotate(' . (float) $value['hueRotate'] . 'deg)';
				}
			}

			if ( 'invert' === $value['type'] ) {
				if ( isset( $value['invert'] ) && '' !== $value['invert'] ) {
					$filterData[ $element ]['filters'][] = 'invert(' . (float) $value['invert'] . '%)';
				}
			}

			if ( 'saturate' === $value['type'] ) {
				if ( isset( $value['saturate'] ) && '' !== $value['saturate'] ) {
					$filterData[ $element ]['filters'][] = 'saturate(' . (float) $value['saturate'] . '%)';
				}
			}

			if ( 'sepia' === $value['type'] ) {
				if ( isset( $value['sepia'] ) && '' !== $value['sepia'] ) {
					$filterData[ $element ]['filters'][] = 'sepia(' . (float) $value['sepia'] . '%)';
				}
			}
		}
	}

	return $filterData;
}

/**
 * Check if we can upload SVG files.
 *
 * @since 1.1.0
 */
function generateblocks_pro_has_svg_support() {
	$has_mime_type = in_array( 'image/svg+xml', (array) get_allowed_mime_types() );
	$has_safe_svg  = is_plugin_active( 'safe-svg/safe-svg.php' );

	return $has_mime_type || $has_safe_svg;
}

/**
 * Try to sanitize SVGs if the Safe SVG plugin isn't active.
 *
 * @since 1.0.0
 */
function generateblocks_pro_kses_svg() {
	$kses_defaults = wp_kses_allowed_html( 'post' );

	$svg_args = array(
		'svg' => array(
			'class'           => true,
			'aria-hidden'     => true,
			'aria-labelledby' => true,
			'role'            => true,
			'xmlns'           => true,
			'width'           => true,
			'height'          => true,
			'viewbox'         => true, // <= Must be lower case!
		),
		'g' => array( 'fill' => true ),
		'title' => array( 'title' => true ),
		'path' => array(
			'd' => true,
			'fill' => true,
		),
	);

	return array_merge( $kses_defaults, $svg_args );
}

/**
 * Prepare our custom icons for the editor.
 *
 * @since 1.0.0
 */
function generateblocks_pro_editor_icon_list() {
	$custom_icons = get_option( 'generateblocks_svg_icons', array() );
	$new_icons = array();

	// Format our custom shapes to fit our shapes structure.
	foreach ( $custom_icons as $index => $data ) {
		$new_icons[ sanitize_key( $data['group'] ) ] = array(
			'group' => sanitize_text_field( $data['group'] ),
			'svgs' => array(),
		);

		if ( isset( $data['icons'] ) ) {
			foreach ( (array) $data['icons'] as $shape_index => $icon ) {
				$new_icons[ sanitize_key( $data['group'] ) ]['svgs'][ $icon['id'] ] = array(
					'label' => $icon['name'],
					'icon' => $icon['icon'],
				);
			}
		}
	}

	return $new_icons;
}

/**
 * Get all of our global style IDs.
 *
 * @since 1.0.0
 */
function generateblocks_pro_get_global_style_ids() {
	$global_styles = get_option( 'generateblocks_global_style_attrs', array() );
	$ids = array();

	foreach ( $global_styles as $id => $data ) {
		foreach ( $data['ids'] as $name => $block_ids ) {
			foreach ( $block_ids as $id ) {
				$ids[ $name ][] = $id['uniqueId'];
			}
		}
	}

	return $ids;
}

/**
 * Get all of our global style IDs.
 *
 * @since 1.0.0
 */
function generateblocks_pro_get_global_style_attrs() {
	$global_styles = get_option( 'generateblocks_global_style_attrs', array() );
	$ids = array();

	foreach ( $global_styles as $id => $data ) {
		foreach ( $data['ids'] as $name => $block_ids ) {
			foreach ( $block_ids as $id => $attrs ) {
				$ids[ $name ][ $id ] = $attrs;
			}
		}
	}

	return $ids;
}

/**
 * Add custom attributes to a block.
 *
 * @since 1.3.0
 * @param array $attributes The existing attributes.
 * @param array $settings The settings for the block.
 */
function generateblocks_pro_with_custom_attributes( $attributes, $settings ) {
	if ( ! empty( $settings['htmlAttributes'] ) && is_array( $settings['htmlAttributes'] ) ) {
		foreach ( $settings['htmlAttributes'] as $key => $data ) {
			if ( ! $data['attribute'] ) {
				continue;
			}

			$attribute = esc_attr( $data['attribute'] );
			$attributes[ $attribute ] = isset( $data['value'] ) && '' !== $data['value'] ? esc_attr( $data['value'] ) : true;
		}
	}

	return $attributes;
}

/**
 * Get our script dependencies and version.
 *
 * @param string $filename The filename to use.
 * @param array  $fallback_assets The assets to fallback to.
 * @param string $base_path The base path for the file search.
 */
function generateblocks_pro_get_enqueue_assets(
	$filename = '',
	$fallback_assets = [
		'dependencies' => [],
		'version' => '',
	],
	$base_path = GENERATEBLOCKS_PRO_DIR . 'dist/'
) {
	if ( ! $filename ) {
		return $fallback_assets;
	}

	$assets_file = $base_path . $filename . '.asset.php';
	$compiled_assets = file_exists( $assets_file )
		? require $assets_file
		: false;

	$assets =
		isset( $compiled_assets['dependencies'] ) &&
		isset( $compiled_assets['version'] )
		? $compiled_assets
		: $fallback_assets;

	return $assets;
}

/**
 * Get the total number of legacy patterns.
 */
function generateblocks_pro_get_legacy_patterns_count() {
	$count = wp_count_posts( 'gblocks_templates' );

	if ( ! is_object( $count ) ) {
		return 0;
	}

	$total = 0;

	foreach ( array( 'publish', 'future', 'draft', 'pending', 'private', 'trash' ) as $status ) {
		$total += isset( $count->{$status} ) ? (int) $count->{$status} : 0;
	}

	return $total;
}

/**
 * Check if we have any legacy patterns.
 */
function generateblocks_pro_has_legacy_patterns() {
	return generateblocks_pro_get_legacy_patterns_count() > 0;
}

/**
 * Get inline CSS for blocks as a pure string without using filters.
 * This allows CSS generation for specific content without side effects.
 *
 * @since 2.3.0
 * @param string|array $content The content to parse (can be raw content or parsed blocks).
 * @return string The generated CSS.
 */
function generateblocks_pro_get_css_from_content( $content ) {
	// Short circuit if GenerateBlocks adds this function in the future.
	if ( function_exists( 'generateblocks_get_css_from_content' ) ) {
		return generateblocks_get_css_from_content( $content );
	}

	if ( ! function_exists( 'generateblocks_get_block_data' ) ) {
		return '';
	}

	// Parse blocks if we received raw content.
	$parsed_blocks = is_array( $content ) ? $content : parse_blocks( $content );
	$data = generateblocks_get_block_data( $parsed_blocks );

	if ( empty( $data ) ) {
		return '';
	}

	$css = '';
	$css_data = array();

	$blocks = apply_filters(
		'generateblocks_dynamic_css_blocks',
		array(
			'text'               => 'GenerateBlocks_Block_Text',
			'element'            => 'GenerateBlocks_Block_Element',
			'media'              => 'GenerateBlocks_Block_Media',
			'shape'              => 'GenerateBlocks_Block_Shape',
			'query'              => 'GenerateBlocks_Block_Query',
			'looper'             => 'GenerateBlocks_Block_Looper',
			'query-page-numbers' => 'GenerateBlocks_Block_Query_Page_Numbers',
			'loop-item'          => 'GenerateBlocks_Block_Loop_Item',
		)
	);

	// Legacy blocks that use get_css_data().
	$legacy_blocks = array(
		'grid'             => 'GenerateBlocks_Block_Grid',
		'container'        => 'GenerateBlocks_Block_Container',
		'button-container' => 'GenerateBlocks_Block_Button_Container',
		'button'           => 'GenerateBlocks_Block_Button',
		'headline'         => 'GenerateBlocks_Block_Headline',
		'image'            => 'GenerateBlocks_Block_Image',
	);

	// Process each block type.
	foreach ( $data as $block_type => $block_instances ) {
		// Handle modern blocks.
		if ( isset( $blocks[ $block_type ] ) ) {
			$class_name = $blocks[ $block_type ];

			if ( class_exists( $class_name ) && is_callable( array( $class_name, 'get_css' ) ) ) {
				foreach ( $block_instances as $attributes ) {
					$block_css = $class_name::get_css( $attributes );

					if ( $block_css ) {
						$css .= wp_strip_all_tags( $block_css );
					}
				}
			}
		} elseif ( isset( $legacy_blocks[ $block_type ] ) ) {
			// Handle legacy blocks.
			$class_name = $legacy_blocks[ $block_type ];

			if ( class_exists( $class_name ) && is_callable( array( $class_name, 'get_css_data' ) ) ) {
				foreach ( $block_instances as $attributes ) {
					$block_css_data = $class_name::get_css_data( $attributes );

					if ( $block_css_data && function_exists( 'generateblocks_group_css_data' ) ) {
						$css_data = generateblocks_group_css_data( $css_data, $block_css_data );
					}
				}
			}
		}
	}

	// Compile legacy block CSS data if we have any.
	if ( ! empty( $css_data ) && function_exists( 'generateblocks_get_compiled_css' ) ) {
		$css .= generateblocks_get_compiled_css( $css_data );
	}

	return $css;
}

/**
 * Check if the overlay panels feature is enabled.
 *
 * @since 2.4.0
 * @return bool Whether overlay panels are enabled.
 */
function generateblocks_pro_overlays_enabled() {
	// Bail early if the function doesn't exist.
	if ( ! function_exists( 'generateblocks_get_option' ) ) {
		return true;
	}

	$enabled = generateblocks_get_option( 'enable_overlay_panels' );

	// If explicitly set to false, respect that.
	if ( false === $enabled ) {
		return false;
	}

	// Default to true for null or any other value.
	return true;
}

/**
 * Check if the block conditions feature is enabled.
 *
 * @since 2.4.0
 * @return bool Whether block conditions are enabled.
 */
function generateblocks_pro_block_conditions_enabled() {
	// Bail early if the function doesn't exist.
	if ( ! function_exists( 'generateblocks_get_option' ) ) {
		return true;
	}

	$enabled = generateblocks_get_option( 'enable_block_conditions' );

	// If explicitly set to false, respect that.
	if ( false === $enabled ) {
		return false;
	}

	// Default to true for null or any other value.
	return true;
}

/**
 * Check if the forms feature is enabled.
 *
 * @since 2.6.0
 * @return bool Whether forms are enabled.
 */
function generateblocks_pro_forms_enabled() {
	// Bail early if the function doesn't exist.
	if ( ! function_exists( 'generateblocks_get_option' ) ) {
		return false;
	}

	$enabled = generateblocks_get_option( 'enable_forms' );

	return true === $enabled || 1 === $enabled || '1' === $enabled || 'true' === $enabled;
}

/**
 * Whether the current user can import forms via the pattern library route.
 *
 * Resolves the same cap + filter the route uses, so the editor's disable
 * state mirrors what the REST permission_callback will accept. Filters
 * that branch on the WP_REST_Request must tolerate `null` here — this is
 * an enqueue-time evaluation with no request in flight.
 *
 * @since 2.6.0
 * @return bool
 */
function generateblocks_pro_can_import_pattern_forms() {
	if ( ! class_exists( 'GenerateBlocks_Pro_Form_Post_Type' ) ) {
		return false;
	}

	$can_import = GenerateBlocks_Pro_Form_Post_Type::current_user_can( 'manage' );

	/**
	 * Filter whether the current user can import forms via the pattern
	 * library route. Receives `null` for `$request` when evaluated at
	 * editor enqueue time (no request in flight).
	 *
	 * @since 2.6.0
	 * @param bool                  $can_import Default cap result.
	 * @param WP_REST_Request|null  $request    Request when called from the
	 *                                          REST permission_callback,
	 *                                          `null` at enqueue time.
	 */
	return (bool) apply_filters(
		'generateblocks_pattern_library_can_import_forms',
		$can_import,
		null
	);
}
