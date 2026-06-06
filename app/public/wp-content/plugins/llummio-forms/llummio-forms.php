<?php
/**
 * Plugin Name: Llummio Forms
 * Description: Lightweight secure lead forms for Llummio blueprint sites.
 * Version: 0.1.0
 * Author: Llummio
 * Text Domain: llummio-forms
 */

defined( 'ABSPATH' ) || exit;

const LLUMMIO_FORMS_VERSION        = '0.1.0';
const LLUMMIO_FORMS_SETTINGS       = 'llummio_forms_settings';
const LLUMMIO_FORMS_SETTINGS_GROUP = 'llummio_forms_settings_group';
const LLUMMIO_FORMS_NONCE_ACTION   = 'llummio_forms_submit';

/**
 * Register settings.
 */
function llummio_forms_register_settings() {
	register_setting(
		LLUMMIO_FORMS_SETTINGS_GROUP,
		LLUMMIO_FORMS_SETTINGS,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'llummio_forms_sanitize_settings',
			'default'           => llummio_forms_default_settings(),
		)
	);
}

add_action( 'admin_init', 'llummio_forms_register_settings' );

/**
 * Add settings page.
 */
function llummio_forms_add_settings_page() {
	add_options_page(
		__( 'Llummio Forms', 'llummio-forms' ),
		__( 'Llummio Forms', 'llummio-forms' ),
		'manage_options',
		'llummio-forms',
		'llummio_forms_render_settings_page'
	);
}

add_action( 'admin_menu', 'llummio_forms_add_settings_page' );

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
 * Handle submissions before output starts.
 */
function llummio_forms_handle_submission() {
	if ( empty( $_POST['llummio_forms_action'] ) || 'submit' !== $_POST['llummio_forms_action'] ) {
		return;
	}

	$result = llummio_forms_validate_submission();

	if ( ! empty( $result['errors'] ) ) {
		$GLOBALS['llummio_forms_submission'] = $result;
		return;
	}

	llummio_forms_send_notifications( $result['data'] );

	$settings = llummio_forms_get_settings();

	if ( 'redirect' === $settings['confirmation_type'] && '' !== $settings['redirect_url'] ) {
		wp_safe_redirect( $settings['redirect_url'] );
		exit;
	}

	wp_safe_redirect( add_query_arg( 'llummio_form_status', 'success', llummio_forms_get_current_url() ) );
	exit;
}

add_action( 'init', 'llummio_forms_handle_submission', 9 );

/**
 * Render settings page.
 */
function llummio_forms_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = llummio_forms_get_settings();
	$option   = LLUMMIO_FORMS_SETTINGS;
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Llummio Forms', 'llummio-forms' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( LLUMMIO_FORMS_SETTINGS_GROUP ); ?>

			<h2><?php esc_html_e( 'Fields', 'llummio-forms' ); ?></h2>
			<p><?php esc_html_e( 'Choose which fields appear in the default lead form. Email and privacy consent stay required for security and compliance.', 'llummio-forms' ); ?></p>
			<table class="widefat striped" style="max-width: 720px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Field', 'llummio-forms' ); ?></th>
						<th><?php esc_html_e( 'Show', 'llummio-forms' ); ?></th>
						<th><?php esc_html_e( 'Required', 'llummio-forms' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( llummio_forms_fields() as $key => $field ) : ?>
						<tr>
							<td><?php echo esc_html( $field['label'] ); ?></td>
							<td>
								<input
									type="checkbox"
									name="<?php echo esc_attr( $option . '[fields][' . $key . '][enabled]' ); ?>"
									value="1"
									<?php checked( ! empty( $settings['fields'][ $key ]['enabled'] ) ); ?>
									<?php disabled( 'email' === $key ); ?>
								/>
								<?php if ( 'email' === $key ) : ?>
									<input type="hidden" name="<?php echo esc_attr( $option . '[fields][' . $key . '][enabled]' ); ?>" value="1" />
								<?php endif; ?>
							</td>
							<td>
								<input
									type="checkbox"
									name="<?php echo esc_attr( $option . '[fields][' . $key . '][required]' ); ?>"
									value="1"
									<?php checked( ! empty( $settings['fields'][ $key ]['required'] ) ); ?>
									<?php disabled( 'email' === $key ); ?>
								/>
								<?php if ( 'email' === $key ) : ?>
									<input type="hidden" name="<?php echo esc_attr( $option . '[fields][' . $key . '][required]' ); ?>" value="1" />
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
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
				llummio_forms_render_textarea_setting( 'confirmation_message', __( 'Confirmation message', 'llummio-forms' ), $settings );
				llummio_forms_render_url_setting( 'redirect_url', __( 'Thank-you page URL', 'llummio-forms' ), $settings );
				llummio_forms_render_url_setting( 'terms_url', __( 'Terms URL', 'llummio-forms' ), $settings );
				llummio_forms_render_url_setting( 'privacy_url', __( 'Privacy policy URL', 'llummio-forms' ), $settings );
				?>
			</table>

			<h2><?php esc_html_e( 'Email Notifications', 'llummio-forms' ); ?></h2>
			<p><?php esc_html_e( 'This plugin uses the normal WordPress email system. Configure SMTP with your host or a dedicated SMTP plugin when a project needs it.', 'llummio-forms' ); ?></p>
			<table class="form-table" role="presentation">
				<?php
				llummio_forms_render_checkbox_setting( 'send_admin_email', __( 'Send admin email', 'llummio-forms' ), $settings );
				llummio_forms_render_email_setting( 'admin_email', __( 'Admin recipient', 'llummio-forms' ), $settings );
				llummio_forms_render_text_setting( 'admin_subject', __( 'Admin subject', 'llummio-forms' ), $settings );
				llummio_forms_render_checkbox_setting( 'send_user_email', __( 'Send user confirmation email', 'llummio-forms' ), $settings );
				llummio_forms_render_text_setting( 'user_subject', __( 'User subject', 'llummio-forms' ), $settings );
				llummio_forms_render_textarea_setting( 'user_message', __( 'User message', 'llummio-forms' ), $settings );
				?>
			</table>

			<h2><?php esc_html_e( 'Spam Protection', 'llummio-forms' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php llummio_forms_render_checkbox_setting( 'recaptcha_enabled', __( 'Enable Google reCAPTCHA v2 checkbox', 'llummio-forms' ), $settings ); ?>
				<?php llummio_forms_render_text_setting( 'recaptcha_site_key', __( 'reCAPTCHA site key', 'llummio-forms' ), $settings ); ?>
				<?php llummio_forms_render_text_setting( 'recaptcha_secret_key', __( 'reCAPTCHA secret key', 'llummio-forms' ), $settings ); ?>
			</table>

			<h2><?php esc_html_e( 'Style', 'llummio-forms' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				llummio_forms_render_color_setting( 'field_background', __( 'Field background', 'llummio-forms' ), $settings );
				llummio_forms_render_color_setting( 'field_text', __( 'Field text', 'llummio-forms' ), $settings );
				llummio_forms_render_color_setting( 'field_border', __( 'Field border', 'llummio-forms' ), $settings );
				llummio_forms_render_color_setting( 'button_background', __( 'Button background', 'llummio-forms' ), $settings );
				llummio_forms_render_color_setting( 'button_text', __( 'Button text', 'llummio-forms' ), $settings );
				?>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Render form shortcode.
 */
function llummio_forms_render_shortcode() {
	$settings = llummio_forms_get_settings();

	wp_enqueue_style( 'llummio-forms' );
	wp_add_inline_style( 'llummio-forms', llummio_forms_get_inline_css( $settings ) );

	$status     = isset( $_GET['llummio_form_status'] ) ? sanitize_key( $_GET['llummio_form_status'] ) : '';
	$submission = isset( $GLOBALS['llummio_forms_submission'] ) ? $GLOBALS['llummio_forms_submission'] : array();
	$errors     = ! empty( $submission['errors'] ) ? $submission['errors'] : array();
	$values     = ! empty( $submission['data'] ) ? $submission['data'] : array();

	ob_start();
	?>
	<form class="llummio-form" method="post" action="<?php echo esc_url( llummio_forms_get_current_url() ); ?>" novalidate>
		<?php if ( 'success' === $status ) : ?>
			<div class="llummio-form__message llummio-form__message--success">
				<?php echo esc_html( $settings['confirmation_message'] ); ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $errors ) ) : ?>
			<div class="llummio-form__message llummio-form__message--error">
				<?php esc_html_e( 'Please check the highlighted fields and try again.', 'llummio-forms' ); ?>
			</div>
		<?php endif; ?>

		<?php wp_nonce_field( LLUMMIO_FORMS_NONCE_ACTION, 'llummio_forms_nonce' ); ?>
		<input type="hidden" name="llummio_forms_action" value="submit" />
		<input type="hidden" name="llummio_forms_started_at" value="<?php echo esc_attr( time() ); ?>" />
		<div class="llummio-form__hidden" aria-hidden="true">
			<label for="llummio-form-company-website"><?php esc_html_e( 'Company website', 'llummio-forms' ); ?></label>
			<input id="llummio-form-company-website" type="text" name="llummio_company_website" tabindex="-1" autocomplete="off" />
		</div>

		<?php foreach ( llummio_forms_fields() as $key => $field ) : ?>
			<?php if ( empty( $settings['fields'][ $key ]['enabled'] ) ) : ?>
				<?php continue; ?>
			<?php endif; ?>
			<?php llummio_forms_render_form_field( $key, $field, $settings, $values, $errors ); ?>
		<?php endforeach; ?>

		<?php llummio_forms_render_privacy_field( $settings, $errors ); ?>

		<?php if ( llummio_forms_recaptcha_ready( $settings ) ) : ?>
			<div class="llummio-form__field">
				<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $settings['recaptcha_site_key'] ); ?>"></div>
			</div>
			<script src="https://www.google.com/recaptcha/api.js" async defer></script>
		<?php endif; ?>

		<button class="llummio-form__submit" type="submit"><?php esc_html_e( 'Send', 'llummio-forms' ); ?></button>
	</form>
	<?php
	return ob_get_clean();
}

/**
 * Validate a submission.
 */
function llummio_forms_validate_submission() {
	$settings = llummio_forms_get_settings();
	$data     = llummio_forms_sanitize_submission_data();
	$errors   = array();

	if ( empty( $_POST['llummio_forms_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['llummio_forms_nonce'] ) ), LLUMMIO_FORMS_NONCE_ACTION ) ) {
		$errors['form'] = __( 'The form session expired. Please try again.', 'llummio-forms' );
	}

	if ( ! empty( $_POST['llummio_company_website'] ) ) {
		$errors['form'] = __( 'The form could not be submitted.', 'llummio-forms' );
	}

	$started_at = isset( $_POST['llummio_forms_started_at'] ) ? absint( $_POST['llummio_forms_started_at'] ) : 0;
	if ( ! $started_at || time() - $started_at < 3 ) {
		$errors['form'] = __( 'Please wait a moment before submitting the form.', 'llummio-forms' );
	}

	if ( llummio_forms_rate_limit_reached() ) {
		$errors['form'] = __( 'Please wait before submitting the form again.', 'llummio-forms' );
	}

	foreach ( llummio_forms_fields() as $key => $field ) {
		if ( empty( $settings['fields'][ $key ]['enabled'] ) ) {
			continue;
		}

		if ( ! empty( $settings['fields'][ $key ]['required'] ) && '' === $data[ $key ] ) {
			$errors[ $key ] = __( 'This field is required.', 'llummio-forms' );
		}
	}

	if ( '' === $data['email'] || ! is_email( $data['email'] ) ) {
		$errors['email'] = __( 'Please enter a valid email address.', 'llummio-forms' );
	}

	if ( '' !== $data['phone'] && ! llummio_forms_is_valid_phone( $data['phone'] ) ) {
		$errors['phone'] = __( 'Please enter a valid phone number.', 'llummio-forms' );
	}

	if ( empty( $_POST['llummio_privacy'] ) ) {
		$errors['privacy'] = __( 'Please accept the privacy policy.', 'llummio-forms' );
	}

	if ( llummio_forms_recaptcha_ready( $settings ) && ! llummio_forms_verify_recaptcha( $settings ) ) {
		$errors['recaptcha'] = __( 'Please complete the reCAPTCHA check.', 'llummio-forms' );
	}

	return array(
		'data'   => $data,
		'errors' => $errors,
	);
}

/**
 * Send admin and user emails.
 *
 * @param array $data Submission data.
 */
function llummio_forms_send_notifications( $data ) {
	$settings = llummio_forms_get_settings();
	$site     = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

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
 * Return default settings.
 */
function llummio_forms_default_settings() {
	return array(
		'fields'               => array(
			'first_name'  => array( 'enabled' => true, 'required' => true ),
			'second_name' => array( 'enabled' => true, 'required' => false ),
			'last_name'   => array( 'enabled' => true, 'required' => true ),
			'phone'       => array( 'enabled' => true, 'required' => false ),
			'email'       => array( 'enabled' => true, 'required' => true ),
			'address'     => array( 'enabled' => true, 'required' => false ),
			'country'     => array( 'enabled' => true, 'required' => false ),
			'comments'    => array( 'enabled' => true, 'required' => false ),
		),
		'confirmation_type'    => 'message',
		'confirmation_message' => __( 'Thank you. We have received your message.', 'llummio-forms' ),
		'redirect_url'         => '',
		'terms_url'            => '',
		'privacy_url'          => function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '',
		'send_admin_email'     => true,
		'admin_email'          => get_option( 'admin_email' ),
		'admin_subject'        => __( 'New website form submission', 'llummio-forms' ),
		'send_user_email'      => true,
		'user_subject'         => __( 'We received your message', 'llummio-forms' ),
		'user_message'         => __( 'Thank you, {first_name}. We received your message and will get back to you shortly.', 'llummio-forms' ),
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
 * Return settings merged with defaults.
 */
function llummio_forms_get_settings() {
	$settings = get_option( LLUMMIO_FORMS_SETTINGS, array() );
	$settings = is_array( $settings ) ? $settings : array();
	$settings = wp_parse_args( $settings, llummio_forms_default_settings() );

	$settings['fields'] = wp_parse_args( $settings['fields'], llummio_forms_default_settings()['fields'] );

	return $settings;
}

/**
 * Sanitize settings.
 *
 * @param array $settings Raw settings.
 */
function llummio_forms_sanitize_settings( $settings ) {
	$settings = is_array( $settings ) ? $settings : array();
	$defaults = llummio_forms_default_settings();
	$clean    = $defaults;

	foreach ( llummio_forms_fields() as $key => $field ) {
		$enabled = ! empty( $settings['fields'][ $key ]['enabled'] );
		$required = ! empty( $settings['fields'][ $key ]['required'] );

		if ( 'email' === $key ) {
			$enabled  = true;
			$required = true;
		}

		$clean['fields'][ $key ] = array(
			'enabled'  => $enabled,
			'required' => $enabled && $required,
		);
	}

	$clean['confirmation_type']    = isset( $settings['confirmation_type'] ) && 'redirect' === $settings['confirmation_type'] ? 'redirect' : 'message';
	$clean['confirmation_message'] = isset( $settings['confirmation_message'] ) ? sanitize_textarea_field( $settings['confirmation_message'] ) : $defaults['confirmation_message'];
	$clean['redirect_url']         = isset( $settings['redirect_url'] ) ? esc_url_raw( $settings['redirect_url'] ) : '';
	$clean['terms_url']            = isset( $settings['terms_url'] ) ? esc_url_raw( $settings['terms_url'] ) : '';
	$clean['privacy_url']          = isset( $settings['privacy_url'] ) ? esc_url_raw( $settings['privacy_url'] ) : '';
	$clean['send_admin_email']     = ! empty( $settings['send_admin_email'] );
	$clean['admin_email']          = isset( $settings['admin_email'] ) ? sanitize_email( $settings['admin_email'] ) : get_option( 'admin_email' );
	$clean['admin_subject']        = isset( $settings['admin_subject'] ) ? sanitize_text_field( $settings['admin_subject'] ) : $defaults['admin_subject'];
	$clean['send_user_email']      = ! empty( $settings['send_user_email'] );
	$clean['user_subject']         = isset( $settings['user_subject'] ) ? sanitize_text_field( $settings['user_subject'] ) : $defaults['user_subject'];
	$clean['user_message']         = isset( $settings['user_message'] ) ? sanitize_textarea_field( $settings['user_message'] ) : $defaults['user_message'];
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
		'first_name'  => array( 'label' => __( 'Name', 'llummio-forms' ), 'type' => 'text', 'autocomplete' => 'given-name' ),
		'second_name' => array( 'label' => __( 'Second name', 'llummio-forms' ), 'type' => 'text', 'autocomplete' => 'additional-name' ),
		'last_name'   => array( 'label' => __( 'Last name', 'llummio-forms' ), 'type' => 'text', 'autocomplete' => 'family-name' ),
		'phone'       => array( 'label' => __( 'Phone number', 'llummio-forms' ), 'type' => 'tel', 'autocomplete' => 'tel' ),
		'email'       => array( 'label' => __( 'Email address', 'llummio-forms' ), 'type' => 'email', 'autocomplete' => 'email' ),
		'address'     => array( 'label' => __( 'Address', 'llummio-forms' ), 'type' => 'text', 'autocomplete' => 'street-address' ),
		'country'     => array( 'label' => __( 'Country', 'llummio-forms' ), 'type' => 'text', 'autocomplete' => 'country-name' ),
		'comments'    => array( 'label' => __( 'Comments', 'llummio-forms' ), 'type' => 'textarea', 'autocomplete' => '' ),
	);
}

/**
 * Render a frontend field.
 */
function llummio_forms_render_form_field( $key, $field, $settings, $values, $errors ) {
	$value    = isset( $values[ $key ] ) ? $values[ $key ] : ( 'country' === $key ? __( 'Spain', 'llummio-forms' ) : '' );
	$required = ! empty( $settings['fields'][ $key ]['required'] );
	$field_id = 'llummio-form-' . $key;
	?>
	<div class="llummio-form__field <?php echo isset( $errors[ $key ] ) ? 'is-error' : ''; ?>">
		<label class="llummio-form__label" for="<?php echo esc_attr( $field_id ); ?>">
			<?php echo esc_html( $field['label'] ); ?>
			<?php if ( $required ) : ?>
				<span aria-hidden="true">*</span>
			<?php endif; ?>
		</label>
		<?php if ( 'textarea' === $field['type'] ) : ?>
			<textarea id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $key ); ?>" rows="5" <?php echo $required ? 'required' : ''; ?>><?php echo esc_textarea( $value ); ?></textarea>
		<?php else : ?>
			<input
				id="<?php echo esc_attr( $field_id ); ?>"
				type="<?php echo esc_attr( $field['type'] ); ?>"
				name="<?php echo esc_attr( $key ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				autocomplete="<?php echo esc_attr( $field['autocomplete'] ); ?>"
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
				<?php esc_html_e( 'I agree to the terms and privacy policy.', 'llummio-forms' ); ?>
				<?php if ( '' !== $settings['terms_url'] ) : ?>
					<a href="<?php echo esc_url( $settings['terms_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Terms', 'llummio-forms' ); ?></a>
				<?php endif; ?>
				<?php if ( '' !== $settings['privacy_url'] ) : ?>
					<a href="<?php echo esc_url( $settings['privacy_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Read policy', 'llummio-forms' ); ?></a>
				<?php endif; ?>
			</span>
		</label>
		<?php if ( isset( $errors['privacy'] ) ) : ?>
			<p class="llummio-form__error"><?php echo esc_html( $errors['privacy'] ); ?></p>
		<?php endif; ?>
	</div>
	<?php
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
function llummio_forms_rate_limit_reached() {
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
	$key = 'llummio_forms_rate_' . md5( $ip );
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

	return remove_query_arg( 'llummio_form_status', $scheme . $host . $uri );
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
 * Render a text setting.
 */
function llummio_forms_render_text_setting( $key, $label, $settings ) {
	llummio_forms_render_input_setting( $key, $label, $settings, 'text' );
}

/**
 * Render an email setting.
 */
function llummio_forms_render_email_setting( $key, $label, $settings ) {
	llummio_forms_render_input_setting( $key, $label, $settings, 'email' );
}

/**
 * Render a URL setting.
 */
function llummio_forms_render_url_setting( $key, $label, $settings ) {
	llummio_forms_render_input_setting( $key, $label, $settings, 'url' );
}

/**
 * Render a color setting.
 */
function llummio_forms_render_color_setting( $key, $label, $settings ) {
	llummio_forms_render_input_setting( $key, $label, $settings, 'color' );
}

/**
 * Render checkbox setting.
 */
function llummio_forms_render_checkbox_setting( $key, $label, $settings ) {
	?>
	<tr>
		<th scope="row"><?php echo esc_html( $label ); ?></th>
		<td>
			<input type="checkbox" name="<?php echo esc_attr( LLUMMIO_FORMS_SETTINGS . '[' . $key . ']' ); ?>" value="1" <?php checked( ! empty( $settings[ $key ] ) ); ?> />
		</td>
	</tr>
	<?php
}

/**
 * Render input setting.
 */
function llummio_forms_render_input_setting( $key, $label, $settings, $type ) {
	?>
	<tr>
		<th scope="row">
			<label for="<?php echo esc_attr( 'llummio-forms-' . $key ); ?>"><?php echo esc_html( $label ); ?></label>
		</th>
		<td>
			<input
				id="<?php echo esc_attr( 'llummio-forms-' . $key ); ?>"
				class="<?php echo 'color' === $type ? '' : 'regular-text'; ?>"
				type="<?php echo esc_attr( $type ); ?>"
				name="<?php echo esc_attr( LLUMMIO_FORMS_SETTINGS . '[' . $key . ']' ); ?>"
				value="<?php echo esc_attr( isset( $settings[ $key ] ) ? $settings[ $key ] : '' ); ?>"
			/>
		</td>
	</tr>
	<?php
}

/**
 * Render textarea setting.
 */
function llummio_forms_render_textarea_setting( $key, $label, $settings ) {
	?>
	<tr>
		<th scope="row">
			<label for="<?php echo esc_attr( 'llummio-forms-' . $key ); ?>"><?php echo esc_html( $label ); ?></label>
		</th>
		<td>
			<textarea
				id="<?php echo esc_attr( 'llummio-forms-' . $key ); ?>"
				class="large-text"
				rows="4"
				name="<?php echo esc_attr( LLUMMIO_FORMS_SETTINGS . '[' . $key . ']' ); ?>"
			><?php echo esc_textarea( isset( $settings[ $key ] ) ? $settings[ $key ] : '' ); ?></textarea>
		</td>
	</tr>
	<?php
}
