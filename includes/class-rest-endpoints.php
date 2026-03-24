<?php
// includes/class-rest-endpoints.php

add_action( 'rest_api_init', function () {
	register_rest_route(
		'mcems/v1',
		'/license/verify',
		array(
			'methods'             => 'POST',
			'callback'            => 'mcems_rest_check_license_official',
			'permission_callback' => '__return_true',
		)
	);
} );

/**
 * Build the REST response payload for a license row.
 *
 * @param array|null $license License row.
 * @param string     $status  Response status.
 * @param string     $message Human message.
 * @param string     $reason  Machine-readable reason.
 * @return array
 */
function mcems_rest_build_license_payload( $license, $status, $message, $reason = '' ) {
	$payload = array(
		'status'  => $status,
		'message' => $message,
		'reason'  => $reason,
	);

	if ( is_array( $license ) && ! empty( $license ) ) {
		$payload['license_key']  = $license['license_key'] ?? '';
		$payload['user_id']      = isset( $license['user_id'] ) ? (int) $license['user_id'] : 0;
		$payload['site_url']     = $license['site_url'] ?? '';
		$payload['product_id']   = isset( $license['product_id'] ) ? (int) $license['product_id'] : 0;
		$payload['created_at']   = $license['created_at'] ?? null;
		$payload['activated_at'] = $license['activated_at'] ?? null;
		$payload['expires_at']   = $license['expires_at'] ?? null;
		$payload['updated_at']   = $license['updated_at'] ?? null;
	}

	return $payload;
}

/**
 * REST endpoint per la verifica licenza.
 *
 * Accetta:
 * - license_key (string, required)
 * - site_url (string, optional)
 */
function mcems_rest_check_license_official( \WP_REST_Request $request ) {
	$license_key = trim( (string) $request->get_param( 'license_key' ) );
	$site_url    = trim( (string) $request->get_param( 'site_url' ) );

	if ( ! $license_key ) {
		return rest_ensure_response(
			array(
				'status'  => 'invalid',
				'message' => 'No license key provided.',
				'reason'  => 'missing_key',
			)
		);
	}

	if ( ! class_exists( 'MC_EMS_License_Manager' ) ) {
		return rest_ensure_response(
			array(
				'status'  => 'error',
				'message' => 'License system unavailable.',
				'reason'  => 'system_unavailable',
			)
		);
	}

	$manager    = new MC_EMS_License_Manager();
	$evaluation = $manager->evaluate_license( $license_key, 0, $site_url, true );
	$license    = isset( $evaluation['license'] ) ? $evaluation['license'] : null;
	$reason     = isset( $evaluation['reason'] ) ? $evaluation['reason'] : '';

	if ( ! empty( $evaluation['is_valid'] ) ) {
		return rest_ensure_response(
			mcems_rest_build_license_payload(
				$license,
				'valid',
				'License is valid and active.',
				'valid'
			)
		);
	}

	switch ( $reason ) {
		case 'expired':
			$response = mcems_rest_build_license_payload( $license, 'expired', 'License has expired.', $reason );
			break;

		case 'inactive':
			$response = mcems_rest_build_license_payload( $license, 'inactive', 'License exists but is not active.', $reason );
			break;

		case 'site_mismatch':
			$response = mcems_rest_build_license_payload( $license, 'invalid', 'License does not match this site URL.', $reason );
			break;

		case 'user_mismatch':
			$response = mcems_rest_build_license_payload( $license, 'invalid', 'License does not match the expected user.', $reason );
			break;

		case 'not_found':
		default:
			$response = mcems_rest_build_license_payload( null, 'invalid', 'License not found.', $reason ? $reason : 'not_found' );
			break;
	}

	return rest_ensure_response( $response );
}
