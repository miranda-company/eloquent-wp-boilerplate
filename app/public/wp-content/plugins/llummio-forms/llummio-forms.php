<?php
/**
 * Plugin Name: Llummio Forms
 * Description: Lightweight secure lead forms for Llummio blueprint sites.
 * Version: 0.3.3
 * Author: Llummio
 * Text Domain: llummio-forms
 */

defined( 'ABSPATH' ) || exit;

const LLUMMIO_FORMS_VERSION         = '0.3.3';
const LLUMMIO_FORMS_LEGACY_SETTINGS = 'llummio_forms_settings';
const LLUMMIO_FORMS_FORMS_OPTION    = 'llummio_forms_forms';
const LLUMMIO_FORMS_COUNTS_OPTION   = 'llummio_forms_submission_counts';
const LLUMMIO_FORMS_NONCE_ACTION    = 'llummio_forms_submit';

/**
 * Register assets.
 */
function llummio_forms_register_assets() {
	wp_register_style(
		'llummio-forms',
		plugins_url( 'assets/forms.css', __FILE__ ),
		array(),
		LLUMMIO_FORMS_VERSION
	);
}

add_action( 'wp_enqueue_scripts', 'llummio_forms_register_assets' );

/**
 * Register shortcode.
 */
function llummio_forms_register_shortcode() {
	add_shortcode( 'llummio_form', 'llummio_forms_render_shortcode' );
}

add_action( 'init', 'llummio_forms_register_shortcode' );

/**
 * Add admin menu.
 */
function llummio_forms_add_admin_menu() {
	add_menu_page(
		__( 'Llummio Forms', 'llummio-forms' ),
		__( 'Llummio Forms', 'llummio-forms' ),
		'manage_options',
		'llummio-forms',
		'llummio_forms_render_dashboard_page',
		llummio_forms_admin_menu_icon(),
		58
	);

	add_submenu_page(
		'llummio-forms',
		__( 'Dashboard', 'llummio-forms' ),
		__( 'Dashboard', 'llummio-forms' ),
		'manage_options',
		'llummio-forms',
		'llummio_forms_render_dashboard_page'
	);

	add_submenu_page(
		'llummio-forms',
		__( 'Forms', 'llummio-forms' ),
		__( 'Forms', 'llummio-forms' ),
		'manage_options',
		'llummio-forms-list',
		'llummio_forms_render_forms_page'
	);
}

add_action( 'admin_menu', 'llummio_forms_add_admin_menu' );

/**
 * Return the Llummio SVG icon for the admin menu.
 */
function llummio_forms_admin_menu_icon() {
	$icon_paths = array(
		WP_PLUGIN_DIR . '/llummio-editor-helpers/assets/llummio-editor-helpers-icon.svg',
		get_theme_file_path( 'assets/images/logo-llummio.svg' ),
	);

	foreach ( $icon_paths as $icon_path ) {
		if ( file_exists( $icon_path ) ) {
			$svg = file_get_contents( $icon_path );

			if ( false !== $svg ) {
				return 'data:image/svg+xml;base64,' . base64_encode( llummio_forms_prepare_admin_menu_icon_svg( $svg ) );
			}
		}
	}

	return 'dashicons-feedback';
}

/**
 * Prepare a local SVG so it matches native WordPress admin menu icons.
 *
 * @param string $svg Raw SVG markup.
 */
function llummio_forms_prepare_admin_menu_icon_svg( $svg ) {
	$svg = preg_replace( '/<\?xml.*?\?>\s*/', '', $svg );
	$svg = preg_replace( '/\sfill=(["\']).*?\1/i', '', $svg );
	$svg = preg_replace( '/<svg\b/i', '<svg fill="#fff" width="20" height="20"', $svg, 1 );

	return null === $svg ? '' : $svg;
}

/**
 * Handle admin form saves.
 */
function llummio_forms_handle_admin_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to edit forms.', 'llummio-forms' ) );
	}

	check_admin_referer( 'llummio_forms_save_form' );

	$forms      = llummio_forms_get_forms();
	$posted     = isset( $_POST['llummio_form'] ) && is_array( $_POST['llummio_form'] ) ? wp_unslash( $_POST['llummio_form'] ) : array();
	$form_id    = isset( $_POST['llummio_form_id'] ) ? sanitize_key( wp_unslash( $_POST['llummio_form_id'] ) ) : '';
	$is_new     = '' === $form_id;
	$form_name  = isset( $posted['name'] ) ? sanitize_text_field( $posted['name'] ) : '';
	$form_id    = $is_new ? llummio_forms_generate_form_id( $form_name, $forms ) : $form_id;

	$forms[ $form_id ] = llummio_forms_sanitize_form_settings( $posted, isset( $forms[ $form_id ] ) ? $forms[ $form_id ] : array() );

	update_option( LLUMMIO_FORMS_FORMS_OPTION, $forms, false );

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'    => 'llummio-forms-list',
				'action'  => 'edit',
				'form_id' => $form_id,
				'updated' => '1',
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}

add_action( 'admin_post_llummio_forms_save_form', 'llummio_forms_handle_admin_save' );

/**
 * Handle admin form deletes.
 */
function llummio_forms_handle_admin_delete() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to delete forms.', 'llummio-forms' ) );
	}

	$form_id = isset( $_GET['form_id'] ) ? sanitize_key( wp_unslash( $_GET['form_id'] ) ) : '';

	check_admin_referer( 'llummio_forms_delete_form_' . $form_id );

	$forms = llummio_forms_get_forms();

	if ( isset( $forms[ $form_id ] ) && count( $forms ) > 1 ) {
		unset( $forms[ $form_id ] );
		update_option( LLUMMIO_FORMS_FORMS_OPTION, $forms, false );
	}

	wp_safe_redirect( add_query_arg( array( 'page' => 'llummio-forms-list' ), admin_url( 'admin.php' ) ) );
	exit;
}

add_action( 'admin_post_llummio_forms_delete_form', 'llummio_forms_handle_admin_delete' );

/**
 * Handle count resets.
 */
function llummio_forms_handle_reset_count() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to reset counts.', 'llummio-forms' ) );
	}

	$form_id = isset( $_GET['form_id'] ) ? sanitize_key( wp_unslash( $_GET['form_id'] ) ) : '';

	check_admin_referer( 'llummio_forms_reset_count_' . $form_id );

	$counts = llummio_forms_get_submission_counts();
	unset( $counts[ $form_id ] );
	update_option( LLUMMIO_FORMS_COUNTS_OPTION, $counts, false );

	wp_safe_redirect( add_query_arg( array( 'page' => 'llummio-forms' ), admin_url( 'admin.php' ) ) );
	exit;
}

add_action( 'admin_post_llummio_forms_reset_count', 'llummio_forms_handle_reset_count' );

/**
 * Handle submissions before output starts.
 */
function llummio_forms_handle_submission() {
	if ( empty( $_POST['llummio_forms_action'] ) || 'submit' !== $_POST['llummio_forms_action'] ) {
		return;
	}

	$form_id = isset( $_POST['llummio_form_id'] ) ? sanitize_key( wp_unslash( $_POST['llummio_form_id'] ) ) : 'default';
	$form_id = llummio_forms_resolve_form_id( $form_id );
	$result  = llummio_forms_validate_submission( $form_id );

	if ( ! empty( $result['errors'] ) ) {
		$GLOBALS['llummio_forms_submission'][ $form_id ] = $result;
		return;
	}

	$settings = llummio_forms_get_form( $form_id );

	llummio_forms_send_notifications( $settings, $result['data'] );
	llummio_forms_increment_submission_count( $form_id );

	if ( 'redirect' === $settings['confirmation_type'] && '' !== $settings['redirect_url'] ) {
		wp_safe_redirect( $settings['redirect_url'] );
		exit;
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'llummio_form_status' => 'success',
				'llummio_form_id'     => $form_id,
			),
			llummio_forms_get_current_url()
		)
	);
	exit;
}

add_action( 'init', 'llummio_forms_handle_submission', 9 );

/**
 * Render dashboard page.
 */
function llummio_forms_render_dashboard_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$forms  = llummio_forms_get_forms();
	$counts = llummio_forms_get_submission_counts();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Llummio Forms', 'llummio-forms' ); ?></h1>
		<p><?php esc_html_e( 'Manage lightweight lead forms and monitor submission counts without storing personal data.', 'llummio-forms' ); ?></p>

		<h2><?php esc_html_e( 'Submissions by Form', 'llummio-forms' ); ?></h2>
		<table class="widefat striped" style="max-width: 900px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Form', 'llummio-forms' ); ?></th>
					<th><?php esc_html_e( 'Shortcode', 'llummio-forms' ); ?></th>
					<th><?php esc_html_e( 'Submitted forms', 'llummio-forms' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'llummio-forms' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $forms as $form_id => $form ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $form['name'] ); ?></strong></td>
						<td><code>[llummio_form id="<?php echo esc_attr( $form_id ); ?>"]</code></td>
						<td><?php echo esc_html( isset( $counts[ $form_id ] ) ? absint( $counts[ $form_id ] ) : 0 ); ?></td>
						<td>
							<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'llummio-forms-list', 'action' => 'edit', 'form_id' => $form_id ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit', 'llummio-forms' ); ?></a>
							|
							<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'llummio_forms_reset_count', 'form_id' => $form_id ), admin_url( 'admin-post.php' ) ), 'llummio_forms_reset_count_' . $form_id ) ); ?>"><?php esc_html_e( 'Reset count', 'llummio-forms' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

/**
 * Render forms list or editor page.
 */
function llummio_forms_render_forms_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';

	if ( 'add' === $action || 'edit' === $action ) {
		$form_id = isset( $_GET['form_id'] ) ? sanitize_key( wp_unslash( $_GET['form_id'] ) ) : '';
		llummio_forms_render_form_editor( $form_id );
		return;
	}

	llummio_forms_render_forms_list();
}

/**
 * Render forms list.
 */
function llummio_forms_render_forms_list() {
	$forms = llummio_forms_get_forms();
	?>
	<div class="wrap">
		<h1>
			<?php esc_html_e( 'Forms', 'llummio-forms' ); ?>
			<a class="page-title-action" href="<?php echo esc_url( add_query_arg( array( 'page' => 'llummio-forms-list', 'action' => 'add' ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Add New', 'llummio-forms' ); ?></a>
		</h1>

		<table class="widefat striped" style="max-width: 1000px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'llummio-forms' ); ?></th>
					<th><?php esc_html_e( 'ID', 'llummio-forms' ); ?></th>
					<th><?php esc_html_e( 'Shortcode', 'llummio-forms' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'llummio-forms' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $forms as $form_id => $form ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $form['name'] ); ?></strong></td>
						<td><code><?php echo esc_html( $form_id ); ?></code></td>
						<td><code>[llummio_form id="<?php echo esc_attr( $form_id ); ?>"]</code></td>
						<td>
							<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'llummio-forms-list', 'action' => 'edit', 'form_id' => $form_id ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit', 'llummio-forms' ); ?></a>
							<?php if ( count( $forms ) > 1 ) : ?>
								|
								<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'llummio_forms_delete_form', 'form_id' => $form_id ), admin_url( 'admin-post.php' ) ), 'llummio_forms_delete_form_' . $form_id ) ); ?>"><?php esc_html_e( 'Delete', 'llummio-forms' ); ?></a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

/**
 * Render one form editor.
 *
 * @param string $form_id Form ID.
 */
function llummio_forms_render_form_editor( $form_id ) {
	$forms    = llummio_forms_get_forms();
	$is_new   = '' === $form_id || ! isset( $forms[ $form_id ] );
	$settings = $is_new ? llummio_forms_default_form_settings() : $forms[ $form_id ];
	$option   = 'llummio_form';
	?>
	<div class="wrap">
		<h1><?php echo esc_html( $is_new ? __( 'Add Form', 'llummio-forms' ) : __( 'Edit Form', 'llummio-forms' ) ); ?></h1>

		<?php if ( ! empty( $_GET['updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Form saved.', 'llummio-forms' ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'llummio_forms_save_form' ); ?>
			<input type="hidden" name="action" value="llummio_forms_save_form" />
			<input type="hidden" name="llummio_form_id" value="<?php echo esc_attr( $is_new ? '' : $form_id ); ?>" />

			<h2><?php esc_html_e( 'Form Details', 'llummio-forms' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php llummio_forms_render_scoped_input_setting( $option, 'name', __( 'Form name', 'llummio-forms' ), $settings, 'text' ); ?>
				<?php if ( ! $is_new ) : ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Shortcode', 'llummio-forms' ); ?></th>
						<td><code>[llummio_form id="<?php echo esc_attr( $form_id ); ?>"]</code></td>
					</tr>
				<?php endif; ?>
			</table>

			<h2><?php esc_html_e( 'Fields', 'llummio-forms' ); ?></h2>
			<p><?php esc_html_e( 'Choose which fields appear, edit their labels, and choose their desktop width. Email stays required.', 'llummio-forms' ); ?></p>
			<table class="widefat striped" style="max-width: 960px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Field', 'llummio-forms' ); ?></th>
						<th><?php esc_html_e( 'Label', 'llummio-forms' ); ?></th>
						<th><?php esc_html_e( 'Show', 'llummio-forms' ); ?></th>
						<th><?php esc_html_e( 'Required', 'llummio-forms' ); ?></th>
						<th><?php esc_html_e( 'Width', 'llummio-forms' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( llummio_forms_fields() as $key => $field ) : ?>
						<tr>
							<td><?php echo esc_html( $field['label'] ); ?></td>
							<td>
								<input class="regular-text" type="text" name="<?php echo esc_attr( $option . '[fields][' . $key . '][label]' ); ?>" value="<?php echo esc_attr( $settings['fields'][ $key ]['label'] ); ?>" />
							</td>
							<td>
								<input type="checkbox" name="<?php echo esc_attr( $option . '[fields][' . $key . '][enabled]' ); ?>" value="1" <?php checked( ! empty( $settings['fields'][ $key ]['enabled'] ) ); ?> <?php disabled( 'email' === $key ); ?> />
								<?php if ( 'email' === $key ) : ?>
									<input type="hidden" name="<?php echo esc_attr( $option . '[fields][' . $key . '][enabled]' ); ?>" value="1" />
								<?php endif; ?>
							</td>
							<td>
								<input type="checkbox" name="<?php echo esc_attr( $option . '[fields][' . $key . '][required]' ); ?>" value="1" <?php checked( ! empty( $settings['fields'][ $key ]['required'] ) ); ?> <?php disabled( 'email' === $key ); ?> />
								<?php if ( 'email' === $key ) : ?>
									<input type="hidden" name="<?php echo esc_attr( $option . '[fields][' . $key . '][required]' ); ?>" value="1" />
								<?php endif; ?>
							</td>
							<td>
								<select name="<?php echo esc_attr( $option . '[fields][' . $key . '][width]' ); ?>">
									<option value="full" <?php selected( $settings['fields'][ $key ]['width'], 'full' ); ?>><?php esc_html_e( 'Full', 'llummio-forms' ); ?></option>
									<option value="half" <?php selected( $settings['fields'][ $key ]['width'], 'half' ); ?>><?php esc_html_e( 'Half', 'llummio-forms' ); ?></option>
									<option value="third" <?php selected( $settings['fields'][ $key ]['width'], 'third' ); ?>><?php esc_html_e( 'Third', 'llummio-forms' ); ?></option>
								</select>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Text Labels', 'llummio-forms' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				llummio_forms_render_scoped_input_setting( $option, 'submit_label', __( 'Button label', 'llummio-forms' ), $settings, 'text' );
				?>
			</table>

			<h2><?php esc_html_e( 'Legal Consent', 'llummio-forms' ); ?></h2>
			<p><?php esc_html_e( 'Use {terms} and {privacy} inside the checkbox text to choose exactly where the legal links appear.', 'llummio-forms' ); ?></p>
			<table class="form-table" role="presentation">
				<?php
				llummio_forms_render_scoped_checkbox_setting( $option, 'privacy_required', __( 'Require privacy consent checkbox', 'llummio-forms' ), $settings );
				llummio_forms_render_scoped_input_setting( $option, 'privacy_label', __( 'Privacy checkbox text', 'llummio-forms' ), $settings, 'text' );
				llummio_forms_render_scoped_input_setting( $option, 'terms_link_label', __( 'Terms link label', 'llummio-forms' ), $settings, 'text' );
				llummio_forms_render_scoped_input_setting( $option, 'privacy_link_label', __( 'Privacy link label', 'llummio-forms' ), $settings, 'text' );
				llummio_forms_render_scoped_input_setting( $option, 'terms_url', __( 'Terms URL', 'llummio-forms' ), $settings, 'url' );
				llummio_forms_render_scoped_input_setting( $option, 'privacy_url', __( 'Privacy policy URL', 'llummio-forms' ), $settings, 'url' );
				?>
			</table>

			<h2><?php esc_html_e( 'Error Messages', 'llummio-forms' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				foreach ( llummio_forms_error_fields() as $key => $label ) {
					llummio_forms_render_scoped_input_setting( $option, 'errors][' . $key, $label, $settings, 'text' );
				}
				?>
			</table>

			<h2><?php esc_html_e( 'Confirmation', 'llummio-forms' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'After submit', 'llummio-forms' ); ?></th>
					<td>
						<select name="<?php echo esc_attr( $option ); ?>[confirmation_type]">
							<option value="message" <?php selected( $settings['confirmation_type'], 'message' ); ?>><?php esc_html_e( 'Show confirmation message', 'llummio-forms' ); ?></option>
							<option value="redirect" <?php selected( $settings['confirmation_type'], 'redirect' ); ?>><?php esc_html_e( 'Redirect to thank-you page', 'llummio-forms' ); ?></option>
						</select>
					</td>
				</tr>
				<?php
				llummio_forms_render_scoped_textarea_setting( $option, 'confirmation_message', __( 'Confirmation message', 'llummio-forms' ), $settings );
				llummio_forms_render_scoped_input_setting( $option, 'redirect_url', __( 'Thank-you page URL', 'llummio-forms' ), $settings, 'url' );
				?>
			</table>

			<h2><?php esc_html_e( 'Email Notifications', 'llummio-forms' ); ?></h2>
			<p><?php esc_html_e( 'This plugin uses the normal WordPress email system. Configure SMTP with your host or a dedicated SMTP plugin when a project needs it.', 'llummio-forms' ); ?></p>
			<table class="form-table" role="presentation">
				<?php
				llummio_forms_render_scoped_checkbox_setting( $option, 'send_admin_email', __( 'Send admin email', 'llummio-forms' ), $settings );
				llummio_forms_render_scoped_input_setting( $option, 'admin_email', __( 'Admin recipient', 'llummio-forms' ), $settings, 'email' );
				llummio_forms_render_scoped_input_setting( $option, 'admin_subject', __( 'Admin subject', 'llummio-forms' ), $settings, 'text' );
				llummio_forms_render_scoped_checkbox_setting( $option, 'send_user_email', __( 'Send user confirmation email', 'llummio-forms' ), $settings );
				llummio_forms_render_scoped_input_setting( $option, 'user_subject', __( 'User subject', 'llummio-forms' ), $settings, 'text' );
				llummio_forms_render_scoped_textarea_setting( $option, 'user_message', __( 'User message', 'llummio-forms' ), $settings );
				?>
			</table>

			<h2><?php esc_html_e( 'Spam Protection', 'llummio-forms' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				llummio_forms_render_scoped_checkbox_setting( $option, 'recaptcha_enabled', __( 'Enable Google reCAPTCHA v2 checkbox', 'llummio-forms' ), $settings );
				llummio_forms_render_scoped_input_setting( $option, 'recaptcha_site_key', __( 'reCAPTCHA site key', 'llummio-forms' ), $settings, 'text' );
				llummio_forms_render_scoped_input_setting( $option, 'recaptcha_secret_key', __( 'reCAPTCHA secret key', 'llummio-forms' ), $settings, 'text' );
				?>
			</table>

			<h2><?php esc_html_e( 'Style', 'llummio-forms' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				llummio_forms_render_scoped_input_setting( $option, 'field_background', __( 'Field background', 'llummio-forms' ), $settings, 'color' );
				llummio_forms_render_scoped_input_setting( $option, 'field_text', __( 'Field text', 'llummio-forms' ), $settings, 'color' );
				llummio_forms_render_scoped_input_setting( $option, 'field_border', __( 'Field border', 'llummio-forms' ), $settings, 'color' );
				llummio_forms_render_scoped_input_setting( $option, 'button_background', __( 'Button background', 'llummio-forms' ), $settings, 'color' );
				llummio_forms_render_scoped_input_setting( $option, 'button_text', __( 'Button text', 'llummio-forms' ), $settings, 'color' );
				?>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Render form shortcode.
 *
 * @param array $atts Shortcode attributes.
 */
function llummio_forms_render_shortcode( $atts = array() ) {
	$atts     = shortcode_atts( array( 'id' => 'default' ), $atts, 'llummio_form' );
	$form_id  = sanitize_key( $atts['id'] );
	$form_id  = llummio_forms_resolve_form_id( $form_id );
	$settings = llummio_forms_get_form( $form_id );

	wp_enqueue_style( 'llummio-forms' );
	wp_add_inline_style( 'llummio-forms', llummio_forms_get_inline_css( $settings ) );

	$status        = isset( $_GET['llummio_form_status'] ) ? sanitize_key( wp_unslash( $_GET['llummio_form_status'] ) ) : '';
	$status_form   = isset( $_GET['llummio_form_id'] ) ? sanitize_key( wp_unslash( $_GET['llummio_form_id'] ) ) : '';
	$submission    = isset( $GLOBALS['llummio_forms_submission'][ $form_id ] ) ? $GLOBALS['llummio_forms_submission'][ $form_id ] : array();
	$errors        = ! empty( $submission['errors'] ) ? $submission['errors'] : array();
	$values        = ! empty( $submission['data'] ) ? $submission['data'] : array();
	$success_match = 'success' === $status && $status_form === $form_id;

	ob_start();
	?>
	<form class="llummio-form" style="<?php echo esc_attr( llummio_forms_get_form_style_attribute( $settings ) ); ?>" method="post" action="<?php echo esc_url( llummio_forms_get_current_url() ); ?>" novalidate>
		<?php if ( $success_match ) : ?>
			<div class="llummio-form__message llummio-form__message--success">
				<?php echo esc_html( $settings['confirmation_message'] ); ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $errors ) ) : ?>
			<div class="llummio-form__message llummio-form__message--error">
				<?php echo esc_html( $settings['errors']['summary'] ); ?>
			</div>
		<?php endif; ?>

		<?php wp_nonce_field( LLUMMIO_FORMS_NONCE_ACTION, 'llummio_forms_nonce' ); ?>
		<input type="hidden" name="llummio_forms_action" value="submit" />
		<input type="hidden" name="llummio_form_id" value="<?php echo esc_attr( $form_id ); ?>" />
		<input type="hidden" name="llummio_forms_started_at" value="<?php echo esc_attr( time() ); ?>" />
		<div class="llummio-form__hidden" aria-hidden="true">
			<label for="llummio-form-company-website-<?php echo esc_attr( $form_id ); ?>"><?php esc_html_e( 'Company website', 'llummio-forms' ); ?></label>
			<input id="llummio-form-company-website-<?php echo esc_attr( $form_id ); ?>" type="text" name="llummio_company_website" tabindex="-1" autocomplete="off" />
		</div>

		<?php foreach ( llummio_forms_fields() as $key => $field ) : ?>
			<?php if ( empty( $settings['fields'][ $key ]['enabled'] ) ) : ?>
				<?php continue; ?>
			<?php endif; ?>
			<?php llummio_forms_render_form_field( $form_id, $key, $field, $settings, $values, $errors ); ?>
		<?php endforeach; ?>

		<?php if ( ! empty( $settings['privacy_required'] ) ) : ?>
			<?php llummio_forms_render_privacy_field( $settings, $errors ); ?>
		<?php endif; ?>

		<?php if ( llummio_forms_recaptcha_ready( $settings ) ) : ?>
			<div class="llummio-form__field">
				<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $settings['recaptcha_site_key'] ); ?>"></div>
			</div>
			<script src="https://www.google.com/recaptcha/api.js" async defer></script>
		<?php endif; ?>

		<button class="llummio-form__submit" style="<?php echo esc_attr( llummio_forms_get_button_style_attribute( $settings ) ); ?>" type="submit"><?php echo esc_html( $settings['submit_label'] ); ?></button>
	</form>
	<?php
	return ob_get_clean();
}

/**
 * Validate a submission.
 *
 * @param string $form_id Form ID.
 */
function llummio_forms_validate_submission( $form_id ) {
	$settings = llummio_forms_get_form( $form_id );
	$data     = llummio_forms_sanitize_submission_data();
	$errors   = array();

	if ( empty( $_POST['llummio_forms_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['llummio_forms_nonce'] ) ), LLUMMIO_FORMS_NONCE_ACTION ) ) {
		$errors['form'] = $settings['errors']['session'];
	}

	if ( ! empty( $_POST['llummio_company_website'] ) ) {
		$errors['form'] = $settings['errors']['blocked'];
	}

	$started_at = isset( $_POST['llummio_forms_started_at'] ) ? absint( $_POST['llummio_forms_started_at'] ) : 0;
	if ( ! $started_at || time() - $started_at < 3 ) {
		$errors['form'] = $settings['errors']['timing'];
	}

	if ( llummio_forms_rate_limit_reached( $form_id ) ) {
		$errors['form'] = $settings['errors']['rate_limit'];
	}

	foreach ( llummio_forms_fields() as $key => $field ) {
		if ( empty( $settings['fields'][ $key ]['enabled'] ) ) {
			continue;
		}

		if ( ! empty( $settings['fields'][ $key ]['required'] ) && '' === $data[ $key ] ) {
			$errors[ $key ] = $settings['errors']['required'];
		}
	}

	if ( '' === $data['email'] || ! is_email( $data['email'] ) ) {
		$errors['email'] = $settings['errors']['email'];
	}

	if ( '' !== $data['phone'] && ! llummio_forms_is_valid_phone( $data['phone'] ) ) {
		$errors['phone'] = $settings['errors']['phone'];
	}

	if ( ! empty( $settings['privacy_required'] ) && empty( $_POST['llummio_privacy'] ) ) {
		$errors['privacy'] = $settings['errors']['privacy'];
	}

	if ( llummio_forms_recaptcha_ready( $settings ) && ! llummio_forms_verify_recaptcha( $settings ) ) {
		$errors['recaptcha'] = $settings['errors']['recaptcha'];
	}

	return array(
		'data'   => $data,
		'errors' => $errors,
	);
}

/**
 * Send admin and user emails.
 *
 * @param array $settings Form settings.
 * @param array $data     Submission data.
 */
function llummio_forms_send_notifications( $settings, $data ) {
	$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	if ( ! empty( $settings['send_admin_email'] ) && is_email( $settings['admin_email'] ) ) {
		$headers = array();

		if ( is_email( $data['email'] ) ) {
			$headers[] = 'Reply-To: ' . llummio_forms_get_full_name( $data ) . ' <' . $data['email'] . '>';
		}

		wp_mail(
			$settings['admin_email'],
			llummio_forms_replace_tokens( $settings['admin_subject'], $data ),
			llummio_forms_get_admin_email_body( $data ),
			$headers
		);
	}

	if ( ! empty( $settings['send_user_email'] ) && is_email( $data['email'] ) ) {
		wp_mail(
			$data['email'],
			llummio_forms_replace_tokens( $settings['user_subject'], $data ),
			llummio_forms_replace_tokens( $settings['user_message'], $data ) . "\n\n" . $site
		);
	}
}

/**
 * Return all forms.
 */
function llummio_forms_get_forms() {
	$forms = get_option( LLUMMIO_FORMS_FORMS_OPTION, array() );

	if ( ! is_array( $forms ) || empty( $forms ) ) {
		$legacy = get_option( LLUMMIO_FORMS_LEGACY_SETTINGS, array() );
		$forms  = array(
			'default' => llummio_forms_sanitize_form_settings( is_array( $legacy ) ? $legacy : array(), array( 'name' => __( 'Formulario de contacto', 'llummio-forms' ) ) ),
		);

		update_option( LLUMMIO_FORMS_FORMS_OPTION, $forms, false );
	}

	foreach ( $forms as $form_id => $form ) {
		$forms[ $form_id ] = llummio_forms_normalize_form_settings( $form );
	}

	return $forms;
}

/**
 * Return one form.
 *
 * @param string $form_id Form ID.
 */
function llummio_forms_get_form( $form_id ) {
	$forms = llummio_forms_get_forms();
	$form_id = llummio_forms_resolve_form_id( $form_id );

	if ( isset( $forms[ $form_id ] ) ) {
		return $forms[ $form_id ];
	}

	return reset( $forms );
}

/**
 * Resolve a requested form ID to an existing form ID.
 *
 * @param string $form_id Requested form ID.
 */
function llummio_forms_resolve_form_id( $form_id ) {
	$forms = llummio_forms_get_forms();

	if ( isset( $forms[ $form_id ] ) ) {
		return $form_id;
	}

	if ( isset( $forms['default'] ) ) {
		return 'default';
	}

	return (string) key( $forms );
}

/**
 * Return default form settings.
 */
function llummio_forms_default_form_settings() {
	return array(
		'name'                 => __( 'Formulario de contacto', 'llummio-forms' ),
		'fields'               => array(
			'first_name'  => array( 'enabled' => true, 'required' => true, 'label' => __( 'Nombre', 'llummio-forms' ), 'width' => 'half' ),
			'second_name' => array( 'enabled' => true, 'required' => false, 'label' => __( 'Segundo nombre', 'llummio-forms' ), 'width' => 'half' ),
			'last_name'   => array( 'enabled' => true, 'required' => true, 'label' => __( 'Apellidos', 'llummio-forms' ), 'width' => 'half' ),
			'phone'       => array( 'enabled' => true, 'required' => false, 'label' => __( 'Teléfono', 'llummio-forms' ), 'width' => 'half' ),
			'email'       => array( 'enabled' => true, 'required' => true, 'label' => __( 'Correo electrónico', 'llummio-forms' ), 'width' => 'half' ),
			'address'     => array( 'enabled' => true, 'required' => false, 'label' => __( 'Dirección', 'llummio-forms' ), 'width' => 'full' ),
			'country'     => array( 'enabled' => true, 'required' => false, 'label' => __( 'País', 'llummio-forms' ), 'width' => 'half' ),
			'comments'    => array( 'enabled' => true, 'required' => false, 'label' => __( 'Comentarios', 'llummio-forms' ), 'width' => 'full' ),
		),
		'errors'               => llummio_forms_default_error_messages(),
		'confirmation_type'    => 'message',
		'confirmation_message' => __( 'Gracias. Hemos recibido tu mensaje.', 'llummio-forms' ),
		'redirect_url'         => '',
		'terms_url'            => '',
		'privacy_url'          => function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '',
		'privacy_required'     => true,
		'submit_label'         => __( 'Enviar', 'llummio-forms' ),
		'privacy_label'        => __( 'Acepto los {terms} y la {privacy}.', 'llummio-forms' ),
		'terms_link_label'     => __( 'Términos', 'llummio-forms' ),
		'privacy_link_label'   => __( 'Política de privacidad', 'llummio-forms' ),
		'send_admin_email'     => true,
		'admin_email'          => get_option( 'admin_email' ),
		'admin_subject'        => __( 'Nueva solicitud desde la web', 'llummio-forms' ),
		'send_user_email'      => true,
		'user_subject'         => __( 'Hemos recibido tu mensaje', 'llummio-forms' ),
		'user_message'         => __( 'Gracias, {first_name}. Hemos recibido tu mensaje y te responderemos pronto.', 'llummio-forms' ),
		'recaptcha_enabled'    => false,
		'recaptcha_site_key'   => '',
		'recaptcha_secret_key' => '',
		'field_background'     => '#ffffff',
		'field_text'           => '#111111',
		'field_border'         => '#d4d4d4',
		'button_background'    => '#111111',
		'button_text'          => '#ffffff',
	);
}

/**
 * Return default error messages.
 */
function llummio_forms_default_error_messages() {
	return array(
		'summary'    => __( 'Por favor, revisa los campos marcados e inténtalo de nuevo.', 'llummio-forms' ),
		'session'    => __( 'La sesión del formulario ha caducado. Inténtalo de nuevo.', 'llummio-forms' ),
		'blocked'    => __( 'No se pudo enviar el formulario.', 'llummio-forms' ),
		'timing'     => __( 'Espera un momento antes de enviar el formulario.', 'llummio-forms' ),
		'rate_limit' => __( 'Espera antes de volver a enviar el formulario.', 'llummio-forms' ),
		'required'   => __( 'Este campo es obligatorio.', 'llummio-forms' ),
		'email'      => __( 'Introduce un correo electrónico válido.', 'llummio-forms' ),
		'phone'      => __( 'Introduce un teléfono válido.', 'llummio-forms' ),
		'privacy'    => __( 'Debes aceptar la política de privacidad.', 'llummio-forms' ),
		'recaptcha'  => __( 'Completa la verificación reCAPTCHA.', 'llummio-forms' ),
	);
}

/**
 * Return error setting labels.
 */
function llummio_forms_error_fields() {
	return array(
		'summary'    => __( 'Error general', 'llummio-forms' ),
		'session'    => __( 'Error de sesión caducada', 'llummio-forms' ),
		'blocked'    => __( 'Error de envío bloqueado', 'llummio-forms' ),
		'timing'     => __( 'Error por envío demasiado rápido', 'llummio-forms' ),
		'rate_limit' => __( 'Error por límite de envíos', 'llummio-forms' ),
		'required'   => __( 'Error de campo obligatorio', 'llummio-forms' ),
		'email'      => __( 'Error de correo electrónico', 'llummio-forms' ),
		'phone'      => __( 'Error de teléfono', 'llummio-forms' ),
		'privacy'    => __( 'Error de consentimiento de privacidad', 'llummio-forms' ),
		'recaptcha'  => __( 'Error de reCAPTCHA', 'llummio-forms' ),
	);
}

/**
 * Normalize one form settings array.
 *
 * @param array $settings Raw settings.
 */
function llummio_forms_normalize_form_settings( $settings ) {
	$settings = is_array( $settings ) ? $settings : array();
	$defaults = llummio_forms_default_form_settings();
	$settings = wp_parse_args( $settings, $defaults );

	if ( ! isset( $settings['fields'] ) || ! is_array( $settings['fields'] ) ) {
		$settings['fields'] = array();
	}

	foreach ( llummio_forms_fields() as $key => $field ) {
		$settings['fields'][ $key ] = wp_parse_args(
			isset( $settings['fields'][ $key ] ) && is_array( $settings['fields'][ $key ] ) ? $settings['fields'][ $key ] : array(),
			$defaults['fields'][ $key ]
		);
	}

	$settings['errors'] = wp_parse_args(
		isset( $settings['errors'] ) && is_array( $settings['errors'] ) ? $settings['errors'] : array(),
		$defaults['errors']
	);

	return $settings;
}

/**
 * Sanitize one form settings array.
 *
 * @param array $settings Raw settings.
 * @param array $existing Existing settings.
 */
function llummio_forms_sanitize_form_settings( $settings, $existing = array() ) {
	$settings = is_array( $settings ) ? $settings : array();
	$defaults = llummio_forms_normalize_form_settings( $existing );
	$clean    = $defaults;

	$clean['name'] = isset( $settings['name'] ) && '' !== sanitize_text_field( $settings['name'] ) ? sanitize_text_field( $settings['name'] ) : $defaults['name'];

	$field_settings = isset( $settings['fields'] ) && is_array( $settings['fields'] ) ? $settings['fields'] : array();

	foreach ( llummio_forms_fields() as $key => $field ) {
		$field_setting = isset( $field_settings[ $key ] ) && is_array( $field_settings[ $key ] ) ? $field_settings[ $key ] : array();
		$enabled       = ! empty( $field_setting['enabled'] );
		$required      = ! empty( $field_setting['required'] );
		$label         = isset( $field_setting['label'] ) ? sanitize_text_field( $field_setting['label'] ) : '';
		$width         = isset( $field_setting['width'] ) ? sanitize_key( $field_setting['width'] ) : $defaults['fields'][ $key ]['width'];

		if ( 'email' === $key ) {
			$enabled  = true;
			$required = true;
		}

		if ( ! in_array( $width, array( 'full', 'half', 'third' ), true ) ) {
			$width = $defaults['fields'][ $key ]['width'];
		}

		$clean['fields'][ $key ] = array(
			'enabled'  => $enabled,
			'required' => $enabled && $required,
			'label'    => '' !== $label ? $label : $defaults['fields'][ $key ]['label'],
			'width'    => $width,
		);
	}

	$error_settings = isset( $settings['errors'] ) && is_array( $settings['errors'] ) ? $settings['errors'] : array();
	foreach ( llummio_forms_default_error_messages() as $key => $message ) {
		$value = isset( $error_settings[ $key ] ) ? sanitize_text_field( $error_settings[ $key ] ) : '';
		$clean['errors'][ $key ] = '' !== $value ? $value : $message;
	}

	$clean['confirmation_type']    = isset( $settings['confirmation_type'] ) && 'redirect' === $settings['confirmation_type'] ? 'redirect' : 'message';
	$clean['confirmation_message'] = isset( $settings['confirmation_message'] ) ? sanitize_textarea_field( $settings['confirmation_message'] ) : $defaults['confirmation_message'];
	$clean['redirect_url']         = isset( $settings['redirect_url'] ) ? esc_url_raw( $settings['redirect_url'] ) : '';
	$clean['terms_url']            = isset( $settings['terms_url'] ) ? esc_url_raw( $settings['terms_url'] ) : '';
	$clean['privacy_url']          = isset( $settings['privacy_url'] ) ? esc_url_raw( $settings['privacy_url'] ) : '';
	$clean['privacy_required']     = ! empty( $settings['privacy_required'] );

	foreach ( array( 'submit_label', 'privacy_label', 'terms_link_label', 'privacy_link_label', 'admin_subject', 'user_subject' ) as $key ) {
		$value = isset( $settings[ $key ] ) ? sanitize_text_field( $settings[ $key ] ) : '';
		$clean[ $key ] = '' !== $value ? $value : $defaults[ $key ];
	}

	$clean['send_admin_email'] = ! empty( $settings['send_admin_email'] );
	$clean['admin_email']      = isset( $settings['admin_email'] ) ? sanitize_email( $settings['admin_email'] ) : get_option( 'admin_email' );
	$clean['send_user_email']  = ! empty( $settings['send_user_email'] );
	$clean['user_message']     = isset( $settings['user_message'] ) ? sanitize_textarea_field( $settings['user_message'] ) : $defaults['user_message'];

	$clean['recaptcha_enabled']    = ! empty( $settings['recaptcha_enabled'] );
	$clean['recaptcha_site_key']   = isset( $settings['recaptcha_site_key'] ) ? sanitize_text_field( $settings['recaptcha_site_key'] ) : '';
	$clean['recaptcha_secret_key'] = isset( $settings['recaptcha_secret_key'] ) ? sanitize_text_field( $settings['recaptcha_secret_key'] ) : '';

	foreach ( array( 'field_background', 'field_text', 'field_border', 'button_background', 'button_text' ) as $key ) {
		$color = isset( $settings[ $key ] ) ? sanitize_hex_color( $settings[ $key ] ) : '';
		$clean[ $key ] = $color ? $color : $defaults[ $key ];
	}

	return $clean;
}

/**
 * Return supported fields.
 */
function llummio_forms_fields() {
	return array(
		'first_name'  => array( 'label' => __( 'Nombre', 'llummio-forms' ), 'type' => 'text', 'autocomplete' => 'given-name' ),
		'second_name' => array( 'label' => __( 'Segundo nombre', 'llummio-forms' ), 'type' => 'text', 'autocomplete' => 'additional-name' ),
		'last_name'   => array( 'label' => __( 'Apellidos', 'llummio-forms' ), 'type' => 'text', 'autocomplete' => 'family-name' ),
		'phone'       => array( 'label' => __( 'Teléfono', 'llummio-forms' ), 'type' => 'tel', 'autocomplete' => 'tel' ),
		'email'       => array( 'label' => __( 'Correo electrónico', 'llummio-forms' ), 'type' => 'email', 'autocomplete' => 'email' ),
		'address'     => array( 'label' => __( 'Dirección', 'llummio-forms' ), 'type' => 'text', 'autocomplete' => 'street-address' ),
		'country'     => array( 'label' => __( 'País', 'llummio-forms' ), 'type' => 'text', 'autocomplete' => 'country-name' ),
		'comments'    => array( 'label' => __( 'Comentarios', 'llummio-forms' ), 'type' => 'textarea', 'autocomplete' => '' ),
	);
}

/**
 * Render a frontend field.
 */
function llummio_forms_render_form_field( $form_id, $key, $field, $settings, $values, $errors ) {
	$value    = isset( $values[ $key ] ) ? $values[ $key ] : ( 'country' === $key ? __( 'España', 'llummio-forms' ) : '' );
	$required = ! empty( $settings['fields'][ $key ]['required'] );
	$field_id = 'llummio-form-' . $form_id . '-' . $key;
	$label    = isset( $settings['fields'][ $key ]['label'] ) ? $settings['fields'][ $key ]['label'] : $field['label'];
	$width    = isset( $settings['fields'][ $key ]['width'] ) ? $settings['fields'][ $key ]['width'] : 'full';
	$width    = in_array( $width, array( 'full', 'half', 'third' ), true ) ? $width : 'full';
	?>
	<div class="llummio-form__field llummio-form__field--<?php echo esc_attr( $width ); ?> <?php echo isset( $errors[ $key ] ) ? 'is-error' : ''; ?>">
		<label class="llummio-form__label" for="<?php echo esc_attr( $field_id ); ?>">
			<?php echo esc_html( $label ); ?>
			<?php if ( $required ) : ?>
				<span aria-hidden="true">*</span>
			<?php endif; ?>
		</label>
		<?php if ( 'textarea' === $field['type'] ) : ?>
			<textarea id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $key ); ?>" rows="5" style="<?php echo esc_attr( llummio_forms_get_field_style_attribute( $settings ) ); ?>" <?php echo $required ? 'required' : ''; ?>><?php echo esc_textarea( $value ); ?></textarea>
		<?php else : ?>
			<input
				id="<?php echo esc_attr( $field_id ); ?>"
				type="<?php echo esc_attr( $field['type'] ); ?>"
				name="<?php echo esc_attr( $key ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				autocomplete="<?php echo esc_attr( $field['autocomplete'] ); ?>"
				style="<?php echo esc_attr( llummio_forms_get_field_style_attribute( $settings ) ); ?>"
				<?php echo $required ? 'required' : ''; ?>
			/>
		<?php endif; ?>
		<?php if ( isset( $errors[ $key ] ) ) : ?>
			<p class="llummio-form__error"><?php echo esc_html( $errors[ $key ] ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render privacy field.
 */
function llummio_forms_render_privacy_field( $settings, $errors ) {
	?>
	<div class="llummio-form__field llummio-form__field--checkbox <?php echo isset( $errors['privacy'] ) ? 'is-error' : ''; ?>">
		<label>
			<input type="checkbox" name="llummio_privacy" value="1" required />
			<span>
				<?php echo wp_kses_post( llummio_forms_get_privacy_label_html( $settings ) ); ?>
			</span>
		</label>
		<?php if ( isset( $errors['privacy'] ) ) : ?>
			<p class="llummio-form__error"><?php echo esc_html( $errors['privacy'] ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Return consent text with legal link tokens replaced.
 *
 * @param array $settings Form settings.
 */
function llummio_forms_get_privacy_label_html( $settings ) {
	$text       = isset( $settings['privacy_label'] ) ? $settings['privacy_label'] : '';
	$has_link   = false;
	$has_tokens = false !== strpos( $text, '{terms}' ) || false !== strpos( $text, '{privacy}' );
	$tokens     = array(
		'{terms}'   => llummio_forms_get_legal_link_html( $settings['terms_url'], $settings['terms_link_label'], $has_link ),
		'{privacy}' => llummio_forms_get_legal_link_html( $settings['privacy_url'], $settings['privacy_link_label'], $has_link ),
	);

	$html = esc_html( $text );
	$html = str_replace( array_keys( $tokens ), array_values( $tokens ), $html );

	if ( ! $has_tokens ) {
		$links = array_filter(
			array(
				llummio_forms_get_legal_link_html( $settings['terms_url'], $settings['terms_link_label'], $has_link ),
				llummio_forms_get_legal_link_html( $settings['privacy_url'], $settings['privacy_link_label'], $has_link ),
			)
		);

		if ( ! empty( $links ) ) {
			$html .= ' ' . implode( ' ', $links );
		}
	}

	return $html;
}

/**
 * Return one legal link or plain label fallback.
 *
 * @param string $url      Link URL.
 * @param string $label    Link label.
 * @param bool   $has_link Whether a real link has been created.
 */
function llummio_forms_get_legal_link_html( $url, $label, &$has_link ) {
	if ( '' === $label ) {
		return '';
	}

	if ( '' === $url ) {
		return esc_html( $label );
	}

	$has_link = true;

	return sprintf(
		'<a href="%1$s" target="_blank" rel="noopener">%2$s</a>',
		esc_url( $url ),
		esc_html( $label )
	);
}

/**
 * Return sanitized posted data.
 */
function llummio_forms_sanitize_submission_data() {
	$data = array();

	foreach ( array_keys( llummio_forms_fields() ) as $key ) {
		$value = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
		$data[ $key ] = 'comments' === $key ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
	}

	$data['email']      = sanitize_email( $data['email'] );
	$data['source_url'] = llummio_forms_get_current_url();

	return $data;
}

/**
 * Check basic phone validity, with Spanish numbers supported by default.
 */
function llummio_forms_is_valid_phone( $phone ) {
	$compact = preg_replace( '/[\s\-\.\(\)]/', '', $phone );

	return (bool) preg_match( '/^(?:\+34|0034)?[6789][0-9]{8}$/', $compact );
}

/**
 * Check rate limit and increment the visitor counter.
 */
function llummio_forms_rate_limit_reached( $form_id ) {
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
	$key   = 'llummio_forms_rate_' . md5( $form_id . '|' . $ip );
	$count = (int) get_transient( $key );

	if ( $count >= 5 ) {
		return true;
	}

	set_transient( $key, $count + 1, 10 * MINUTE_IN_SECONDS );

	return false;
}

/**
 * Verify reCAPTCHA with Google.
 */
function llummio_forms_verify_recaptcha( $settings ) {
	$response = isset( $_POST['g-recaptcha-response'] ) ? sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ) : '';

	if ( '' === $response ) {
		return false;
	}

	$result = wp_remote_post(
		'https://www.google.com/recaptcha/api/siteverify',
		array(
			'timeout' => 10,
			'body'    => array(
				'secret'   => $settings['recaptcha_secret_key'],
				'response' => $response,
				'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
			),
		)
	);

	if ( is_wp_error( $result ) ) {
		return false;
	}

	$body = json_decode( wp_remote_retrieve_body( $result ), true );

	return ! empty( $body['success'] );
}

/**
 * Check whether reCAPTCHA has enough settings to run.
 */
function llummio_forms_recaptcha_ready( $settings ) {
	return ! empty( $settings['recaptcha_enabled'] ) && '' !== $settings['recaptcha_site_key'] && '' !== $settings['recaptcha_secret_key'];
}

/**
 * Return admin email body.
 */
function llummio_forms_get_admin_email_body( $data ) {
	$lines = array(
		'Name: ' . llummio_forms_get_full_name( $data ),
		'Phone: ' . $data['phone'],
		'Email: ' . $data['email'],
		'Address: ' . $data['address'],
		'Country: ' . $data['country'],
		'Comments: ' . $data['comments'],
		'Source: ' . $data['source_url'],
	);

	return implode( "\n", array_filter( $lines ) );
}

/**
 * Return full name from submission data.
 */
function llummio_forms_get_full_name( $data ) {
	return trim( $data['first_name'] . ' ' . $data['second_name'] . ' ' . $data['last_name'] );
}

/**
 * Replace simple email tokens.
 */
function llummio_forms_replace_tokens( $text, $data ) {
	$tokens = array(
		'{first_name}' => $data['first_name'],
		'{name}'       => llummio_forms_get_full_name( $data ),
		'{email}'      => $data['email'],
		'{phone}'      => $data['phone'],
		'{source_url}' => $data['source_url'],
	);

	return str_replace( array_keys( $tokens ), array_values( $tokens ), $text );
}

/**
 * Return current URL without form status.
 */
function llummio_forms_get_current_url() {
	$scheme = is_ssl() ? 'https://' : 'http://';
	$host   = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : wp_parse_url( home_url(), PHP_URL_HOST );
	$uri    = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';

	return remove_query_arg( array( 'llummio_form_status', 'llummio_form_id' ), $scheme . $host . $uri );
}

/**
 * Return frontend CSS variables.
 */
function llummio_forms_get_inline_css( $settings ) {
	return sprintf(
		'.llummio-form{--llummio-form-field-background:%1$s;--llummio-form-field-text:%2$s;--llummio-form-field-border:%3$s;--llummio-form-button-background:%4$s;--llummio-form-button-text:%5$s;}',
		esc_html( $settings['field_background'] ),
		esc_html( $settings['field_text'] ),
		esc_html( $settings['field_border'] ),
		esc_html( $settings['button_background'] ),
		esc_html( $settings['button_text'] )
	);
}

/**
 * Return frontend form style variables.
 */
function llummio_forms_get_form_style_attribute( $settings ) {
	return sprintf(
		'--llummio-form-field-background:%1$s;--llummio-form-field-text:%2$s;--llummio-form-field-border:%3$s;--llummio-form-button-background:%4$s;--llummio-form-button-text:%5$s;',
		esc_attr( $settings['field_background'] ),
		esc_attr( $settings['field_text'] ),
		esc_attr( $settings['field_border'] ),
		esc_attr( $settings['button_background'] ),
		esc_attr( $settings['button_text'] )
	);
}

/**
 * Return direct input and textarea styles.
 */
function llummio_forms_get_field_style_attribute( $settings ) {
	return sprintf(
		'background-color:%1$s;color:%2$s;border-color:%3$s;',
		esc_attr( $settings['field_background'] ),
		esc_attr( $settings['field_text'] ),
		esc_attr( $settings['field_border'] )
	);
}

/**
 * Return direct submit button styles.
 */
function llummio_forms_get_button_style_attribute( $settings ) {
	return sprintf(
		'background-color:%1$s;color:%2$s;',
		esc_attr( $settings['button_background'] ),
		esc_attr( $settings['button_text'] )
	);
}

/**
 * Return counts.
 */
function llummio_forms_get_submission_counts() {
	$counts = get_option( LLUMMIO_FORMS_COUNTS_OPTION, array() );

	return is_array( $counts ) ? array_map( 'absint', $counts ) : array();
}

/**
 * Increment one form count.
 *
 * @param string $form_id Form ID.
 */
function llummio_forms_increment_submission_count( $form_id ) {
	$counts = llummio_forms_get_submission_counts();

	$counts[ $form_id ] = isset( $counts[ $form_id ] ) ? absint( $counts[ $form_id ] ) + 1 : 1;

	update_option( LLUMMIO_FORMS_COUNTS_OPTION, $counts, false );
}

/**
 * Generate a unique form ID.
 */
function llummio_forms_generate_form_id( $name, $forms ) {
	$base = sanitize_title( '' !== $name ? $name : __( 'Formulario', 'llummio-forms' ) );
	$base = '' !== $base ? $base : 'form';
	$id   = $base;
	$i    = 2;

	while ( isset( $forms[ $id ] ) ) {
		$id = $base . '-' . $i;
		$i++;
	}

	return $id;
}

/**
 * Render scoped input setting.
 */
function llummio_forms_render_scoped_input_setting( $scope, $key, $label, $settings, $type ) {
	$id    = 'llummio-forms-' . str_replace( array( '][', '[', ']' ), '-', $key );
	$value = llummio_forms_get_nested_setting_value( $settings, $key );
	?>
	<tr>
		<th scope="row">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		</th>
		<td>
			<input
				id="<?php echo esc_attr( $id ); ?>"
				class="<?php echo 'color' === $type ? '' : 'regular-text'; ?>"
				type="<?php echo esc_attr( $type ); ?>"
				name="<?php echo esc_attr( $scope . '[' . $key . ']' ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
			/>
		</td>
	</tr>
	<?php
}

/**
 * Render scoped textarea setting.
 */
function llummio_forms_render_scoped_textarea_setting( $scope, $key, $label, $settings ) {
	$id    = 'llummio-forms-' . str_replace( array( '][', '[', ']' ), '-', $key );
	$value = llummio_forms_get_nested_setting_value( $settings, $key );
	?>
	<tr>
		<th scope="row">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		</th>
		<td>
			<textarea
				id="<?php echo esc_attr( $id ); ?>"
				class="large-text"
				rows="4"
				name="<?php echo esc_attr( $scope . '[' . $key . ']' ); ?>"
			><?php echo esc_textarea( $value ); ?></textarea>
		</td>
	</tr>
	<?php
}

/**
 * Render scoped checkbox setting.
 */
function llummio_forms_render_scoped_checkbox_setting( $scope, $key, $label, $settings ) {
	?>
	<tr>
		<th scope="row"><?php echo esc_html( $label ); ?></th>
		<td>
			<input type="checkbox" name="<?php echo esc_attr( $scope . '[' . $key . ']' ); ?>" value="1" <?php checked( ! empty( $settings[ $key ] ) ); ?> />
		</td>
	</tr>
	<?php
}

/**
 * Return nested setting value from a key like errors][summary.
 */
function llummio_forms_get_nested_setting_value( $settings, $key ) {
	if ( false === strpos( $key, '][' ) ) {
		return isset( $settings[ $key ] ) ? $settings[ $key ] : '';
	}

	$parts = explode( '][', $key );
	$value = $settings;

	foreach ( $parts as $part ) {
		if ( ! is_array( $value ) || ! isset( $value[ $part ] ) ) {
			return '';
		}

		$value = $value[ $part ];
	}

	return $value;
}
