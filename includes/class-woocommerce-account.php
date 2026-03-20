<?php

class WooCommerce_Account_My_Licenses {
    public function __construct() {
        add_action('woocommerce_account_navigation', array($this, 'add_my_licenses_tab'));
        add_action('woocommerce_account_my-licenses_endpoint', array($this, 'my_licenses_content'));
    }

    public function add_my_licenses_tab() {
        // Add 'My Licenses' tab in account navigation
        echo '<li><a href="' . esc_url( wc_get_account_endpoint_url( 'my-licenses' ) ) . '">My Licenses</a></li>'; 
    }

    public function my_licenses_content() {
        // Display licenses information
        $licenses = $this->get_customer_licenses();
        echo '<h2>My Licenses</h2>'; 
        echo '<table class="woocommerce-table">
            <thead>
                <tr>
                    <th>License Key</th>
                    <th>Status</th>
                    <th>Purchased Date</th>
                    <th>Expiration Date</th>
                    <th>Days Remaining</th>
                    <th>Linked Domain</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($licenses as $license) {
            echo '<tr>';
            echo '<td>' . esc_html($license['key']) . '</td>';
            echo '<td>' . esc_html($license['status']) . '</td>';
            echo '<td>' . esc_html($license['purchased_date']) . '</td>';
            echo '<td>' . esc_html($license['expiration_date']) . '</td>';
            echo '<td>' . esc_html($license['days_remaining']) . '</td>';
            echo '<td>' . esc_html($license['domain']) . '</td>';
            if ($license['status'] === 'expired' || $license['status'] === 'expiring') {
                echo '<td><button class="renew-license-button" data-license="' . esc_attr($license['key']) . '">Renew License</button></td>';
            } else {
                echo '<td></td>';
            }
            echo '</tr>';
        }

        echo '</tbody></table>';
        $this->enqueue_scripts(); 
    }

    public function get_customer_licenses() {
        // Return customer licenses (mock data)
        return [
            ['key' => 'LICENSE-12345', 'status' => 'active', 'purchased_date' => '2023-01-15', 'expiration_date' => '2024-01-15', 'days_remaining' => '300', 'domain' => 'example.com'],
            ['key' => 'LICENSE-67890', 'status' => 'expired', 'purchased_date' => '2022-05-20', 'expiration_date' => '2023-05-20', 'days_remaining' => '-20', 'domain' => 'anotherexample.com'],
        ];
    }

    public function enqueue_scripts() {
        // Enqueue AJAX script
        add_action('wp_enqueue_scripts', function() {
            wp_enqueue_script('woocommerce-licenses', get_template_directory_uri() . '/js/licenses.js', array('jquery'), '1.0', true);
            wp_localize_script('woocommerce-licenses', 'LicenseAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
        });
    }
}

new WooCommerce_Account_My_Licenses();

// AJAX renew license function
add_action('wp_ajax_renew_license', 'renew_license_callback');
function renew_license_callback() {
    if (isset($_POST['license_key'])) {
        $license_key = sanitize_text_field($_POST['license_key']);
        // Add product to cart (mock code, add actual product ID)
        WC()->cart->add_to_cart(123); // Replace 123 with actual product ID
        wp_redirect(wc_get_checkout_url());
        exit;
    }
}

?>