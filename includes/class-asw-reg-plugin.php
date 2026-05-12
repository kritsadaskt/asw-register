<?php
/**
 * Main plugin singleton.
 *
 * @package asw-register
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ASW_Reg_Plugin
 */
class ASW_Reg_Plugin {

	/** @var ASW_Reg_Plugin|null */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return ASW_Reg_Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->init();
		}
		return self::$instance;
	}

	/** Private constructor. */
	private function __construct() {}

	/**
	 * Initialise hooks and sub-systems.
	 */
	private function init() {
		// Maybe run DB migrations after plugin update.
		add_action( 'admin_init', array( $this, 'maybe_upgrade_db' ) );

		// Admin.
		if ( is_admin() ) {
			ASW_Reg_Admin::init();
			ASW_Reg_Export_Handler::init();
		}

		// AJAX form submission (logged-in + non-logged-in).
		ASW_Reg_Form_Handler::init();

		// Shortcode.
		ASW_Reg_Shortcode::init();

		// Auto-inject on project pages (#register_form).
		ASW_Reg_Form_Injector::init();

		// i18n.
		load_plugin_textdomain( 'asw-register', false, dirname( plugin_basename( ASW_REG_FILE ) ) . '/languages' );
	}

	/**
	 * Run table creation on version mismatch.
	 */
	public function maybe_upgrade_db() {
		if ( get_option( 'asw_reg_db_version' ) !== ASW_Reg_Activator::DB_VERSION ) {
			ASW_Reg_Activator::create_tables();
			update_option( 'asw_reg_db_version', ASW_Reg_Activator::DB_VERSION );
		}
	}
}
