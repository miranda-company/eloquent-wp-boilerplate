<?php
/**
 * Run lightweight checks before committing blueprint changes.
 *
 * Run from the repository root:
 * php tools/check-blueprint.php
 */

$config = require __DIR__ . DIRECTORY_SEPARATOR . 'llummio-blueprint-config.php';
$root   = dirname( __DIR__ );
$failed = false;

$paths = array(
	'starter SQL' => 'app/sql/starter.sql',
	'theme style.css' => 'app/public/wp-content/themes/' . $config['theme_slug'] . '/style.css',
	'theme functions.php' => 'app/public/wp-content/themes/' . $config['theme_slug'] . '/functions.php',
	'theme theme.json' => 'app/public/wp-content/themes/' . $config['theme_slug'] . '/theme.json',
	'plugin stack docs' => 'docs/07-plugin-stack.md',
	'internal plugin docs' => 'docs/08-internal-plugins.md',
);

foreach ( $paths as $label => $path ) {
	llummio_check_file( $label, $root . DIRECTORY_SEPARATOR . $path );
}

foreach ( $config['required_plugins'] as $plugin ) {
	llummio_check_file( "plugin {$plugin}", $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $plugin ) );
}

$starter_sql_path = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'starter.sql';
$starter_sql      = is_file( $starter_sql_path ) ? file_get_contents( $starter_sql_path ) : '';

if ( false === $starter_sql || '' === $starter_sql ) {
	llummio_check_fail( 'starter SQL readable', 'starter.sql is missing or empty.' );
} else {
	llummio_check_starter_sql( $starter_sql, $config );
}

llummio_check_numbered_docs( $root );

if ( $failed ) {
	echo "\nBlueprint check failed.\n";
	exit( 1 );
}

echo "\nBlueprint check passed.\n";

/**
 * @param string $label Label.
 * @param string $path Absolute path.
 * @return void
 */
function llummio_check_file( string $label, string $path ): void {
	if ( is_file( $path ) ) {
		llummio_check_ok( $label );
		return;
	}

	llummio_check_fail( $label, "Missing file: {$path}" );
}

/**
 * @param string              $starter_sql Starter SQL.
 * @param array<string,mixed> $config Config.
 * @return void
 */
function llummio_check_starter_sql( string $starter_sql, array $config ): void {
	$demo_id    = (int) $config['demo_page_id'];
	$demo_title = (string) $config['demo_page_title'];
	$theme_slug = (string) $config['theme_slug'];

	llummio_check_contains( 'starter Demo Page row', $starter_sql, "INSERT INTO `wp_posts` VALUES ({$demo_id}," );
	llummio_check_contains( 'starter Demo Page title', $starter_sql, "'{$demo_title}'" );
	llummio_check_contains( 'starter front page option', $starter_sql, "'page_on_front','{$demo_id}'" );
	llummio_check_contains( 'starter theme template option', $starter_sql, "'template','{$theme_slug}'" );
	llummio_check_contains( 'starter theme stylesheet option', $starter_sql, "'stylesheet','{$theme_slug}'" );

	foreach ( $config['required_plugins'] as $plugin ) {
		llummio_check_contains( "active plugin {$plugin}", $starter_sql, $plugin );
	}

	foreach ( $config['forbidden_plugins'] as $plugin ) {
		if ( false === strpos( $starter_sql, $plugin ) ) {
			llummio_check_ok( "removed plugin {$plugin}" );
		} else {
			llummio_check_fail( "removed plugin {$plugin}", "Found {$plugin} in starter.sql." );
		}
	}
}

/**
 * @param string $root Repository root.
 * @return void
 */
function llummio_check_numbered_docs( string $root ): void {
	for ( $i = 1; $i <= 10; $i++ ) {
		$pattern = $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . sprintf( '%02d-*', $i );
		$matches = glob( $pattern ) ?: array();

		if ( 1 === count( $matches ) ) {
			llummio_check_ok( sprintf( 'doc %02d exists', $i ) );
			continue;
		}

		llummio_check_fail( sprintf( 'doc %02d exists', $i ), "Expected one match for {$pattern}" );
	}
}

/**
 * @param string $label Label.
 * @param string $haystack Search target.
 * @param string $needle Expected text.
 * @return void
 */
function llummio_check_contains( string $label, string $haystack, string $needle ): void {
	if ( false !== strpos( $haystack, $needle ) ) {
		llummio_check_ok( $label );
		return;
	}

	llummio_check_fail( $label, "Missing expected text: {$needle}" );
}

/**
 * @param string $label Label.
 * @return void
 */
function llummio_check_ok( string $label ): void {
	echo "[OK] {$label}\n";
}

/**
 * @param string $label Label.
 * @param string $message Message.
 * @return void
 */
function llummio_check_fail( string $label, string $message ): void {
	global $failed;

	$failed = true;
	echo "[FAIL] {$label}: {$message}\n";
}
