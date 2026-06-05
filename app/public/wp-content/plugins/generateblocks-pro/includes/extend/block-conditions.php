<?php
/**
 * Block Conditions functionality.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class GenerateBlocks_Pro_Block_Conditions
 */
class GenerateBlocks_Pro_Block_Conditions extends GenerateBlocks_Pro_Singleton {

	/**
	 * Constructor.
	 */
	public function init() {
		// Don't initialize if block conditions are disabled.
		if ( ! generateblocks_pro_block_conditions_enabled() ) {
			return;
		}

		add_filter( 'render_block', [ $this, 'check_block_conditions' ], 10, 2 );
		add_filter( 'generateblocks_condition_usage_handlers', [ $this, 'add_block_usage_handler' ], 10, 2 );
		add_filter( 'register_block_type_args', [ $this, 'add_condition_attributes' ], 10, 2 );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
	}

	/**
	 * Enqueue block editor assets for block conditions.
	 */
	public function enqueue_editor_assets() {
		$assets = generateblocks_pro_get_enqueue_assets( 'block-conditions' );

		wp_enqueue_script(
			'generateblocks-pro-block-conditions',
			GENERATEBLOCKS_PRO_DIR_URL . 'dist/block-conditions.js',
			$assets['dependencies'],
			$assets['version'],
			true
		);
	}

	/**
	 * Check block conditions and prevent rendering if conditions are not met.
	 *
	 * @param string $block_content The block content.
	 * @param array  $block The block data.
	 * @return string The block content or empty string if conditions are not met.
	 */
	public function check_block_conditions( $block_content, $block ) {
		// Early return if no attributes.
		if ( empty( $block['attrs'] ) ) {
			return $block_content;
		}

		$attributes = $block['attrs'];

		// Check if this block has a condition set.
		if ( empty( $attributes['gbBlockCondition'] ) ) {
			return $block_content;
		}

		$condition_id = absint( $attributes['gbBlockCondition'] );

		// Validate condition ID.
		if ( ! $condition_id ) {
			return $block_content;
		}

		// Check if the condition post exists and is published.
		$condition_post = get_post( $condition_id );

		if ( ! $condition_post || 'publish' !== $condition_post->post_status ) {
			return $block_content;
		}

		// Get the condition data.
		$display_conditions = get_post_meta( $condition_id, '_gb_conditions', true );

		if ( empty( $display_conditions ) ) {
			return $block_content;
		}

		// Pass the current post context to the conditions system.
		// This ensures conditions are evaluated for the correct post in loops.
		$context = array( 'post_id' => get_the_ID() );

		// Use the existing conditions system to evaluate.
		$show = GenerateBlocks_Pro_Conditions::show( $display_conditions, $context );

		// If invert is enabled, flip the result.
		if ( ! empty( $attributes['gbBlockConditionInvert'] ) ) {
			$show = ! $show;
		}

		// Return empty string to prevent block from rendering if condition is not met.
		if ( ! $show ) {
			return '';
		}

		return $block_content;
	}

	/**
	 * Add block conditions handler to usage search.
	 *
	 * @param array $handlers Existing handlers.
	 * @param int   $condition_id The condition ID.
	 * @return array Modified handlers.
	 */
	public function add_block_usage_handler( $handlers, $condition_id ) {
		$handlers['block_conditions'] = [
			'method' => 'search_block_conditions_usage',
			'label'  => __( 'Block Conditions', 'generateblocks-pro' ),
		];

		return $handlers;
	}

	/**
	 * Add condition attributes to all blocks during server-side registration.
	 * This ensures ServerSideRender calls don't fail validation.
	 *
	 * @param array  $args The block registration arguments.
	 * @param string $block_type The block type name.
	 * @return array Modified arguments.
	 */
	public function add_condition_attributes( $args, $block_type ) {
		// Ensure attributes array exists.
		if ( ! isset( $args['attributes'] ) ) {
			$args['attributes'] = [];
		}

		// Add gbBlockCondition attribute if not already defined.
		if ( ! isset( $args['attributes']['gbBlockCondition'] ) ) {
			$args['attributes']['gbBlockCondition'] = [
				'type'    => 'string',
				'default' => '',
			];
		}

		// Add gbBlockConditionInvert attribute if not already defined.
		if ( ! isset( $args['attributes']['gbBlockConditionInvert'] ) ) {
			$args['attributes']['gbBlockConditionInvert'] = [
				'type'    => 'boolean',
				'default' => false,
			];
		}

		return $args;
	}
}

// Initialize the class.
GenerateBlocks_Pro_Block_Conditions::get_instance()->init();
