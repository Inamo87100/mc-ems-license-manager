<?php
/**
 * Product Admin Page for MC EMS License Manager.
 * @package MC_EMS_License_Manager
 */
?>
<div class="wrap mc-ems-admin">
    <h1><?php esc_html_e( 'Associazioni prodotti e chiavi licenza', 'mc-ems-license-manager' ); ?></h1>

    <?php
    // Admin notices.
    if ( function_exists( 'settings_errors' ) ) {
        settings_errors();
    }
    ?>

    <form id="mc-ems-add-product-form" method="post">
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row">
                    <label for="product_id"><?php esc_html_e( 'Prodotto WooCommerce', 'mc-ems-license-manager' ); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <select name="product_id" id="product_id" required>
                        <option value=""><?php esc_html_e( '— Seleziona un prodotto —', 'mc-ems-license-manager' ); ?></option>
                        <?php foreach ( $wc_products as $product ) : ?>
                            <option value="<?php echo esc_attr( $product->get_id() ); ?>">
                                <?php echo esc_html( $product->get_name() . ' (#' . $product->get_id() . ')' ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="duration_days"><?php esc_html_e( 'Durata licenza (giorni)', 'mc-ems-license-manager' ); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input
                        type="number"
                        name="duration_days"
                        id="duration_days"
                        min="1"
                        value="365"
                        class="small-text"
                        required
                    />
                    <p class="description"><?php esc_html_e( 'Di default 365 giorni (1 anno).', 'mc-ems-license-manager' ); ?></p>
                </td>
            </tr>
            </tbody>
        </table>
        <?php submit_button( __( 'Aggiungi', 'mc-ems-license-manager' ), 'primary', 'submit_add_product' ); ?>
    </form>

    <hr />

    <h2><?php esc_html_e( 'Prodotti Associati', 'mc-ems-license-manager' ); ?></h2>
    <form id="mc-ems-products-form" method="post">
        <?php $list_table->display(); ?>
    </form>
</div>
