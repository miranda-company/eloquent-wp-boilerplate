<?php
/**
 * Conditions.
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Core files - order matters!
require_once 'interface-condition.php';
require_once 'class-condition-abstract.php';
require_once 'class-conditions-registry.php';

// Individual condition types.
require_once 'conditions/class-condition-location.php';
require_once 'conditions/class-condition-query-arg.php';
require_once 'conditions/class-condition-user-role.php';
require_once 'conditions/class-condition-date-time.php';
require_once 'conditions/class-condition-device.php';
require_once 'conditions/class-condition-referrer.php';
require_once 'conditions/class-condition-post-meta.php';
require_once 'conditions/class-condition-user-meta.php';
require_once 'conditions/class-condition-cookie.php';
require_once 'conditions/class-condition-language.php';
require_once 'conditions/class-condition-options.php';
require_once 'conditions/class-condition-author.php';

// Main conditions class.
require_once 'class-conditions.php';

// REST API.
require_once 'class-conditions-rest.php';

// Post type registration.
require_once 'class-conditions-post-type.php';

// Admin dashboard.
require_once 'class-conditions-dashboard.php';
