<?php
/**
 * Editor preview REST endpoint.
 *
 * Returns server-rendered form HTML for use inside the Form block's
 * in-editor preview. Uses the same render helper as the public runtime
 * so there is no JS/PHP drift — what you see here is what visitors get.
 *
 * Permission: users with the baseline `use` cap can hit the route, and the
 * render helper then allows published forms for those users while requiring
 * edit access on the specific form for draft/private previews.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form preview REST class.
 */
class GenerateBlocks_Pro_Form_Preview_Rest extends GenerateBlocks_Pro_Singleton {

	/**
	 * Initialize.
	 */
	public function init() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			'generateblocks-pro/v1',
			'/forms/(?P<id>\d+)/preview',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'render_preview' ],
				'permission_callback' => [ $this, 'can_preview' ],
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
				],
			]
		);
	}

	/**
	 * Render the form HTML for an editor preview.
	 *
	 * Returns 200 with `{ html }` on success, 200 with `{ success:false, message }`
	 * on failure. 200 is intentional — this is an editor convenience endpoint,
	 * not a state change, and the editor shows the message inline.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function render_preview( WP_REST_Request $request ) {
		$form_id = absint( $request->get_param( 'id' ) );

		$html = GenerateBlocks_Pro_Form_Render::render( $form_id, 'editor' );

		if ( is_wp_error( $html ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => $html->get_error_message(),
				],
				200
			);
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'html'    => $html,
			],
			200
		);
	}

	/**
	 * Permission callback.
	 *
	 * Individual form permission checks happen inside the render helper;
	 * here we only gate on the baseline "can use forms at all" cap to stop
	 * anonymous REST enumeration of preview URLs.
	 *
	 * @return bool
	 */
	public function can_preview() {
		return GenerateBlocks_Pro_Form_Post_Type::current_user_can( 'use' );
	}
}
