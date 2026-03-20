<?php
/**
 * Database class for MC EMS License Manager
 *
 * Handles creation and management of the wp_mc_ems_licenses table.
 *
 * @package MC_EMS_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MC_EMS_Database {

	/**
	 * Table name (without prefix).
	 *
	 * @var string
	 */
	const TABLE = 'mc_ems_licenses';

	/**
	 * Return the full table name with wpdb prefix.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Create the licenses table on plugin activation.
	 *
	 * @return void
	 */
	public static function create_table() {
		global $wpdb;

		$table      = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id          INT          NOT NULL AUTO_INCREMENT,
			user_id     INT          NOT NULL,
			license_key VARCHAR(50)  NOT NULL,
			site_url    VARCHAR(255) DEFAULT NULL,
			status      VARCHAR(20)  NOT NULL DEFAULT 'active',
			created_at  DATETIME     NOT NULL,
			expires_at  DATETIME     DEFAULT NULL,
			updated_at  DATETIME     NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY   license_key (license_key),
			KEY          user_id     (user_id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Drop the licenses table on plugin uninstall.
	 *
	 * @return void
	 */
	public static function drop_table() {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}
}
