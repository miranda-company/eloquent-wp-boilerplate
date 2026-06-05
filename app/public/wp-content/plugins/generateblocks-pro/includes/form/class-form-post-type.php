<?php
/**
 * Form custom post type registration.
 *
 * Forms are authoritative: each form is a `gblocks_form` post. The Form
 * block on a page stores only a `formId` reference; all submission config
 * lives in post meta on the form itself. Storage mirrors the Conditions
 * + Overlays pattern already in this plugin.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form post type class.
 */
class GenerateBlocks_Pro_Form_Post_Type extends GenerateBlocks_Pro_Singleton {

	const POST_TYPE = 'gblocks_form';
	const META_KEY  = '_gb_form';
	const HEALTH_META_KEY = '_gb_form_health';
	const FORM_REFS_META_KEY = '_generateblocks_form_refs';
	const LEGACY_FAILED_DELIVERY_META_KEY = '_gb_form_failed_delivery';

	/**
	 * Initialize.
	 */
	public function init() {
		// We are *already* on the `init` action by the time this runs (called from
		// generateblocks_pro_init_form_system). WordPress's hook iterator does not
		// pick up callbacks added at the same priority mid-execution, so we have to
		// invoke register_post_type / register_meta directly. Forgetting this leaves
		// the CPT unregistered — the symptom is REST writes failing with
		// "Invalid post type" and the editor's Create-new flow erroring out.
		$this->register_post_type();
		$this->register_meta();

		add_action( 'rest_api_init', [ $this, 'register_rest_fields' ] );
		add_action( 'admin_init', [ $this, 'redirect_admin_view' ] );
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_dashboard_scripts' ] );
		add_action( 'generateblocks_dashboard_tabs', [ $this, 'add_dashboard_tab' ] );
		add_filter( 'generateblocks_dashboard_screens', [ $this, 'add_dashboard_screen' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
		add_action( 'wp_after_insert_post', [ $this, 'seed_new_form' ], 10, 4 );
		add_action( 'rest_after_insert_' . self::POST_TYPE, [ $this, 'maybe_install_submissions_table_after_rest_save' ], 10, 3 );
		add_action( 'added_post_meta', [ $this, 'maybe_install_submissions_table_after_meta_save' ], 10, 4 );
		add_action( 'updated_post_meta', [ $this, 'maybe_install_submissions_table_after_meta_save' ], 10, 4 );
		add_action( 'before_delete_post', [ $this, 'delete_form_submissions' ], 10, 2 );
		add_action( 'save_post', [ $this, 'store_form_references' ], 20, 2 );
		add_action( 'save_post_' . self::POST_TYPE, [ $this, 'invalidate_referencing_post_css' ], 20, 2 );
		add_action( 'save_post_' . self::POST_TYPE, [ $this, 'delete_legacy_failed_delivery_meta' ], 30, 2 );
		add_filter( 'allowed_block_types_all', [ $this, 'filter_form_editor_allowed_blocks' ], 10, 2 );
		add_filter( 'rest_' . self::POST_TYPE . '_query', [ $this, 'restrict_rest_query' ], 10, 2 );
		add_filter( 'rest_prepare_' . self::POST_TYPE, [ $this, 'gate_meta_read' ], 10, 3 );
		// Priority 11 runs after the schema-builder gate at 10 so a form
		// with malformed fields surfaces that error first; either rejection
		// is a 400 and the editor renders the message inline.
		add_filter( 'rest_pre_insert_' . self::POST_TYPE, [ $this, 'gate_publish_completeness' ], 11, 2 );
	}

	/**
	 * Register REST fields exposed on `gblocks_form` reads.
	 *
	 * `_gb_form_meta` contains submission counts per form, derived from a
	 * single COUNT-GROUP-BY against the submissions table when it exists.
	 * Surfaced so the dashboard can render its status dot without a per-row
	 * roundtrip.
	 */
	public function register_rest_fields() {
		register_rest_field(
			self::POST_TYPE,
			'_gb_form_meta',
			[
				'get_callback' => function ( $post_array ) {
					$form_id = (int) $post_array['id'];
					$counts  = GenerateBlocks_Pro_Form_Submissions::counts_for_form( $form_id );
					$meta    = get_post_meta( $form_id, self::META_KEY, true );
					$meta   = is_array( $meta ) ? $meta : [];

					$counts['storage_enabled'] = ! empty( $meta['config']['storeSubmissions'] );
					$counts['health']          = self::get_form_health( $form_id );

					return $counts;
				},
				'schema'       => [
					'type'       => 'object',
					'properties' => [
						'submission_count'        => [ 'type' => 'integer' ],
						'failed_submission_count' => [ 'type' => 'integer' ],
						'storage_enabled'         => [ 'type' => 'boolean' ],
						'health'                  => [
							'type'       => 'object',
							'properties' => [
								'status'               => [ 'type' => 'string' ],
								'last_checked_gmt'     => [ 'type' => 'string' ],
								'last_success_gmt'     => [ 'type' => 'string' ],
								'last_failure_gmt'     => [ 'type' => 'string' ],
								'last_error_code'      => [ 'type' => 'string' ],
								'consecutive_failures' => [ 'type' => 'integer' ],
							],
						],
					],
				],
			]
		);
	}

	/**
	 * Default non-PII delivery health for a form.
	 *
	 * An empty health row means "no known delivery failures". We avoid writing
	 * this state on every successful submission; successful delivery only writes
	 * when it clears a previous failure.
	 *
	 * @return array
	 */
	public static function default_form_health() {
		return [
			'status'               => 'healthy',
			'last_checked_gmt'     => '',
			'last_success_gmt'     => '',
			'last_failure_gmt'     => '',
			'last_error_code'      => '',
			'consecutive_failures' => 0,
		];
	}

	/**
	 * Get sanitized delivery health for a form.
	 *
	 * @param int $form_id Form post ID.
	 * @return array
	 */
	public static function get_form_health( $form_id ) {
		$health = get_post_meta( absint( $form_id ), self::HEALTH_META_KEY, true );

		return self::sanitize_form_health( $health );
	}

	/**
	 * Mark a form's delivery health as failing.
	 *
	 * No submitted field values or action error messages are stored here. The
	 * submission drawer remains the opt-in place for payload/error details.
	 *
	 * @param int   $form_id Form post ID.
	 * @param array $errors  Delivery/action errors.
	 */
	public static function record_delivery_failure( $form_id, $errors ) {
		$form_id = absint( $form_id );

		if ( ! $form_id ) {
			return;
		}

		$health = self::get_form_health( $form_id );
		$now    = current_time( 'mysql', true );

		update_post_meta(
			$form_id,
			self::HEALTH_META_KEY,
			[
				'status'               => 'failing',
				'last_checked_gmt'     => $now,
				'last_success_gmt'     => $health['last_success_gmt'],
				'last_failure_gmt'     => $now,
				'last_error_code'      => self::get_delivery_error_code( $errors ),
				'consecutive_failures' => absint( $health['consecutive_failures'] ) + 1,
			]
		);
	}

	/**
	 * Mark a form's delivery health as healthy after a successful delivery.
	 *
	 * This is intentionally a no-op unless the previous state was failing, so
	 * normal successful submissions do not create hot-path postmeta writes.
	 *
	 * @param int $form_id Form post ID.
	 */
	public static function record_delivery_success( $form_id ) {
		$form_id = absint( $form_id );

		if ( ! $form_id ) {
			return;
		}

		$health = self::get_form_health( $form_id );

		if ( 'failing' !== $health['status'] ) {
			return;
		}

		$now = current_time( 'mysql', true );

		update_post_meta(
			$form_id,
			self::HEALTH_META_KEY,
			[
				'status'               => 'healthy',
				'last_checked_gmt'     => $now,
				'last_success_gmt'     => $now,
				'last_failure_gmt'     => $health['last_failure_gmt'],
				'last_error_code'      => '',
				'consecutive_failures' => 0,
			]
		);
	}

	/**
	 * Sanitize a delivery-health meta row.
	 *
	 * @param mixed $health Raw health meta.
	 * @return array
	 */
	private static function sanitize_form_health( $health ) {
		$health = is_array( $health ) ? $health : [];
		$out    = self::default_form_health();
		$status = isset( $health['status'] ) ? sanitize_key( $health['status'] ) : '';

		if ( in_array( $status, [ 'healthy', 'failing' ], true ) ) {
			$out['status'] = $status;
		}

		foreach ( [ 'last_checked_gmt', 'last_success_gmt', 'last_failure_gmt' ] as $key ) {
			if ( isset( $health[ $key ] ) && is_scalar( $health[ $key ] ) ) {
				$out[ $key ] = sanitize_text_field( (string) $health[ $key ] );
			}
		}

		if ( isset( $health['last_error_code'] ) && is_scalar( $health['last_error_code'] ) ) {
			$out['last_error_code'] = sanitize_key( (string) $health['last_error_code'] );
		}

		if ( isset( $health['consecutive_failures'] ) ) {
			$out['consecutive_failures'] = absint( $health['consecutive_failures'] );
		}

		return $out;
	}

	/**
	 * Get a non-PII error code for delivery-health metadata.
	 *
	 * @param array $errors Delivery/action errors.
	 * @return string
	 */
	private static function get_delivery_error_code( $errors ) {
		foreach ( (array) $errors as $error ) {
			if ( is_wp_error( $error ) ) {
				$code = sanitize_key( $error->get_error_code() );

				if ( '' !== $code ) {
					return $code;
				}
			}
		}

		return 'action_failed';
	}

	/**
	 * Add the Forms admin page (under the GenerateBlocks parent menu).
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'generateblocks',
			__( 'Forms', 'generateblocks-pro' ),
			__( 'Forms', 'generateblocks-pro' ),
			self::get_capability( 'manage' ),
			'generateblocks-forms',
			[ $this, 'render_dashboard' ],
			5
		);
	}

	/**
	 * Render the dashboard mount point.
	 */
	public function render_dashboard() {
		if ( ! self::current_user_can( 'manage' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'generateblocks-pro' ) );
		}
		?>
		<div class="wrap">
			<div id="gb-forms-dashboard"></div>
		</div>
		<?php
	}

	/**
	 * Enqueue the Forms dashboard React bundle on its own admin page.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_dashboard_scripts( $hook_suffix ) {
		if ( 'generateblocks_page_generateblocks-forms' !== $hook_suffix ) {
			return;
		}

		$assets = generateblocks_pro_get_enqueue_assets( 'forms-dashboard' );

		wp_enqueue_script(
			'gb-forms-dashboard',
			GENERATEBLOCKS_PRO_DIR_URL . 'dist/forms-dashboard.js',
			$assets['dependencies'],
			$assets['version'],
			true
		);

		wp_localize_script(
			'gb-forms-dashboard',
			'gbFormsDashboard',
			[
				'adminUrl' => admin_url(),
			]
		);

		wp_enqueue_style(
			'gb-forms-dashboard',
			GENERATEBLOCKS_PRO_DIR_URL . 'dist/forms-dashboard.css',
			[ 'wp-components', 'generateblocks-pro-dashboard-table' ],
			GENERATEBLOCKS_PRO_VERSION
		);
	}

	/**
	 * Register the Forms tab in the GB dashboard navigation.
	 *
	 * @param array $tabs Existing tabs.
	 * @return array
	 */
	public function add_dashboard_tab( $tabs ) {
		$screen = get_current_screen();

		$tabs['forms'] = [
			'name'  => __( 'Forms', 'generateblocks-pro' ),
			'url'   => admin_url( 'admin.php?page=generateblocks-forms' ),
			'class' => $screen && 'generateblocks_page_generateblocks-forms' === $screen->id ? 'active' : '',
		];

		return $tabs;
	}

	/**
	 * Register the Forms screen as a GB dashboard page.
	 *
	 * @param array $pages Existing pages.
	 * @return array
	 */
	public function add_dashboard_screen( $pages ) {
		$pages[] = 'generateblocks_page_generateblocks-forms';

		return $pages;
	}

	/**
	 * Enqueue the form editor sidebar plugin only on `gblocks_form` posts.
	 *
	 * Other CPTs don't need the form-level config UI; gating via screen check
	 * keeps the bundle off every block editor screen.
	 */
	public function enqueue_editor_assets() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
			return;
		}

		$assets = generateblocks_pro_get_enqueue_assets( 'form-editor' );

		wp_enqueue_script(
			'generateblocks-pro-form-editor',
			GENERATEBLOCKS_PRO_DIR_URL . 'dist/form-editor.js',
			$assets['dependencies'],
			$assets['version'],
			true
		);

		wp_enqueue_style(
			'generateblocks-pro-form-editor',
			GENERATEBLOCKS_PRO_DIR_URL . 'dist/form-editor.css',
			[ 'wp-components' ],
			$assets['version']
		);

		wp_set_script_translations(
			'generateblocks-pro-form-editor',
			'generateblocks-pro'
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['gb_form_context'] ) || '1' !== $_GET['gb_form_context'] ) {
			return;
		}

		$iframe_assets = generateblocks_pro_get_enqueue_assets( 'form-iframe-context' );

		wp_enqueue_script(
			'generateblocks-pro-form-iframe-context',
			GENERATEBLOCKS_PRO_DIR_URL . 'dist/form-iframe-context.js',
			$iframe_assets['dependencies'],
			$iframe_assets['version'],
			true
		);

		wp_enqueue_style(
			'generateblocks-pro-form-iframe-context',
			GENERATEBLOCKS_PRO_DIR_URL . 'dist/form-iframe-context.css',
			[],
			$iframe_assets['version']
		);

		wp_set_script_translations(
			'generateblocks-pro-form-iframe-context',
			'generateblocks-pro'
		);

		add_filter( 'admin_body_class', [ $this, 'add_form_context_body_class' ] );
	}

	/**
	 * Add a body class for embedded form editor mode.
	 *
	 * @param string $classes Existing body classes.
	 * @return string
	 */
	public function add_form_context_body_class( $classes ) {
		return $classes . ' gb-form-context';
	}

	/**
	 * Constrain the block inserter based on whether we're editing a form.
	 *
	 * Inside the Form CPT: the Form block is insertable (so authors can put
	 * one back if they delete the auto-inserted one) but reference blocks are
	 * not — embedding a renderer creates nested forms and recursive render
	 * chains, and synced patterns can change outside the form save path so
	 * the saved schema would drift from what renders.
	 *
	 * Outside the Form CPT: leave the global inserter policy untouched. The
	 * Form block is fail-closed in block.json and opts into the inserter only
	 * in the Form CPT editor.
	 *
	 * @param bool|array              $allowed_block_types Allowed block types.
	 * @param WP_Block_Editor_Context $editor_context      Editor context.
	 * @return bool|array
	 */
	public function filter_form_editor_allowed_blocks( $allowed_block_types, $editor_context ) {
		$post        = isset( $editor_context->post ) ? $editor_context->post : null;
		$is_form_cpt = $post instanceof WP_Post && self::POST_TYPE === $post->post_type;

		if ( ! $is_form_cpt ) {
			return $allowed_block_types;
		}

		$disallowed = [ 'generateblocks-pro/form-render', 'core/block' ];

		if ( false === $allowed_block_types ) {
			return false;
		}

		if ( is_array( $allowed_block_types ) ) {
			return array_values( array_diff( $allowed_block_types, $disallowed ) );
		}

		if ( class_exists( 'WP_Block_Type_Registry' ) ) {
			$registered = WP_Block_Type_Registry::get_instance()->get_all_registered();

			return array_values( array_diff( array_keys( $registered ), $disallowed ) );
		}

		return $allowed_block_types;
	}

	/**
	 * Get the capability for a given context.
	 *
	 * Mirrors Overlays + Conditions. `manage` gates create/edit/delete;
	 * `use` gates "select a form in a block dropdown" which any editor can
	 * do. Filter `generateblocks_form_capability` lets sites raise or
	 * lower either bar.
	 *
	 * @param string $context 'manage' or 'use'.
	 * @return string
	 */
	public static function get_capability( $context = 'use' ) {
		$capability = 'manage' === $context ? 'manage_options' : 'edit_posts';

		/**
		 * Filter the capability required for forms.
		 *
		 * @since 2.6.0
		 * @param string $capability The capability required.
		 * @param string $context    'manage' or 'use'.
		 */
		return apply_filters( 'generateblocks_form_capability', $capability, $context );
	}

	/**
	 * Current user capability check.
	 *
	 * @param string $context 'manage' or 'use'.
	 * @return bool
	 */
	public static function current_user_can( $context = 'use' ) {
		return current_user_can( self::get_capability( $context ) );
	}

	/**
	 * Register the form CPT.
	 *
	 * `template` gives new forms a single starter Form block, but the editor
	 * remains unlocked so authors can wrap that block in normal layout/content.
	 * Save validation enforces the real invariant: exactly one Form block must
	 * exist anywhere in the form post content.
	 *
	 * `custom-fields` support is included so WP REST exposes registered meta
	 * on editor reads/writes. That does not surface the legacy Custom Fields
	 * metabox unless users enable it manually.
	 */
	public function register_post_type() {
		$manage_cap = self::get_capability( 'manage' );

		$labels = [
			'name'               => _x( 'Forms', 'post type general name', 'generateblocks-pro' ),
			'singular_name'      => _x( 'Form', 'post type singular name', 'generateblocks-pro' ),
			'menu_name'          => _x( 'Forms', 'admin menu', 'generateblocks-pro' ),
			'name_admin_bar'     => _x( 'Form', 'add new on admin bar', 'generateblocks-pro' ),
			'add_new'            => _x( 'Add New', 'form', 'generateblocks-pro' ),
			'add_new_item'       => __( 'Add New Form', 'generateblocks-pro' ),
			'new_item'           => __( 'New Form', 'generateblocks-pro' ),
			'edit_item'          => __( 'Edit Form', 'generateblocks-pro' ),
			'view_item'          => __( 'View Form', 'generateblocks-pro' ),
			'all_items'          => __( 'All Forms', 'generateblocks-pro' ),
			'search_items'       => __( 'Search Forms', 'generateblocks-pro' ),
			'not_found'          => __( 'No forms found.', 'generateblocks-pro' ),
			'not_found_in_trash' => __( 'No forms found in Trash.', 'generateblocks-pro' ),
		];

		$args = [
			'labels'              => $labels,
			// `custom-fields` is required for WP REST to expose the `meta`
			// key on responses — without it the block editor cannot read
			// `_gb_form` via useEntityProp and the sidebar renders with an
			// empty config (resulting in spurious "no actions" warnings).
			'supports'            => [ 'title', 'editor', 'revisions', 'custom-fields' ],
			'hierarchical'        => false,
			'public'              => false,
			'publicly_queryable'  => false,
			'has_archive'         => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'exclude_from_search' => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'rewrite'             => false,
			'can_export'          => true,
			'show_in_rest'        => true,
			'rest_base'           => 'gblocks-forms',
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'capabilities'        => [
				'edit_posts'             => $manage_cap,
				'edit_others_posts'      => $manage_cap,
				'delete_posts'           => $manage_cap,
				'delete_others_posts'    => $manage_cap,
				'delete_private_posts'   => $manage_cap,
				'delete_published_posts' => $manage_cap,
				'edit_private_posts'     => $manage_cap,
				'edit_published_posts'   => $manage_cap,
				'publish_posts'          => $manage_cap,
				'read_private_posts'     => $manage_cap,
				'create_posts'           => $manage_cap,
				'read'                   => self::get_capability( 'use' ),
			],
			// The Form block's `showTemplateSelector: true` opens the Contact /
			// Email Signup picker on first render. The selected template owns
			// starter canvas styles in the editor. We do not template-lock the
			// post; the REST schema gate enforces the "exactly one Form block"
			// invariant on save.
			'template'            => [
				[
					'generateblocks-pro/form',
					[ 'showTemplateSelector' => true ],
				],
			],
		];

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Register the _gb_form post meta.
	 *
	 * Single object holding `config` (user-edited submission settings).
	 * Field structure lives in the form post's saved block content and is
	 * derived server-side when needed.
	 *
	 * sanitize_callback is a class method, NOT a [class, instance-method]
	 * pair — is_callable() fails on the latter and register_meta silently
	 * drops the filter. See Conditions (class-conditions-post-type.php) for
	 * the precedent.
	 */
	public function register_meta() {
		register_post_meta(
			self::POST_TYPE,
			self::META_KEY,
			[
				'single'            => true,
				'type'              => 'object',
				'auth_callback'     => static function ( $allowed, $meta_key, $post_id ) {
					if ( ! self::current_user_can( 'manage' ) ) {
						return false;
					}

					// Restrict _gb_form writes to the form CPT. WP REST already
					// scopes registered meta to its post type, so this is
					// belt-and-suspenders against direct update_post_meta calls
					// on non-form posts. Accept $post_id=0 for the brief window
					// inside REST create_item before the post is persisted.
					if ( $post_id && get_post_type( $post_id ) !== self::POST_TYPE ) {
						return false;
					}

					if ( $post_id && ! current_user_can( 'edit_post', $post_id ) ) {
						return false;
					}

					return true;
				},
				'sanitize_callback' => [ __CLASS__, 'sanitize_meta' ],
				'show_in_rest'      => [
					'schema' => self::rest_schema(),
				],
			]
		);
	}

	/**
	 * REST schema for the _gb_form meta object.
	 *
	 * Explicit shape so the REST layer validates write requests and so
	 * useEntityProp sees the full structure on read. Keep this in sync
	 * with sanitize_meta() and the processor's expected layout.
	 *
	 * @return array
	 */
	private static function rest_schema() {
		// `additionalProperties => true` lets REST serialize the nested
		// config object without silently dropping keys whose stored value
		// diverges from the declared primitive type. Strict validation on
		// writes is still enforced by sanitize_callback.
		return [
			'type'                 => 'object',
			'additionalProperties' => true,
			'properties'           => [
				'config' => [
					'type'                 => 'object',
					'additionalProperties' => true,
				],
			],
		];
	}

	/**
	 * Sanitize _gb_form writes from the REST layer.
	 *
	 * Only `config` is persisted in meta. Field structure comes from the
	 * saved form post content.
	 *
	 * @param mixed $value Incoming meta value.
	 * @return array Sanitized meta value.
	 */
	public static function sanitize_meta( $value ) {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$config_in = isset( $value['config'] ) && is_array( $value['config'] ) ? $value['config'] : [];

		// Coerce to a string-or-empty before casting. Direct DB edits or
		// buggy migrations can leave array/object/null values here; casting
		// `(string) [...]` raises a PHP notice and yields the literal
		// "Array", which is worse than just dropping the value.
		$as_text = static function ( $key ) use ( $config_in ) {
			return isset( $config_in[ $key ] ) && is_scalar( $config_in[ $key ] )
				? (string) $config_in[ $key ]
				: '';
		};

		$action_settings = self::sanitize_action_settings( $config_in['actionSettings'] ?? [] );
		$actions         = self::sanitize_actions_for_settings(
			self::sanitize_action_list( $config_in['actions'] ?? [] ),
			$action_settings
		);

		$config = [
			'actions'                       => $actions,
			'actionSettings'                => $action_settings,
			'formType'                      => self::sanitize_form_type( $config_in['formType'] ?? '' ),
			'emailTo'                       => sanitize_text_field( $as_text( 'emailTo' ) ),
			'emailSubject'                  => sanitize_text_field( $as_text( 'emailSubject' ) ),
			'emailReplyToField'             => sanitize_key( $as_text( 'emailReplyToField' ) ),
			'confirmationEmailField'        => sanitize_key( $as_text( 'confirmationEmailField' ) ),
			'confirmationEmailSubject'      => sanitize_text_field( $as_text( 'confirmationEmailSubject' ) ),
			'confirmationEmailBody'         => sanitize_textarea_field( $as_text( 'confirmationEmailBody' ) ),
			'confirmationEmailReplyToEmail' => sanitize_email( $as_text( 'confirmationEmailReplyToEmail' ) ),
			'confirmationEmailReplyToName'  => sanitize_text_field( $as_text( 'confirmationEmailReplyToName' ) ),
			'useTurnstile'                  => ! empty( $config_in['useTurnstile'] ),
			'storeSubmissions'              => ! empty( $config_in['storeSubmissions'] ),
			'successMessage'                => sanitize_text_field( $as_text( 'successMessage' ) ),
			'errorMessage'                  => sanitize_text_field( $as_text( 'errorMessage' ) ),
			'redirectUrl'                   => esc_url_raw( $as_text( 'redirectUrl' ) ),
		];

		return [
			'config' => $config,
		];
	}

	/**
	 * Sanitize the persisted form-type hint.
	 *
	 * `formType` is a sidebar UX hint (which panel set to render): contact,
	 * email-signup, or contact-email-signup. The actions array is still
	 * authoritative — formType just lets the picker preserve an Email Signup
	 * choice across reloads when the user hasn't connected a service yet (in
	 * which case actions is briefly empty and would otherwise read as "no type").
	 *
	 * @param mixed $value Raw input.
	 * @return string
	 */
	private static function sanitize_form_type( $value ) {
		$allowed = [ 'contact', 'email-signup', 'contact-email-signup' ];
		$value   = is_string( $value ) ? sanitize_key( $value ) : '';

		return in_array( $value, $allowed, true ) ? $value : '';
	}

	/**
	 * Sanitize a list of action identifiers.
	 *
	 * @param mixed $actions Raw input.
	 * @return array
	 */
	private static function sanitize_action_list( $actions ) {
		if ( ! is_array( $actions ) ) {
			return [];
		}

		$out = [];

		foreach ( $actions as $name ) {
			if ( ! is_string( $name ) || '' === $name ) {
				continue;
			}

			$clean = sanitize_key( $name );

			if ( '' !== $clean && ! in_array( $clean, $out, true ) ) {
				$out[] = $clean;
			}
		}

		return $out;
	}

	/**
	 * Sanitize the actionSettings object.
	 *
	 * Per-service settings have different shapes. Coerce to an associative
	 * array keyed by service name; each service's subarray is sanitized as
	 * string values. Action handlers do their own strict validation of
	 * values they care about (e.g. audience IDs, webhook URLs), so this
	 * pass only needs to prevent type confusion and script-tag values.
	 *
	 * @param mixed $settings Raw input.
	 * @return array
	 */
	private static function sanitize_action_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			return [];
		}

		$out = [];

		foreach ( $settings as $service => $subsettings ) {
			if ( ! is_string( $service ) || '' === $service ) {
				continue;
			}

			$service_key = sanitize_key( $service );

			if ( '' === $service_key || ! is_array( $subsettings ) ) {
				continue;
			}

			$clean = [];

			foreach ( $subsettings as $k => $v ) {
				if ( ! is_string( $k ) ) {
					continue;
				}

				// Preserve case — integration handlers read camelCase keys
				// (destinationId, emailField, fieldMap).
				// sanitize_key() would lowercase them and silently break lookups.
				$key = preg_replace( '/[^A-Za-z0-9_]/', '', $k );

				if ( '' === $key ) {
					continue;
				}

				if ( is_string( $v ) ) {
					// URL-looking values stay unmodified here — handlers validate
					// them before use.
					$clean[ $key ] = sanitize_text_field( $v );
				} elseif ( is_numeric( $v ) ) {
					$clean[ $key ] = (string) $v;
				} elseif ( is_bool( $v ) ) {
					$clean[ $key ] = $v;
				} elseif ( is_array( $v ) ) {
					// fieldMap-style nested maps: { fieldName: tag }.
					$nested = [];

					foreach ( $v as $nk => $nv ) {
						if ( is_string( $nk ) && ( is_string( $nv ) || is_numeric( $nv ) ) ) {
							$nested[ sanitize_key( $nk ) ] = sanitize_text_field( (string) $nv );
						}
					}

					$clean[ $key ] = $nested;
				}
			}

			$out[ $service_key ] = $clean;
		}

		return $out;
	}

	/**
	 * Keep action identifiers limited to runnable delivery steps.
	 *
	 * Email signup is runnable only after a provider has been selected. The
	 * formType hint preserves editor intent while actions stays execution-only.
	 *
	 * @param array $actions         Sanitized action names.
	 * @param array $action_settings Sanitized action settings.
	 * @return array
	 */
	private static function sanitize_actions_for_settings( $actions, $action_settings ) {
		$provider = $action_settings['email-signup']['provider'] ?? '';

		if ( is_scalar( $provider ) && '' !== sanitize_key( (string) $provider ) ) {
			return $actions;
		}

		$out = [];

		foreach ( $actions as $action_name ) {
			if ( 'email-signup' !== $action_name ) {
				$out[] = $action_name;
			}
		}

		return $out;
	}

	/**
	 * Default config for a new form.
	 *
	 * Stored in post meta so a fresh form behaves like a contact form
	 * immediately instead of rendering with an empty actions array.
	 *
	 * @return array
	 */
	public static function default_form_config() {
		return [
			'actions'                       => [ 'email' ],
			'actionSettings'                => [],
			'formType'                      => '',
			'emailTo'                       => '',
			'emailSubject'                  => '',
			'emailReplyToField'             => '',
			'confirmationEmailField'        => '',
			'confirmationEmailSubject'      => '',
			'confirmationEmailBody'         => '',
			'confirmationEmailReplyToEmail' => '',
			'confirmationEmailReplyToName'  => '',
			'useTurnstile'                  => false,
			'storeSubmissions'              => false,
			'successMessage'                => __( 'Thanks for your message. We will get back to you soon.', 'generateblocks-pro' ),
			'errorMessage'                  => __( 'Something went wrong. Please try again.', 'generateblocks-pro' ),
			'redirectUrl'                   => '',
		];
	}

	/**
	 * Seed new forms with starter config.
	 *
	 * Covers REST-created forms as well as wp-admin's auto-draft flow.
	 * The block content itself comes from the post-type template; we only
	 * need to guarantee the form config exists server-side so a brand-new
	 * form behaves like a contact form without the user touching the sidebar.
	 *
	 * @param int          $post_id     Post ID.
	 * @param WP_Post      $post        Inserted post object.
	 * @param bool         $update      Whether this is an update.
	 * @param WP_Post|null $post_before Previous post object.
	 */
	public function seed_new_form( $post_id, $post, $update, $post_before ) {
		unset( $post_before );

		if (
			$update
			|| ! $post instanceof WP_Post
			|| self::POST_TYPE !== $post->post_type
			|| wp_is_post_revision( $post_id )
			|| wp_is_post_autosave( $post_id )
		) {
			return;
		}

		$meta         = get_post_meta( $post_id, self::META_KEY, true );
		$meta         = is_array( $meta ) ? $meta : [];
		$needs_config = empty( $meta['config'] ) || ! is_array( $meta['config'] );

		if ( ! $needs_config ) {
			return;
		}

		$meta['config'] = self::default_form_config();

		update_post_meta( $post_id, self::META_KEY, $meta );
	}

	/**
	 * Delete stored submissions when a form is permanently deleted.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function delete_form_submissions( $post_id, $post ) {
		if ( ! $post instanceof WP_Post || self::POST_TYPE !== $post->post_type ) {
			return;
		}

		GenerateBlocks_Pro_Form_Submissions::delete_for_form( $post_id );
	}

	/**
	 * Remove legacy failed-delivery records the next time a form is saved.
	 *
	 * Failed deliveries used to be stored as repeated postmeta rows. The new
	 * submissions table intentionally does not migrate those records, but a
	 * future save of the same form can safely clear stale legacy rows without
	 * scanning the whole site.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function delete_legacy_failed_delivery_meta( $post_id, $post ) {
		if (
			! $post instanceof WP_Post
			|| self::POST_TYPE !== $post->post_type
			|| wp_is_post_revision( $post_id )
			|| wp_is_post_autosave( $post_id )
		) {
			return;
		}

		delete_post_meta( $post_id, self::LEGACY_FAILED_DELIVERY_META_KEY );
	}

	/**
	 * Ensure the submissions table exists after a REST form save enables storage.
	 *
	 * REST post meta is saved before `rest_after_insert_{$post_type}`, so this
	 * hook sees the current `_gb_form.config.storeSubmissions` value. Plain
	 * `save_post` can see stale meta, so it is intentionally not used for this.
	 *
	 * @param WP_Post         $post     Inserted or updated post object.
	 * @param WP_REST_Request $request  REST request.
	 * @param bool            $creating Whether this was a create request.
	 */
	public function maybe_install_submissions_table_after_rest_save( $post, $request, $creating ) {
		unset( $request, $creating );

		if ( ! $post instanceof WP_Post || self::POST_TYPE !== $post->post_type ) {
			return;
		}

		self::maybe_install_submissions_table_for_form( $post->ID );
	}

	/**
	 * Ensure the submissions table exists after direct `_gb_form` meta writes.
	 *
	 * Covers imports, programmatic updates, and any non-REST save path that
	 * updates the form meta outside the block editor.
	 *
	 * @param int    $meta_id    Meta row ID.
	 * @param int    $post_id    Post ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Stored meta value.
	 */
	public function maybe_install_submissions_table_after_meta_save( $meta_id, $post_id, $meta_key, $meta_value ) {
		unset( $meta_id );

		if (
			self::META_KEY !== $meta_key
			|| ! is_array( $meta_value )
			|| empty( $meta_value['config']['storeSubmissions'] )
			|| self::POST_TYPE !== get_post_type( $post_id )
		) {
			return;
		}

		GenerateBlocks_Pro_Form_Submissions::maybe_install( $post_id );
	}

	/**
	 * Create the submissions table when storage is enabled for a form.
	 *
	 * @param int $form_id Form post ID.
	 */
	private static function maybe_install_submissions_table_for_form( $form_id ) {
		$form_id = absint( $form_id );

		if ( ! $form_id ) {
			return;
		}

		$meta = get_post_meta( $form_id, self::META_KEY, true );
		$meta = is_array( $meta ) ? $meta : [];

		if ( empty( $meta['config']['storeSubmissions'] ) ) {
			return;
		}

		GenerateBlocks_Pro_Form_Submissions::maybe_install( $form_id );
	}

	/**
	 * Store referenced form IDs on posts that embed Form Render blocks.
	 *
	 * Mirrors GenerateBlocks' `_generateblocks_reusable_blocks` tracking so a
	 * later save of the referenced object can invalidate generated CSS on host
	 * pages without scanning every post on normal page loads.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function store_form_references( $post_id, $post ) {
		if (
			! $post instanceof WP_Post
			|| wp_is_post_revision( $post_id )
			|| wp_is_post_autosave( $post_id )
			|| ! isset( $post->post_content )
		) {
			return;
		}

		$form_ids = self::collect_form_references( $post->post_content );

		if ( empty( $form_ids ) ) {
			delete_post_meta( $post_id, self::FORM_REFS_META_KEY );
			return;
		}

		update_post_meta( $post_id, self::FORM_REFS_META_KEY, $form_ids );
	}

	/**
	 * Invalidate generated CSS for posts that reference a changed form.
	 *
	 * GenerateBlocks marks CSS files as current in `generateblocks_dynamic_css_posts`.
	 * The host page's CSS includes styles from referenced form content, so saving
	 * the form must unset those host-page entries the same way GB does for
	 * reusable blocks.
	 *
	 * @param int     $post_id Form post ID.
	 * @param WP_Post $post    Form post object.
	 */
	public function invalidate_referencing_post_css( $post_id, $post ) {
		if (
			! $post instanceof WP_Post
			|| self::POST_TYPE !== $post->post_type
			|| wp_is_post_revision( $post_id )
			|| wp_is_post_autosave( $post_id )
		) {
			return;
		}

		$host_ids = self::get_form_reference_host_ids( $post_id );

		if ( empty( $host_ids ) ) {
			return;
		}

		$option = get_option( 'generateblocks_dynamic_css_posts', [] );

		foreach ( $host_ids as $host_id ) {
			unset( $option[ (int) $host_id ] );
		}

		update_option( 'generateblocks_dynamic_css_posts', $option );
	}

	/**
	 * Collect Form Render block references from post content.
	 *
	 * @param string $content Post content.
	 * @return array<int> Unique form IDs.
	 */
	private static function collect_form_references( $content ) {
		if ( ! is_string( $content ) || '' === trim( $content ) || ! function_exists( 'parse_blocks' ) ) {
			return [];
		}

		$refs   = [];
		$blocks = parse_blocks( $content );

		self::collect_form_references_from_blocks( $blocks, $refs );

		$refs = array_values( array_unique( array_filter( array_map( 'absint', $refs ) ) ) );
		sort( $refs );

		return $refs;
	}

	/**
	 * Walk parsed blocks and collect Form Render references.
	 *
	 * @param array $blocks Parsed blocks.
	 * @param array $refs   Reference accumulator.
	 */
	private static function collect_form_references_from_blocks( $blocks, array &$refs ) {
		foreach ( $blocks as $block ) {
			if ( 'generateblocks-pro/form-render' === ( $block['blockName'] ?? '' ) ) {
				$form_id = isset( $block['attrs']['formId'] ) ? absint( $block['attrs']['formId'] ) : 0;

				if ( $form_id ) {
					$refs[] = $form_id;
				}
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				self::collect_form_references_from_blocks( $block['innerBlocks'], $refs );
			}
		}
	}

	/**
	 * Get post IDs whose generated CSS should refresh after a form save.
	 *
	 * @param int $form_id Form post ID.
	 * @return array<int> Host post IDs.
	 */
	private static function get_form_reference_host_ids( $form_id ) {
		global $wpdb;

		if ( ! $wpdb ) {
			return [];
		}

		$tracked_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
				self::FORM_REFS_META_KEY
			)
		);
		$direct_ids = [];

		foreach ( (array) $tracked_rows as $row ) {
			if ( self::meta_value_references_id( $row->meta_value ?? '', $form_id ) ) {
				$direct_ids[] = absint( $row->post_id ?? 0 );
			}
		}

		$direct_ids             = array_values( array_unique( array_filter( array_map( 'absint', (array) $direct_ids ) ) ) );
		$host_ids               = [];
		$has_reusable_reference = false;

		foreach ( $direct_ids as $id ) {
			if ( 'wp_block' === get_post_type( $id ) ) {
				$has_reusable_reference = true;
				continue;
			}

			$host_ids[] = $id;
		}

		if ( $has_reusable_reference ) {
			// Match GenerateBlocks' reusable-block invalidation strategy: when a
			// referenced object inside a wp_block changes, invalidate every post
			// that uses reusable blocks. This is intentionally broad so nested
			// reusable chains are covered without graph traversal.
			$reusable_host_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s",
					'_generateblocks_reusable_blocks'
				)
			);

			$host_ids = array_merge( $host_ids, (array) $reusable_host_ids );
		}

		return array_values( array_unique( array_filter( array_map( 'absint', $host_ids ) ) ) );
	}

	/**
	 * Search for posts that embed a given form via the Form Render block.
	 *
	 * Mirrors the shape returned by `search_condition_usage()` so the dashboard
	 * delete-flow and the standalone usage modal can reuse the same React
	 * components (`UsageModalBody`).
	 *
	 * Reverse index: every host page maintains a `_generateblocks_form_refs`
	 * post-meta list of embedded form IDs (populated by `store_form_references`
	 * on save_post). That lets us answer "where is this form used?" without
	 * scanning post_content.
	 *
	 * @param int $form_id Form post ID to look up.
	 * @param int $limit   Maximum number of items to return (default: 50).
	 * @return array {
	 *     @type array $usage       Map of usage handler key => { label, items, has_more }.
	 *     @type int   $total       Total items across handlers (capped at limit).
	 *     @type bool  $has_more    Whether more results exist beyond the cap.
	 *     @type bool  $limited     Whether the scan was capped before completing.
	 *     @type array $limitations Per-limit metadata for UI surfacing.
	 * }
	 */
	public static function search_form_usage( $form_id, $limit = 50 ) {
		$form_id = absint( $form_id );
		$limit   = max( 1, absint( $limit ) );

		$empty_result = [
			'usage'       => [],
			'total'       => 0,
			'has_more'    => false,
			'limited'     => false,
			'limitations' => [],
		];

		if ( ! $form_id ) {
			return $empty_result;
		}

		global $wpdb;

		if ( ! $wpdb ) {
			return $empty_result;
		}

		// Cap the number of postmeta rows we scan so this stays cheap on
		// sites with very large content. Reuses the same filter the
		// Conditions usage scan uses so admins can raise the cap once for
		// both.
		$max_rows = (int) apply_filters( 'generateblocks_usage_search_max_posts', 5000 );
		$max_rows = max( 1, $max_rows );

		// Total tracked rows — informs the "scan was limited" notice.
		$total_rows = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s",
				self::FORM_REFS_META_KEY
			)
		);

		// Pull the most-recently-modified host pages first so the warning
		// surfaces likely-current content before we hit the cap. Sorting
		// by post_modified is more useful than postmeta_id at delete time.
		$tracked_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pm.post_id, pm.meta_value
				FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE pm.meta_key = %s
				ORDER BY p.post_modified DESC
				LIMIT %d",
				self::FORM_REFS_META_KEY,
				$max_rows
			)
		);

		$matching_ids = [];

		foreach ( (array) $tracked_rows as $row ) {
			if ( self::meta_value_references_id( $row->meta_value ?? '', $form_id ) ) {
				$matching_ids[] = absint( $row->post_id ?? 0 );
			}
		}

		$matching_ids = array_values( array_unique( array_filter( $matching_ids ) ) );

		if ( empty( $matching_ids ) ) {
			$result = $empty_result;

			// Even with no hits, surface the limitation so the UI can warn
			// when the scan didn't cover every tracked post.
			if ( $total_rows > $max_rows ) {
				$result['limited']                          = true;
				$result['limitations']['recent_post_scan'] = [
					'max_posts'     => $max_rows,
					'scanned_posts' => $max_rows,
					'total_posts'   => $total_rows,
				];
			}

			return $result;
		}

		// Query one more than `limit` to detect overflow.
		$query_limit  = $limit + 1;
		$ids_to_fetch = array_slice( $matching_ids, 0, $query_limit );

		// `save_post` fires for every post type, so any CPT — including
		// CPTs registered with `exclude_from_search => true` (popups,
		// landing pages, custom builder content) — can land in
		// `_generateblocks_form_refs`. `'any'` and the search-visibility
		// allowlist would silently drop those, turning the in-use guard
		// into a no-op. `post__in` is already the authoritative filter
		// (we only fetch IDs we matched in postmeta), so query every
		// registered post type and let the IN clause do the narrowing.
		$searchable_types = array_values( (array) get_post_types() );

		if ( empty( $searchable_types ) ) {
			$searchable_types = [ 'post', 'page' ];
		}

		$posts = get_posts(
			[
				'post_type'              => $searchable_types,
				'post_status'            => [ 'publish', 'private', 'draft', 'pending', 'future' ],
				'post__in'               => $ids_to_fetch,
				'orderby'                => 'post__in',
				'posts_per_page'         => $query_limit,
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'suppress_filters'       => true,
			]
		);

		$has_more = count( $matching_ids ) > $limit;
		$items    = [];

		foreach ( $posts as $post ) {
			if ( count( $items ) >= $limit ) {
				$has_more = true;
				break;
			}

			$post_type_object = get_post_type_object( $post->post_type );
			$type_label       = $post_type_object ? $post_type_object->labels->singular_name : $post->post_type;
			$is_public        = $post_type_object && ( $post_type_object->public || $post_type_object->publicly_queryable );

			if ( $post->post_title ) {
				$fallback_title = $post->post_title;
			} else {
				$fallback_title = sprintf(
					/* translators: %1$s: post type label, %2$d: post ID */
					__( '%1$s #%2$d', 'generateblocks-pro' ),
					$type_label,
					$post->ID
				);
			}

			$items[] = [
				'id'         => absint( $post->ID ),
				'title'      => $fallback_title,
				'type'       => $post->post_type,
				'type_label' => $type_label,
				'status'     => $post->post_status,
				'edit_url'   => get_edit_post_link( $post->ID, 'raw' ),
				'view_url'   => ( 'publish' === $post->post_status && $is_public ) ? get_permalink( $post->ID ) : null,
				'usage_type' => 'form_render_block',
			];
		}

		$result = [
			'usage'       => [],
			'total'       => count( $items ),
			'has_more'    => $has_more,
			'limited'     => false,
			'limitations' => [],
		];

		if ( ! empty( $items ) ) {
			$result['usage']['form_render_block'] = [
				'label'    => __( 'Form embed', 'generateblocks-pro' ),
				'items'    => $items,
				'has_more' => $has_more,
			];
		}

		if ( $total_rows > $max_rows ) {
			$result['limited']                          = true;
			$result['limitations']['recent_post_scan'] = [
				'max_posts'     => $max_rows,
				'scanned_posts' => $max_rows,
				'total_posts'   => $total_rows,
			];
		}

		return $result;
	}

	/**
	 * Check whether a stored meta value references a specific ID.
	 *
	 * @param mixed $meta_value Stored meta value, maybe serialized.
	 * @param int   $target_id  ID to find.
	 * @return bool
	 */
	private static function meta_value_references_id( $meta_value, $target_id ) {
		return in_array( absint( $target_id ), self::normalize_meta_id_list( $meta_value ), true );
	}

	/**
	 * Normalize a meta value that stores an ID list.
	 *
	 * WordPress serializes array meta values in postmeta. Tests may pass the
	 * raw array directly, so support both shapes.
	 *
	 * @param mixed $value Stored meta value.
	 * @return array<int> Normalized IDs.
	 */
	private static function normalize_meta_id_list( $value ) {
		if ( is_string( $value ) && function_exists( 'maybe_unserialize' ) ) {
			$value = maybe_unserialize( $value );
		}

		if ( is_array( $value ) ) {
			return array_values( array_unique( array_filter( array_map( 'absint', $value ) ) ) );
		}

		if ( is_numeric( $value ) ) {
			$value = absint( $value );

			return $value ? [ $value ] : [];
		}

		return [];
	}

	/**
	 * Reject REST writes that would publish a form with invalid form structure.
	 *
	 * The processor returns `form_schema_empty` before the failover-store
	 * handoff, so a published form with no fields silently drops every real
	 * submission. Empty actions are allowed: they represent an incomplete
	 * configuration and return a public-safe error at submission time.
	 *
	 *   - `form_schema_empty` — no fields in the form post_content
	 *
	 * Drafts can stay incomplete while the author is mid-build; only the
	 * publish/update transition is gated. Surfacing each as a 400 from the
	 * REST insert filter lets the Gutenberg editor render the message as a
	 * snackbar notice — the Publish button stays clickable so the author
	 * gets clear cause-and-effect feedback instead of a disabled UI.
	 *
	 * @param stdClass        $prepared_post Prepared post object.
	 * @param WP_REST_Request $request       The REST request.
	 * @return stdClass|WP_Error
	 */
	public function gate_publish_completeness( $prepared_post, $request ) {
		if ( ! is_object( $prepared_post ) ) {
			return $prepared_post;
		}

		// Only enforce when the resulting status would be `publish`. The
		// status filter on auto-draft → draft transitions usually leaves
		// post_status unset on the prepared object, so we look up the
		// existing post when the request didn't supply a new status.
		$resulting_status = isset( $prepared_post->post_status ) ? $prepared_post->post_status : '';

		if ( '' === $resulting_status && ! empty( $prepared_post->ID ) ) {
			$existing         = get_post( $prepared_post->ID );
			$resulting_status = $existing ? $existing->post_status : '';
		}

		if ( 'publish' !== $resulting_status ) {
			return $prepared_post;
		}

		// 1. Fields. The schema builder is authoritative for what counts as a
		// usable field — running the same parse the public submission path
		// uses keeps "looks editable but won't submit" forms out of publish.
		// Use the *prepared* content when the request includes one, falling
		// back to existing content for status-only updates.
		$resulting_content = isset( $prepared_post->post_content ) ? $prepared_post->post_content : null;

		if ( null === $resulting_content && ! empty( $prepared_post->ID ) ) {
			$existing          = $existing ?? get_post( $prepared_post->ID );
			$resulting_content = $existing ? $existing->post_content : '';
		}

		$schema = null;

		if ( class_exists( 'GenerateBlocks_Pro_Form_Schema_Builder' ) ) {
			$schema = GenerateBlocks_Pro_Form_Schema_Builder::get_instance()
				->build_schema_from_content(
					(string) $resulting_content,
					[ 'require_field_names' => true ]
				);

			if ( is_wp_error( $schema ) ) {
				return new WP_Error(
					$schema->get_error_code(),
					$schema->get_error_message(),
					[ 'status' => 400 ]
				);
			}

			if ( is_array( $schema ) && empty( $schema ) ) {
				return new WP_Error(
					'gb_form_no_fields',
					__( 'This form has no fields. Add at least one field before publishing.', 'generateblocks-pro' ),
					[ 'status' => 400 ]
				);
			}
		}

		// 2. Actions. Sanitize the incoming meta through the same pipeline
		// the save path uses so validation runs against the final runnable
		// action list. Empty lists are allowed and fail publicly at submit time.
		$incoming_meta = $request->get_param( 'meta' );
		$actions       = null;
		$config        = [];

		if ( is_array( $incoming_meta ) && isset( $incoming_meta[ self::META_KEY ] ) ) {
			$sanitized = self::sanitize_meta( $incoming_meta[ self::META_KEY ] );
			$config    = $sanitized['config'] ?? [];
			$actions   = $config['actions'] ?? [];
		} elseif ( ! empty( $prepared_post->ID ) ) {
			// Existing meta is already post-sanitize, so it can be trusted.
			$existing_meta = get_post_meta( $prepared_post->ID, self::META_KEY, true );

			if ( is_array( $existing_meta ) && isset( $existing_meta['config']['actions'] ) ) {
				$config  = $existing_meta['config'];
				$actions = $config['actions'];
			}
		}

		$actions = is_array( $actions ) ? $actions : [];

		$has_primary_action = false;

		foreach ( $actions as $action_name ) {
			if ( is_string( $action_name ) && 'confirmation-email' !== sanitize_key( $action_name ) ) {
				$has_primary_action = true;
				break;
			}
		}

		if ( in_array( 'webhook', $actions, true ) && class_exists( 'GenerateBlocks_Pro_Form_Action_Webhook' ) ) {
			$settings_check = GenerateBlocks_Pro_Form_Action_Webhook::validate_settings( $config );

			if ( is_wp_error( $settings_check ) ) {
				return $settings_check;
			}
		}

		if (
			! $has_primary_action
			&& in_array( 'confirmation-email', $actions, true )
			&& class_exists( 'GenerateBlocks_Pro_Form_Action_Confirmation_Email' )
		) {
			$settings_check = GenerateBlocks_Pro_Form_Action_Confirmation_Email::validate_settings(
				$config,
				is_array( $schema ) ? $schema : null
			);

			if ( is_wp_error( $settings_check ) ) {
				return $settings_check;
			}
		}

		// Email signup forms can publish before a provider is selected. Once
		// email-signup delivery is runnable, provider/destination readiness is
		// intentionally validated at submission time.
		return $prepared_post;
	}

	/**
	 * Strip manager-only form metadata from REST responses for non-manager reads.
	 *
	 * `auth_callback` on register_post_meta only gates writes. Meta reads
	 * follow the post read cap, which is `use` (default `edit_posts`) —
	 * meaning any editor could read `emailTo`, webhook URLs, and audience IDs
	 * off every form. That config isn't secret the way an API key is, but
	 * site owners don't generally expect contributors to see where form
	 * submissions get delivered. Scope the meta read to the same `manage`
	 * cap that gates writes. The derived `_gb_form_meta` REST field is also
	 * manager-only; it exposes stored-submission and delivery-health state for
	 * the dashboard and is not needed by block selectors listing published forms.
	 *
	 * @param WP_REST_Response $response Prepared response.
	 * @param WP_Post          $post     Post object.
	 * @param WP_REST_Request  $request  REST request.
	 * @return WP_REST_Response
	 */
	public function gate_meta_read( $response, $post, $request ) {
		unset( $post, $request );

		if ( self::current_user_can( 'manage' ) ) {
			return $response;
		}

		$data = $response->get_data();

		if ( ! is_array( $data ) ) {
			return $response;
		}

		if ( isset( $data['meta'] ) && is_array( $data['meta'] ) && array_key_exists( self::META_KEY, $data['meta'] ) ) {
			unset( $data['meta'][ self::META_KEY ] );
		}

		if ( array_key_exists( '_gb_form_meta', $data ) ) {
			unset( $data['_gb_form_meta'] );
		}

		$response->set_data( $data );

		return $response;
	}

	/**
	 * Restrict REST queries on `gblocks_form`.
	 *
	 * Users with the `use` cap may list published forms for the block
	 * dropdown. Draft/private reads remain for managers only.
	 *
	 * @param array           $args    Query args.
	 * @param WP_REST_Request $request REST request.
	 * @return array
	 */
	public function restrict_rest_query( $args, $request ) {
		unset( $request );

		if ( ! self::current_user_can( 'use' ) ) {
			// Force an impossible query so the response is empty without 403.
			$args['post__in'] = [ 0 ];

			return $args;
		}

		if ( ! self::current_user_can( 'manage' ) ) {
			$args['post_status'] = [ 'publish' ];
		}

		return $args;
	}

	/**
	 * Redirect the default post-type list view to the Forms dashboard.
	 *
	 * Matches Overlays. `edit.php?post_type=gblocks_form` is the URL WP
	 * generates for the admin menu; we surface a richer React dashboard
	 * instead. The edit.php page itself isn't useful — the row actions
	 * and sidebar live inside the Gutenberg editor for each form.
	 */
	public function redirect_admin_view() {
		global $pagenow;

		if ( 'edit.php' !== $pagenow ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['post_type'] ) || self::POST_TYPE !== $_GET['post_type'] ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=generateblocks-forms' ) );
		exit;
	}
}
