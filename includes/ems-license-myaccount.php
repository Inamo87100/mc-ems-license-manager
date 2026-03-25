<?php
// 1. Register new endpoint /licenses in My Account
add_action('init', function () {
    add_rewrite_endpoint('licenses', EP_ROOT | EP_PAGES);
});

// 2. Add tab to My Account navigation
add_filter('woocommerce_account_menu_items', function ($items) {
    $logout = $items['customer-logout'] ?? '';
    unset($items['customer-logout']);
    $items['licenses'] = __('My Licenses', 'ems-license-manager');
    if ($logout) {
        $items['customer-logout'] = $logout;
    }
    return $items;
});

// 3. License table rendering: NO "Activated At", dates formatted as Y-m-d
add_action('woocommerce_account_licenses_endpoint', function () {
    $user_id = get_current_user_id();
    $licenses = ems_get_user_licenses($user_id);

    if (!$licenses) {
        echo "<p>You have no licenses.</p>";
    } else {
        echo '<table class="shop_table shop_table_responsive">';
        echo '<thead><tr>';
        echo '<th>Product</th>';
        echo '<th>License Key</th>';
        echo '<th>Site URL</th>';
        echo '<th>Status</th>';
        echo '<th>Created At</th>';
        echo '<th>Expiration Date</th>';
        echo '</tr></thead><tbody>';
        foreach ($licenses as $lic) {
            $created = $lic->created_at ? date_i18n('Y-m-d', strtotime($lic->created_at)) : '-';
            $expires = $lic->expires ? date_i18n('Y-m-d', strtotime($lic->expires)) : '-';

            echo "<tr>
                <td>" . esc_html($lic->product ? $lic->product . " (#{$lic->product_id})" : '-') . "</td>
                <td><code>" . esc_html($lic->code) . "</code></td>
                <td>" . esc_html($lic->site_url ?: '-') . "</td>
                <td>" . esc_html(ucfirst($lic->status)) . "</td>
                <td>" . esc_html($created) . "</td>
                <td>" . esc_html($expires) . "</td>
            </tr>";
        }
        echo '</tbody></table>';
    }
});

// 4. Function to fetch all licenses for current user
function ems_get_user_licenses($user_id) {
    global $wpdb;

    $table = $wpdb->prefix . 'mc_ems_licenses';

    // Take ALL licenses, regardless of status
    $licenses = $wpdb->get_results($wpdb->prepare(
        "SELECT id, license_key, product_id, site_url, status, created_at, activated_at, expires_at 
         FROM $table WHERE user_id = %d", 
        $user_id
    ));

    // Attach product name
    $out = [];
    foreach ($licenses as $lic) {
        $product_name = $lic->product_id ? get_the_title($lic->product_id) : '';
        $out[] = (object) [
            'id'           => $lic->id,
            'code'         => $lic->license_key,
            'product'      => $product_name,
            'product_id'   => $lic->product_id,
            'site_url'     => $lic->site_url,
            'status'       => $lic->status,
            'created_at'   => $lic->created_at,
            'activated_at' => $lic->activated_at,
            'expires'      => $lic->expires_at,
        ];
    }
    return $out;
}
