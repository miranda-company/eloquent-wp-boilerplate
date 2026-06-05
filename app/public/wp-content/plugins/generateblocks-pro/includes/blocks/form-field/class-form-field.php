<?php
/**
 * Handles the Form Field wrapper block.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form field wrapper block class.
 */
class GenerateBlocks_Block_Form_Field extends GenerateBlocks_Block {

	/**
	 * Keep track of all blocks of this type on the page.
	 *
	 * @var array
	 */
	protected static $block_ids = [];

	/**
	 * Block name.
	 *
	 * @var string
	 */
	public static $block_name = 'generateblocks-pro/form-field';

	/**
	 * All allowed field types.
	 *
	 * @var array
	 */
	private static $allowed_types = [ 'text', 'email', 'url', 'tel', 'number', 'hidden', 'textarea', 'select', 'checkbox', 'radio', 'checkbox-group' ];

	/**
	 * Field types that support same-value match validation.
	 *
	 * @var array
	 */
	private static $matchable_types = [ 'text', 'email', 'url', 'tel', 'number', 'textarea' ];

	/**
	 * Allowed display condition operators.
	 *
	 * @var array
	 */
	private static $allowed_condition_operators = [ 'is', 'isnot', 'isempty', 'isnotempty' ];

	/**
	 * Per-render instance number, set by GenerateBlocks_Pro_Form_Render before
	 * rendering each form.
	 *
	 * @var int
	 */
	private static $render_instance = 0;

	/**
	 * Set the current render instance number.
	 *
	 * @param int $instance Instance number (1+) or 0 to reset.
	 */
	public static function set_render_instance( $instance ) {
		self::$render_instance = (int) $instance;
	}

	/**
	 * Get the current render instance number.
	 *
	 * @return int
	 */
	public static function get_render_instance() {
		return (int) self::$render_instance;
	}

	/**
	 * Suffix appended to every field ID for the current render.
	 *
	 * @return string
	 */
	public static function id_suffix() {
		return self::$render_instance > 0 ? '-' . self::$render_instance : '';
	}

	/**
	 * Normalize a field type.
	 *
	 * @param mixed $field_type Raw field type.
	 * @return string
	 */
	public static function normalize_field_type( $field_type ) {
		$field_type = is_string( $field_type ) ? $field_type : '';

		if ( '' === $field_type ) {
			return 'text';
		}

		if ( ! in_array( $field_type, self::$allowed_types, true ) ) {
			return 'text';
		}

		return $field_type;
	}

	/**
	 * Whether a field type uses the wrapper as a fieldset.
	 *
	 * @param string $field_type Field type.
	 * @return bool
	 */
	public static function is_group_type( $field_type ) {
		return in_array( self::normalize_field_type( $field_type ), [ 'radio', 'checkbox-group' ], true );
	}

	/**
	 * Get a field ID shared by label/control children.
	 *
	 * @param string $unique_id  Wrapper unique ID.
	 * @param string $field_name Field name fallback.
	 * @return string
	 */
	public static function get_field_id( $unique_id, $field_name = '' ) {
		$key = $unique_id ? $unique_id : sanitize_key( $field_name );

		if ( '' === $key ) {
			$key = 'field';
		}

		return 'gb-field-' . sanitize_html_class( $key ) . self::id_suffix();
	}

	/**
	 * Get a value from a WP_Block context object.
	 *
	 * @param mixed  $block    Block object.
	 * @param string $key      Context key suffix.
	 * @param mixed  $fallback Fallback.
	 * @return mixed
	 */
	public static function get_context_value( $block, $key, $fallback = '' ) {
		$context_key = 'generateblocks-pro/form-field/' . $key;

		if ( is_object( $block ) && isset( $block->context ) && is_array( $block->context ) && array_key_exists( $context_key, $block->context ) ) {
			return $block->context[ $context_key ];
		}

		return $fallback;
	}

	/**
	 * Build classes for a styles-builder-aware block.
	 *
	 * @param string $base_class Base class.
	 * @param array  $attributes Block attributes.
	 * @param array  $extra       Extra classes.
	 * @return string
	 */
	public static function get_block_classes( $base_class, $attributes, $extra = [] ) {
		$classes = [ $base_class ];

		if ( ! empty( $attributes['globalClasses'] ) && is_array( $attributes['globalClasses'] ) ) {
			foreach ( $attributes['globalClasses'] as $class_name ) {
				$class_name = sanitize_html_class( $class_name );

				if ( $class_name ) {
					$classes[] = $class_name;
				}
			}
		}

		if ( ! empty( $attributes['styles'] ) && is_array( $attributes['styles'] ) && ! empty( $attributes['uniqueId'] ) ) {
			$classes[] = $base_class . '-' . sanitize_html_class( $attributes['uniqueId'] );
		}

		foreach ( $extra as $class_name ) {
			$class_name = sanitize_html_class( $class_name );

			if ( $class_name ) {
				$classes[] = $class_name;
			}
		}

		return implode( ' ', array_unique( array_filter( $classes ) ) );
	}

	/**
	 * Format an associative attribute array.
	 *
	 * @param array $attributes Attribute name/value pairs.
	 * @return string
	 */
	public static function format_attributes( $attributes ) {
		$out = [];

		foreach ( $attributes as $name => $value ) {
			if ( true === $value ) {
				$out[] = esc_attr( $name );
				continue;
			}

			if ( false === $value || null === $value || is_array( $value ) || is_object( $value ) ) {
				continue;
			}

			$out[] = sprintf( '%s="%s"', esc_attr( $name ), esc_attr( (string) $value ) );
		}

		return implode( ' ', $out );
	}

	/**
	 * Normalize a choice option for rendering.
	 *
	 * @param mixed $option Raw option attribute.
	 * @return array|null Normalized label/value pair or null for empty rows.
	 */
	public static function normalize_option( $option ) {
		if ( ! is_array( $option ) ) {
			return null;
		}

		$label = isset( $option['label'] ) ? (string) $option['label'] : '';
		$value = isset( $option['value'] ) ? (string) $option['value'] : '';

		if ( '' === $value && '' !== $label ) {
			$value = $label;
		}

		if ( '' === $label && '' !== $value ) {
			$label = $value;
		}

		if ( '' === $label && '' === $value ) {
			return null;
		}

		return [
			'label' => $label,
			'value' => $value,
		];
	}

	/**
	 * Sanitize display conditions before rendering them as frontend data attrs.
	 *
	 * Incomplete rows can be left behind while editing, especially if a trigger
	 * field is removed before the empty row is deleted. Those must not create
	 * `data-gb-conditions`, because an empty field name evaluates as empty on the
	 * frontend and causes a hidden-then-revealed layout shift.
	 *
	 * @param mixed $conditions Raw conditions attribute.
	 * @return array
	 */
	private static function sanitize_conditions( $conditions ) {
		if ( ! is_array( $conditions ) ) {
			return [];
		}

		$sanitized = [];

		foreach ( $conditions as $condition ) {
			if ( ! is_array( $condition ) ) {
				continue;
			}

			$field    = sanitize_key( $condition['field'] ?? '' );
			$operator = sanitize_key( $condition['operator'] ?? '' );

			if ( '' === $field || ! in_array( $operator, self::$allowed_condition_operators, true ) ) {
				continue;
			}

			$sanitized[] = [
				'field'    => $field,
				'operator' => $operator,
				'value'    => (string) ( $condition['value'] ?? '' ),
			];
		}

		return $sanitized;
	}

	/**
	 * Resolve dynamic tags in a default value.
	 *
	 * @param string $default_value Raw default value.
	 * @param mixed  $block         Block instance.
	 * @return string
	 */
	public static function resolve_default_value( $default_value, $block ) {
		$default_value = (string) $default_value;

		if ( '' !== $default_value && false !== strpos( $default_value, '{{' ) && class_exists( 'GenerateBlocks_Register_Dynamic_Tag' ) ) {
			$default_value = GenerateBlocks_Register_Dynamic_Tag::replace_tags(
				$default_value,
				[],
				$block
			);
		}

		return (string) $default_value;
	}

	/**
	 * Render the Form Field wrapper block.
	 *
	 * @param array    $attributes    The block attributes.
	 * @param string   $block_content The block content.
	 * @param WP_Block $block         The block instance.
	 * @return string The modified block content.
	 */
	public static function render_block( $attributes, $block_content, $block ) {
		unset( $block );

		$block_content = generateblocks_maybe_add_block_css(
			$block_content,
			[
				'class_name' => __CLASS__,
				'attributes' => $attributes,
				'block_ids'  => self::$block_ids,
			]
		);

		$field_type = self::normalize_field_type( $attributes['fieldType'] ?? '' );

		if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			return $block_content;
		}

		$processor = new WP_HTML_Tag_Processor( $block_content );

		$tag_name = self::is_group_type( $field_type ) ? 'fieldset' : 'div';

		if ( ! $processor->next_tag( $tag_name ) ) {
			return $block_content;
		}

		$modifier_map = [
			'checkbox'       => 'gb-form-field--checkbox',
			'radio'          => 'gb-form-field--radio',
			'checkbox-group' => 'gb-form-field--checkbox-group',
		];

		if ( isset( $modifier_map[ $field_type ] ) ) {
			$processor->add_class( $modifier_map[ $field_type ] );
		}

		if ( self::is_group_type( $field_type ) ) {
			$processor->set_attribute( 'role', 'radio' === $field_type ? 'radiogroup' : 'group' );

			if ( ! empty( $attributes['isRequired'] ) ) {
				$processor->set_attribute( 'aria-required', 'true' );

				if ( 'checkbox-group' === $field_type ) {
					$processor->set_attribute( 'data-gb-required', 'true' );
				}
			}
		}

		$field_name    = sanitize_key( $attributes['fieldName'] ?? '' );
		$matches_field = sanitize_key( $attributes['matchesField'] ?? '' );

		if (
			$field_name &&
			$matches_field &&
			$field_name !== $matches_field &&
			in_array( $field_type, self::$matchable_types, true )
		) {
			$processor->set_attribute( 'data-gb-field-name', $field_name );
			$processor->set_attribute( 'data-gb-matches-field', $matches_field );
			$processor->set_attribute(
				'data-gb-match-message',
				__( 'The matching fields must have the same value.', 'generateblocks-pro' )
			);
			$processor->set_attribute(
				'data-gb-match-message-with-label',
				/* translators: %1$s: source field label, %2$s: target field label. */
				__( 'Please make sure "%1$s" matches "%2$s".', 'generateblocks-pro' )
			);
			$processor->set_attribute(
				'data-gb-match-message-with-target',
				/* translators: %s: target field label. */
				__( 'This field must match "%s".', 'generateblocks-pro' )
			);
		}

		$conditions = self::sanitize_conditions( $attributes['conditions'] ?? [] );

		if ( ! empty( $conditions ) ) {
			$processor->set_attribute( 'data-gb-conditions', wp_json_encode( $conditions ) );
			$processor->set_attribute( 'hidden', true );
		}

		return $processor->get_updated_html();
	}
}
