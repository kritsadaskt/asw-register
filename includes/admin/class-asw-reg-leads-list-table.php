<?php
/**
 * WP_List_Table for Leads.
 *
 * @package asw-register
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class ASW_Reg_Leads_List_Table
 */
class ASW_Reg_Leads_List_Table extends WP_List_Table {

	/** @var array */
	private $forms_map = array();

	/** @var array Custom field columns: 'cf_{key}' => 'Label' */
	private $custom_fields_cols = array();

	/** Constructor. */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'lead',
			'plural'   => 'leads',
			'ajax'     => false,
		) );
	}

	/** Prepare items with pagination and filtering. */
	public function prepare_items() {
		// Build forms map for display.
		foreach ( ASW_Reg_Form_Manager::get_forms() as $form ) {
			$this->forms_map[ $form['id'] ] = $form['name'];
		}

		$per_page = $this->get_items_per_page( 'asw_reg_leads_per_page', 20 );
		$current  = $this->get_pagenum();

		$args = array(
			'form_id'   => isset( $_GET['form_id'] ) ? (int) $_GET['form_id'] : 0, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'search'    => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'date_from' => isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'date_to'   => isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'per_page'  => $per_page,
			'page'      => $current,
			'orderby'   => isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'created_at', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'order'     => isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'DESC', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		);

		$result      = ASW_Reg_Lead_Manager::get_leads( $args );
		$this->items = $result['items'];

		// Decode extra_fields for each item so column_default can access them.
		foreach ( $this->items as &$item ) {
			if ( ! empty( $item['extra_fields'] ) && is_string( $item['extra_fields'] ) ) {
				$decoded             = json_decode( $item['extra_fields'], true );
				$item['extra_fields'] = is_array( $decoded ) ? $decoded : array();
			} else {
				$item['extra_fields'] = array();
			}
		}
		unset( $item );

		// Collect custom field columns for the current form filter.
		$filter_form_id = isset( $_GET['form_id'] ) ? (int) $_GET['form_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $filter_form_id ) {
			$filter_form = ASW_Reg_Form_Manager::get_form( $filter_form_id );
			if ( $filter_form && ! empty( $filter_form['fields_config']['custom_fields'] ) ) {
				foreach ( $filter_form['fields_config']['custom_fields'] as $cf ) {
					$this->custom_fields_cols[ 'cf_' . $cf['key'] ] = $cf['label'];
				}
			}
		} else {
			foreach ( ASW_Reg_Form_Manager::get_forms() as $f ) {
				if ( ! empty( $f['fields_config']['custom_fields'] ) ) {
					foreach ( $f['fields_config']['custom_fields'] as $cf ) {
						$this->custom_fields_cols[ 'cf_' . $cf['key'] ] = $cf['label'];
					}
				}
			}
		}

		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);

		$this->set_pagination_args( array(
			'total_items' => $result['total'],
			'per_page'    => $per_page,
			'total_pages' => ceil( $result['total'] / $per_page ),
		) );
	}

	/** Define columns. */
	public function get_columns() {
		$cols = array(
			'cb'           => '<input type="checkbox">',
			'id'           => __( 'ID', 'asw-register' ),
			'form_id'      => __( 'Form', 'asw-register' ),
			'name'         => __( 'Name', 'asw-register' ),
			'email'        => __( 'Email', 'asw-register' ),
			'tel'          => __( 'Phone', 'asw-register' ),
			'utm_source'   => __( 'UTM Source', 'asw-register' ),
			'api_status'   => __( 'API', 'asw-register' ),
			'email_status' => __( 'Email', 'asw-register' ),
			'created_at'   => __( 'Date', 'asw-register' ),
		);
		foreach ( $this->custom_fields_cols as $col_key => $col_label ) {
			$cols[ $col_key ] = esc_html( $col_label );
		}
		return $cols;
	}

	/** Sortable columns. */
	public function get_sortable_columns() {
		return array(
			'id'         => array( 'id', true ),
			'form_id'    => array( 'form_id', false ),
			'email'      => array( 'email', false ),
			'created_at' => array( 'created_at', true ),
		);
	}

	/** Checkbox. */
	public function column_cb( $item ) {
		return '<input type="checkbox" name="lead_ids[]" value="' . esc_attr( $item['id'] ) . '">';
	}

	/** ID with row actions. */
	public function column_id( $item ) {
		$view_url   = admin_url( 'admin.php?page=asw-reg-leads&view=' . $item['id'] );
		$delete_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=asw_reg_delete_lead&lead_id=' . $item['id'] . '&redirect=' . rawurlencode( self::current_url() ) ),
			'asw_reg_delete_lead'
		);

		$actions = array(
			'view'   => '<a href="' . esc_url( $view_url ) . '">' . __( 'View', 'asw-register' ) . '</a>',
			'delete' => '<a href="' . esc_url( $delete_url ) . '" class="asw-reg-confirm-delete">' . __( 'Delete', 'asw-register' ) . '</a>',
		);

		return '#' . (int) $item['id'] . $this->row_actions( $actions );
	}

	/** Form name. */
	public function column_form_id( $item ) {
		return isset( $this->forms_map[ $item['form_id'] ] )
			? esc_html( $this->forms_map[ $item['form_id'] ] )
			: '#' . (int) $item['form_id'];
	}

	/** Full name. */
	public function column_name( $item ) {
		return esc_html( trim( $item['first_name'] . ' ' . $item['last_name'] ) );
	}

	/** Email. */
	public function column_email( $item ) {
		return esc_html( $item['email'] );
	}

	/** Tel. */
	public function column_tel( $item ) {
		return esc_html( $item['tel'] );
	}

	/** UTM source. */
	public function column_utm_source( $item ) {
		return esc_html( $item['utm_source'] );
	}

	/** API status badge. */
	public function column_api_status( $item ) {
		return self::status_badge( $item['api_status'] );
	}

	/** Email status badge. */
	public function column_email_status( $item ) {
		return self::status_badge( $item['email_status'] );
	}

	/** Created date. */
	public function column_created_at( $item ) {
		return esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item['created_at'] ) ) );
	}

	/** Default column. */
	public function column_default( $item, $column_name ) {
		if ( strpos( $column_name, 'cf_' ) === 0 ) {
			$cf_key = substr( $column_name, 3 );
			$extra  = is_array( $item['extra_fields'] ) ? $item['extra_fields'] : array();
			$val    = $extra[ 'custom_' . $cf_key ] ?? '';
			return $val !== '' ? esc_html( $val ) : '—';
		}
		return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '—';
	}

	/** No items. */
	public function no_items() {
		esc_html_e( 'No leads found.', 'asw-register' );
	}

	/**
	 * Render a status badge.
	 *
	 * @param string $status Status string.
	 * @return string HTML.
	 */
	private static function status_badge( $status ) {
		$map = array(
			'not_sent' => array( 'label' => __( 'Not Sent', 'asw-register' ), 'class' => 'asw-reg-badge--inactive' ),
			'success'  => array( 'label' => __( 'Success', 'asw-register' ),  'class' => 'asw-reg-badge--active' ),
			'sent'     => array( 'label' => __( 'Sent', 'asw-register' ),     'class' => 'asw-reg-badge--active' ),
			'error'    => array( 'label' => __( 'Error', 'asw-register' ),    'class' => 'asw-reg-badge--error' ),
			'failed'   => array( 'label' => __( 'Failed', 'asw-register' ),   'class' => 'asw-reg-badge--error' ),
			'skipped'  => array( 'label' => __( 'Skipped', 'asw-register' ),  'class' => 'asw-reg-badge--skip' ),
		);

		$info = isset( $map[ $status ] ) ? $map[ $status ] : array( 'label' => $status, 'class' => '' );
		return '<span class="asw-reg-badge ' . esc_attr( $info['class'] ) . '">' . esc_html( $info['label'] ) . '</span>';
	}

	/**
	 * Current page URL (for redirect after delete).
	 *
	 * @return string
	 */
	private static function current_url() {
		global $wp;
		return home_url( add_query_arg( array(), $wp->request ) );
	}

	/** Bulk actions. */
	public function get_bulk_actions() {
		return array(
			'delete' => __( 'Delete', 'asw-register' ),
		);
	}

	/** Process bulk actions. */
	public function process_bulk_action() {
		if ( 'delete' !== $this->current_action() ) {
			return;
		}
		check_admin_referer( 'bulk-leads' );
		$ids = isset( $_POST['lead_ids'] ) ? array_map( 'intval', (array) $_POST['lead_ids'] ) : array();
		foreach ( $ids as $id ) {
			ASW_Reg_Lead_Manager::delete_lead( $id );
		}
	}
}
