<?php
/**
 * Menu Item Conditions functionality.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class GenerateBlocks_Pro_Menu_Item_Conditions
 */
class GenerateBlocks_Pro_Menu_Item_Conditions extends GenerateBlocks_Pro_Singleton {

	/**
	 * Cache for evaluated conditions to avoid repeated checks.
	 *
	 * @var array
	 */
	private $condition_cache = [];

	/**
	 * Initialize the class.
	 */
	public function init() {
		// Don't initialize if block conditions are disabled.
		if ( ! generateblocks_pro_block_conditions_enabled() ) {
			return;
		}

		// Register post meta for menu items.
		add_action( 'init', [ $this, 'register_post_meta' ] );

		// Add fields to menu item editor.
		add_action( 'wp_nav_menu_item_custom_fields', [ $this, 'add_condition_fields' ], 20, 5 );

		// Save menu item conditions.
		add_action( 'wp_update_nav_menu_item', [ $this, 'save_menu_item_conditions' ], 10, 2 );

		// Filter menu items based on conditions.
		add_filter( 'wp_nav_menu_objects', [ $this, 'filter_menu_items_by_conditions' ], 10, 2 );

		// Add to condition usage tracking.
		add_filter( 'generateblocks_condition_usage_handlers', [ $this, 'add_menu_usage_handler' ], 10, 2 );

		// Add admin scripts for dynamic UI.
		add_action( 'admin_enqueue_scripts', [ $this, 'add_admin_scripts' ] );

		// AJAX handler for condition search.
		add_action( 'wp_ajax_gb_search_conditions', [ $this, 'ajax_search_conditions' ] );

		// AJAX handler for loading more conditions.
		add_action( 'wp_ajax_gb_load_more_conditions', [ $this, 'ajax_load_more_conditions' ] );
	}

	/**
	 * Register the post meta.
	 */
	public function register_post_meta() {
		register_post_meta(
			'nav_menu_item',
			'_gb_menu_condition',
			[
				'show_in_rest' => true,
				'single'       => true,
				'type'         => 'string',
			]
		);

		register_post_meta(
			'nav_menu_item',
			'_gb_menu_condition_invert',
			[
				'show_in_rest' => true,
				'single'       => true,
				'type'         => 'boolean',
			]
		);
	}

	/**
	 * Get the conditions limit per page.
	 */
	private function get_conditions_limit() {
		return apply_filters( 'generateblocks_menu_conditions_limit', 100 );
	}

	/**
	 * Add condition fields to menu items.
	 *
	 * @param string        $item_id           Menu item ID as a numeric string.
	 * @param WP_Post       $menu_item         Menu item data object.
	 * @param int           $depth             Depth of menu item. Used for padding.
	 * @param stdClass|null $args              An object of menu item arguments.
	 * @param int           $current_object_id Nav menu ID.
	 */
	public function add_condition_fields( $item_id, $menu_item, $depth, $args, $current_object_id ) {
		$condition_id = get_post_meta( $item_id, '_gb_menu_condition', true );
		$invert = get_post_meta( $item_id, '_gb_menu_condition_invert', true );

		// Get conditions for dropdown.
		static $conditions_cache = null;

		if ( null === $conditions_cache ) {
			// Get the most recent conditions.
			$conditions_cache = get_posts(
				[
					'post_type' => 'gblocks_condition',
					'posts_per_page' => $this->get_conditions_limit(),
					'orderby' => 'date',
					'order' => 'DESC',
					'post_status' => 'publish',
					'suppress_filters' => false,
					'no_found_rows' => true,
				]
			);
		}

		$conditions = $conditions_cache;

		// Always include the currently selected condition if it's not in the list.
		if ( $condition_id && 'gblocks_condition' === get_post_type( $condition_id ) ) {
			$found = false;
			foreach ( $conditions as $condition ) {
				if ( $condition->ID === $condition_id ) {
					$found = true;
					break;
				}
			}

			if ( ! $found ) {
				$selected_condition = get_post( $condition_id );
				if ( $selected_condition ) {
					// Add it to the list for this menu item only.
					$conditions = array_merge( [ $selected_condition ], $conditions );
				}
			}
		}

		// Check if there are more conditions than we're showing.
		$total_conditions = wp_count_posts( 'gblocks_condition' )->publish;
		$has_more = $total_conditions > count( $conditions_cache );
		?>
		<p class="field-gb-menu-condition description description-wide">
			<label for="gb-menu-condition-<?php echo esc_attr( $item_id ); ?>">
				<?php esc_html_e( 'Display Condition', 'generateblocks-pro' ); ?>
			</label>
			<div class="gb-menu-condition-wrapper" style="display: flex; align-items: center; gap: 10px;">
				<select
					name="gb-menu-condition[<?php echo esc_attr( $item_id ); ?>]"
					id="gb-menu-condition-<?php echo esc_attr( $item_id ); ?>"
					class="gb-menu-condition-select"
					style="flex: 1;"
					data-item-id="<?php echo esc_attr( $item_id ); ?>"
					data-page="1"
				>
					<option value=""><?php esc_html_e( 'No condition', 'generateblocks-pro' ); ?></option>
					<?php foreach ( $conditions as $condition ) : ?>
						<option value="<?php echo esc_attr( $condition->ID ); ?>" <?php selected( $condition_id, $condition->ID ); ?>>
							<?php echo esc_html( $condition->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<?php if ( $has_more ) : ?>
					<button
						type="button"
						class="button gb-load-more-conditions"
						data-item-id="<?php echo esc_attr( $item_id ); ?>"
					>
						<?php esc_html_e( 'Load More', 'generateblocks-pro' ); ?>
					</button>
				<?php endif; ?>
			</div>
			<span class="description" style="display: block; margin-top: 5px;">
				<?php esc_html_e( 'Choose a condition to control when this menu item appears.', 'generateblocks-pro' ); ?>
			</span>
		</p>

		<p class="field-gb-menu-condition-invert description description-wide" style="<?php echo $condition_id ? '' : 'display: none;'; ?>">
			<label>
				<input
					type="checkbox"
					name="gb-menu-condition-invert[<?php echo esc_attr( $item_id ); ?>]"
					value="1"
					<?php checked( $invert, '1' ); ?>
				/>
				<?php esc_html_e( 'Invert condition', 'generateblocks-pro' ); ?>
			</label>
			<span class="description" style="display: block"><?php esc_html_e( 'Hide the menu item when the condition is true instead of false.', 'generateblocks-pro' ); ?></span>
		</p>

		<?php wp_nonce_field( 'update-menu-item-condition', 'menu-item-condition-nonce' ); ?>
		<?php
	}

	/**
	 * Save the menu item conditions.
	 *
	 * @param int $menu_id      The menu ID.
	 * @param int $menu_item_id The menu item ID.
	 * @return mixed
	 */
	public function save_menu_item_conditions( $menu_id, $menu_item_id ) {
		// Verify nonce.
		if (
			! isset( $_POST['menu-item-condition-nonce'] )
			|| ! wp_verify_nonce( $_POST['menu-item-condition-nonce'], 'update-menu-item-condition' )
		) {
			return $menu_id;
		}

		// Save condition from dropdown.
		$condition_id = isset( $_POST['gb-menu-condition'][ $menu_item_id ] )
			? sanitize_text_field( $_POST['gb-menu-condition'][ $menu_item_id ] )
			: '';

		if ( $condition_id ) {
			update_post_meta( $menu_item_id, '_gb_menu_condition', $condition_id );
		} else {
			delete_post_meta( $menu_item_id, '_gb_menu_condition' );
		}

		// Save invert setting.
		$invert = isset( $_POST['gb-menu-condition-invert'][ $menu_item_id ] ) ? '1' : '0';

		if ( $condition_id && '1' === $invert ) {
			update_post_meta( $menu_item_id, '_gb_menu_condition_invert', '1' );
		} else {
			delete_post_meta( $menu_item_id, '_gb_menu_condition_invert' );
		}

		return $menu_id;
	}

	/**
	 * Filter menu items based on conditions.
	 *
	 * @param array    $sorted_menu_items The menu items, sorted by menu order.
	 * @param stdClass $args              The menu arguments.
	 * @return array Filtered menu items.
	 */
	public function filter_menu_items_by_conditions( $sorted_menu_items, $args ) {
		if ( empty( $sorted_menu_items ) ) {
			return $sorted_menu_items;
		}

		$removed_items = [];

		// Build list of all items that should be removed (condition fails or parent removed).
		// Note: WordPress guarantees menu items are in hierarchical order (parents before children).
		foreach ( $sorted_menu_items as $item ) {
			$item_id = (int) $item->ID;

			// Skip if already marked for removal.
			if ( in_array( $item_id, $removed_items, true ) ) {
				continue;
			}

			// Check if parent was removed.
			$parent_id = ! empty( $item->menu_item_parent ) ? (int) $item->menu_item_parent : 0;
			if ( $parent_id && in_array( $parent_id, $removed_items, true ) ) {
				$removed_items[] = $item_id;
				continue;
			}

			// Check condition.
			$condition_id = get_post_meta( $item_id, '_gb_menu_condition', true );
			if ( $condition_id && ! $this->should_show_menu_item( $item_id, $condition_id ) ) {
				$removed_items[] = $item_id;
			}
		}

		// If no items were removed, return the original array.
		if ( empty( $removed_items ) ) {
			return $sorted_menu_items;
		}

		// Filter out removed items.
		$final_items = [];
		foreach ( $sorted_menu_items as $item ) {
			if ( ! in_array( (int) $item->ID, $removed_items, true ) ) {
				$final_items[] = $item;
			}
		}

		return $final_items;
	}

	/**
	 * Check if a menu item should be shown based on its condition.
	 *
	 * @param int    $menu_item_id The menu item ID.
	 * @param string $condition_id The condition ID.
	 * @return bool Whether to show the menu item.
	 */
	private function should_show_menu_item( $menu_item_id, $condition_id ) {
		$condition_id = absint( $condition_id );

		if ( ! $condition_id ) {
			return true;
		}

		$invert_condition = get_post_meta( $menu_item_id, '_gb_menu_condition_invert', true ) === '1';

		// Check cache first to avoid repeated evaluations.
		$cache_key = $condition_id . '_' . ( $invert_condition ? '1' : '0' );

		if ( isset( $this->condition_cache[ $cache_key ] ) ) {
			return $this->condition_cache[ $cache_key ];
		}

		// Default to showing the menu item.
		$show = true;

		// Check if the condition post exists and is published.
		$condition_post = get_post( $condition_id );

		if ( $condition_post && 'publish' === $condition_post->post_status ) {
			// Get the condition data.
			$display_conditions = get_post_meta( $condition_id, '_gb_conditions', true );

			if ( ! empty( $display_conditions ) ) {
				// Use the existing conditions system to evaluate.
				$show = GenerateBlocks_Pro_Conditions::show( $display_conditions );

				// If invert is enabled, flip the result.
				if ( $invert_condition ) {
					$show = ! $show;
				}
			}
		}

		// Cache the result.
		$this->condition_cache[ $cache_key ] = $show;

		return $show;
	}

	/**
	 * Add menu item conditions handler to usage search.
	 *
	 * @param array $handlers     Existing handlers.
	 * @param int   $condition_id The condition ID.
	 * @return array Modified handlers.
	 */
	public function add_menu_usage_handler( $handlers, $condition_id ) {
		$handlers['menu_item_conditions'] = [
			'method' => 'search_menu_item_conditions_usage',
			'label'  => __( 'Menu Item Conditions', 'generateblocks-pro' ),
		];

		return $handlers;
	}

	/**
	 * AJAX handler for searching conditions.
	 */
	public function ajax_search_conditions() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_GET['_ajax_nonce'], 'gb_search_conditions' ) ) {
			wp_die();
		}

		$search = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';
		$page = isset( $_GET['page'] ) ? absint( $_GET['page'] ) : 1;
		$per_page = $this->get_conditions_limit();

		$args = [
			'post_type' => 'gblocks_condition',
			'posts_per_page' => $per_page,
			'paged' => $page,
			'orderby' => 'date',
			'order' => 'DESC',
			'post_status' => 'publish',
		];

		if ( $search ) {
			$args['s'] = $search;
			$args['orderby'] = 'relevance';
		}

		$conditions = get_posts( $args );
		$total = wp_count_posts( 'gblocks_condition' )->publish;

		$results = [];
		foreach ( $conditions as $condition ) {
			$results[] = [
				'id' => $condition->ID,
				'text' => $condition->post_title ? $condition->post_title : __( 'Untitled Condition', 'generateblocks-pro' ),
			];
		}

		wp_send_json(
			[
				'results' => $results,
				'pagination' => [
					'more' => ( $page * $per_page ) < $total,
				],
			]
		);
	}


	/**
	 * AJAX handler for loading more conditions.
	 */
	public function ajax_load_more_conditions() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_GET['_ajax_nonce'], 'gb_load_more_conditions' ) ) {
			wp_die();
		}

		$page = isset( $_GET['page'] ) ? absint( $_GET['page'] ) : 2;
		$limit = $this->get_conditions_limit();
		$offset = ( $page - 1 ) * $limit;

		$conditions = get_posts(
			[
				'post_type' => 'gblocks_condition',
				'posts_per_page' => $limit,
				'offset' => $offset,
				'orderby' => 'date',
				'order' => 'DESC',
				'post_status' => 'publish',
				'suppress_filters' => false,
				'no_found_rows' => false,
			]
		);

		$formatted_conditions = [];
		foreach ( $conditions as $condition ) {
			$formatted_conditions[] = [
				'id' => $condition->ID,
				'title' => $condition->post_title ? $condition->post_title : __( 'Untitled Condition', 'generateblocks-pro' ),
			];
		}

		$total = wp_count_posts( 'gblocks_condition' )->publish;
		$has_more = ( $page * $limit ) < $total;

		wp_send_json_success(
			[
				'conditions' => $formatted_conditions,
				'has_more' => $has_more,
			]
		);
	}

	/**
	 * Add admin scripts for dynamic UI behavior.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function add_admin_scripts( $hook_suffix ) {
		// Only load on nav-menus.php page.
		if ( 'nav-menus.php' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_script(
			'gb-menu-item-conditions',
			GENERATEBLOCKS_PRO_DIR_URL . 'dist/menu-item-conditions.js',
			array(),
			GENERATEBLOCKS_PRO_VERSION,
			true
		);

		wp_localize_script(
			'gb-menu-item-conditions',
			'gbMenuConditions',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonces'  => array(
					'loadMore' => wp_create_nonce( 'gb_load_more_conditions' ),
					'search'   => wp_create_nonce( 'gb_search_conditions' ),
				),
				'strings' => array(
					'loading'  => __( 'Loading...', 'generateblocks-pro' ),
					'loadMore' => __( 'Load More', 'generateblocks-pro' ),
				),
			)
		);
	}
}

// Initialize the class.
GenerateBlocks_Pro_Menu_Item_Conditions::get_instance()->init();
