<?php
/**
 * Plugin Name: Llummio Editor Helpers
 * Description: Lightweight editor helpers for Llummio blueprint sites, including SEO fields, canonical tags, schema tools, and wireframe preview controls.
 * Version: 0.4.0
 * Author: Llummio
 * Text Domain: llummio-editor-helpers
 */

defined( 'ABSPATH' ) || exit;

const LLUMMIO_EDITOR_HELPERS_TITLE_KEY       = '_llummio_seo_title';
const LLUMMIO_EDITOR_HELPERS_DESCRIPTION_KEY = '_llummio_seo_description';
const LLUMMIO_EDITOR_HELPERS_NOINDEX_KEY     = '_llummio_seo_noindex';
const LLUMMIO_EDITOR_HELPERS_NOFOLLOW_KEY    = '_llummio_seo_nofollow';
const LLUMMIO_EDITOR_HELPERS_SCHEMA_TYPE_KEY = '_llummio_schema_type';
const LLUMMIO_EDITOR_HELPERS_SCHEMA_NAME_KEY = '_llummio_schema_name';
const LLUMMIO_EDITOR_HELPERS_SCHEMA_DESCRIPTION_KEY = '_llummio_schema_description';
const LLUMMIO_EDITOR_HELPERS_SCHEMA_URL_KEY  = '_llummio_schema_url';
const LLUMMIO_EDITOR_HELPERS_SCHEMA_IMAGE_URL_KEY = '_llummio_schema_image_url';
const LLUMMIO_EDITOR_HELPERS_SCHEMA_TELEPHONE_KEY = '_llummio_schema_telephone';
const LLUMMIO_EDITOR_HELPERS_SCHEMA_EMAIL_KEY = '_llummio_schema_email';
const LLUMMIO_EDITOR_HELPERS_SCHEMA_STREET_KEY = '_llummio_schema_street';
const LLUMMIO_EDITOR_HELPERS_SCHEMA_LOCALITY_KEY = '_llummio_schema_locality';
const LLUMMIO_EDITOR_HELPERS_SCHEMA_REGION_KEY = '_llummio_schema_region';
const LLUMMIO_EDITOR_HELPERS_SCHEMA_POSTAL_CODE_KEY = '_llummio_schema_postal_code';
const LLUMMIO_EDITOR_HELPERS_SCHEMA_COUNTRY_KEY = '_llummio_schema_country';
const LLUMMIO_EDITOR_HELPERS_SCHEMA_PRICE_RANGE_KEY = '_llummio_schema_price_range';
const LLUMMIO_EDITOR_HELPERS_SCHEMA_SERVICE_AREA_KEY = '_llummio_schema_service_area';
const LLUMMIO_EDITOR_HELPERS_SCHEMA_PRODUCT_SKU_KEY = '_llummio_schema_product_sku';
const LLUMMIO_EDITOR_HELPERS_SCHEMA_PRODUCT_BRAND_KEY = '_llummio_schema_product_brand';
const LLUMMIO_EDITOR_HELPERS_SCHEMA_PRODUCT_PRICE_KEY = '_llummio_schema_product_price';
const LLUMMIO_EDITOR_HELPERS_SCHEMA_PRODUCT_CURRENCY_KEY = '_llummio_schema_product_currency';
const LLUMMIO_EDITOR_HELPERS_SCHEMA_PRODUCT_AVAILABILITY_KEY = '_llummio_schema_product_availability';
const LLUMMIO_EDITOR_HELPERS_SCHEMA_FAQ_KEY  = '_llummio_schema_faq_items';
const LLUMMIO_EDITOR_HELPERS_SCHEMA_BREADCRUMB_KEY = '_llummio_schema_breadcrumb_items';
const LLUMMIO_EDITOR_HELPERS_REVIEW_ITEM_NAME_KEY = '_llummio_schema_review_item_name';
const LLUMMIO_EDITOR_HELPERS_REVIEW_ITEM_TYPE_KEY = '_llummio_schema_review_item_type';
const LLUMMIO_EDITOR_HELPERS_REVIEW_RATING_KEY    = '_llummio_schema_review_rating';
const LLUMMIO_EDITOR_HELPERS_REVIEW_AUTHOR_KEY    = '_llummio_schema_review_author';
const LLUMMIO_EDITOR_HELPERS_REVIEW_BODY_KEY      = '_llummio_schema_review_body';
const LLUMMIO_EDITOR_HELPERS_VERSION         = '0.4.0';

/**
 * Register SEO metadata for public editable post types.
 */
function llummio_editor_helpers_register_meta() {
	foreach ( llummio_editor_helpers_post_types() as $post_type ) {
		register_post_meta(
			$post_type,
			LLUMMIO_EDITOR_HELPERS_TITLE_KEY,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => 'llummio_editor_helpers_can_edit_meta',
			)
		);

		register_post_meta(
			$post_type,
			LLUMMIO_EDITOR_HELPERS_DESCRIPTION_KEY,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_textarea_field',
				'auth_callback'     => 'llummio_editor_helpers_can_edit_meta',
			)
		);

		foreach ( array( LLUMMIO_EDITOR_HELPERS_NOINDEX_KEY, LLUMMIO_EDITOR_HELPERS_NOFOLLOW_KEY ) as $meta_key ) {
			register_post_meta(
				$post_type,
				$meta_key,
				array(
					'type'              => 'boolean',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'rest_sanitize_boolean',
					'auth_callback'     => 'llummio_editor_helpers_can_edit_meta',
				)
			);
		}

		register_post_meta(
			$post_type,
			LLUMMIO_EDITOR_HELPERS_SCHEMA_TYPE_KEY,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'llummio_editor_helpers_sanitize_schema_type',
				'auth_callback'     => 'llummio_editor_helpers_can_edit_meta',
			)
		);

		register_post_meta(
			$post_type,
			LLUMMIO_EDITOR_HELPERS_SCHEMA_FAQ_KEY,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'llummio_editor_helpers_sanitize_faq_items',
				'auth_callback'     => 'llummio_editor_helpers_can_edit_meta',
			)
		);

		register_post_meta(
			$post_type,
			LLUMMIO_EDITOR_HELPERS_SCHEMA_BREADCRUMB_KEY,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'llummio_editor_helpers_sanitize_breadcrumb_items',
				'auth_callback'     => 'llummio_editor_helpers_can_edit_meta',
			)
		);

		foreach ( llummio_editor_helpers_schema_meta_fields() as $meta_key => $sanitize_callback ) {
			register_post_meta(
				$post_type,
				$meta_key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => $sanitize_callback,
					'auth_callback'     => 'llummio_editor_helpers_can_edit_meta',
				)
			);
		}

		foreach ( llummio_editor_helpers_review_meta_fields() as $meta_key => $sanitize_callback ) {
			register_post_meta(
				$post_type,
				$meta_key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => $sanitize_callback,
					'auth_callback'     => 'llummio_editor_helpers_can_edit_meta',
				)
			);
		}
	}
}

add_action( 'init', 'llummio_editor_helpers_register_meta' );

/**
 * Load the block editor sidebar controls.
 */
function llummio_editor_helpers_enqueue_editor_assets() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen || 'post' !== $screen->base || ! in_array( $screen->post_type, llummio_editor_helpers_post_types(), true ) ) {
		return;
	}

	$script_path = plugin_dir_path( __FILE__ ) . 'assets/editor-sidebar.js';
	$style_path  = plugin_dir_path( __FILE__ ) . 'assets/editor-sidebar.css';
	$version     = file_exists( $script_path ) ? (string) filemtime( $script_path ) : LLUMMIO_EDITOR_HELPERS_VERSION;

	wp_enqueue_script(
		'llummio-editor-helpers-editor-sidebar',
		plugin_dir_url( __FILE__ ) . 'assets/editor-sidebar.js',
		array( 'wp-components', 'wp-data', 'wp-edit-post', 'wp-editor', 'wp-element', 'wp-i18n', 'wp-plugins' ),
		$version,
		true
	);

	if ( file_exists( $style_path ) ) {
		wp_enqueue_style(
			'llummio-editor-helpers-editor-sidebar',
			plugin_dir_url( __FILE__ ) . 'assets/editor-sidebar.css',
			array( 'wp-components' ),
			(string) filemtime( $style_path )
		);
	}
}

add_action( 'enqueue_block_editor_assets', 'llummio_editor_helpers_enqueue_editor_assets' );

/**
 * Return the post types where SEO fields should appear.
 */
function llummio_editor_helpers_post_types() {
	$post_types = get_post_types(
		array(
			'public'  => true,
			'show_ui' => true,
		),
		'names'
	);

	unset( $post_types['attachment'] );

	return apply_filters( 'llummio_editor_helpers_post_types', array_values( $post_types ) );
}

/**
 * Check whether the current user can edit SEO fields.
 *
 * @param bool   $allowed Whether the user can edit.
 * @param string $meta_key Meta key.
 * @param int    $post_id  Post ID.
 * @param int    $user_id  User ID.
 */
function llummio_editor_helpers_can_edit_meta( $allowed, $meta_key, $post_id, $user_id = 0 ) {
	return user_can( $user_id ? $user_id : get_current_user_id(), 'edit_post', $post_id );
}

/**
 * Remove empty SEO values after the editor saves metadata.
 *
 * @param int $post_id Post ID.
 */
function llummio_editor_helpers_delete_empty_meta( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || ! in_array( get_post_type( $post_id ), llummio_editor_helpers_post_types(), true ) ) {
		return;
	}

	foreach ( llummio_editor_helpers_empty_string_meta_keys() as $meta_key ) {
		if ( '' === get_post_meta( $post_id, $meta_key, true ) ) {
			delete_post_meta( $post_id, $meta_key );
		}
	}

	if ( 'default' === get_post_meta( $post_id, LLUMMIO_EDITOR_HELPERS_SCHEMA_TYPE_KEY, true ) ) {
		delete_post_meta( $post_id, LLUMMIO_EDITOR_HELPERS_SCHEMA_TYPE_KEY );
	}

	foreach ( array( LLUMMIO_EDITOR_HELPERS_NOINDEX_KEY, LLUMMIO_EDITOR_HELPERS_NOFOLLOW_KEY ) as $meta_key ) {
		if ( ! llummio_editor_helpers_get_boolean_meta( $post_id, $meta_key ) ) {
			delete_post_meta( $post_id, $meta_key );
		}
	}
}

add_action( 'save_post', 'llummio_editor_helpers_delete_empty_meta', 20 );

/**
 * Make sure themes can use WordPress-managed document titles.
 */
function llummio_editor_helpers_add_title_support() {
	add_theme_support( 'title-tag' );
}

add_action( 'after_setup_theme', 'llummio_editor_helpers_add_title_support' );

/**
 * Override the frontend document title for singular content.
 *
 * @param string $title Existing document title.
 */
function llummio_editor_helpers_document_title( $title ) {
	if ( llummio_editor_helpers_should_skip_frontend_output() || ! is_singular() ) {
		return $title;
	}

	$seo_title = llummio_editor_helpers_get_meta( get_queried_object_id(), LLUMMIO_EDITOR_HELPERS_TITLE_KEY );

	return '' !== $seo_title ? llummio_editor_helpers_replace_title_tokens( $seo_title ) : $title;
}

add_filter( 'pre_get_document_title', 'llummio_editor_helpers_document_title' );

/**
 * Replace supported SEO title tokens.
 *
 * @param string $title SEO title.
 */
function llummio_editor_helpers_replace_title_tokens( $title ) {
	$separator = apply_filters( 'document_title_separator', '-' );
	$site_name = get_bloginfo( 'name' );

	$title = str_replace(
		array( '%sep%', '%sitename%' ),
		array( $separator, $site_name ),
		$title
	);

	return trim( preg_replace( '/\s+/', ' ', $title ) );
}

/**
 * Output the frontend meta description for singular content.
 */
function llummio_editor_helpers_meta_description() {
	if ( llummio_editor_helpers_should_skip_frontend_output() || ! is_singular() ) {
		return;
	}

	$description = llummio_editor_helpers_get_meta( get_queried_object_id(), LLUMMIO_EDITOR_HELPERS_DESCRIPTION_KEY );

	if ( '' === $description ) {
		return;
	}

	printf(
		'<meta name="description" content="%s" />' . "\n",
		esc_attr( $description )
	);
}

add_action( 'wp_head', 'llummio_editor_helpers_meta_description', 1 );

/**
 * Output page-level robots controls for singular content.
 */
function llummio_editor_helpers_robots_meta() {
	if ( llummio_editor_helpers_should_skip_frontend_output() || ! is_singular() ) {
		return;
	}

	$post_id = get_queried_object_id();
	$rules   = array();

	if ( llummio_editor_helpers_get_boolean_meta( $post_id, LLUMMIO_EDITOR_HELPERS_NOINDEX_KEY ) ) {
		$rules[] = 'noindex';
	}

	if ( llummio_editor_helpers_get_boolean_meta( $post_id, LLUMMIO_EDITOR_HELPERS_NOFOLLOW_KEY ) ) {
		$rules[] = 'nofollow';
	}

	if ( empty( $rules ) ) {
		return;
	}

	printf(
		'<meta name="robots" content="%s" />' . "\n",
		esc_attr( implode( ', ', $rules ) )
	);
}

add_action( 'wp_head', 'llummio_editor_helpers_robots_meta', 1 );

/**
 * Replace the WordPress default canonical tag when this plugin handles SEO output.
 */
function llummio_editor_helpers_prepare_canonical_link() {
	if ( llummio_editor_helpers_should_skip_frontend_output() || ! is_singular() ) {
		return;
	}

	remove_action( 'wp_head', 'rel_canonical' );
	add_action( 'wp_head', 'llummio_editor_helpers_canonical_link', 1 );
}

add_action( 'wp', 'llummio_editor_helpers_prepare_canonical_link' );

/**
 * Output one canonical link for singular content.
 */
function llummio_editor_helpers_canonical_link() {
	if ( llummio_editor_helpers_should_skip_frontend_output() || ! is_singular() ) {
		return;
	}

	$url = llummio_editor_helpers_get_canonical_url( get_queried_object_id() );

	if ( '' === $url ) {
		return;
	}

	printf(
		'<link rel="canonical" href="%s" />' . "\n",
		esc_url( $url )
	);
}

/**
 * Return the canonical URL for singular content.
 *
 * @param int $post_id Post ID.
 */
function llummio_editor_helpers_get_canonical_url( $post_id ) {
	if ( ! $post_id ) {
		return '';
	}

	$url = function_exists( 'wp_get_canonical_url' ) ? wp_get_canonical_url( $post_id ) : get_permalink( $post_id );

	return (string) apply_filters( 'llummio_editor_helpers_canonical_url', $url ? $url : '', $post_id );
}

/**
 * Output JSON-LD structured data for singular content.
 */
function llummio_editor_helpers_structured_data() {
	if ( llummio_editor_helpers_should_skip_schema_output() || ! is_singular() ) {
		return;
	}

	$post_id     = get_queried_object_id();
	$schema_type = llummio_editor_helpers_get_schema_type( $post_id );

	if ( ! $post_id || 'none' === $schema_type || llummio_editor_helpers_get_boolean_meta( $post_id, LLUMMIO_EDITOR_HELPERS_NOINDEX_KEY ) ) {
		return;
	}

	$graph = array_filter(
		array(
			llummio_editor_helpers_get_organization_schema(),
			llummio_editor_helpers_get_website_schema(),
			llummio_editor_helpers_get_page_schema( $post_id, $schema_type ),
			llummio_editor_helpers_get_selected_schema( $post_id, $schema_type ),
			'faq' === $schema_type ? llummio_editor_helpers_get_faq_schema( $post_id ) : null,
			'review' === $schema_type ? llummio_editor_helpers_get_review_schema( $post_id ) : null,
		)
	);

	if ( empty( $graph ) ) {
		return;
	}

	$schema = apply_filters(
		'llummio_editor_helpers_structured_data',
		array(
			'@context' => 'https://schema.org',
			'@graph'   => array_values( $graph ),
		),
		$post_id
	);

	if ( empty( $schema['@graph'] ) ) {
		return;
	}

	printf(
		'<script type="application/ld+json">%s</script>' . "\n",
		wp_json_encode( $schema, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
	);
}

add_action( 'wp_head', 'llummio_editor_helpers_structured_data', 2 );

/**
 * Return a trimmed SEO field value.
 *
 * @param int    $post_id  Post ID.
 * @param string $meta_key Meta key.
 */
function llummio_editor_helpers_get_meta( $post_id, $meta_key ) {
	if ( ! $post_id ) {
		return '';
	}

	return trim( (string) get_post_meta( $post_id, $meta_key, true ) );
}

/**
 * Return a boolean SEO field value.
 *
 * @param int    $post_id  Post ID.
 * @param string $meta_key Meta key.
 */
function llummio_editor_helpers_get_boolean_meta( $post_id, $meta_key ) {
	if ( ! $post_id ) {
		return false;
	}

	return (bool) get_post_meta( $post_id, $meta_key, true );
}

/**
 * Return review meta fields and their sanitizer callbacks.
 */
function llummio_editor_helpers_review_meta_fields() {
	return array(
		LLUMMIO_EDITOR_HELPERS_REVIEW_ITEM_NAME_KEY => 'sanitize_text_field',
		LLUMMIO_EDITOR_HELPERS_REVIEW_ITEM_TYPE_KEY => 'llummio_editor_helpers_sanitize_review_item_type',
		LLUMMIO_EDITOR_HELPERS_REVIEW_RATING_KEY    => 'llummio_editor_helpers_sanitize_rating',
		LLUMMIO_EDITOR_HELPERS_REVIEW_AUTHOR_KEY    => 'sanitize_text_field',
		LLUMMIO_EDITOR_HELPERS_REVIEW_BODY_KEY      => 'sanitize_textarea_field',
	);
}

/**
 * Return general schema meta fields and their sanitizer callbacks.
 */
function llummio_editor_helpers_schema_meta_fields() {
	return array(
		LLUMMIO_EDITOR_HELPERS_SCHEMA_NAME_KEY                 => 'sanitize_text_field',
		LLUMMIO_EDITOR_HELPERS_SCHEMA_DESCRIPTION_KEY          => 'sanitize_textarea_field',
		LLUMMIO_EDITOR_HELPERS_SCHEMA_URL_KEY                  => 'esc_url_raw',
		LLUMMIO_EDITOR_HELPERS_SCHEMA_IMAGE_URL_KEY            => 'esc_url_raw',
		LLUMMIO_EDITOR_HELPERS_SCHEMA_TELEPHONE_KEY            => 'sanitize_text_field',
		LLUMMIO_EDITOR_HELPERS_SCHEMA_EMAIL_KEY                => 'sanitize_email',
		LLUMMIO_EDITOR_HELPERS_SCHEMA_STREET_KEY               => 'sanitize_text_field',
		LLUMMIO_EDITOR_HELPERS_SCHEMA_LOCALITY_KEY             => 'sanitize_text_field',
		LLUMMIO_EDITOR_HELPERS_SCHEMA_REGION_KEY               => 'sanitize_text_field',
		LLUMMIO_EDITOR_HELPERS_SCHEMA_POSTAL_CODE_KEY          => 'sanitize_text_field',
		LLUMMIO_EDITOR_HELPERS_SCHEMA_COUNTRY_KEY              => 'sanitize_text_field',
		LLUMMIO_EDITOR_HELPERS_SCHEMA_PRICE_RANGE_KEY          => 'sanitize_text_field',
		LLUMMIO_EDITOR_HELPERS_SCHEMA_SERVICE_AREA_KEY         => 'sanitize_text_field',
		LLUMMIO_EDITOR_HELPERS_SCHEMA_PRODUCT_SKU_KEY          => 'sanitize_text_field',
		LLUMMIO_EDITOR_HELPERS_SCHEMA_PRODUCT_BRAND_KEY        => 'sanitize_text_field',
		LLUMMIO_EDITOR_HELPERS_SCHEMA_PRODUCT_PRICE_KEY        => 'llummio_editor_helpers_sanitize_product_price',
		LLUMMIO_EDITOR_HELPERS_SCHEMA_PRODUCT_CURRENCY_KEY     => 'llummio_editor_helpers_sanitize_currency',
		LLUMMIO_EDITOR_HELPERS_SCHEMA_PRODUCT_AVAILABILITY_KEY => 'llummio_editor_helpers_sanitize_product_availability',
	);
}

/**
 * Return string post meta keys that should not store empty values.
 */
function llummio_editor_helpers_empty_string_meta_keys() {
	return array_merge(
		array(
			LLUMMIO_EDITOR_HELPERS_TITLE_KEY,
			LLUMMIO_EDITOR_HELPERS_DESCRIPTION_KEY,
			LLUMMIO_EDITOR_HELPERS_SCHEMA_TYPE_KEY,
			LLUMMIO_EDITOR_HELPERS_SCHEMA_FAQ_KEY,
			LLUMMIO_EDITOR_HELPERS_SCHEMA_BREADCRUMB_KEY,
		),
		array_keys( llummio_editor_helpers_schema_meta_fields() ),
		array_keys( llummio_editor_helpers_review_meta_fields() )
	);
}

/**
 * Return allowed page schema modes.
 */
function llummio_editor_helpers_schema_types() {
	return array(
		'default',
		'none',
		'webpage',
		'organization',
		'localbusiness',
		'professionalservice',
		'person',
		'service',
		'article',
		'faq',
		'breadcrumb',
		'review',
		'product',
	);
}

/**
 * Return allowed review item types.
 */
function llummio_editor_helpers_review_item_types() {
	return array( 'Product', 'Book', 'Course', 'Event', 'Movie', 'Recipe', 'SoftwareApplication', 'LocalBusiness', 'ProfessionalService', 'Organization', 'Person', 'Service' );
}

/**
 * Sanitize the page schema mode.
 *
 * @param string $value Schema mode.
 */
function llummio_editor_helpers_sanitize_schema_type( $value ) {
	$value = sanitize_key( $value );

	return in_array( $value, llummio_editor_helpers_schema_types(), true ) ? $value : 'default';
}

/**
 * Sanitize review item type.
 *
 * @param string $value Review item type.
 */
function llummio_editor_helpers_sanitize_review_item_type( $value ) {
	return in_array( $value, llummio_editor_helpers_review_item_types(), true ) ? $value : 'Product';
}

/**
 * Sanitize a 1-5 rating value.
 *
 * @param string $value Rating value.
 */
function llummio_editor_helpers_sanitize_rating( $value ) {
	if ( '' === trim( (string) $value ) || ! is_numeric( $value ) ) {
		return '';
	}

	$rating = min( 5, max( 1, (float) $value ) );

	return rtrim( rtrim( number_format( $rating, 1, '.', '' ), '0' ), '.' );
}

/**
 * Sanitize a product price value.
 *
 * @param string $value Product price.
 */
function llummio_editor_helpers_sanitize_product_price( $value ) {
	if ( '' === trim( (string) $value ) || ! is_numeric( $value ) ) {
		return '';
	}

	return rtrim( rtrim( number_format( max( 0, (float) $value ), 2, '.', '' ), '0' ), '.' );
}

/**
 * Sanitize a currency code.
 *
 * @param string $value Currency code.
 */
function llummio_editor_helpers_sanitize_currency( $value ) {
	$value = strtoupper( preg_replace( '/[^A-Z]/i', '', (string) $value ) );

	return 3 === strlen( $value ) ? $value : '';
}

/**
 * Sanitize product availability.
 *
 * @param string $value Product availability.
 */
function llummio_editor_helpers_sanitize_product_availability( $value ) {
	$value   = sanitize_key( $value );
	$allowed = array( 'instock', 'outofstock', 'preorder', 'backorder' );

	return in_array( $value, $allowed, true ) ? $value : '';
}

/**
 * Sanitize FAQ items stored as a JSON string.
 *
 * @param string $value FAQ JSON.
 */
function llummio_editor_helpers_sanitize_faq_items( $value ) {
	$items = json_decode( (string) $value, true );

	if ( ! is_array( $items ) ) {
		return '';
	}

	$clean_items = array();

	foreach ( array_slice( $items, 0, 10 ) as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		$question = isset( $item['question'] ) ? sanitize_text_field( $item['question'] ) : '';
		$answer   = isset( $item['answer'] ) ? sanitize_textarea_field( $item['answer'] ) : '';

		if ( '' === $question || '' === $answer ) {
			continue;
		}

		$clean_items[] = array(
			'question' => $question,
			'answer'   => $answer,
		);
	}

	return empty( $clean_items ) ? '' : wp_json_encode( $clean_items );
}

/**
 * Sanitize breadcrumb items stored as a JSON string.
 *
 * @param string $value Breadcrumb JSON.
 */
function llummio_editor_helpers_sanitize_breadcrumb_items( $value ) {
	$items = json_decode( (string) $value, true );

	if ( ! is_array( $items ) ) {
		return '';
	}

	$clean_items = array();

	foreach ( array_slice( $items, 0, 10 ) as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		$name = isset( $item['name'] ) ? sanitize_text_field( $item['name'] ) : '';
		$url  = isset( $item['url'] ) ? esc_url_raw( $item['url'] ) : '';

		if ( '' === $name ) {
			continue;
		}

		$clean_items[] = array(
			'name' => $name,
			'url'  => $url,
		);
	}

	return empty( $clean_items ) ? '' : wp_json_encode( $clean_items );
}

/**
 * Return the schema mode for a post.
 *
 * @param int $post_id Post ID.
 */
function llummio_editor_helpers_get_schema_type( $post_id ) {
	$type = llummio_editor_helpers_get_meta( $post_id, LLUMMIO_EDITOR_HELPERS_SCHEMA_TYPE_KEY );

	return in_array( $type, llummio_editor_helpers_schema_types(), true ) ? $type : 'default';
}

/**
 * Return schema-ready FAQ items.
 *
 * @param int $post_id Post ID.
 */
function llummio_editor_helpers_get_faq_items( $post_id ) {
	$items = json_decode( (string) get_post_meta( $post_id, LLUMMIO_EDITOR_HELPERS_SCHEMA_FAQ_KEY, true ), true );

	return is_array( $items ) ? $items : array();
}

/**
 * Return schema-ready breadcrumb items.
 *
 * @param int $post_id Post ID.
 */
function llummio_editor_helpers_get_breadcrumb_items( $post_id ) {
	$items = json_decode( (string) get_post_meta( $post_id, LLUMMIO_EDITOR_HELPERS_SCHEMA_BREADCRUMB_KEY, true ), true );

	if ( is_array( $items ) && ! empty( $items ) ) {
		return $items;
	}

	$items = array(
		array(
			'name' => get_bloginfo( 'name' ),
			'url'  => home_url( '/' ),
		),
	);

	if ( 'page' === get_post_type( $post_id ) ) {
		$ancestors = array_reverse( get_post_ancestors( $post_id ) );

		foreach ( $ancestors as $ancestor_id ) {
			$items[] = array(
				'name' => get_the_title( $ancestor_id ),
				'url'  => get_permalink( $ancestor_id ),
			);
		}
	}

	$items[] = array(
		'name' => get_the_title( $post_id ),
		'url'  => get_permalink( $post_id ),
	);

	return $items;
}

/**
 * Return the organization node for the current site.
 */
function llummio_editor_helpers_get_organization_schema() {
	$schema = array(
		'@type' => 'Organization',
		'@id'   => home_url( '/#organization' ),
		'name'  => get_bloginfo( 'name' ),
		'url'   => home_url( '/' ),
	);

	$logo_id = (int) get_theme_mod( 'custom_logo' );

	if ( $logo_id ) {
		$logo_url = wp_get_attachment_image_url( $logo_id, 'full' );

		if ( $logo_url ) {
			$schema['logo'] = array(
				'@type' => 'ImageObject',
				'url'   => $logo_url,
			);
		}
	}

	return $schema;
}

/**
 * Return the selected page-level schema node.
 *
 * @param int    $post_id     Post ID.
 * @param string $schema_type Page schema mode.
 */
function llummio_editor_helpers_get_selected_schema( $post_id, $schema_type ) {
	switch ( $schema_type ) {
		case 'organization':
			return llummio_editor_helpers_get_entity_schema( $post_id, 'Organization' );
		case 'localbusiness':
			return llummio_editor_helpers_get_business_schema( $post_id, 'LocalBusiness' );
		case 'professionalservice':
			return llummio_editor_helpers_get_business_schema( $post_id, 'ProfessionalService' );
		case 'person':
			return llummio_editor_helpers_get_person_schema( $post_id );
		case 'service':
			return llummio_editor_helpers_get_service_schema( $post_id );
		case 'breadcrumb':
			return llummio_editor_helpers_get_breadcrumb_schema( $post_id );
		case 'product':
			return llummio_editor_helpers_get_product_schema( $post_id );
	}

	return null;
}

/**
 * Return a common entity schema node.
 *
 * @param int    $post_id Post ID.
 * @param string $type    Schema type.
 */
function llummio_editor_helpers_get_entity_schema( $post_id, $type ) {
	$name = llummio_editor_helpers_get_schema_entity_name( $post_id );

	if ( '' === $name ) {
		return null;
	}

	$schema = array(
		'@type'       => $type,
		'@id'         => llummio_editor_helpers_get_selected_schema_id( $post_id, strtolower( $type ) ),
		'name'        => $name,
		'url'         => llummio_editor_helpers_get_schema_entity_url( $post_id ),
		'description' => llummio_editor_helpers_get_schema_entity_description( $post_id ),
	);

	$image = llummio_editor_helpers_get_schema_entity_image( $post_id );

	if ( $image ) {
		$schema['image'] = $image;
	}

	return array_filter( $schema );
}

/**
 * Return business schema.
 *
 * @param int    $post_id Post ID.
 * @param string $type    Business schema type.
 */
function llummio_editor_helpers_get_business_schema( $post_id, $type ) {
	$schema = llummio_editor_helpers_get_entity_schema( $post_id, $type );

	if ( ! $schema ) {
		return null;
	}

	$telephone   = llummio_editor_helpers_get_meta( $post_id, LLUMMIO_EDITOR_HELPERS_SCHEMA_TELEPHONE_KEY );
	$email       = llummio_editor_helpers_get_meta( $post_id, LLUMMIO_EDITOR_HELPERS_SCHEMA_EMAIL_KEY );
	$price_range = llummio_editor_helpers_get_meta( $post_id, LLUMMIO_EDITOR_HELPERS_SCHEMA_PRICE_RANGE_KEY );
	$address     = llummio_editor_helpers_get_address_schema( $post_id );

	if ( $telephone ) {
		$schema['telephone'] = $telephone;
	}

	if ( $email ) {
		$schema['email'] = $email;
	}

	if ( $price_range ) {
		$schema['priceRange'] = $price_range;
	}

	if ( $address ) {
		$schema['address'] = $address;
	}

	return $schema;
}

/**
 * Return person schema.
 *
 * @param int $post_id Post ID.
 */
function llummio_editor_helpers_get_person_schema( $post_id ) {
	$schema = llummio_editor_helpers_get_entity_schema( $post_id, 'Person' );

	if ( ! $schema ) {
		return null;
	}

	$email = llummio_editor_helpers_get_meta( $post_id, LLUMMIO_EDITOR_HELPERS_SCHEMA_EMAIL_KEY );

	if ( $email ) {
		$schema['email'] = $email;
	}

	return $schema;
}

/**
 * Return service schema.
 *
 * @param int $post_id Post ID.
 */
function llummio_editor_helpers_get_service_schema( $post_id ) {
	$schema = llummio_editor_helpers_get_entity_schema( $post_id, 'Service' );

	if ( ! $schema ) {
		return null;
	}

	$service_area = llummio_editor_helpers_get_meta( $post_id, LLUMMIO_EDITOR_HELPERS_SCHEMA_SERVICE_AREA_KEY );

	$schema['provider'] = array(
		'@id' => home_url( '/#organization' ),
	);

	if ( $service_area ) {
		$schema['areaServed'] = $service_area;
	}

	return $schema;
}

/**
 * Return product schema.
 *
 * @param int $post_id Post ID.
 */
function llummio_editor_helpers_get_product_schema( $post_id ) {
	$schema = llummio_editor_helpers_get_entity_schema( $post_id, 'Product' );

	if ( ! $schema ) {
		return null;
	}

	$sku          = llummio_editor_helpers_get_meta( $post_id, LLUMMIO_EDITOR_HELPERS_SCHEMA_PRODUCT_SKU_KEY );
	$brand        = llummio_editor_helpers_get_meta( $post_id, LLUMMIO_EDITOR_HELPERS_SCHEMA_PRODUCT_BRAND_KEY );
	$price        = llummio_editor_helpers_get_meta( $post_id, LLUMMIO_EDITOR_HELPERS_SCHEMA_PRODUCT_PRICE_KEY );
	$currency     = llummio_editor_helpers_get_meta( $post_id, LLUMMIO_EDITOR_HELPERS_SCHEMA_PRODUCT_CURRENCY_KEY );
	$availability = llummio_editor_helpers_get_meta( $post_id, LLUMMIO_EDITOR_HELPERS_SCHEMA_PRODUCT_AVAILABILITY_KEY );

	if ( $sku ) {
		$schema['sku'] = $sku;
	}

	if ( $brand ) {
		$schema['brand'] = array(
			'@type' => 'Brand',
			'name'  => $brand,
		);
	}

	if ( $price && $currency ) {
		$schema['offers'] = array(
			'@type'         => 'Offer',
			'url'           => llummio_editor_helpers_get_schema_entity_url( $post_id ),
			'price'         => $price,
			'priceCurrency' => $currency,
		);

		if ( $availability ) {
			$schema['offers']['availability'] = 'https://schema.org/' . llummio_editor_helpers_get_product_availability_label( $availability );
		}
	}

	return $schema;
}

/**
 * Return BreadcrumbList schema.
 *
 * @param int $post_id Post ID.
 */
function llummio_editor_helpers_get_breadcrumb_schema( $post_id ) {
	$items = llummio_editor_helpers_get_breadcrumb_items( $post_id );

	if ( count( $items ) < 2 ) {
		return null;
	}

	return array(
		'@type'           => 'BreadcrumbList',
		'@id'             => llummio_editor_helpers_get_selected_schema_id( $post_id, 'breadcrumb' ),
		'itemListElement' => array_values(
			array_map(
				function( $item, $index ) use ( $post_id ) {
					$list_item = array(
						'@type'    => 'ListItem',
						'position' => $index + 1,
						'name'     => $item['name'],
					);

					$url = ! empty( $item['url'] ) ? $item['url'] : get_permalink( $post_id );

					if ( $url ) {
						$list_item['item'] = $url;
					}

					return $list_item;
				},
				$items,
				array_keys( $items )
			)
		),
	);
}

/**
 * Return a stable selected schema node ID.
 *
 * @param int    $post_id Post ID.
 * @param string $slug    Schema slug.
 */
function llummio_editor_helpers_get_selected_schema_id( $post_id, $slug ) {
	return get_permalink( $post_id ) . '#' . sanitize_key( $slug );
}

/**
 * Return the selected entity ID for WebPage mainEntity.
 *
 * @param int    $post_id     Post ID.
 * @param string $schema_type Page schema mode.
 */
function llummio_editor_helpers_get_main_entity_id( $post_id, $schema_type ) {
	$entity_slugs = array(
		'organization'        => 'organization',
		'localbusiness'       => 'localbusiness',
		'professionalservice' => 'professionalservice',
		'person'              => 'person',
		'service'             => 'service',
		'product'             => 'product',
	);

	return isset( $entity_slugs[ $schema_type ] ) ? llummio_editor_helpers_get_selected_schema_id( $post_id, $entity_slugs[ $schema_type ] ) : '';
}

/**
 * Return the selected schema name.
 *
 * @param int $post_id Post ID.
 */
function llummio_editor_helpers_get_schema_entity_name( $post_id ) {
	$name = llummio_editor_helpers_get_meta( $post_id, LLUMMIO_EDITOR_HELPERS_SCHEMA_NAME_KEY );

	return '' !== $name ? $name : get_the_title( $post_id );
}

/**
 * Return the selected schema description.
 *
 * @param int $post_id Post ID.
 */
function llummio_editor_helpers_get_schema_entity_description( $post_id ) {
	$description = llummio_editor_helpers_get_meta( $post_id, LLUMMIO_EDITOR_HELPERS_SCHEMA_DESCRIPTION_KEY );

	return '' !== $description ? $description : llummio_editor_helpers_get_schema_description( $post_id );
}

/**
 * Return the selected schema URL.
 *
 * @param int $post_id Post ID.
 */
function llummio_editor_helpers_get_schema_entity_url( $post_id ) {
	$url = llummio_editor_helpers_get_meta( $post_id, LLUMMIO_EDITOR_HELPERS_SCHEMA_URL_KEY );

	return '' !== $url ? $url : get_permalink( $post_id );
}

/**
 * Return the selected schema image URL.
 *
 * @param int $post_id Post ID.
 */
function llummio_editor_helpers_get_schema_entity_image( $post_id ) {
	$image = llummio_editor_helpers_get_meta( $post_id, LLUMMIO_EDITOR_HELPERS_SCHEMA_IMAGE_URL_KEY );

	if ( $image ) {
		return $image;
	}

	$image = get_the_post_thumbnail_url( $post_id, 'full' );

	if ( $image ) {
		return $image;
	}

	$logo_id = (int) get_theme_mod( 'custom_logo' );

	return $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';
}

/**
 * Return PostalAddress schema when enough address data exists.
 *
 * @param int $post_id Post ID.
 */
function llummio_editor_helpers_get_address_schema( $post_id ) {
	$fields = array(
		'streetAddress'   => llummio_editor_helpers_get_meta( $post_id, LLUMMIO_EDITOR_HELPERS_SCHEMA_STREET_KEY ),
		'addressLocality' => llummio_editor_helpers_get_meta( $post_id, LLUMMIO_EDITOR_HELPERS_SCHEMA_LOCALITY_KEY ),
		'addressRegion'   => llummio_editor_helpers_get_meta( $post_id, LLUMMIO_EDITOR_HELPERS_SCHEMA_REGION_KEY ),
		'postalCode'      => llummio_editor_helpers_get_meta( $post_id, LLUMMIO_EDITOR_HELPERS_SCHEMA_POSTAL_CODE_KEY ),
		'addressCountry'  => llummio_editor_helpers_get_meta( $post_id, LLUMMIO_EDITOR_HELPERS_SCHEMA_COUNTRY_KEY ),
	);

	$fields = array_filter( $fields );

	if ( empty( $fields ) ) {
		return null;
	}

	return array_merge(
		array(
			'@type' => 'PostalAddress',
		),
		$fields
	);
}

/**
 * Return Schema.org availability label.
 *
 * @param string $availability Stored availability slug.
 */
function llummio_editor_helpers_get_product_availability_label( $availability ) {
	$labels = array(
		'instock'    => 'InStock',
		'outofstock' => 'OutOfStock',
		'preorder'   => 'PreOrder',
		'backorder'  => 'BackOrder',
	);

	return isset( $labels[ $availability ] ) ? $labels[ $availability ] : '';
}

/**
 * Return the website node for the current site.
 */
function llummio_editor_helpers_get_website_schema() {
	return array(
		'@type'     => 'WebSite',
		'@id'       => home_url( '/#website' ),
		'url'       => home_url( '/' ),
		'name'      => get_bloginfo( 'name' ),
		'publisher' => array(
			'@id' => home_url( '/#organization' ),
		),
	);
}

/**
 * Return the page node for singular content.
 *
 * @param int    $post_id     Post ID.
 * @param string $schema_type Page schema mode.
 */
function llummio_editor_helpers_get_page_schema( $post_id, $schema_type ) {
	$permalink = get_permalink( $post_id );
	$type      = llummio_editor_helpers_get_page_schema_type( $post_id, $schema_type );
	$title     = llummio_editor_helpers_get_schema_title( $post_id );
	$schema    = array(
		'@type'      => $type,
		'@id'        => $permalink . '#webpage',
		'url'        => $permalink,
		'name'       => $title,
		'isPartOf'   => array(
			'@id' => home_url( '/#website' ),
		),
		'publisher'  => array(
			'@id' => home_url( '/#organization' ),
		),
		'inLanguage' => get_bloginfo( 'language' ),
	);

	$description = llummio_editor_helpers_get_schema_description( $post_id );

	if ( $description ) {
		$schema['description'] = $description;
	}

	$main_entity_id = llummio_editor_helpers_get_main_entity_id( $post_id, $schema_type );

	if ( $main_entity_id ) {
		$schema['mainEntity'] = array(
			'@id' => $main_entity_id,
		);
	}

	if ( 'breadcrumb' === $schema_type ) {
		$schema['breadcrumb'] = array(
			'@id' => llummio_editor_helpers_get_selected_schema_id( $post_id, 'breadcrumb' ),
		);
	}

	if ( in_array( $type, array( 'Article', 'BlogPosting' ), true ) ) {
		$schema['headline']      = $title;
		$schema['datePublished'] = get_the_date( DATE_W3C, $post_id );
		$schema['dateModified']  = get_the_modified_date( DATE_W3C, $post_id );
		$schema['author']        = array(
			'@type' => 'Person',
			'name'  => get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) ),
		);

		$image_url = get_the_post_thumbnail_url( $post_id, 'full' );

		if ( $image_url ) {
			$schema['image'] = array( $image_url );
		}
	}

	return $schema;
}

/**
 * Return the concrete page schema type.
 *
 * @param int    $post_id     Post ID.
 * @param string $schema_type Page schema mode.
 */
function llummio_editor_helpers_get_page_schema_type( $post_id, $schema_type ) {
	if ( 'article' === $schema_type ) {
		return 'Article';
	}

	if ( 'default' === $schema_type && 'post' === get_post_type( $post_id ) ) {
		return 'BlogPosting';
	}

	return 'WebPage';
}

/**
 * Return the title used in schema output.
 *
 * @param int $post_id Post ID.
 */
function llummio_editor_helpers_get_schema_title( $post_id ) {
	$title = llummio_editor_helpers_get_meta( $post_id, LLUMMIO_EDITOR_HELPERS_TITLE_KEY );

	return '' !== $title ? llummio_editor_helpers_replace_title_tokens( $title ) : get_the_title( $post_id );
}

/**
 * Return the description used in schema output.
 *
 * @param int $post_id Post ID.
 */
function llummio_editor_helpers_get_schema_description( $post_id ) {
	$description = llummio_editor_helpers_get_meta( $post_id, LLUMMIO_EDITOR_HELPERS_DESCRIPTION_KEY );

	if ( '' !== $description ) {
		return $description;
	}

	return wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post_id ) ), 40, '' );
}

/**
 * Return FAQPage structured data when the page has complete FAQ items.
 *
 * @param int $post_id Post ID.
 */
function llummio_editor_helpers_get_faq_schema( $post_id ) {
	$items = llummio_editor_helpers_get_faq_items( $post_id );

	if ( empty( $items ) ) {
		return null;
	}

	return array(
		'@type'      => 'FAQPage',
		'@id'        => get_permalink( $post_id ) . '#faq',
		'mainEntity' => array_map(
			function( $item ) {
				return array(
					'@type'          => 'Question',
					'name'           => $item['question'],
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => $item['answer'],
					),
				);
			},
			$items
		),
	);
}

/**
 * Return Review structured data when the page has complete review details.
 *
 * @param int $post_id Post ID.
 */
function llummio_editor_helpers_get_review_schema( $post_id ) {
	$item_name = llummio_editor_helpers_get_meta( $post_id, LLUMMIO_EDITOR_HELPERS_REVIEW_ITEM_NAME_KEY );
	$item_type = llummio_editor_helpers_sanitize_review_item_type( get_post_meta( $post_id, LLUMMIO_EDITOR_HELPERS_REVIEW_ITEM_TYPE_KEY, true ) );
	$rating    = llummio_editor_helpers_get_meta( $post_id, LLUMMIO_EDITOR_HELPERS_REVIEW_RATING_KEY );
	$author    = llummio_editor_helpers_get_meta( $post_id, LLUMMIO_EDITOR_HELPERS_REVIEW_AUTHOR_KEY );
	$body      = llummio_editor_helpers_get_meta( $post_id, LLUMMIO_EDITOR_HELPERS_REVIEW_BODY_KEY );

	if ( '' === $item_name || '' === $rating || '' === $author || '' === $body ) {
		return null;
	}

	$item = array(
		'@type' => $item_type,
		'name'  => $item_name,
	);

	$image_url = get_the_post_thumbnail_url( $post_id, 'full' );

	if ( $image_url ) {
		$item['image'] = $image_url;
	}

	return array(
		'@type'            => 'Review',
		'@id'              => get_permalink( $post_id ) . '#review',
		'mainEntityOfPage' => array(
			'@id' => get_permalink( $post_id ) . '#webpage',
		),
		'itemReviewed'     => $item,
		'reviewRating'     => array(
			'@type'       => 'Rating',
			'ratingValue' => $rating,
			'bestRating'  => '5',
			'worstRating' => '1',
		),
		'author'           => array(
			'@type' => 'Person',
			'name'  => $author,
		),
		'reviewBody'       => $body,
		'datePublished'    => get_the_date( DATE_W3C, $post_id ),
	);
}

/**
 * Avoid duplicate frontend SEO output when a full SEO plugin is active.
 */
function llummio_editor_helpers_should_skip_frontend_output() {
	$known_seo_plugin_active = defined( 'RANK_MATH_VERSION' ) || defined( 'WPSEO_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'SEOPRESS_PRO_VERSION' );

	return (bool) apply_filters( 'llummio_editor_helpers_skip_frontend_output', $known_seo_plugin_active );
}

/**
 * Avoid duplicate schema output when a full SEO plugin is active.
 */
function llummio_editor_helpers_should_skip_schema_output() {
	return (bool) apply_filters( 'llummio_editor_helpers_skip_schema_output', llummio_editor_helpers_should_skip_frontend_output() );
}
