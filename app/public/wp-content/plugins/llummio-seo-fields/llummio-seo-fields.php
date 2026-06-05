<?php
/**
 * Plugin Name: Llummio SEO Fields
 * Description: Lightweight per-page SEO title and description fields for Llummio blueprint sites.
 * Version: 0.1.0
 * Author: Llummio
 * Text Domain: llummio-seo-fields
 */

defined( 'ABSPATH' ) || exit;

const LLUMMIO_SEO_FIELDS_TITLE_KEY       = '_llummio_seo_title';
const LLUMMIO_SEO_FIELDS_DESCRIPTION_KEY = '_llummio_seo_description';

/**
 * Register SEO metadata for public editable post types.
 */
function llummio_seo_fields_register_meta() {
	foreach ( llummio_seo_fields_post_types() as $post_type ) {
		register_post_meta(
			$post_type,
			LLUMMIO_SEO_FIELDS_TITLE_KEY,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => 'llummio_seo_fields_can_edit_meta',
			)
		);

		register_post_meta(
			$post_type,
			LLUMMIO_SEO_FIELDS_DESCRIPTION_KEY,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_textarea_field',
				'auth_callback'     => 'llummio_seo_fields_can_edit_meta',
			)
		);
	}
}

add_action( 'init', 'llummio_seo_fields_register_meta' );

/**
 * Return the post types where SEO fields should appear.
 */
function llummio_seo_fields_post_types() {
	$post_types = get_post_types(
		array(
			'public'  => true,
			'show_ui' => true,
		),
		'names'
	);

	unset( $post_types['attachment'] );

	return apply_filters( 'llummio_seo_fields_post_types', array_values( $post_types ) );
}

/**
 * Check whether the current user can edit SEO fields.
 *
 * @param bool   $allowed Whether the user can edit.
 * @param string $meta_key Meta key.
 * @param int    $post_id  Post ID.
 * @param int    $user_id  User ID.
 */
function llummio_seo_fields_can_edit_meta( $allowed, $meta_key, $post_id, $user_id = 0 ) {
	return user_can( $user_id ? $user_id : get_current_user_id(), 'edit_post', $post_id );
}

/**
 * Add the SEO fields box to supported edit screens.
 */
function llummio_seo_fields_add_meta_box() {
	foreach ( llummio_seo_fields_post_types() as $post_type ) {
		add_meta_box(
			'llummio-seo-fields',
			__( 'SEO', 'llummio-seo-fields' ),
			'llummio_seo_fields_render_meta_box',
			$post_type,
			'normal',
			'default'
		);
	}
}

add_action( 'add_meta_boxes', 'llummio_seo_fields_add_meta_box' );

/**
 * Render the SEO fields box.
 *
 * @param WP_Post $post Current post.
 */
function llummio_seo_fields_render_meta_box( $post ) {
	$seo_title       = get_post_meta( $post->ID, LLUMMIO_SEO_FIELDS_TITLE_KEY, true );
	$seo_description = get_post_meta( $post->ID, LLUMMIO_SEO_FIELDS_DESCRIPTION_KEY, true );

	wp_nonce_field( 'llummio_seo_fields_save', 'llummio_seo_fields_nonce' );
	?>
	<p>
		<label for="llummio-seo-title"><strong><?php esc_html_e( 'SEO Title', 'llummio-seo-fields' ); ?></strong></label>
	</p>
	<input
		type="text"
		id="llummio-seo-title"
		name="llummio_seo_title"
		value="<?php echo esc_attr( $seo_title ); ?>"
		class="widefat"
		maxlength="160"
	/>
	<p class="description">
		<?php esc_html_e( 'Overrides the browser title and search result title for this content.', 'llummio-seo-fields' ); ?>
	</p>

	<p>
		<label for="llummio-seo-description"><strong><?php esc_html_e( 'SEO Description', 'llummio-seo-fields' ); ?></strong></label>
	</p>
	<textarea
		id="llummio-seo-description"
		name="llummio_seo_description"
		class="widefat"
		rows="4"
		maxlength="320"
	><?php echo esc_textarea( $seo_description ); ?></textarea>
	<p class="description">
		<?php esc_html_e( 'Outputs a meta description tag for this content.', 'llummio-seo-fields' ); ?>
	</p>
	<?php
}

/**
 * Save SEO fields.
 *
 * @param int $post_id Post ID.
 */
function llummio_seo_fields_save_meta( $post_id ) {
	if ( ! isset( $_POST['llummio_seo_fields_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['llummio_seo_fields_nonce'] ) ), 'llummio_seo_fields_save' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['llummio_seo_title'] ) ) {
		$title = sanitize_text_field( wp_unslash( $_POST['llummio_seo_title'] ) );
		llummio_seo_fields_update_or_delete_meta( $post_id, LLUMMIO_SEO_FIELDS_TITLE_KEY, $title );
	}

	if ( isset( $_POST['llummio_seo_description'] ) ) {
		$description = sanitize_textarea_field( wp_unslash( $_POST['llummio_seo_description'] ) );
		$description = preg_replace( '/\s+/', ' ', $description );
		llummio_seo_fields_update_or_delete_meta( $post_id, LLUMMIO_SEO_FIELDS_DESCRIPTION_KEY, trim( $description ) );
	}
}

add_action( 'save_post', 'llummio_seo_fields_save_meta' );

/**
 * Update meta when it has a value, delete it when empty.
 *
 * @param int    $post_id  Post ID.
 * @param string $meta_key Meta key.
 * @param string $value    Meta value.
 */
function llummio_seo_fields_update_or_delete_meta( $post_id, $meta_key, $value ) {
	if ( '' === $value ) {
		delete_post_meta( $post_id, $meta_key );
		return;
	}

	update_post_meta( $post_id, $meta_key, $value );
}

/**
 * Make sure themes can use WordPress-managed document titles.
 */
function llummio_seo_fields_add_title_support() {
	add_theme_support( 'title-tag' );
}

add_action( 'after_setup_theme', 'llummio_seo_fields_add_title_support' );

/**
 * Override the frontend document title for singular content.
 *
 * @param string $title Existing document title.
 */
function llummio_seo_fields_document_title( $title ) {
	if ( llummio_seo_fields_should_skip_frontend_output() || ! is_singular() ) {
		return $title;
	}

	$seo_title = llummio_seo_fields_get_meta( get_queried_object_id(), LLUMMIO_SEO_FIELDS_TITLE_KEY );

	return '' !== $seo_title ? $seo_title : $title;
}

add_filter( 'pre_get_document_title', 'llummio_seo_fields_document_title' );

/**
 * Output the frontend meta description for singular content.
 */
function llummio_seo_fields_meta_description() {
	if ( llummio_seo_fields_should_skip_frontend_output() || ! is_singular() ) {
		return;
	}

	$description = llummio_seo_fields_get_meta( get_queried_object_id(), LLUMMIO_SEO_FIELDS_DESCRIPTION_KEY );

	if ( '' === $description ) {
		return;
	}

	printf(
		'<meta name="description" content="%s" />' . "\n",
		esc_attr( $description )
	);
}

add_action( 'wp_head', 'llummio_seo_fields_meta_description', 1 );

/**
 * Return a trimmed SEO field value.
 *
 * @param int    $post_id  Post ID.
 * @param string $meta_key Meta key.
 */
function llummio_seo_fields_get_meta( $post_id, $meta_key ) {
	if ( ! $post_id ) {
		return '';
	}

	return trim( (string) get_post_meta( $post_id, $meta_key, true ) );
}

/**
 * Avoid duplicate frontend SEO output when a full SEO plugin is active.
 */
function llummio_seo_fields_should_skip_frontend_output() {
	$known_seo_plugin_active = defined( 'RANK_MATH_VERSION' ) || defined( 'WPSEO_VERSION' ) || defined( 'AIOSEO_VERSION' );

	return (bool) apply_filters( 'llummio_seo_fields_skip_frontend_output', $known_seo_plugin_active );
}
