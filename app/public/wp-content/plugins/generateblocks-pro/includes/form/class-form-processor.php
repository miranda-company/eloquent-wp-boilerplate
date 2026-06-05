<?php
/**
 * Form submission processor.
 *
 * Reads form config from the `gblocks_form` post meta and derives the field
 * contract from the form post's saved block content at submit time. Running
 * through a single authoritative record by form post ID means template
 * parts, widgets, reusable blocks, and FSE templates all submit identically
 * with no host-page ambiguity or cached schema drift.
 *
 * Order:
 *   1. Load config by $form_id (post ID).
 *   2. Derive the field schema from saved form content.
 *   3. Enforce server-side Turnstile requirement if configured.
 *   4. Run conditional-visibility convergence against the derived schema.
 *   5. Sanitize fields through the server-derived schema.
 *   6. Store a bounded submission record when the form opts in.
 *   7. Run registered actions and update the stored submission status.
 *
 * @package GenerateBlocksPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form processor class.
 */
class GenerateBlocks_Pro_Form_Processor {

	/**
	 * Process a form submission.
	 *
	 * @param int   $form_id  gblocks_form post ID.
	 * @param int   $post_id  Host page ID where the form was submitted.
	 * @param array $raw_data Raw submitted field data (field_name => value).
	 * @param array $context  Request context (post_id, page_url, turnstile_token?).
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public static function process( $form_id, $post_id, $raw_data, $context = [] ) {
		$form_id = absint( $form_id );

		if ( ! $form_id ) {
			return new WP_Error( 'invalid_form_id', 'Invalid form ID.' );
		}

		$form = get_post( $form_id );

		if (
			! $form
			|| GenerateBlocks_Pro_Form_Post_Type::POST_TYPE !== $form->post_type
			|| 'publish' !== $form->post_status
		) {
			return new WP_Error( 'form_not_found', 'Form not found.' );
		}

		$meta = get_post_meta( $form_id, GenerateBlocks_Pro_Form_Post_Type::META_KEY, true );
		$meta = is_array( $meta ) ? $meta : [];

		$form_settings = wp_parse_args(
			isset( $meta['config'] ) && is_array( $meta['config'] )
				? $meta['config']
				: [],
			GenerateBlocks_Pro_Form_Post_Type::default_form_config()
		);
		$actions = isset( $form_settings['actions'] ) && is_array( $form_settings['actions'] )
			? $form_settings['actions']
			: [];

		if ( empty( $actions ) ) {
			$public_message = self::incomplete_configuration_public_message();

			return new WP_Error(
				'form_actions_empty',
				$public_message,
				[
					'public_message' => $public_message,
				]
			);
		}

		if ( ! class_exists( 'GenerateBlocks_Pro_Form_Schema_Builder' ) ) {
			return new WP_Error( 'form_schema_unavailable', 'Form schema unavailable.' );
		}

		$field_schema_list = GenerateBlocks_Pro_Form_Schema_Builder::get_instance()->build_schema_from_content( $form->post_content );

		if ( is_wp_error( $field_schema_list ) ) {
			return $field_schema_list;
		}

		if ( empty( $field_schema_list ) ) {
			return new WP_Error( 'form_schema_empty', 'Form has no fields.' );
		}

		// Server-side Turnstile enforcement: require a token only when the
		// integration is fully configured (both secret and site key present).
		// A secret-only configuration can't render the widget, so the token
		// would never arrive and every submission would be rejected — the
		// editor UI explicitly promises fail-open until both keys are set.
		if (
			! empty( $form_settings['useTurnstile'] )
			&& '' !== GenerateBlocks_Pro_Form_Turnstile::get_secret_key()
			&& '' !== GenerateBlocks_Pro_Form_Turnstile::get_site_key()
			&& ! array_key_exists( 'turnstile_token', $context )
		) {
			return new WP_Error( 'turnstile_required', 'Turnstile verification required.' );
		}

		// Convert the indexed schema list (order-preserving) into the
		// associative map the downstream convergence/sanitizer expect.
		$field_schema = [];

		foreach ( $field_schema_list as $entry ) {
			if ( ! is_array( $entry ) || empty( $entry['name'] ) ) {
				continue;
			}

			$field_schema[ $entry['name'] ] = self::schema_entry_to_map( $entry );
		}

		// Evaluate conditional visibility server-side (see resolve_hidden_fields
		// for the convergence contract). Fields that remain hidden after
		// convergence are stripped from both the submitted data and the schema
		// so the sanitizer never sees them.
		$hidden_fields = self::resolve_hidden_fields( $field_schema, $raw_data );

		foreach ( $hidden_fields as $name ) {
			unset( $raw_data[ $name ] );
			unset( $field_schema[ $name ] );
		}

		$sanitized = GenerateBlocks_Pro_Form_Sanitizer::sanitize_submission( $raw_data, $field_schema );

		if ( is_wp_error( $sanitized ) ) {
			return $sanitized;
		}

		/**
		 * Filter to validate sanitized form data before actions run.
		 *
		 * @since 2.6.0
		 * @param array $sanitized     Sanitized field data.
		 * @param int   $form_id       Form post ID.
		 * @param array $form_settings Form config from meta.
		 * @param array $context       Request context.
		 */
		$sanitized = apply_filters(
			'generateblocks_form_validate_submission',
			$sanitized,
			$form_id,
			$form_settings,
			$context
		);

		if ( is_wp_error( $sanitized ) ) {
			return $sanitized;
		}

		$submission_id = false;

		if ( ! empty( $form_settings['storeSubmissions'] ) ) {
			$submission_id = GenerateBlocks_Pro_Form_Submissions::insert_received(
				$form_id,
				$post_id,
				$sanitized
			);
		}

		$preflight_errors = self::validate_actions_before_execution(
			$actions,
			$form_settings,
			$field_schema_list,
			$sanitized
		);

		if ( ! empty( $preflight_errors ) ) {
			return self::handle_action_failures( $form_id, $post_id, $sanitized, $preflight_errors, $submission_id );
		}

		$errors = [];
		$has_blocking_action = self::has_blocking_actions( $actions );

		foreach ( $actions as $action_name ) {
			if ( ! is_string( $action_name ) ) {
				continue;
			}

			$callback = GenerateBlocks_Pro_Form_Action_Registry::get( $action_name );

			if ( ! $callback ) {
				if ( $has_blocking_action && self::is_non_blocking_action( $action_name ) ) {
					continue;
				}

				// Unknown/deactivated action: treat as failure so a stored
				// submission is marked failed. Silent skip would hide data loss.
				$errors[] = 'Unknown action: ' . sanitize_key( $action_name );

				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log(
					'GenerateBlocks Form: unknown action "' . sanitize_key( $action_name )
					. '" in form ' . $form_id
				);

				continue;
			}

			$result = call_user_func( $callback, $sanitized, $form_settings, $context );

			if ( is_wp_error( $result ) ) {
				if ( $has_blocking_action && self::is_non_blocking_action( $action_name ) ) {
					continue;
				}

				$errors[] = $result;
			}
		}

		if ( ! empty( $errors ) ) {
			return self::handle_action_failures( $form_id, $post_id, $sanitized, $errors, $submission_id );
		}

		if ( $submission_id ) {
			GenerateBlocks_Pro_Form_Submissions::update_status( $submission_id, 'processed', null, $form_id );
		}

		GenerateBlocks_Pro_Form_Post_Type::record_delivery_success( $form_id );

		return true;
	}

	/**
	 * Validate built-in action configuration before any action can run.
	 *
	 * This prevents partial delivery when an earlier action has a side effect
	 * and a later action is guaranteed to fail from missing configuration.
	 *
	 * @param array $actions           Action names from form config.
	 * @param array $form_settings     Form config from meta.
	 * @param array $field_schema_list Derived field schema list.
	 * @param array $sanitized_data    Sanitized submitted data.
	 * @return array Error messages or WP_Error instances.
	 */
	private static function validate_actions_before_execution( $actions, $form_settings, $field_schema_list, $sanitized_data ) {
		$errors              = [];
		$has_blocking_action = self::has_blocking_actions( $actions );

		foreach ( $actions as $action_name ) {
			if ( ! is_string( $action_name ) ) {
				continue;
			}

			$action_name = sanitize_key( $action_name );
			$callback    = GenerateBlocks_Pro_Form_Action_Registry::get( $action_name );

			if ( $has_blocking_action && self::is_non_blocking_action( $action_name ) ) {
				continue;
			}

			if ( ! $callback ) {
				$errors[] = 'Unknown action: ' . $action_name;

				continue;
			}

			$validation = self::validate_builtin_action_before_execution(
				$action_name,
				$form_settings,
				$field_schema_list,
				$sanitized_data
			);

			if ( is_wp_error( $validation ) ) {
				$errors[] = $validation;
			}
		}

		return $errors;
	}

	/**
	 * Validate configuration/data for built-in actions with known prerequisites.
	 *
	 * Third-party actions still run through the normal callback result handling.
	 *
	 * @param string $action_name       Sanitized action name.
	 * @param array  $form_settings     Form config from meta.
	 * @param array  $field_schema_list Derived field schema list.
	 * @param array  $sanitized_data    Sanitized submitted data.
	 * @return true|WP_Error
	 */
	private static function validate_builtin_action_before_execution( $action_name, $form_settings, $field_schema_list, $sanitized_data ) {
		switch ( $action_name ) {
			case 'email':
				if ( class_exists( 'GenerateBlocks_Pro_Form_Action_Email' ) ) {
					return GenerateBlocks_Pro_Form_Action_Email::validate_settings( $form_settings );
				}

				break;

			case 'webhook':
				if ( class_exists( 'GenerateBlocks_Pro_Form_Action_Webhook' ) ) {
					return GenerateBlocks_Pro_Form_Action_Webhook::validate_settings( $form_settings );
				}

				break;

			case 'confirmation-email':
				if ( class_exists( 'GenerateBlocks_Pro_Form_Action_Confirmation_Email' ) ) {
					$settings_check = GenerateBlocks_Pro_Form_Action_Confirmation_Email::validate_settings(
						$form_settings,
						$field_schema_list
					);

					if ( is_wp_error( $settings_check ) ) {
						return $settings_check;
					}

					return GenerateBlocks_Pro_Form_Action_Confirmation_Email::validate_submission(
						$sanitized_data,
						$form_settings
					);
				}

				break;

			case 'email-signup':
				if ( class_exists( 'GenerateBlocks_Pro_Form_Action_Email_Signup' ) ) {
					$settings = isset( $form_settings['actionSettings'][ GenerateBlocks_Pro_Form_Action_Email_Signup::ACTION ] )
						? $form_settings['actionSettings'][ GenerateBlocks_Pro_Form_Action_Email_Signup::ACTION ]
						: [];

					$settings_check = GenerateBlocks_Pro_Form_Action_Email_Signup::validate_settings(
						$settings,
						$field_schema_list
					);

					if ( is_wp_error( $settings_check ) ) {
						return self::with_public_failure_message(
							$settings_check,
							self::incomplete_configuration_public_message()
						);
					}

					return GenerateBlocks_Pro_Form_Action_Email_Signup::validate_submission(
						$sanitized_data,
						$settings
					);
				}

				break;
		}

		return true;
	}

	/**
	 * Check whether an action is secondary and should not fail other delivery.
	 *
	 * @param string $action_name Action name.
	 * @return bool
	 */
	private static function is_non_blocking_action( $action_name ) {
		return 'confirmation-email' === sanitize_key( (string) $action_name );
	}

	/**
	 * Check whether the action list includes at least one primary delivery action.
	 *
	 * @param array $actions Action names.
	 * @return bool
	 */
	private static function has_blocking_actions( $actions ) {
		foreach ( $actions as $action_name ) {
			if ( is_string( $action_name ) && ! self::is_non_blocking_action( $action_name ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Persist failed action delivery and return a public-safe error.
	 *
	 * @param int   $form_id        gblocks_form post ID.
	 * @param int   $post_id        Host page ID.
	 * @param array $sanitized_data Sanitized submitted data.
	 * @param array $errors         Action error messages or WP_Error instances.
	 * @param int   $submission_id  Stored submission ID, if storage succeeded.
	 * @return WP_Error
	 */
	private static function handle_action_failures( $form_id, $post_id, $sanitized_data, $errors, $submission_id = 0 ) {
		$error_messages = self::normalize_failure_messages( $errors );
		unset( $post_id, $sanitized_data );

		if ( $submission_id ) {
			GenerateBlocks_Pro_Form_Submissions::update_status( $submission_id, 'failed', $error_messages, $form_id );
		}

		GenerateBlocks_Pro_Form_Post_Type::record_delivery_failure( $form_id, $errors );

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log(
			'GenerateBlocks Form Error [' . $form_id . ']: ' . implode( '; ', $error_messages )
		);

		$public_message = self::get_public_failure_message( $errors );

		if ( '' !== $public_message ) {
			return new WP_Error(
				'action_failed',
				$public_message,
				[
					'public_message' => $public_message,
				]
			);
		}

		return new WP_Error( 'action_failed', 'One or more actions failed.' );
	}

	/**
	 * Public-safe message for forms that are published before setup is complete.
	 *
	 * @return string
	 */
	private static function incomplete_configuration_public_message() {
		return __( 'This form is not fully configured. Please contact the site administrator.', 'generateblocks-pro' );
	}

	/**
	 * Attach a public-safe message while preserving the admin-facing error.
	 *
	 * @param WP_Error $error          Original error.
	 * @param string   $public_message Message safe to show to submitters.
	 * @return WP_Error
	 */
	private static function with_public_failure_message( WP_Error $error, $public_message ) {
		$existing_data = $error->get_error_data();
		$data          = is_array( $existing_data ) ? $existing_data : [];

		if ( null !== $existing_data && ! is_array( $existing_data ) ) {
			$data['private_data'] = $existing_data;
		}

		$data['public_message'] = sanitize_text_field( (string) $public_message );

		$error->add_data( $data, $error->get_error_code() );

		return $error;
	}

	/**
	 * Convert mixed action failures to admin-facing strings.
	 *
	 * @param array $errors Action error messages or WP_Error instances.
	 * @return array
	 */
	private static function normalize_failure_messages( $errors ) {
		$messages = [];

		foreach ( $errors as $error ) {
			if ( is_wp_error( $error ) ) {
				$error = $error->get_error_message();
			}

			if ( is_string( $error ) && '' !== $error ) {
				$messages[] = $error;
			}
		}

		return $messages;
	}

	/**
	 * Read the first explicitly public-safe failure message.
	 *
	 * @param array $errors Action error messages or WP_Error instances.
	 * @return string
	 */
	private static function get_public_failure_message( $errors ) {
		foreach ( $errors as $error ) {
			if ( ! is_wp_error( $error ) ) {
				continue;
			}

			$data = $error->get_error_data();

			if ( ! is_array( $data ) || empty( $data['public_message'] ) || ! is_string( $data['public_message'] ) ) {
				continue;
			}

			return sanitize_text_field( $data['public_message'] );
		}

		return '';
	}

	/**
	 * Resolve which fields remain hidden after conditional-visibility convergence.
	 *
	 * Contract: every field with non-empty `conditions` starts hidden. On each
	 * pass we build an effective data map where currently-hidden fields
	 * contribute empty values, then calculate the next visibility state for all
	 * conditional fields from that snapshot. Applying changes after the full
	 * pass matches the frontend's fixed-point model and avoids DOM/schema order
	 * deciding which fields submit.
	 *
	 * Matching the frontend's start-hidden model here means cascading
	 * conditions (A reveals B reveals C) resolve the same way regardless
	 * of schema order, and a browser that disables JS submits exactly the
	 * fields the server expects.
	 *
	 * Public so unit tests can exercise the convergence in isolation.
	 *
	 * @param array $field_schema Associative field_name => config map.
	 * @param array $raw_data     Raw user-submitted data.
	 * @return array Names of fields that remain hidden after convergence.
	 */
	public static function resolve_hidden_fields( $field_schema, $raw_data ) {
		$visibility = [];

		foreach ( $field_schema as $name => $config ) {
			$visibility[ $name ] = empty( $config['conditions'] );
		}

		$passes     = 0;
		$max_passes = max( 10, count( $visibility ) + 1 );

		do {
			$changed        = false;
			$effective_data = $raw_data;

			foreach ( $visibility as $field_name => $is_visible ) {
				if ( ! $is_visible ) {
					$effective_data[ $field_name ] = '';
				}
			}

			$next_visibility = $visibility;

			foreach ( $field_schema as $name => $config ) {
				if ( empty( $config['conditions'] ) ) {
					$next_visibility[ $name ] = true;
					continue;
				}

				$next_visibility[ $name ] = self::evaluate_conditions( $config['conditions'], $field_schema, $effective_data );
			}

			foreach ( $next_visibility as $name => $is_visible ) {
				if ( ( $visibility[ $name ] ?? false ) !== $is_visible ) {
					$changed = true;
					break;
				}
			}

			$visibility = $next_visibility;

			$passes++;
		} while ( $changed && $passes < $max_passes );

		// Hitting the cap with state still changing means a circular or
		// pathologically deep condition graph. Log so an admin debugging "field
		// never reveals" has a signal — conditional fields fail closed, which is
		// the safe default for submission validation.
		if ( $changed ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log(
				'GenerateBlocks Form: conditional visibility did not converge in '
				. $max_passes . ' passes; conditional fields failed closed.'
			);

			foreach ( $field_schema as $name => $config ) {
				if ( ! empty( $config['conditions'] ) ) {
					$visibility[ $name ] = false;
				}
			}
		}

		$hidden_fields = [];

		foreach ( $visibility as $name => $is_visible ) {
			if ( ! $is_visible ) {
				$hidden_fields[] = $name;
			}
		}

		return $hidden_fields;
	}

	/**
	 * Convert a schema-list entry into the map shape the sanitizer expects.
	 *
	 * Schema list uses `options` as array-of-{value,label}; the sanitizer
	 * wants `allowedValues` as a flat array of strings.
	 *
	 * @param array $entry Schema entry.
	 * @return array
	 */
	private static function schema_entry_to_map( $entry ) {
		$map = [
			'type'     => $entry['type'] ?? 'text',
			'required' => ! empty( $entry['required'] ),
			'label'    => $entry['label'] ?? $entry['name'] ?? '',
		];

		if ( isset( $entry['options'] ) && is_array( $entry['options'] ) ) {
			$values = [];

			foreach ( $entry['options'] as $option ) {
				if ( isset( $option['value'] ) && '' !== $option['value'] ) {
					$values[] = (string) $option['value'];
				}
			}

			$map['allowedValues'] = $values;
		}

		if ( isset( $entry['checkedValue'] ) ) {
			$map['checkedValue'] = $entry['checkedValue'];
		}

		if ( isset( $entry['conditions'] ) && is_array( $entry['conditions'] ) ) {
			$map['conditions'] = $entry['conditions'];
		}

		if ( isset( $entry['matchesField'] ) && is_string( $entry['matchesField'] ) ) {
			$map['matchesField'] = $entry['matchesField'];
		}

		return $map;
	}

	/**
	 * Evaluate conditions for a field against submitted data.
	 *
	 * All conditions must be met (AND logic). If any trigger field is missing
	 * from the schema, the condition is treated as not met.
	 *
	 * @param array $conditions   Conditions.
	 * @param array $field_schema Full schema.
	 * @param array $raw_data     Raw data.
	 * @return bool
	 */
	private static function evaluate_conditions( $conditions, $field_schema, $raw_data ) {
		foreach ( $conditions as $condition ) {
			$trigger_name = $condition['field'] ?? '';
			$operator     = $condition['operator'] ?? 'is';
			$expected     = $condition['value'] ?? '';

			if ( ! $trigger_name || ! isset( $field_schema[ $trigger_name ] ) ) {
				return false;
			}

			$actual = $raw_data[ $trigger_name ] ?? '';
			$is_arr = is_array( $actual );

			if ( ! $is_arr ) {
				$actual = sanitize_text_field( (string) $actual );
			}

			switch ( $operator ) {
				case 'is':
					if ( $is_arr ? ! in_array( $expected, $actual, true ) : $actual !== $expected ) {
						return false;
					}
					break;

				case 'isnot':
					if ( $is_arr ? in_array( $expected, $actual, true ) : $actual === $expected ) {
						return false;
					}
					break;

				case 'isempty':
					if ( $is_arr ? ! empty( $actual ) : '' !== trim( $actual ) ) {
						return false;
					}
					break;

				case 'isnotempty':
					if ( $is_arr ? empty( $actual ) : '' === trim( $actual ) ) {
						return false;
					}
					break;

				default:
					return false;
			}
		}

		return true;
	}
}
