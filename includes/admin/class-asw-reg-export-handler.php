<?php
/**
 * Export leads as CSV or XLSX.
 *
 * @package asw-register
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ASW_Reg_Export_Handler
 */
class ASW_Reg_Export_Handler {

	/**
	 * Register export hook.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'maybe_export' ) );
	}

	/**
	 * Trigger export if query vars are present.
	 */
	public static function maybe_export() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['asw_reg_export'] ) || ! isset( $_GET['page'] ) || 'asw-reg-leads' !== $_GET['page'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'asw-register' ) );
		}

		check_admin_referer( 'asw_reg_export' );

		$format = isset( $_GET['format'] ) ? sanitize_key( $_GET['format'] ) : 'csv';

		$args = array(
			'form_id'   => isset( $_GET['form_id'] ) ? (int) $_GET['form_id'] : 0, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'search'    => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'date_from' => isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'date_to'   => isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		);

		$rows = ASW_Reg_Lead_Manager::get_leads_for_export( $args );

		// Collect custom field columns from filtered form(s).
		$custom_cols = self::get_custom_cols( $args['form_id'] );

		if ( 'xlsx' === $format ) {
			self::export_xlsx( $rows, $custom_cols );
		} else {
			self::export_csv( $rows, $custom_cols );
		}
		exit;
	}

	/**
	 * Collect custom field columns from a form or all forms.
	 *
	 * @param int $form_id 0 = all forms.
	 * @return array key => label, e.g. [ 'custom_contact_time' => 'Preferred Contact Time' ]
	 */
	private static function get_custom_cols( $form_id ) {
		$cols  = array();
		$forms = $form_id
			? array( ASW_Reg_Form_Manager::get_form( $form_id ) )
			: ASW_Reg_Form_Manager::get_forms();

		foreach ( $forms as $form ) {
			if ( ! $form || empty( $form['fields_config']['custom_fields'] ) ) {
				continue;
			}
			foreach ( $form['fields_config']['custom_fields'] as $cf ) {
				$cols[ 'custom_' . $cf['key'] ] = $cf['label'];
			}
		}
		return $cols;
	}

	/**
	 * Column headers for export.
	 *
	 * @return array
	 */
	private static function export_headers() {
		return array(
			'id'                 => 'ID',
			'form_id'            => 'Form ID',
			'first_name'         => 'First Name',
			'last_name'          => 'Last Name',
			'email'              => 'Email',
			'tel'                => 'Phone',
			'utm_source'         => 'UTM Source',
			'utm_medium'         => 'UTM Medium',
			'utm_campaign'       => 'UTM Campaign',
			'utm_term'           => 'UTM Term',
			'utm_content'        => 'UTM Content',
			'utm_gclid'          => 'GCLID',
			'handl_landing_page' => 'Landing Page',
			'handl_original_ref' => 'Original Referrer',
			'api_status'         => 'API Status',
			'api_http_code'      => 'API HTTP Code',
			'email_status'       => 'Email Status',
			'ip_address'         => 'IP Address',
			'page_url'           => 'Page URL',
			'created_at'         => 'Date',
		);
	}

	/**
	 * Stream CSV to browser.
	 *
	 * @param array $rows        Lead rows.
	 * @param array $custom_cols Extra columns: key => label.
	 */
	private static function export_csv( array $rows, array $custom_cols = array() ) {
		$filename = 'tt-leads-' . gmdate( 'Y-m-d' ) . '.csv';

		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output       = fopen( 'php://output', 'w' );
		$base_headers = self::export_headers();
		$all_labels   = array_values( $base_headers );
		foreach ( $custom_cols as $label ) {
			$all_labels[] = $label;
		}

		// BOM for Excel UTF-8.
		fwrite( $output, "\xEF\xBB\xBF" );

		fputcsv( $output, $all_labels );

		foreach ( $rows as $row ) {
			$extra = array();
			if ( ! empty( $row['extra_fields'] ) ) {
				$decoded = json_decode( $row['extra_fields'], true );
				$extra   = is_array( $decoded ) ? $decoded : array();
			}

			$line = array();
			foreach ( array_keys( $base_headers ) as $col ) {
				$line[] = isset( $row[ $col ] ) ? $row[ $col ] : '';
			}
			foreach ( array_keys( $custom_cols ) as $col ) {
				$line[] = isset( $extra[ $col ] ) ? $extra[ $col ] : '';
			}
			fputcsv( $output, $line );
		}

		fclose( $output );
	}

	/**
	 * Generate XLSX and stream to browser.
	 *
	 * Requires PhpSpreadsheet (via Composer).
	 *
	 * @param array $rows        Lead rows.
	 * @param array $custom_cols Extra columns: key => label.
	 */
	private static function export_xlsx( array $rows, array $custom_cols = array() ) {
		if ( ! class_exists( '\PhpOffice\PhpSpreadsheet\Spreadsheet' ) ) {
			wp_die( esc_html__( 'PhpSpreadsheet is not installed. Run "composer install" in the plugin directory.', 'asw-register' ) );
		}

		$filename     = 'tt-leads-' . gmdate( 'Y-m-d' ) . '.xlsx';
		$spreadsheet  = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$sheet        = $spreadsheet->getActiveSheet();
		$base_headers = self::export_headers();
		$all_labels   = array_values( $base_headers );
		foreach ( $custom_cols as $label ) {
			$all_labels[] = $label;
		}

		// Header row.
		$col = 1;
		foreach ( $all_labels as $header_label ) {
			$sheet->setCellValueByColumnAndRow( $col, 1, $header_label );

			// Style header.
			$cell_coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex( $col ) . '1';
			$sheet->getStyle( $cell_coord )->applyFromArray( array(
				'font'      => array( 'bold' => true, 'color' => array( 'argb' => 'FFFFFFFF' ) ),
				'fill'      => array(
					'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
					'startColor' => array( 'argb' => 'FF2271B1' ),
				),
			) );

			$col++;
		}

		// Data rows.
		$row_num = 2;
		foreach ( $rows as $row ) {
			$extra = array();
			if ( ! empty( $row['extra_fields'] ) ) {
				$decoded = json_decode( $row['extra_fields'], true );
				$extra   = is_array( $decoded ) ? $decoded : array();
			}

			$col = 1;
			foreach ( array_keys( $base_headers ) as $db_col ) {
				$val = isset( $row[ $db_col ] ) ? $row[ $db_col ] : '';
				$sheet->setCellValueByColumnAndRow( $col, $row_num, $val );
				$col++;
			}
			foreach ( array_keys( $custom_cols ) as $cf_col ) {
				$val = isset( $extra[ $cf_col ] ) ? $extra[ $cf_col ] : '';
				$sheet->setCellValueByColumnAndRow( $col, $row_num, $val );
				$col++;
			}
			$row_num++;
		}

		// Auto-size columns.
		foreach ( range( 1, count( $all_labels ) ) as $col_idx ) {
			$sheet->getColumnDimensionByColumn( $col_idx )->setAutoSize( true );
		}

		// Output.
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Cache-Control: max-age=0' );

		$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx( $spreadsheet );
		$writer->save( 'php://output' );
	}
}
