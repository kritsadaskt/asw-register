<?php
/**
 * [asw_register_form] shortcode.
 *
 * @package asw-register
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ASW_Reg_Shortcode
 */
class ASW_Reg_Shortcode {

	/**
	 * Register shortcode and asset hooks.
	 */
	public static function init() {
		add_shortcode( 'asw_register_form', array( __CLASS__, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue_assets' ) );
	}

	/**
	 * Enqueue front-end assets on pages that use the shortcode.
	 */
	public static function maybe_enqueue_assets() {
		global $post;
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'asw_register_form' ) ) {
			self::enqueue_assets();
			return;
		}
		if ( is_singular() ) {
			$pid = (int) get_queried_object_id();
			if ( $pid > 0 && ! empty( ASW_Reg_Form_Injector::get_forms_for_post( $pid ) ) ) {
				self::enqueue_assets();
			}
		}
	}

	/**
	 * Enqueue CSS + JS.
	 */
	public static function enqueue_assets() {
		wp_enqueue_style(
			'asw-reg-form-tw',
			ASW_REG_URL . 'assets/build/asw-reg-form-tw.css',
			array(),
			ASW_REG_VERSION
		);

		wp_enqueue_script(
			'asw-reg-form',
			ASW_REG_URL . 'assets/js/asw-reg-form.js',
			array(),
			ASW_REG_VERSION,
			true
		);

		wp_localize_script(
			'asw-reg-form',
			'aswRegister',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public static function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'    => 0,
				'class' => '',
			),
			$atts,
			'asw_register_form'
		);

		$form_id = (int) $atts['id'];
		if ( ! $form_id ) {
			if ( ! wp_style_is( 'asw-reg-form-tw', 'enqueued' ) ) {
				self::enqueue_assets();
			}
			return '<p class="aswr:rounded-lg aswr:border aswr:border-red-200 aswr:bg-red-50 aswr:px-3 aswr:py-2 aswr:text-sm aswr:text-red-800">' . esc_html__( 'Please specify a form ID: [asw_register_form id="1"]', 'asw-register' ) . '</p>';
		}

		$form = ASW_Reg_Form_Manager::get_form( $form_id );
		if ( ! $form || 'active' !== $form['status'] ) {
			return '';
		}

		// Enqueue assets inline if not already queued.
		if ( ! wp_script_is( 'asw-reg-form', 'enqueued' ) ) {
			self::enqueue_assets();
		}

		$fields_config = is_array( $form['fields_config'] ) ? $form['fields_config'] : array();
		$registry      = ASW_Reg_Field_Registry::get_fields();
		$extra_class   = ! empty( $atts['class'] ) ? ' ' . sanitize_html_class( $atts['class'] ) : '';

		ob_start();
		include ASW_REG_DIR . 'templates/form-template.php';
		return ob_get_clean();
	}
}
