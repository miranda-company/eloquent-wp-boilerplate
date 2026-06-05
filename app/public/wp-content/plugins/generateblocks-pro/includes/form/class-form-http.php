<?php
/**
 * Shared HTTP helpers for form integrations.
 *
 * Converts non-2xx responses from third-party APIs into actionable WP_Errors
 * so auth failures, rate limits, and outages don't silently look like
 * "no lists / no forms / no groups" in the editor.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form HTTP helper class.
 */
class GenerateBlocks_Pro_Form_Http {

	/**
	 * Default timeout for form submission HTTP actions.
	 *
	 * Multiple actions can run for a single submission, so keep this lower
	 * than WordPress' default to avoid tying up PHP workers for too long.
	 */
	const DEFAULT_TIMEOUT = 8;

	/**
	 * Convert a non-2xx HTTP response into a WP_Error with a useful message.
	 *
	 * Returns null when the response is a 2xx so callers can continue. Checks
	 * common JSON error-message keys used across providers.
	 *
	 * @param array  $response The wp_remote_* response (not a WP_Error — caller checks first).
	 * @param string $service  Human-readable service name for the error message.
	 * @return WP_Error|null
	 */
	public static function response_error( $response, $service ) {
		$status = wp_remote_retrieve_response_code( $response );

		if ( $status >= 200 && $status < 300 ) {
			return null;
		}

		$body   = json_decode( wp_remote_retrieve_body( $response ), true );
		$detail = '';

		if ( is_array( $body ) ) {
			// Try each common error-message field.
			foreach ( [ 'detail', 'message', 'error' ] as $key ) {
				if ( ! empty( $body[ $key ] ) && is_string( $body[ $key ] ) ) {
					$detail = $body[ $key ];
					break;
				}
			}
		}

		$message = $service . ': ';

		if ( 401 === $status || 403 === $status ) {
			$message .= __( 'authentication failed — check your API key.', 'generateblocks-pro' );
		} elseif ( 429 === $status ) {
			$retry_after = wp_remote_retrieve_header( $response, 'retry-after' );
			$message    .= __( 'rate limit exceeded.', 'generateblocks-pro' );

			if ( $retry_after ) {
				$message .= ' ' . sprintf(
					/* translators: %s: seconds */
					__( 'Retry after %s seconds.', 'generateblocks-pro' ),
					sanitize_text_field( (string) $retry_after )
				);
			}
		} elseif ( $status >= 500 ) {
			$message .= __( 'provider error — try again later.', 'generateblocks-pro' );
		} elseif ( $detail ) {
			$message .= sanitize_text_field( $detail );
		} else {
			$message .= sprintf(
				/* translators: %d: HTTP status code */
				__( 'unexpected response (HTTP %d).', 'generateblocks-pro' ),
				$status
			);
		}

		return new WP_Error( 'provider_error', $message, [ 'status' => $status ] );
	}

	/**
	 * Normalize a submitted field value for provider payloads.
	 *
	 * Form fields can produce scalar values or arrays (for example checkbox
	 * groups). Provider merge/custom-field APIs expect strings, so flatten arrays
	 * consistently and reject objects or nested non-scalar array values.
	 *
	 * @param mixed $value Submitted value.
	 * @return string
	 */
	public static function normalize_field_value( $value ) {
		if ( is_array( $value ) ) {
			$parts = [];

			foreach ( $value as $part ) {
				if ( is_scalar( $part ) ) {
					$parts[] = (string) $part;
				}
			}

			$value = implode( ', ', $parts );
		}

		if ( is_object( $value ) ) {
			return '';
		}

		return trim( (string) $value );
	}
}
