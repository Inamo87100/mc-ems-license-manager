<?php
// Assuming this is the structure of the file

class Product_List_Table extends WP_List_Table {
    
    // Other methods

    function column_actions( $item ) {
        $actions = array();

        // Keeping only the Delete button
        $actions['delete'] = sprintf( '<a href="%s">%s</a>',
            wp_nonce_url( admin_url( "admin.php?page=your_page&action=delete&product_id={$item['id']}" ), 'delete_product' ),
            __( 'Delete', 'your-text-domain' )
        );

        return $actions;
    }

    // Other methods
}

?>