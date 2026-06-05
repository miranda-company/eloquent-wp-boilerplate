<?php
/**
 * Built-in form integration loader.
 *
 * Loads the official Pro integration providers (Mailchimp, Kit, MailerLite,
 * ActiveCampaign, Brevo) on the `generateblocks_form_register_integrations`
 * hook. Built-ins use the same public registration entry point as third-party
 * integrations, so the seam between Pro and any future connectors plugin stays
 * a directory move rather than a code rewrite.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Priority 5 so built-ins run first; third-party callbacks at default priority
// 10 can then re-register the same provider ID to replace the built-in, which
// is the documented override contract in class-form-integration-registry.php.
add_action( 'generateblocks_form_register_integrations', 'generateblocks_pro_load_builtin_integrations', 5 );

/**
 * Require and initialize the built-in integration providers.
 */
function generateblocks_pro_load_builtin_integrations() {
	$dir = GENERATEBLOCKS_PRO_DIR . 'includes/form/integrations/';

	require_once $dir . 'mailchimp.php';
	require_once $dir . 'convertkit.php';
	require_once $dir . 'mailerlite.php';
	require_once $dir . 'activecampaign.php';
	require_once $dir . 'brevo.php';

	GenerateBlocks_Pro_Form_Action_Mailchimp::init();
	GenerateBlocks_Pro_Form_Action_ConvertKit::init();
	GenerateBlocks_Pro_Form_Action_MailerLite::init();
	GenerateBlocks_Pro_Form_Action_ActiveCampaign::init();
	GenerateBlocks_Pro_Form_Action_Brevo::init();
}
