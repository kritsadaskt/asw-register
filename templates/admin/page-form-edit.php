<?php
/**
 * Admin: Form add/edit page (tabbed).
 *
 * @package asw-register
 */

defined( 'ABSPATH' ) || exit;

$form_id = isset( $_GET['form_id'] ) ? (int) $_GET['form_id'] : 0;
$form    = $form_id ? ASW_Reg_Form_Manager::get_form( $form_id ) : null;
$is_new  = ! $form;
$registry = ASW_Reg_Field_Registry::get_fields();

$fields_config   = ( $form && is_array( $form['fields_config'] ) ) ? $form['fields_config'] : array();
$api_headers     = ( $form && is_array( $form['api_headers'] ) )   ? $form['api_headers']   : array();

$page_title = $is_new ? __( 'Add New Form', 'asw-register' ) : __( 'Edit Form', 'asw-register' );

// Build custom fields data for Fields tab.
$custom_fields = isset( $fields_config['custom_fields'] ) && is_array( $fields_config['custom_fields'] ) ? $fields_config['custom_fields'] : array();
$custom_map    = array();
foreach ( $custom_fields as $cf ) {
	$custom_map[ $cf['key'] ] = $cf;
}
$default_order = array_keys( $registry );
foreach ( $custom_fields as $cf ) {
	$default_order[] = $cf['key'];
}
$field_order = ( isset( $fields_config['field_order'] ) && is_array( $fields_config['field_order'] ) && ! empty( $fields_config['field_order'] ) )
	? $fields_config['field_order']
	: $default_order;
// Ensure all known keys are present.
foreach ( $default_order as $dk ) {
	if ( ! in_array( $dk, $field_order, true ) ) {
		$field_order[] = $dk;
	}
}

$cf_type_options = array( 'text', 'email', 'tel', 'number', 'textarea', 'select', 'radio', 'checkbox' );

$inject_choices  = ASW_Reg_Page_Options::get_choice_list();
$current_inject  = $form ? (int) ( $form['inject_post_id'] ?? 0 ) : 0;
if ( $current_inject && ! isset( $inject_choices[ $current_inject ] ) ) {
	$orphan_title = get_the_title( $current_inject );
	if ( ! $orphan_title ) {
		$orphan_title = sprintf( __( 'Post #%d (not in list)', 'asw-register' ), $current_inject );
	}
	$inject_choices[ $current_inject ] = $orphan_title;
}
?>
<div class="wrap asw-reg-wrap">
	<h1><?php echo esc_html( $page_title ); ?></h1>

	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Form saved.', 'asw-register' ); ?></p></div>
	<?php endif; ?>

	<?php if ( $form ) : ?>
		<p class="asw-reg-shortcode-hint">
			<?php esc_html_e( 'Shortcode:', 'asw-register' ); ?>
			<code class="asw-reg-shortcode">[asw_register_form id="<?php echo esc_attr( $form['id'] ); ?>"]</code>
		</p>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'asw_reg_save_form' ); ?>
		<input type="hidden" name="action"  value="asw_reg_save_form">
		<input type="hidden" name="form_id" value="<?php echo esc_attr( $form_id ); ?>">

		<!-- Tab nav -->
		<nav class="asw-reg-tabs" role="tablist">
			<?php
			$tabs = array(
				'general' => __( 'General', 'asw-register' ),
				'fields'  => __( 'Fields', 'asw-register' ),
				'api'     => __( 'API', 'asw-register' ),
				'email'   => __( 'Email', 'asw-register' ),
				'success' => __( 'Success', 'asw-register' ),
			);
			foreach ( $tabs as $tab_id => $tab_label ) :
				?>
				<button type="button" class="asw-reg-tab-btn" data-tab="<?php echo esc_attr( $tab_id ); ?>" role="tab">
					<?php echo esc_html( $tab_label ); ?>
				</button>
			<?php endforeach; ?>
		</nav>

		<!-- Tab: General -->
		<div class="asw-reg-tab-panel" id="asw-reg-tab-general">
			<table class="form-table">
				<tr>
					<th><label for="form_name"><?php esc_html_e( 'Form Name', 'asw-register' ); ?></label></th>
					<td>
						<input type="text" id="form_name" name="form_name" class="regular-text"
							   value="<?php echo esc_attr( $form['name'] ?? '' ); ?>" required>
					</td>
				</tr>
				<tr>
					<th><label for="status"><?php esc_html_e( 'Status', 'asw-register' ); ?></label></th>
					<td>
						<select id="status" name="status">
							<option value="active"   <?php selected( $form['status'] ?? 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'asw-register' ); ?></option>
							<option value="inactive" <?php selected( $form['status'] ?? 'active', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'asw-register' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="inject_post_id"><?php esc_html_e( 'Inject into page', 'asw-register' ); ?></label></th>
					<td>
						<select id="inject_post_id" name="inject_post_id" class="regular-text asw-reg-inject-select" style="max-width:100%;min-width:20em;">
							<option value="0"><?php esc_html_e( '— None (shortcode only) —', 'asw-register' ); ?></option>
							<?php foreach ( $inject_choices as $pid => $choice_label ) : ?>
								<option value="<?php echo esc_attr( (string) $pid ); ?>" <?php selected( $current_inject, (int) $pid ); ?>>
									<?php echo esc_html( $choice_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php esc_html_e( 'If set, the form is appended to the theme anchor #register_form on that project page (condominium or house, excluding the thank-you category). The shortcode still works anywhere.', 'asw-register' ); ?>
						</p>
					</td>
				</tr>
			</table>
		</div>

		<!-- Tab: Fields -->
		<div class="asw-reg-tab-panel" id="asw-reg-tab-fields">
			<p class="description"><?php esc_html_e( 'Drag rows to reorder fields. Click "+ Add Custom Field" to add a new custom field.', 'asw-register' ); ?></p>

			<input type="hidden" name="fields[field_order]" id="asw-reg-field-order" value="">

			<table class="widefat asw-reg-fields-table">
				<thead>
					<tr>
						<th style="width:24px"></th>
						<th><?php esc_html_e( 'Field', 'asw-register' ); ?></th>
						<th><?php esc_html_e( 'Enabled', 'asw-register' ); ?></th>
						<th><?php esc_html_e( 'Required', 'asw-register' ); ?></th>
						<th><?php esc_html_e( 'Label', 'asw-register' ); ?></th>
						<th><?php esc_html_e( 'Placeholder', 'asw-register' ); ?></th>
						<th><?php esc_html_e( 'Type', 'asw-register' ); ?></th>
						<th><?php esc_html_e( 'Options', 'asw-register' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody id="asw-reg-fields-list">
				<?php
				$cf_index = 0;
				foreach ( $field_order as $fkey ) :
					if ( isset( $registry[ $fkey ] ) ) :
						$def         = $registry[ $fkey ];
						$cfg         = isset( $fields_config[ $fkey ] ) ? $fields_config[ $fkey ] : array();
						$is_enabled  = isset( $cfg['enabled'] )     ? (bool) $cfg['enabled']     : (bool) ( $def['enabled'] ?? true );
						$is_required = isset( $cfg['required'] )    ? (bool) $cfg['required']    : (bool) ( $def['required'] ?? false );
						$label_val   = isset( $cfg['label'] )       ? $cfg['label']              : $def['label'];
						$ph_val      = isset( $cfg['placeholder'] ) ? $cfg['placeholder']        : ( $def['placeholder'] ?? '' );
						?>
						<tr class="asw-reg-core-row" data-field-type="core" data-field-key="<?php echo esc_attr( $fkey ); ?>">
							<td><span class="asw-reg-drag-handle dashicons dashicons-menu" title="<?php esc_attr_e( 'Drag to reorder', 'asw-register' ); ?>"></span></td>
							<td><strong><?php echo esc_html( $fkey ); ?></strong></td>
							<td>
								<input type="checkbox" name="field_enabled[<?php echo esc_attr( $fkey ); ?>]" value="1" <?php checked( $is_enabled ); ?>>
							</td>
							<td>
								<input type="checkbox" name="field_required[<?php echo esc_attr( $fkey ); ?>]" value="1" <?php checked( $is_required ); ?>>
							</td>
							<td>
								<input type="text" name="field_label[<?php echo esc_attr( $fkey ); ?>]" value="<?php echo esc_attr( $label_val ); ?>" class="regular-text">
							</td>
							<td>
								<input type="text" name="field_placeholder[<?php echo esc_attr( $fkey ); ?>]" value="<?php echo esc_attr( $ph_val ); ?>" class="regular-text">
							</td>
							<td>—</td>
							<td>—</td>
							<td>—</td>
						</tr>
					<?php elseif ( isset( $custom_map[ $fkey ] ) ) :
						$cf      = $custom_map[ $fkey ];
						$cf_type = $cf['type'] ?? 'text';
						$has_opts = in_array( $cf_type, array( 'select', 'radio', 'checkbox' ), true );
						?>
						<tr class="asw-reg-custom-row" data-field-type="custom">
							<td><span class="asw-reg-drag-handle dashicons dashicons-menu" title="<?php esc_attr_e( 'Drag to reorder', 'asw-register' ); ?>"></span></td>
							<td>
								<input type="text" name="fields[custom_fields][<?php echo esc_attr( $cf_index ); ?>][key]" value="<?php echo esc_attr( $cf['key'] ); ?>" class="asw-reg-cf-key" style="width:120px" placeholder="field_key">
							</td>
							<td>—</td>
							<td>
								<input type="checkbox" name="fields[custom_fields][<?php echo esc_attr( $cf_index ); ?>][required]" value="1" <?php checked( ! empty( $cf['required'] ) ); ?>>
							</td>
							<td>
								<input type="text" name="fields[custom_fields][<?php echo esc_attr( $cf_index ); ?>][label]" value="<?php echo esc_attr( $cf['label'] ?? '' ); ?>" class="asw-reg-cf-label regular-text" placeholder="<?php esc_attr_e( 'Label', 'asw-register' ); ?>">
							</td>
							<td>
								<input type="text" name="fields[custom_fields][<?php echo esc_attr( $cf_index ); ?>][placeholder]" value="<?php echo esc_attr( $cf['placeholder'] ?? '' ); ?>" class="asw-reg-cf-placeholder regular-text" placeholder="<?php esc_attr_e( 'Placeholder', 'asw-register' ); ?>">
							</td>
							<td>
								<select name="fields[custom_fields][<?php echo esc_attr( $cf_index ); ?>][type]" class="asw-reg-cf-type">
									<?php foreach ( $cf_type_options as $t ) : ?>
										<option value="<?php echo esc_attr( $t ); ?>" <?php selected( $cf_type, $t ); ?>><?php echo esc_html( ucfirst( $t ) ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
							<td class="asw-reg-cf-options-cell"<?php if ( ! $has_opts ) : ?> style="display:none"<?php endif; ?>>
								<textarea name="fields[custom_fields][<?php echo esc_attr( $cf_index ); ?>][options]" class="asw-reg-cf-options" rows="3" placeholder="<?php esc_attr_e( 'One option per line', 'asw-register' ); ?>"><?php echo esc_textarea( $cf['options'] ?? '' ); ?></textarea>
							</td>
							<td><button type="button" class="button button-small asw-reg-remove-field"><?php esc_html_e( 'Remove', 'asw-register' ); ?></button></td>
						</tr>
						<?php
						$cf_index++;
					endif;
				endforeach;
				?>
				</tbody>
			</table>

			<p>
				<button type="button" id="asw-reg-add-field" class="button" data-next-index="<?php echo esc_attr( $cf_index ); ?>"><?php esc_html_e( '+ Add Custom Field', 'asw-register' ); ?></button>
			</p>
		</div>

		<!-- Tab: API -->
		<div class="asw-reg-tab-panel" id="asw-reg-tab-api">
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Enable API', 'asw-register' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="api_enabled" value="1" <?php checked( ! empty( $form['api_enabled'] ) ); ?>>
							<?php esc_html_e( 'Post lead data to external API on submission', 'asw-register' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th><label for="api_endpoint"><?php esc_html_e( 'Endpoint URL', 'asw-register' ); ?></label></th>
					<td>
						<input type="url" id="api_endpoint" name="api_endpoint" class="large-text"
							   value="<?php echo esc_attr( $form['api_endpoint'] ?? '' ); ?>" placeholder="https://...">
					</td>
				</tr>
				<tr>
					<th><label for="api_method"><?php esc_html_e( 'HTTP Method', 'asw-register' ); ?></label></th>
					<td>
						<select id="api_method" name="api_method">
							<?php foreach ( array( 'POST', 'PUT', 'PATCH', 'GET' ) as $m ) : ?>
								<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $form['api_method'] ?? 'POST', $m ); ?>><?php echo esc_html( $m ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Headers', 'asw-register' ); ?></th>
					<td>
						<div id="asw-reg-api-headers">
							<?php if ( ! empty( $api_headers ) ) : foreach ( $api_headers as $header ) : ?>
								<div class="asw-reg-header-row">
									<input type="text" name="api_header_key[]"   value="<?php echo esc_attr( $header['key'] ?? '' ); ?>"   placeholder="Header name" class="regular-text">
									<input type="text" name="api_header_value[]" value="<?php echo esc_attr( $header['value'] ?? '' ); ?>" placeholder="Value" class="regular-text">
									<button type="button" class="button asw-reg-remove-header">&times;</button>
								</div>
							<?php endforeach; else : ?>
								<div class="asw-reg-header-row">
									<input type="text" name="api_header_key[]"   placeholder="Header name" class="regular-text">
									<input type="text" name="api_header_value[]" placeholder="Value" class="regular-text">
									<button type="button" class="button asw-reg-remove-header">&times;</button>
								</div>
							<?php endif; ?>
						</div>
						<button type="button" id="asw-reg-add-header" class="button"><?php esc_html_e( '+ Add Header', 'asw-register' ); ?></button>
					</td>
				</tr>
			</table>
		</div>

		<!-- Tab: Email -->
		<div class="asw-reg-tab-panel" id="asw-reg-tab-email">
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Enable Email', 'asw-register' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="email_enabled" value="1" <?php checked( ! empty( $form['email_enabled'] ) ); ?>>
							<?php esc_html_e( 'Send thank-you email to the lead after submission', 'asw-register' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th><label for="email_from_name"><?php esc_html_e( 'From Name', 'asw-register' ); ?></label></th>
					<td>
						<input type="text" id="email_from_name" name="email_from_name" class="regular-text"
							   value="<?php echo esc_attr( $form['email_from_name'] ?? '' ); ?>"
							   placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
					</td>
				</tr>
				<tr>
					<th><label for="email_from_addr"><?php esc_html_e( 'From Email', 'asw-register' ); ?></label></th>
					<td>
						<input type="email" id="email_from_addr" name="email_from_addr" class="regular-text"
							   value="<?php echo esc_attr( $form['email_from_addr'] ?? '' ); ?>"
							   placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
					</td>
				</tr>
				<tr>
					<th><label for="email_subject"><?php esc_html_e( 'Subject', 'asw-register' ); ?></label></th>
					<td>
						<input type="text" id="email_subject" name="email_subject" class="large-text"
							   value="<?php echo esc_attr( $form['email_subject'] ?? '' ); ?>">
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Body', 'asw-register' ); ?></th>
					<td>
						<?php
						wp_editor(
							! empty( $form['email_body'] ) ? wp_kses_post( $form['email_body'] ) : '',
							'email_body',
							array(
								'textarea_name' => 'email_body',
								'media_buttons' => false,
								'teeny'         => true,
								'textarea_rows' => 10,
							)
						);
						?>
						<p class="description">
							<?php esc_html_e( 'Available merge tags:', 'asw-register' ); ?>
							<code>{first_name}</code> <code>{last_name}</code> <code>{email}</code> <code>{tel}</code>
							<code>{form_name}</code> <code>{date}</code> <code>{site_name}</code>
						</p>
					</td>
				</tr>
			</table>
		</div>

		<!-- Tab: Success -->
		<div class="asw-reg-tab-panel" id="asw-reg-tab-success">
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Success Message', 'asw-register' ); ?></th>
					<td>
						<?php
						wp_editor(
							! empty( $form['success_message'] ) ? wp_kses_post( $form['success_message'] ) : '',
							'success_message',
							array(
								'textarea_name' => 'success_message',
								'media_buttons' => false,
								'teeny'         => true,
								'textarea_rows' => 6,
							)
						);
						?>
						<p class="description"><?php esc_html_e( 'Displayed inline after successful submission. Leave empty for the default message.', 'asw-register' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="redirect_url"><?php esc_html_e( 'Redirect URL', 'asw-register' ); ?></label></th>
					<td>
						<input type="url" id="redirect_url" name="redirect_url" class="large-text"
							   value="<?php echo esc_attr( $form['redirect_url'] ?? '' ); ?>"
							   placeholder="https://...">
						<p class="description"><?php esc_html_e( 'If set, the user is redirected instead of seeing the success message.', 'asw-register' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<?php submit_button( $is_new ? __( 'Create Form', 'asw-register' ) : __( 'Save Changes', 'asw-register' ) ); ?>
	</form>

	<!-- Hidden template row for JS cloning (outside form so inputs are not submitted) -->
	<table id="asw-reg-field-template-wrap" style="display:none" aria-hidden="true">
		<tbody>
			<tr id="asw-reg-field-template" class="asw-reg-custom-row" data-field-type="custom">
				<td><span class="asw-reg-drag-handle dashicons dashicons-menu"></span></td>
				<td>
					<input type="text" name="fields[custom_fields][__IDX__][key]" class="asw-reg-cf-key" style="width:120px" placeholder="field_key">
				</td>
				<td>—</td>
				<td>
					<input type="checkbox" name="fields[custom_fields][__IDX__][required]" value="1">
				</td>
				<td>
					<input type="text" name="fields[custom_fields][__IDX__][label]" class="asw-reg-cf-label regular-text" placeholder="<?php esc_attr_e( 'Label', 'asw-register' ); ?>">
				</td>
				<td>
					<input type="text" name="fields[custom_fields][__IDX__][placeholder]" class="asw-reg-cf-placeholder regular-text" placeholder="<?php esc_attr_e( 'Placeholder', 'asw-register' ); ?>">
				</td>
				<td>
					<select name="fields[custom_fields][__IDX__][type]" class="asw-reg-cf-type">
						<?php foreach ( $cf_type_options as $t ) : ?>
							<option value="<?php echo esc_attr( $t ); ?>"><?php echo esc_html( ucfirst( $t ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
				<td class="asw-reg-cf-options-cell" style="display:none">
					<textarea name="fields[custom_fields][__IDX__][options]" class="asw-reg-cf-options" rows="3" placeholder="<?php esc_attr_e( 'One option per line', 'asw-register' ); ?>"></textarea>
				</td>
				<td><button type="button" class="button button-small asw-reg-remove-field"><?php esc_html_e( 'Remove', 'asw-register' ); ?></button></td>
			</tr>
		</tbody>
	</table>
</div>
