<?php
/**
 * Admin: Forms list page.
 *
 * @package asw-register
 */

defined( 'ABSPATH' ) || exit;

$table = new ASW_Reg_Forms_List_Table();
$table->prepare_items();
?>
<div class="wrap asw-reg-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Forms', 'asw-register' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=asw-reg-form-edit' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Add New', 'asw-register' ); ?>
	</a>

	<?php if ( isset( $_GET['deleted'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Form deleted.', 'asw-register' ); ?></p></div>
	<?php endif; ?>

	<form method="get">
		<input type="hidden" name="page" value="asw-reg-forms">
		<?php $table->display(); ?>
	</form>
</div>
