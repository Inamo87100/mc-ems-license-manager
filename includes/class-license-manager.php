<?php
/**
 * License Manager core class
 *
 * Handles creation, validation, activation, deactivation, and revocation of licenses.
 *
 * @package MC_EMS_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MC_EMS_License_Manager {

	/**
	 * Generate a unique license key in the format MC-XXXXX-XXXXX-XXXXX.
	 *
	 * @return string
	 */
	public function generate_license_key() {
		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$key   = 'MC';

		for ( $g = 0; $g < 3; $g++ ) {
			$key .= '-';
			for ( $c = 0; $c < 5; $c++ ) {
				$key .= $chars[ wp_rand( 0, strlen( $chars ) - 1 ) ];
			}
		}

		return $key;
	}

	/**
	 * Create a new license for a WordPress user.
	 *
	 * @param int         $user_id    WordPress user ID.
	 * @param int|null    $duration   Duration in days. NULL = never expires.
	 * @param string|null $site_url   Site URL to bind the license to.
	 * @param int|null    $product_id WooCommerce product ID (optional).
	 * @return int|false Inserted license ID on success, false on failure.
	 */
	public function create_license( $user_id, $duration = null, $site_url = null, $product_id = null ) {
		global $wpdb;

		$user_id = absint( $user_id );
		if ( ! $user_id || ! get_userdata( $user_id ) ) {
			return false;
		}

		// Generate a key that does not yet exist in the database.
		do {
			$license_key = $this->generate_license_key();
			$exists      = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT id FROM ' . MC_EMS_Database::table_name() . ' WHERE license_key = %s',
					$license_key
				)
			);
		} while ( $exists );

		$now        = current_time( 'mysql' );
		$expires_at = null;
		if ( ! is_null( $duration ) ) {
			$duration   = absint( $duration );
			$expires_at = date( 'Y-m-d H:i:s', strtotime( "+{$duration} days", strtotime( $now ) ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		}

		$data = array(
			'user_id'     => $user_id,
			'license_key' => $license_key,
			'site_url'    => $site_url ? esc_url_raw( $site_url ) : null,
			'status'      => 'active',
			'created_at'  => $now,
			'expires_at'  => $expires_at,
			'updated_at'  => $now,
		);

		$formats = array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' );

		// Only include product_id when it is explicitly set to avoid inserting 0 instead of NULL.
		if ( ! is_null( $product_id ) ) {
			$data['product_id'] = absint( $product_id );
			$formats[]          = '%d';
		}

		$result = $wpdb->insert( MC_EMS_Database::table_name(), $data, $formats );

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get an existing license for a specific user and product.
	 *
	 * @param int $user_id    WordPress user ID.
	 * @param int $product_id WooCommerce product ID.
	 * @return array|null License row, or null if not found.
	 */
	public function get_license_by_user_product( $user_id, $product_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . MC_EMS_Database::table_name() . ' WHERE user_id = %d AND product_id = %d LIMIT 1',
				absint( $user_id ),
				absint( $product_id )
			),
			ARRAY_A
		);
	}

	/**
	 * Validate a license key.
	 *
	 * @param string $license_key License key to validate.
	 * @param int    $user_id     Expected WordPress user ID.
	 * @param string $site_url    Site URL requesting validation.
	 * @return array|false License row array on success, false on failure.
	 */
	public function validate_license( $license_key, $user_id = 0, $site_url = '' ) {
		global $wpdb;

		$license = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . MC_EMS_Database::table_name() . ' WHERE license_key = %s',
				sanitize_text_field( $license_key )
			),
			ARRAY_A
		);

		if ( ! $license ) {
			return false;
		}

		// Check user.
		if ( $user_id && (int) $license['user_id'] !== (int) $user_id ) {
			return false;
		}

		$incoming_site = $site_url ? trailingslashit( esc_url_raw( $site_url ) ) : '';
		$stored_site   = ! empty( $license['site_url'] ) ? trailingslashit( esc_url_raw( $license['site_url'] ) ) : '';

		// First valid use binds the license to the requesting site.
		if ( $incoming_site && empty( $stored_site ) && ! empty( $license['id'] ) ) {
			$this->update_site_url( (int) $license['id'], $incoming_site );
			$license['site_url'] = $incoming_site;
			$stored_site         = $incoming_site;
		}

		// Block use on a different site once the license has been bound.
		if ( $incoming_site && $stored_site && $incoming_site !== $stored_site ) {
			return false;
		}

		// Check status.
		if ( 'active' !== $license['status'] ) {
			return false;
		}

		// Check expiry.
		if ( ! empty( $license['expires_at'] ) ) {
			$now     = strtotime( current_time( 'mysql' ) );
			$expires = strtotime( $license['expires_at'] );
			if ( $expires < $now ) {
				// Auto-mark as expired.
				$this->update_status( (int) $license['id'], 'expired' );
				return false;
			}
		}

		return $license;
	}

	/**
	 * Bind a license to a specific site URL.
	 *
	 * @param int    $license_id License ID.
	 * @param string $site_url   Site URL.
	 * @return bool
	 */
	public function update_site_url( $license_id, $site_url ) {
		global $wpdb;

		$license_id = absint( $license_id );
		$site_url   = $site_url ? trailingslashit( esc_url_raw( $site_url ) ) : '';

		if ( ! $license_id || empty( $site_url ) ) {
			return false;
		}

		$result = $wpdb->update(
			MC_EMS_Database::table_name(),
			array(
				'site_url'   => $site_url,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $license_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Get a single license by ID.
	 *
	 * @param int $license_id License ID.
	 * @return array|null
	 */
	public function get_license( $license_id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . MC_EMS_Database::table_name() . ' WHERE id = %d',
				absint( $license_id )
			),
			ARRAY_A
		);
	}

	/**
	 * Get all licenses, optionally filtered.
	 *
	 * @param array $args  Optional query args: status, user_id, search, orderby, order, per_page, paged.
	 * @return array
	 */
	public function get_licenses( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status'   => '',
			'user_id'  => 0,
			'search'   => '',
			'orderby'  => 'id',
			'order'    => 'DESC',
			'per_page' => 20,
			'paged'    => 1,
		);
		$args = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$params[] = sanitize_text_field( $args['status'] );
		}

		if ( ! empty( $args['user_id'] ) ) {
			$where[]  = 'user_id = %d';
			$params[] = absint( $args['user_id'] );
		}

		if ( ! empty( $args['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where[]  = '(license_key LIKE %s OR site_url LIKE %s)';
			$params[] = $like;
			$params[] = $like;
		}

		$allowed_orderby = array( 'id', 'user_id', 'license_key', 'status', 'created_at', 'expires_at' );
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'id';
		$order   = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$per_page = max( 1, absint( $args['per_page'] ) );
		$offset   = ( max( 1, absint( $args['paged'] ) ) - 1 ) * $per_page;

		$where_sql = implode( ' AND ', $where );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
		if ( $params ) {
			$sql = $wpdb->prepare(
				"SELECT * FROM " . MC_EMS_Database::table_name() . " WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
				array_merge( $params, array( $per_page, $offset ) )
			);
		} else {
			$sql = $wpdb->prepare(
				"SELECT * FROM " . MC_EMS_Database::table_name() . " WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
				$per_page,
				$offset
			);
		}
		// phpcs:enable

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Count total licenses matching filters.
	 *
	 * @param array $args Same as get_licenses().
	 * @return int
	 */
	public function count_licenses( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status'  => '',
			'user_id' => 0,
			'search'  => '',
		);
		$args = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$params[] = sanitize_text_field( $args['status'] );
		}

		if ( ! empty( $args['user_id'] ) ) {
			$where[]  = 'user_id = %d';
			$params[] = absint( $args['user_id'] );
		}

		if ( ! empty( $args['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where[]  = '(license_key LIKE %s OR site_url LIKE %s)';
			$params[] = $like;
			$params[] = $like;
		}

		$where_sql = implode( ' AND ', $where );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
		if ( $params ) {
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM " . MC_EMS_Database::table_name() . " WHERE {$where_sql}",
					$params
				)
			);
		} else {
			$count = $wpdb->get_var(
				"SELECT COUNT(*) FROM " . MC_EMS_Database::table_name() . " WHERE {$where_sql}"
			);
		}
		// phpcs:enable

		return (int) $count;
	}

	/**
	 * Update the status of a license.
	 *
	 * @param int    $license_id License ID.
	 * @param string $status     New status: 'active', 'inactive', or 'expired'.
	 * @return bool
	 */
	public function update_status( $license_id, $status ) {
		global $wpdb;

		$allowed = array( 'active', 'inactive', 'expired' );
		if ( ! in_array( $status, $allowed, true ) ) {
			return false;
		}

		$result = $wpdb->update(
			MC_EMS_Database::table_name(),
			array(
				'status'     => $status,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => absint( $license_id ) ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Extend a license's expiration by a number of days.
	 *
	 * @param int $license_id License ID.
	 * @param int $days       Number of days to extend.
	 * @return bool
	 */
	public function extend_license( $license_id, $days ) {
		global $wpdb;

		$license = $this->get_license( absint( $license_id ) );
		if ( ! $license ) {
			return false;
		}

		$days = absint( $days );
		if ( ! $days ) {
			return false;
		}

		$base       = ( ! empty( $license['expires_at'] ) ) ? $license['expires_at'] : current_time( 'mysql' );
		$expires_at = date( 'Y-m-d H:i:s', strtotime( "+{$days} days", strtotime( $base ) ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

		$result = $wpdb->update(
			MC_EMS_Database::table_name(),
			array(
				'expires_at' => $expires_at,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => absint( $license_id ) ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Delete a license permanently.
	 *
	 * @param int $license_id License ID.
	 * @return bool
	 */
	public function delete_license( $license_id ) {
		global $wpdb;

		$result = $wpdb->delete(
			MC_EMS_Database::table_name(),
			array( 'id' => absint( $license_id ) ),
			array( '%d' )
		);

		return false !== $result;
	}
}
