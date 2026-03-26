<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register new endpoint /licenses in My Account.
 */
add_action( 'init', function() {
	add_rewrite_endpoint( 'licenses', EP_ROOT | EP_PAGES );
} );

/**
 * Add tab to My Account navigation.
 */
add_filter( 'woocommerce_account_menu_items', function( $items ) {
	$logout = $items['customer-logout'] ?? '';
	unset( $items['customer-logout'] );

	$items['licenses'] = __( 'My Licenses', 'ems-license-manager' );

	if ( $logout ) {
		$items['customer-logout'] = $logout;
	}

	return $items;
} );

/**
 * Returns HTML badge for status.
 *
 * @param string $status License status.
 * @return string
 */
function ems_render_license_status_badge( $status ) {
	$status = strtolower( (string) $status );
	$label  = ucfirst( $status ?: 'Unknown' );

	$style = 'display:inline-block;padding:6px 12px;border-radius:999px;font-size:12px;font-weight:700;line-height:1;white-space:nowrap;';

	switch ( $status ) {
		case 'active':
			$label = __( 'Active', 'ems-license-manager' );
			$style .= 'background:#e7f6ec;color:#1f7a3e;border:1px solid #b7e4c7;';
			break;

		case 'expired':
			$label = __( 'Expired', 'ems-license-manager' );
			$style .= 'background:#fdecec;color:#b42318;border:1px solid #f5c2c7;';
			break;

		case 'inactive':
			$label = __( 'Inactive', 'ems-license-manager' );
			$style .= 'background:#f2f4f7;color:#475467;border:1px solid #d0d5dd;';
			break;

		default:
			$label = __( 'Unknown', 'ems-license-manager' );
			$style .= 'background:#f2f4f7;color:#475467;border:1px solid #d0d5dd;';
			break;
	}

	return '<span style="' . esc_attr( $style ) . '">' . esc_html( $label ) . '</span>';
}

/**
 * Render licenses in My Account.
 */
add_action( 'woocommerce_account_licenses_endpoint', function() {
	$user_id  = get_current_user_id();
	$licenses = ems_get_user_licenses( $user_id );

	echo '<div style="width:100%;max-width:100%;">';
	echo '<h2 style="margin:0 0 10px 0;font-size:42px;line-height:1.1;font-weight:800;color:#111827;">' . esc_html__( 'My Licenses', 'ems-license-manager' ) . '</h2>';
	echo '<p style="margin:0 0 24px 0;font-size:16px;line-height:1.6;color:#6b7280;">' . esc_html__( 'View all licenses associated with your account.', 'ems-license-manager' ) . '</p>';

	if ( empty( $licenses ) ) {
		echo '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:22px;">';
		echo '<strong style="display:block;margin-bottom:8px;font-size:18px;color:#111827;">' . esc_html__( 'No licenses found', 'ems-license-manager' ) . '</strong>';
		echo '<span style="font-size:15px;line-height:1.6;color:#6b7280;">' . esc_html__( 'There are currently no licenses associated with your account.', 'ems-license-manager' ) . '</span>';
		echo '</div>';
		echo '</div>';
		return;
	}

	echo '<div style="width:100%;overflow-x:auto;background:#fff;border:1px solid #e5e7eb;border-radius:18px;box-shadow:0 8px 24px rgba(0,0,0,0.04);">';
	echo '<table style="width:100%;min-width:980px;border-collapse:separate;border-spacing:0;font-size:15px;line-height:1.5;color:#111827;">';

	echo '<thead>';
	echo '<tr>';
	echo '<th style="text-align:left;padding:16px 18px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#6b7280;">' . esc_html__( 'Product', 'ems-license-manager' ) . '</th>';
	echo '<th style="text-align:left;padding:16px 18px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#6b7280;">' . esc_html__( 'License Key', 'ems-license-manager' ) . '</th>';
	echo '<th style="text-align:left;padding:16px 18px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#6b7280;">' . esc_html__( 'Site URL', 'ems-license-manager' ) . '</th>';
	echo '<th style="text-align:left;padding:16px 18px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#6b7280;">' . esc_html__( 'Status', 'ems-license-manager' ) . '</th>';
	echo '<th style="text-align:left;padding:16px 18px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#6b7280;">' . esc_html__( 'Created At', 'ems-license-manager' ) . '</th>';
	echo '<th style="text-align:left;padding:16px 18px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#6b7280;">' . esc_html__( 'Expiration Date', 'ems-license-manager' ) . '</th>';
	echo '</tr>';
	echo '</thead>';

	echo '<tbody>';

	foreach ( $licenses as $lic ) {
		$created = ! empty( $lic->created_at ) ? date_i18n( 'Y-m-d', strtotime( $lic->created_at ) ) : '-';
		$expires = ! empty( $lic->expires ) ? date_i18n( 'Y-m-d', strtotime( $lic->expires ) ) : '-';

		$product_label = $lic->product ? $lic->product : __( 'Unknown product', 'ems-license-manager' );
		if ( ! empty( $lic->product_id ) ) {
			$product_label .= ' (#' . absint( $lic->product_id ) . ')';
		}

		echo '<tr>';

		echo '<td style="padding:18px;border-bottom:1px solid #eef2f7;vertical-align:top;min-width:260px;font-weight:600;color:#111827;">';
		echo esc_html( $product_label );
		echo '</td>';

		echo '<td style="padding:18px;border-bottom:1px solid #eef2f7;vertical-align:top;min-width:220px;">';
		echo '<code style="display:inline-block;background:#f6f7f8;border:1px solid #e5e7eb;border-radius:10px;padding:8px 10px;font-family:Consolas,Monaco,monospace;font-size:14px;line-height:1.5;color:#111827;word-break:break-word;">' . esc_html( $lic->code ) . '</code>';
		echo '</td>';

		echo '<td style="padding:18px;border-bottom:1px solid #eef2f7;vertical-align:top;min-width:220px;">';
		if ( ! empty( $lic->site_url ) ) {
			echo '<a href="' . esc_url( $lic->site_url ) . '" target="_blank" rel="noopener noreferrer" style="color:#3b82f6;text-decoration:none;word-break:break-word;">' . esc_html( untrailingslashit( $lic->site_url ) ) . '</a>';
		} else {
			echo '-';
		}
		echo '</td>';

		echo '<td style="padding:18px;border-bottom:1px solid #eef2f7;vertical-align:top;min-width:120px;">';
		echo ems_render_license_status_badge( $lic->status );
		echo '</td>';

		echo '<td style="padding:18px;border-bottom:1px solid #eef2f7;vertical-align:top;min-width:130px;white-space:nowrap;">';
		echo esc_html( $created );
		echo '</td>';

		echo '<td style="padding:18px;border-bottom:1px solid #eef2f7;vertical-align:top;min-width:130px;white-space:nowrap;">';
		echo esc_html( $expires );
		echo '</td>';

		echo '</tr>';
	}

	echo '</tbody>';
	echo '</table>';
	echo '</div>';
	echo '</div>';
} );

/**
 * Fetch all licenses for the current user.
 *
 * @param int $user_id User ID.
 * @return array
 */
function ems_get_user_licenses( $user_id ) {
	global $wpdb;

	$table = $wpdb->prefix . 'mc_ems_licenses';

	$licenses = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT id, license_key, product_id, site_url, status, created_at, activated_at, expires_at
			 FROM {$table}
			 WHERE user_id = %d
			 ORDER BY created_at DESC, id DESC",
			$user_id
		)
	);

	$out = array();

	foreach ( $licenses as $lic ) {
		$product_name = $lic->product_id ? get_the_title( $lic->product_id ) : '';

		$out[] = (object) array(
			'id'           => $lic->id,
			'code'         => $lic->license_key,
			'product'      => $product_name,
			'product_id'   => $lic->product_id,
			'site_url'     => $lic->site_url,
			'status'       => $lic->status,
			'created_at'   => $lic->created_at,
			'activated_at' => $lic->activated_at,
			'expires'      => $lic->expires_at,
		);
	}

	return $out;
}
