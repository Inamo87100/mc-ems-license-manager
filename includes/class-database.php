<?php
/**
 * Database class for MC EMS License Manager
 *
 * Handles creation and management of the wp_mc_ems_licenses and
 * wp_mc_ems_product_licenses tables.
 *
 * @package MC_EMS_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MC_EMS_Database {

	/**
	 * Licenses table name (without prefix).
	 *
	 * @var string
	 */
	const TABLE = 'mc_ems_licenses';

	/**
	 * Product-license associations table name (without prefix).
	 *
	 * @var string
	 */
	const PRODUCT_TABLE = 'mc_ems_product_licenses';

	/**
	 * Return the full licenses table name with wpdb prefix.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Return the full product-licenses table name with wpdb prefix.
	 *
	 * @return string
	 */
	public static function product_table_name() {
		global $wpdb;
		return $wpdb->prefix . self::PRODUCT_TABLE;
	}

	/**
	 * Create or update the licenses table.
	 *
	 * @return void
	 */
	public static function create_table() {
		global $wpdb;

		$table           = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id           INT          NOT NULL AUTO_INCREMENT,
			user_id      INT          NOT NULL,
			product_id   INT          DEFAULT NULL,
			license_key  VARCHAR(50)  NOT NULL,
			site_url     VARCHAR(255) DEFAULT NULL,
			status       VARCHAR(20)  NOT NULL DEFAULT 'active',
			created_at   DATETIME     NOT NULL,
			activated_at DATETIME     DEFAULT NULL,
			expires_at   DATETIME     DEFAULT NULL,
			updated_at   DATETIME     NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY   license_key (license_key),
			KEY          user_id     (user_id),
			KEY          product_id  (product_id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		self::backfill_activated_at();
	}

	/**
	 * Create the product-licenses associations table on plugin activation.
	 *
	 * @return void
	 */
	public static function create_product_licenses_table() {
		global $wpdb;

		$table           = self::product_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id            INT      NOT NULL AUTO_INCREMENT,
			product_id    INT      NOT NULL,
			duration_days INT      NOT NULL DEFAULT 365,
			created_at    DATETIME NOT NULL,
			updated_at    DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY   product_id (product_id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Ensure activated_at is populated for existing rows.
	 *
	 * @return void
	 */
	public static function backfill_activated_at() {
		global $wpdb;

		$table = self::table_name();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "UPDATE {$table} SET activated_at = created_at WHERE activated_at IS NULL AND status = 'active'" );
		// phpcs:enable
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
