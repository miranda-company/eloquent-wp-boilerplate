<?php
/**
 * Handle post types in GenerateBlocks Pro.
 *
 * @package GenerateBlocks Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The Local templates class.
 */
class GenerateBlocks_Pro_Local_Templates {
	/**
	 * Instance.
	 *
	 * @access private
	 * @var object Instance
	 */
	private static $instance;

	/**
	 * Initiator.
	 *
	 * @return object initialized object of class.
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initiate class.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_custom_post_types' ) );

		if ( ! is_admin() ) {
			return;
		}

		$admin_settings = wp_parse_args(
			get_option( 'generateblocks_admin', array() ),
			generateblocks_pro_get_admin_option_defaults()
		);

		if ( ! $admin_settings['enable_local_templates'] ) {
			return;
		}

		add_action( 'admin_init', array( $this, 'maybe_redirect_empty_legacy_templates' ) );
		add_action( 'admin_head', array( $this, 'fix_menu' ) );
		add_action( 'admin_footer', [ $this, 'add_scripts' ] );
	}

	/**
	 * Register custom post type.
	 */
	public function add_custom_post_types() {
		register_post_type(
			'gblocks_templates',
			array(
				'labels'             => array(
					'name'                => _x( 'Local Patterns (Legacy)', 'Post Type General Name', 'generateblocks-pro' ),
					'singular_name'       => _x( 'Local Pattern', 'Post Type Singular Name', 'generateblocks-pro' ),
					'menu_name'           => __( 'Local Patterns', 'generateblocks-pro' ),
					'parent_item_colon'   => __( 'Parent Local Pattern', 'generateblocks-pro' ),
					'all_items'           => __( 'Local Patterns', 'generateblocks-pro' ),
					'view_item'           => __( 'View Local Pattern', 'generateblocks-pro' ),
					'add_new_item'        => __( 'Add New Local Pattern', 'generateblocks-pro' ),
					'add_new'             => __( 'Add New', 'generateblocks-pro' ),
					'edit_item'           => __( 'Edit Local Pattern', 'generateblocks-pro' ),
					'update_item'         => __( 'Update Local Pattern', 'generateblocks-pro' ),
					'search_items'        => __( 'Search Local Pattern', 'generateblocks-pro' ),
					'not_found'           => __( 'Not Found', 'generateblocks-pro' ),
					'not_found_in_trash'  => __( 'Not found in Trash', 'generateblocks-pro' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'has_archive'         => false,
				'show_ui'             => true,
				'exclude_from_search' => true,
				'show_in_nav_menus'   => false,
				'rewrite'             => false,
				'hierarchical'        => false,
				'show_in_menu'        => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => $this->should_show_in_rest(),
				'capabilities'        => array(
					'create_posts'        => 'do_not_allow',
					'publish_posts'       => 'manage_options',
					'edit_posts'          => 'manage_options',
					'edit_others_posts'   => 'manage_options',
					'delete_posts'        => 'manage_options',
					'delete_others_posts' => 'manage_options',
					'read_private_posts'  => 'manage_options',
					'edit_post'           => 'manage_options',
					'delete_post'         => 'manage_options',
					'read_post'           => 'manage_options',
				),
				'supports'            => array(
					'title',
					'editor',
					'thumbnail',
				),
			)
		);
	}

	/**
	 * Determine if the legacy CPT should be exposed through core REST routes.
	 *
	 * @return bool
	 */
	private function should_show_in_rest() {
		if ( is_admin() ) {
			return true;
		}

		if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
			return current_user_can( 'manage_options' );
		}

		return false;
	}

	/**
	 * Redirect the empty legacy CPT list back to the dashboard.
	 */
	public function maybe_redirect_empty_legacy_templates() {
		global $pagenow;

		if ( ! in_array( $pagenow, array( 'edit.php', 'post-new.php' ), true ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Redirecting a read-only admin URL.
		$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';

		if ( 'gblocks_templates' !== $post_type ) {
			return;
		}

		if ( generateblocks_pro_has_legacy_patterns() ) {
			return;
		}

		wp_safe_redirect( admin_url() );
		exit;
	}

	/**
	 * Trick the WordPress menu to highlight our Patterns menu item
	 * when we're dealing with collections or categories.
	 */
	public function fix_menu() {
		global $parent_file, $submenu_file, $post_type;

		$screen = get_current_screen();

		if ( 'edit-gblocks_templates' === $screen->id ) {
			$parent_file = 'generateblocks'; // phpcs:ignore -- Override necessary.
			$submenu_file = 'edit.php?post_type=wp_block'; // phpcs:ignore -- Override necessary.
		}
	}

	/**
	 * Add a link to the new patterns.
	 */
	public function add_scripts() {
		$screen = get_current_screen();

		if ( 'edit-gblocks_templates' !== $screen->id ) {
			return;
		}
		?>
		<script>
			document.addEventListener( 'DOMContentLoaded', () => {
				const button = document.querySelector( '.page-title-action' );
				const heading = document.querySelector( '.wrap .wp-heading-inline' );
				const buttonParent = button ? button.parentNode : null;
				const headingParent = heading ? heading.parentNode : null;

				if ( ! buttonParent && ! headingParent ) {
					return;
				}

				if ( button ) {
					button.style.pointerEvents = 'none';
					button.style.opacity = '0.5';
				}

				const newPatternsButton = document.createElement( 'a' );
				newPatternsButton.classList.add( 'page-title-action' );
				newPatternsButton.href = '<?php echo esc_url( admin_url( 'edit.php?post_type=wp_block' ) ); ?>';
				newPatternsButton.textContent = '<?php echo esc_html( __( 'New Pattern Library', 'generateblocks-pro' ) ); ?>';

				if ( buttonParent ) {
					buttonParent.insertBefore( newPatternsButton, button );
					return;
				}

				headingParent.insertBefore( newPatternsButton, heading.nextSibling );
			} );
		</script>
		<?php
	}
}

GenerateBlocks_Pro_Local_Templates::get_instance();
