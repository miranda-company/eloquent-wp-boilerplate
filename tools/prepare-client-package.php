<?php
/**
 * Create a clean package of the reusable blueprint files for a new Local site.
 *
 * Run from the repository root:
 * php tools/prepare-client-package.php
 *
 * Optional:
 * php tools/prepare-client-package.php --name=client-name --zip
 */

$config  = require __DIR__ . DIRECTORY_SEPARATOR . 'llummio-blueprint-config.php';
$options = llummio_package_parse_options( $argv );
$root    = dirname( __DIR__ );
$name    = isset( $options['name'] ) ? llummio_package_safe_name( (string) $options['name'] ) : 'llummio-wp-blueprint';
$stamp   = date( 'Ymd-His' );
$output  = isset( $options['output'] )
	? rtrim( (string) $options['output'], "\\/" )
	: $root . DIRECTORY_SEPARATOR . ".blueprint-package-{$name}-{$stamp}";

if ( is_dir( $output ) || is_file( $output ) ) {
	llummio_package_fail( "Output already exists: {$output}" );
}

llummio_package_run_blueprint_check( $root, $config );
llummio_package_make_dir( $output );

$copy_plan = array(
	'README.md' => 'README.md',
	'app/sql/starter.sql' => 'app/sql/starter.sql',
	'docs' => 'docs',
	'tools' => 'tools',
	'app/public/wp-content/themes/' . $config['theme_slug'] => 'app/public/wp-content/themes/' . $config['theme_slug'],
);

foreach ( $config['plugin_slugs'] as $plugin_slug ) {
	$copy_plan[ 'app/public/wp-content/plugins/' . $plugin_slug ] = 'app/public/wp-content/plugins/' . $plugin_slug;
}

foreach ( $copy_plan as $source => $destination ) {
	llummio_package_copy_path(
		$root . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $source ),
		$output . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $destination )
	);
}

echo "Prepared package folder:\n{$output}\n";

if ( isset( $options['zip'] ) ) {
	$zip_path = $output . '.zip';
	llummio_package_zip_dir( $output, $zip_path );
	echo "Prepared package zip:\n{$zip_path}\n";
}

echo "\nCopy this package into the new Local site folder without overwriting Local's WordPress core files or wp-config.php.\n";

/**
 * @param array<int, string> $argv Raw CLI args.
 * @return array<string, string|bool>
 */
function llummio_package_parse_options( array $argv ): array {
	$options = array();

	foreach ( array_slice( $argv, 1 ) as $arg ) {
		if ( '--zip' === $arg ) {
			$options['zip'] = true;
			continue;
		}

		if ( str_starts_with( $arg, '--name=' ) ) {
			$options['name'] = substr( $arg, 7 );
			continue;
		}

		if ( str_starts_with( $arg, '--output=' ) ) {
			$options['output'] = substr( $arg, 9 );
			continue;
		}

		llummio_package_fail( "Unknown option: {$arg}" );
	}

	return $options;
}

/**
 * @param string $name Raw name.
 * @return string
 */
function llummio_package_safe_name( string $name ): string {
	$name = strtolower( trim( $name ) );
	$name = preg_replace( '/[^a-z0-9_-]+/', '-', $name ) ?? '';
	$name = trim( $name, '-' );

	return '' !== $name ? $name : 'client';
}

/**
 * @param string              $root Repository root.
 * @param array<string,mixed> $config Shared config.
 * @return void
 */
function llummio_package_run_blueprint_check( string $root, array $config ): void {
	$required_files = array(
		'README.md',
		'app/sql/starter.sql',
		'docs/07-plugin-stack.md',
		'docs/08-internal-plugins.md',
		'tools/check-blueprint.php',
		'tools/setup-client-site.php',
		'tools/update-starter-demo-page.php',
		'app/public/wp-content/themes/' . $config['theme_slug'] . '/style.css',
		'app/public/wp-content/themes/' . $config['theme_slug'] . '/functions.php',
		'app/public/wp-content/themes/' . $config['theme_slug'] . '/theme.json',
	);

	foreach ( $config['required_plugins'] as $plugin ) {
		$required_files[] = 'app/public/wp-content/plugins/' . $plugin;
	}

	foreach ( $required_files as $file ) {
		$path = $root . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $file );

		if ( ! is_file( $path ) ) {
			llummio_package_fail( "Blueprint preflight failed. Missing file: {$file}" );
		}
	}

	for ( $i = 1; $i <= 10; $i++ ) {
		$pattern = $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . sprintf( '%02d-*', $i );
		$matches = glob( $pattern ) ?: array();

		if ( 1 !== count( $matches ) ) {
			llummio_package_fail( sprintf( 'Blueprint preflight failed. Expected one docs/%02d-* file.', $i ) );
		}
	}

	$starter_sql = file_get_contents( $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'starter.sql' );

	if ( false === $starter_sql ) {
		llummio_package_fail( 'Blueprint preflight failed. Could not read app/sql/starter.sql.' );
	}

	$expected_sql = array(
		'Demo Page row' => 'INSERT INTO `wp_posts` VALUES (' . (int) $config['demo_page_id'] . ',',
		'Demo Page title' => "'" . $config['demo_page_title'] . "'",
		'front page option' => "'page_on_front','" . (int) $config['demo_page_id'] . "'",
		'theme option' => "'template','" . $config['theme_slug'] . "'",
		'stylesheet option' => "'stylesheet','" . $config['theme_slug'] . "'",
	);

	foreach ( $config['required_plugins'] as $plugin ) {
		$expected_sql[ 'active plugin ' . $plugin ] = $plugin;
	}

	foreach ( $expected_sql as $label => $needle ) {
		if ( false === strpos( $starter_sql, $needle ) ) {
			llummio_package_fail( "Blueprint preflight failed. Missing {$label} in starter.sql." );
		}
	}

	foreach ( $config['forbidden_plugins'] as $plugin ) {
		if ( false !== strpos( $starter_sql, $plugin ) ) {
			llummio_package_fail( "Blueprint preflight failed. Found removed plugin in starter.sql: {$plugin}" );
		}
	}
}

/**
 * @param string $path Directory path.
 * @return void
 */
function llummio_package_make_dir( string $path ): void {
	if ( is_dir( $path ) ) {
		return;
	}

	if ( ! mkdir( $path, 0777, true ) && ! is_dir( $path ) ) {
		llummio_package_fail( "Could not create directory: {$path}" );
	}
}

/**
 * @param string $source Source path.
 * @param string $destination Destination path.
 * @return void
 */
function llummio_package_copy_path( string $source, string $destination ): void {
	if ( is_file( $source ) ) {
		llummio_package_make_dir( dirname( $destination ) );

		if ( ! copy( $source, $destination ) ) {
			llummio_package_fail( "Could not copy file: {$source}" );
		}

		return;
	}

	if ( ! is_dir( $source ) ) {
		llummio_package_fail( "Missing package source: {$source}" );
	}

	llummio_package_make_dir( $destination );

	$items = scandir( $source );

	if ( false === $items ) {
		llummio_package_fail( "Could not read directory: {$source}" );
	}

	foreach ( $items as $item ) {
		if ( '.' === $item || '..' === $item || llummio_package_should_skip( $item ) ) {
			continue;
		}

		llummio_package_copy_path(
			$source . DIRECTORY_SEPARATOR . $item,
			$destination . DIRECTORY_SEPARATOR . $item
		);
	}
}

/**
 * @param string $name File or folder name.
 * @return bool
 */
function llummio_package_should_skip( string $name ): bool {
	$skipped_names = array(
		'.DS_Store' => true,
		'Thumbs.db' => true,
	);

	if ( isset( $skipped_names[ $name ] ) ) {
		return true;
	}

	return str_ends_with( strtolower( $name ), '.log' );
}

/**
 * @param string $dir Directory to zip.
 * @param string $zip_path Output zip path.
 * @return void
 */
function llummio_package_zip_dir( string $dir, string $zip_path ): void {
	if ( ! class_exists( 'ZipArchive' ) ) {
		llummio_package_fail( 'ZipArchive is not available in this PHP install. Run without --zip and zip the package folder manually if needed.' );
	}

	$zip = new ZipArchive();

	if ( true !== $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
		llummio_package_fail( "Could not create zip: {$zip_path}" );
	}

	$root_length = strlen( rtrim( $dir, DIRECTORY_SEPARATOR ) ) + 1;
	$iterator    = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::SELF_FIRST
	);

	foreach ( $iterator as $item ) {
		$path = $item->getPathname();
		$name = str_replace( DIRECTORY_SEPARATOR, '/', substr( $path, $root_length ) );

		if ( $item->isDir() ) {
			$zip->addEmptyDir( $name );
		} else {
			$zip->addFile( $path, $name );
		}
	}

	$zip->close();
}

/**
 * @param string $message Error message.
 * @return never
 */
function llummio_package_fail( string $message ): void {
	fwrite( STDERR, $message . "\n" );
	exit( 1 );
}
