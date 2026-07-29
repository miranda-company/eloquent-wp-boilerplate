<?php
/**
 * Update the committed starter database with the current blueprint Demo Page.
 *
 * Run from the blueprint site's WP-CLI shell or terminal:
 * php ..\..\tools\update-starter-demo-page.php
 */

$config  = require __DIR__ . DIRECTORY_SEPARATOR . 'llummio-blueprint-config.php';
$options = llummio_parse_options( $argv );
$root    = dirname( __DIR__ );
$post_id = isset( $options['post-id'] ) ? (int) $options['post-id'] : (int) $config['demo_page_id'];
$sql     = isset( $options['sql'] ) ? $options['sql'] : $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'starter.sql';
$dry_run = isset( $options['dry-run'] );

if ( $post_id <= 0 ) {
	llummio_fail( 'Post ID must be a positive number.' );
}

if ( ! is_file( $sql ) ) {
	llummio_fail( "Could not find starter SQL file: {$sql}" );
}

$public_dir = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'public';

if ( ! is_dir( $public_dir ) ) {
	llummio_fail( "Could not find WordPress public directory: {$public_dir}" );
}

chdir( $public_dir );

$post = llummio_run_wp_json( array( 'post', 'get', (string) $post_id, '--format=json' ) );
$meta = llummio_run_wp_json( array( 'post', 'meta', 'list', (string) $post_id, '--format=json' ) );

if ( ! is_array( $post ) || empty( $post['ID'] ) ) {
	llummio_fail( "WP-CLI did not return page data for post {$post_id}." );
}

if ( 'page' !== ( $post['post_type'] ?? '' ) ) {
	llummio_fail( "Post {$post_id} is not a page. Found post type: " . ( $post['post_type'] ?? 'unknown' ) );
}

$sql_contents = file_get_contents( $sql );

if ( false === $sql_contents ) {
	llummio_fail( "Could not read starter SQL file: {$sql}" );
}

$updated_sql = llummio_replace_post_row( $sql_contents, $post );
$updated_sql = llummio_replace_post_meta_rows( $updated_sql, $post_id, $meta );

if ( $updated_sql === $sql_contents ) {
	echo "No changes needed. Starter SQL already matches page {$post_id}.\n";
	exit( 0 );
}

if ( $dry_run ) {
	echo "Dry run complete. Starter SQL would be updated for page {$post_id} ({$post['post_title']}).\n";
	exit( 0 );
}

if ( false === file_put_contents( $sql, $updated_sql ) ) {
	llummio_fail( "Could not write starter SQL file: {$sql}" );
}

echo "Updated app/sql/starter.sql with page {$post_id}: {$post['post_title']}\n";

/**
 * Parse simple CLI options.
 *
 * @param array<int, string> $argv Raw CLI args.
 * @return array<string, string|bool>
 */
function llummio_parse_options( array $argv ): array {
	$options = array();

	foreach ( array_slice( $argv, 1 ) as $arg ) {
		if ( '--dry-run' === $arg ) {
			$options['dry-run'] = true;
			continue;
		}

		if ( str_starts_with( $arg, '--post-id=' ) ) {
			$options['post-id'] = substr( $arg, 10 );
			continue;
		}

		if ( str_starts_with( $arg, '--sql=' ) ) {
			$options['sql'] = substr( $arg, 6 );
			continue;
		}

		llummio_fail( "Unknown option: {$arg}" );
	}

	return $options;
}

/**
 * Run a WP-CLI command and decode its JSON output.
 *
 * @param array<int, string> $args WP-CLI args without the wp executable.
 * @return mixed
 */
function llummio_run_wp_json( array $args ) {
	$command = llummio_build_wp_command( array_merge( array( 'wp' ), $args ) );

	$descriptor_spec = array(
		0 => array( 'pipe', 'r' ),
		1 => array( 'pipe', 'w' ),
		2 => array( 'pipe', 'w' ),
	);

	$process = proc_open( $command, $descriptor_spec, $pipes );

	if ( ! is_resource( $process ) ) {
		llummio_fail( 'Could not start WP-CLI. Run this from the blueprint site\'s WP-CLI shell or terminal.' );
	}

	fclose( $pipes[0] );
	$output = stream_get_contents( $pipes[1] );
	$error  = stream_get_contents( $pipes[2] );
	fclose( $pipes[1] );
	fclose( $pipes[2] );

	$status = proc_close( $process );

	if ( 0 !== $status ) {
		llummio_fail( "WP-CLI failed:\n" . trim( $error ) );
	}

	$decoded = json_decode( $output, true );

	if ( JSON_ERROR_NONE !== json_last_error() ) {
		llummio_fail( 'WP-CLI returned invalid JSON: ' . json_last_error_msg() );
	}

	return $decoded;
}

/**
 * @param array<int, string> $command Command parts.
 * @return string
 */
function llummio_build_wp_command( array $command ): string {
	$inner_command = implode( ' ', array_map( 'llummio_shell_arg', $command ) );

	if ( 'Windows' === PHP_OS_FAMILY ) {
		return 'cmd.exe /d /s /c ' . escapeshellarg( $inner_command );
	}

	return $inner_command;
}

/**
 * @param string $arg Command argument.
 * @return string
 */
function llummio_shell_arg( string $arg ): string {
	if ( preg_match( '/^[A-Za-z0-9_@%+=:,.\/\\\\-]+$/', $arg ) ) {
		return $arg;
	}

	return escapeshellarg( $arg );
}

/**
 * Replace the wp_posts row for the Demo Page.
 *
 * @param string               $sql Starter SQL.
 * @param array<string, mixed> $post WP post data.
 * @return string
 */
function llummio_replace_post_row( string $sql, array $post ): string {
	$post_id = (int) $post['ID'];
	$row     = 'INSERT INTO `wp_posts` VALUES (' . implode(
		',',
		array(
			llummio_sql_value( $post['ID'], true ),
			llummio_sql_value( $post['post_author'], true ),
			llummio_sql_value( $post['post_date'] ),
			llummio_sql_value( $post['post_date_gmt'] ),
			llummio_sql_value( $post['post_content'] ),
			llummio_sql_value( $post['post_title'] ),
			llummio_sql_value( $post['post_excerpt'] ),
			llummio_sql_value( $post['post_status'] ),
			llummio_sql_value( $post['comment_status'] ),
			llummio_sql_value( $post['ping_status'] ),
			llummio_sql_value( $post['post_password'] ),
			llummio_sql_value( $post['post_name'] ),
			llummio_sql_value( $post['to_ping'] ),
			llummio_sql_value( $post['pinged'] ),
			llummio_sql_value( $post['post_modified'] ),
			llummio_sql_value( $post['post_modified_gmt'] ),
			llummio_sql_value( $post['post_content_filtered'] ),
			llummio_sql_value( $post['post_parent'], true ),
			llummio_sql_value( $post['guid'] ),
			llummio_sql_value( $post['menu_order'], true ),
			llummio_sql_value( $post['post_type'] ),
			llummio_sql_value( $post['post_mime_type'] ),
			llummio_sql_value( $post['comment_count'], true ),
		)
	) . ');';

	$pattern = '/^INSERT INTO `wp_posts` VALUES \(' . $post_id . ',.*\);$/m';
	$count   = 0;
	$updated = preg_replace( $pattern, $row, $sql, 1, $count );

	if ( 1 !== $count || null === $updated ) {
		llummio_fail( "Could not find the wp_posts row for page {$post_id} in starter.sql." );
	}

	return $updated;
}

/**
 * Replace safe wp_postmeta rows for the Demo Page.
 *
 * @param string                    $sql Starter SQL.
 * @param int                       $post_id Page ID.
 * @param array<int, array<string, mixed>> $meta WP post meta rows.
 * @return string
 */
function llummio_replace_post_meta_rows( string $sql, int $post_id, array $meta ): string {
	$existing_ids_by_key = llummio_get_existing_meta_ids_by_key( $sql, $post_id );
	$next_meta_id        = llummio_get_next_meta_id( $sql );
	$rows                = array();

	foreach ( $meta as $meta_row ) {
		$key = (string) ( $meta_row['meta_key'] ?? '' );

		if ( '' === $key || llummio_should_skip_meta_key( $key ) ) {
			continue;
		}

		if ( ! empty( $existing_ids_by_key[ $key ] ) ) {
			$meta_id = array_shift( $existing_ids_by_key[ $key ] );
		} else {
			$meta_id = $next_meta_id++;
		}

		$rows[] = 'INSERT INTO `wp_postmeta` VALUES (' . implode(
			',',
			array(
				llummio_sql_value( $meta_id, true ),
				llummio_sql_value( $post_id, true ),
				llummio_sql_value( $key ),
				llummio_sql_value( (string) ( $meta_row['meta_value'] ?? '' ) ),
			)
		) . ');';
	}

	$without_old_rows = preg_replace(
		'/^INSERT INTO `wp_postmeta` VALUES \([0-9]+,' . $post_id . ',.*\);$\R?/m',
		'',
		$sql
	);

	if ( null === $without_old_rows ) {
		llummio_fail( 'Could not remove old wp_postmeta rows from starter.sql.' );
	}

	$insert = '';

	if ( ! empty( $rows ) ) {
		$insert = implode( "\n", $rows ) . "\n";
	}

	$updated = preg_replace_callback(
		'/(\/\*!40000 ALTER TABLE `wp_postmeta` DISABLE KEYS \*\/;\R)/',
		static function ( array $matches ) use ( $insert ): string {
			return $matches[1] . $insert;
		},
		$without_old_rows,
		1,
		$count
	);

	if ( 1 !== $count || null === $updated ) {
		llummio_fail( 'Could not find the wp_postmeta insert location in starter.sql.' );
	}

	return llummio_update_postmeta_auto_increment( $updated );
}

/**
 * @param string $key Meta key.
 * @return bool
 */
function llummio_should_skip_meta_key( string $key ): bool {
	$skipped = array(
		'_edit_last' => true,
		'_edit_lock' => true,
	);

	return isset( $skipped[ $key ] );
}

/**
 * @param string $sql Starter SQL.
 * @param int    $post_id Page ID.
 * @return array<string, array<int, int>>
 */
function llummio_get_existing_meta_ids_by_key( string $sql, int $post_id ): array {
	preg_match_all(
		'/^INSERT INTO `wp_postmeta` VALUES \(([0-9]+),' . $post_id . ',\'((?:\\\\.|[^\'])*)\',/m',
		$sql,
		$matches,
		PREG_SET_ORDER
	);

	$ids = array();

	foreach ( $matches as $match ) {
		$ids[ $match[2] ][] = (int) $match[1];
	}

	return $ids;
}

/**
 * @param string $sql Starter SQL.
 * @return int
 */
function llummio_get_next_meta_id( string $sql ): int {
	preg_match_all( '/^INSERT INTO `wp_postmeta` VALUES \(([0-9]+),/m', $sql, $matches );
	$ids = array_map( 'intval', $matches[1] ?? array() );

	return empty( $ids ) ? 1 : max( $ids ) + 1;
}

/**
 * @param string $sql Starter SQL.
 * @return string
 */
function llummio_update_postmeta_auto_increment( string $sql ): string {
	$next_id = llummio_get_next_meta_id( $sql );

	return preg_replace(
		'/(CREATE TABLE `wp_postmeta` \([\\s\\S]*?\) ENGINE=InnoDB AUTO_INCREMENT=)([0-9]+)/',
		'${1}' . $next_id,
		$sql,
		1
	) ?? $sql;
}

/**
 * @param mixed $value Value.
 * @param bool  $numeric Whether to render as a number.
 * @return string
 */
function llummio_sql_value( $value, bool $numeric = false ): string {
	if ( $numeric ) {
		return (string) (int) $value;
	}

	return "'" . llummio_sql_escape( (string) $value ) . "'";
}

/**
 * @param string $value Raw value.
 * @return string
 */
function llummio_sql_escape( string $value ): string {
	return str_replace(
		array( '\\', "\0", "\n", "\r", "\t", "\x1a", "'" ),
		array( '\\\\', '\\0', '\\n', '\\r', '\\t', '\\Z', "\\'" ),
		$value
	);
}

/**
 * @param string $message Error message.
 * @return never
 */
function llummio_fail( string $message ): void {
	fwrite( STDERR, $message . "\n" );
	exit( 1 );
}
