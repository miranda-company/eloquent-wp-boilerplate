<?php
/**
 * Bounded form submission storage.
 *
 * The submissions table is created from authenticated save/admin paths only
 * when a form has submission storage enabled. Sites that never enable stored
 * submissions get no table, no schema-version option, and no scheduled cleanup.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Submission store backed by a small custom table.
 */
class GenerateBlocks_Pro_Form_Submissions extends GenerateBlocks_Pro_Singleton {
	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Internal custom table names cannot be parameterized.

	const DB_VERSION             = '1';
	const DB_VERSION_OPTION      = 'generateblocks_pro_form_submissions_db_version';
	const INSTALL_LOCK_TRANSIENT = 'generateblocks_pro_form_submissions_installing';
	const STORAGE_ERROR_TRANSIENT = 'generateblocks_pro_form_submissions_storage_error';
	const CLEANUP_HOOK           = 'generateblocks_pro_form_submissions_cleanup';
	const MAX_RECORDS_PER_FORM   = 500;
	const DEFAULT_PAGE_SIZE      = 25;
	const MAX_PAGE_SIZE          = 100;
	const RETENTION_DAYS         = 180;
	const MAX_RECORD_BYTES       = 51200;
	const PRIVACY_PAGE_SIZE      = 50;
	const PRIVACY_ERASE_CURSOR_TRANSIENT = 'generateblocks_pro_form_submissions_erase_cursor_';
	const PRIVACY_EXPORT_CURSOR_TRANSIENT = 'generateblocks_pro_form_submissions_export_cursor_';

	/**
	 * Per-request table-existence cache.
	 *
	 * @var bool|null
	 */
	private static $table_exists = null;

	/**
	 * Per-request count cache keyed by form ID.
	 *
	 * @var array<int, array<string, int>>|null
	 */
	private static $count_cache = null;

	/**
	 * Initialize hooks.
	 */
	public function init() {
		add_filter( 'wp_privacy_personal_data_exporters', [ $this, 'register_data_exporter' ] );
		add_filter( 'wp_privacy_personal_data_erasers', [ $this, 'register_data_eraser' ] );
		add_action( 'admin_init', [ $this, 'add_privacy_policy_content' ] );
		add_action( self::CLEANUP_HOOK, [ __CLASS__, 'prune_all' ] );
	}

	/**
	 * Get the table name for this site.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;

		$prefix = isset( $wpdb->prefix ) ? $wpdb->prefix : 'wp_';

		return $prefix . 'gbp_form_submissions';
	}

	/**
	 * Whether the storage table exists.
	 *
	 * @return bool
	 */
	public static function table_exists() {
		if ( null !== self::$table_exists ) {
			return self::$table_exists;
		}

		global $wpdb;

		$table = self::table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		self::$table_exists = $table === $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table
			)
		);

		return self::$table_exists;
	}

	/**
	 * Create or update the submissions table.
	 *
	 * @param int $form_id Form post ID for scoped storage warnings.
	 * @return bool Whether the table is ready after the attempt.
	 */
	public static function maybe_install( $form_id = 0 ) {
		if ( self::table_exists() && self::DB_VERSION === get_option( self::DB_VERSION_OPTION ) ) {
			self::clear_storage_error( $form_id );
			self::maybe_schedule_cleanup();

			return true;
		}

		if ( get_transient( self::INSTALL_LOCK_TRANSIENT ) ) {
			self::$table_exists = null;

			return self::table_exists();
		}

		set_transient( self::INSTALL_LOCK_TRANSIENT, 1, MINUTE_IN_SECONDS );

		global $wpdb;

		$table           = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			form_id bigint(20) unsigned NOT NULL,
			source_post_id bigint(20) unsigned NOT NULL DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT 'received',
			created_at_gmt datetime NOT NULL,
			updated_at_gmt datetime DEFAULT NULL,
			payload longtext NOT NULL,
			payload_bytes int(10) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY form_created (form_id, created_at_gmt),
			KEY status_created (status, created_at_gmt)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		delete_transient( self::INSTALL_LOCK_TRANSIENT );
		self::$table_exists = null;

		if ( ! self::table_exists() ) {
			self::record_storage_error( __( 'Submission storage table could not be created.', 'generateblocks-pro' ), $form_id );

			return false;
		}

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION, false );
		self::clear_storage_error( $form_id );
		self::maybe_schedule_cleanup();

		return true;
	}

	/**
	 * Store a newly received, sanitized submission.
	 *
	 * @param int   $form_id        Form post ID.
	 * @param int   $source_post_id Host post ID.
	 * @param array $sanitized      Sanitized field data.
	 * @return int|false Submission ID or false.
	 */
	public static function insert_received( $form_id, $source_post_id, $sanitized ) {
		$form_id = absint( $form_id );

		if ( ! $form_id ) {
			return false;
		}

		if ( ! self::table_exists() ) {
			self::record_storage_error(
				__( 'Submission storage table is not available.', 'generateblocks-pro' ),
				$form_id
			);

			return false;
		}

		$submitted_at_gmt = current_time( 'mysql', true );
		$payload          = [
			'fields'           => is_array( $sanitized ) ? $sanitized : [],
			'errors'           => [],
			'submitted_at_gmt' => $submitted_at_gmt,
		];
		$encoded          = wp_json_encode( $payload );

		if ( ! is_string( $encoded ) ) {
			self::record_storage_error( __( 'Submission storage payload could not be encoded.', 'generateblocks-pro' ), $form_id );

			return false;
		}

		$payload_bytes = strlen( $encoded );

		if ( $payload_bytes > self::MAX_RECORD_BYTES ) {
			self::record_storage_error(
				sprintf(
					/* translators: %d: form ID. */
					__( 'Submission storage payload exceeded the per-record limit for form %d.', 'generateblocks-pro' ),
					$form_id
				),
				$form_id
			);

			return false;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $wpdb->insert(
			self::table_name(),
			[
				'form_id'        => $form_id,
				'source_post_id' => absint( $source_post_id ),
				'status'         => 'received',
				'created_at_gmt' => $submitted_at_gmt,
				'updated_at_gmt' => null,
				'payload'        => $encoded,
				'payload_bytes'  => $payload_bytes,
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%d' ]
		);

		if ( false === $inserted ) {
			self::record_storage_error( __( 'Submission could not be stored.', 'generateblocks-pro' ), $form_id );

			return false;
		}

		self::$count_cache = null;
		self::clear_storage_error( $form_id );

		$submission_id = (int) $wpdb->insert_id;
		self::prune_for_form( $form_id );

		return $submission_id;
	}

	/**
	 * Update a stored submission status.
	 *
	 * @param int        $submission_id Submission ID.
	 * @param string     $status        New status.
	 * @param array|null $errors        Optional action errors.
	 * @param int        $form_id       Parent form ID, for scoped admin warnings.
	 * @return bool
	 */
	public static function update_status( $submission_id, $status, $errors = null, $form_id = 0 ) {
		$submission_id = absint( $submission_id );
		$status        = sanitize_key( (string) $status );
		$form_id       = absint( $form_id );

		if ( ! $submission_id || ! in_array( $status, [ 'received', 'processed', 'failed' ], true ) || ! self::table_exists() ) {
			return false;
		}

		$data    = [
			'status'         => $status,
			'updated_at_gmt' => current_time( 'mysql', true ),
		];
		$formats = [ '%s', '%s' ];

		if ( null !== $errors ) {
			$record = self::get_raw_record( $submission_id );

			if ( $record ) {
				$form_id           = $form_id ? $form_id : (int) $record->form_id;
				$payload           = self::decode_payload( $record->payload );
				$payload['errors'] = self::sanitize_errors( $errors );
				$encoded           = wp_json_encode( $payload );

				if ( is_string( $encoded ) && strlen( $encoded ) <= self::MAX_RECORD_BYTES ) {
					$data['payload']       = $encoded;
					$data['payload_bytes'] = strlen( $encoded );
					$formats[]             = '%s';
					$formats[]             = '%d';
				}
			}
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$updated = $wpdb->update(
			self::table_name(),
			$data,
			[ 'id' => $submission_id ],
			$formats,
			[ '%d' ]
		);

		if ( false === $updated ) {
			self::record_storage_error( __( 'Stored submission status could not be updated.', 'generateblocks-pro' ), $form_id );

			return false;
		}

		self::$count_cache = null;
		self::clear_storage_error( $form_id );

		return true;
	}

	/**
	 * Fetch submissions for a form, newest first.
	 *
	 * @param int $form_id  Form post ID.
	 * @param int $page     Page number.
	 * @param int $per_page Records per page.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_for_form( $form_id, $page = 1, $per_page = self::DEFAULT_PAGE_SIZE ) {
		$form_id  = absint( $form_id );
		$page     = max( 1, absint( $page ) );
		$per_page = max( 1, min( self::MAX_PAGE_SIZE, absint( $per_page ) ) );
		$offset   = ( $page - 1 ) * $per_page;

		if ( ! $form_id || ! self::table_exists() ) {
			return [];
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, form_id, source_post_id, status, created_at_gmt, payload
				FROM ' . self::table_name() . '
				WHERE form_id = %d
				ORDER BY created_at_gmt DESC, id DESC
				LIMIT %d OFFSET %d',
				$form_id,
				$per_page,
				$offset
			)
		);

		$out = [];

		foreach ( (array) $rows as $row ) {
			$payload = self::decode_payload( $row->payload ?? '' );

			$out[] = [
				'id'             => (int) $row->id,
				'formId'         => (int) $row->form_id,
				'sourcePostId'   => (int) $row->source_post_id,
				'status'         => self::normalize_status( $row->status ?? '' ),
				'fields'         => is_array( $payload['fields'] ?? null ) ? $payload['fields'] : [],
				'errors'         => is_array( $payload['errors'] ?? null ) ? $payload['errors'] : [],
				'submittedAtGmt' => (string) ( $payload['submitted_at_gmt'] ?? $row->created_at_gmt ?? '' ),
			];
		}

		return $out;
	}

	/**
	 * Count stored submissions for one form.
	 *
	 * @param int $form_id Form post ID.
	 * @return int
	 */
	public static function total_for_form( $form_id ) {
		$form_id = absint( $form_id );

		if ( ! $form_id || ! self::table_exists() ) {
			return 0;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . self::table_name() . ' WHERE form_id = %d',
				$form_id
			)
		);
	}

	/**
	 * Delete one submission scoped to its form.
	 *
	 * @param int $form_id       Form post ID.
	 * @param int $submission_id Submission ID.
	 * @return bool|null True on delete, null if not found, false on DB error.
	 */
	public static function delete_record( $form_id, $submission_id ) {
		$form_id       = absint( $form_id );
		$submission_id = absint( $submission_id );

		if ( ! $form_id || ! $submission_id || ! self::table_exists() ) {
			return null;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM ' . self::table_name() . ' WHERE id = %d AND form_id = %d',
				$submission_id,
				$form_id
			)
		);

		if ( ! $exists ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->delete(
			self::table_name(),
			[
				'id'      => $submission_id,
				'form_id' => $form_id,
			],
			[ '%d', '%d' ]
		);

		if ( false === $result ) {
			return false;
		}

		self::$count_cache = null;

		return true;
	}

	/**
	 * Clear all submissions for a form.
	 *
	 * @param int $form_id Form post ID.
	 * @return int|false Deleted count or false on DB error.
	 */
	public static function clear_for_form( $form_id ) {
		$form_id = absint( $form_id );

		if ( ! $form_id || ! self::table_exists() ) {
			return 0;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->delete(
			self::table_name(),
			[ 'form_id' => $form_id ],
			[ '%d' ]
		);

		if ( false === $result ) {
			return false;
		}

		self::$count_cache = null;

		return (int) $result;
	}

	/**
	 * Delete form submissions if the table exists.
	 *
	 * @param int $form_id Form post ID.
	 */
	public static function delete_for_form( $form_id ) {
		self::clear_for_form( $form_id );
	}

	/**
	 * Count submissions by form for dashboard metadata.
	 *
	 * @return array<int, array{submission_count:int, failed_submission_count:int}>
	 */
	public static function count_by_form() {
		if ( null !== self::$count_cache ) {
			return self::$count_cache;
		}

		self::$count_cache = [];

		if ( ! self::table_exists() ) {
			return self::$count_cache;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			"SELECT form_id, COUNT(*) AS total, SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed
			FROM " . self::table_name() . '
			GROUP BY form_id',
			ARRAY_A
		);

		foreach ( (array) $rows as $row ) {
			$form_id = (int) $row['form_id'];

			self::$count_cache[ $form_id ] = [
				'submission_count'        => (int) $row['total'],
				'failed_submission_count' => (int) $row['failed'],
			];
		}

		return self::$count_cache;
	}

	/**
	 * Counts for a single form.
	 *
	 * @param int $form_id Form post ID.
	 * @return array{submission_count:int, failed_submission_count:int}
	 */
	public static function counts_for_form( $form_id ) {
		$counts = self::count_by_form();

		return $counts[ absint( $form_id ) ] ?? [
			'submission_count'        => 0,
			'failed_submission_count' => 0,
		];
	}

	/**
	 * Get filtered storage limits for one form.
	 *
	 * @param int $form_id Form post ID.
	 * @return array{max_records:int, retention_days:int}
	 */
	public static function storage_limits_for_form( $form_id ) {
		$form_id = absint( $form_id );

		return [
			'max_records'    => self::max_records_per_form( $form_id ),
			'retention_days' => self::retention_days( $form_id ),
		];
	}

	/**
	 * Prune all forms by retention rules.
	 */
	public static function prune_all() {
		if ( ! self::table_exists() ) {
			return;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$form_ids = $wpdb->get_col( 'SELECT DISTINCT form_id FROM ' . self::table_name() );

		foreach ( (array) $form_ids as $form_id ) {
			self::prune_for_form( (int) $form_id );
		}
	}

	/**
	 * Register the GDPR personal-data exporter.
	 *
	 * @param array $exporters Existing exporters.
	 * @return array
	 */
	public function register_data_exporter( $exporters ) {
		$exporters['generateblocks-pro-form-submissions'] = [
			'exporter_friendly_name' => __( 'GenerateBlocks Form Submissions', 'generateblocks-pro' ),
			'callback'               => [ $this, 'export_personal_data' ],
		];

		return $exporters;
	}

	/**
	 * Register the GDPR personal-data eraser.
	 *
	 * @param array $erasers Existing erasers.
	 * @return array
	 */
	public function register_data_eraser( $erasers ) {
		$erasers['generateblocks-pro-form-submissions'] = [
			'eraser_friendly_name' => __( 'GenerateBlocks Form Submissions', 'generateblocks-pro' ),
			'callback'             => [ $this, 'erase_personal_data' ],
		];

		return $erasers;
	}

	/**
	 * Export personal data for an email address.
	 *
	 * @param string $email_address Email to export.
	 * @param int    $page          Page number.
	 * @return array
	 */
	public function export_personal_data( $email_address, $page = 1 ) {
		$email = strtolower( trim( (string) $email_address ) );
		$out   = [];
		$page  = max( 1, absint( $page ) );

		if ( '' === $email || ! self::table_exists() ) {
			return [
				'data' => [],
				'done' => true,
			];
		}

		$cursor_key = self::privacy_export_cursor_key( $email );

		if ( 1 === $page ) {
			delete_transient( $cursor_key );
		}

		$after_id = absint( get_transient( $cursor_key ) );
		$records  = self::get_privacy_records_after_id( $after_id, self::PRIVACY_PAGE_SIZE );
		$last_id  = $after_id;

		foreach ( $records as $record ) {
			$last_id = max( $last_id, (int) $record['id'] );

			if ( ! self::record_matches_email( $record, $email ) ) {
				continue;
			}

			$data = [];

			foreach ( (array) ( $record['fields'] ?? [] ) as $name => $field ) {
				if ( ! is_array( $field ) ) {
					continue;
				}

				$data[] = [
					'name'  => (string) ( $field['label'] ?? $name ),
					'value' => (string) ( $field['value'] ?? '' ),
				];
			}

			$data[] = [
				'name'  => __( 'Submitted', 'generateblocks-pro' ),
				'value' => (string) ( $record['submitted_at_gmt'] ?? '' ),
			];

			$out[] = [
				'group_id'          => 'generateblocks-form-submissions',
				'group_label'       => __( 'Form Submissions', 'generateblocks-pro' ),
				'group_description' => __( 'Stored GenerateBlocks form submissions.', 'generateblocks-pro' ),
				'item_id'           => 'submission-' . (int) $record['id'],
				'data'              => $data,
			];
		}

		if ( count( $records ) < self::PRIVACY_PAGE_SIZE ) {
			delete_transient( $cursor_key );
			$done = true;
		} else {
			set_transient( $cursor_key, $last_id, HOUR_IN_SECONDS );
			$done = false;
		}

		return [
			'data' => $out,
			'done' => $done,
		];
	}

	/**
	 * Erase personal data for an email address.
	 *
	 * @param string $email_address Email to erase.
	 * @param int    $page          Page number.
	 * @return array
	 */
	public function erase_personal_data( $email_address, $page = 1 ) {
		$email = strtolower( trim( (string) $email_address ) );
		$page  = max( 1, absint( $page ) );

		$result = [
			'items_removed'  => 0,
			'items_retained' => false,
			'messages'       => [],
			'done'           => true,
		];

		if ( '' === $email || ! self::table_exists() ) {
			return $result;
		}

		$cursor_key = self::privacy_erase_cursor_key( $email );

		if ( 1 === $page ) {
			delete_transient( $cursor_key );
		}

		$after_id = absint( get_transient( $cursor_key ) );
		$records  = self::get_privacy_records_after_id( $after_id, self::PRIVACY_PAGE_SIZE );
		$last_id  = $after_id;

		foreach ( $records as $record ) {
			$last_id = max( $last_id, (int) $record['id'] );

			if ( ! self::record_matches_email( $record, $email ) ) {
				continue;
			}

			if ( true === self::delete_record( (int) $record['form_id'], (int) $record['id'] ) ) {
				$result['items_removed']++;
			} else {
				$result['items_retained'] = true;
			}
		}

		if ( count( $records ) < self::PRIVACY_PAGE_SIZE ) {
			delete_transient( $cursor_key );
			$result['done'] = true;
		} else {
			set_transient( $cursor_key, $last_id, HOUR_IN_SECONDS );
			$result['done'] = false;
		}

		return $result;
	}

	/**
	 * Privacy policy content suggestion.
	 */
	public function add_privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = sprintf(
			'<h2>%s</h2><p>%s</p><p>%s</p>',
			__( 'Forms', 'generateblocks-pro' ),
			__( 'When you submit a form on this site, the data is sent by email and may also be sent to third-party services when configured. These services have their own privacy policies governing how your data is handled.', 'generateblocks-pro' ),
			__( 'Some forms may store recent submissions so the site administrator can recover inquiries if delivery fails or an email is missed. Stored submissions are retained for a limited time or until the administrator deletes them.', 'generateblocks-pro' )
		);

		wp_add_privacy_policy_content(
			'GenerateBlocks Pro',
			wp_kses_post( $content )
		);
	}

	/**
	 * Get the latest storage warning.
	 *
	 * @param int $form_id Form post ID.
	 * @return string
	 */
	public static function get_storage_error( $form_id = 0 ) {
		$form_id = absint( $form_id );

		if ( ! $form_id ) {
			return '';
		}

		$error = get_transient( self::storage_error_transient_key( $form_id ) );

		return is_string( $error ) ? $error : '';
	}

	/**
	 * Store a temporary storage warning for admins.
	 *
	 * @param string $message Error message.
	 * @param int    $form_id Form post ID.
	 */
	private static function record_storage_error( $message, $form_id = 0 ) {
		$message = sanitize_text_field( (string) $message );
		$form_id = absint( $form_id );

		if ( '' === $message ) {
			return;
		}

		if ( $form_id ) {
			set_transient( self::storage_error_transient_key( $form_id ), $message, DAY_IN_SECONDS );
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'GenerateBlocks Forms: ' . $message );
	}

	/**
	 * Clear a previous storage warning after storage proves healthy again.
	 *
	 * @param int $form_id Form post ID.
	 */
	private static function clear_storage_error( $form_id = 0 ) {
		$form_id = absint( $form_id );

		if ( ! $form_id ) {
			return;
		}

		delete_transient( self::storage_error_transient_key( $form_id ) );
	}

	/**
	 * Build the per-form transient key for storage warnings.
	 *
	 * @param int $form_id Form post ID.
	 * @return string
	 */
	private static function storage_error_transient_key( $form_id ) {
		return self::STORAGE_ERROR_TRANSIENT . '_' . absint( $form_id );
	}

	/**
	 * Build the transient key for an in-progress privacy eraser scan.
	 *
	 * @param string $email Lowercase email address.
	 * @return string
	 */
	private static function privacy_erase_cursor_key( $email ) {
		return self::PRIVACY_ERASE_CURSOR_TRANSIENT . md5( $email );
	}

	/**
	 * Build the transient key for an in-progress privacy export scan.
	 *
	 * @param string $email Lowercase email address.
	 * @return string
	 */
	private static function privacy_export_cursor_key( $email ) {
		return self::PRIVACY_EXPORT_CURSOR_TRANSIENT . md5( $email );
	}

	/**
	 * Schedule retention cleanup after the table exists.
	 */
	private static function maybe_schedule_cleanup() {
		if ( ! function_exists( 'wp_next_scheduled' ) || ! function_exists( 'wp_schedule_event' ) ) {
			return;
		}

		if ( ! wp_next_scheduled( self::CLEANUP_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CLEANUP_HOOK );
		}
	}

	/**
	 * Get the maximum stored submissions retained for one form.
	 *
	 * @param int $form_id Form post ID.
	 * @return int
	 */
	private static function max_records_per_form( $form_id ) {
		/**
		 * Filter the maximum number of stored submissions retained for one form.
		 *
		 * @since 2.6.0
		 *
		 * @param int $max_records Maximum stored submissions per form.
		 * @param int $form_id     Form post ID.
		 */
		$max_records = apply_filters(
			'generateblocks_form_submissions_max_records_per_form',
			self::MAX_RECORDS_PER_FORM,
			absint( $form_id )
		);

		return max( 1, absint( $max_records ) );
	}

	/**
	 * Get the maximum age for stored submissions.
	 *
	 * @param int $form_id Form post ID.
	 * @return int
	 */
	private static function retention_days( $form_id ) {
		/**
		 * Filter how many days stored submissions are retained.
		 *
		 * @since 2.6.0
		 *
		 * @param int $retention_days Retention period in days.
		 * @param int $form_id        Form post ID.
		 */
		$retention_days = apply_filters(
			'generateblocks_form_submissions_retention_days',
			self::RETENTION_DAYS,
			absint( $form_id )
		);

		return max( 1, absint( $retention_days ) );
	}

	/**
	 * Prune records for a form by age and count.
	 *
	 * @param int $form_id Form post ID.
	 */
	private static function prune_for_form( $form_id ) {
		$form_id = absint( $form_id );

		if ( ! $form_id || ! self::table_exists() ) {
			return;
		}

		global $wpdb;

		$retention_days = self::retention_days( $form_id );
		$max_records    = self::max_records_per_form( $form_id );
		$cutoff         = gmdate( 'Y-m-d H:i:s', time() - ( $retention_days * DAY_IN_SECONDS ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM ' . self::table_name() . ' WHERE form_id = %d AND created_at_gmt < %s',
				$form_id,
				$cutoff
			)
		);

		// Find the first row beyond the retained window so large backlogs do
		// not require loading every submission ID into PHP.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$delete_from = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT id, created_at_gmt FROM ' . self::table_name() . ' WHERE form_id = %d ORDER BY created_at_gmt DESC, id DESC LIMIT 1 OFFSET %d',
				$form_id,
				$max_records
			)
		);

		if ( ! $delete_from || empty( $delete_from->id ) || empty( $delete_from->created_at_gmt ) ) {
			return;
		}

		$delete_from_id   = absint( $delete_from->id );
		$delete_from_date = (string) $delete_from->created_at_gmt;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM ' . self::table_name() . ' WHERE form_id = %d AND (created_at_gmt < %s OR (created_at_gmt = %s AND id <= %d))',
				$form_id,
				$delete_from_date,
				$delete_from_date,
				$delete_from_id
			)
		);
	}

	/**
	 * Fetch one raw row.
	 *
	 * @param int $submission_id Submission ID.
	 * @return object|null
	 */
	private static function get_raw_record( $submission_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT id, form_id, payload FROM ' . self::table_name() . ' WHERE id = %d',
				absint( $submission_id )
			)
		);
	}

	/**
	 * Fetch stored submissions after an ID cursor.
	 *
	 * Both privacy callbacks page through the table with this keyset cursor.
	 * For the eraser it is required for correctness: deleting matches from
	 * earlier pages would otherwise shift later records behind an OFFSET and
	 * leave them unerased. For the exporter it keeps each page cheap on large
	 * tables (no growing OFFSET scan) and immune to rows pruned mid-export.
	 *
	 * @param int $after_id Fetch records after this row ID.
	 * @param int $per_page Records per page.
	 * @return array<int, array<string, mixed>>
	 */
	private static function get_privacy_records_after_id( $after_id, $per_page ) {
		global $wpdb;

		$after_id = absint( $after_id );
		$per_page = max( 1, absint( $per_page ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, form_id, source_post_id, status, created_at_gmt, payload
				FROM ' . self::table_name() . '
				WHERE id > %d
				ORDER BY id ASC
				LIMIT %d',
				$after_id,
				$per_page
			)
		);

		return self::normalize_privacy_records( $rows );
	}

	/**
	 * Normalize raw DB rows into the privacy record shape.
	 *
	 * @param array $rows DB rows.
	 * @return array<int, array<string, mixed>>
	 */
	private static function normalize_privacy_records( $rows ) {
		$out = [];

		foreach ( (array) $rows as $row ) {
			$payload = self::decode_payload( $row->payload ?? '' );

			$out[] = [
				'id'               => (int) $row->id,
				'form_id'          => (int) $row->form_id,
				'source_post_id'   => (int) $row->source_post_id,
				'status'           => self::normalize_status( $row->status ?? '' ),
				'fields'           => is_array( $payload['fields'] ?? null ) ? $payload['fields'] : [],
				'errors'           => is_array( $payload['errors'] ?? null ) ? $payload['errors'] : [],
				'submitted_at_gmt' => (string) ( $payload['submitted_at_gmt'] ?? $row->created_at_gmt ?? '' ),
			];
		}

		return $out;
	}

	/**
	 * Decode a JSON payload.
	 *
	 * @param string $payload JSON payload.
	 * @return array
	 */
	private static function decode_payload( $payload ) {
		$decoded = json_decode( (string) $payload, true );

		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Sanitize action errors for storage.
	 *
	 * @param array $errors Raw errors.
	 * @return array
	 */
	private static function sanitize_errors( $errors ) {
		return array_values(
			array_map( 'sanitize_text_field', array_filter( (array) $errors, 'is_string' ) )
		);
	}

	/**
	 * Normalize a stored status.
	 *
	 * @param string $status Raw status.
	 * @return string
	 */
	private static function normalize_status( $status ) {
		$status = sanitize_key( (string) $status );

		return in_array( $status, [ 'received', 'processed', 'failed' ], true ) ? $status : 'received';
	}

	/**
	 * Check whether any email value on a record matches the given address.
	 *
	 * @param array  $record Record array.
	 * @param string $email  Lowercased, trimmed email.
	 * @return bool
	 */
	private static function record_matches_email( $record, $email ) {
		$fields = isset( $record['fields'] ) && is_array( $record['fields'] ) ? $record['fields'] : [];

		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) || ! isset( $field['value'] ) || ! is_string( $field['value'] ) ) {
				continue;
			}

			$value = trim( $field['value'] );

			if ( '' === $value || strtolower( $value ) !== $email ) {
				continue;
			}

			if ( 'email' === ( $field['type'] ?? '' ) || self::is_email_value( $value ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check whether a field value is an email address.
	 *
	 * @param string $value Field value.
	 * @return bool
	 */
	private static function is_email_value( $value ) {
		if ( function_exists( 'is_email' ) ) {
			return (bool) is_email( $value );
		}

		return false !== filter_var( $value, FILTER_VALIDATE_EMAIL );
	}

	// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
}
