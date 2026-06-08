<?php
/**
 * Set up a new Local client site after the blueprint files have been copied in.
 *
 * Run from the new Local site's Site Shell:
 * php ..\..\tools\setup-client-site.php --url=http://client-name.local --yes
 *
 * If Local uses a custom database port and WP-CLI cannot connect:
 * php ..\..\tools\setup-client-site.php --url=http://client-name.local --db-host=localhost:10005 --yes
 */

$config  = require __DIR__ . DIRECTORY_SEPARATOR . 'llummio-blueprint-config.php';
$options = llummio_setup_parse_options( $argv );
$root    = dirname( __DIR__ );
$public  = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'public';
$sql     = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'starter.sql';

if ( empty( $options['url'] ) ) {
	llummio_setup_fail( 'Missing required --url option. Example: --url=http://client-name.local' );
}

if ( empty( $options['yes'] ) ) {
	llummio_setup_fail( 'This imports the starter database. Add --yes when you are ready to replace this Local database.' );
}

if ( ! is_dir( $public ) ) {
	llummio_setup_fail( "Could not find WordPress public directory: {$public}" );
}

if ( ! is_file( $sql ) ) {
	llummio_setup_fail( "Could not find starter database: {$sql}" );
}

chdir( $public );

$site_url = rtrim( (string) $options['url'], '/' );

llummio_setup_step( 'Checking WP-CLI.' );
llummio_setup_run( array( 'wp', '--info' ) );

if ( ! empty( $options['db-host'] ) ) {
	llummio_setup_step( 'Updating Local database host.' );
	llummio_setup_run( array( 'wp', 'config', 'set', 'DB_HOST', (string) $options['db-host'] ) );
}

llummio_setup_step( 'Checking database connection.' );
$db_check = llummio_setup_run( array( 'wp', 'db', 'check' ), false );

if ( 0 !== $db_check['status'] ) {
	llummio_setup_fail(
		"WP-CLI could not connect to the database.\n" .
		"If Local shows a custom database port, rerun with --db-host=localhost:PORT.\n" .
		trim( $db_check['error'] )
	);
}

llummio_setup_step( 'Importing starter database.' );
llummio_setup_run( array( 'wp', 'db', 'reset', '--yes' ) );
llummio_setup_run( array( 'wp', 'db', 'import', '../sql/starter.sql' ) );

llummio_setup_step( 'Setting Local site URL.' );
llummio_setup_run( array( 'wp', 'option', 'update', 'siteurl', $site_url ) );
llummio_setup_run( array( 'wp', 'option', 'update', 'home', $site_url ) );

llummio_setup_step( 'Activating theme.' );
llummio_setup_run( array( 'wp', 'theme', 'activate', $config['theme_slug'] ) );

llummio_setup_step( 'Activating approved plugins.' );
llummio_setup_run( array_merge( array( 'wp', 'plugin', 'activate' ), $config['plugin_slugs'] ) );

llummio_setup_step( 'Refreshing permalinks.' );
llummio_setup_run( array( 'wp', 'rewrite', 'flush' ) );

llummio_setup_step( 'Verifying starter site.' );
$siteurl = trim( llummio_setup_run( array( 'wp', 'option', 'get', 'siteurl' ) )['output'] );
$home    = trim( llummio_setup_run( array( 'wp', 'option', 'get', 'home' ) )['output'] );
$title   = trim( llummio_setup_run( array( 'wp', 'post', 'get', (string) $config['demo_page_id'], '--field=post_title' ) )['output'] );

if ( $site_url !== $siteurl || $site_url !== $home ) {
	llummio_setup_fail( "URL verification failed. siteurl={$siteurl}; home={$home}" );
}

if ( $config['demo_page_title'] !== $title ) {
	llummio_setup_fail( "Demo Page verification failed. Expected {$config['demo_page_title']}, found {$title}." );
}

echo "\nSetup complete.\n";
echo "Local URL: {$site_url}\n";
echo "Starter page: {$title}\n";

/**
 * @param array<int, string> $argv Raw CLI args.
 * @return array<string, string|bool>
 */
function llummio_setup_parse_options( array $argv ): array {
	$options = array();

	foreach ( array_slice( $argv, 1 ) as $arg ) {
		if ( '--yes' === $arg ) {
			$options['yes'] = true;
			continue;
		}

		if ( str_starts_with( $arg, '--url=' ) ) {
			$options['url'] = substr( $arg, 6 );
			continue;
		}

		if ( str_starts_with( $arg, '--db-host=' ) ) {
			$options['db-host'] = substr( $arg, 10 );
			continue;
		}

		llummio_setup_fail( "Unknown option: {$arg}" );
	}

	return $options;
}

/**
 * @param string $message Message.
 * @return void
 */
function llummio_setup_step( string $message ): void {
	echo "\n> {$message}\n";
}

/**
 * @param array<int, string> $command Command parts.
 * @param bool               $fail_on_error Whether to exit on failure.
 * @return array{status:int, output:string, error:string}
 */
function llummio_setup_run( array $command, bool $fail_on_error = true ): array {
	$command_string = implode( ' ', array_map( 'escapeshellarg', $command ) );

	$descriptor_spec = array(
		0 => array( 'pipe', 'r' ),
		1 => array( 'pipe', 'w' ),
		2 => array( 'pipe', 'w' ),
	);

	$process = proc_open( $command_string, $descriptor_spec, $pipes );

	if ( ! is_resource( $process ) ) {
		llummio_setup_fail( "Could not start command: {$command_string}" );
	}

	fclose( $pipes[0] );
	$output = stream_get_contents( $pipes[1] );
	$error  = stream_get_contents( $pipes[2] );
	fclose( $pipes[1] );
	fclose( $pipes[2] );

	$status = proc_close( $process );

	if ( 0 !== $status && $fail_on_error ) {
		llummio_setup_fail( trim( $error ) ?: "Command failed: {$command_string}" );
	}

	if ( '' !== trim( $output ) ) {
		echo trim( $output ) . "\n";
	}

	return array(
		'status' => $status,
		'output' => $output,
		'error'  => $error,
	);
}

/**
 * @param string $message Error message.
 * @return never
 */
function llummio_setup_fail( string $message ): void {
	fwrite( STDERR, $message . "\n" );
	exit( 1 );
}
