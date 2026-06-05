<?php
/**
 * Template partial for overlays.
 *
 * Variables available:
 *
 * @param string $overlay_content The content of the overlay.
 * @param string $overlay_id      The HTML ID attribute for the overlay.
 * @param string $overlay_classes The classes for the overlay.
 * @param string $overlay_type    The type of overlay (standard or anchored).
 * @param string $trigger_type      The trigger type (click, hover, or both).
 * @param string $placement         The placement for anchored overlays (e.g., bottom-start).
 * @param bool   $backdrop          Whether to show a backdrop for standard overlays.
 * @param string $backdrop_color    The backdrop color value.
 * @param string $backdrop_blur     The backdrop blur value in pixels.
 * @param string $animation_in      The animation class for overlay entrance.
 * @param string $animation_out     The animation class for overlay exit.
 * @param string $animation_duration The animation duration.
 * @param string $animation_target  The animation target selector.
 * @param string $animation_distance The animation distance.
 * @param mixed  $scroll_percent    The scroll percentage trigger (false to disable).
 * @param mixed  $time_delay        The time delay trigger (false to disable).
 * @param mixed  $cookie_duration   The cookie duration (false to disable).
 * @param bool   $close_on_esc      Whether to close on ESC key (standard overlays).
 * @param bool   $close_on_click_outside Whether to close on click outside (standard overlays).
 * @param bool   $disable_page_scroll Whether to disable page scroll when open (standard overlays).
 * @param string $position          The position for standard overlays.
 * @param bool   $hide_if_cookies_disabled Whether to hide overlay if cookies are disabled.
 * @param string $position_to_parent The selector for positioning anchored overlays.
 * @param string $width_mode        The width mode for standard overlays ('' or 'full').
 *
 * @package generateblocks-pro
 */

// Early return if no content.
if ( empty( $overlay_content ) ) {
	return;
}

// Setup variables.
$html_id = $overlay_id ? $overlay_id : wp_unique_id( 'gb-overlay-' );

// We want our mega menu overlays to be treated as anchored overlays.
if ( 'mega-menu' === $overlay_type ) {
	$overlay_type = 'anchored';
}

$overlay_classes = 'gb-overlay gb-overlay--' . esc_attr( $overlay_type );

// Add position class for standard overlays.
if ( 'standard' === $overlay_type && ! empty( $position ) && 'center' !== $position ) {
	$overlay_classes .= ' gb-overlay--' . esc_attr( $position );
}

// Add width mode class for all overlay types.
if ( ! empty( $width_mode ) && 'full' === $width_mode ) {
	$overlay_classes .= ' gb-overlay--width-full';
}

// Build data attributes array.
$data_attributes = [
	'data-gb-overlay' => '',
	'data-gb-overlay-type' => $overlay_type,
	'data-gb-overlay-trigger-type' => $trigger_type,
];

// Conditional data attributes.
if ( 'anchored' === $overlay_type && ! empty( $placement ) ) {
	$data_attributes['data-gb-overlay-placement'] = $placement;
}

// Position to parent for anchored overlays.
if ( 'anchored' === $overlay_type && ! empty( $position_to_parent ) ) {
	$data_attributes['data-gb-overlay-position-to-parent'] = $position_to_parent;
}

// Standard overlay specific attributes.
if ( 'standard' === $overlay_type ) {
	// Handle boolean attributes - only add if false (true is default).
	if ( false === $close_on_esc ) {
		$data_attributes['data-gb-overlay-close-on-esc'] = 'false';
	}

	if ( false === $close_on_click_outside ) {
		$data_attributes['data-gb-overlay-close-on-click-outside'] = 'false';
	}

	if ( true === $disable_page_scroll ) {
		$data_attributes['data-gb-overlay-disable-page-scroll'] = 'true';
	}

	if ( ! empty( $position ) && 'center' !== $position ) {
		$data_attributes['data-gb-overlay-position'] = $position;
	}

	if ( true === $hide_if_cookies_disabled ) {
		$data_attributes['data-gb-overlay-hide-if-cookies-disabled'] = 'true';
	}
}

$optional_data_attrs = [
	'animation_in' => 'data-gb-overlay-animation-in',
	'animation_out' => 'data-gb-overlay-animation-out',
	'animation_duration' => 'data-gb-overlay-animation-duration',
	'animation_target' => 'data-gb-overlay-animation-target',
	'animation_distance' => 'data-gb-overlay-animation-distance',
	'hover_buffer' => 'data-gb-overlay-hover-buffer',
];

foreach ( $optional_data_attrs as $var => $attr ) {
	if ( ! empty( $$var ) ) {
		$data_attributes[ $attr ] = $$var;
	}
}

// Handle special case attributes.
if ( 'scroll' === $trigger_type && false !== $scroll_percent && null !== $scroll_percent ) {
	$data_attributes['data-gb-overlay-scroll-percent'] = $scroll_percent;
}

if ( 'time' === $trigger_type && false !== $time_delay && null !== $time_delay ) {
	$data_attributes['data-gb-overlay-time-delay'] = $time_delay;
}

if ( 'custom' === $trigger_type && ! empty( $custom_event ) ) {
	$data_attributes['data-gb-overlay-custom-event'] = $custom_event;
}

if ( 'exit-intent' === $trigger_type || 'time' === $trigger_type || 'scroll' === $trigger_type || 'custom' === $trigger_type ) {
	if ( false !== $cookie_duration && null !== $cookie_duration ) {
		$data_attributes['data-gb-overlay-cookie-duration'] = $cookie_duration;
	}
}

// Build attributes string.
$attributes = [];
foreach ( $data_attributes as $key => $value ) {
	if ( '' === $value ) {
		$attributes[] = $key;
	} else {
		$attributes[] = sprintf( '%s="%s"', $key, esc_attr( $value ) );
	}
}

// Add ARIA attributes.
$attributes[] = 'role="dialog"';
$attributes[] = 'aria-modal="true"';
$attributes[] = 'aria-hidden="true"';

$attributes_string = implode( ' ', $attributes );
?>

<div
	id="<?php echo esc_attr( $html_id ); ?>"
	class="<?php echo esc_attr( $overlay_classes ); ?>"
    <?php echo $attributes_string; // phpcs:ignore ?>
>
	<?php if ( 'standard' === $overlay_type && $backdrop ) : ?>
		<?php
		$backdrop_styles = [];

		if ( ! empty( $backdrop_color ) ) {
			$backdrop_styles[] = 'background-color: ' . esc_attr( $backdrop_color );
		}

		if ( ! empty( $backdrop_blur ) ) {
			// Convert to numeric value and add 'px' if not already present.
			$blur_value = is_numeric( $backdrop_blur ) ? $backdrop_blur . 'px' : $backdrop_blur;
			$backdrop_styles[] = 'backdrop-filter: blur(' . esc_attr( $blur_value ) . ');-webkit-backdrop-filter: blur(' . esc_attr( $blur_value ) . ');';
		}

		$backdrop_style_string = ! empty( $backdrop_styles ) ? ' style="' . implode( '; ', $backdrop_styles ) . '"' : '';
		?>
		<div class="gb-overlay__backdrop"<?php echo $backdrop_style_string; // phpcs:ignore ?>></div>
	<?php endif; ?>

	<div class="gb-overlay__content">
        <?php echo $overlay_content; // phpcs:ignore ?>
	</div>
</div>
