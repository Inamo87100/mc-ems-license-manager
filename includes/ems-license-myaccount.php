<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 1. Register new endpoint /licenses in My Account
 */
add_action( 'init', function() {
	add_rewrite_endpoint( 'licenses', EP_ROOT | EP_PAGES );
} );

/**
 * 2. Add tab to My Account navigation
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
 * Returns the CSS class for the license status badge.
 *
 * @param string $status License status.
 * @return string
 */
function ems_get_license_status_class( $status ) {
	$status = strtolower( (string) $status );

	switch ( $status ) {
		case 'active':
			return 'is-active';

		case 'expired':
			return 'is-expired';

		case 'inactive':
			return 'is-inactive';

		default:
			return 'is-unknown';
	}
}

/**
 * 3. Render license table in My Account
 */
add_action( 'woocommerce_account_licenses_endpoint', function() {
	$user_id  = get_current_user_id();
	$licenses = ems_get_user_licenses( $user_id );

	echo '<div class="mc-ems-my-licenses-wrap">';

	echo '<div class="mc-ems-my-licenses-header">';
	echo '<h3>' . esc_html__( 'My Licenses', 'ems-license-manager' ) . '</h3>';
	echo '</div>';

	if ( empty( $licenses ) ) {
		echo '<div class="woocommerce-info mc-ems-no-licenses">';
		echo esc_html__( 'You have no licenses.', 'ems-license-manager' );
		echo '</div>';
		echo '</div>';
		return;
	}

	echo '<div class="mc-ems-license-table-wrap">';
	echo '<table class="shop_table shop_table_responsive mc-ems-license-table">';
	echo '<thead><tr>';
	echo '<th>' . esc_html__( 'Product', 'ems-license-manager' ) . '</th>';
	echo '<th>' . esc_html__( 'License Key', 'ems-license-manager' ) . '</th>';
	echo '<th>' . esc_html__( 'Site URL', 'ems-license-manager' ) . '</th>';
	echo '<th>' . esc_html__( 'Status', 'ems-license-manager' ) . '</th>';
	echo '<th>' . esc_html__( 'Created At', 'ems-license-manager' ) . '</th>';
	echo '<th>' . esc_html__( 'Expiration Date', 'ems-license-manager' ) . '</th>';
	echo '</tr></thead>';
	echo '<tbody>';

	foreach ( $licenses as $lic ) {
		$created = ! empty( $lic->created_at ) ? date_i18n( 'Y-m-d', strtotime( $lic->created_at ) ) : '-';
		$expires = ! empty( $lic->expires ) ? date_i18n( 'Y-m-d', strtotime( $lic->expires ) ) : '-';

		$product_label = $lic->product ? $lic->product . ' (#' . absint( $lic->product_id ) . ')' : '-';
		$status_label  = ucfirst( (string) $lic->status );
		$status_class  = ems_get_license_status_class( $lic->status );

		echo '<tr>';

		echo '<td class="mc-ems-col-product" data-title="' . esc_attr__( 'Product', 'ems-license-manager' ) . '">';
		echo esc_html( $product_label );
		echo '</td>';

		echo '<td class="mc-ems-col-key" data-title="' . esc_attr__( 'License Key', 'ems-license-manager' ) . '">';
		echo '<code class="mc-ems-license-key">' . esc_html( $lic->code ) . '</code>';
		echo '</td>';

		echo '<td class="mc-ems-col-site" data-title="' . esc_attr__( 'Site URL', 'ems-license-manager' ) . '">';
		if ( ! empty( $lic->site_url ) ) {
			echo '<a href="' . esc_url( $lic->site_url ) . '" target="_blank" rel="noopener noreferrer">';
			echo esc_html( untrailingslashit( $lic->site_url ) );
			echo '</a>';
		} else {
			echo '-';
		}
		echo '</td>';

		echo '<td class="mc-ems-col-status" data-title="' . esc_attr__( 'Status', 'ems-license-manager' ) . '">';
		echo '<span class="mc-ems-license-status ' . esc_attr( $status_class ) . '">' . esc_html( $status_label ) . '</span>';
		echo '</td>';

		echo '<td class="mc-ems-col-created" data-title="' . esc_attr__( 'Created At', 'ems-license-manager' ) . '">';
		echo esc_html( $created );
		echo '</td>';

		echo '<td class="mc-ems-col-expires" data-title="' . esc_attr__( 'Expiration Date', 'ems-license-manager' ) . '">';
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
 * 4. Fetch all licenses for the current user (all statuses)
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

/**
 * 5. Enqueue CSS only on the My Licenses endpoint
 */
add_action( 'wp_enqueue_scripts', function() {
	if ( function_exists( 'is_account_page' ) && is_account_page() && function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'licenses' ) ) {
		wp_enqueue_style(
			'mc-ems-license-myaccount-style',
			plugins_url( 'includes/ems-license-myaccount-style.css', MC_EMS_LICENSE_MANAGER_FILE ),
			array(),
			MC_EMS_LICENSE_MANAGER_VERSION
		);
	}
} );
