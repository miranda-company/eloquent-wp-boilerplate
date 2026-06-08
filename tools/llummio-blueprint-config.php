<?php
/**
 * Shared blueprint settings used by maintenance helpers.
 */

return array(
	'theme_slug'        => 'llummio-blueprint',
	'demo_page_id'      => 8,
	'demo_page_title'   => 'Demo Page',
	'required_plugins'  => array(
		'advanced-custom-fields/acf.php',
		'create-block-theme/create-block-theme.php',
		'generateblocks-pro/plugin.php',
		'generateblocks/plugin.php',
		'llummio-editor-helpers/llummio-editor-helpers.php',
		'llummio-forms/llummio-forms.php',
		'llummio-svg-uploads/llummio-svg-uploads.php',
	),
	'plugin_slugs'      => array(
		'advanced-custom-fields',
		'create-block-theme',
		'generateblocks',
		'generateblocks-pro',
		'llummio-editor-helpers',
		'llummio-forms',
		'llummio-svg-uploads',
	),
	'forbidden_plugins' => array(
		'fluentform',
		'generatecloud',
		'search-filter',
		'wp-optimize',
		'wp-svg-images',
	),
);
