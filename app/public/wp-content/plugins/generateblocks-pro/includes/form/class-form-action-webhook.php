<?php
/**
 * Webhook form action.
 *
 * POSTs form submission data as JSON to a configured URL.
 * Uses wp_safe_remote_post() to block requests to internal/private IPs.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Webhook form action class.
 */
class GenerateBlocks_Pro_Form_Action_Webhook {

	/**
	 * Register the webhook action with the registry.
	 */
	public static function init() {
		GenerateBlocks_Pro_Form_Action_Registry::register( 'webhook', [ __CLASS__, 'send' ] );
	}

	/**
	 * Validate the webhook action settings before delivery starts.
	 *
	 * @param array $form_settings The form block settings.
	 * @return true|WP_Error
	 */
	public static function validate_settings( $form_settings ) {
		$url = self::get_valid_webhook_url( $form_settings );

		return is_wp_error( $url ) ? $url : true;
	}

	/**
	 * Send the form submission to the configured webhook URL.
	 *
	 * @param array $sanitized_data The sanitized form data (field_name => ['value' => ..., 'label' => ..., 'type' => ...]).
	 * @param array $form_settings  The form block settings.
	 * @param array $context        Request context (post_id, page_url).
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public static function send( $sanitized_data, $form_settings, $context = [] ) {
		$webhook_url = self::get_valid_webhook_url( $form_settings );

		if ( is_wp_error( $webhook_url ) ) {
			return $webhook_url;
		}

		// Build a clean payload — field name => value (flat), plus metadata.
		$fields = [];

		foreach ( $sanitized_data as $field_name => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$fields[ $field_name ] = (string) ( $field['value'] ?? '' );
		}

		/**
		 * Filter the webhook payload before sending.
		 *
		 * @since 2.6.0
		 * @param array $payload        The webhook payload.
		 * @param array $sanitized_data The full sanitized form data with labels and types.
		 * @param array $form_settings  The form block settings.
		 * @param array $context        Request context.
		 */
		$payload = apply_filters(
			'generateblocks_form_webhook_payload',
			[
				'form_name' => ! empty( $context['form_id'] ) ? get_the_title( $context['form_id'] ) : '',
				'fields'    => $fields,
				'page_url'  => $context['page_url'] ?? '',
				'timestamp' => gmdate( 'c' ),
			],
			$sanitized_data,
			$form_settings,
			$context
		);

		// wp_safe_remote_post blocks requests to internal/private IPs (127.0.0.1,
		// 10.x.x.x, 169.254.169.254, etc.) via wp_http_validate_url(). This prevents
		// SSRF since the webhook URL is user-supplied. Redirection is disabled so a
		// 302 to an internal IP can't bypass the validation.
		$response = wp_safe_remote_post(
			$webhook_url,
			[
				'body'        => wp_json_encode( $payload ),
				'headers'     => [ 'Content-Type' => 'application/json' ],
				'timeout'     => GenerateBlocks_Pro_Form_Http::DEFAULT_TIMEOUT,
				'redirection' => 0,
				'data_format' => 'body',
			]
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'webhook_failed',
				'Webhook request failed: ' . $response->get_error_message()
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		// Accept any 2xx response.
		if ( $status_code < 200 || $status_code >= 300 ) {
			return new WP_Error(
				'webhook_failed',
				'Webhook returned status ' . $status_code . '.'
			);
		}

		return true;
	}

	/**
	 * Get the validated webhook URL.
	 *
	 * @param array $form_settings The form block settings.
	 * @return string|WP_Error
	 */
	private static function get_valid_webhook_url( $form_settings ) {
		$webhook_url = $form_settings['actionSettings']['webhook']['url'] ?? '';

		if ( empty( $webhook_url ) ) {
			return new WP_Error(
				'missing_webhook_url',
				'Webhook URL is not configured.',
				[ 'status' => 400 ]
			);
		}

		$webhook_url = esc_url_raw( $webhook_url, [ 'https', 'http' ] );

		if ( empty( $webhook_url ) || ! wp_http_validate_url( $webhook_url ) ) {
			return new WP_Error(
				'invalid_webhook_url',
				'Invalid webhook URL.',
				[ 'status' => 400 ]
			);
		}

		return $webhook_url;
	}
}
