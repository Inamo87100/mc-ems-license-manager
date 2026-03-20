<?php
/**
 * Plugin Name: MC EMS License Manager
 * Plugin URI: https://mambacoding.com
 * Description: A license manager for MC EMS.
 * Version: 1.0.0
 * Author: Inamo87100
 * Author URI: https://mambacoding.com
 * Text Domain: mc-ems-license-manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) exit;

// Constants definition
define( 'MC_EMS_LICENSE_MANAGER_VERSION', '1.0.0' );
define( 'MC_EMS_LICENSE_MANAGER_FILE', __FILE__ );
define( 'MC_EMS_LICENSE_MANAGER_DIR', plugin_dir_path( __FILE__ ) );

defined( 'MC_EMS_LICENSE_MANAGER_URL' ) || define( 'MC_EMS_LICENSE_MANAGER_URL', plugin_dir_url( __FILE__ ) );

// Include classes
require_once MC_EMS_LICENSE_MANAGER_DIR . 'includes/class-database.php';
require_once MC_EMS_LICENSE_MANAGER_DIR . 'includes/class-license-manager.php';
require_once MC_EMS_LICENSE_MANAGER_DIR . 'includes/class-license-list-table.php';
require_once MC_EMS_LICENSE_MANAGER_DIR . 'includes/class-admin.php';

// Activation hook – create DB table.
function mc_ems_license_manager_activate() {
	MC_EMS_Database::create_table();
}
register_activation_hook( MC_EMS_LICENSE_MANAGER_FILE, 'mc_ems_license_manager_activate' );

// Deactivation hook – nothing to clean up (table kept for data safety).
function mc_ems_license_manager_deactivate() {
	// Intentionally left empty; table is preserved across deactivations.
}
register_deactivation_hook( MC_EMS_LICENSE_MANAGER_FILE, 'mc_ems_license_manager_deactivate' );

// Initialize the plugin.
add_action( 'plugins_loaded', 'mc_ems_license_manager_init' );
function mc_ems_license_manager_init() {
	// Load text domain for i18n.
	load_plugin_textdomain(
		'mc-ems-license-manager',
		false,
		dirname( plugin_basename( MC_EMS_LICENSE_MANAGER_FILE ) ) . '/languages'
	);

	$license_manager = new MC_EMS_License_Manager();

	if ( is_admin() ) {
		new MC_EMS_Admin( $license_manager );
	}
}
?>