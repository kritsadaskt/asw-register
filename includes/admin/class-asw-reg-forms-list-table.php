<?php
/**
 * WP_List_Table for Forms.
 *
 * @package asw-register
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class ASW_Reg_Forms_List_Table
 */
class ASW_Reg_Forms_List_Table extends WP_List_Table {

	/** @var array */
	private $lead_counts = array();

	/** Constructor. */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'form',
			'plural'   => 'forms',
			'ajax'     => false,
		) );
	}

	/** Prepare items. */
	public function prepare_items() {
		$this->lead_counts = ASW_Reg_Lead_Manager::counts_by_form();
		$this->items       = ASW_Reg_Form_Manager::get_forms();

		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);
	}

	/** Define columns. */
	public function get_columns() {
		return array(
			'cb'        => '<input type="checkbox">',
			'name'      => __( 'Form Name', 'asw-register' ),
			'shortcode' => __( 'Shortcode', 'asw-register' ),
			'leads'     => __( 'Leads', 'asw-register' ),
			'status'    => __( 'Status', 'asw-register' ),
			'created_at' => __( 'Created', 'asw-register' ),
		);
	}

	/** Sortable columns. */
	public function get_sortable_columns() {
		return array(
			'name'       => array( 'name', false ),
			'leads'      => array( 'leads', false ),
			'created_at' => array( 'created_at', true ),
		);
	}

	/** Checkbox column. */
	public function column_cb( $item ) {
		return '<input type="checkbox" name="form_ids[]" value="' . esc_attr( $item['id'] ) . '">';
	}

	/** Name column with row actions. */
	public function column_name( $item ) {
		$edit_url   = admin_url( 'admin.php?page=asw-reg-form-edit&form_id=' . $item['id'] );
		$delete_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=asw_reg_delete_form&form_id=' . $item['id'] ),
			'asw_reg_delete_form'
		);

		$actions = array(
			'edit'   => '<a href="' . esc_url( $edit_url ) . '">' . __( 'Edit', 'asw-register' ) . '</a>',
			'delete' => '<a href="' . esc_url( $delete_url ) . '" class="asw-reg-confirm-delete">' . __( 'Delete', 'asw-register' ) . '</a>',
		);

		return '<a href="' . esc_url( $edit_url ) . '"><strong>' . esc_html( $item['name'] ) . '</strong></a>' . $this->row_actions( $actions );
	}

	/** Shortcode column. */
	public function column_shortcode( $item ) {
		return '<code class="asw-reg-shortcode" title="' . esc_attr__( 'Click to copy', 'asw-register' ) . '">[asw_register_form id="' . esc_attr( $item['id'] ) . '"]</code>';
	}

	/** Leads count column. */
	public function column_leads( $item ) {
		$count    = isset( $this->lead_counts[ $item['id'] ] ) ? $this->lead_counts[ $item['id'] ] : 0;
		$url      = admin_url( 'admin.php?page=asw-reg-leads&form_id=' . $item['id'] );
		return '<a href="' . esc_url( $url ) . '">' . (int) $count . '</a>';
	}

	/** Status column. */
	public function column_status( $item ) {
		$label = 'active' === $item['status'] ? __( 'Active', 'asw-register' ) : __( 'Inactive', 'asw-register' );
		$class = 'active' === $item['status'] ? 'asw-reg-badge--active' : 'asw-reg-badge--inactive';
		return '<span class="asw-reg-badge ' . esc_attr( $class ) . '">' . esc_html( $label ) . '</span>';
	}

	/** Created date column. */
	public function column_created_at( $item ) {
		return esc_html( wp_date( get_option( 'date_format' ), strtotime( $item['created_at'] ) ) );
	}

	/** Default column handler. */
	public function column_default( $item, $column_name ) {
		return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '—';
	}

	/** No items message. */
	public function no_items() {
		esc_html_e( 'No forms found. Click "Add New" to create your first form.', 'asw-register' );
	}
}
