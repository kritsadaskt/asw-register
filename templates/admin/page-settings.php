<?php
/**
 * Admin: Settings page.
 *
 * @package asw-register
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap asw-reg-wrap">
	<h1><?php esc_html_e( 'Settings', 'asw-register' ); ?></h1>

	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'asw-register' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'asw_reg_save_settings' ); ?>
		<input type="hidden" name="action" value="asw_reg_save_settings">

		<table class="form-table">
			<tr>
				<th><label for="default_from_name"><?php esc_html_e( 'Default From Name', 'asw-register' ); ?></label></th>
				<td>
					<input type="text" id="default_from_name" name="default_from_name" class="regular-text"
						   value="<?php echo esc_attr( get_option( 'asw_reg_default_from_name', get_bloginfo( 'name' ) ) ); ?>">
					<p class="description"><?php esc_html_e( 'Used when the form does not specify a "From Name".', 'asw-register' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="default_from_email"><?php esc_html_e( 'Default From Email', 'asw-register' ); ?></label></th>
				<td>
					<input type="email" id="default_from_email" name="default_from_email" class="regular-text"
						   value="<?php echo esc_attr( get_option( 'asw_reg_default_from_email', get_option( 'admin_email' ) ) ); ?>">
					<p class="description"><?php esc_html_e( 'Used when the form does not specify a "From Email".', 'asw-register' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Data on Uninstall', 'asw-register' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="delete_data_on_uninstall" value="1"
							   <?php checked( get_option( 'asw_reg_delete_data_on_uninstall', '0' ), '1' ); ?>>
						<?php esc_html_e( 'Delete all leads and form data when the plugin is uninstalled', 'asw-register' ); ?>
					</label>
					<p class="description" style="color:#c00;">
						<?php esc_html_e( 'Warning: this cannot be undone. Leave unchecked to keep your data safe.', 'asw-register' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>
</div>
