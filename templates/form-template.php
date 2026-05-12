<?php
/**
 * Front-end form template.
 *
 * Variables available:
 *  - $form           (array)  Form config row
 *  - $fields_config  (array)  Per-form field config
 *  - $registry       (array)  All registered fields from ASW_Reg_Field_Registry
 *  - $extra_class    (string) Additional CSS class
 *
 * @package asw-register
 */

defined( 'ABSPATH' ) || exit;

// Build custom fields map and determine render order.
$custom_map  = array();
foreach ( $fields_config['custom_fields'] ?? array() as $cf ) {
	$custom_map[ $cf['key'] ] = $cf;
}
$field_order = ! empty( $fields_config['field_order'] ) ? $fields_config['field_order'] : array_keys( $registry );
// Append any custom fields not yet in field_order.
foreach ( array_keys( $custom_map ) as $ck ) {
	if ( ! in_array( $ck, $field_order, true ) ) {
		$field_order[] = $ck;
	}
}
$extra_class = trim( (string) $extra_class );
?>
<div class="asw-reg-form-wrap aswr:mx-auto aswr:w-full aswr:max-w-xl aswr:font-sans aswr:text-neutral-900<?php echo $extra_class ? ' ' . esc_attr( $extra_class ) : ''; ?>" id="asw-reg-form-<?php echo esc_attr( $form['id'] ); ?>">

	<div class="asw-reg-messages aswr:mb-4 aswr:min-h-0" aria-live="polite"></div>

	<form class="asw-reg-form aswr:flex aswr:flex-col aswr:gap-6"
		  data-form-id="<?php echo esc_attr( $form['id'] ); ?>"
		  data-nonce="<?php echo esc_attr( wp_create_nonce( 'asw_reg_submit_' . $form['id'] ) ); ?>"
		  novalidate>

		<?php foreach ( $field_order as $fkey ) :
			if ( isset( $registry[ $fkey ] ) ) :
				// ── Core field ──────────────────────────────────────────
				$field_def = $registry[ $fkey ];
				$form_cfg  = isset( $fields_config[ $fkey ] ) ? $fields_config[ $fkey ] : array();
				$enabled   = isset( $form_cfg['enabled'] ) ? (bool) $form_cfg['enabled'] : (bool) ( $field_def['enabled'] ?? true );

				if ( ! $enabled ) {
					continue;
				}

				$required    = isset( $form_cfg['required'] ) ? (bool) $form_cfg['required'] : (bool) ( $field_def['required'] ?? false );
				$label       = ! empty( $form_cfg['label'] )       ? $form_cfg['label']       : $field_def['label'];
				$placeholder = ! empty( $form_cfg['placeholder'] ) ? $form_cfg['placeholder'] : ( $field_def['placeholder'] ?? '' );
				$type        = $field_def['type'] ?? 'text';
				$field_id    = 'asw-reg-' . esc_attr( $form['id'] ) . '-' . esc_attr( $fkey );
				?>
				<div class="asw-reg-field asw-reg-field--<?php echo esc_attr( $fkey ); ?> aswr:flex aswr:flex-col aswr:gap-1.5">
					<label class="aswr:block aswr:text-sm aswr:font-semibold aswr:text-neutral-800" for="<?php echo esc_attr( $field_id ); ?>">
						<?php echo esc_html( $label ); ?>
						<?php if ( $required ) : ?>
							<span class="asw-reg-required aswr:ml-0.5 aswr:font-bold aswr:text-red-600" aria-hidden="true">*</span>
						<?php endif; ?>
					</label>
					<input
						type="<?php echo esc_attr( $type ); ?>"
						id="<?php echo esc_attr( $field_id ); ?>"
						name="<?php echo esc_attr( $fkey ); ?>"
						placeholder="<?php echo esc_attr( $placeholder ); ?>"
						<?php if ( $required ) : ?>required aria-required="true"<?php endif; ?>
						class="asw-reg-input aswr:block aswr:w-full aswr:rounded-xl aswr:border aswr:border-neutral-300 aswr:bg-white aswr:px-3.5 aswr:py-2.5 aswr:text-base aswr:leading-normal aswr:text-neutral-900 aswr:placeholder-neutral-400 aswr:shadow-sm aswr:outline-none aswr:transition aswr:focus:border-blue-600 aswr:focus:ring-2 aswr:focus:ring-blue-600/20 invalid:aswr:border-red-500"
						autocomplete="<?php echo esc_attr( self_autocomplete( $fkey ) ); ?>"
					>
				</div>
			<?php elseif ( isset( $custom_map[ $fkey ] ) ) :
				// ── Custom field ─────────────────────────────────────────
				$cf          = $custom_map[ $fkey ];
				$cf_type     = $cf['type'] ?? 'text';
				$cf_label    = $cf['label'] ?? $fkey;
				$cf_ph       = $cf['placeholder'] ?? '';
				$cf_required = ! empty( $cf['required'] );
				$cf_opts     = array_filter( array_map( 'trim', explode( "\n", $cf['options'] ?? '' ) ) );
				$cf_id       = 'asw-reg-' . esc_attr( $form['id'] ) . '-cf-' . esc_attr( $fkey );
				$cf_name     = 'asw_cf[' . esc_attr( $fkey ) . ']';
				$input_class = 'asw-reg-input aswr:block aswr:w-full aswr:rounded-xl aswr:border aswr:border-neutral-300 aswr:bg-white aswr:px-3.5 aswr:py-2.5 aswr:text-base aswr:leading-normal aswr:text-neutral-900 aswr:placeholder-neutral-400 aswr:shadow-sm aswr:outline-none aswr:transition aswr:focus:border-blue-600 aswr:focus:ring-2 aswr:focus:ring-blue-600/20 invalid:aswr:border-red-500';
				?>
				<div class="asw-reg-field asw-reg-field--cf-<?php echo esc_attr( $fkey ); ?> aswr:flex aswr:flex-col aswr:gap-1.5">
					<?php if ( 'radio' === $cf_type || 'checkbox' === $cf_type ) : ?>
						<fieldset class="aswr:m-0 aswr:border-0 aswr:p-0">
							<legend class="aswr:mb-2 aswr:block aswr:text-sm aswr:font-semibold aswr:text-neutral-800">
								<?php echo esc_html( $cf_label ); ?>
								<?php if ( $cf_required ) : ?><span class="asw-reg-required aswr:ml-0.5 aswr:font-bold aswr:text-red-600" aria-hidden="true">*</span><?php endif; ?>
							</legend>
							<div class="asw-reg-<?php echo esc_attr( $cf_type ); ?>-group aswr:flex aswr:flex-col aswr:gap-2">
								<?php foreach ( $cf_opts as $opt ) :
									$opt_id = $cf_id . '-' . sanitize_title( $opt );
									?>
									<label class="aswr:inline-flex aswr:cursor-pointer aswr:items-center aswr:gap-2 aswr:text-sm aswr:font-normal aswr:text-neutral-800">
										<input
											class="aswr:size-4 aswr:shrink-0 aswr:rounded aswr:border-neutral-300 aswr:text-blue-600 aswr:focus:ring-blue-600/30"
											type="<?php echo esc_attr( $cf_type ); ?>"
											id="<?php echo esc_attr( $opt_id ); ?>"
											name="<?php echo esc_attr( 'checkbox' === $cf_type ? $cf_name . '[]' : $cf_name ); ?>"
											value="<?php echo esc_attr( $opt ); ?>"
											<?php if ( $cf_required && 'radio' === $cf_type ) : ?>required<?php endif; ?>
										>
										<span><?php echo esc_html( $opt ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</fieldset>
					<?php elseif ( 'select' === $cf_type ) : ?>
						<label class="aswr:block aswr:text-sm aswr:font-semibold aswr:text-neutral-800" for="<?php echo esc_attr( $cf_id ); ?>">
							<?php echo esc_html( $cf_label ); ?>
							<?php if ( $cf_required ) : ?><span class="asw-reg-required aswr:ml-0.5 aswr:font-bold aswr:text-red-600" aria-hidden="true">*</span><?php endif; ?>
						</label>
						<select
							id="<?php echo esc_attr( $cf_id ); ?>"
							name="<?php echo esc_attr( $cf_name ); ?>"
							class="<?php echo esc_attr( $input_class ); ?>"
							<?php if ( $cf_required ) : ?>required aria-required="true"<?php endif; ?>
						>
							<?php if ( $cf_ph ) : ?>
								<option value=""><?php echo esc_html( $cf_ph ); ?></option>
							<?php else : ?>
								<option value=""></option>
							<?php endif; ?>
							<?php foreach ( $cf_opts as $opt ) : ?>
								<option value="<?php echo esc_attr( $opt ); ?>"><?php echo esc_html( $opt ); ?></option>
							<?php endforeach; ?>
						</select>
					<?php elseif ( 'textarea' === $cf_type ) : ?>
						<label class="aswr:block aswr:text-sm aswr:font-semibold aswr:text-neutral-800" for="<?php echo esc_attr( $cf_id ); ?>">
							<?php echo esc_html( $cf_label ); ?>
							<?php if ( $cf_required ) : ?><span class="asw-reg-required aswr:ml-0.5 aswr:font-bold aswr:text-red-600" aria-hidden="true">*</span><?php endif; ?>
						</label>
						<textarea
							id="<?php echo esc_attr( $cf_id ); ?>"
							name="<?php echo esc_attr( $cf_name ); ?>"
							class="<?php echo esc_attr( $input_class ); ?> aswr:min-h-32 aswr:resize-y"
							placeholder="<?php echo esc_attr( $cf_ph ); ?>"
							<?php if ( $cf_required ) : ?>required aria-required="true"<?php endif; ?>
						></textarea>
					<?php else : ?>
						<label class="aswr:block aswr:text-sm aswr:font-semibold aswr:text-neutral-800" for="<?php echo esc_attr( $cf_id ); ?>">
							<?php echo esc_html( $cf_label ); ?>
							<?php if ( $cf_required ) : ?><span class="asw-reg-required aswr:ml-0.5 aswr:font-bold aswr:text-red-600" aria-hidden="true">*</span><?php endif; ?>
						</label>
						<input
							type="<?php echo esc_attr( $cf_type ); ?>"
							id="<?php echo esc_attr( $cf_id ); ?>"
							name="<?php echo esc_attr( $cf_name ); ?>"
							class="<?php echo esc_attr( $input_class ); ?>"
							placeholder="<?php echo esc_attr( $cf_ph ); ?>"
							<?php if ( $cf_required ) : ?>required aria-required="true"<?php endif; ?>
						>
					<?php endif; ?>
				</div>
			<?php endif;
		endforeach; ?>

		<!-- Hidden UTM fields — populated by asw-reg-form.js -->
		<?php
		$utm_params = array(
			'utm_field_utm_source', 'utm_field_utm_medium', 'utm_field_utm_campaign',
			'utm_field_utm_term', 'utm_field_utm_content', 'utm_field_gclid',
			'utm_field_handl_landing_page', 'utm_field_handl_original_ref',
		);
		foreach ( $utm_params as $utm_field ) :
			?>
			<input type="hidden" name="<?php echo esc_attr( $utm_field ); ?>" value="">
		<?php endforeach; ?>
		<input type="hidden" name="page_url" value="">

		<div class="asw-reg-submit-wrap aswr:flex aswr:flex-row aswr:flex-wrap aswr:items-center aswr:gap-3 aswr:pt-2">
			<button type="submit" class="asw-reg-btn asw-reg-btn--submit aswr:inline-flex aswr:min-w-[10rem] aswr:flex-1 aswr:items-center aswr:justify-center aswr:rounded-full aswr:bg-blue-600 aswr:px-8 aswr:py-3 aswr:text-base aswr:font-semibold aswr:text-white aswr:shadow-md aswr:transition-colors hover:aswr:bg-blue-700 focus-visible:aswr:outline focus-visible:aswr:outline-2 focus-visible:aswr:outline-offset-2 focus-visible:aswr:outline-blue-600 disabled:aswr:cursor-not-allowed disabled:aswr:opacity-60">
				<?php esc_html_e( 'Submit', 'asw-register' ); ?>
			</button>
			<span class="asw-reg-spinner aswr:inline-block aswr:size-5 aswr:shrink-0 aswr:rounded-full aswr:border-2 aswr:border-blue-600/25 aswr:border-t-blue-600 aswr:animate-spin" hidden aria-hidden="true"></span>
		</div>

	</form>

</div><!-- .asw-reg-form-wrap -->

<?php
/**
 * Map field keys to autocomplete tokens.
 *
 * @param string $key Field key.
 * @return string
 */
function self_autocomplete( $key ) {
	$map = array(
		'first_name' => 'given-name',
		'last_name'  => 'family-name',
		'email'      => 'email',
		'tel'        => 'tel',
	);
	return isset( $map[ $key ] ) ? $map[ $key ] : 'on';
}
