<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package asw-register
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$delete_data = get_option( 'asw_reg_delete_data_on_uninstall', '0' );

if ( '1' === $delete_data ) {
	global $wpdb;

	// Drop tables.
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}tt_leads" );         // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}asw_register_forms" );    // phpcs:ignore WordPress.DB.DirectDatabaseQuery

	// Delete options.
	delete_option( 'asw_reg_db_version' );
	delete_option( 'asw_reg_default_from_name' );
	delete_option( 'asw_reg_default_from_email' );
	delete_option( 'asw_reg_delete_data_on_uninstall' );
}
