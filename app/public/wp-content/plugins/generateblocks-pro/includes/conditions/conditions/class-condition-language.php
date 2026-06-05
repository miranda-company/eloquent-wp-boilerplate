<?php
/**
 * Language Condition
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Language condition evaluator.
 */
class GenerateBlocks_Pro_Condition_Language extends GenerateBlocks_Pro_Condition_Abstract {
	/**
	 * Evaluate the condition.
	 *
	 * @param string $rule     The condition rule.
	 * @param string $operator The condition operator.
	 * @param mixed  $value    The value to check against.
	 * @param array  $context  Additional context data.
	 * @return bool
	 */
	public function evaluate( $rule, $operator, $value, $context = [] ) {
		$current_locale = $this->get_current_locale();
		$is_match = false;

		switch ( $rule ) {
			case 'locale':
				// Full locale match (e.g., en_US).
				$is_match = $current_locale === $value;
				break;

			case 'language':
				// Language code match (e.g., en).
				$current_lang = substr( $current_locale, 0, 2 );
				$is_match = $current_lang === $value;
				break;

			case 'rtl':
				// Check if Right-to-Left language.
				$is_match = is_rtl();
				break;

			case 'custom':
				// Custom locale check.
				$is_match = $current_locale === $value;
				break;

			default:
				// For predefined locales (en_US, fr_FR, etc.), check directly.
				$is_match = $current_locale === $rule;
				break;
		}

		return 'is_not' === $operator ? ! $is_match : $is_match;
	}

	/**
	 * Get the current locale.
	 *
	 * @return string
	 */
	private function get_current_locale() {
		// Check for WPML.
		if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
			global $sitepress;
			if ( $sitepress && method_exists( $sitepress, 'get_locale' ) ) {
				$locale = $sitepress->get_locale( ICL_LANGUAGE_CODE );
				if ( $locale ) {
					return $locale;
				}
			}
		}

		// Check for Polylang.
		if ( function_exists( 'pll_current_language' ) ) {
			$locale = pll_current_language( 'locale' );
			if ( $locale ) {
				return $locale;
			}
		}

		// Default WordPress locale.
		return get_locale();
	}

	/**
	 * Get available rules for this condition type.
	 *
	 * @return array
	 */
	public function get_rules() {
		$rules = [
			'locale'   => __( 'Full Locale (e.g., en_US)', 'generateblocks-pro' ),
			'language' => __( 'Language Code (e.g., en)', 'generateblocks-pro' ),
			'rtl'      => __( 'RTL Language', 'generateblocks-pro' ),
			'custom'   => __( 'Custom Locale', 'generateblocks-pro' ),
		];

		// Add common locales.
		$common_locales = [
			'en_US' => __( 'English (United States)', 'generateblocks-pro' ),
			'en_GB' => __( 'English (UK)', 'generateblocks-pro' ),
			'en_CA' => __( 'English (Canada)', 'generateblocks-pro' ),
			'en_AU' => __( 'English (Australia)', 'generateblocks-pro' ),
			'es_ES' => __( 'Spanish (Spain)', 'generateblocks-pro' ),
			'es_MX' => __( 'Spanish (Mexico)', 'generateblocks-pro' ),
			'fr_FR' => __( 'French (France)', 'generateblocks-pro' ),
			'fr_CA' => __( 'French (Canada)', 'generateblocks-pro' ),
			'de_DE' => __( 'German', 'generateblocks-pro' ),
			'it_IT' => __( 'Italian', 'generateblocks-pro' ),
			'pt_BR' => __( 'Portuguese (Brazil)', 'generateblocks-pro' ),
			'pt_PT' => __( 'Portuguese (Portugal)', 'generateblocks-pro' ),
			'nl_NL' => __( 'Dutch', 'generateblocks-pro' ),
			'ru_RU' => __( 'Russian', 'generateblocks-pro' ),
			'ja'    => __( 'Japanese', 'generateblocks-pro' ),
			'zh_CN' => __( 'Chinese (Simplified)', 'generateblocks-pro' ),
			'zh_TW' => __( 'Chinese (Traditional)', 'generateblocks-pro' ),
			'ko_KR' => __( 'Korean', 'generateblocks-pro' ),
			'ar'    => __( 'Arabic', 'generateblocks-pro' ),
			'he_IL' => __( 'Hebrew', 'generateblocks-pro' ),
		];

		// Get installed languages if available.
		if ( function_exists( 'get_available_languages' ) ) {
			$installed = get_available_languages();
			foreach ( $installed as $locale ) {
				if ( ! isset( $common_locales[ $locale ] ) && 'en_US' !== $locale ) {
					$common_locales[ $locale ] = $locale;
				}
			}
		}

		$rules = array_merge( $rules, $common_locales );

		return apply_filters( 'generateblocks_language_rules', $rules );
	}

	/**
	 * Get metadata for a specific rule.
	 *
	 * @param string $rule The rule key.
	 * @return array
	 */
	public function get_rule_metadata( $rule ) {
		// RTL check doesn't need a value.
		if ( 'rtl' === $rule ) {
			return [
				'needs_value' => false,
				'value_type'  => 'none',
			];
		}

		// Language code needs a 2-letter code.
		if ( 'language' === $rule ) {
			return [
				'needs_value' => true,
				'value_type'  => 'text',
			];
		}

		// Custom and locale need full locale codes.
		if ( in_array( $rule, [ 'locale', 'custom' ], true ) ) {
			return [
				'needs_value' => true,
				'value_type'  => 'text',
			];
		}

		// Predefined locales don't need values.
		return [
			'needs_value' => false,
			'value_type'  => 'none',
		];
	}

	/**
	 * Sanitize the condition value.
	 *
	 * @param mixed  $value The value to sanitize.
	 * @param string $rule  The rule being used.
	 * @return mixed
	 */
	public function sanitize_value( $value, $rule ) {
		// For language codes, ensure lowercase and 2 characters.
		if ( 'language' === $rule ) {
			return strtolower( substr( sanitize_text_field( $value ), 0, 2 ) );
		}

		// For locales, allow underscores and dashes.
		if ( in_array( $rule, [ 'locale', 'custom' ], true ) ) {
			return preg_replace( '/[^a-zA-Z0-9_-]/', '', sanitize_text_field( $value ) );
		}

		return sanitize_text_field( $value );
	}
}
