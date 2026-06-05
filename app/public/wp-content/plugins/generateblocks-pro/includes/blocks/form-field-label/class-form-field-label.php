<?php
/**
 * Handles the Form Field Label block.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form field label block class.
 */
class GenerateBlocks_Block_Form_Field_Label extends GenerateBlocks_Block {

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
	public static $block_name = 'generateblocks-pro/form-field-label';

	/**
	 * Render the Form Field Label block.
	 *
	 * @param array    $attributes    The block attributes.
	 * @param string   $block_content The block content.
	 * @param WP_Block $block         The block instance.
	 * @return string
	 */
	public static function render_block( $attributes, $block_content, $block ) {
		$field_type = GenerateBlocks_Block_Form_Field::normalize_field_type(
			GenerateBlocks_Block_Form_Field::get_context_value( $block, 'fieldType', '' )
		);

		if ( 'hidden' === $field_type ) {
			return '';
		}

		$field_name = (string) GenerateBlocks_Block_Form_Field::get_context_value( $block, 'fieldName', '' );
		$unique_id  = (string) GenerateBlocks_Block_Form_Field::get_context_value( $block, 'uniqueId', '' );
		$required   = ! empty( GenerateBlocks_Block_Form_Field::get_context_value( $block, 'isRequired', false ) );
		$is_group   = GenerateBlocks_Block_Form_Field::is_group_type( $field_type );

		$has_content = '' !== trim( wp_strip_all_tags( (string) ( $attributes['content'] ?? '' ) ) );

		if ( ! $has_content ) {
			return '';
		}

		$block_content = generateblocks_maybe_add_block_css(
			$block_content,
			[
				'class_name' => __CLASS__,
				'attributes' => $attributes,
				'block_ids'  => self::$block_ids,
			]
		);

		if ( class_exists( 'WP_HTML_Tag_Processor' ) ) {
			$processor = new WP_HTML_Tag_Processor( $block_content );
			$tag_name  = $is_group ? 'legend' : 'label';

			if ( $processor->next_tag( $tag_name ) ) {
				if ( ! $is_group ) {
					$processor->set_attribute( 'for', GenerateBlocks_Block_Form_Field::get_field_id( $unique_id, $field_name ) );
				}

				$block_content = $processor->get_updated_html();
			}
		}

		return $required ? self::append_required_marker( $block_content, $is_group ? 'legend' : 'label' ) : $block_content;
	}

	/**
	 * Append the runtime required marker inside the saved label/legend markup.
	 *
	 * @param string $html     Label block HTML.
	 * @param string $tag_name Label tag name.
	 * @return string
	 */
	private static function append_required_marker( $html, $tag_name ) {
		if ( false !== strpos( $html, 'gb-form-field__required' ) ) {
			return $html;
		}

		$tag_name = tag_escape( $tag_name );
		$updated  = preg_replace(
			sprintf( '#</%s>\s*$#i', preg_quote( $tag_name, '#' ) ),
			'<span class="gb-form-field__required"> *</span></' . $tag_name . '>',
			$html,
			1
		);

		return is_string( $updated ) ? $updated : $html;
	}
}
