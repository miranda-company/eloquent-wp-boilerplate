<?php
/**
 * Form action registry.
 *
 * Extensible system for registering form submission handlers.
 * Third-party developers can register their own actions.
 * All callbacks receive sanitized data only.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form action registry class.
 */
class GenerateBlocks_Pro_Form_Action_Registry {

	/**
	 * Registered actions.
	 *
	 * @var array<string, callable>
	 */
	private static $actions = [];

	/**
	 * Register a form action.
	 *
	 * @param string   $name     The action identifier (e.g., 'email', 'mailchimp').
	 * @param callable $callback The handler function. Signature: function( array $sanitized_data, array $form_settings ): WP_Error|true.
	 */
	public static function register( $name, $callback ) {
		if ( is_callable( $callback ) ) {
			self::$actions[ sanitize_key( $name ) ] = $callback;
		}
	}

	/**
	 * Get a registered action callback.
	 *
	 * @param string $name The action identifier.
	 * @return callable|null The callback or null if not registered.
	 */
	public static function get( $name ) {
		return self::$actions[ sanitize_key( $name ) ] ?? null;
	}

	/**
	 * Unregister a form action.
	 *
	 * @param string $name The action identifier.
	 */
	public static function unregister( $name ) {
		unset( self::$actions[ sanitize_key( $name ) ] );
	}

	/**
	 * Get all registered action names.
	 *
	 * @return array List of registered action identifiers.
	 */
	public static function get_registered() {
		return array_keys( self::$actions );
	}
}
