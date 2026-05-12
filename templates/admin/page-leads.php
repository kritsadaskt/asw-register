<?php
/**
 * Admin: Leads list page.
 *
 * @package asw-register
 */

defined( 'ABSPATH' ) || exit;

// Lead detail view.
$view_id = isset( $_GET['view'] ) ? (int) $_GET['view'] : 0;
if ( $view_id ) {
	$lead = ASW_Reg_Lead_Manager::get_lead( $view_id );
	$form = $lead ? ASW_Reg_Form_Manager::get_form( $lead['form_id'] ) : null;

	if ( ! $lead ) {
		echo '<div class="wrap"><h1>' . esc_html__( 'Lead not found.', 'asw-register' ) . '</h1></div>';
		return;
	}
	?>
	<div class="wrap asw-reg-wrap">
		<h1>
			<?php esc_html_e( 'Lead Detail', 'asw-register' ); ?> #<?php echo (int) $lead['id']; ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=asw-reg-leads' ) ); ?>" class="page-title-action">
				&larr; <?php esc_html_e( 'Back to Leads', 'asw-register' ); ?>
			</a>
		</h1>

		<div class="asw-reg-lead-detail">
			<h2><?php esc_html_e( 'Contact Information', 'asw-register' ); ?></h2>
			<table class="widefat striped">
				<?php
				$display = array(
					'id'         => __( 'ID', 'asw-register' ),
					'form_id'    => __( 'Form', 'asw-register' ),
					'first_name' => __( 'First Name', 'asw-register' ),
					'last_name'  => __( 'Last Name', 'asw-register' ),
					'email'      => __( 'Email', 'asw-register' ),
					'tel'        => __( 'Phone', 'asw-register' ),
					'created_at' => __( 'Submitted', 'asw-register' ),
					'ip_address' => __( 'IP Address', 'asw-register' ),
					'page_url'   => __( 'Page URL', 'asw-register' ),
				);
				foreach ( $display as $k => $label ) :
					$val = $lead[ $k ] ?? '';
					if ( 'form_id' === $k ) {
						$val = $form ? esc_html( $form['name'] ) . ' (#' . $lead['form_id'] . ')' : '#' . $lead['form_id'];
					}
					?>
					<tr>
						<th style="width:200px"><?php echo esc_html( $label ); ?></th>
						<td><?php echo esc_html( $val ); ?></td>
					</tr>
				<?php endforeach; ?>
			</table>

			<h2><?php esc_html_e( 'UTM Attribution', 'asw-register' ); ?></h2>
			<table class="widefat striped">
				<?php
				$utm_fields = array(
					'utm_source'         => 'UTM Source',
					'utm_medium'         => 'UTM Medium',
					'utm_campaign'       => 'UTM Campaign',
					'utm_term'           => 'UTM Term',
					'utm_content'        => 'UTM Content',
					'utm_gclid'          => 'GCLID',
					'handl_landing_page' => 'Landing Page',
					'handl_original_ref' => 'Original Referrer',
				);
				foreach ( $utm_fields as $k => $label ) :
					?>
					<tr>
						<th style="width:200px"><?php echo esc_html( $label ); ?></th>
						<td><?php echo esc_html( $lead[ $k ] ?? '' ); ?></td>
					</tr>
				<?php endforeach; ?>
			</table>

			<?php if ( ! empty( $lead['extra_fields'] ) ) : ?>
				<h2><?php esc_html_e( 'Additional Fields', 'asw-register' ); ?></h2>
				<table class="widefat striped">
					<?php foreach ( $lead['extra_fields'] as $k => $v ) : ?>
						<tr>
							<th style="width:200px"><?php echo esc_html( $k ); ?></th>
							<td><?php echo esc_html( $v ); ?></td>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php endif; ?>

			<h2><?php esc_html_e( 'API Status', 'asw-register' ); ?></h2>
			<table class="widefat striped">
				<tr><th style="width:200px"><?php esc_html_e( 'Status', 'asw-register' ); ?></th><td><?php echo esc_html( $lead['api_status'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'HTTP Code', 'asw-register' ); ?></th><td><?php echo esc_html( $lead['api_http_code'] ?? '' ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Sent At', 'asw-register' ); ?></th><td><?php echo esc_html( $lead['api_sent_at'] ?? '' ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Response', 'asw-register' ); ?></th><td><pre class="asw-reg-pre"><?php echo esc_html( $lead['api_response'] ?? '' ); ?></pre></td></tr>
			</table>

			<h2><?php esc_html_e( 'Email Status', 'asw-register' ); ?></h2>
			<table class="widefat striped">
				<tr><th style="width:200px"><?php esc_html_e( 'Status', 'asw-register' ); ?></th><td><?php echo esc_html( $lead['email_status'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Sent At', 'asw-register' ); ?></th><td><?php echo esc_html( $lead['email_sent_at'] ?? '' ); ?></td></tr>
			</table>
		</div>
	</div>
	<?php
	return;
}

// List view.
$table = new ASW_Reg_Leads_List_Table();
$table->process_bulk_action();
$table->prepare_items();

$forms    = ASW_Reg_Form_Manager::get_forms();
$form_filter = isset( $_GET['form_id'] ) ? (int) $_GET['form_id'] : 0;
$date_from   = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
$date_to     = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';

// Build export URL.
$export_base = admin_url( 'admin.php?page=asw-reg-leads&asw_reg_export=1' );
$export_args = array_filter( array(
	'form_id'   => $form_filter,
	'date_from' => $date_from,
	'date_to'   => $date_to,
	's'         => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
) );
$export_nonce   = wp_create_nonce( 'asw_reg_export' );
$export_csv_url = add_query_arg( array_merge( $export_args, array( 'format' => 'csv', '_wpnonce' => $export_nonce ) ), $export_base );
$export_xlsx_url = add_query_arg( array_merge( $export_args, array( 'format' => 'xlsx', '_wpnonce' => $export_nonce ) ), $export_base );
?>
<div class="wrap asw-reg-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Leads', 'asw-register' ); ?></h1>
	<a href="<?php echo esc_url( $export_csv_url ); ?>" class="page-title-action"><?php esc_html_e( 'Export CSV', 'asw-register' ); ?></a>
	<a href="<?php echo esc_url( $export_xlsx_url ); ?>" class="page-title-action"><?php esc_html_e( 'Export Excel', 'asw-register' ); ?></a>

	<!-- Filters -->
	<form method="get" class="asw-reg-filters">
		<input type="hidden" name="page" value="asw-reg-leads">
		<select name="form_id">
			<option value=""><?php esc_html_e( 'All Forms', 'asw-register' ); ?></option>
			<?php foreach ( $forms as $f ) : ?>
				<option value="<?php echo esc_attr( $f['id'] ); ?>" <?php selected( $form_filter, $f['id'] ); ?>>
					<?php echo esc_html( $f['name'] ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" placeholder="From">
		<input type="date" name="date_to"   value="<?php echo esc_attr( $date_to ); ?>"   placeholder="To">
		<?php $table->search_box( __( 'Search Leads', 'asw-register' ), 'asw-reg-leads' ); ?>
		<?php submit_button( __( 'Filter', 'asw-register' ), 'secondary', 'filter', false ); ?>
	</form>

	<form method="post">
		<?php $table->display(); ?>
	</form>
</div>
