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
 * Returns a readable status label.
 *
 * @param string $status License status.
 * @return string
 */
function ems_get_license_status_label( $status ) {
	$status = strtolower( (string) $status );

	switch ( $status ) {
		case 'active':
			return __( 'Active', 'ems-license-manager' );

		case 'expired':
			return __( 'Expired', 'ems-license-manager' );

		case 'inactive':
			return __( 'Inactive', 'ems-license-manager' );

		default:
			return ucfirst( $status ?: __( 'Unknown', 'ems-license-manager' ) );
	}
}

/**
 * Render licenses in My Account.
 */
add_action( 'woocommerce_account_licenses_endpoint', function() {
	$user_id  = get_current_user_id();
	$licenses = ems_get_user_licenses( $user_id );

	echo '<div class="mc-ems-licenses-page">';

	echo '<div class="mc-ems-licenses-hero">';
	echo '<h2>' . esc_html__( 'My Licenses', 'ems-license-manager' ) . '</h2>';
	echo '<p>' . esc_html__( 'View all licenses associated with your account.', 'ems-license-manager' ) . '</p>';
	echo '</div>';

	if ( empty( $licenses ) ) {
		echo '<div class="mc-ems-empty-state">';
		echo '<div class="mc-ems-empty-state-inner">';
		echo '<h3>' . esc_html__( 'No licenses found', 'ems-license-manager' ) . '</h3>';
		echo '<p>' . esc_html__( 'There are currently no licenses associated with your account.', 'ems-license-manager' ) . '</p>';
		echo '</div>';
		echo '</div>';
		echo '</div>';
		return;
	}

	echo '<div class="mc-ems-license-grid">';

	foreach ( $licenses as $lic ) {
		$created = ! empty( $lic->created_at ) ? date_i18n( 'Y-m-d', strtotime( $lic->created_at ) ) : '-';
		$expires = ! empty( $lic->expires ) ? date_i18n( 'Y-m-d', strtotime( $lic->expires ) ) : '-';

		$product_label = $lic->product ? $lic->product : __( 'Unknown product', 'ems-license-manager' );
		if ( ! empty( $lic->product_id ) ) {
			$product_label .= ' (#' . absint( $lic->product_id ) . ')';
		}

		$status_class = ems_get_license_status_class( $lic->status );
		$status_label = ems_get_license_status_label( $lic->status );

		echo '<article class="mc-ems-license-card">';

		echo '<div class="mc-ems-license-card-header">';
		echo '<div class="mc-ems-license-card-product">';
		echo '<h3>' . esc_html( $product_label ) . '</h3>';
		echo '</div>';
		echo '<div class="mc-ems-license-card-status">';
		echo '<span class="mc-ems-license-status ' . esc_attr( $status_class ) . '">' . esc_html( $status_label ) . '</span>';
		echo '</div>';
		echo '</div>';

		echo '<div class="mc-ems-license-card-body">';

		echo '<div class="mc-ems-license-block mc-ems-license-block-full">';
		echo '<span class="mc-ems-license-label">' . esc_html__( 'License Key', 'ems-license-manager' ) . '</span>';
		echo '<code class="mc-ems-license-key">' . esc_html( $lic->code ) . '</code>';
		echo '</div>';

		echo '<div class="mc-ems-license-meta-grid">';

		echo '<div class="mc-ems-license-block">';
		echo '<span class="mc-ems-license-label">' . esc_html__( 'Site URL', 'ems-license-manager' ) . '</span>';
		if ( ! empty( $lic->site_url ) ) {
			echo '<a class="mc-ems-license-link" href="' . esc_url( $lic->site_url ) . '" target="_blank" rel="noopener noreferrer">';
			echo esc_html( untrailingslashit( $lic->site_url ) );
			echo '</a>';
		} else {
			echo '<span class="mc-ems-license-value">-</span>';
		}
		echo '</div>';

		echo '<div class="mc-ems-license-block">';
		echo '<span class="mc-ems-license-label">' . esc_html__( 'Created At', 'ems-license-manager' ) . '</span>';
		echo '<span class="mc-ems-license-value">' . esc_html( $created ) . '</span>';
		echo '</div>';

		echo '<div class="mc-ems-license-block">';
		echo '<span class="mc-ems-license-label">' . esc_html__( 'Expiration Date', 'ems-license-manager' ) . '</span>';
		echo '<span class="mc-ems-license-value">' . esc_html( $expires ) . '</span>';
		echo '</div>';

		echo '</div>'; // meta grid
		echo '</div>'; // body
		echo '</article>';
	}

	echo '</div>'; // grid
	echo '</div>'; // page
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

/**
 * Enqueue CSS only on the My Licenses endpoint.
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
