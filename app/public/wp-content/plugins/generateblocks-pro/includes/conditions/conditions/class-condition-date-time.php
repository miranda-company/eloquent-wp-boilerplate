<?php
/**
 * Date Time Condition
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Date/time condition evaluator.
 */
class GenerateBlocks_Pro_Condition_Date_Time extends GenerateBlocks_Pro_Condition_Abstract {
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
		// Handle day of week separately with multi-select support.
		if ( 'day_of_week' === $rule ) {
			return $this->evaluate_day_of_week( $operator, $value );
		}

		// Get current time as DateTime object for proper timezone handling.
		$current_datetime = current_datetime();

		if ( empty( $value ) ) {
			return false;
		}

		// Handle time-only rules (current_time and time_of_day).
		if ( in_array( $rule, [ 'current_time', 'time_of_day' ], true ) ) {
			return $this->evaluate_time_only( $current_datetime, $operator, $value );
		}

		// For date-based rules, use full timestamp comparison.
		$current_time = $current_datetime->getTimestamp();

		switch ( $operator ) {
			case 'before':
				return $this->is_before( $current_time, $value );

			case 'after':
				return $this->is_after( $current_time, $value );

			case 'on':
				return $this->is_on( $current_time, $value, $rule );

			case 'between':
				return $this->is_between( $current_time, $value );

			default:
				return false;
		}
	}

	/**
	 * Evaluate time-only condition (ignoring date).
	 *
	 * @param DateTime $current_datetime Current DateTime object.
	 * @param string   $operator         The condition operator.
	 * @param mixed    $value            The value to check against.
	 * @return bool
	 */
	private function evaluate_time_only( $current_datetime, $operator, $value ) {
		// Convert current time to seconds since midnight.
		$current_hours = (int) $current_datetime->format( 'H' );
		$current_minutes = (int) $current_datetime->format( 'i' );
		$current_seconds_component = (int) $current_datetime->format( 's' );

		$current_seconds = ( $current_hours * HOUR_IN_SECONDS ) +
			( $current_minutes * MINUTE_IN_SECONDS ) +
			$current_seconds_component;

		switch ( $operator ) {
			case 'before':
				$target_seconds = $this->parse_time_to_seconds( $value );
				return false !== $target_seconds && $current_seconds < $target_seconds;

			case 'after':
				$target_seconds = $this->parse_time_to_seconds( $value );
				return false !== $target_seconds && $current_seconds > $target_seconds;

			case 'on':
				// For time "on", check if within the same hour.
				$target_seconds = $this->parse_time_to_seconds( $value );
				if ( false === $target_seconds ) {
					return false;
				}

				$target_hour = intval( $target_seconds / HOUR_IN_SECONDS );
				return $current_hours === $target_hour;

			case 'between':
				// Handle time range (e.g., "09:00, 17:00" or "22:00, 02:00" for overnight).
				$times = array_map( 'trim', explode( ',', $value ) );
				if ( 2 !== count( $times ) ) {
					return false;
				}

				$start_seconds = $this->parse_time_to_seconds( $times[0] );
				$end_seconds = $this->parse_time_to_seconds( $times[1] );

				if ( false === $start_seconds || false === $end_seconds ) {
					return false;
				}

				// Handle overnight ranges (e.g., 22:00 to 02:00).
				if ( $start_seconds > $end_seconds ) {
					// Current time is either after start OR before end.
					return $current_seconds >= $start_seconds || $current_seconds <= $end_seconds;
				} else {
					// Normal range within same day.
					return $current_seconds >= $start_seconds && $current_seconds <= $end_seconds;
				}

			default:
				return false;
		}
	}

	/**
	 * Parse time string to seconds since midnight.
	 *
	 * @param string $time_str Time string (HH:MM or full datetime).
	 * @return int|false Seconds since midnight or false on failure.
	 */
	private function parse_time_to_seconds( $time_str ) {
		if ( empty( $time_str ) ) {
			return false;
		}

		// For time-only values (HH:MM format), parse directly in WordPress timezone
		// to avoid timezone conversion issues.
		try {
			// Create DateTime in WordPress timezone directly.
			$datetime = new DateTime( $time_str, wp_timezone() );
		} catch ( Exception $e ) {
			// If parsing fails, try with today's date prepended.
			try {
				$today = current_datetime()->format( 'Y-m-d' );
				$datetime = new DateTime( $today . ' ' . $time_str, wp_timezone() );
			} catch ( Exception $e2 ) {
				return false;
			}
		}

		// Extract time components.
		$hours = (int) $datetime->format( 'H' );
		$minutes = (int) $datetime->format( 'i' );
		$seconds = (int) $datetime->format( 's' );

		// Calculate seconds since midnight.
		$result = ( $hours * HOUR_IN_SECONDS ) + ( $minutes * MINUTE_IN_SECONDS ) + $seconds;

		return $result;
	}

	/**
	 * Evaluate day of week condition.
	 *
	 * @param string $operator The condition operator.
	 * @param mixed  $value    The value to check against.
	 * @return bool
	 */
	private function evaluate_day_of_week( $operator, $value ) {
		// Get current day of week (1 = Monday, 7 = Sunday).
		$current_day = intval( current_datetime()->format( 'N' ) );

		// Handle multi-value operators.
		if ( $this->is_multi_value_operator( $operator ) ) {
			return $this->evaluate_multi_value( $operator, $current_day, $value );
		}

		// Parse value for single operators.
		$target_day = intval( $value );

		switch ( $operator ) {
			case 'is':
				return $current_day === $target_day;

			case 'is_not':
				return $current_day !== $target_day;

			default:
				return false;
		}
	}

	/**
	 * Check if current time is before target time.
	 *
	 * @param int    $current_time Current timestamp.
	 * @param string $target_time  Target time string.
	 * @return bool
	 */
	private function is_before( $current_time, $target_time ) {
		try {
			// Parse the target time using WordPress timezone.
			$target_datetime = new DateTime( $target_time, wp_timezone() );
			$compare_time = $target_datetime->getTimestamp();
			return $current_time < $compare_time;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Check if current time is after target time.
	 *
	 * @param int    $current_time Current timestamp.
	 * @param string $target_time  Target time string.
	 * @return bool
	 */
	private function is_after( $current_time, $target_time ) {
		try {
			// Parse the target time using WordPress timezone.
			$target_datetime = new DateTime( $target_time, wp_timezone() );
			$compare_time = $target_datetime->getTimestamp();
			return $current_time > $compare_time;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Check if current time is on target date.
	 *
	 * @param int    $current_time Current timestamp.
	 * @param string $target_time  Target time string.
	 * @param string $rule         The rule type.
	 * @return bool
	 */
	private function is_on( $current_time, $target_time, $rule ) {
		try {
			// Parse the target time using WordPress timezone.
			$target_datetime = new DateTime( $target_time, wp_timezone() );
			$current_datetime = new DateTime();
			$current_datetime->setTimestamp( $current_time );
			$current_datetime->setTimezone( wp_timezone() );

			if ( 'current_date' === $rule ) {
				// Compare dates in WordPress timezone.
				return $current_datetime->format( 'Y-m-d' ) === $target_datetime->format( 'Y-m-d' );
			}

			// For time comparison, check if within the same hour.
			return $current_datetime->format( 'Y-m-d H' ) === $target_datetime->format( 'Y-m-d H' );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Check if current time is between two times.
	 *
	 * @param int    $current_time Current timestamp.
	 * @param string $value        Comma-separated start and end times.
	 * @return bool
	 */
	private function is_between( $current_time, $value ) {
		$dates = array_map( 'trim', explode( ',', $value ) );
		if ( 2 !== count( $dates ) ) {
			return false;
		}

		try {
			// Parse both dates using WordPress timezone.
			$start_datetime = new DateTime( $dates[0], wp_timezone() );
			$end_datetime = new DateTime( $dates[1], wp_timezone() );

			$start_time = $start_datetime->getTimestamp();
			$end_time = $end_datetime->getTimestamp();

			return $current_time >= $start_time && $current_time <= $end_time;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Get available rules for this condition type.
	 *
	 * @return array
	 */
	public function get_rules() {
		$rules = [
			'current_date' => __( 'Date', 'generateblocks-pro' ),
			'current_time' => __( 'Time', 'generateblocks-pro' ),
			'day_of_week'  => __( 'Day of Week', 'generateblocks-pro' ),
			'time_of_day'  => __( 'Time of Day', 'generateblocks-pro' ), // Hidden in UI, kept for backward compatibility.
		];

		return apply_filters( 'generateblocks_date_time_rules', $rules );
	}

	/**
	 * Get metadata for a specific rule.
	 *
	 * @param string $rule The rule key.
	 * @return array
	 */
	public function get_rule_metadata( $rule ) {
		if ( 'day_of_week' === $rule ) {
			return [
				'needs_value'     => true,
				'value_type'      => 'day_selector',
				'supports_multi'  => true,
			];
		}

		// Time-only rules use time picker.
		if ( in_array( $rule, [ 'current_time', 'time_of_day' ], true ) ) {
			return [
				'needs_value' => true,
				'value_type'  => 'time',
			];
		}

		// Date rule uses datetime picker.
		return [
			'needs_value' => true,
			'value_type'  => 'datetime',
		];
	}

	/**
	 * Get operators available for a specific date/time rule.
	 *
	 * @param string $rule The rule key.
	 * @return array Array of operator keys.
	 */
	public function get_operators_for_rule( $rule ) {
		if ( 'day_of_week' === $rule ) {
			return [ 'is', 'is_not', 'includes_any', 'excludes_any' ];
		}

		// Other date/time rules use temporal operators.
		return [ 'before', 'after', 'between', 'on' ];
	}

	/**
	 * Get available day options.
	 *
	 * @return array
	 */
	public function get_day_options() {
		return [
			'1' => __( 'Monday', 'generateblocks-pro' ),
			'2' => __( 'Tuesday', 'generateblocks-pro' ),
			'3' => __( 'Wednesday', 'generateblocks-pro' ),
			'4' => __( 'Thursday', 'generateblocks-pro' ),
			'5' => __( 'Friday', 'generateblocks-pro' ),
			'6' => __( 'Saturday', 'generateblocks-pro' ),
			'7' => __( 'Sunday', 'generateblocks-pro' ),
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
		if ( 'day_of_week' === $rule ) {
			// Handle array values for multi-select.
			if ( is_array( $value ) ) {
				return array_map( 'intval', $value );
			}

			// Try to decode JSON array.
			$decoded = json_decode( $value, true );
			if ( is_array( $decoded ) ) {
				return wp_json_encode( array_map( 'intval', $decoded ) );
			}

			// Single value.
			return intval( $value );
		}

		// For between operator, ensure proper format.
		if ( false !== strpos( $value, ',' ) ) {
			$dates = array_map( 'trim', explode( ',', $value ) );
			$sanitized_dates = array_map( 'sanitize_text_field', $dates );
			return implode( ', ', $sanitized_dates );
		}

		return sanitize_text_field( $value );
	}
}
