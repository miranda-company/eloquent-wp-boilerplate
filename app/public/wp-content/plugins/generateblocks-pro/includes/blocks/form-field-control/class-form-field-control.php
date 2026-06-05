<?php
/**
 * Handles the Form Field Control block.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form field control block class.
 */
class GenerateBlocks_Block_Form_Field_Control extends GenerateBlocks_Block {

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
	public static $block_name = 'generateblocks-pro/form-field-control';

	/**
	 * Maximum textarea rows accepted from block attributes.
	 *
	 * @var int
	 */
	const MAX_TEXTAREA_ROWS = 100;

	/**
	 * Render the Form Field Control block.
	 *
	 * @param array    $attributes    The block attributes.
	 * @param string   $block_content The block content.
	 * @param WP_Block $block         The block instance.
	 * @return string
	 */
	public static function render_block( $attributes, $block_content, $block ) {
		unset( $block_content );

		$field_type = GenerateBlocks_Block_Form_Field::normalize_field_type(
			GenerateBlocks_Block_Form_Field::get_context_value( $block, 'fieldType', '' )
		);
		$field_name = sanitize_key(
			GenerateBlocks_Block_Form_Field::get_context_value( $block, 'fieldName', '' )
		);
		$unique_id  = (string) GenerateBlocks_Block_Form_Field::get_context_value( $block, 'uniqueId', '' );
		$required   = 'hidden' !== $field_type && ! empty( GenerateBlocks_Block_Form_Field::get_context_value( $block, 'isRequired', false ) );
		$field_id   = GenerateBlocks_Block_Form_Field::get_field_id( $unique_id, $field_name );
		$html       = self::build_control( $attributes, $field_type, $field_name, $field_id, $required, $block );

		return generateblocks_maybe_add_block_css(
			$html,
			[
				'class_name' => __CLASS__,
				'attributes' => $attributes,
				'block_ids'  => self::$block_ids,
			]
		);
	}

	/**
	 * Build the control markup.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $field_type Field type.
	 * @param string $field_name Field name.
	 * @param string $field_id   Field ID.
	 * @param bool   $required   Whether required.
	 * @param mixed  $block      Block instance.
	 * @return string
	 */
	private static function build_control( $attributes, $field_type, $field_name, $field_id, $required, $block ) {
		$default_value = GenerateBlocks_Block_Form_Field::resolve_default_value( $attributes['defaultValue'] ?? '', $block );

		switch ( $field_type ) {
			case 'textarea':
				return self::build_textarea( $attributes, $field_name, $field_id, $required, $default_value );

			case 'select':
				return self::build_select( $attributes, $field_name, $field_id, $required, $default_value );

			case 'checkbox':
				return self::build_checkbox( $attributes, $field_name, $field_id, $required );

			case 'radio':
				return self::build_choice_group( $attributes, $field_name, $field_id, true, $required, $default_value );

			case 'checkbox-group':
				return self::build_choice_group( $attributes, $field_name, $field_id, false, false, '' );

			default:
				return self::build_input( $attributes, $field_name, $field_id, $field_type, $required, $default_value );
		}
	}

	/**
	 * Build base control classes.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $element    Element class.
	 * @return string
	 */
	private static function get_control_classes( $attributes, $element ) {
		return GenerateBlocks_Block_Form_Field::get_block_classes(
			'gb-form-field-control',
			$attributes,
			[ $element ]
		);
	}

	/**
	 * Build an input element.
	 *
	 * @param array  $attributes    Block attributes.
	 * @param string $name          Field name.
	 * @param string $id            Field ID.
	 * @param string $type          Input type.
	 * @param bool   $required      Whether required.
	 * @param string $default_value Default value.
	 * @return string
	 */
	private static function build_input( $attributes, $name, $id, $type, $required, $default_value ) {
		$attrs = [
			'class' => self::get_control_classes( $attributes, 'gb-form-field__input' ),
			'type'  => $type,
			'name'  => $name,
			'id'    => $id,
		];

		if ( ! empty( $attributes['placeholder'] ) && 'hidden' !== $type ) {
			$attrs['placeholder'] = (string) $attributes['placeholder'];
		}

		if ( '' !== $default_value ) {
			$attrs['value'] = $default_value;
		}

		if ( $required ) {
			$attrs['required']      = true;
			$attrs['aria-required'] = 'true';
		}

		return self::single_tag( 'input', $attrs );
	}

	/**
	 * Build a textarea element.
	 *
	 * @param array  $attributes    Block attributes.
	 * @param string $name          Field name.
	 * @param string $id            Field ID.
	 * @param bool   $required      Whether required.
	 * @param string $default_value Default value.
	 * @return string
	 */
	private static function build_textarea( $attributes, $name, $id, $required, $default_value ) {
		$attrs = [
			'class' => self::get_control_classes( $attributes, 'gb-form-field__input' ),
			'name'  => $name,
			'id'    => $id,
		];
		$rows  = isset( $attributes['rows'] ) ? min( self::MAX_TEXTAREA_ROWS, absint( $attributes['rows'] ) ) : 0;

		if ( $rows ) {
			$attrs['rows'] = $rows;
		}

		if ( ! empty( $attributes['placeholder'] ) ) {
			$attrs['placeholder'] = (string) $attributes['placeholder'];
		}

		if ( $required ) {
			$attrs['required']      = true;
			$attrs['aria-required'] = 'true';
		}

		return self::wrap_tag( 'textarea', $attrs, esc_textarea( $default_value ) );
	}

	/**
	 * Build a select element.
	 *
	 * @param array  $attributes    Block attributes.
	 * @param string $name          Field name.
	 * @param string $id            Field ID.
	 * @param bool   $required      Whether required.
	 * @param string $default_value Default selected value.
	 * @return string
	 */
	private static function build_select( $attributes, $name, $id, $required, $default_value ) {
		$attrs = [
			'class' => self::get_control_classes( $attributes, 'gb-form-field__input' ),
			'name'  => $name,
			'id'    => $id,
		];

		if ( $required ) {
			$attrs['required']      = true;
			$attrs['aria-required'] = 'true';
		}

		$options_html = '';
		$placeholder  = $attributes['placeholder'] ?? '';

		if ( '' !== $placeholder ) {
			$options_html .= sprintf(
				'<option value="" disabled%s>%s</option>',
				'' === $default_value ? ' selected' : '',
				esc_html( $placeholder )
			);
		}

		foreach ( (array) ( $attributes['options'] ?? [] ) as $option ) {
			$option = GenerateBlocks_Block_Form_Field::normalize_option( $option );

			if ( ! $option ) {
				continue;
			}

			$options_html .= sprintf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $option['value'] ),
				'' !== $default_value && $option['value'] === $default_value ? ' selected' : '',
				esc_html( $option['label'] )
			);
		}

			return self::wrap_tag( 'select', $attrs, $options_html );
	}

	/**
	 * Build a checkbox element.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $name       Field name.
	 * @param string $id         Field ID.
	 * @param bool   $required   Whether required.
	 * @return string
	 */
	private static function build_checkbox( $attributes, $name, $id, $required ) {
		$checked_value = ! empty( $attributes['checkedValue'] )
			? sanitize_text_field( (string) $attributes['checkedValue'] )
			: 'yes';
		$attrs         = [
			'class' => self::get_control_classes( $attributes, 'gb-form-field__input' ),
			'type'  => 'checkbox',
			'name'  => $name,
			'id'    => $id,
			'value' => $checked_value,
		];

		if ( $required ) {
			$attrs['required']      = true;
			$attrs['aria-required'] = 'true';
		}

			return self::single_tag( 'input', $attrs );
	}

	/**
	 * Build radio/checkbox-group options.
	 *
	 * @param array  $attributes    Block attributes.
	 * @param string $name          Field name.
	 * @param string $id            Field ID prefix.
	 * @param bool   $radio         Whether radio buttons.
	 * @param bool   $required      Whether radio group is required.
	 * @param string $default_value Default selected value.
	 * @return string
	 */
	private static function build_choice_group( $attributes, $name, $id, $radio, $required, $default_value ) {
		$attrs = [
			'class' => self::get_control_classes( $attributes, 'gb-form-field__group' ),
		];
		$html  = '';

		foreach ( (array) ( $attributes['options'] ?? [] ) as $index => $option ) {
			$option = GenerateBlocks_Block_Form_Field::normalize_option( $option );

			if ( ! $option ) {
				continue;
			}

			$option_id = $id . '-' . $index;
			$html     .= sprintf(
				'<label class="gb-form-field__group-option" for="%s">',
				esc_attr( $option_id )
			);

			$input_attrs = [
				'type'  => $radio ? 'radio' : 'checkbox',
				'name'  => $radio ? $name : $name . '[]',
				'id'    => $option_id,
				'value' => $option['value'],
			];

			if ( $radio && '' !== $default_value && $option['value'] === $default_value ) {
				$input_attrs['checked'] = true;
			}

			if ( $radio && $required ) {
				$input_attrs['required'] = true;
			}

			$html .= '<input ' . GenerateBlocks_Block_Form_Field::format_attributes( $input_attrs ) . ' />';
			$html .= '<span>' . esc_html( $option['label'] ) . '</span>';
			$html .= '</label>';
		}

			return self::wrap_tag( 'div', $attrs, $html );
	}

	/**
	 * Build a single tag.
	 *
	 * @param string $tag   Tag name.
	 * @param array  $attrs Generated attributes.
	 * @return string
	 */
	private static function single_tag( $tag, $attrs ) {
		$attr_string = GenerateBlocks_Block_Form_Field::format_attributes( $attrs );

		return sprintf( '<%s %s />', tag_escape( $tag ), $attr_string );
	}

	/**
	 * Build an element with content.
	 *
	 * @param string $tag     Tag name.
	 * @param array  $attrs   Generated attributes.
	 * @param string $content Inner HTML.
	 * @return string
	 */
	private static function wrap_tag( $tag, $attrs, $content ) {
		$attr_string = GenerateBlocks_Block_Form_Field::format_attributes( $attrs );

		return sprintf(
			'<%1$s %2$s>%3$s</%1$s>',
			tag_escape( $tag ),
			$attr_string,
			$content
		);
	}
}
