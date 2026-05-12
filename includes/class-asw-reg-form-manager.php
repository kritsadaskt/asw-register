<?php
/**
 * Form CRUD operations.
 *
 * @package asw-register
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ASW_Reg_Form_Manager
 */
class ASW_Reg_Form_Manager {

	/**
	 * Table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'asw_register_forms';
	}

	/**
	 * Get all forms.
	 *
	 * @return array
	 */
	public static function get_forms() {
		global $wpdb;
		$table = self::table();
		$rows  = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $rows ? array_map( array( __CLASS__, 'decode_json_fields' ), $rows ) : array();
	}

	/**
	 * Get a single form by ID.
	 *
	 * @param int $id Form ID.
	 * @return array|null
	 */
	public static function get_form( $id ) {
		global $wpdb;
		$table = self::table();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $row ? self::decode_json_fields( $row ) : null;
	}

	/**
	 * Create a new form.
	 *
	 * @param array $data Form data.
	 * @return int|false Inserted ID or false on failure.
	 */
	public static function create_form( array $data ) {
		global $wpdb;
		$data = self::encode_json_fields( $data );
		$data = self::prepare_defaults( $data );
		$wpdb->insert( self::table(), $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->insert_id ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Update an existing form.
	 *
	 * @param int   $id   Form ID.
	 * @param array $data Data to update.
	 * @return bool
	 */
	public static function update_form( $id, array $data ) {
		global $wpdb;
		$data = self::encode_json_fields( $data );
		$result = $wpdb->update( self::table(), $data, array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return false !== $result;
	}

	/**
	 * Delete a form.
	 *
	 * @param int $id Form ID.
	 * @return bool
	 */
	public static function delete_form( $id ) {
		global $wpdb;
		return (bool) $wpdb->delete( self::table(), array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Generate a unique slug from a name.
	 *
	 * @param string $name Form name.
	 * @param int    $exclude_id Exclude this ID when checking uniqueness.
	 * @return string
	 */
	public static function generate_slug( $name, $exclude_id = 0 ) {
		global $wpdb;
		$table    = self::table();
		$base     = sanitize_title( $name );
		$slug     = $base;
		$suffix   = 1;

		while ( true ) {
			if ( $exclude_id ) {
				$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE slug = %s AND id != %d", $slug, $exclude_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			} else {
				$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE slug = %s", $slug ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}

			if ( ! $exists ) {
				break;
			}
			$slug = $base . '-' . $suffix;
			$suffix++;
		}

		return $slug;
	}

	/**
	 * Decode JSON columns into arrays.
	 *
	 * @param array $row DB row.
	 * @return array
	 */
	private static function decode_json_fields( array $row ) {
		foreach ( array( 'fields_config', 'api_headers' ) as $col ) {
			if ( isset( $row[ $col ] ) && is_string( $row[ $col ] ) ) {
				$decoded = json_decode( $row[ $col ], true );
				$row[ $col ] = is_array( $decoded ) ? $decoded : array();
			}
		}
		return $row;
	}

	/**
	 * Encode array columns to JSON for storage.
	 *
	 * @param array $data Data.
	 * @return array
	 */
	private static function encode_json_fields( array $data ) {
		foreach ( array( 'fields_config', 'api_headers' ) as $col ) {
			if ( isset( $data[ $col ] ) && is_array( $data[ $col ] ) ) {
				$data[ $col ] = wp_json_encode( $data[ $col ] );
			}
		}
		return $data;
	}

	/**
	 * Fill defaults for new form.
	 *
	 * @param array $data Data.
	 * @return array
	 */
	private static function prepare_defaults( array $data ) {
		$defaults = array(
			'status'           => 'active',
			'api_enabled'      => 0,
			'api_method'       => 'POST',
			'email_enabled'    => 0,
			'success_message'  => '',
			'redirect_url'     => '',
			'inject_post_id'   => 0,
		);
		return array_merge( $defaults, $data );
	}
}
