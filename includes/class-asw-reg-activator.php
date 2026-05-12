<?php
/**
 * Fired during plugin activation.
 *
 * @package asw-register
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ASW_Reg_Activator
 */
class ASW_Reg_Activator {

	const DB_VERSION = '1.1';

	/**
	 * Run on activation.
	 */
	public static function activate() {
		self::create_tables();
		update_option( 'asw_reg_db_version', self::DB_VERSION );
	}

	/**
	 * Create/update database tables using dbDelta.
	 */
	public static function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Forms table.
		$sql_forms = "CREATE TABLE {$wpdb->prefix}asw_register_forms (
			id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name        VARCHAR(255)        NOT NULL DEFAULT '',
			slug        VARCHAR(191)        NOT NULL DEFAULT '',
			status      VARCHAR(20)         NOT NULL DEFAULT 'active',
			fields_config LONGTEXT,
			api_enabled  TINYINT(1)         NOT NULL DEFAULT 0,
			api_endpoint VARCHAR(500)       NOT NULL DEFAULT '',
			api_method   VARCHAR(10)        NOT NULL DEFAULT 'POST',
			api_headers  LONGTEXT,
			email_enabled   TINYINT(1)      NOT NULL DEFAULT 0,
			email_subject   VARCHAR(255)    NOT NULL DEFAULT '',
			email_body      LONGTEXT,
			email_from_name VARCHAR(255)    NOT NULL DEFAULT '',
			email_from_addr VARCHAR(255)    NOT NULL DEFAULT '',
			success_message LONGTEXT,
			redirect_url    VARCHAR(500)    NOT NULL DEFAULT '',
			inject_post_id  BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			created_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug (slug)
		) $charset_collate;";

		// Leads table.
		$sql_leads = "CREATE TABLE {$wpdb->prefix}tt_leads (
			id               BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			form_id          BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			first_name       VARCHAR(255) NOT NULL DEFAULT '',
			last_name        VARCHAR(255) NOT NULL DEFAULT '',
			tel              VARCHAR(50)  NOT NULL DEFAULT '',
			email            VARCHAR(255) NOT NULL DEFAULT '',
			utm_source       VARCHAR(255) NOT NULL DEFAULT '',
			utm_medium       VARCHAR(255) NOT NULL DEFAULT '',
			utm_campaign     VARCHAR(255) NOT NULL DEFAULT '',
			utm_term         VARCHAR(255) NOT NULL DEFAULT '',
			utm_content      VARCHAR(255) NOT NULL DEFAULT '',
			utm_gclid        VARCHAR(255) NOT NULL DEFAULT '',
			handl_landing_page VARCHAR(500) NOT NULL DEFAULT '',
			handl_original_ref VARCHAR(500) NOT NULL DEFAULT '',
			api_status       ENUM('not_sent','success','error','skipped') NOT NULL DEFAULT 'not_sent',
			api_response     TEXT,
			api_http_code    SMALLINT UNSIGNED,
			api_sent_at      DATETIME,
			email_status     ENUM('not_sent','sent','failed','skipped') NOT NULL DEFAULT 'not_sent',
			email_sent_at    DATETIME,
			extra_fields     LONGTEXT,
			ip_address       VARCHAR(45)  NOT NULL DEFAULT '',
			user_agent       TEXT,
			page_url         VARCHAR(500) NOT NULL DEFAULT '',
			created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY form_id (form_id),
			KEY created_at (created_at)
		) $charset_collate;";

		dbDelta( $sql_forms );
		dbDelta( $sql_leads );
	}
}
