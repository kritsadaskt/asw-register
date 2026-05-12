<?php
/**
 * AJAX form submission handler.
 *
 * @package asw-register
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ASW_Reg_Form_Handler
 */
class ASW_Reg_Form_Handler {

	/**
	 * Register AJAX hooks.
	 */
	public static function init() {
		add_action( 'wp_ajax_asw_reg_submit',        array( __CLASS__, 'handle_submission' ) );
		add_action( 'wp_ajax_nopriv_asw_reg_submit', array( __CLASS__, 'handle_submission' ) );
	}

	/**
	 * Process form submission.
	 */
	public static function handle_submission() {
		// 1. Validate nonce.
		$form_id = isset( $_POST['form_id'] ) ? (int) $_POST['form_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! $form_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'asw-register' ) ), 400 );
		}

		check_ajax_referer( 'asw_reg_submit_' . $form_id, 'nonce' );

		// 2. Load form.
		$form = ASW_Reg_Form_Manager::get_form( $form_id );
		if ( ! $form || 'active' !== $form['status'] ) {
			wp_send_json_error( array( 'message' => __( 'Form not found or inactive.', 'asw-register' ) ), 404 );
		}

		// 3. Get field config for this form.
		$fields_config = is_array( $form['fields_config'] ) ? $form['fields_config'] : array();
		$registry      = ASW_Reg_Field_Registry::get_fields();

		// 4. Sanitize and validate submitted fields.
		$core_data   = array();
		$extra_data  = array();
		$errors      = array();

		foreach ( $registry as $key => $field_def ) {
			$form_cfg = isset( $fields_config[ $key ] ) ? $fields_config[ $key ] : array();

			// Is the field enabled for this form?
			$enabled = isset( $form_cfg['enabled'] ) ? (bool) $form_cfg['enabled'] : (bool) ( $field_def['enabled'] ?? true );
			if ( ! $enabled ) {
				continue;
			}

			$required = isset( $form_cfg['required'] ) ? (bool) $form_cfg['required'] : (bool) ( $field_def['required'] ?? false );
			$label    = ! empty( $form_cfg['label'] ) ? $form_cfg['label'] : $field_def['label'];

			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$raw_value = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
			$value     = self::sanitize_field( $raw_value, $field_def['type'] ?? 'text' );

			if ( $required && '' === $value ) {
				/* translators: %s: field label */
				$errors[] = sprintf( __( '%s is required.', 'asw-register' ), $label );
			}

			// Email validation.
			if ( 'email' === ( $field_def['type'] ?? 'text' ) && '' !== $value && ! is_email( $value ) ) {
				/* translators: %s: field label */
				$errors[] = sprintf( __( '%s is not a valid email address.', 'asw-register' ), $label );
			}

			// Route to core column or extra_fields.
			if ( ASW_Reg_Field_Registry::has_db_column( $key ) ) {
				$column              = $field_def['db_column'];
				$core_data[ $column ] = $value;
			} else {
				$extra_data[ $key ] = $value;
			}
		}

		// 4b. Sanitize and validate custom fields.
		foreach ( $fields_config['custom_fields'] ?? array() as $cf ) {
			$cf_key      = $cf['key'] ?? '';
			$cf_type     = $cf['type'] ?? 'text';
			$cf_required = ! empty( $cf['required'] );
			$cf_label    = $cf['label'] ?? $cf_key;

			if ( '' === $cf_key ) {
				continue;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$raw = isset( $_POST['asw_cf'][ $cf_key ] ) ? wp_unslash( $_POST['asw_cf'][ $cf_key ] ) : '';

			// Handle array values (checkbox multi-select).
			if ( is_array( $raw ) ) {
				if ( $cf_required && empty( $raw ) ) {
					/* translators: %s: field label */
					$errors[] = sprintf( __( '%s is required.', 'asw-register' ), $cf_label );
					continue;
				}
				$sanitized = implode( ', ', array_map( 'sanitize_text_field', $raw ) );
			} else {
				if ( $cf_required && '' === $raw ) {
					/* translators: %s: field label */
					$errors[] = sprintf( __( '%s is required.', 'asw-register' ), $cf_label );
					continue;
				}
				$sanitized = self::sanitize_field( $raw, $cf_type );
			}

			$extra_data[ 'custom_' . $cf_key ] = $sanitized;
		}

		if ( ! empty( $errors ) ) {
			wp_send_json_error( array( 'message' => implode( ' ', $errors ) ), 422 );
		}

		// 5. Read UTMs server-side.
		$utms = ASW_Reg_UTM_Reader::get_utms();

		// 6. Build lead row.
		$lead_data = array_merge(
			array(
				'form_id'    => $form_id,
				'ip_address' => self::get_ip(),
				'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
				'page_url'   => isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			),
			$core_data,
			$utms,
			array( 'extra_fields' => $extra_data )
		);

		/**
		 * Filter: asw_reg_before_save_lead
		 *
		 * @param array $lead_data Lead data before DB insert.
		 * @param array $form      Form config.
		 */
		$lead_data = apply_filters( 'asw_reg_before_save_lead', $lead_data, $form );

		// 7. Save lead.
		$lead_id = ASW_Reg_Lead_Manager::create_lead( $lead_data );
		if ( ! $lead_id ) {
			wp_send_json_error( array( 'message' => __( 'Could not save your submission. Please try again.', 'asw-register' ) ), 500 );
		}

		/**
		 * Action: asw_reg_lead_saved
		 *
		 * @param int   $lead_id   Newly created lead ID.
		 * @param array $lead_data Lead data.
		 * @param array $form      Form config.
		 */
		do_action( 'asw_reg_lead_saved', $lead_id, $lead_data, $form );

		// Reload fresh lead row for API / email (includes db-generated fields).
		$lead = ASW_Reg_Lead_Manager::get_lead( $lead_id );

		// 8. API send.
		if ( ! empty( $form['api_enabled'] ) ) {
			$api_result = ASW_Reg_API_Connector::send( $lead_id, $lead, $form );
			ASW_Reg_Lead_Manager::update_api_status(
				$lead_id,
				$api_result['status'],
				$api_result['response'],
				$api_result['http_code']
			);
		}

		// 9. Email.
		if ( ! empty( $form['email_enabled'] ) ) {
			$email_status = ASW_Reg_Email_Sender::send_thank_you( $lead_id, $lead, $form );
			ASW_Reg_Lead_Manager::update_email_status( $lead_id, $email_status );
		}

		// 10. Build response.
		$success_message = ! empty( $form['success_message'] )
			? wp_kses_post( $form['success_message'] )
			: __( 'Thank you! Your submission has been received.', 'asw-register' );

		wp_send_json_success( array(
			'message'      => $success_message,
			'redirect_url' => ! empty( $form['redirect_url'] ) ? esc_url( $form['redirect_url'] ) : '',
		) );
	}

	/**
	 * Sanitize a field value based on type.
	 *
	 * @param string $value Raw value.
	 * @param string $type  Field type.
	 * @return string
	 */
	private static function sanitize_field( $value, $type ) {
		switch ( $type ) {
			case 'email':
				return sanitize_email( $value );
			case 'url':
				return esc_url_raw( $value );
			case 'textarea':
				return sanitize_textarea_field( $value );
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Get visitor IP address.
	 *
	 * @return string
	 */
	private static function get_ip() {
		$ip_keys = array(
			'HTTP_CF_CONNECTING_IP',  // Cloudflare.
			'HTTP_X_FORWARDED_FOR',
			'REMOTE_ADDR',
		);
		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				// X-Forwarded-For can be a comma-separated list.
				$ip = trim( explode( ',', $ip )[0] );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}
		return '';
	}
}
