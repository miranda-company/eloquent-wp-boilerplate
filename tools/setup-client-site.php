<?php
/**
 * Set up a new local client site after the blueprint files have been copied in.
 *
 * Run from the new site's WordPress public directory:
 * php ..\..\tools\setup-client-site.php --url=http://client-name.local --yes
 *
 * If the local tool uses a custom database host/port and WP-CLI cannot connect:
 * php ..\..\tools\setup-client-site.php --url=http://client-name.local --db-host=localhost:10005 --yes
 *
 * If WP-CLI is wrapped by the local tool:
 * php ..\..\tools\setup-client-site.php --url=http://client-name.local --wp-command="ddev wp" --yes
 */

$config  = require __DIR__ . DIRECTORY_SEPARATOR . 'llummio-blueprint-config.php';
$options = llummio_setup_parse_options( $argv );
$root    = dirname( __DIR__ );
$public  = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'public';
$sql     = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'starter.sql';
$wp      = isset( $options['wp-command'] ) ? (string) $options['wp-command'] : 'wp';

if ( empty( $options['url'] ) ) {
	llummio_setup_fail( 'Missing required --url option. Example: --url=http://client-name.local' );
}

if ( empty( $options['yes'] ) ) {
	llummio_setup_fail( 'This imports the starter database. Add --yes when you are ready to replace this local database.' );
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
llummio_setup_wp( $wp, array( '--info' ) );

if ( ! empty( $options['db-host'] ) ) {
	llummio_setup_step( 'Updating database host.' );
	llummio_setup_wp( $wp, array( 'config', 'set', 'DB_HOST', (string) $options['db-host'] ) );
} else {
	$detected_db_host = llummio_setup_detect_tool_db_host( $root, $wp );

	if ( null !== $detected_db_host ) {
		llummio_setup_step( 'Detected database host.' );
		llummio_setup_wp( $wp, array( 'config', 'set', 'DB_HOST', $detected_db_host ) );
	}
}

llummio_setup_step( 'Checking database connection.' );
$db_check = llummio_setup_wp( $wp, array( 'db', 'check' ), false );

if ( 0 !== $db_check['status'] ) {
	llummio_setup_fail(
		"WP-CLI could not connect to the database.\n" .
		"If your local tool shows a custom database host or port, rerun with --db-host=HOST:PORT. Example: --db-host=localhost:10005\n" .
		trim( $db_check['error'] )
	);
}

llummio_setup_step( 'Importing starter database.' );
llummio_setup_wp( $wp, array( 'db', 'reset', '--yes' ) );
llummio_setup_wp( $wp, array( 'db', 'import', '../sql/starter.sql' ) );

llummio_setup_step( 'Setting site URL.' );
llummio_setup_wp( $wp, array( 'option', 'update', 'siteurl', $site_url ) );
llummio_setup_wp( $wp, array( 'option', 'update', 'home', $site_url ) );

llummio_setup_step( 'Activating theme.' );
llummio_setup_wp( $wp, array( 'theme', 'activate', $config['theme_slug'] ) );

llummio_setup_step( 'Activating approved plugins.' );
llummio_setup_wp( $wp, array_merge( array( 'plugin', 'activate' ), $config['plugin_slugs'] ) );

llummio_setup_step( 'Refreshing permalinks.' );
llummio_setup_wp( $wp, array( 'rewrite', 'flush' ) );

llummio_setup_step( 'Verifying starter site.' );
$siteurl = trim( llummio_setup_wp( $wp, array( 'option', 'get', 'siteurl' ), true, false )['output'] );
$home    = trim( llummio_setup_wp( $wp, array( 'option', 'get', 'home' ), true, false )['output'] );
$title   = trim( llummio_setup_wp( $wp, array( 'post', 'get', (string) $config['demo_page_id'], '--field=post_title' ), true, false )['output'] );

if ( $site_url !== $siteurl || $site_url !== $home ) {
	llummio_setup_fail( "URL verification failed. siteurl={$siteurl}; home={$home}" );
}

if ( $config['demo_page_title'] !== $title ) {
	llummio_setup_fail( "Demo Page verification failed. Expected {$config['demo_page_title']}, found {$title}." );
}

echo "\nSetup complete.\n";
echo "Site URL: {$site_url}\n";
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

		if ( str_starts_with( $arg, '--wp-command=' ) ) {
			$options['wp-command'] = substr( $arg, 13 );
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
 * @param string             $wp_command WP-CLI command or wrapper.
 * @param array<int, string> $args WP-CLI arguments.
 * @param bool               $fail_on_error Whether to exit on failure.
 * @param bool               $show_output Whether to print command output.
 * @return array{status:int, output:string, error:string}
 */
function llummio_setup_wp( string $wp_command, array $args, bool $fail_on_error = true, bool $show_output = true ): array {
	return llummio_setup_run( array_merge( llummio_setup_split_command( $wp_command ), $args ), $fail_on_error, $show_output );
}

/**
 * @param array<int, string> $command Command parts.
 * @param bool               $fail_on_error Whether to exit on failure.
 * @param bool               $show_output Whether to print command output.
 * @return array{status:int, output:string, error:string}
 */
function llummio_setup_run( array $command, bool $fail_on_error = true, bool $show_output = true ): array {
	$command_string = llummio_setup_build_command( $command );

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

	if ( $show_output && '' !== trim( $output ) ) {
		echo trim( $output ) . "\n";
	}

	return array(
		'status' => $status,
		'output' => $output,
		'error'  => $error,
	);
}

/**
 * Try to infer the MySQL host from common local development runtime files/env vars.
 *
 * @param string $root Site root.
 * @param string $wp_command WP-CLI command or wrapper.
 * @return string|null
 */
function llummio_setup_detect_tool_db_host( string $root, string $wp_command ): ?string {
	$current_db_host = llummio_setup_wp( $wp_command, array( 'config', 'get', 'DB_HOST' ), false, false );
	$current_db_host = 0 === $current_db_host['status'] ? trim( $current_db_host['output'] ) : '';

	if ( '' !== $current_db_host && false !== strpos( $current_db_host, ':' ) ) {
		return null;
	}

	$port = llummio_setup_detect_tool_db_port( $root );

	if ( null === $port ) {
		return null;
	}

	$host = '' !== $current_db_host ? $current_db_host : 'localhost';

	if ( '127.0.0.1' !== $host && 'localhost' !== $host ) {
		return null;
	}

	return $host . ':' . $port;
}

/**
 * @param string $root Site root.
 * @return string|null
 */
function llummio_setup_detect_tool_db_port( string $root ): ?string {
	$env_port = getenv( 'LOCAL_DB_PORT' ) ?: getenv( 'MYSQL_TCP_PORT' );

	if ( llummio_setup_is_port( $env_port ) ) {
		return (string) $env_port;
	}

	$site_config_port = llummio_setup_detect_port_from_local_site_json( $root );

	if ( null !== $site_config_port ) {
		return $site_config_port;
	}

	$php_ini = php_ini_loaded_file();

	if ( false !== $php_ini && '' !== $php_ini ) {
		$conf_dir = dirname( dirname( $php_ini ) );
		$port     = llummio_setup_detect_port_from_mysql_config( $conf_dir . DIRECTORY_SEPARATOR . 'mysql' . DIRECTORY_SEPARATOR . 'my.cnf' );

		if ( null !== $port ) {
			return $port;
		}
	}

	return null;
}

/**
 * @param string $root Site root.
 * @return string|null
 */
function llummio_setup_detect_port_from_local_site_json( string $root ): ?string {
	$path = $root . DIRECTORY_SEPARATOR . 'local-site.json';

	if ( ! is_file( $path ) ) {
		return null;
	}

	$contents = file_get_contents( $path );

	if ( false === $contents ) {
		return null;
	}

	$data = json_decode( $contents, true );

	if ( ! is_array( $data ) ) {
		return null;
	}

	return llummio_setup_find_port_in_array( $data );
}

/**
 * @param mixed $value Value.
 * @return string|null
 */
function llummio_setup_find_port_in_array( $value ): ?string {
	if ( ! is_array( $value ) ) {
		return null;
	}

	foreach ( $value as $key => $item ) {
		if ( is_string( $key ) && 'port' === strtolower( $key ) && llummio_setup_is_port( $item ) ) {
			return (string) $item;
		}

		$nested = llummio_setup_find_port_in_array( $item );

		if ( null !== $nested ) {
			return $nested;
		}
	}

	return null;
}

/**
 * @param string $path MySQL config path.
 * @return string|null
 */
function llummio_setup_detect_port_from_mysql_config( string $path ): ?string {
	if ( ! is_file( $path ) ) {
		return null;
	}

	$contents = file_get_contents( $path );

	if ( false === $contents ) {
		return null;
	}

	if ( preg_match( '/^\s*port\s*=\s*([0-9]{2,5})\s*$/m', $contents, $matches ) ) {
		return llummio_setup_is_port( $matches[1] ) ? $matches[1] : null;
	}

	return null;
}

/**
 * @param mixed $port Port.
 * @return bool
 */
function llummio_setup_is_port( $port ): bool {
	if ( ! is_scalar( $port ) ) {
		return false;
	}

	$port = (string) $port;

	return (bool) preg_match( '/^[0-9]{2,5}$/', $port ) && (int) $port > 0 && (int) $port <= 65535;
}

/**
 * @param string $command Command string.
 * @return array<int, string>
 */
function llummio_setup_split_command( string $command ): array {
	$command = trim( $command );

	if ( '' === $command ) {
		llummio_setup_fail( 'The WP-CLI command cannot be empty.' );
	}

	preg_match_all( '/"([^"]*)"|\'([^\']*)\'|(\S+)/', $command, $matches, PREG_SET_ORDER );

	$parts = array();

	foreach ( $matches as $match ) {
		if ( isset( $match[1] ) && '' !== $match[1] ) {
			$parts[] = $match[1];
			continue;
		}

		if ( isset( $match[2] ) && '' !== $match[2] ) {
			$parts[] = $match[2];
			continue;
		}

		$parts[] = $match[3];
	}

	if ( empty( $parts ) ) {
		llummio_setup_fail( 'The WP-CLI command cannot be empty.' );
	}

	return $parts;
}

/**
 * @param array<int, string> $command Command parts.
 * @return string
 */
function llummio_setup_build_command( array $command ): string {
	$inner_command = implode( ' ', array_map( 'llummio_setup_shell_arg', $command ) );

	if ( 'Windows' === PHP_OS_FAMILY ) {
		return 'cmd.exe /d /s /c ' . escapeshellarg( $inner_command );
	}

	return $inner_command;
}

/**
 * @param string $arg Command argument.
 * @return string
 */
function llummio_setup_shell_arg( string $arg ): string {
	if ( preg_match( '/^[A-Za-z0-9_@%+=:,.\/\\\\-]+$/', $arg ) ) {
		return $arg;
	}

	return escapeshellarg( $arg );
}

/**
 * @param string $message Error message.
 * @return never
 */
function llummio_setup_fail( string $message ): void {
	fwrite( STDERR, $message . "\n" );
	exit( 1 );
}
