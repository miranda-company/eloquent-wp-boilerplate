<?php
/**
 * Boot the optional Forms module.
 *
 * Nothing in this file is loaded unless Forms is enabled. Keep all Forms
 * class loading, hooks, routes, post types, and provider registration behind
 * this boundary so disabled Forms stay out of the request lifecycle.
 *
 * @package GenerateBlocks Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once GENERATEBLOCKS_PRO_DIR . 'includes/form/class-form-sanitizer.php';
require_once GENERATEBLOCKS_PRO_DIR . 'includes/form/class-form-spam.php';
require_once GENERATEBLOCKS_PRO_DIR . 'includes/form/class-form-turnstile.php';
require_once GENERATEBLOCKS_PRO_DIR . 'includes/form/class-form-http.php';
require_once GENERATEBLOCKS_PRO_DIR . 'includes/form/class-form-action-registry.php';
require_once GENERATEBLOCKS_PRO_DIR . 'includes/form/class-form-integration-registry.php';
require_once GENERATEBLOCKS_PRO_DIR . 'includes/form/class-form-action-email.php';
require_once GENERATEBLOCKS_PRO_DIR . 'includes/form/class-form-action-email-signup.php';
require_once GENERATEBLOCKS_PRO_DIR . 'includes/form/integrations/builtin-loader.php';
require_once GENERATEBLOCKS_PRO_DIR . 'includes/form/class-form-action-webhook.php';
require_once GENERATEBLOCKS_PRO_DIR . 'includes/form/class-form-action-confirmation-email.php';
require_once GENERATEBLOCKS_PRO_DIR . 'includes/form/class-form-post-type.php';
require_once GENERATEBLOCKS_PRO_DIR . 'includes/form/class-form-schema-builder.php';
require_once GENERATEBLOCKS_PRO_DIR . 'includes/form/class-form-render.php';
require_once GENERATEBLOCKS_PRO_DIR . 'includes/form/class-form-preview-rest.php';
require_once GENERATEBLOCKS_PRO_DIR . 'includes/form/class-form-processor.php';
require_once GENERATEBLOCKS_PRO_DIR . 'includes/form/class-form-rest.php';
require_once GENERATEBLOCKS_PRO_DIR . 'includes/form/class-form-integration-rest.php';
require_once GENERATEBLOCKS_PRO_DIR . 'includes/form/class-form-submissions.php';

// Initialize form system on 'init' so third-party code can hook in.
// Third-party form integrations can register on the
// generateblocks_form_register_integrations hook.
add_action( 'init', 'generateblocks_pro_init_form_system' );

/**
 * Initialize the form system.
 */
function generateblocks_pro_init_form_system() {
	/**
	 * Register email integration API keys.
	 *
	 * Use generateblocks_pro_set_email_integration() inside this hook
	 * to provide API keys for email marketing services.
	 *
	 * @since 2.6.0
	 *
	 * @example
	 * add_action( 'generateblocks_form_register_email_integrations', function() {
	 *     generateblocks_pro_set_email_integration( 'mailchimp', 'your-api-key' );
	 * } );
	 */
	do_action( 'generateblocks_form_register_email_integrations' );

	GenerateBlocks_Pro_Form_Action_Email::init();
	GenerateBlocks_Pro_Form_Action_Email_Signup::init();
	GenerateBlocks_Pro_Form_Action_Webhook::init();
	GenerateBlocks_Pro_Form_Action_Confirmation_Email::init();
	GenerateBlocks_Pro_Form_Turnstile::init();

	/**
	 * Register custom form integration providers.
	 *
	 * Use generateblocks_pro_register_form_integration() inside this hook to
	 * expose a provider in the form editor.
	 * This runs after built-ins, so registering an existing ID intentionally
	 * replaces the built-in definition for this request.
	 *
	 * @since 2.6.0
	 */
	do_action( 'generateblocks_form_register_integrations' );

	GenerateBlocks_Pro_Form_Post_Type::get_instance()->init();
	GenerateBlocks_Pro_Form_Schema_Builder::get_instance()->init();
	GenerateBlocks_Pro_Form_Preview_Rest::get_instance()->init();
	GenerateBlocks_Pro_Form_Rest::get_instance()->init();
	GenerateBlocks_Pro_Form_Integration_Rest::get_instance()->init();
	GenerateBlocks_Pro_Form_Submissions::get_instance()->init();
}
