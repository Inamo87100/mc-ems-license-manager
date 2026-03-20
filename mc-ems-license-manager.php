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
require_once MC_EMS_LICENSE_MANAGER_DIR . 'includes/class-license-manager.php';

// Activation hook
function mc_ems_license_manager_activate() {
    // Activation code here
}
register_activation_hook( MC_EMS_LICENSE_MANAGER_FILE, 'mc_ems_license_manager_activate' );

// Deactivation hook
function mc_ems_license_manager_deactivate() {
    // Deactivation code here
}
register_deactivation_hook( MC_EMS_LICENSE_MANAGER_FILE, 'mc_ems_license_manager_deactivate' );

// Initialize the plugin
add_action( 'plugins_loaded', 'mc_ems_license_manager_init' );
function mc_ems_license_manager_init() {
    // Initialization code here
}
?>