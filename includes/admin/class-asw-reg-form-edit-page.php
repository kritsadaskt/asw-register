<?php
/**
 * Form add/edit page logic.
 *
 * @package asw-register
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ASW_Reg_Form_Edit_Page
 */
class ASW_Reg_Form_Edit_Page {

	/**
	 * Handle the save form POST action.
	 */
	public static function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'asw-register' ) );
		}
		check_admin_referer( 'asw_reg_save_form' );

		$form_id = isset( $_POST['form_id'] ) ? (int) $_POST['form_id'] : 0;

		// -- General --
		$name            = sanitize_text_field( wp_unslash( $_POST['form_name'] ?? '' ) );
		$status          = in_array( $_POST['status'] ?? '', array( 'active', 'inactive' ), true ) ? $_POST['status'] : 'active';
		$success_message = wp_kses_post( wp_unslash( $_POST['success_message'] ?? '' ) );
		$redirect_url    = esc_url_raw( wp_unslash( $_POST['redirect_url'] ?? '' ) );

		$inject_post_id = isset( $_POST['inject_post_id'] ) ? (int) $_POST['inject_post_id'] : 0;
		if ( $inject_post_id > 0 && ! ASW_Reg_Page_Options::is_valid_inject_target( $inject_post_id ) ) {
			$inject_post_id = 0;
		}

		// -- Fields config --
		$fields_config = array();
		$registry      = ASW_Reg_Field_Registry::get_fields();
		foreach ( $registry as $key => $def ) {
			$fields_config[ $key ] = array(
				'enabled'     => isset( $_POST['field_enabled'][ $key ] ) ? 1 : 0,
				'required'    => isset( $_POST['field_required'][ $key ] ) ? 1 : 0,
				'label'       => sanitize_text_field( wp_unslash( $_POST['field_label'][ $key ] ?? $def['label'] ) ),
				'placeholder' => sanitize_text_field( wp_unslash( $_POST['field_placeholder'][ $key ] ?? ( $def['placeholder'] ?? '' ) ) ),
			);
		}

		// -- Custom Fields --
		$custom_fields    = array();
		$allowed_cf_types = array( 'text', 'email', 'tel', 'number', 'textarea', 'select', 'radio', 'checkbox' );
		$cf_raw           = isset( $_POST['fields']['custom_fields'] ) ? (array) wp_unslash( $_POST['fields']['custom_fields'] ) : array();
		foreach ( $cf_raw as $cf ) {
			$cf_key = sanitize_key( $cf['key'] ?? '' );
			$cf_lbl = sanitize_text_field( $cf['label'] ?? '' );
			if ( '' === $cf_key || '' === $cf_lbl ) {
				continue;
			}
			$cf_type         = in_array( $cf['type'] ?? 'text', $allowed_cf_types, true ) ? $cf['type'] : 'text';
			$custom_fields[] = array(
				'key'         => $cf_key,
				'label'       => $cf_lbl,
				'type'        => $cf_type,
				'required'    => (int) ( $cf['required'] ?? 0 ),
				'placeholder' => sanitize_text_field( $cf['placeholder'] ?? '' ),
				'options'     => sanitize_textarea_field( $cf['options'] ?? '' ),
			);
		}
		$fields_config['custom_fields'] = $custom_fields;

		// -- Field order --
		$field_order_raw = isset( $_POST['fields']['field_order'] ) ? sanitize_text_field( wp_unslash( $_POST['fields']['field_order'] ) ) : '';
		if ( $field_order_raw ) {
			$fields_config['field_order'] = array_values( array_filter( array_map( 'sanitize_key', explode( ',', $field_order_raw ) ) ) );
		}

		// -- API --
		$api_enabled  = isset( $_POST['api_enabled'] ) ? 1 : 0;
		$api_endpoint = esc_url_raw( wp_unslash( $_POST['api_endpoint'] ?? '' ) );
		$api_method   = in_array( strtoupper( $_POST['api_method'] ?? 'POST' ), array( 'POST', 'PUT', 'PATCH', 'GET' ), true ) ? strtoupper( $_POST['api_method'] ) : 'POST';

		// Build headers array from parallel key/value arrays.
		$api_headers = array();
		$header_keys   = isset( $_POST['api_header_key'] ) ? (array) $_POST['api_header_key'] : array();
		$header_values = isset( $_POST['api_header_value'] ) ? (array) $_POST['api_header_value'] : array();
		foreach ( $header_keys as $i => $hk ) {
			$hk = sanitize_text_field( wp_unslash( $hk ) );
			if ( '' !== $hk ) {
				$api_headers[] = array(
					'key'   => $hk,
					'value' => sanitize_text_field( wp_unslash( $header_values[ $i ] ?? '' ) ),
				);
			}
		}

		// -- Email --
		$email_enabled   = isset( $_POST['email_enabled'] ) ? 1 : 0;
		$email_subject   = sanitize_text_field( wp_unslash( $_POST['email_subject'] ?? '' ) );
		$email_body      = wp_kses_post( wp_unslash( $_POST['email_body'] ?? '' ) );
		$email_from_name = sanitize_text_field( wp_unslash( $_POST['email_from_name'] ?? '' ) );
		$email_from_addr = sanitize_email( wp_unslash( $_POST['email_from_addr'] ?? '' ) );

		$data = array(
			'name'            => $name,
			'status'          => $status,
			'fields_config'   => $fields_config,
			'api_enabled'     => $api_enabled,
			'api_endpoint'    => $api_endpoint,
			'api_method'      => $api_method,
			'api_headers'     => $api_headers,
			'email_enabled'   => $email_enabled,
			'email_subject'   => $email_subject,
			'email_body'      => $email_body,
			'email_from_name' => $email_from_name,
			'email_from_addr' => $email_from_addr,
			'success_message' => $success_message,
			'redirect_url'    => $redirect_url,
			'inject_post_id'  => $inject_post_id,
		);

		if ( $form_id ) {
			ASW_Reg_Form_Manager::update_form( $form_id, $data );
			wp_safe_redirect( admin_url( 'admin.php?page=asw-reg-form-edit&form_id=' . $form_id . '&saved=1' ) );
		} else {
			$data['slug'] = ASW_Reg_Form_Manager::generate_slug( $name );
			$new_id       = ASW_Reg_Form_Manager::create_form( $data );
			wp_safe_redirect( admin_url( 'admin.php?page=asw-reg-form-edit&form_id=' . $new_id . '&saved=1' ) );
		}
		exit;
	}
}
