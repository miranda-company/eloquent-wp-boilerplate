<?php
/**
 * Form spam protection.
 *
 * Honeypot and timestamp validation.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form spam protection class.
 */
class GenerateBlocks_Pro_Form_Spam {

	/**
	 * Maximum age of a form token in seconds.
	 *
	 * 1 hour — long enough for a reasonable fill-out window, short enough to
	 * limit replay. The previous 24h window allowed a single harvested token
	 * to be reused all day.
	 *
	 * @var int
	 */
	const MAX_AGE_SECONDS = 3600;

	/**
	 * Generate an HMAC-signed timestamp token for embedding in the form.
	 *
	 * Signs the triple (timestamp, form_id, post_id) so a harvested token can't
	 * be replayed against a different form or a different page hosting the same
	 * form. Format: "timestamp-hmac".
	 *
	 * @param int $form_id The form post ID.
	 * @param int $post_id The post/page ID containing the form.
	 * @return string The signed timestamp token.
	 */
	public static function generate_timestamp( $form_id = 0, $post_id = 0 ) {
		$time = (string) time();
		$hmac = hash_hmac( 'sha256', self::sign_payload( $time, $form_id, $post_id ), wp_salt() );

		return $time . '-' . $hmac;
	}

	/**
	 * Extract the timestamp portion of a signed token, after verifying the HMAC.
	 *
	 * Requires the same (form_id, post_id) used at generation time. Passing
	 * empty values still verifies — but only tokens generated with empty values
	 * will pass.
	 *
	 * @param string $signed  The signed timestamp token ("timestamp-hmac").
	 * @param int    $form_id The form post ID that the token was issued for.
	 * @param int    $post_id The post/page ID the token was issued for.
	 * @return int|false The timestamp on success, false if the signature is invalid.
	 */
	public static function verified_timestamp( $signed, $form_id = 0, $post_id = 0 ) {
		if ( empty( $signed ) || ! is_string( $signed ) ) {
			return false;
		}

		$parts = explode( '-', $signed, 2 );

		if ( 2 !== count( $parts ) ) {
			return false;
		}

		$timestamp = absint( $parts[0] );

		if ( 0 === $timestamp ) {
			return false;
		}

		$expected = hash_hmac( 'sha256', self::sign_payload( (string) $timestamp, $form_id, $post_id ), wp_salt() );

		if ( ! hash_equals( $expected, $parts[1] ) ) {
			return false;
		}

		return $timestamp;
	}

	/**
	 * Check whether an elapsed-time window is valid.
	 *
	 * No minimum elapsed-time floor: post-refresh submissions and fast humans
	 * legitimately land in the same second the token was issued. HMAC + honeypot
	 * + size cap + origin check carry the anti-bot load.
	 *
	 * @param int $timestamp The verified timestamp.
	 * @return bool True if elapsed time is inside the allowed window.
	 */
	public static function is_fresh( $timestamp ) {
		$elapsed = time() - $timestamp;

		return $elapsed >= 0 && $elapsed <= self::MAX_AGE_SECONDS;
	}

	/**
	 * Derive the per-render honeypot field name.
	 *
	 * Scoped to the timestamp + form + post so two forms on the same render
	 * don't share a honeypot name, and a bot that scraped one form can't reuse
	 * the name against another.
	 *
	 * @param int $timestamp The verified timestamp.
	 * @param int $form_id   The form post ID.
	 * @param int $post_id   The post ID.
	 * @return string The expected honeypot field name (e.g. "gb_a1b2c3").
	 */
	public static function honeypot_field_name( $timestamp, $form_id = 0, $post_id = 0 ) {
		$payload = 'hp:' . self::sign_payload( (string) $timestamp, $form_id, $post_id );
		$hash    = hash_hmac( 'sha256', $payload, wp_salt() );

		return 'gb_' . substr( $hash, 0, 6 );
	}

	/**
	 * Build a canonical payload string for HMAC signing.
	 *
	 * Pipe-delimited and segment values are absint'd, so collisions between
	 * (form_id=12, post_id=345) and (form_id=1, post_id=2345) etc. are not
	 * possible — integers can't contain pipes.
	 *
	 * @param string $time    Timestamp string.
	 * @param int    $form_id Form post ID.
	 * @param int    $post_id Host page ID.
	 * @return string
	 */
	private static function sign_payload( $time, $form_id, $post_id ) {
		return $time . '|' . absint( $form_id ) . '|' . absint( $post_id );
	}
}
