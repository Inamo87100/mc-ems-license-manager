<?php
/**
 * Product List Table for MC EMS License Manager Admin
 * @package MC_EMS_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class MC_EMS_Product_List_Table extends WP_List_Table {

    private $product_manager;

    public function __construct( MC_EMS_Product_Manager $product_manager ) {
        $this->product_manager = $product_manager;
        parent::__construct(
            array(
                'singular' => __( 'Product', 'mc-ems-license-manager' ),
                'plural'   => __( 'Products', 'mc-ems-license-manager' ),
                'ajax'     => false,
            )
        );
    }

    public function get_columns() {
        return array(
            'product_id'    => __( 'Product ID', 'mc-ems-license-manager' ),
            'product_name'  => __( 'Product Name', 'mc-ems-license-manager' ),
            'duration_days' => __( 'Duration (days)', 'mc-ems-license-manager' ),
            'actions'       => __( 'Actions', 'mc-ems-license-manager' ),
        );
    }

    public function column_product_id( $item ) {
        return '#' . (int) $item['product_id'];
    }

    public function column_product_name( $item ) {
        $name = get_the_title( (int) $item['product_id'] );
        return $name ? esc_html( $name ) : esc_html__( '(Product not found)', 'mc-ems-license-manager' );
    }

    public function column_duration_days( $item ) {
        return (int) $item['duration_days'];
    }

    public function column_actions( $item ) {
        $product_id = (int) $item['product_id'];
        $nonce      = wp_create_nonce( 'mc_ems_product_action' );
        $base       = admin_url( 'admin.php?page=mc-ems-products&nonce=' . $nonce );

        $delete_btn = sprintf(
            '<a href="%s" class="button button-small button-link-delete" onclick="return confirm(\'%s\')">%s</a>',
            esc_url( $base . '&action=delete_product&product_id=' . $product_id ),
            esc_js( __( 'Are you sure you want to remove this product association?', 'mc-ems-license-manager' ) ),
            esc_html__( 'Delete', 'mc-ems-license-manager' )
        );
        return $delete_btn;
    }

    public function column_default( $item, $column_name ) {
        return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '';
    }

    public function no_items() {
        esc_html_e( 'No products associated yet.', 'mc-ems-license-manager' );
    }

    public function prepare_items() {
        $this->items = $this->product_manager->get_all_products();
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = array();
        $this->_column_headers = array( $columns, $hidden, $sortable );
    }
}
