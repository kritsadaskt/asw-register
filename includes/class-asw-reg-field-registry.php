<?php
/**
 * Field Registry — defines available form fields.
 *
 * @package asw-register
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ASW_Reg_Field_Registry
 */
class ASW_Reg_Field_Registry {

	/** @var array<string,array> */
	private static $fields = array();

	/**
	 * Return all registered fields (core + filtered).
	 *
	 * @return array<string,array>
	 */
	public static function get_fields() {
		if ( ! empty( self::$fields ) ) {
			return self::$fields;
		}

		$core = array(
			'first_name' => array(
				'label'       => __( 'First Name', 'asw-register' ),
				'placeholder' => __( 'First Name', 'asw-register' ),
				'type'        => 'text',
				'db_column'   => 'first_name',
				'enabled'     => 1,
				'required'    => 0,
			),
			'last_name'  => array(
				'label'       => __( 'Last Name', 'asw-register' ),
				'placeholder' => __( 'Last Name', 'asw-register' ),
				'type'        => 'text',
				'db_column'   => 'last_name',
				'enabled'     => 1,
				'required'    => 0,
			),
			'tel'        => array(
				'label'       => __( 'Phone Number', 'asw-register' ),
				'placeholder' => __( 'Phone Number', 'asw-register' ),
				'type'        => 'tel',
				'db_column'   => 'tel',
				'enabled'     => 1,
				'required'    => 0,
			),
			'email'      => array(
				'label'       => __( 'Email', 'asw-register' ),
				'placeholder' => __( 'Email Address', 'asw-register' ),
				'type'        => 'email',
				'db_column'   => 'email',
				'enabled'     => 1,
				'required'    => 0,
			),
		);

		/**
		 * Filter: asw_reg_registered_fields
		 *
		 * Add custom fields. Fields without `db_column` (or `db_column => false`)
		 * are serialized into the `extra_fields` JSON column.
		 *
		 * @param array $fields Keyed by field slug.
		 */
		self::$fields = apply_filters( 'asw_reg_registered_fields', $core );

		return self::$fields;
	}

	/**
	 * Return a single field definition or null.
	 *
	 * @param string $key Field slug.
	 * @return array|null
	 */
	public static function get_field( $key ) {
		$fields = self::get_fields();
		return isset( $fields[ $key ] ) ? $fields[ $key ] : null;
	}

	/**
	 * Whether a field has a dedicated DB column.
	 *
	 * @param string $key Field slug.
	 * @return bool
	 */
	public static function has_db_column( $key ) {
		$field = self::get_field( $key );
		if ( ! $field ) {
			return false;
		}
		return ! empty( $field['db_column'] ) && false !== $field['db_column'];
	}
}
