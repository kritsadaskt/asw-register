<?php
/**
 * Lead CRUD operations.
 *
 * @package asw-register
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ASW_Reg_Lead_Manager
 */
class ASW_Reg_Lead_Manager {

	/**
	 * Table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'tt_leads';
	}

	/**
	 * Insert a new lead.
	 *
	 * @param array $data Lead data.
	 * @return int|false Inserted ID or false.
	 */
	public static function create_lead( array $data ) {
		global $wpdb;

		// Encode extra_fields array to JSON.
		if ( isset( $data['extra_fields'] ) && is_array( $data['extra_fields'] ) ) {
			$data['extra_fields'] = wp_json_encode( $data['extra_fields'] );
		}

		$data['created_at'] = current_time( 'mysql', true );

		$wpdb->insert( self::table(), $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->insert_id ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Get a single lead by ID.
	 *
	 * @param int $id Lead ID.
	 * @return array|null
	 */
	public static function get_lead( $id ) {
		global $wpdb;
		$table = self::table();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( ! $row ) {
			return null;
		}
		if ( ! empty( $row['extra_fields'] ) ) {
			$decoded = json_decode( $row['extra_fields'], true );
			$row['extra_fields'] = is_array( $decoded ) ? $decoded : array();
		} else {
			$row['extra_fields'] = array();
		}
		return $row;
	}

	/**
	 * Get leads with optional filters.
	 *
	 * @param array $args Query arguments.
	 * @return array { items: array, total: int }
	 */
	public static function get_leads( array $args = array() ) {
		global $wpdb;
		$table = self::table();

		$defaults = array(
			'form_id'    => 0,
			'search'     => '',
			'date_from'  => '',
			'date_to'    => '',
			'per_page'   => 20,
			'page'       => 1,
			'orderby'    => 'created_at',
			'order'      => 'DESC',
		);
		$args = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['form_id'] ) ) {
			$where[]  = 'form_id = %d';
			$params[] = (int) $args['form_id'];
		}

		if ( ! empty( $args['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[]  = '(first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR tel LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		if ( ! empty( $args['date_from'] ) ) {
			$where[]  = 'created_at >= %s';
			$params[] = $args['date_from'] . ' 00:00:00';
		}

		if ( ! empty( $args['date_to'] ) ) {
			$where[]  = 'created_at <= %s';
			$params[] = $args['date_to'] . ' 23:59:59';
		}

		$where_sql = implode( ' AND ', $where );

		// Validate orderby / order.
		$allowed_orderby = array( 'id', 'created_at', 'first_name', 'last_name', 'email', 'form_id' );
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order   = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$per_page = max( 1, (int) $args['per_page'] );
		$offset   = ( max( 1, (int) $args['page'] ) - 1 ) * $per_page;

		// Count query.
		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		$total     = $params ? (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ) : (int) $wpdb->get_var( $count_sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		// Data query.
		$data_sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$params[] = $per_page;
		$params[] = $offset;
		$rows = $wpdb->get_results( $wpdb->prepare( $data_sql, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		return array(
			'items' => $rows ? $rows : array(),
			'total' => $total,
		);
	}

	/**
	 * Get all leads for export (no pagination).
	 *
	 * @param array $args Filter args (form_id, date_from, date_to, search).
	 * @return array
	 */
	public static function get_leads_for_export( array $args = array() ) {
		$args['per_page'] = 99999;
		$args['page']     = 1;
		$result = self::get_leads( $args );
		return $result['items'];
	}

	/**
	 * Update API status fields.
	 *
	 * @param int    $lead_id     Lead ID.
	 * @param string $status      'success'|'error'|'skipped'.
	 * @param string $response    Raw response body.
	 * @param int    $http_code   HTTP status code.
	 */
	public static function update_api_status( $lead_id, $status, $response = '', $http_code = 0 ) {
		global $wpdb;
		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array(
				'api_status'    => $status,
				'api_response'  => $response,
				'api_http_code' => $http_code ? $http_code : null,
				'api_sent_at'   => current_time( 'mysql', true ),
			),
			array( 'id' => (int) $lead_id )
		);
	}

	/**
	 * Update email status fields.
	 *
	 * @param int    $lead_id Lead ID.
	 * @param string $status  'sent'|'failed'|'skipped'.
	 */
	public static function update_email_status( $lead_id, $status ) {
		global $wpdb;
		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array(
				'email_status'  => $status,
				'email_sent_at' => current_time( 'mysql', true ),
			),
			array( 'id' => (int) $lead_id )
		);
	}

	/**
	 * Delete a lead.
	 *
	 * @param int $id Lead ID.
	 * @return bool
	 */
	public static function delete_lead( $id ) {
		global $wpdb;
		return (bool) $wpdb->delete( self::table(), array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Count leads per form.
	 *
	 * @return array keyed by form_id.
	 */
	public static function counts_by_form() {
		global $wpdb;
		$table = self::table();
		$rows  = $wpdb->get_results( "SELECT form_id, COUNT(*) as cnt FROM {$table} GROUP BY form_id", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$map   = array();
		foreach ( (array) $rows as $row ) {
			$map[ $row['form_id'] ] = (int) $row['cnt'];
		}
		return $map;
	}
}
