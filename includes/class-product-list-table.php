public function column_actions( $item ) {
    // Remove the Edit button and keep only the Delete button
    $delete_button = '<a href="?action=delete&id=' . esc_attr($item['ID']) . '" class="button button-secondary">Delete</a>'; 
    return $delete_button; 
}