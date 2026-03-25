// 1. Register new endpoint /licenses in My Account
add_action('init', function () {
    add_rewrite_endpoint('licenses', EP_ROOT | EP_PAGES);
});

// 2. Add tab to My Account navigation
add_filter('woocommerce_account_menu_items', function ($items) {
    $logout = $items['customer-logout'];
    unset($items['customer-logout']);
    $items['licenses'] = __('My Licenses', 'ems-license-manager');
    $items['customer-logout'] = $logout;
    return $items;
});

// 3. Add handler for endpoint content
add_action('woocommerce_account_licenses_endpoint', function () {
    $user_id = get_current_user_id();

    // Replace with your actual query to fetch licenses
    $licenses = ems_get_user_licenses($user_id);

    if (!$licenses) {
        echo "<p>You have no active licenses.</p>";
    } else {
        echo '<table class="shop_table shop_table_responsive">';
        echo '<thead><tr><th>License</th><th>Expiration Date</th></tr></thead><tbody>';
        foreach ($licenses as $lic) {
            echo "<tr><td>{$lic->code}</td><td>{$lic->expires}</td></tr>";
        }
        echo '</tbody></table>';
    }
});

// 4. (Optional) Flush rewrite rules after plugin activation
register_activation_hook(__FILE__, function () {
    flush_rewrite_rules();
});
register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

// 5. (Implement this function to fetch actual licenses)
function ems_get_user_licenses($user_id) {
    // Dummy example: Replace with your DB query
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare(
        "SELECT code, expires FROM {$wpdb->prefix}ems_licenses WHERE user_id = %d", $user_id
    ));
}
