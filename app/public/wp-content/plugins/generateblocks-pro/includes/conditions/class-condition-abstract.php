<?php
/**
 * Abstract Condition Base Class.
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract base class for conditions.
 */
abstract class GenerateBlocks_Pro_Condition_Abstract implements GenerateBlocks_Pro_Condition_Interface {
	/**
	 * Get default rule metadata.
	 *
	 * @return array
	 */
	protected function get_default_rule_metadata() {
		return [
			'needs_value' => true,
			'value_type'  => 'text',
		];
	}

	/**
	 * Parse custom field value structure with edge case handling.
	 *
	 * @param string $value The value to parse.
	 * @return array
	 */
	protected function parse_custom_value( $value ) {
		// Handle null, empty, or non-string values.
		if ( null === $value || '' === $value ) {
			return [
				'field_name' => '',
				'comparison_value' => '',
			];
		}

		// Convert to string if not already (handles numbers, booleans).
		if ( ! is_string( $value ) ) {
			if ( is_scalar( $value ) ) {
				$value = (string) $value;
			} else {
				// Arrays, objects, resources - return empty.
				return [
					'field_name' => '',
					'comparison_value' => '',
				];
			}
		}

		// Handle extremely long values to prevent memory issues.
		if ( 10000 < strlen( $value ) ) {
			$value = substr( $value, 0, 10000 );
		}

		if ( false === strpos( $value, '|' ) ) {
			return [
				'field_name' => sanitize_text_field( $value ),
				'comparison_value' => '',
			];
		}

		$parts = explode( '|', $value, 2 );
		return [
			'field_name' => sanitize_text_field( $parts[0] ),
			'comparison_value' => sanitize_text_field( $parts[1] ?? '' ),
		];
	}

	/**
	 * Sanitize custom field value with comprehensive validation.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return string
	 */
	protected function sanitize_custom_value( $value ) {
		if ( null === $value || '' === $value ) {
			return '';
		}

		// Handle arrays or objects - reject them for custom fields.
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = (string) $value;

		// Prevent extremely long values.
		if ( 10000 < strlen( $value ) ) {
			$value = substr( $value, 0, 10000 );
		}

		if ( false !== strpos( $value, '|' ) ) {
			$parts = $this->parse_custom_value( $value );
			// Validate field name (basic WordPress meta key validation).
			$field_name = $parts['field_name'];
			if ( empty( $field_name ) || 255 < strlen( $field_name ) ) {
				return '';
			}
			// Only rebuild if we have a valid field name.
			return $field_name . '|' . $parts['comparison_value'];
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Standardized meta field parsing with edge case handling.
	 *
	 * @param string $rule  The condition rule.
	 * @param mixed  $value The condition value.
	 * @return array Parsed field data with 'field_name' and 'comparison_value'.
	 */
	protected function parse_meta_field( $rule, $value ) {
		if ( 'custom' === $rule ) {
			return $this->parse_custom_value( $value );
		}

		// Ensure rule is a valid string.
		if ( ! is_string( $rule ) || empty( $rule ) ) {
			return [
				'field_name'       => '',
				'comparison_value' => '',
			];
		}

		// Handle non-scalar values.
		if ( ! is_scalar( $value ) && null !== $value ) {
			$comparison_value = '';
		} else {
			$comparison_value = is_scalar( $value ) ? sanitize_text_field( $value ) : '';
		}

		return [
			'field_name'       => sanitize_text_field( $rule ),
			'comparison_value' => $comparison_value,
		];
	}

	/**
	 * Enhanced meta existence evaluation with comprehensive validation.
	 *
	 * @param string $meta_type The meta type ('post', 'user', 'option').
	 * @param int    $object_id The object ID (post ID, user ID, etc.).
	 * @param string $meta_key  The meta key to check.
	 * @param string $operator  The operator ('exists' or 'not_exists').
	 * @return bool
	 */
	protected function evaluate_meta_existence( $meta_type, $object_id, $meta_key, $operator ) {
		// Validate meta type.
		if ( ! in_array( $meta_type, [ 'post', 'user', 'option' ], true ) ) {
			return false;
		}

		// Validate meta key.
		if ( empty( $meta_key ) || ! is_string( $meta_key ) || 255 < strlen( $meta_key ) ) {
			return false;
		}

		// Validate object ID for non-option types.
		if ( 'option' !== $meta_type ) {
			if ( ! is_numeric( $object_id ) || 1 > $object_id || PHP_INT_MAX < $object_id ) {
				return false;
			}
			$object_id = absint( $object_id );
		}

		// Validate operator.
		if ( ! in_array( $operator, [ 'exists', 'not_exists' ], true ) ) {
			return false;
		}

		try {
			if ( 'option' === $meta_type ) {
				$exists = false !== get_option( $meta_key, false );
			} else {
				$exists = metadata_exists( $meta_type, $object_id, $meta_key );
			}
		} catch ( Exception $e ) {
			// Database errors, invalid meta types, etc.
			return false;
		}

		return 'not_exists' === $operator ? ! $exists : $exists;
	}

	/**
	 * Enhanced meta value retrieval with comprehensive validation.
	 *
	 * @param string $meta_type        The meta type ('post', 'user', 'option').
	 * @param int    $object_id        The object ID (post ID, user ID, etc.).
	 * @param string $meta_key         The meta key.
	 * @param string $operator         The condition operator.
	 * @param mixed  $comparison_value The value to compare against.
	 * @return bool
	 */
	protected function evaluate_meta_value( $meta_type, $object_id, $meta_key, $operator, $comparison_value ) {
		// Validate inputs using same validation as existence check.
		if ( ! in_array( $meta_type, [ 'post', 'user', 'option' ], true ) ) {
			return false;
		}

		if ( empty( $meta_key ) || ! is_string( $meta_key ) || 255 < strlen( $meta_key ) ) {
			return false;
		}

		if ( 'option' !== $meta_type ) {
			if ( ! is_numeric( $object_id ) || 1 > $object_id || PHP_INT_MAX < $object_id ) {
				return false;
			}
			$object_id = absint( $object_id );
		}

		// Handle multi-value operators first.
		if ( $this->is_multi_value_operator( $operator ) ) {
			try {
				if ( 'option' === $meta_type ) {
					$meta_value = get_option( $meta_key );
				} elseif ( 'post' === $meta_type ) {
					$meta_value = get_post_meta( $object_id, $meta_key, true );
				} elseif ( 'user' === $meta_type ) {
					$meta_value = get_user_meta( $object_id, $meta_key, true );
				} else {
					return false;
				}
			} catch ( Exception $e ) {
				return false;
			}

			return $this->evaluate_multi_value_meta( $operator, $meta_value, $comparison_value );
		}

		// Get single meta value with error handling.
		try {
			if ( 'option' === $meta_type ) {
				$meta_value = get_option( $meta_key );
			} elseif ( 'post' === $meta_type ) {
				$meta_value = get_post_meta( $object_id, $meta_key, true );
			} elseif ( 'user' === $meta_type ) {
				$meta_value = get_user_meta( $object_id, $meta_key, true );
			} else {
				return false;
			}
		} catch ( Exception $e ) {
			return false;
		}

		// Evaluate based on operator.
		switch ( $operator ) {
			case 'equals':
				return $this->compare_values_equals( $meta_value, $comparison_value );

			case 'contains':
				return is_string( $meta_value ) && is_string( $comparison_value ) &&
					false !== strpos( $meta_value, $comparison_value );

			case 'not_contains':
				return ! is_string( $meta_value ) || ! is_string( $comparison_value ) ||
					false === strpos( $meta_value, $comparison_value );

			case 'greater_than':
				return $this->compare_numeric( $meta_value, $comparison_value, '>' );

			case 'less_than':
				return $this->compare_numeric( $meta_value, $comparison_value, '<' );

			default:
				return false;
		}
	}

	/**
	 * Check if operator supports multiple values.
	 *
	 * @param string $operator The operator to check.
	 * @return bool
	 */
	protected function is_multi_value_operator( $operator ) {
		$multi_operators = [ 'includes_any', 'includes_all', 'excludes_any', 'excludes_all' ];
		return in_array( $operator, $multi_operators, true );
	}

	/**
	 * Parse value as array for multi-select operators with comprehensive validation.
	 *
	 * @param mixed $value The value to parse.
	 * @return array
	 */
	protected function parse_multi_value( $value ) {
		if ( is_array( $value ) ) {
			// Limit array size to prevent memory issues.
			if ( 1000 < count( $value ) ) {
				$value = array_slice( $value, 0, 1000 );
			}
			return array_filter( array_map( 'sanitize_text_field', $value ) );
		}

		if ( null === $value || '' === $value ) {
			return [];
		}

		// Handle non-scalar values.
		if ( ! is_scalar( $value ) ) {
			return [];
		}

		$value = (string) $value;

		// Prevent extremely long JSON strings.
		if ( 50000 < strlen( $value ) ) {
			return [];
		}

		// Try to decode as JSON first.
		if ( '{' === $value[0] || '[' === $value[0] ) {
			$decoded = json_decode( $value, true );
			if ( is_array( $decoded ) ) {
				// Limit decoded array size.
				if ( 1000 < count( $decoded ) ) {
					$decoded = array_slice( $decoded, 0, 1000 );
				}
				return array_filter( array_map( 'sanitize_text_field', $decoded ) );
			}
		}

		// Fallback to single value.
		return [ sanitize_text_field( $value ) ];
	}

	/**
	 * Generic multi-value condition evaluator with validation.
	 *
	 * @param string   $operator The operator (includes_any, includes_all, etc.).
	 * @param mixed    $condition_values The condition values (array or string).
	 * @param callable $match_callback Callback function to check if a value matches.
	 * @return bool
	 */
	protected function evaluate_multi_value_generic( $operator, $condition_values, $match_callback ) {
		if ( ! is_callable( $match_callback ) ) {
			return false;
		}

		if ( ! in_array( $operator, [ 'includes_any', 'includes_all', 'excludes_any', 'excludes_all' ], true ) ) {
			return false;
		}

		$values = $this->parse_multi_value( $condition_values );

		if ( empty( $values ) ) {
			return false;
		}

		$matches = [];

		foreach ( $values as $value ) {
			try {
				$matches[] = call_user_func( $match_callback, $value );
			} catch ( Exception $e ) {
				$matches[] = false;
			}
		}

		// Apply operator logic.
		switch ( $operator ) {
			case 'includes_any':
				return in_array( true, $matches, true );
			case 'includes_all':
				return ! in_array( false, $matches, true );
			case 'excludes_any':
				return ! in_array( true, $matches, true );
			case 'excludes_all':
				return in_array( false, $matches, true );
			default:
				return false;
		}
	}

	/**
	 * Evaluate multi-value condition.
	 *
	 * @param string $operator The operator (includes_any, includes_all, etc.).
	 * @param mixed  $target_value The current value to check against.
	 * @param mixed  $condition_values The condition values (array or string).
	 * @return bool
	 */
	protected function evaluate_multi_value( $operator, $target_value, $condition_values ) {
		return $this->evaluate_multi_value_generic(
			$operator,
			$condition_values,
			function( $value ) use ( $target_value ) {
				return $this->values_match( $target_value, $value );
			}
		);
	}

	/**
	 * Evaluate multi-value condition for arrays (like post terms).
	 *
	 * @param string $operator The operator (includes_any, includes_all, etc.).
	 * @param array  $target_values Array of current values to check against.
	 * @param mixed  $condition_values The condition values (array or string).
	 * @return bool
	 */
	protected function evaluate_multi_value_array( $operator, $target_values, $condition_values ) {
		if ( ! is_array( $target_values ) ) {
			return false;
		}

		return $this->evaluate_multi_value_generic(
			$operator,
			$condition_values,
			function( $value ) use ( $target_values ) {
				foreach ( $target_values as $target_value ) {
					if ( $this->values_match( $target_value, $value ) ) {
						return true;
					}
				}
				return false;
			}
		);
	}

	/**
	 * Enhanced multi-value meta evaluation with error handling.
	 *
	 * @param string $operator The operator (includes_any, includes_all, etc.).
	 * @param mixed  $meta_value The meta value (could be string, array, or object).
	 * @param mixed  $condition_values The condition values (array or string).
	 * @return bool
	 */
	protected function evaluate_multi_value_meta( $operator, $meta_value, $condition_values ) {
		// Convert meta value to array if it's not already.
		$meta_values = [];
		if ( is_array( $meta_value ) ) {
			// Limit meta array size.
			if ( 1000 < count( $meta_value ) ) {
				$meta_value = array_slice( $meta_value, 0, 1000 );
			}
			$meta_values = array_map( 'sanitize_text_field', $meta_value );
		} elseif ( null !== $meta_value && '' !== $meta_value ) {
			if ( is_scalar( $meta_value ) ) {
				$meta_value_string = (string) $meta_value;

				// Try to decode JSON if it looks like JSON.
				if ( ( '{' === $meta_value_string[0] || '[' === $meta_value_string[0] ) && 50000 > strlen( $meta_value_string ) ) {
					$decoded = json_decode( $meta_value_string, true );
					if ( is_array( $decoded ) ) {
						if ( 1000 < count( $decoded ) ) {
							$decoded = array_slice( $decoded, 0, 1000 );
						}
						$meta_values = array_map( 'sanitize_text_field', $decoded );
					} else {
						$meta_values = [ sanitize_text_field( $meta_value_string ) ];
					}
				} else {
					$meta_values = [ sanitize_text_field( $meta_value_string ) ];
				}
			}
		}

		return $this->evaluate_multi_value_generic(
			$operator,
			$condition_values,
			function( $condition_value ) use ( $meta_values ) {
				foreach ( $meta_values as $current_meta_value ) {
					if ( $this->values_match( $current_meta_value, $condition_value ) ) {
						return true;
					}
				}
				return false;
			}
		);
	}

	/**
	 * Check if two values match (with loose comparison).
	 *
	 * @param mixed $value1 First value.
	 * @param mixed $value2 Second value.
	 * @return bool
	 */
	protected function values_match( $value1, $value2 ) {
		// Convert both to strings for comparison.
		return (string) $value1 === (string) $value2;
	}

	/**
	 * Compare values with type juggling for equals operator.
	 *
	 * @param mixed $value1 First value.
	 * @param mixed $value2 Second value.
	 * @return bool
	 */
	protected function compare_values_equals( $value1, $value2 ) {
		return $value1 == $value2; // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
	}

	/**
	 * Compare numeric values with enhanced validation.
	 *
	 * @param mixed  $value1 First value.
	 * @param mixed  $value2 Second value.
	 * @param string $operator Comparison operator (>, <).
	 * @return bool
	 */
	protected function compare_numeric( $value1, $value2, $operator ) {
		if ( ! in_array( $operator, [ '>', '<' ], true ) ) {
			return false;
		}

		// Handle edge cases for numeric comparison.
		if ( null === $value1 || null === $value2 ) {
			return false;
		}

		if ( ! is_numeric( $value1 ) || ! is_numeric( $value2 ) ) {
			return false;
		}

		// Check for potential overflow issues.
		if ( ! is_finite( (float) $value1 ) || ! is_finite( (float) $value2 ) ) {
			return false;
		}

		$float1 = floatval( $value1 );
		$float2 = floatval( $value2 );

		if ( '>' === $operator ) {
			return $float1 > $float2;
		} elseif ( '<' === $operator ) {
			return $float1 < $float2;
		}

		return false;
	}

	/**
	 * Enhanced value sanitization with better validation.
	 *
	 * @param mixed  $value The value to sanitize.
	 * @param string $rule  The rule being used.
	 * @return mixed
	 */
	public function sanitize_value( $value, $rule ) {
		if ( 'custom' === $rule ) {
			return $this->sanitize_custom_value( $value );
		}

		// Handle array values for multi-select.
		if ( is_array( $value ) ) {
			if ( 1000 < count( $value ) ) {
				$value = array_slice( $value, 0, 1000 );
			}
			return array_filter( array_map( 'sanitize_text_field', $value ) );
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Enhanced query parameter validation.
	 *
	 * @param string $param Parameter name.
	 * @return mixed
	 */
	protected function get_query_param( $param ) {
		if ( empty( $param ) || ! is_string( $param ) || 255 < strlen( $param ) ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET[ $param ] ) ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$value = wp_unslash( $_GET[ $param ] );

		// Handle arrays in query params.
		if ( is_array( $value ) ) {
			if ( 100 < count( $value ) ) {
				$value = array_slice( $value, 0, 100 );
			}
			return array_map( 'sanitize_text_field', $value );
		}

		// Prevent extremely long query values.
		if ( is_string( $value ) && 10000 < strlen( $value ) ) {
			$value = substr( $value, 0, 10000 );
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Enhanced input validation for server variables.
	 *
	 * @param string $var Variable name.
	 * @return string
	 */
	protected function get_server_var( $var ) {
		if ( empty( $var ) || ! is_string( $var ) || 100 < strlen( $var ) ) {
			return '';
		}

		// Whitelist allowed server variables for security.
		$allowed_vars = [
			'HTTP_USER_AGENT',
			'HTTP_REFERER',
			'REQUEST_METHOD',
			'REMOTE_ADDR',
			'HTTP_HOST',
			'REQUEST_URI',
			'QUERY_STRING',
		];

		if ( ! in_array( $var, $allowed_vars, true ) ) {
			return '';
		}

		if ( ! isset( $_SERVER[ $var ] ) ) {
			return '';
		}

		$value = wp_unslash( $_SERVER[ $var ] );

		// Type-specific validation for enhanced security.
		switch ( $var ) {
			case 'HTTP_REFERER':
				// Validate and sanitize as URL.
				$value = esc_url_raw( $value );
				break;

			case 'HTTP_USER_AGENT':
				// Remove control characters and normalize.
				$value = preg_replace( '/[\x00-\x1F\x7F]/', '', $value );
				$value = sanitize_text_field( $value );
				break;

			default:
				$value = sanitize_text_field( $value );
		}

		// Prevent extremely long values.
		if ( is_string( $value ) && 10000 < strlen( $value ) ) {
			$value = substr( $value, 0, 10000 );
		}

		return $value;
	}

	/**
	 * Enhanced cookie validation.
	 *
	 * @param string $cookie_name Cookie name.
	 * @return string|null
	 */
	protected function get_cookie_value( $cookie_name ) {
		if ( empty( $cookie_name ) || ! is_string( $cookie_name ) || 255 < strlen( $cookie_name ) ) {
			return null;
		}

		// Basic cookie name validation (prevent obvious XSS attempts).
		// Also reject browser-reserved cookie prefixes (__Host-, __Secure-).
		if ( ! preg_match( '/^[a-zA-Z0-9_-]+$/', $cookie_name ) || 0 === strpos( $cookie_name, '__' ) ) {
			return null;
		}

		if ( ! isset( $_COOKIE[ $cookie_name ] ) ) {
			return null;
		}

		$value = wp_unslash( $_COOKIE[ $cookie_name ] );

		// Prevent extremely long cookie values.
		if ( is_string( $value ) && 4096 < strlen( $value ) ) {
			$value = substr( $value, 0, 4096 );
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Check if cookie exists with validation.
	 *
	 * @param string $cookie_name Cookie name.
	 * @return bool
	 */
	protected function cookie_exists( $cookie_name ) {
		if ( empty( $cookie_name ) || ! is_string( $cookie_name ) || 255 < strlen( $cookie_name ) ) {
			return false;
		}

		// Reject browser-reserved cookie prefixes.
		if ( ! preg_match( '/^[a-zA-Z0-9_-]+$/', $cookie_name ) || 0 === strpos( $cookie_name, '__' ) ) {
			return false;
		}

		return isset( $_COOKIE[ $cookie_name ] );
	}

	/**
	 * Check if query parameter exists with validation.
	 *
	 * @param string $param_name Parameter name.
	 * @return bool
	 */
	protected function query_param_exists( $param_name ) {
		if ( empty( $param_name ) || ! is_string( $param_name ) || 255 < strlen( $param_name ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET[ $param_name ] );
	}

	/**
	 * Enhanced meta value check - determines if meta has a non-empty value.
	 *
	 * @param string $meta_type The meta type ('post', 'user', 'option').
	 * @param int    $object_id The object ID (post ID, user ID, etc.).
	 * @param string $meta_key  The meta key.
	 * @return bool
	 */
	protected function evaluate_meta_has_value( $meta_type, $object_id, $meta_key ) {
		// Validate inputs using same validation as existence check.
		if ( ! in_array( $meta_type, [ 'post', 'user', 'option' ], true ) ) {
			return false;
		}

		if ( empty( $meta_key ) || ! is_string( $meta_key ) || 255 < strlen( $meta_key ) ) {
			return false;
		}

		if ( 'option' !== $meta_type ) {
			if ( ! is_numeric( $object_id ) || 1 > $object_id || PHP_INT_MAX < $object_id ) {
				return false;
			}
			$object_id = absint( $object_id );
		}

		// Get the value.
		try {
			if ( 'option' === $meta_type ) {
				// For options, false means it doesn't exist.
				if ( false === get_option( $meta_key, false ) ) {
					return false;
				}
				$value = get_option( $meta_key );
			} elseif ( 'post' === $meta_type ) {
				// First check if it exists.
				if ( ! metadata_exists( $meta_type, $object_id, $meta_key ) ) {
					return false;
				}
				$value = get_post_meta( $object_id, $meta_key, true );
			} elseif ( 'user' === $meta_type ) {
				// First check if it exists.
				if ( ! metadata_exists( $meta_type, $object_id, $meta_key ) ) {
					return false;
				}
				$value = get_user_meta( $object_id, $meta_key, true );
			} else {
				return false;
			}
		} catch ( Exception $e ) {
			return false;
		}

		// Check for various "empty" states.
		if ( null === $value || '' === $value || false === $value ) {
			return false;
		}

		// Empty array is also "no value".
		if ( is_array( $value ) && 0 === count( $value ) ) {
			return false;
		}

		// Everything else is considered "has value" (including 0 and "0").
		return true;
	}

	/**
	 * Check if cookie has a non-empty value.
	 *
	 * @param string $cookie_name Cookie name.
	 * @return bool
	 */
	protected function cookie_has_value( $cookie_name ) {
		if ( ! $this->cookie_exists( $cookie_name ) ) {
			return false;
		}

		$value = $this->get_cookie_value( $cookie_name );

		// Check for various "empty" states.
		if ( null === $value || '' === $value || false === $value ) {
			return false;
		}

		// Everything else is considered "has value" (including 0 and "0").
		return true;
	}

	/**
	 * Check if query parameter has a non-empty value.
	 *
	 * @param string $param_name Parameter name.
	 * @return bool
	 */
	protected function query_param_has_value( $param_name ) {
		if ( ! $this->query_param_exists( $param_name ) ) {
			return false;
		}

		$value = $this->get_query_param( $param_name );

		// Check for various "empty" states.
		if ( null === $value || '' === $value || false === $value ) {
			return false;
		}

		// Empty array is also "no value".
		if ( is_array( $value ) && 0 === count( $value ) ) {
			return false;
		}

		// Everything else is considered "has value" (including 0 and "0").
		return true;
	}

	/**
	 * Check if referrer has a non-empty value.
	 *
	 * @return bool
	 */
	protected function referrer_has_value() {
		$referrer = $this->get_server_var( 'HTTP_REFERER' );

		// Check for various "empty" states.
		if ( null === $referrer || '' === $referrer || false === $referrer ) {
			return false;
		}

		// Everything else is considered "has value".
		return true;
	}

	/**
	 * Check if operator needs a value.
	 *
	 * @param string $operator The operator.
	 * @return bool
	 */
	protected function operator_needs_value( $operator ) {
		$no_value_operators = [ 'exists', 'not_exists', 'has_value', 'no_value' ];
		return ! in_array( $operator, $no_value_operators, true );
	}

	/**
	 * Check if a rule actually supports multi-value selection.
	 *
	 * @param string $rule The rule key.
	 * @return bool
	 */
	protected function rule_supports_multi_select( $rule ) {
		$metadata = $this->get_rule_metadata( $rule );

		// Only support multi-select if we have object selectors.
		$multi_select_types = [ 'object_selector', 'hierarchical_object_selector' ];

		return in_array( $metadata['value_type'] ?? '', $multi_select_types, true );
	}

	/**
	 * Get operators with intelligent filtering based on actual multi-select support.
	 *
	 * @param string $rule The rule key.
	 * @return array Array of operator keys.
	 */
	public function get_operators_for_rule( $rule ) {
		// Get base operators for this condition type.
		$types = GenerateBlocks_Pro_Conditions::get_condition_types();
		$class_name = get_class( $this );

		$base_operators = [];
		foreach ( $types as $type_data ) {
			if ( $type_data['class'] === $class_name ) {
				$base_operators = $type_data['operators'];
				break;
			}
		}

		// If this rule doesn't support multi-select, remove ALL "any/all" operators.
		if ( ! $this->rule_supports_multi_select( $rule ) ) {
			$multi_operators = [ 'includes_any', 'includes_all', 'excludes_any', 'excludes_all' ];
			$base_operators = array_diff( $base_operators, $multi_operators );
		}

		return $base_operators;
	}
}
