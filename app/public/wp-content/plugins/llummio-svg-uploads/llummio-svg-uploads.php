<?php
/**
 * Plugin Name: Llummio SVG Uploads
 * Description: Lightweight SVG upload support for trusted Llummio blueprint sites.
 * Version: 0.1.0
 * Author: Llummio
 * Text Domain: llummio-svg-uploads
 */

defined( 'ABSPATH' ) || exit;

/**
 * Check whether the current user may upload SVG files.
 */
function llummio_svg_uploads_current_user_can_upload_svg() {
	$capability = apply_filters( 'llummio_svg_uploads_capability', 'upload_files' );

	return current_user_can( $capability );
}

/**
 * Allow SVG files in the Media Library for trusted users.
 *
 * @param array $mimes Allowed mime types.
 */
function llummio_svg_uploads_allow_mime_type( $mimes ) {
	if ( llummio_svg_uploads_current_user_can_upload_svg() ) {
		$mimes['svg'] = 'image/svg+xml';
	}

	return $mimes;
}

add_filter( 'upload_mimes', 'llummio_svg_uploads_allow_mime_type' );

/**
 * Help WordPress validate SVG files during upload.
 *
 * @param array       $data      Filetype and extension data.
 * @param string      $file      Full path to the file.
 * @param string      $filename  Uploaded filename.
 * @param string[]    $mimes     Allowed mime types.
 * @param string|bool $real_mime Real mime type.
 */
function llummio_svg_uploads_check_filetype_and_ext( $data, $file, $filename, $mimes, $real_mime ) {
	if ( ! llummio_svg_uploads_current_user_can_upload_svg() ) {
		return $data;
	}

	if ( 'svg' !== strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) ) ) {
		return $data;
	}

	if ( ! llummio_svg_uploads_is_svg_file( $file ) ) {
		return $data;
	}

	$data['ext']             = 'svg';
	$data['type']            = 'image/svg+xml';
	$data['proper_filename'] = false;

	return $data;
}

add_filter( 'wp_check_filetype_and_ext', 'llummio_svg_uploads_check_filetype_and_ext', 10, 5 );

/**
 * Reject obviously unsafe SVG files before WordPress stores them.
 *
 * This is intentionally small and conservative. It is not a full SVG sanitizer.
 *
 * @param array $file Uploaded file data.
 */
function llummio_svg_uploads_prefilter( $file ) {
	if ( 'svg' !== strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) ) ) {
		return $file;
	}

	if ( ! llummio_svg_uploads_current_user_can_upload_svg() ) {
		$file['error'] = __( 'You are not allowed to upload SVG files.', 'llummio-svg-uploads' );
		return $file;
	}

	if ( empty( $file['tmp_name'] ) || ! llummio_svg_uploads_is_svg_file( $file['tmp_name'] ) ) {
		$file['error'] = __( 'This file does not look like a valid SVG.', 'llummio-svg-uploads' );
		return $file;
	}

	$svg = file_get_contents( $file['tmp_name'], false, null, 0, 2097152 );

	if ( false === $svg || ! llummio_svg_uploads_is_safe_svg_markup( $svg ) ) {
		$file['error'] = __( 'This SVG contains markup that is not allowed.', 'llummio-svg-uploads' );
		return $file;
	}

	return $file;
}

add_filter( 'wp_handle_upload_prefilter', 'llummio_svg_uploads_prefilter' );

/**
 * Check whether a file starts like an SVG document.
 *
 * @param string $file Full path to the file.
 */
function llummio_svg_uploads_is_svg_file( $file ) {
	if ( ! is_readable( $file ) ) {
		return false;
	}

	$contents = file_get_contents( $file, false, null, 0, 4096 );

	if ( false === $contents ) {
		return false;
	}

	return (bool) preg_match( '/<svg[\s>]/i', $contents );
}

/**
 * Perform a small deny-list check for dangerous SVG markup.
 *
 * @param string $svg SVG markup.
 */
function llummio_svg_uploads_is_safe_svg_markup( $svg ) {
	$blocked_patterns = array(
		'/<\s*script\b/i',
		'/<\s*foreignObject\b/i',
		'/<\s*iframe\b/i',
		'/<\s*object\b/i',
		'/<\s*embed\b/i',
		'/\son[a-z]+\s*=/i',
		'/javascript\s*:/i',
		'/data\s*:\s*text\/html/i',
		'/<!ENTITY/i',
		'/<!DOCTYPE/i',
		'/<\?xml-stylesheet/i',
	);

	foreach ( $blocked_patterns as $pattern ) {
		if ( preg_match( $pattern, $svg ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Store SVG dimensions so the Media Library can preview uploaded SVG files.
 *
 * @param array|false $metadata      Attachment metadata.
 * @param int         $attachment_id Attachment ID.
 */
function llummio_svg_uploads_attachment_metadata( $metadata, $attachment_id ) {
	if ( 'image/svg+xml' !== get_post_mime_type( $attachment_id ) ) {
		return $metadata;
	}

	$dimensions = llummio_svg_uploads_get_dimensions( get_attached_file( $attachment_id ) );

	if ( ! $dimensions ) {
		return $metadata;
	}

	$metadata          = is_array( $metadata ) ? $metadata : array();
	$metadata['width'] = $dimensions['width'];
	$metadata['height'] = $dimensions['height'];

	return $metadata;
}

add_filter( 'wp_generate_attachment_metadata', 'llummio_svg_uploads_attachment_metadata', 10, 2 );

/**
 * Add SVG dimensions to Media Library responses.
 *
 * @param array   $response   Attachment response data.
 * @param WP_Post $attachment Attachment post.
 * @param array   $meta       Attachment metadata.
 */
function llummio_svg_uploads_prepare_attachment_for_js( $response, $attachment, $meta ) {
	if ( 'image/svg+xml' !== $response['mime'] || ! empty( $response['sizes'] ) ) {
		return $response;
	}

	$dimensions = llummio_svg_uploads_get_dimensions( get_attached_file( $attachment->ID ) );

	if ( ! $dimensions ) {
		return $response;
	}

	$response['sizes']['full'] = array(
		'url'         => $response['url'],
		'width'       => $dimensions['width'],
		'height'      => $dimensions['height'],
		'orientation' => $dimensions['width'] > $dimensions['height'] ? 'landscape' : 'portrait',
	);

	return $response;
}

add_filter( 'wp_prepare_attachment_for_js', 'llummio_svg_uploads_prepare_attachment_for_js', 10, 3 );

/**
 * Read width and height from an SVG file.
 *
 * @param string $file Full path to the SVG file.
 */
function llummio_svg_uploads_get_dimensions( $file ) {
	if ( ! is_readable( $file ) ) {
		return false;
	}

	$svg = file_get_contents( $file, false, null, 0, 8192 );

	if ( false === $svg || ! preg_match( '/<svg\b[^>]*>/i', $svg, $match ) ) {
		return false;
	}

	$tag    = $match[0];
	$width  = llummio_svg_uploads_get_numeric_attribute( $tag, 'width' );
	$height = llummio_svg_uploads_get_numeric_attribute( $tag, 'height' );

	if ( ( ! $width || ! $height ) && preg_match( '/viewBox=["\']\s*[-\d.]+\s+[-\d.]+\s+([-\d.]+)\s+([-\d.]+)\s*["\']/i', $tag, $viewbox ) ) {
		$width  = $width ? $width : (float) $viewbox[1];
		$height = $height ? $height : (float) $viewbox[2];
	}

	if ( ! $width || ! $height ) {
		return false;
	}

	return array(
		'width'  => (int) round( $width ),
		'height' => (int) round( $height ),
	);
}

/**
 * Extract a numeric SVG attribute value.
 *
 * @param string $tag       SVG opening tag.
 * @param string $attribute Attribute name.
 */
function llummio_svg_uploads_get_numeric_attribute( $tag, $attribute ) {
	if ( ! preg_match( '/' . preg_quote( $attribute, '/' ) . '=["\']\s*([\d.]+)/i', $tag, $match ) ) {
		return 0;
	}

	return (float) $match[1];
}

/**
 * Keep SVG previews tidy in the Media Library.
 */
function llummio_svg_uploads_admin_styles() {
	echo '<style>.attachment img[src$=".svg"],.media-icon img[src$=".svg"]{width:100%;height:auto;}</style>';
}

add_action( 'admin_head-upload.php', 'llummio_svg_uploads_admin_styles' );
add_action( 'admin_head-post.php', 'llummio_svg_uploads_admin_styles' );
add_action( 'admin_head-post-new.php', 'llummio_svg_uploads_admin_styles' );
