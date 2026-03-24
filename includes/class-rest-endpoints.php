<?php
// includes/class-rest-endpoints.php

add_action('rest_api_init', function () {
    register_rest_route('mcems/v1', '/license/verify', [
        'methods'  => 'POST',
        'callback' => 'mcems_rest_check_license_official',
        'permission_callback' => '__return_true',
    ]);
});

/**
 * REST endpoint per la verifica licenza utilizzando la funzione ufficiale del plugin.
 *
 * Accetta:
 * - license_key (string, required)
 * - site_url (string, optional)
 */
function mcems_rest_check_license_official(\WP_REST_Request $request) {
    $license_key = trim((string) $request->get_param('license_key'));
    $site_url    = trim((string) $request->get_param('site_url'));

    if (!$license_key) {
        return rest_ensure_response([
            'status'  => 'invalid',
            'message' => 'No license key provided.',
        ]);
    }

    if ( ! class_exists('MC_EMS_License_Manager') ) {
        return rest_ensure_response([
            'status'  => 'error',
            'message' => 'License system unavailable.',
        ]);
    }

    $manager = new MC_EMS_License_Manager();
    $license = $manager->validate_license($license_key, 0, $site_url);

    if ( ! $license ) {
        return rest_ensure_response([
            'status'  => 'invalid',
            'message' => 'License not found or does not match.',
        ]);
    }

    // Check status
    if ( isset($license['status']) && $license['status'] !== 'active' ) {
        return rest_ensure_response([
            'status'      => $license['status'],
            'message'     => 'License is not active.',
            'expire_date' => $license['expires_at'] ?? null,
        ]);
    }

    // Check expiry
    if ( !empty($license['expires_at']) && strtotime($license['expires_at']) < time() ) {
        return rest_ensure_response([
            'status'      => 'expired',
            'message'     => 'License has expired.',
            'expire_date' => $license['expires_at'],
        ]);
    }

    // License is valid!
    return rest_ensure_response([
        'status'        => 'valid',
        'message'       => 'License is valid.',
        'license_key'   => $license['license_key'],
        'user_id'       => $license['user_id'],
        'site_url'      => $license['site_url'],
        'product_id'    => $license['product_id'],
        'created_at'    => $license['created_at'],
        'expires_at'    => $license['expires_at'],
        'updated_at'    => $license['updated_at'],
    ]);
}
