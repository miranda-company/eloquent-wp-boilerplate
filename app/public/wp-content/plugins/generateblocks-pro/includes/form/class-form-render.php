<?php
/**
 * Shared form render helper.
 *
 * One function renders a form for both the public runtime and the editor
 * preview. Permission behavior differs by context:
 *
 *   - public: only accepts `publish` forms.
 *   - editor: accepts published forms for users who can use forms, and
 *     accepts draft/private forms only for users who can edit the form.
 *
 * This keeps JS and PHP aligned — no client-side rendering of fields, no
 * drift between what's previewed and what's submitted.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form render helper.
 */
class GenerateBlocks_Pro_Form_Render {

	/**
	 * Per-request render counter. Bumped on each render() so the same form
	 * embedded twice on a page produces non-colliding field IDs.
	 *
	 * @var int
	 */
	private static $instance_counter = 0;

	/**
	 * Active render frame, or null when no render is in flight.
	 *
	 * Holds form id, host post id, config, context, instance number, and a
	 * counter of Form-block render-callback invocations during the current
	 * render. The Form block render callback reads this frame so runtime
	 * attribute injection is scoped to the actual Form block output, never to
	 * arbitrary <form> tags rendered by wrapper or sibling blocks. Nested
	 * renders are rejected outright (see render() guard) so a single frame is
	 * sufficient.
	 *
	 * @var array|null
	 */
	private static $current_render = null;

	/**
	 * Render a form by post ID.
	 *
	 * @param int    $form_id    Form post ID.
	 * @param string $context    'public' or 'editor'.
	 * @param array  $overrides  Render-time overrides (e.g. host post ID for context).
	 * @return string|WP_Error Rendered HTML on success, WP_Error on failure.
	 */
	public static function render( $form_id, $context = 'public', $overrides = [] ) {
		$form_id = absint( $form_id );

		if ( ! $form_id ) {
			return new WP_Error( 'invalid_form_id', 'Invalid form ID.' );
		}

		$form = get_post( $form_id );

		if ( ! $form || GenerateBlocks_Pro_Form_Post_Type::POST_TYPE !== $form->post_type ) {
			return new WP_Error( 'form_not_found', 'Form not found.' );
		}

		if ( null !== self::$current_render ) {
			return new WP_Error( 'form_render_recursion', 'Nested form renderer detected.' );
		}

		// Public runtime: only render published forms. Editor preview allows
		// published forms for users who can use forms, but draft/private forms
		// still require edit access on the specific form post.
		if ( 'public' === $context ) {
			if ( 'publish' !== $form->post_status ) {
				return new WP_Error( 'form_not_published', 'Form not published.' );
			}
		} else {
			if ( 'publish' !== $form->post_status && ! current_user_can( 'edit_post', $form_id ) ) {
				return new WP_Error( 'cannot_edit_form', 'Cannot preview this form.' );
			}

			if (
				'publish' === $form->post_status
				&& ! GenerateBlocks_Pro_Form_Post_Type::current_user_can( 'use' )
				&& ! current_user_can( 'edit_post', $form_id )
			) {
				return new WP_Error( 'cannot_edit_form', 'Cannot preview this form.' );
			}
		}

		$meta   = get_post_meta( $form_id, GenerateBlocks_Pro_Form_Post_Type::META_KEY, true );
		$config = is_array( $meta ) && isset( $meta['config'] ) && is_array( $meta['config'] )
			? $meta['config']
			: [];

		$host_post_id = isset( $overrides['host_post_id'] ) ? absint( $overrides['host_post_id'] ) : 0;

		// Bump the per-request instance counter and tell the form-field
		// renderer to suffix its IDs with it. Reset after `do_blocks()` so
		// nested rendering paths and tests aren't affected by leftover state.
		self::$instance_counter++;
		$instance = self::$instance_counter;

		self::$current_render = [
			'form_id'              => $form_id,
			'host_post_id'         => $host_post_id,
			'config'               => $config,
			'context'              => $context,
			'instance'             => $instance,
			'rendered_form_blocks' => 0,
		];

		if ( class_exists( 'GenerateBlocks_Block_Form_Field' ) ) {
			GenerateBlocks_Block_Form_Field::set_render_instance( $instance );
		}

		try {
			$fields_html         = self::render_fields( $form->post_content, $context );
			$rendered_form_blocks = self::$current_render['rendered_form_blocks'];

			if ( 0 === $rendered_form_blocks ) {
				return new WP_Error( 'form_block_missing', 'Form block missing.' );
			}

			if ( $rendered_form_blocks > 1 ) {
				return new WP_Error( 'form_multiple_blocks', 'Multiple Form blocks found.' );
			}

			if ( '' === trim( $fields_html ) ) {
				return new WP_Error( 'form_empty', 'Form has no fields.' );
			}

			return $fields_html;
		} finally {
			self::$current_render = null;

			if ( class_exists( 'GenerateBlocks_Block_Form_Field' ) ) {
				GenerateBlocks_Block_Form_Field::set_render_instance( 0 );
			}
		}
	}

	/**
	 * Render the inner field blocks.
	 *
	 * Uses do_blocks so the full render pipeline runs — dynamic tags,
	 * conditional attributes, and generated classes stay consistent with how
	 * inner form-field blocks render anywhere else.
	 *
	 * Editor preview runs inside a REST request — wp_head never fires, so
	 * GenerateBlocks' default "queue CSS for wp_head" path drops the block
	 * CSS on the floor. Force inline <style> output for editor context so
	 * the preview matches the public render visually.
	 *
	 * @param string $content Form post_content.
	 * @param string $context 'public' or 'editor'.
	 * @return string
	 */
	private static function render_fields( $content, $context = 'public' ) {
		if ( ! is_string( $content ) || '' === trim( $content ) ) {
			return '';
		}

		if ( 'editor' === $context ) {
			add_filter( 'generateblocks_do_inline_styles', '__return_true' );

			try {
				return do_blocks( $content );
			} finally {
				remove_filter( 'generateblocks_do_inline_styles', '__return_true' );
			}
		}

		return do_blocks( $content );
	}

	/**
	 * Add runtime submission attributes to the rendered Form block.
	 *
	 * Called by GenerateBlocks_Block_Form::render_block(). This scopes form
	 * mutation to the actual Form block output instead of scanning arbitrary
	 * forms that may appear in wrapper/sibling blocks.
	 *
	 * @param string $block_content Rendered Form block HTML.
	 * @return string
	 */
	public static function prepare_form_block_output( $block_content ) {
		if ( null === self::$current_render ) {
			return $block_content;
		}

		self::$current_render['rendered_form_blocks']++;

		return self::build_wrapper(
			(int) self::$current_render['form_id'],
			(int) self::$current_render['host_post_id'],
			(array) self::$current_render['config'],
			$block_content,
			(string) self::$current_render['context'],
			(int) self::$current_render['instance']
		);
	}

	/**
	 * Augment the Form block's rendered <form> tag with submission attrs,
	 * and append integration / message / noscript HTML before </form>.
	 *
	 * The Form block (canvas) already emits `<form class="gb-form">...</form>`
	 * via its save/render; this helper uses WP_HTML_Tag_Processor to set
	 * method/novalidate/data-* on that existing tag without re-wrapping.
	 * Editor previews then downgrade that outer tag to a `<div>` so Gutenberg
	 * never hosts a live nested form.
	 *
	 * @param int    $form_id      Form post ID.
	 * @param int    $host_post_id Host page ID (for HMAC scope + page_url in actions).
	 * @param array  $config       Form config from meta.
	 * @param string $form_html    Rendered form block HTML (includes the <form> tag).
	 * @param string $context      'public' or 'editor'.
	 * @param int    $instance     Per-render instance number (matches the field-id suffix).
	 * @return string
	 */
	private static function build_wrapper( $form_id, $host_post_id, $config, $form_html, $context, $instance = 1 ) {
		$defaults        = GenerateBlocks_Pro_Form_Post_Type::default_form_config();
		$success_message = ! empty( $config['successMessage'] ) ? $config['successMessage'] : $defaults['successMessage'];
		$error_message   = ! empty( $config['errorMessage'] ) ? $config['errorMessage'] : $defaults['errorMessage'];
		$redirect_url    = $config['redirectUrl'] ?? '';

		$attrs = [
			'data-gb-form-id'  => (string) $form_id,
			// Read by form.js to suffix the honeypot input ID so the same form
			// embedded twice doesn't produce two `<input id="gb-hp-{formId}">`.
			'data-gb-instance' => (string) $instance,
		];

		if ( 'editor' === $context ) {
			$attrs['role'] = 'form';
			// `host` distinguishes "previewed inside another post via the
			// Form Render block" from `canvas` (the form CPT editor where
			// authors actually design the form). Authors can't edit the
			// form from a host page, so we keep the public-style hidden
			// state of the success container — the form stays compact and
			// looks like the live frontend.
			$attrs['data-gb-preview'] = 'host';
		} else {
			$attrs['method']                    = 'post';
			$attrs['novalidate']                = 'novalidate';
			$attrs['data-gb-post-id']           = (string) $host_post_id;
			$attrs['data-gb-form-endpoint']     = esc_url_raw( rest_url( 'generateblocks-pro/v1/forms/submit' ) );
			$attrs['data-gb-security-endpoint'] = esc_url_raw( rest_url( 'generateblocks-pro/v1/forms/security' ) );
			$attrs['data-gb-success-message']   = $success_message;
			$attrs['data-gb-error-message']     = $error_message;

			if ( '' !== $redirect_url ) {
				$attrs['data-gb-redirect-url'] = esc_url_raw( $redirect_url );
			}
		}

		$processor = new WP_HTML_Tag_Processor( $form_html );

		if ( $processor->next_tag( [ 'tag_name' => 'form' ] ) ) {
			foreach ( $attrs as $key => $value ) {
				$processor->set_attribute( $key, (string) $value );
			}

			$form_html = $processor->get_updated_html();
		}

		/**
		 * Filter HTML injected just before the Form block's closing </form>.
		 *
		 * Internal hook used by the bundled Turnstile integration to add its
		 * widget markup. Not yet documented as a public extension point —
		 * the JS-side provider contract is still in flux. Callbacks must
		 * escape their own output.
		 *
		 * @param string $html         Accumulated HTML. Default empty string.
		 * @param int    $form_id      Form post ID.
		 * @param int    $host_post_id Host page ID (0 in editor preview).
		 * @param array  $config       Form config from meta.
		 * @param string $context      'public' or 'editor'.
		 * @param int    $instance     Per-render instance number.
		 */
		$integration_html = apply_filters(
			'generateblocks_form_render_footer_html',
			'',
			$form_id,
			$host_post_id,
			$config,
			$context,
			$instance
		);
		$integration_html = is_string( $integration_html ) ? $integration_html : '';

		// role="status" + aria-live so a server-rendered or no-JS-fallback error
		// in this container is announced even before form.js can upgrade the role.
		// showMessage() flips role to "alert" + aria-live to "assertive" for errors.
		$message_el = '<div class="gb-form-message" role="status" aria-live="polite" aria-atomic="true" hidden></div>';
		$noscript   = 'public' === $context
			? '<noscript><p>' . esc_html__( 'Please enable JavaScript to submit this form.', 'generateblocks-pro' ) . '</p></noscript>'
			: '';

		$injected = $integration_html . $message_el . $noscript;

		if ( '' !== $injected ) {
			// Inject before the last </form>. strripos beats `</form>\s*$`
			// because third-party render_block filters can append comments
			// or whitespace past the close tag, defeating the end anchor.
			$close_pos = strripos( $form_html, '</form>' );

			if ( false !== $close_pos ) {
				$form_html = substr_replace( $form_html, $injected, $close_pos, 0 );
			}
		}

		if ( 'editor' === $context ) {
			$form_html = self::downgrade_editor_preview_form_tag( $form_html );
		}

		return $form_html;
	}

	/**
	 * Replace the outer preview `<form>` wrapper with `<div>`.
	 *
	 * Nested live forms inside Gutenberg can submit the admin post editor.
	 * Editor previews are visual only, so keep the same attributes/classes
	 * but switch the tag to a neutral container.
	 *
	 * @param string $html Form HTML.
	 * @return string
	 */
	private static function downgrade_editor_preview_form_tag( $html ) {
		if ( ! is_string( $html ) || '' === trim( $html ) ) {
			return '';
		}

		$html = preg_replace( '/<form\b/i', '<div', $html, 1 );

		$close_pos = strripos( $html, '</form>' );

		if ( false === $close_pos ) {
			return $html;
		}

		return substr_replace( $html, '</div>', $close_pos, strlen( '</form>' ) );
	}

	/**
	 * Enqueue the public runtime assets.
	 *
	 * Called from the Form block render path when a publish-status form
	 * is rendered on a public page.
	 */
	public static function enqueue_public_assets() {
		if ( ! wp_style_is( 'generateblocks-form', 'enqueued' ) ) {
			wp_enqueue_style(
				'generateblocks-form',
				GENERATEBLOCKS_PRO_DIR_URL . 'dist/form-style.css',
				[],
				GENERATEBLOCKS_PRO_VERSION
			);
		}

		if ( ! wp_script_is( 'generateblocks-form', 'enqueued' ) ) {
			wp_enqueue_script(
				'generateblocks-form',
				GENERATEBLOCKS_PRO_DIR_URL . 'dist/form.js',
				[],
				GENERATEBLOCKS_PRO_VERSION,
				true
			);
		}
	}
}
