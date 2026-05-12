<?php
/**
 * Send thank-you emails via wp_mail().
 *
 * @package asw-register
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ASW_Reg_Email_Sender
 */
class ASW_Reg_Email_Sender {

	/**
	 * Send thank-you email to the lead's email address.
	 *
	 * @param int   $lead_id Lead ID.
	 * @param array $lead    Lead data row.
	 * @param array $form    Form config row.
	 * @return string 'sent'|'failed'|'skipped'
	 */
	public static function send_thank_you( $lead_id, array $lead, array $form ) {
		if ( empty( $form['email_enabled'] ) || empty( $lead['email'] ) ) {
			return 'skipped';
		}

		$subject = self::replace_tags( $form['email_subject'], $lead, $form );
		$body    = self::replace_tags( $form['email_body'], $lead, $form );

		$from_name  = ! empty( $form['email_from_name'] )
			? $form['email_from_name']
			: get_option( 'asw_reg_default_from_name', get_bloginfo( 'name' ) );

		$from_email = ! empty( $form['email_from_addr'] )
			? $form['email_from_addr']
			: get_option( 'asw_reg_default_from_email', get_option( 'admin_email' ) );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'From: %s <%s>', $from_name, $from_email ),
		);

		$mail_args = array(
			'to'      => $lead['email'],
			'subject' => $subject,
			'message' => $body,
			'headers' => $headers,
		);

		/**
		 * Filter: asw_reg_email_args
		 *
		 * @param array $mail_args Arguments to pass to wp_mail.
		 * @param int   $lead_id   Lead ID.
		 * @param array $form      Form config.
		 */
		$mail_args = apply_filters( 'asw_reg_email_args', $mail_args, $lead_id, $form );

		$sent = wp_mail(
			$mail_args['to'],
			$mail_args['subject'],
			$mail_args['message'],
			$mail_args['headers']
		);

		return $sent ? 'sent' : 'failed';
	}

	/**
	 * Replace merge tags in a string.
	 *
	 * Supported tags: {first_name} {last_name} {email} {tel} {form_name} {date} {site_name}
	 *
	 * @param string $text Text with merge tags.
	 * @param array  $lead Lead data.
	 * @param array  $form Form config.
	 * @return string
	 */
	public static function replace_tags( $text, array $lead, array $form ) {
		$map = array(
			'{first_name}' => isset( $lead['first_name'] ) ? esc_html( $lead['first_name'] ) : '',
			'{last_name}'  => isset( $lead['last_name'] )  ? esc_html( $lead['last_name'] )  : '',
			'{email}'      => isset( $lead['email'] )      ? esc_html( $lead['email'] )      : '',
			'{tel}'        => isset( $lead['tel'] )        ? esc_html( $lead['tel'] )        : '',
			'{form_name}'  => isset( $form['name'] )       ? esc_html( $form['name'] )       : '',
			'{date}'       => wp_date( get_option( 'date_format' ) ),
			'{site_name}'  => esc_html( get_bloginfo( 'name' ) ),
		);

		return str_replace( array_keys( $map ), array_values( $map ), $text );
	}
}
