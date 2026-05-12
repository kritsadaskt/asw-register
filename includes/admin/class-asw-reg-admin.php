<?php
/**
 * Admin menu registration and shared admin hooks.
 *
 * @package asw-register
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ASW_Reg_Admin
 */
class ASW_Reg_Admin {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'admin_menu',            array( __CLASS__, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_post_asw_reg_save_form',    array( 'ASW_Reg_Form_Edit_Page', 'handle_save' ) );
		add_action( 'admin_post_asw_reg_delete_form',  array( __CLASS__, 'handle_delete_form' ) );
		add_action( 'admin_post_asw_reg_delete_lead',  array( __CLASS__, 'handle_delete_lead' ) );
		add_action( 'admin_post_asw_reg_save_settings', array( __CLASS__, 'handle_save_settings' ) );
	}

	/**
	 * Register admin menu pages.
	 */
	public static function register_menus() {
		add_menu_page(
			__( 'ASW Register', 'asw-register' ),
			__( 'ASW Register', 'asw-register' ),
			'manage_options',
			'asw-reg-forms',
			array( __CLASS__, 'page_forms' ),
			'dashicons-email-alt',
			56
		);

		add_submenu_page(
			'asw-reg-forms',
			__( 'Forms', 'asw-register' ),
			__( 'Forms', 'asw-register' ),
			'manage_options',
			'asw-reg-forms',
			array( __CLASS__, 'page_forms' )
		);

		add_submenu_page(
			'asw-reg-forms',
			__( 'Add New Form', 'asw-register' ),
			__( 'Add New', 'asw-register' ),
			'manage_options',
			'asw-reg-form-edit',
			array( __CLASS__, 'page_form_edit' )
		);

		add_submenu_page(
			'asw-reg-forms',
			__( 'Leads', 'asw-register' ),
			__( 'Leads', 'asw-register' ),
			'manage_options',
			'asw-reg-leads',
			array( __CLASS__, 'page_leads' )
		);

		add_submenu_page(
			'asw-reg-forms',
			__( 'Settings', 'asw-register' ),
			__( 'Settings', 'asw-register' ),
			'manage_options',
			'asw-reg-settings',
			array( __CLASS__, 'page_settings' )
		);
	}

	/**
	 * Enqueue admin CSS/JS on our pages.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_assets( $hook ) {
		$our_pages = array(
			'toplevel_page_asw-reg-forms',
			'asw-register_page_asw-reg-form-edit',
			'asw-register_page_asw-reg-leads',
			'asw-register_page_asw-reg-settings',
		);

		if ( ! in_array( $hook, $our_pages, true ) ) {
			return;
		}

		$admin_script_deps = array( 'jquery', 'jquery-ui-sortable' );
		if ( 'asw-register_page_asw-reg-form-edit' === $hook ) {
			wp_enqueue_style(
				'asw-reg-select2',
				ASW_REG_URL . 'assets/vendor/select2/select2.min.css',
				array(),
				'4.1.0-rc.0'
			);
			wp_enqueue_script(
				'asw-reg-select2',
				ASW_REG_URL . 'assets/vendor/select2/select2.min.js',
				array( 'jquery' ),
				'4.1.0-rc.0',
				true
			);
			$admin_script_deps[] = 'asw-reg-select2';
		}

		wp_enqueue_style(
			'asw-reg-admin',
			ASW_REG_URL . 'assets/css/asw-reg-admin.css',
			array(),
			ASW_REG_VERSION
		);

		wp_enqueue_script(
			'asw-reg-admin',
			ASW_REG_URL . 'assets/js/asw-reg-admin.js',
			$admin_script_deps,
			ASW_REG_VERSION,
			true
		);

		// wp_editor needs media.
		wp_enqueue_media();
	}

	/** Render Forms list page. */
	public static function page_forms() {
		include ASW_REG_DIR . 'templates/admin/page-forms.php';
	}

	/** Render Form edit/add page. */
	public static function page_form_edit() {
		include ASW_REG_DIR . 'templates/admin/page-form-edit.php';
	}

	/** Render Leads page. */
	public static function page_leads() {
		include ASW_REG_DIR . 'templates/admin/page-leads.php';
	}

	/** Render Settings page. */
	public static function page_settings() {
		include ASW_REG_DIR . 'templates/admin/page-settings.php';
	}

	/**
	 * Handle form deletion.
	 */
	public static function handle_delete_form() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'asw-register' ) );
		}
		check_admin_referer( 'asw_reg_delete_form' );
		$form_id = isset( $_GET['form_id'] ) ? (int) $_GET['form_id'] : 0;
		if ( $form_id ) {
			ASW_Reg_Form_Manager::delete_form( $form_id );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=asw-reg-forms&deleted=1' ) );
		exit;
	}

	/**
	 * Handle lead deletion.
	 */
	public static function handle_delete_lead() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'asw-register' ) );
		}
		check_admin_referer( 'asw_reg_delete_lead' );
		$lead_id = isset( $_GET['lead_id'] ) ? (int) $_GET['lead_id'] : 0;
		if ( $lead_id ) {
			ASW_Reg_Lead_Manager::delete_lead( $lead_id );
		}
		$back = isset( $_GET['redirect'] ) ? esc_url_raw( wp_unslash( $_GET['redirect'] ) ) : admin_url( 'admin.php?page=asw-reg-leads' );
		wp_safe_redirect( $back );
		exit;
	}

	/**
	 * Handle settings save.
	 */
	public static function handle_save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'asw-register' ) );
		}
		check_admin_referer( 'asw_reg_save_settings' );

		update_option( 'asw_reg_default_from_name',           sanitize_text_field( wp_unslash( $_POST['default_from_name'] ?? '' ) ) );
		update_option( 'asw_reg_default_from_email',          sanitize_email( wp_unslash( $_POST['default_from_email'] ?? '' ) ) );
		update_option( 'asw_reg_delete_data_on_uninstall',    isset( $_POST['delete_data_on_uninstall'] ) ? '1' : '0' );

		wp_safe_redirect( admin_url( 'admin.php?page=asw-reg-settings&saved=1' ) );
		exit;
	}
}
