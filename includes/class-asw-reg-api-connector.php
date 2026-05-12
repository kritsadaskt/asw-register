<?php
/**
 * Send lead data to an external API endpoint.
 *
 * @package asw-register
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ASW_Reg_API_Connector
 */
class ASW_Reg_API_Connector {

	/**
	 * Send lead data to the form's configured API endpoint.
	 *
	 * @param int   $lead_id Lead ID.
	 * @param array $lead    Full lead row.
	 * @param array $form    Full form row.
	 * @return array { status: string, response: string, http_code: int }
	 */
	public static function send( $lead_id, array $lead, array $form ) {
		if ( empty( $form['api_enabled'] ) || empty( $form['api_endpoint'] ) ) {
			return array(
				'status'    => 'skipped',
				'response'  => '',
				'http_code' => 0,
			);
		}

		// Build payload.
		$payload = array(
			'lead_id'    => $lead_id,
			'form_id'    => $lead['form_id'],
			'first_name' => $lead['first_name'],
			'last_name'  => $lead['last_name'],
			'email'      => $lead['email'],
			'tel'        => $lead['tel'],
			'utm_source'         => $lead['utm_source'],
			'utm_medium'         => $lead['utm_medium'],
			'utm_campaign'       => $lead['utm_campaign'],
			'utm_term'           => $lead['utm_term'],
			'utm_content'        => $lead['utm_content'],
			'utm_gclid'          => $lead['utm_gclid'],
			'handl_landing_page' => $lead['handl_landing_page'],
			'handl_original_ref' => $lead['handl_original_ref'],
		);

		// Merge extra_fields into payload.
		if ( ! empty( $lead['extra_fields'] ) && is_array( $lead['extra_fields'] ) ) {
			$payload = array_merge( $payload, $lead['extra_fields'] );
		}

		/**
		 * Filter: asw_reg_api_payload
		 *
		 * @param array $payload   Data to send.
		 * @param int   $lead_id   Lead ID.
		 * @param array $form      Form config.
		 */
		$payload = apply_filters( 'asw_reg_api_payload', $payload, $lead_id, $form );

		// Build headers.
		$headers = array(
			'Content-Type' => 'application/json',
			'Accept'       => 'application/json',
		);

		if ( ! empty( $form['api_headers'] ) && is_array( $form['api_headers'] ) ) {
			foreach ( $form['api_headers'] as $row ) {
				if ( ! empty( $row['key'] ) ) {
					$headers[ $row['key'] ] = isset( $row['value'] ) ? $row['value'] : '';
				}
			}
		}

		$method = strtoupper( $form['api_method'] ?? 'POST' );

		$args = array(
			'method'  => $method,
			'headers' => $headers,
			'body'    => wp_json_encode( $payload ),
			'timeout' => 15,
		);

		$response = wp_remote_request( esc_url_raw( $form['api_endpoint'] ), $args );

		if ( is_wp_error( $response ) ) {
			return array(
				'status'    => 'error',
				'response'  => $response->get_error_message(),
				'http_code' => 0,
			);
		}

		$http_code     = (int) wp_remote_retrieve_response_code( $response );
		$body          = wp_remote_retrieve_body( $response );
		$is_success    = $http_code >= 200 && $http_code < 300;

		return array(
			'status'    => $is_success ? 'success' : 'error',
			'response'  => $body,
			'http_code' => $http_code,
		);
	}
}
