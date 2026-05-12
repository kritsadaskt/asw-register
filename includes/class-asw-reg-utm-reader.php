<?php
/**
 * Read UTM data from query string and cookies.
 *
 * Priority: $_GET > HandL cookies (lowercase) > plain cookies
 *
 * @package asw-register
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ASW_Reg_UTM_Reader
 */
class ASW_Reg_UTM_Reader {

	/**
	 * Map of our internal keys to cookie / GET param names.
	 *
	 * HandL v2.x stores cookies with the plain lowercase name.
	 *
	 * @var array<string,string>
	 */
	private static $utm_keys = array(
		'utm_source'         => 'utm_source',
		'utm_medium'         => 'utm_medium',
		'utm_campaign'       => 'utm_campaign',
		'utm_term'           => 'utm_term',
		'utm_content'        => 'utm_content',
		'utm_gclid'          => 'gclid',
		'handl_landing_page' => 'handl_landing_page',
		'handl_original_ref' => 'handl_original_ref',
	);

	/**
	 * Collect UTM values from all available sources.
	 *
	 * Front-end JS also populates hidden POST fields, so we check
	 * $_POST first (as submitted by asw-reg-form.js), then fall
	 * back to server-side cookie reading.
	 *
	 * @return array<string,string>
	 */
	public static function get_utms() {
		$result = array();

		foreach ( self::$utm_keys as $internal => $param ) {
			$result[ $internal ] = self::read_value( $param );
		}

		return $result;
	}

	/**
	 * Read a single UTM value with the priority chain.
	 *
	 * @param string $param Cookie / GET param name.
	 * @return string
	 */
	private static function read_value( $param ) {
		// 1. POST field submitted by JS (most specific — current page URL).
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST[ 'utm_field_' . $param ] ) ) {
			return sanitize_text_field( wp_unslash( $_POST[ 'utm_field_' . $param ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		// 2. GET parameter on the current request.
		if ( isset( $_GET[ $param ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return sanitize_text_field( wp_unslash( $_GET[ $param ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		// 3. HandL lowercase cookie (v2.x free version).
		if ( isset( $_COOKIE[ $param ] ) ) {
			return sanitize_text_field( wp_unslash( $_COOKIE[ $param ] ) );
		}

		// 4. HandL uppercase-prefixed cookie (HandL Pro naming convention).
		$handl_key = 'HandL_' . $param;
		if ( isset( $_COOKIE[ $handl_key ] ) ) {
			return sanitize_text_field( wp_unslash( $_COOKIE[ $handl_key ] ) );
		}

		return '';
	}
}
