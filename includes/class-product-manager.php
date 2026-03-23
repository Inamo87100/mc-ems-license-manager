<?php
/**
 * Product Manager class for MC EMS License Manager
 *
 * Manages the associations between WooCommerce products and license durations.
 *
 * @package MC_EMS_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MC_EMS_Product_Manager {

	/**
	 * Return all products that have a license association.
	 *
	 * @return array[]
	 */
	public function get_all_products() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->get_results(
			'SELECT * FROM ' . MC_EMS_Database::product_table_name() . ' ORDER BY id DESC',
			ARRAY_A
		);
	}

	/**
	 * Associate a WooCommerce product with a license duration.
	 *
	 * @param int $product_id    WooCommerce product ID.
	 * @param int $duration_days License duration in days.
	 * @return bool
	 */
	public function add_product( $product_id, $duration_days = 365 ) {
		global $wpdb;

		$product_id    = absint( $product_id );
		$duration_days = absint( $duration_days );

		if ( ! $product_id || ! $duration_days ) {
			return false;
		}

		$now    = current_time( 'mysql' );
		$result = $wpdb->insert(
			MC_EMS_Database::product_table_name(),
			array(
				'product_id'    => $product_id,
				'duration_days' => $duration_days,
				'created_at'    => $now,
				'updated_at'    => $now,
			),
			array( '%d', '%d', '%s', '%s' )
		);

		return false !== $result;
	}

	/**
	 * Update the license duration for an associated product.
	 *
	 * @param int $product_id    WooCommerce product ID.
	 * @param int $duration_days New license duration in days.
	 * @return bool
	 */
	public function update_product( $product_id, $duration_days ) {
		global $wpdb;

		$product_id    = absint( $product_id );
		$duration_days = absint( $duration_days );

		if ( ! $product_id || ! $duration_days ) {
			return false;
		}

		$result = $wpdb->update(
			MC_EMS_Database::product_table_name(),
			array(
				'duration_days' => $duration_days,
				'updated_at'    => current_time( 'mysql' ),
			),
			array( 'product_id' => $product_id ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Remove a product-license association.
	 *
	 * @param int $product_id WooCommerce product ID.
	 * @return bool
	 */
	public function delete_product( $product_id ) {
		global $wpdb;

		$result = $wpdb->delete(
			MC_EMS_Database::product_table_name(),
			array( 'product_id' => absint( $product_id ) ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Get the license duration (days) for a product.
	 *
	 * @param int $product_id WooCommerce product ID.
	 * @return int|null Duration in days, or null if not found.
	 */
	public function get_product_duration( $product_id ) {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT duration_days FROM ' . MC_EMS_Database::product_table_name() . ' WHERE product_id = %d',
				absint( $product_id )
			),
			ARRAY_A
		);

		return $row ? (int) $row['duration_days'] : null;
	}

	/**
	 * Check whether a product has a license association.
	 *
	 * @param int $product_id WooCommerce product ID.
	 * @return bool
	 */
	public function is_product_licensed( $product_id ) {
		return null !== $this->get_product_duration( $product_id );
	}
}
