<?php
/**
 * Conditions Dashboard Admin Page
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GenerateBlocks_Pro_Conditions_Dashboard
 */
class GenerateBlocks_Pro_Conditions_Dashboard {
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
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'generateblocks_dashboard_tabs', [ $this, 'add_tab' ] );
		add_filter( 'generateblocks_dashboard_screens', [ $this, 'add_to_dashboard_pages' ] );
	}

	/**
	 * Add admin menu page.
	 */
	public function add_admin_menu() {
		// Get the required capability.
		$capability = GenerateBlocks_Pro_Conditions::get_conditions_capability( 'manage' );

		// Only add menu if user has permission.
		if ( ! current_user_can( $capability ) ) {
			return;
		}

		add_submenu_page(
			'generateblocks',
			__( 'Conditions', 'generateblocks-pro' ),
			__( 'Conditions', 'generateblocks-pro' ),
			$capability,
			'generateblocks-conditions',
			[ $this, 'render_dashboard' ],
			4
		);
	}

	/**
	 * Render the dashboard page.
	 */
	public function render_dashboard() {
		// Double-check permission before rendering.
		if ( ! GenerateBlocks_Pro_Conditions::current_user_can_use_conditions( 'manage' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'generateblocks-pro' ) );
		}
		?>
		<div class="wrap">
			<div id="gb-conditions-dashboard"></div>
		</div>
		<?php
	}

	/**
	 * Enqueue scripts for the dashboard.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_scripts( $hook_suffix ) {
		if ( 'generateblocks_page_generateblocks-conditions' !== $hook_suffix ) {
			return;
		}

		$assets = generateblocks_pro_get_enqueue_assets( 'conditions-dashboard' );

		wp_enqueue_script(
			'gb-conditions-dashboard',
			GENERATEBLOCKS_PRO_DIR_URL . 'dist/conditions-dashboard.js',
			$assets['dependencies'],
			$assets['version'],
			true
		);

		wp_enqueue_style(
			'gb-conditions-dashboard',
			GENERATEBLOCKS_PRO_DIR_URL . 'dist/conditions-dashboard.css',
			[ 'wp-components', 'generateblocks-pro-dashboard-table' ],
			GENERATEBLOCKS_PRO_VERSION
		);

		wp_localize_script(
			'gb-conditions-dashboard',
			'gbConditionsDashboard',
			[
				'apiUrl' => rest_url( 'wp/v2/' ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
			]
		);
	}

	/**
	 * Add a Local Templates tab to the GB Dashboard tabs.
	 *
	 * @param array $tabs The existing tabs.
	 */
	public function add_tab( $tabs ) {
		$screen = get_current_screen();

		$tabs['conditions'] = array(
			'name' => __( 'Conditions', 'generateblocks-pro' ),
			'url' => admin_url( 'admin.php?page=generateblocks-conditions' ),
			'class' => 'generateblocks_page_generateblocks-conditions' === $screen->id ? 'active' : '',
		);

		return $tabs;
	}

	/**
	 * Add to our Dashboard pages.
	 *
	 * @since 1.0.0
	 * @param array $pages The existing pages.
	 */
	public function add_to_dashboard_pages( $pages ) {
		$pages[] = 'generateblocks_page_generateblocks-conditions';

		return $pages;
	}
}

GenerateBlocks_Pro_Conditions_Dashboard::get_instance();
