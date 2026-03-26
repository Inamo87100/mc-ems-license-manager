<?php
/**
 * REST endpoints for MC-EMS License Manager
 *
 * @package MC_EMS_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register REST routes.
 */
add_action( 'rest_api_init', 'mcems_register_license_rest_routes' );

/**
 * Registers all REST routes used by the license manager.
 *
 * @return void
 */
function mcems_register_license_rest_routes() {
	register_rest_route(
		'mcems/v1',
		'/license/verify',
		array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => 'mcems_rest_check_license_official',
			'permission_callback' => '__return_true',
			'args'                => array(
				'license_key' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'site_url'    => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'esc_url_raw',
				),
			),
		)
	);
}

/**
 * Normalizes a site URL for comparisons.
 *
 * @param string $url URL to normalize.
 * @return string
 */
function mcems_rest_normalize_site_url( $url ) {
	$url = trim( (string) $url );

	if ( '' === $url ) {
		return '';
	}

	return trailingslashit( esc_url_raw( $url ) );
}

/**
 * Build the REST response payload for a license row.
 *
 * @param array|null $license License row.
 * @param string     $status  Response status.
 * @param string     $message Human-readable message.
 * @param string     $reason  Machine-readable reason.
 * @return array
 */
function mcems_rest_build_license_payload( $license, $status, $message, $reason = '' ) {
	$payload = array(
		'status'  => (string) $status,
		'message' => (string) $message,
		'reason'  => (string) $reason,
	);

	if ( is_array( $license ) && ! empty( $license ) ) {
		$payload['license_key']  = isset( $license['license_key'] ) ? (string) $license['license_key'] : '';
		$payload['user_id']      = isset( $license['user_id'] ) ? (int) $license['user_id'] : 0;
		$payload['site_url']     = isset( $license['site_url'] ) ? (string) $license['site_url'] : '';
		$payload['product_id']   = isset( $license['product_id'] ) ? (int) $license['product_id'] : 0;
		$payload['status_db']    = isset( $license['status'] ) ? (string) $license['status'] : '';
		$payload['created_at']   = isset( $license['created_at'] ) ? $license['created_at'] : null;
		$payload['activated_at'] = isset( $license['activated_at'] ) ? $license['activated_at'] : null;
		$payload['expires_at']   = isset( $license['expires_at'] ) ? $license['expires_at'] : null;
		$payload['updated_at']   = isset( $license['updated_at'] ) ? $license['updated_at'] : null;
	}

	return $payload;
}

/**
 * Returns a standard error payload.
 *
 * @param string $message Error message.
 * @param string $reason  Machine-readable reason.
 * @return array
 */
function mcems_rest_build_error_payload( $message, $reason = 'error' ) {
	return array(
		'status'  => 'error',
		'message' => (string) $message,
		'reason'  => (string) $reason,
	);
}

/**
 * Official REST endpoint for license verification.
 *
 * Accepts:
 * - license_key (required)
 * - site_url (optional but strongly recommended)
 *
 * @param \WP_REST_Request $request Request object.
 * @return \WP_REST_Response
 */
function mcems_rest_check_license_official( \WP_REST_Request $request ) {
	global $wpdb;

	$license_key = trim( (string) $request->get_param( 'license_key' ) );
	$site_url    = mcems_rest_normalize_site_url( (string) $request->get_param( 'site_url' ) );

	if ( '' === $license_key ) {
		return rest_ensure_response(
			mcems_rest_build_license_payload(
				null,
				'invalid',
				'No license key provided.',
				'missing_key'
			)
		);
	}

	if ( ! class_exists( 'MC_EMS_License_Manager' ) ) {
		return rest_ensure_response(
			mcems_rest_build_error_payload(
				'License system unavailable.',
				'system_unavailable'
			)
		);
	}

	if ( ! class_exists( 'MC_EMS_Database' ) ) {
		return rest_ensure_response(
			mcems_rest_build_error_payload(
				'License database system unavailable.',
				'database_unavailable'
			)
		);
	}

	$table = MC_EMS_Database::table_name();

	if ( empty( $table ) ) {
		return rest_ensure_response(
			mcems_rest_build_error_payload(
				'License table not available.',
				'table_unavailable'
			)
		);
	}

	$manager = new MC_EMS_License_Manager();

	/*
	 * First try the official validation flow.
	 * This method already:
	 * - checks existence
	 * - checks bound site mismatch
	 * - binds first site usage if site_url is provided and DB site_url is empty
	 * - checks active status
	 * - auto-expires licenses when needed
	 */
	$license = $manager->validate_license( $license_key, 0, $site_url );

	if ( $license ) {
		// Ensure site_url in returned payload is normalized after possible first activation binding.
		if ( ! empty( $license['id'] ) ) {
			$refreshed = $manager->get_license( (int) $license['id'] );
			if ( is_array( $refreshed ) && ! empty( $refreshed ) ) {
				$license = $refreshed;
			}
		}

		return rest_ensure_response(
			mcems_rest_build_license_payload(
				$license,
				'valid',
				'License is valid and active.',
				'valid'
			)
		);
	}

	/*
	 * If validate_license() failed, inspect the row directly to determine why.
	 */
	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE license_key = %s LIMIT 1",
			sanitize_text_field( $license_key )
		),
		ARRAY_A
	);

	if ( ! $row ) {
		return rest_ensure_response(
			mcems_rest_build_license_payload(
				null,
				'invalid',
				'License not found.',
				'not_found'
			)
		);
	}

	$stored_site = ! empty( $row['site_url'] ) ? mcems_rest_normalize_site_url( $row['site_url'] ) : '';

	// Different domain than the one already bound.
	if ( '' !== $site_url && '' !== $stored_site && $site_url !== $stored_site ) {
		return rest_ensure_response(
			mcems_rest_build_license_payload(
				$row,
				'invalid',
				'License does not match this site URL.',
				'site_mismatch'
			)
		);
	}

	// Not active.
	if ( isset( $row['status'] ) && 'active' !== $row['status'] ) {
		$status_db = (string) $row['status'];

		if ( 'expired' === $status_db ) {
			return rest_ensure_response(
				mcems_rest_build_license_payload(
					$row,
					'expired',
					'License has expired.',
					'expired'
				)
			);
		}

		return rest_ensure_response(
			mcems_rest_build_license_payload(
				$row,
				'inactive',
				'License exists but is not active.',
				'inactive'
			)
		);
	}

	// Expiry check if status is still active but date is already past.
	if ( ! empty( $row['expires_at'] ) ) {
		$now_ts     = strtotime( current_time( 'mysql' ) );
		$expires_ts = strtotime( $row['expires_at'] );

		if ( $expires_ts && $expires_ts < $now_ts ) {
			if ( ! empty( $row['id'] ) ) {
				$manager->update_status( (int) $row['id'], 'expired' );
				$row = $manager->get_license( (int) $row['id'] );
			}

			return rest_ensure_response(
				mcems_rest_build_license_payload(
					$row,
					'expired',
					'License has expired.',
					'expired'
				)
			);
		}
	}

	// Generic fallback.
	return rest_ensure_response(
		mcems_rest_build_license_payload(
			$row,
			'invalid',
			'License is not valid.',
			'invalid'
		)
	);
}
