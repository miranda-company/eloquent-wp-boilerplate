<?php
/**
 * Usage search utilities trait.
 *
 * @package GenerateBlocksPro\Utils
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Shared utilities for usage search functionality.
 *
 * @since 2.5.0
 */
trait GenerateBlocks_Pro_Usage_Search {
	/**
	 * Get cached usage data.
	 *
	 * @param string $cache_key The cache key.
	 * @return mixed|false The cached data or false if not found.
	 */
	protected function get_usage_cache( $cache_key ) {
		return get_transient( $cache_key );
	}

	/**
	 * Set cached usage data.
	 *
	 * @param string $cache_key  The cache key.
	 * @param mixed  $data       The data to cache.
	 * @param int    $expiration Cache expiration in seconds (default: 300 = 5 minutes).
	 * @return bool True if the value was set, false otherwise.
	 */
	protected function set_usage_cache( $cache_key, $data, $expiration = 300 ) {
		return set_transient( $cache_key, $data, $expiration );
	}

	/**
	 * Clear all usage caches matching a pattern.
	 *
	 * @param string $pattern The cache key pattern (e.g., 'gb_style_usage_', 'gb_condition_usage_').
	 * @return void
	 */
	protected function clear_usage_caches( $pattern ) {
		global $wpdb;

		// Delete all transients matching the pattern.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_' . $pattern ) . '%'
			)
		);

		// Also clear timeout transients.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_timeout_' . $pattern ) . '%'
			)
		);
	}
}
