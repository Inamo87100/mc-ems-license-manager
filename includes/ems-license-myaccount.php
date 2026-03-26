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
 * Print inline styles only on the licenses endpoint.
 */
add_action( 'wp_head', function() {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
		return;
	}

	if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'licenses' ) ) {
		return;
	}
	?>
	<style id="mc-ems-my-licenses-inline-style">
		.woocommerce-account .mcems-licenses-page,
		.woocommerce-account .mcems-licenses-page * {
			box-sizing: border-box;
		}

		.woocommerce-account .mcems-licenses-page {
			width: 100%;
			max-width: 100%;
		}

		.woocommerce-account .mcems-licenses-hero {
			margin: 0 0 28px;
		}

		.woocommerce-account .mcems-licenses-title {
			margin: 0 0 10px;
			font-size: 42px;
			line-height: 1.08;
			font-weight: 800;
			letter-spacing: -0.02em;
			color: #111827;
		}

		.woocommerce-account .mcems-licenses-subtitle {
			margin: 0;
			font-size: 16px;
			line-height: 1.65;
			color: #6b7280;
		}

		.woocommerce-account .mcems-licenses-list {
			display: grid;
			grid-template-columns: 1fr;
			gap: 22px;
			width: 100%;
		}

		.woocommerce-account .mcems-license-card {
			display: block;
			width: 100%;
			background: #ffffff;
			border: 1px solid #e5e7eb;
			border-radius: 22px;
			padding: 24px 24px 22px;
			box-shadow: 0 10px 30px rgba(17, 24, 39, 0.05);
		}

		.woocommerce-account .mcems-license-card-header {
			display: flex;
			align-items: flex-start;
			justify-content: space-between;
			gap: 16px;
			margin-bottom: 18px;
		}

		.woocommerce-account .mcems-license-product {
			margin: 0;
			font-size: 30px;
			line-height: 1.2;
			font-weight: 800;
			letter-spacing: -0.02em;
			color: #111827;
			word-break: break-word;
		}

		.woocommerce-account .mcems-license-badge {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			padding: 8px 12px;
			border-radius: 999px;
			font-size: 12px;
			font-weight: 700;
			line-height: 1;
			white-space: nowrap;
			border: 1px solid transparent;
		}

		.woocommerce-account .mcems-license-badge.is-active {
			background: #ecfdf3;
			color: #067647;
			border-color: #abefc6;
		}

		.woocommerce-account .mcems-license-badge.is-expired {
			background: #fef3f2;
			color: #b42318;
			border-color: #fecdca;
		}

		.woocommerce-account .mcems-license-badge.is-inactive,
		.woocommerce-account .mcems-license-badge.is-unknown {
			background: #f8fafc;
			color: #475467;
			border-color: #e5e7eb;
		}

		.woocommerce-account .mcems-license-key-wrap {
			margin-bottom: 20px;
		}

		.woocommerce-account .mcems-label {
			display: block;
			margin: 0 0 8px;
			font-size: 12px;
			line-height: 1.2;
			font-weight: 700;
			letter-spacing: 0.08em;
			text-transform: uppercase;
			color: #6b7280;
		}

		.woocommerce-account .mcems-license-key-box {
			display: block;
			width: 100%;
			padding: 14px 16px;
			border-radius: 14px;
			background: #f8fafc;
			border: 1px solid #e5e7eb;
			font-family: Consolas, Monaco, monospace;
			font-size: 16px;
			line-height: 1.6;
			color: #111827;
			word-break: break-word;
		}

		.woocommerce-account .mcems-license-grid {
			display: grid;
			grid-template-columns: repeat(3, minmax(0, 1fr));
			gap: 16px;
		}

		.woocommerce-account .mcems-license-item {
			display: block;
			padding: 16px 16px 14px;
			border-radius: 16px;
			background: #ffffff;
			border: 1px solid #eef2f7;
		}

		.woocommerce-account .mcems-license-value,
		.woocommerce-account .mcems-license-value a {
			font-size: 16px;
			line-height: 1.6;
			color: #111827;
			text-decoration: none;
			word-break: break-word;
		}

		.woocommerce-account .mcems-license-value a:hover {
			text-decoration: underline;
		}

		.woocommerce-account .mcems-empty {
			padding: 26px;
			background: #ffffff;
			border: 1px solid #e5e7eb;
			border-radius: 20px;
			box-shadow: 0 10px 30px rgba(17, 24, 39, 0.05);
		}

		.woocommerce-account .mcems-empty-title {
			margin: 0 0 8px;
			font-size: 22px;
			font-weight: 700;
			color: #111827;
		}

		.woocommerce-account .mcems-empty-text {
			margin: 0;
			font-size: 15px;
			line-height: 1.6;
			color: #6b7280;
		}

		@media (max-width: 900px) {
			.woocommerce-account .mcems-license-grid {
				grid-template-columns: 1fr;
			}

			.woocommerce-account .mcems-license-card-header {
				flex-direction: column;
				align-items: flex-start;
			}

			.woocommerce-account .mcems-license-product {
				font-size: 24px;
			}

			.woocommerce-account .mcems-licenses-title {
				font-size: 34px;
			}
		}

		@media (max-width: 640px) {
			.woocommerce-account .mcems-license-card {
				padding: 20px 18px;
				border-radius: 18px;
			}

			.woocommerce-account .mcems-licenses-title {
				font-size: 28px;
			}

			.woocommerce-account .mcems-license-product {
				font-size: 22px;
			}
		}
	</style>
	<?php
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
			return __( 'Unknown', 'ems-license-manager' );
	}
}

/**
 * Render licenses in My Account.
 */
add_action( 'woocommerce_account_licenses_endpoint', function() {
	$user_id  = get_current_user_id();
	$licenses = ems_get_user_licenses( $user_id );

	echo '<div class="mcems-licenses-page">';

	echo '<div class="mcems-licenses-hero">';
	echo '<h2 class="mcems-licenses-title">' . esc_html__( 'My Licenses', 'ems-license-manager' ) . '</h2>';
	echo '<p class="mcems-licenses-subtitle">' . esc_html__( 'View all licenses associated with your account.', 'ems-license-manager' ) . '</p>';
	echo '</div>';

	if ( empty( $licenses ) ) {
		echo '<div class="mcems-empty">';
		echo '<h3 class="mcems-empty-title">' . esc_html__( 'No licenses found', 'ems-license-manager' ) . '</h3>';
		echo '<p class="mcems-empty-text">' . esc_html__( 'There are currently no licenses associated with your account.', 'ems-license-manager' ) . '</p>';
		echo '</div>';
		echo '</div>';
		return;
	}

	echo '<div class="mcems-licenses-list">';

	foreach ( $licenses as $lic ) {
		$created = ! empty( $lic->created_at ) ? date_i18n( 'Y-m-d', strtotime( $lic->created_at ) ) : '-';
		$expires = ! empty( $lic->expires ) ? date_i18n( 'Y-m-d', strtotime( $lic->expires ) ) : '-';

		$product_label = $lic->product ? $lic->product : __( 'Unknown product', 'ems-license-manager' );
		if ( ! empty( $lic->product_id ) ) {
			$product_label .= ' (#' . absint( $lic->product_id ) . ')';
		}

		$status_class = ems_get_license_status_class( $lic->status );
		$status_label = ems_get_license_status_label( $lic->status );

		echo '<article class="mcems-license-card">';

		echo '<div class="mcems-license-card-header">';
		echo '<h3 class="mcems-license-product">' . esc_html( $product_label ) . '</h3>';
		echo '<span class="mcems-license-badge ' . esc_attr( $status_class ) . '">' . esc_html( $status_label ) . '</span>';
		echo '</div>';

		echo '<div class="mcems-license-key-wrap">';
		echo '<span class="mcems-label">' . esc_html__( 'License Key', 'ems-license-manager' ) . '</span>';
		echo '<code class="mcems-license-key-box">' . esc_html( $lic->code ) . '</code>';
		echo '</div>';

		echo '<div class="mcems-license-grid">';

		echo '<div class="mcems-license-item">';
		echo '<span class="mcems-label">' . esc_html__( 'Site URL', 'ems-license-manager' ) . '</span>';
		echo '<div class="mcems-license-value">';
		if ( ! empty( $lic->site_url ) ) {
			echo '<a href="' . esc_url( $lic->site_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( untrailingslashit( $lic->site_url ) ) . '</a>';
		} else {
			echo '-';
		}
		echo '</div>';
		echo '</div>';

		echo '<div class="mcems-license-item">';
		echo '<span class="mcems-label">' . esc_html__( 'Created At', 'ems-license-manager' ) . '</span>';
		echo '<div class="mcems-license-value">' . esc_html( $created ) . '</div>';
		echo '</div>';

		echo '<div class="mcems-license-item">';
		echo '<span class="mcems-label">' . esc_html__( 'Expiration Date', 'ems-license-manager' ) . '</span>';
		echo '<div class="mcems-license-value">' . esc_html( $expires ) . '</div>';
		echo '</div>';

		echo '</div>';
		echo '</article>';
	}

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
