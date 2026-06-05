<?php
/**
 * Turnstile CAPTCHA verification.
 *
 * Renders and verifies Cloudflare Turnstile tokens for form submissions.
 * Hooks into form render and verification filters — not actions.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turnstile CAPTCHA class.
 */
class GenerateBlocks_Pro_Form_Turnstile {

	/**
	 * Cloudflare Turnstile verification endpoint.
	 *
	 * @var string
	 */
	const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

	/**
	 * Initialize Turnstile verification.
	 */
	public static function init() {
		GenerateBlocks_Pro_Form_Integration_Registry::register(
			[
				'id'         => 'turnstile',
				'label'      => __( 'Cloudflare Turnstile', 'generateblocks-pro' ),
				'type'       => 'spam',
				'connection' => [
					'type'          => 'api_key',
					'fields'        => [
						[
							'key'           => 'site_key',
							'label'         => __( 'Site Key', 'generateblocks-pro' ),
							'type'          => 'text',
							'help'          => __( 'The public key rendered in your form. Find it in your Cloudflare dashboard under Turnstile.', 'generateblocks-pro' ),
							'required'      => true,
							'test_required' => false,
							'define'        => 'GENERATEBLOCKS_PRO_TURNSTILE_SITE_KEY',
							'public'        => true,
						],
						[
							'key'      => 'api_key',
							'label'    => __( 'Secret Key', 'generateblocks-pro' ),
							'type'     => 'password',
							'help'     => __( 'The secret key used to verify submissions server-side.', 'generateblocks-pro' ),
							'required' => true,
							'define'   => 'GENERATEBLOCKS_PRO_TURNSTILE_SECRET_KEY',
						],
					],
					'test_callback' => function ( $settings ) {
						return self::test_connection( $settings['api_key'] ?? '' );
					},
				],
			]
		);

		add_filter( 'generateblocks_form_verify_submission', [ __CLASS__, 'verify_submission' ], 10, 4 );
		add_filter( 'generateblocks_form_render_footer_html', [ __CLASS__, 'render_widget' ], 10, 6 );
	}

	/**
	 * Render the Turnstile widget inside the Form block.
	 *
	 * Hooked to the form render footer filter so all Turnstile concerns —
	 * render, script enqueue, server-side verification — live in one class.
	 *
	 * @param string $html         Accumulated HTML from prior callbacks.
	 * @param int    $form_id      Form post ID. Unused; required by filter signature.
	 * @param int    $host_post_id Host page ID. Unused; required by filter signature.
	 * @param array  $config       Form config from meta.
	 * @param string $context      'public' or 'editor'.
	 * @param int    $instance     Per-render instance number. Unused; required by filter signature.
	 * @return string
	 */
	public static function render_widget( $html, $form_id, $host_post_id, $config, $context, $instance ) {
		unset( $form_id, $host_post_id, $instance );

		if ( 'public' !== $context ) {
			return $html;
		}

		if ( empty( $config['useTurnstile'] ) ) {
			return $html;
		}

		$site_key = self::get_site_key();

		if ( '' === $site_key ) {
			return $html;
		}

		wp_enqueue_script(
			'cf-turnstile',
			'https://challenges.cloudflare.com/turnstile/v0/api.js',
			[],
			GENERATEBLOCKS_PRO_VERSION,
			[ 'strategy' => 'defer' ]
		);

		return $html . sprintf(
			'<div class="cf-turnstile" data-sitekey="%s"></div>',
			esc_attr( $site_key )
		);
	}

	/**
	 * Verify the Turnstile token on form submission.
	 *
	 * @param null|WP_Error $pre_result Default null. Return WP_Error to reject.
	 * @param int           $form_id    The form post ID.
	 * @param array         $raw_fields All submitted fields.
	 * @param array         $context    Request context.
	 * @return null|WP_Error
	 */
	public static function verify_submission( $pre_result, $form_id, $raw_fields, $context ) {
		// Don't override an existing rejection.
		if ( is_wp_error( $pre_result ) ) {
			return $pre_result;
		}

		$secret_key = self::get_secret_key();

		// No secret key configured — Turnstile is not set up, pass through.
		if ( empty( $secret_key ) ) {
			return $pre_result;
		}

		// No turnstile_token in context — the client didn't send one. Pass
		// through here; the processor's server-side useTurnstile check
		// (which runs after this filter) will reject if the form requires it.
		if ( ! array_key_exists( 'turnstile_token', $context ) ) {
			return $pre_result;
		}

		$token = $context['turnstile_token'];

		// Form has Turnstile enabled but no token was provided (bot or JS error).
		if ( empty( $token ) ) {
			return new WP_Error( 'turnstile_missing', 'Turnstile verification required.' );
		}

		$response = wp_safe_remote_post(
			self::VERIFY_URL,
			[
				'timeout' => 10,
				'body'    => [
					'secret'   => $secret_key,
					'response' => $token,
				],
			]
		);

		/**
		 * Filter whether Turnstile should fail open when Cloudflare is unreachable.
		 *
		 * Default: false (fail closed). If Cloudflare is down, submissions are
		 * rejected — a form configured with CAPTCHA should keep its CAPTCHA
		 * protection rather than silently disappearing. Sites that prefer to
		 * accept submissions during provider outages can return true.
		 *
		 * @since 2.6.0
		 * @param bool $fail_open Whether to fail open. Default false.
		 * @param int  $form_id   The form post ID.
		 */
		$fail_open = (bool) apply_filters( 'generateblocks_form_turnstile_fail_open', false, $form_id );

		if ( is_wp_error( $response ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'GenerateBlocks Form: Turnstile verification request failed: ' . $response->get_error_message() );
			}

			if ( $fail_open ) {
				return $pre_result;
			}

			return new WP_Error( 'turnstile_unreachable', 'Turnstile verification unavailable.' );
		}

		$status = wp_remote_retrieve_response_code( $response );

		if ( $status < 200 || $status >= 300 ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'GenerateBlocks Form: Turnstile returned HTTP ' . $status );
			}

			if ( $fail_open ) {
				return $pre_result;
			}

			return new WP_Error( 'turnstile_unreachable', 'Turnstile verification unavailable.' );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || empty( $body['success'] ) ) {
			return new WP_Error( 'turnstile_failed', 'Turnstile verification failed.' );
		}

		return $pre_result;
	}

	/**
	 * Get the Turnstile site key.
	 *
	 * Priority: wp-config.php define → database option.
	 *
	 * @return string The site key, or empty string if not configured.
	 */
	public static function get_site_key() {
		return GenerateBlocks_Pro_Form_Integration_Rest::get_site_key_for( 'turnstile' );
	}

	/**
	 * Get the Turnstile secret key.
	 *
	 * Uses the standard integration key lookup (define → registry → db).
	 *
	 * @return string The secret key, or empty string if not configured.
	 */
	public static function get_secret_key() {
		return GenerateBlocks_Pro_Form_Integration_Rest::get_api_key_for( 'turnstile' );
	}

	/**
	 * Test the Turnstile secret key.
	 *
	 * Sends a verification request with an empty token. Cloudflare will return
	 * "invalid-input-secret" if the key is wrong, or other error codes if the
	 * key is valid but the token is (expectedly) invalid.
	 *
	 * @param string $secret_key The secret key to test.
	 * @return true|WP_Error
	 */
	public static function test_connection( $secret_key ) {
		$response = wp_safe_remote_post(
			self::VERIFY_URL,
			[
				'timeout' => 10,
				'body'    => [
					'secret'   => $secret_key,
					'response' => '',
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'connection_failed', 'Could not connect to Cloudflare.' );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		// If the secret key is invalid, Cloudflare includes "invalid-input-secret"
		// in the error-codes array. Any other error means the key is valid.
		$error_codes = $body['error-codes'] ?? [];

		if ( in_array( 'invalid-input-secret', $error_codes, true ) ) {
			return new WP_Error( 'invalid_key', 'Invalid Turnstile secret key.' );
		}

		return true;
	}
}
