<?php
/**
 * Auto-inject register form into theme anchor #register_form on selected project pages.
 *
 * @package asw-register
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ASW_Reg_Form_Injector
 */
class ASW_Reg_Form_Injector {

	/**
	 * Boot hooks.
	 */
	public static function init() {
		add_action( 'wp_footer', array( __CLASS__, 'maybe_inject' ), 5 );
	}

	/**
	 * Append configured form(s) into #register_form when viewing the selected singular project.
	 */
	public static function maybe_inject() {
		if ( is_admin() || ! is_singular() ) {
			return;
		}

		$post_id = (int) get_queried_object_id();
		if ( $post_id <= 0 ) {
			return;
		}

		$forms = self::get_forms_for_post( $post_id );
		if ( empty( $forms ) ) {
			return;
		}

		if ( ! wp_script_is( 'asw-reg-form', 'enqueued' ) ) {
			ASW_Reg_Shortcode::enqueue_assets();
		}

		$html = '';
		foreach ( $forms as $form ) {
			$html .= ASW_Reg_Shortcode::render( array( 'id' => (int) $form['id'] ) );
		}

		if ( '' === $html ) {
			return;
		}

		echo '<div id="asw-reg-inject-bundle" hidden="hidden" style="display:none;">';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode output is intentional HTML.
		echo $html;
		echo '</div>';
		?>
		<script>
		(function () {
			var target = document.getElementById('register_form');
			var bundle = document.getElementById('asw-reg-inject-bundle');
			if (!target || !bundle) { return; }
			while (bundle.firstChild) {
				target.appendChild(bundle.firstChild);
			}
			bundle.parentNode.removeChild(bundle);
		})();
		</script>
		<?php
	}

	/**
	 * Active forms targeting this post ID.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public static function get_forms_for_post( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 ) {
			return array();
		}
		$matched = array();
		foreach ( ASW_Reg_Form_Manager::get_forms() as $form ) {
			if ( 'active' !== ( $form['status'] ?? '' ) ) {
				continue;
			}
			if ( (int) ( $form['inject_post_id'] ?? 0 ) !== $post_id ) {
				continue;
			}
			$matched[] = $form;
		}
		return $matched;
	}
}
