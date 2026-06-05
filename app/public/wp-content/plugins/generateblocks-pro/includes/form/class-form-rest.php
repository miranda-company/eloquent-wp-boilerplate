<?php
/**
 * Form submission REST endpoint.
 *
 * Public endpoint for form submissions. No auth required.
 * Security: honeypot, timestamp, server-side field validation.
 * All errors return generic messages. Honeypot failures return fake success.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once GENERATEBLOCKS_PRO_DIR . 'includes/utils/trait-usage-search.php';

/**
 * Form REST endpoint class.
 */
class GenerateBlocks_Pro_Form_Rest extends GenerateBlocks_Pro_Singleton {

	use GenerateBlocks_Pro_Usage_Search;

	/**
	 * Initialize the REST routes.
	 */
	public function init() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes() {
		// Security token issuer.
		//
		// Issues a fresh signed timestamp + honeypot name for a given form.
		// Called by the frontend JS on form mount, *not* baked into the
		// server-rendered HTML, so page caches (WP Rocket, Cloudflare APO,
		// etc.) cannot freeze a single token into cached HTML.
		register_rest_route(
			'generateblocks-pro/v1',
			'/forms/security',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'issue_security_token' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'formId' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'postId' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		// Admin: per-form stored submissions — list + clear-all.
		register_rest_route(
			'generateblocks-pro/v1',
			'/admin/forms/(?P<id>\d+)/submissions',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'list_form_submissions' ],
					'permission_callback' => [ $this, 'admin_permission' ],
					'args'                => [
						'id' => [
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						],
						'page' => [
							'required'          => false,
							'type'              => 'integer',
							'default'           => 1,
							'minimum'           => 1,
							'sanitize_callback' => 'absint',
						],
						'per_page' => [
							'required'          => false,
							'type'              => 'integer',
							'default'           => GenerateBlocks_Pro_Form_Submissions::DEFAULT_PAGE_SIZE,
							'minimum'           => 1,
							'maximum'           => GenerateBlocks_Pro_Form_Submissions::MAX_PAGE_SIZE,
							'sanitize_callback' => 'absint',
						],
					],
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'clear_form_submissions' ],
					'permission_callback' => [ $this, 'admin_permission' ],
					'args'                => [
						'id' => [
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						],
					],
				],
			]
		);

		// Admin: delete one stored submission, scoped to its form.
		register_rest_route(
			'generateblocks-pro/v1',
			'/admin/forms/(?P<id>\d+)/submissions/(?P<submissionId>\d+)',
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_form_submission' ],
				'permission_callback' => [ $this, 'admin_permission' ],
				'args'                => [
					'id'     => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'submissionId' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		// Admin: duplicate a form. Post + meta copy is atomic server-side so
		// the dashboard doesn't need to reason about block content structure.
		register_rest_route(
			'generateblocks-pro/v1',
			'/admin/forms/(?P<id>\d+)/duplicate',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'duplicate_form' ],
				'permission_callback' => [ $this, 'admin_permission' ],
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		// Admin: toggle submission storage for a single form. Lets the
		// dashboard's storage-off failure view enable storage in place,
		// without round-tripping through the block editor. The meta write
		// triggers maybe_install_submissions_table_after_meta_save() on the
		// post type, so the table is created when storage is switched on.
		register_rest_route(
			'generateblocks-pro/v1',
			'/admin/forms/(?P<id>\d+)/storage',
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_form_storage' ],
				'permission_callback' => [ $this, 'admin_permission' ],
				'args'                => [
					'id'      => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'enabled' => [
						'required' => true,
						'type'     => 'boolean',
					],
				],
			]
		);

		// Admin: where is this form embedded? Used by the dashboard's
		// "Check Usage" menu item and pre-populated by the delete modal so
		// admins see content references before they confirm.
		register_rest_route(
			'generateblocks-pro/v1',
			'/admin/forms/(?P<id>\d+)/usage',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_form_usage' ],
				'permission_callback' => [ $this, 'admin_permission' ],
				'args'                => [
					'id'      => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'limit'   => [
						'required'          => false,
						'type'              => 'integer',
						'default'           => 50,
						'sanitize_callback' => 'absint',
					],
					'refresh' => [
						'required'          => false,
						'type'              => 'boolean',
						'default'           => false,
					],
				],
			]
		);

		// Admin: usage-aware form deletion. Mirrors the conditions delete
		// flow — a form referenced by published content is blocked unless
		// the caller passes force=true. The core REST DELETE on
		// /wp/v2/gblocks-forms/{id} would skip this guard, so the dashboard
		// uses this endpoint exclusively. force=true is always required;
		// trash is intentionally unsupported for forms.
		register_rest_route(
			'generateblocks-pro/v1',
			'/admin/forms/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_form' ],
				'permission_callback' => [ $this, 'admin_permission' ],
				'args'                => [
					'id'    => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'force' => [
						'required'          => false,
						'type'              => 'boolean',
						'default'           => false,
					],
				],
			]
		);

		register_rest_route(
			'generateblocks-pro/v1',
			'/forms/submit',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_submission' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'formId' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'postId' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'fields' => [
						'required'          => true,
						'type'              => 'object',
						'validate_callback' => function ( $value ) {
							return is_array( $value );
						},
						'sanitize_callback' => function ( $value ) {
							if ( ! is_array( $value ) ) {
								return [];
							}

							$flat = [];

							foreach ( $value as $key => $val ) {
								// sanitize_key (not sanitize_text_field) so the honeypot
								// lookup at handle_submission() can't be bypassed by
								// submitting the field name with non-lowercase casing.
								// Schema field names are all sanitize_key-normalized.
								$clean_key = sanitize_key( $key );

								if ( '' === $clean_key ) {
									continue;
								}

								if ( is_array( $val ) ) {
									$clean_array = [];

									foreach ( $val as $item ) {
										if ( is_string( $item ) || is_numeric( $item ) ) {
											$clean_array[] = (string) $item;
										}
									}

									$flat[ $clean_key ] = $clean_array;
								} elseif ( is_string( $val ) || is_numeric( $val ) ) {
									$flat[ $clean_key ] = (string) $val;
								}
							}

							return $flat;
						},
					],
					'gb_form_ts' => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'gb_hp' => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'cf_turnstile_response' => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);
	}

	/**
	 * Handle a form submission.
	 *
	 * Order of operations matters — cheapest checks first.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response The response.
	 */
	public function handle_submission( WP_REST_Request $request ) {
		$form_id    = absint( $request->get_param( 'formId' ) );
		$post_id    = absint( $request->get_param( 'postId' ) );
		$raw_fields = $request->get_param( 'fields' );
		$gb_form_ts = $request->get_param( 'gb_form_ts' );
		$gb_hp      = $request->get_param( 'gb_hp' );

		if ( ! $form_id ) {
			return $this->generic_error( 'missing_form_id' );
		}

		if ( ! is_array( $raw_fields ) ) {
			return $this->generic_error( 'fields_not_array' );
		}

		// Reject oversized submissions (max 500 KB aggregate, max 100 fields).
		if ( count( $raw_fields ) > 100 ) {
			return $this->generic_error( 'too_many_fields' );
		}

		$total_size = 0;

		foreach ( $raw_fields as $value ) {
			if ( is_string( $value ) ) {
				$total_size += strlen( $value );
			} elseif ( is_array( $value ) ) {
				foreach ( $value as $item ) {
					$total_size += is_string( $item ) ? strlen( $item ) : 0;
				}
			}

			if ( $total_size > 500000 ) {
				return $this->generic_error( 'payload_too_large' );
			}
		}

		// 1. Origin check — reject cross-site submissions.
		if ( ! $this->origin_is_allowed() ) {
			return $this->generic_error( 'origin_not_allowed' );
		}

		// 2. Honeypot — return fake success so bots don't adapt.
		if ( ! empty( $gb_hp ) ) {
			return $this->fake_success();
		}

		// 3. Timestamp check — bound to (form_id, post_id) so a harvested token
		// can only replay against the same form on the same page.
		//
		// `retry: true` on these branches lets the client request a fresh
		// token and replay once. Other failure branches (origin, post-process,
		// action errors) intentionally don't set it — replaying after a
		// partial action-success would re-execute already-delivered actions
		// (e.g. duplicate emails / webhooks).
		$verified_ts = GenerateBlocks_Pro_Form_Spam::verified_timestamp( $gb_form_ts, $form_id, $post_id );

		if ( false === $verified_ts ) {
			return $this->generic_error( 'timestamp_signature_invalid', true );
		}

		if ( ! GenerateBlocks_Pro_Form_Spam::is_fresh( $verified_ts ) ) {
			return $this->generic_error( 'timestamp_stale', true );
		}

		// 3b. Per-render honeypot check.
		$expected_hp = GenerateBlocks_Pro_Form_Spam::honeypot_field_name( $verified_ts, $form_id, $post_id );

		if ( isset( $raw_fields[ $expected_hp ] ) ) {
			$hp_value = $raw_fields[ $expected_hp ];

			if ( is_array( $hp_value ) ? ! empty( $hp_value ) : '' !== trim( (string) $hp_value ) ) {
				return $this->fake_success();
			}
		}

		// 4. Validate the form post exists + is published.
		$form = get_post( $form_id );

		if ( ! $form ) {
			return $this->generic_error( 'form_not_found:' . $form_id );
		}

		if ( GenerateBlocks_Pro_Form_Post_Type::POST_TYPE !== $form->post_type ) {
			return $this->generic_error( 'form_wrong_post_type:' . $form->post_type );
		}

		if ( 'publish' !== $form->post_status ) {
			return $this->generic_error( 'form_not_published:' . $form->post_status );
		}

		// 5. Validate the host post exists and is accessible.
		if ( $post_id ) {
			$host = get_post( $post_id );

			$allowed_statuses = [ 'publish', 'private' ];

			if ( ! $host || ! in_array( $host->post_status, $allowed_statuses, true ) ) {
				return $this->generic_error( 'host_not_accessible' );
			}

			if ( 'private' === $host->post_status && ! current_user_can( 'read_post', $post_id ) ) {
				return $this->generic_error( 'host_private_no_cap' );
			}

			if ( post_password_required( $host ) ) {
				return $this->generic_error( 'host_password_protected' );
			}
		}

		// 6. Process the submission.
		$context = [
			'form_id'  => $form_id,
			'post_id'  => $post_id,
			'page_url' => $post_id ? esc_url( get_permalink( $post_id ) ) : '',
		];

		$turnstile_token = $request->get_param( 'cf_turnstile_response' );

		if ( null !== $turnstile_token ) {
			$context['turnstile_token'] = $turnstile_token;
		}

		/**
		 * Filter to verify a form submission before processing.
		 *
		 * Receives ALL submitted fields, including fields injected by third-party
		 * plugins (e.g. Cloudflare Turnstile, hCaptcha). These extra fields are
		 * available here but will be stripped during schema-based sanitization,
		 * so this is the correct hook for verifying them.
		 *
		 * Return a WP_Error to reject the submission.
		 *
		 * @since 2.6.0
		 * @param null|WP_Error $pre_result Default null. Return WP_Error to reject.
		 * @param int           $form_id    The form post ID.
		 * @param array         $raw_fields All submitted fields.
		 * @param array         $context    Request context (post_id, page_url).
		 */
		$pre_result = apply_filters( 'generateblocks_form_verify_submission', null, $form_id, $raw_fields, $context );

		if ( is_wp_error( $pre_result ) ) {
			return $this->generic_error( 'pre_verify:' . $pre_result->get_error_code() );
		}

		$result = GenerateBlocks_Pro_Form_Processor::process( $form_id, $post_id, $raw_fields, $context );

		if ( is_wp_error( $result ) ) {
			$error_data     = $result->get_error_data();
			$public_message = (
				is_array( $error_data )
				&& ! empty( $error_data['public_message'] )
				&& is_string( $error_data['public_message'] )
			)
				? $error_data['public_message']
				: '';

			return $this->generic_error(
				'processor:' . $result->get_error_code() . ':' . $result->get_error_message(),
				false,
				$public_message
			);
		}

		/**
		 * Fires after a form submission has been successfully processed.
		 *
		 * @since 2.6.0
		 * @param int   $form_id The form post ID.
		 * @param array $context Request context (post_id, page_url).
		 */
		do_action( 'generateblocks_form_after_process', $form_id, $context );

		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * Permission callback for admin routes.
	 *
	 * @return bool
	 */
	public function admin_permission() {
		return GenerateBlocks_Pro_Form_Post_Type::current_user_can( 'manage' );
	}

	/**
	 * Validate a form_id resolves to a published/draft `gblocks_form` post.
	 *
	 * @param int $form_id Form post ID.
	 * @return bool
	 */
	private static function is_form_post( $form_id ) {
		$post = get_post( $form_id );

		return $post instanceof WP_Post
			&& GenerateBlocks_Pro_Form_Post_Type::POST_TYPE === $post->post_type;
	}

	/**
	 * Check whether a form has opted into submission storage.
	 *
	 * @param int $form_id Form post ID.
	 * @return bool
	 */
	private static function form_submission_storage_enabled( $form_id ) {
		$meta = get_post_meta( absint( $form_id ), GenerateBlocks_Pro_Form_Post_Type::META_KEY, true );
		$meta = is_array( $meta ) ? $meta : [];

		return ! empty( $meta['config']['storeSubmissions'] );
	}

	/**
	 * List stored submissions for a single form.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public function list_form_submissions( WP_REST_Request $request ) {
		$form_id  = absint( $request->get_param( 'id' ) );
		$page     = max( 1, absint( $request->get_param( 'page' ) ) );
		$per_page = absint( $request->get_param( 'per_page' ) );
		$per_page = $per_page ? $per_page : GenerateBlocks_Pro_Form_Submissions::DEFAULT_PAGE_SIZE;
		$per_page = max( 1, min( GenerateBlocks_Pro_Form_Submissions::MAX_PAGE_SIZE, $per_page ) );

		if ( ! self::is_form_post( $form_id ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Form not found.',
				],
				404
			);
		}

		$storage_enabled = self::form_submission_storage_enabled( $form_id );

		if ( $storage_enabled && ! GenerateBlocks_Pro_Form_Submissions::table_exists() ) {
			GenerateBlocks_Pro_Form_Submissions::maybe_install( $form_id );
		}

		$total       = GenerateBlocks_Pro_Form_Submissions::total_for_form( $form_id );
		$total_pages = $total > 0 ? (int) ceil( $total / $per_page ) : 1;
		$page        = min( $page, $total_pages );
		$items       = GenerateBlocks_Pro_Form_Submissions::get_for_form( $form_id, $page, $per_page );
		$limits      = GenerateBlocks_Pro_Form_Submissions::storage_limits_for_form( $form_id );

		return new WP_REST_Response(
			[
				'items'          => $items,
				'total'          => $total,
				'totalPages'     => $total_pages,
				'page'           => $page,
				'perPage'        => $per_page,
				'storageEnabled' => $storage_enabled,
				'storageError'   => GenerateBlocks_Pro_Form_Submissions::get_storage_error( $form_id ),
				'maxRecords'     => $limits['max_records'],
				'retentionDays'  => $limits['retention_days'],
			],
			200
		);
	}

	/**
	 * Delete one stored submission (scoped to its form).
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public function delete_form_submission( WP_REST_Request $request ) {
		$form_id       = absint( $request->get_param( 'id' ) );
		$submission_id = absint( $request->get_param( 'submissionId' ) );

		if ( ! self::is_form_post( $form_id ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Form not found.',
				],
				404
			);
		}

		$result = GenerateBlocks_Pro_Form_Submissions::delete_record( $form_id, $submission_id );

		if ( true === $result ) {
			return new WP_REST_Response(
				[
					'success'      => true,
					'submissionId' => $submission_id,
				],
				200
			);
		}

		if ( null === $result ) {
			return new WP_REST_Response(
				[
					'success'      => false,
					'submissionId' => $submission_id,
					'message'      => 'Not found.',
				],
				404
			);
		}

		return new WP_REST_Response(
			[
				'success'      => false,
				'submissionId' => $submission_id,
				'message'      => 'Could not persist deletion; please retry.',
			],
			500
		);
	}

	/**
	 * Delete every stored submission for a form.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public function clear_form_submissions( WP_REST_Request $request ) {
		$form_id = absint( $request->get_param( 'id' ) );

		if ( ! self::is_form_post( $form_id ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Form not found.',
				],
				404
			);
		}

		$deleted = GenerateBlocks_Pro_Form_Submissions::clear_for_form( $form_id );

		if ( false === $deleted ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Could not persist clear; please retry.',
				],
				500
			);
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'deleted' => $deleted,
			],
			200
		);
	}

	/**
	 * Duplicate a form post (content + meta).
	 *
	 * Status is forced to `draft` on the copy — seed_new_form() intentionally
	 * skips `$update=true` inserts, but a fresh insert with `draft` gives the
	 * user a chance to review before publishing regardless.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public function duplicate_form( WP_REST_Request $request ) {
		$id   = (int) $request->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post || GenerateBlocks_Pro_Form_Post_Type::POST_TYPE !== $post->post_type ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Not found.',
				],
				404
			);
		}

		$base_title = $post->post_title ? $post->post_title : __( '(untitled)', 'generateblocks-pro' );

		// Mint fresh uniqueIds on every block before inserting. uniqueId drives
		// `gb-field-{id}` field IDs and the generated CSS class names; copying
		// post_content verbatim would let the original and the duplicate
		// collide if both end up on the same page.
		$new_content = self::regenerate_block_unique_ids( $post->post_content );

		$new_id = wp_insert_post(
			wp_slash(
				[
					'post_type'    => GenerateBlocks_Pro_Form_Post_Type::POST_TYPE,
					'post_status'  => 'draft',
					/* translators: %s: original form title */
					'post_title'   => sprintf( __( '%s (Copy)', 'generateblocks-pro' ), $base_title ),
					'post_content' => $new_content,
				]
			),
			true
		);

		if ( is_wp_error( $new_id ) || ! $new_id ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Duplicate failed.',
				],
				500
			);
		}

		$meta = get_post_meta( $id, GenerateBlocks_Pro_Form_Post_Type::META_KEY, true );

		if ( is_array( $meta ) ) {
			update_post_meta( $new_id, GenerateBlocks_Pro_Form_Post_Type::META_KEY, $meta );
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'id'      => (int) $new_id,
			],
			200
		);
	}

	/**
	 * Toggle a single form's submission storage.
	 *
	 * Read-modify-write of the nested `_gb_form.config.storeSubmissions` flag
	 * so the dashboard can enable storage from the storage-off failure view
	 * without opening the block editor. Enabling installs the submissions
	 * table via the meta-save hook on GenerateBlocks_Pro_Form_Post_Type, so
	 * future submissions start being recorded immediately.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public function update_form_storage( WP_REST_Request $request ) {
		$form_id = absint( $request->get_param( 'id' ) );
		$enabled = (bool) $request->get_param( 'enabled' );

		if ( ! self::is_form_post( $form_id ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Form not found.',
				],
				404
			);
		}

		$meta = get_post_meta( $form_id, GenerateBlocks_Pro_Form_Post_Type::META_KEY, true );
		$meta = is_array( $meta ) ? $meta : [];

		// Preserve every other config key — only the storage flag changes.
		// Seed from the defaults when a form has never saved config so we
		// don't write a config blob that's missing expected keys.
		$config = isset( $meta['config'] ) && is_array( $meta['config'] )
			? $meta['config']
			: GenerateBlocks_Pro_Form_Post_Type::default_form_config();

		$config['storeSubmissions'] = $enabled;
		$meta['config']             = $config;

		// update_post_meta() unslashes values before saving. Slash the complete
		// blob so unrelated text config containing backslashes survives this
		// read-modify-write unchanged.
		update_post_meta( $form_id, GenerateBlocks_Pro_Form_Post_Type::META_KEY, wp_slash( $meta ) );

		return new WP_REST_Response(
			[
				'success'        => true,
				'storageEnabled' => $enabled,
			],
			200
		);
	}

	/**
	 * Walk parsed blocks and replace every `uniqueId` attribute with a fresh
	 * 8-char hex value, matching the format the editor mints from
	 * `clientId.substr(2, 9).replace('-', '')`.
	 *
	 * Returns serialized post_content. If parse fails or content is empty,
	 * the original is returned untouched.
	 *
	 * @param string $post_content Post content to walk.
	 * @return string Post content with regenerated uniqueIds.
	 */
	public static function regenerate_block_unique_ids( $post_content ) {
		if ( ! is_string( $post_content ) || '' === trim( $post_content ) ) {
			return $post_content;
		}

		$blocks = parse_blocks( $post_content );

		if ( empty( $blocks ) ) {
			return $post_content;
		}

		$walk = static function ( &$blocks ) use ( &$walk ) {
			foreach ( $blocks as &$block ) {
				$old_id = $block['attrs']['uniqueId'] ?? '';

				if ( '' !== $old_id ) {
					$new_id                     = self::mint_unique_id();
					$block['attrs']['uniqueId'] = $new_id;

					// Three places carry the old id forward when only the attr is
					// rewritten: (1) `getBlockClasses` bakes `{slug}-{uniqueId}`
					// into the saved wrapper class in the block's innerContent;
					// (2) innerHTML mirrors innerContent and is read by some
					// block APIs; (3) attrs.css holds the local stylesheet and
					// is emitted verbatim by GenerateBlocks_Block::get_css() as
					// selectors like `.gb-form-field-{uniqueId}`. Without all
					// three rewrites a duplicate renders with the new wrapper
					// class but CSS still targets the old id, so the duplicate
					// looks unstyled. The 8-char hex id shape makes string
					// replace effectively collision-free.
					$needle      = '-' . $old_id;
					$replacement = '-' . $new_id;

					if ( ! empty( $block['innerContent'] ) && is_array( $block['innerContent'] ) ) {
						foreach ( $block['innerContent'] as $i => $chunk ) {
							if ( is_string( $chunk ) ) {
								$block['innerContent'][ $i ] = str_replace( $needle, $replacement, $chunk );
							}
						}
					}

					if ( isset( $block['innerHTML'] ) && is_string( $block['innerHTML'] ) ) {
						$block['innerHTML'] = str_replace( $needle, $replacement, $block['innerHTML'] );
					}

					if ( isset( $block['attrs']['css'] ) && is_string( $block['attrs']['css'] ) ) {
						$block['attrs']['css'] = str_replace( $needle, $replacement, $block['attrs']['css'] );
					}
				}

				if ( ! empty( $block['innerBlocks'] ) ) {
					$walk( $block['innerBlocks'] );
				}
			}
		};

		$walk( $blocks );

		return serialize_blocks( $blocks );
	}

	/**
	 * Mint an 8-char hex uniqueId in the same shape the editor uses.
	 *
	 * @return string
	 */
	private static function mint_unique_id() {
		// Editor mints via `clientId.substr(2, 9).replace('-', '')` — slicing
		// 9 chars from a UUID always crosses one dash, leaving 8 hex chars
		// after the strip. Match that 8-char shape so duplicates are
		// indistinguishable from editor-minted ids.
		$uuid = str_replace( '-', '', wp_generate_uuid4() );

		return substr( $uuid, 0, 8 );
	}

	/**
	 * Issue a security token for a form on a given host page.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public function issue_security_token( WP_REST_Request $request ) {
		$form_id = absint( $request->get_param( 'formId' ) );
		$post_id = absint( $request->get_param( 'postId' ) );

		if ( ! $form_id ) {
			return $this->security_token_error();
		}

		// Softer origin check on the GET token issuer (see comments below).
		if ( ! $this->referer_or_origin_is_same_site() ) {
			return $this->security_token_error();
		}

		// Confirm the form exists + is published. Without this, the issuer
		// could mint tokens for nonexistent or draft forms — submission
		// handler would reject them, but we want consistent failure modes.
		$form = get_post( $form_id );

		if (
			! $form
			|| GenerateBlocks_Pro_Form_Post_Type::POST_TYPE !== $form->post_type
			|| 'publish' !== $form->post_status
		) {
			return $this->security_token_error();
		}

		// Host page (optional — forms might be rendered standalone).
		if ( $post_id ) {
			$host = get_post( $post_id );

			if ( ! $host || ! in_array( $host->post_status, [ 'publish', 'private' ], true ) ) {
				return $this->security_token_error();
			}

			if ( 'private' === $host->post_status && ! current_user_can( 'read_post', $post_id ) ) {
				return $this->security_token_error();
			}

			if ( post_password_required( $host ) ) {
				return $this->security_token_error();
			}
		}

		$token   = GenerateBlocks_Pro_Form_Spam::generate_timestamp( $form_id, $post_id );
		$ts_only = (int) explode( '-', $token, 2 )[0];
		$hp_name = GenerateBlocks_Pro_Form_Spam::honeypot_field_name( $ts_only, $form_id, $post_id );

		$response = new WP_REST_Response(
			[
				'gb_form_ts' => $token,
				'hp_name'    => $hp_name,
			],
			200
		);

		$response->header( 'Cache-Control', 'private, no-store, max-age=0' );

		return $response;
	}

	/**
	 * Generic error response for the token issuer.
	 *
	 * @return WP_REST_Response
	 */
	private function security_token_error() {
		$response = new WP_REST_Response(
			[
				'success' => false,
				'message' => __( 'Unable to issue token.', 'generateblocks-pro' ),
			],
			200
		);

		$response->header( 'Cache-Control', 'private, no-store, max-age=0' );

		return $response;
	}

	/**
	 * Strict origin check for the POST submit endpoint.
	 *
	 * @return bool
	 */
	private function origin_is_allowed() {
		$origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ) : '';

		if ( '' !== $origin ) {
			return $this->matches_site( $origin );
		}

		// Some legitimate requests arrive without an Origin header (older browsers,
		// strict Referrer-Policy documents). Fall back to Referer before the filter
		// so admins enabling `generateblocks_form_allow_missing_origin` keep
		// at least one same-site signal.
		$referer = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';

		if ( '' !== $referer ) {
			return $this->matches_site( $referer );
		}

		return (bool) apply_filters( 'generateblocks_form_allow_missing_origin', false );
	}

	/**
	 * Softer origin check for the GET token issuer.
	 *
	 * @return bool
	 */
	private function referer_or_origin_is_same_site() {
		$origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ) : '';

		if ( '' !== $origin ) {
			return $this->matches_site( $origin );
		}

		$referer = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';

		if ( '' !== $referer ) {
			return $this->matches_site( $referer );
		}

		return (bool) apply_filters( 'generateblocks_form_allow_missing_origin', false );
	}

	/**
	 * Compare a URL's scheme+host+port to home_url().
	 *
	 * @param string $url The URL to compare.
	 * @return bool
	 */
	private function matches_site( $url ) {
		$parsed = wp_parse_url( $url );
		$home   = wp_parse_url( home_url() );

		if ( empty( $parsed['scheme'] ) || empty( $parsed['host'] ) || empty( $home['scheme'] ) || empty( $home['host'] ) ) {
			return false;
		}

		$scheme = strtolower( $parsed['scheme'] );

		if ( strtolower( $home['scheme'] ) !== $scheme ) {
			return false;
		}

		if ( strtolower( $parsed['host'] ) !== strtolower( $home['host'] ) ) {
			return false;
		}

		// Browsers omit default ports from Origin/Referer (so `https://x.com`,
		// not `https://x.com:443`), but home_url() may be configured with
		// explicit ports (or vice versa behind a reverse proxy). Resolve both
		// sides to the explicit port before comparing so a representational
		// difference doesn't cause a false rejection.
		$default_port = 'https' === $scheme ? 443 : ( 'http' === $scheme ? 80 : null );
		$req_port     = $parsed['port'] ?? $default_port;
		$home_port    = $home['port'] ?? $default_port;

		return $req_port === $home_port;
	}

	/**
	 * Return a generic error response.
	 *
	 * @param string $reason    Internal reason for the rejection (logged only when WP_DEBUG).
	 * @param bool   $retryable Set when a fresh client retry could legitimately succeed
	 *                          (currently only timestamp-token failures). Surfaced as
	 *                          `retry: true` so the runtime knows it's safe to re-mint
	 *                          a token and resubmit. Any failure path that may have
	 *                          already executed side effects (action-success then
	 *                          action-fail) MUST leave this false to avoid duplicates.
	 * @param string $public_message Optional public-safe message for known configuration failures.
	 * @return WP_REST_Response
	 */
	private function generic_error( $reason = '', $retryable = false, $public_message = '' ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && '' !== $reason ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'GB Form reject: ' . sanitize_text_field( $reason ) );
		}

		$public_message = sanitize_text_field( (string) $public_message );

		$payload = [
			'success' => false,
			'message' => '' !== $public_message
				? $public_message
				: __( 'Something went wrong. Please try again.', 'generateblocks-pro' ),
		];

		if ( '' !== $public_message ) {
			$payload['publicMessage'] = true;
		}

		if ( $retryable ) {
			$payload['retry'] = true;
		}

		return new WP_REST_Response( $payload, 200 );
	}

	/**
	 * Return a fake success response for honeypot-caught bots.
	 *
	 * @return WP_REST_Response
	 */
	private function fake_success() {
		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * Get the list of posts that embed a given form.
	 *
	 * Cached for 15 minutes per (form_id, limit) pair. Pass `refresh=true`
	 * to bypass the cache — used by the delete modal so the warning reflects
	 * current content at delete time.
	 *
	 * Cache invalidation is intentionally manual (refresh button in the UI),
	 * matching the conditions usage cache: invalidating on every host-page
	 * save would force us to clear caches across every form when any post
	 * changes, which is too broad to be useful.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public function get_form_usage( WP_REST_Request $request ) {
		$form_id = absint( $request->get_param( 'id' ) );
		$limit   = absint( $request->get_param( 'limit' ) );
		$refresh = (bool) $request->get_param( 'refresh' );

		if ( ! $form_id ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'Form ID is required.', 'generateblocks-pro' ),
				],
				400
			);
		}

		if ( ! self::is_form_post( $form_id ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'Form not found.', 'generateblocks-pro' ),
				],
				404
			);
		}

		$cache_key = sprintf( 'gb_form_usage_%d_limit%d', $form_id, $limit );

		if ( ! $refresh ) {
			$cached = $this->get_usage_cache( $cache_key );

			if ( false !== $cached ) {
				$cached['cached']    = true;
				$cached['cached_at'] = $cached['cached_at'] ?? time();

				return new WP_REST_Response(
					[
						'success'  => true,
						'response' => $cached,
					],
					200
				);
			}
		}

		$usage_data              = GenerateBlocks_Pro_Form_Post_Type::search_form_usage( $form_id, $limit );
		$usage_data['cached_at'] = time();

		$this->set_usage_cache( $cache_key, $usage_data, 900 );

		$usage_data['cached'] = false;

		return new WP_REST_Response(
			[
				'success'  => true,
				'response' => $usage_data,
			],
			200
		);
	}

	/**
	 * Delete a form, with a usage guard.
	 *
	 * Mirrors the conditions delete flow: without `force=true`, run a fresh
	 * usage scan (with the per-page cap raised so we're less likely to miss
	 * older content) and 409 if anything references the form. With
	 * `force=true`, skip the guard and call `wp_delete_post()` directly.
	 *
	 * Trash is intentionally not supported here — forms are configuration,
	 * not content, and a trashed form still leaves embedded blocks rendering
	 * nothing on the frontend.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_form( WP_REST_Request $request ) {
		$form_id = absint( $request->get_param( 'id' ) );
		$force   = (bool) $request->get_param( 'force' );

		if ( ! $form_id ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'Form ID is required.', 'generateblocks-pro' ),
				],
				400
			);
		}

		if ( ! self::is_form_post( $form_id ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'Form not found.', 'generateblocks-pro' ),
				],
				404
			);
		}

		if ( ! $force ) {
			// Raise the scan cap at delete time so we're less likely to
			// miss a reference in older content. Sites can override
			// further via the standard filter.
			$raise_scan_cap = static function ( $max ) {
				$current = is_numeric( $max ) ? (int) $max : 5000;

				return max( $current, 50000 );
			};

			add_filter( 'generateblocks_usage_search_max_posts', $raise_scan_cap, 99 );

			try {
				$usage = GenerateBlocks_Pro_Form_Post_Type::search_form_usage( $form_id, 50 );
			} finally {
				remove_filter( 'generateblocks_usage_search_max_posts', $raise_scan_cap, 99 );
			}

			$usage_found           = ! empty( $usage['total'] );
			$usage_scan_incomplete = ! empty( $usage['limited'] ) || ! empty( $usage['has_more'] );

			if ( $usage_found || $usage_scan_incomplete ) {
				$this->clear_usage_caches( 'gb_form_usage_' . $form_id . '_' );

				return new WP_Error(
					'form_in_use',
					__( 'This form is still embedded in published content, or usage could not be fully verified. Resend the request with force=true to confirm deletion.', 'generateblocks-pro' ),
					[
						'status' => 409,
						'usage'  => $usage,
					]
				);
			}
		}

		$result = wp_delete_post( $form_id, true );

		if ( ! $result ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'Failed to delete form.', 'generateblocks-pro' ),
				],
				500
			);
		}

		$this->clear_usage_caches( 'gb_form_usage_' . $form_id . '_' );

		return new WP_REST_Response(
			[
				'success' => true,
				'deleted' => true,
			],
			200
		);
	}
}
