public function column_actions( $item ) {
    $delete_nonce = wp_create_nonce( 'delete_product' );
    $delete_link = sprintf( "<a href='?page=%s&action=delete&product_id=%d&_wpnonce=%s' title='%s'>%s</a>",
        esc_attr( 'your_page_slug' ),
        $item['product_id'],
        $delete_nonce,
        esc_attr( 'Delete this item' ),
        __('Delete', 'your-text-domain')
    );

    return $delete_link;
}