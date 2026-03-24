<?php
/**
 * Plugin Name: MC EMS License Manager
 * Plugin URI: https://mambacoding.com
 * Description: A license manager for MC EMS.
 * Version: 1.1.0
 * Author: Mamba Coding
 * Author URI: https://mambacoding.com
 * Text Domain: mc-ems-license-manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) exit;

// Constants definition
define( 'MC_EMS_LICENSE_MANAGER_VERSION', '1.1.0' );
define( 'MC_EMS_LICENSE_MANAGER_FILE', __FILE__ );
define( 'MC_EMS_LICENSE_MANAGER_DIR', plugin_dir_path( __FILE__ ) );

defined( 'MC_EMS_LICENSE_MANAGER_URL' ) || define( 'MC_EMS_LICENSE_MANAGER_URL', plugin_dir_url( __FILE__ ) );

// Include classes
require_once MC_EMS_LICENSE_MANAGER_DIR . 'includes/class-database.php';
require_once MC_EMS_LICENSE_MANAGER_DIR . 'includes/class-license-manager.php';
require_once MC_EMS_LICENSE_MANAGER_DIR . 'includes/class-product-manager.php';
require_once MC_EMS_LICENSE_MANAGER_DIR . 'includes/class-license-list-table.php';
require_once MC_EMS_LICENSE_MANAGER_DIR . 'includes/class-product-list-table.php';
require_once MC_EMS_LICENSE_MANAGER_DIR . 'includes/class-admin.php';
require_once MC_EMS_LICENSE_MANAGER_DIR . 'includes/class-woocommerce-integration.php';

// >>> Include il REST endpoint della licenza
require_once MC_EMS_LICENSE_MANAGER_DIR . 'includes/class-rest-endpoints.php';

// Activation hook – create DB tables.
function mc_ems_license_manager_activate() {
	MC_EMS_Database::create_table();
	MC_EMS_Database::create_product_licenses_table();
}
register_activation_hook( MC_EMS_LICENSE_MANAGER_FILE, 'mc_ems_license_manager_activate' );

// Deactivation hook – nothing to clean up (tables kept for data safety).
function mc_ems_license_manager_deactivate() {
	// Intentionally left empty; tables are preserved across deactivations.
}
register_deactivation_hook( MC_EMS_LICENSE_MANAGER_FILE, 'mc_ems_license_manager_deactivate' );

// Initialize the plugin.
add_action( 'plugins_loaded', 'mc_ems_license_manager_init' );
function mc_ems_license_manager_init() {
	// Ensure database schema is up to date.
	MC_EMS_Database::create_table();
	MC_EMS_Database::create_product_licenses_table();

	// Load text domain for i18n.
	load_plugin_textdomain(
		'mc-ems-license-manager',
		false,
		dirname( plugin_basename( MC_EMS_LICENSE_MANAGER_FILE ) ) . '/languages'
	);

	$license_manager = new MC_EMS_License_Manager();
	$product_manager = new MC_EMS_Product_Manager();

	// Initialize WooCommerce integration when WooCommerce is active.
	if ( function_exists( 'WC' ) ) {
		new MC_EMS_WooCommerce_Integration( $license_manager, $product_manager );
	}

	if ( is_admin() ) {
		new MC_EMS_Admin( $license_manager, $product_manager );
	}
}
?>
