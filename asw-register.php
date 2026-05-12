<?php
/**
 * Plugin Name:       ASW Register
 * Plugin URI:        https://thetitle.co.th
 * Description:       Multi-form lead capture with UTM attribution, API posting, email notifications, and Excel/CSV export.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            The Title
 * Text Domain:       asw-register
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'ASW_REG_VERSION', '1.0.0' );
define( 'ASW_REG_FILE', __FILE__ );
define( 'ASW_REG_DIR', plugin_dir_path( __FILE__ ) );
define( 'ASW_REG_URL', plugin_dir_url( __FILE__ ) );

// Autoload via Composer (PhpSpreadsheet).
if ( file_exists( ASW_REG_DIR . 'vendor/autoload.php' ) ) {
	require_once ASW_REG_DIR . 'vendor/autoload.php';
}

// Load plugin classes.
require_once ASW_REG_DIR . 'includes/class-asw-reg-activator.php';
require_once ASW_REG_DIR . 'includes/class-asw-reg-field-registry.php';
require_once ASW_REG_DIR . 'includes/class-asw-reg-form-manager.php';
require_once ASW_REG_DIR . 'includes/class-asw-reg-lead-manager.php';
require_once ASW_REG_DIR . 'includes/class-asw-reg-utm-reader.php';
require_once ASW_REG_DIR . 'includes/class-asw-reg-api-connector.php';
require_once ASW_REG_DIR . 'includes/class-asw-reg-email-sender.php';
require_once ASW_REG_DIR . 'includes/class-asw-reg-form-handler.php';
require_once ASW_REG_DIR . 'includes/class-asw-reg-page-options.php';
require_once ASW_REG_DIR . 'includes/class-asw-reg-shortcode.php';
require_once ASW_REG_DIR . 'includes/class-asw-reg-form-injector.php';
require_once ASW_REG_DIR . 'includes/admin/class-asw-reg-admin.php';
require_once ASW_REG_DIR . 'includes/admin/class-asw-reg-forms-list-table.php';
require_once ASW_REG_DIR . 'includes/admin/class-asw-reg-leads-list-table.php';
require_once ASW_REG_DIR . 'includes/admin/class-asw-reg-form-edit-page.php';
require_once ASW_REG_DIR . 'includes/admin/class-asw-reg-export-handler.php';
require_once ASW_REG_DIR . 'includes/class-asw-reg-plugin.php';

// Activation / deactivation hooks.
register_activation_hook( __FILE__, array( 'ASW_Reg_Activator', 'activate' ) );

// Boot.
add_action( 'plugins_loaded', array( 'ASW_Reg_Plugin', 'get_instance' ) );
