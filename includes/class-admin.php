<?php
/**
 * MC EMS License Manager - Admin Class
 * @package MC_EMS_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MC_EMS_License_Manager_Admin {

    private $product_manager;

    public function __construct( $product_manager ) {
        $this->product_manager = $product_manager;
        // Altri hook, azioni ecc.
    }

    // ... altri metodi ...

    public function handle_admin_post() {
        // AGGIUNGI prodotto
        if ( isset( $_POST['submit_add_product'] ) ) {
            check_admin_referer( 'mc_ems_add_product', 'mc_ems_add_product_nonce' );
            $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
            $duration_days = isset( $_POST['duration_days'] ) ? absint( $_POST['duration_days'] ) : 365;

            if ( ! $product_id || ! $duration_days ) {
                $this->add_notice( __( 'Invalid product or duration.', 'mc-ems-license-manager' ), 'error' );
                wp_safe_redirect( admin_url( 'admin.php?page=mc-ems-products' ) );
                exit;
            }

            if ( $this->product_manager->is_product_licensed( $product_id ) ) {
                $this->add_notice( __( 'This product is already associated with a license.', 'mc-ems-license-manager' ), 'error' );
                wp_safe_redirect( admin_url( 'admin.php?page=mc-ems-products' ) );
                exit;
            }

            $result = $this->product_manager->add_product( $product_id, $duration_days ?: 365 );

            if ( $result ) {
                $this->add_notice( __( 'Product added successfully.', 'mc-ems-license-manager' ), 'success' );
            } else {
                $this->add_notice( __( 'Failed to add product.', 'mc-ems-license-manager' ), 'error' );
            }

            wp_safe_redirect( admin_url( 'admin.php?page=mc-ems-products' ) );
            exit;
        }

        // ELIMINA associazione prodotto
        if ( ! empty( $_GET['action'] ) && 'delete_product' === sanitize_key( $_GET['action'] ) && ! empty( $_GET['product_id'] ) ) {
            if ( empty( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['nonce'] ), 'mc_ems_product_action' ) ) {
                wp_die( esc_html__( 'Security check failed.', 'mc-ems-license-manager' ) );
            }
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'You do not have permission to do this.', 'mc-ems-license-manager' ) );
            }
            $product_id = absint( $_GET['product_id'] );
            $result     = $this->product_manager->delete_product( $product_id );

            if ( $result ) {
                $this->add_notice( __( 'Product deleted successfully.', 'mc-ems-license-manager' ), 'success' );
            } else {
                $this->add_notice( __( 'Failed to delete product.', 'mc-ems-license-manager' ), 'error' );
            }

            wp_safe_redirect( admin_url( 'admin.php?page=mc-ems-products' ) );
            exit;
        }
    }

    // ... altri metodi es. add_notice() e setup form/page ...
}
