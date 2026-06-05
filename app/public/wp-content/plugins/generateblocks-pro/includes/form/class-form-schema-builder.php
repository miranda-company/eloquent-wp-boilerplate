<?php
/**
 * Form schema builder.
 *
 * Parses and validates a `gblocks_form` post's field structure from its
 * saved block content. Submission processing derives field definitions from
 * post_content at request time; save hooks use this class only to surface
 * editor notices for invalid field setups.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form schema builder class.
 */
class GenerateBlocks_Pro_Form_Schema_Builder extends GenerateBlocks_Pro_Singleton {

	const NOTICE_TRANSIENT_PREFIX = 'gb_form_schema_error_';

	/**
	 * Allowed field types.
	 *
	 * Kept in one place — the sanitizer's type map should match.
	 *
	 * @var array
	 */
	private static $allowed_types = [
		'text',
		'email',
		'url',
		'tel',
		'number',
		'hidden',
		'textarea',
		'select',
		'checkbox',
		'radio',
		'checkbox-group',
	];

	/**
	 * Allowed condition operators (post-sanitize_key — all lowercase).
	 *
	 * @var array
	 */
	private static $allowed_operators = [ 'is', 'isnot', 'isempty', 'isnotempty' ];

	/**
	 * Option field types whose allowed-value list must be non-empty.
	 *
	 * An empty allowlist would let any submitted value slip through the
	 * processor's in_array() validation, so we reject the save entirely
	 * rather than persist a config that can't enforce itself.
	 *
	 * @var array
	 */
	private static $option_types = [ 'select', 'radio', 'checkbox-group' ];

	/**
	 * Field types that support same-value match validation.
	 *
	 * @var array
	 */
	private static $matchable_types = [ 'text', 'email', 'url', 'tel', 'number', 'textarea' ];

	/**
	 * Initialize.
	 */
	public function init() {
		// Block REST writes outright when the schema is invalid — a saved
		// form with duplicate field names or invalid options would publish
		// fine but reject every submission with a generic error, which is
		// worse UX than failing the save in the editor.
		add_filter( 'rest_pre_insert_' . GenerateBlocks_Pro_Form_Post_Type::POST_TYPE, [ $this, 'gate_rest_save' ], 10, 2 );

		// Non-REST save fallback (classic editor, programmatic inserts):
		// keep the post-save transient-notice path so the user at least sees
		// what's wrong on the next admin page load.
		add_action( 'save_post_' . GenerateBlocks_Pro_Form_Post_Type::POST_TYPE, [ $this, 'validate_schema' ], 10, 3 );
		add_action( 'admin_notices', [ $this, 'render_admin_notices' ] );
	}

	/**
	 * Reject REST writes whose post_content fails schema validation.
	 *
	 * Runs before the post hits the database, so the editor's save flow
	 * surfaces the WP_Error inline instead of letting an invalid form
	 * publish and silently break submissions.
	 *
	 * @param stdClass        $prepared_post Prepared post object as a stdClass.
	 * @param WP_REST_Request $request       The REST request.
	 * @return stdClass|WP_Error The prepared post, or WP_Error to block the save.
	 */
	public function gate_rest_save( $prepared_post, $request ) {
		unset( $request );

		if ( ! is_object( $prepared_post ) || ! isset( $prepared_post->post_content ) ) {
			return $prepared_post;
		}

		$schema = $this->build_schema_from_content( (string) $prepared_post->post_content );

		if ( is_wp_error( $schema ) ) {
			return new WP_Error(
				$schema->get_error_code(),
				$schema->get_error_message(),
				[ 'status' => 400 ]
			);
		}

		return $prepared_post;
	}

	/**
	 * Validate the form's saved field structure after a form post saves.
	 *
	 * Skip revisions, autosaves, and auto-drafts — they don't carry the live
	 * content and running on them would thrash notice state with partial data.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an update.
	 */
	public function validate_schema( $post_id, $post, $update ) {
		unset( $update );

		if (
			wp_is_post_revision( $post_id )
			|| wp_is_post_autosave( $post_id )
			|| 'auto-draft' === $post->post_status
		) {
			return;
		}

		$schema = $this->build_schema_from_content( $post->post_content );

		if ( is_wp_error( $schema ) ) {
			$this->record_error( $post_id, $schema );
			return;
		}

		delete_transient( self::NOTICE_TRANSIENT_PREFIX . $post_id );
	}

	/**
	 * Parse post_content, walk form-field blocks, build sanitized schema.
	 *
	 * @param string $content Post content.
	 * @param array  $args    Optional validation args.
	 * @return array|WP_Error Schema array on success, WP_Error on validation failure.
	 */
	public function build_schema_from_content( $content, $args = [] ) {
		$args = wp_parse_args(
			$args,
			[
				'require_field_names' => false,
			]
		);

		if ( ! is_string( $content ) || '' === trim( $content ) ) {
			return new WP_Error(
				'gb_form_missing_form_block',
				__( 'This form is missing a Form block. Add one Form block before saving.', 'generateblocks-pro' )
			);
		}

		$blocks = parse_blocks( $content );
		$fields = [];

		$disallowed_block = $this->find_disallowed_form_block( $blocks );

		if ( is_wp_error( $disallowed_block ) ) {
			return $disallowed_block;
		}

		$form_blocks = $this->find_form_blocks( $blocks );

		if ( empty( $form_blocks ) ) {
			return new WP_Error(
				'gb_form_missing_form_block',
				__( 'This form is missing a Form block. Add one Form block before saving.', 'generateblocks-pro' )
			);
		}

		if ( count( $form_blocks ) > 1 ) {
			return new WP_Error(
				'gb_form_multiple_form_blocks',
				__( 'This form has multiple Form blocks. Keep only one Form block before saving.', 'generateblocks-pro' )
			);
		}

		$form_inner_blocks = isset( $form_blocks[0]['innerBlocks'] ) && is_array( $form_blocks[0]['innerBlocks'] )
			? $form_blocks[0]['innerBlocks']
			: [];

		$collected = $this->collect_fields( $form_inner_blocks, $fields );

		if ( is_wp_error( $collected ) ) {
			return $collected;
		}

		$schema    = [];
		$seen_keys = [];

		foreach ( $fields as $attrs ) {
			$entry = $this->build_field_entry( $attrs, $args );

			if ( is_wp_error( $entry ) ) {
				return $entry;
			}

			if ( null === $entry ) {
				continue;
			}

			if ( isset( $seen_keys[ $entry['name'] ] ) ) {
				return new WP_Error(
					'gb_form_duplicate_field_name',
					sprintf(
						/* translators: %s: duplicate field name */
						__( 'Duplicate field name: %s. Field names must be unique within a form.', 'generateblocks-pro' ),
						$entry['name']
					)
				);
			}

			$seen_keys[ $entry['name'] ] = true;
			$schema[]                    = $entry;
		}

		return $this->sanitize_match_references( $schema );
	}

	/**
	 * Recursively find static Form blocks in parsed post content.
	 *
	 * Reusable blocks are intentionally not followed here. The Form block must
	 * live in the saved Form CPT content so render output and submission schema
	 * are derived from the same document.
	 *
	 * @param array $blocks Parsed blocks.
	 * @return array<int,array> Matching Form blocks.
	 */
	private function find_form_blocks( $blocks ) {
		$matches = [];

		foreach ( $blocks as $block ) {
			$name = $block['blockName'] ?? '';

			if ( 'generateblocks-pro/form' === $name ) {
				$matches[] = $block;
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$matches = array_merge( $matches, $this->find_form_blocks( $block['innerBlocks'] ) );
			}
		}

		return $matches;
	}

	/**
	 * Recursively collect form-field definitions.
	 *
	 * @param array $blocks Parsed blocks.
	 * @param array $out    Accumulator.
	 * @return WP_Error|null
	 */
	private function collect_fields( $blocks, array &$out ) {
		foreach ( $blocks as $block ) {
			$name = $block['blockName'] ?? '';

			if ( 'generateblocks-pro/form-field' === $name ) {
				$attrs = $this->build_attrs_from_field_block( $block );

				if ( is_wp_error( $attrs ) ) {
					return $attrs;
				}

				$out[] = $attrs;
				continue;
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$result = $this->collect_fields( $block['innerBlocks'], $out );

				if ( is_wp_error( $result ) ) {
					return $result;
				}
			}
		}

		return null;
	}

	/**
	 * Build the schema-facing field attrs from a wrapper + child blocks.
	 *
	 * The wrapper owns field identity/behavior; label/control children own
	 * their own content/settings. The schema flattens those pieces because
	 * the submission processor works from one field definition per field.
	 *
	 * @param array $block Parsed form-field wrapper block.
	 * @return array|WP_Error Flattened field attributes or an error for ambiguous markup.
	 */
	private function build_attrs_from_field_block( $block ) {
		$attrs          = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : [];
		$field_name     = sanitize_key( $attrs['fieldName'] ?? '' );
		$label_blocks   = $this->find_inner_block_attrs( $block, 'generateblocks-pro/form-field-label' );
		$control_blocks = $this->find_inner_block_attrs( $block, 'generateblocks-pro/form-field-control' );

		if ( count( $label_blocks ) > 1 ) {
			return new WP_Error(
				'gb_form_multiple_labels',
				sprintf(
					/* translators: %s: field name */
					__( 'Field "%s" has multiple labels. Each field can only use one Form Field Label block.', 'generateblocks-pro' ),
					$field_name ? $field_name : __( '(unnamed)', 'generateblocks-pro' )
				)
			);
		}

		if ( count( $control_blocks ) > 1 ) {
			return new WP_Error(
				'gb_form_multiple_controls',
				sprintf(
					/* translators: %s: field name */
					__( 'Field "%s" has multiple controls. Each field can only use one Form Field Control block.', 'generateblocks-pro' ),
					$field_name ? $field_name : __( '(unnamed)', 'generateblocks-pro' )
				)
			);
		}

		if ( $field_name && empty( $control_blocks ) ) {
			return new WP_Error(
				'gb_form_missing_control',
				sprintf(
					/* translators: %s: field name */
					__( 'Field "%s" is missing a Form Field Control block.', 'generateblocks-pro' ),
					$field_name
				)
			);
		}

		$label_attrs   = $label_blocks[0] ?? [];
		$control_attrs = $control_blocks[0] ?? [];
		$field_type    = $attrs['fieldType'] ?? '';

		if ( '' !== $field_type && in_array( $field_type, [ 'radio', 'checkbox-group' ], true ) && ! empty( $label_blocks ) && ! $this->has_first_direct_inner_block( $block, 'generateblocks-pro/form-field-label' ) ) {
			return new WP_Error(
				'gb_form_group_label_position',
				sprintf(
					/* translators: %s: field name */
					__( 'Field "%s" uses a group label, so its Form Field Label block must be the first block directly inside the Form Field block.', 'generateblocks-pro' ),
					$field_name ? $field_name : __( '(unnamed)', 'generateblocks-pro' )
				)
			);
		}

		if ( ! empty( $label_blocks ) && ! $this->has_direct_inner_block( $block, 'generateblocks-pro/form-field-label' ) ) {
			return new WP_Error(
				'gb_form_nested_label',
				sprintf(
					/* translators: %s: field name */
					__( 'Field "%s" has a nested Form Field Label block. Add the label directly inside the Form Field block.', 'generateblocks-pro' ),
					$field_name ? $field_name : __( '(unnamed)', 'generateblocks-pro' )
				)
			);
		}

		if ( isset( $label_attrs['content'] ) ) {
			$attrs['label'] = $label_attrs['content'];
		}

		foreach ( [ 'placeholder', 'rows', 'options', 'checkedValue', 'defaultValue' ] as $key ) {
			if ( array_key_exists( $key, $control_attrs ) ) {
				$attrs[ $key ] = $control_attrs[ $key ];
			}
		}

		return $attrs;
	}

	/**
	 * Find matching child block attrs inside a parsed form-field block.
	 *
	 * Layout blocks are allowed inside a field, so controls may be nested. Label
	 * lookup remains recursive for defensive parsing; group labels are validated
	 * separately because <legend> must be the first fieldset child.
	 *
	 * @param array  $block      Parsed block.
	 * @param string $block_name Block name.
	 * @return array<int,array>
	 */
	private function find_inner_block_attrs( $block, $block_name ) {
		$matches = [];

		if ( empty( $block['innerBlocks'] ) || ! is_array( $block['innerBlocks'] ) ) {
			return $matches;
		}

		foreach ( $block['innerBlocks'] as $inner_block ) {
			$inner_name = $inner_block['blockName'] ?? '';

			if ( 'generateblocks-pro/form-field' === $inner_name ) {
				continue;
			}

			if ( $block_name === $inner_name ) {
				$matches[] = isset( $inner_block['attrs'] ) && is_array( $inner_block['attrs'] ) ? $inner_block['attrs'] : [];
				continue;
			}

			$matches = array_merge( $matches, $this->find_inner_block_attrs( $inner_block, $block_name ) );
		}

		return $matches;
	}

	/**
	 * Check whether the first direct inner block matches a block name.
	 *
	 * Group fields render labels as <legend>, which is only valid when it is the
	 * first child of the fieldset. Controls may still be nested in layout blocks.
	 *
	 * @param array  $block      Parsed block.
	 * @param string $block_name Block name.
	 * @return bool
	 */
	private function has_first_direct_inner_block( $block, $block_name ) {
		if ( empty( $block['innerBlocks'] ) || ! is_array( $block['innerBlocks'] ) ) {
			return false;
		}

		$first_block = $block['innerBlocks'][0] ?? [];

		return ( $first_block['blockName'] ?? '' ) === $block_name;
	}

	/**
	 * Check whether a direct inner block matches a block name.
	 *
	 * @param array  $block      Parsed block.
	 * @param string $block_name Block name.
	 * @return bool
	 */
	private function has_direct_inner_block( $block, $block_name ) {
		if ( empty( $block['innerBlocks'] ) || ! is_array( $block['innerBlocks'] ) ) {
			return false;
		}

		foreach ( $block['innerBlocks'] as $inner_block ) {
			if ( ( $inner_block['blockName'] ?? '' ) === $block_name ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check whether parsed content contains disallowed form blocks.
	 *
	 * Synced patterns/reusable blocks are rejected outright. Forms need one
	 * static saved document so rendering, submission schema, and validation all
	 * read from the same source of truth.
	 *
	 * @param array $blocks            Parsed blocks.
	 * @param bool  $inside_form_block Whether the current block list is inside a Form block.
	 * @param bool  $inside_form_field Whether the current block list is inside a form-field block.
	 * @return WP_Error|null Error when a disallowed block is found.
	 */
	private function find_disallowed_form_block( $blocks, $inside_form_block = false, $inside_form_field = false ) {
		foreach ( $blocks as $block ) {
			$name = $block['blockName'] ?? '';

			$is_form_block  = 'generateblocks-pro/form' === $name;
			$is_form_field  = 'generateblocks-pro/form-field' === $name;
			$is_field_child = in_array(
				$name,
				[
					'generateblocks-pro/form-field-label',
					'generateblocks-pro/form-field-control',
				],
				true
			);

			if ( 'core/block' === $name ) {
				return new WP_Error(
					'gb_form_reusable_block',
					__( 'Synced patterns and reusable blocks cannot be used inside forms. Add static blocks directly inside the Form post.', 'generateblocks-pro' )
				);
			}

			if ( 'generateblocks-pro/form-render' === $name ) {
				return new WP_Error(
					'gb_form_disallowed_block',
					__( 'Form renderer blocks cannot be used inside forms.', 'generateblocks-pro' )
				);
			}

			if ( ! $inside_form_block && ( $is_form_field || $is_field_child ) ) {
				return new WP_Error(
					'gb_form_field_outside_form',
					__( 'Form fields must be inside the Form block.', 'generateblocks-pro' )
				);
			}

			if ( $inside_form_block && ! $inside_form_field && $is_field_child ) {
				return new WP_Error(
					'gb_form_field_part_outside_field',
					__( 'Form field labels and controls must be inside a Form Field block.', 'generateblocks-pro' )
				);
			}

			if ( $inside_form_field && $is_form_field ) {
				return new WP_Error(
					'gb_form_nested_field',
					__( 'Form fields cannot be nested inside other form fields.', 'generateblocks-pro' )
				);
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$disallowed_block = $this->find_disallowed_form_block(
					$block['innerBlocks'],
					$inside_form_block || $is_form_block,
					$inside_form_field || $is_form_field
				);

				if ( is_wp_error( $disallowed_block ) ) {
					return $disallowed_block;
				}
			}
		}

		return null;
	}

	/**
	 * Build a single sanitized field entry, or return WP_Error on invalid data.
	 *
	 * Returns null when the field should be silently skipped in permissive mode
	 * (e.g. empty fieldName from an unconfigured draft block).
	 *
	 * @param array $attrs Raw field attributes.
	 * @param array $args  Validation args.
	 * @return array|WP_Error|null
	 */
	private function build_field_entry( $attrs, $args = [] ) {
		$name = sanitize_key( $attrs['fieldName'] ?? '' );

		if ( '' === $name ) {
			if ( ! empty( $args['require_field_names'] ) ) {
				return new WP_Error(
					'gb_form_missing_field_name',
					__( 'Every form field needs a field name before publishing.', 'generateblocks-pro' )
				);
			}

			// Unconfigured draft field — skip without error.
			return null;
		}

		$type = $attrs['fieldType'] ?? 'text';

		// Render fallback treats empty fieldType as text (block.json default is "").
		// Normalize here so an explicit "" doesn't paint a working text input but
		// fail submission with gb_form_invalid_field_type.
		if ( '' === $type ) {
			$type = 'text';
		}

		if ( ! in_array( $type, self::$allowed_types, true ) ) {
			return new WP_Error(
				'gb_form_invalid_field_type',
				sprintf(
					/* translators: %1$s: field name; %2$s: invalid field type */
					__( 'Field "%1$s" has an invalid type (%2$s).', 'generateblocks-pro' ),
					$name,
					sanitize_text_field( (string) $type )
				)
			);
		}

		$entry = [
			'name'     => $name,
			'type'     => $type,
			'required' => 'hidden' !== $type && ! empty( $attrs['isRequired'] ),
			'label'    => sanitize_text_field( (string) ( $attrs['label'] ?? $name ) ),
		];

		if ( in_array( $type, self::$option_types, true ) ) {
			$options = $this->sanitize_options( $attrs['options'] ?? [], $name );

			if ( is_wp_error( $options ) ) {
				return $options;
			}

			$entry['options'] = $options;
		}

		if ( 'checkbox' === $type ) {
			$entry['checkedValue'] = ! empty( $attrs['checkedValue'] )
				? sanitize_text_field( (string) $attrs['checkedValue'] )
				: 'yes';
		}

		if ( in_array( $type, self::$matchable_types, true ) ) {
			$matches_field = sanitize_key( $attrs['matchesField'] ?? '' );

			if ( '' !== $matches_field && $matches_field !== $name ) {
				$entry['matchesField'] = $matches_field;
			}
		}

		if ( ! empty( $attrs['conditions'] ) && is_array( $attrs['conditions'] ) ) {
			$entry['conditions'] = $this->sanitize_conditions( $attrs['conditions'] );
		}

		return $entry;
	}

	/**
	 * Drop incomplete or incompatible match-validation references.
	 *
	 * @param array $schema Schema entries.
	 * @return array Schema entries with valid matchesField values only.
	 */
	private function sanitize_match_references( array $schema ) {
		$field_types = [];

		foreach ( $schema as $entry ) {
			if ( ! empty( $entry['name'] ) && ! empty( $entry['type'] ) ) {
				$field_types[ $entry['name'] ] = $entry['type'];
			}
		}

		foreach ( $schema as $index => $entry ) {
			if ( empty( $entry['matchesField'] ) ) {
				continue;
			}

			$matches_field = sanitize_key( $entry['matchesField'] );

			if (
				empty( $entry['name'] ) ||
				$matches_field === $entry['name'] ||
				! isset( $field_types[ $matches_field ] ) ||
				! in_array( $entry['type'], self::$matchable_types, true ) ||
				! in_array( $field_types[ $matches_field ], self::$matchable_types, true )
			) {
				unset( $schema[ $index ]['matchesField'] );
				continue;
			}

			$schema[ $index ]['matchesField'] = $matches_field;
		}

		return $schema;
	}

	/**
	 * Sanitize + validate the options array for a select/radio/checkbox-group.
	 *
	 * Requires at least one non-empty value. Empty allowlist is rejected
	 * because the processor uses in_array() to validate submitted values —
	 * an empty allowlist would accept anything.
	 *
	 * @param mixed  $raw_options Raw options.
	 * @param string $field_name  Field name for error messages.
	 * @return array|WP_Error
	 */
	private function sanitize_options( $raw_options, $field_name ) {
		if ( ! is_array( $raw_options ) ) {
			return new WP_Error(
				'gb_form_missing_options',
				sprintf(
					/* translators: %s: field name */
					__( 'Field "%s" requires options but none were provided.', 'generateblocks-pro' ),
					$field_name
				)
			);
		}

		$clean      = [];
		$seen_vals  = [];

		foreach ( $raw_options as $option ) {
			if ( ! is_array( $option ) ) {
				continue;
			}

			$value = isset( $option['value'] ) ? sanitize_text_field( (string) $option['value'] ) : '';
			$label = isset( $option['label'] ) ? sanitize_text_field( (string) $option['label'] ) : '';

			if ( '' === $value && '' !== $label ) {
				$value = $label;
			}

			if ( '' === $label && '' !== $value ) {
				$label = $value;
			}

			if ( '' === $value ) {
				continue;
			}

			if ( isset( $seen_vals[ $value ] ) ) {
				return new WP_Error(
					'gb_form_duplicate_option',
					sprintf(
						/* translators: %1$s: field name; %2$s: duplicated option value */
						__( 'Field "%1$s" has duplicate option value "%2$s".', 'generateblocks-pro' ),
						$field_name,
						$value
					)
				);
			}

			$seen_vals[ $value ] = true;
			$clean[]             = [
				'value' => $value,
				'label' => '' !== $label ? $label : $value,
			];
		}

		if ( empty( $clean ) ) {
			return new WP_Error(
				'gb_form_empty_options',
				sprintf(
					/* translators: %s: field name */
					__( 'Field "%s" requires at least one option with a non-empty value.', 'generateblocks-pro' ),
					$field_name
				)
			);
		}

		return $clean;
	}

	/**
	 * Sanitize a conditions array.
	 *
	 * Conditions with unknown operators or empty trigger fields are dropped
	 * silently (not fatal — a stale condition referencing a deleted field
	 * shouldn't block the whole save).
	 *
	 * @param array $conditions Raw conditions.
	 * @return array
	 */
	private function sanitize_conditions( $conditions ) {
		$out = [];

		foreach ( $conditions as $condition ) {
			if ( ! is_array( $condition ) ) {
				continue;
			}

			$field    = sanitize_key( $condition['field'] ?? '' );
			$operator = sanitize_key( $condition['operator'] ?? '' );

			if ( '' === $field || ! in_array( $operator, self::$allowed_operators, true ) ) {
				continue;
			}

			$out[] = [
				'field'    => $field,
				'operator' => $operator,
				'value'    => sanitize_text_field( (string) ( $condition['value'] ?? '' ) ),
			];
		}

		return $out;
	}

	/**
	 * Record a validation failure for admin-notice rendering on the next page load.
	 *
	 * @param int      $post_id Post ID.
	 * @param WP_Error $error   Error to record.
	 */
	private function record_error( $post_id, WP_Error $error ) {
		set_transient(
			self::NOTICE_TRANSIENT_PREFIX . $post_id,
			[
				'message' => $error->get_error_message(),
				'code'    => $error->get_error_code(),
			],
			MINUTE_IN_SECONDS * 10
		);
	}

	/**
	 * Render admin notices for saved-content validation errors.
	 *
	 * Matches on global $post when editing a form so the notice surfaces on
	 * the same screen where the user just made the breaking edit.
	 */
	public function render_admin_notices() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || GenerateBlocks_Pro_Form_Post_Type::POST_TYPE !== $screen->post_type ) {
			return;
		}

		global $post;

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$notice = get_transient( self::NOTICE_TRANSIENT_PREFIX . $post->ID );

		if ( empty( $notice['message'] ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
			esc_html__( 'Form fields are invalid:', 'generateblocks-pro' ),
			esc_html( (string) $notice['message'] )
		);
	}
}
