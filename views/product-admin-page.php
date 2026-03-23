<?php
/**
 * Admin page template for MC EMS License Manager – Product management
 *
 * Variables available:
 *   $list_table  MC_EMS_Product_List_Table
 *   $wc_products WC_Product[] (empty array when WooCommerce is inactive)
 *   $notices     array|false  { message, type }
 *
 * @package MC_EMS_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap mc-ems-admin">

	<h1 class="wp-heading-inline"><?php esc_html_e( 'Prodotti', 'mc-ems-license-manager' ); ?></h1>

	<?php if ( $notices ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notices['type'] ); ?> is-dismissible">
			<p><?php echo esc_html( $notices['message'] ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! function_exists( 'WC' ) ) : ?>
		<div class="notice notice-warning">
			<p><?php esc_html_e( 'WooCommerce non è attivo. Attivalo per utilizzare questa funzionalità.', 'mc-ems-license-manager' ); ?></p>
		</div>
	<?php endif; ?>

	<!-- Add product form -->
	<div class="mc-ems-card card">
		<h2><?php esc_html_e( 'Aggiungi Prodotto', 'mc-ems-license-manager' ); ?></h2>
		<p class="description">
			<?php esc_html_e( "Associa un prodotto WooCommerce alla generazione automatica di licenze. Quando un ordine che include questo prodotto viene completato, verrà generata o rinnovata la licenza per l'acquirente.", 'mc-ems-license-manager' ); ?>
		</p>

		<form method="post" action="">
			<?php wp_nonce_field( 'mc_ems_add_product', 'mc_ems_add_product_nonce' ); ?>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="product_id"><?php esc_html_e( 'Prodotto WooCommerce', 'mc-ems-license-manager' ); ?> <span class="required">*</span></label>
						</th>
						<td>
							<?php if ( empty( $wc_products ) ) : ?>
								<p class="description"><?php esc_html_e( 'Nessun prodotto WooCommerce trovato.', 'mc-ems-license-manager' ); ?></p>
								<input type="hidden" name="product_id" value="0" />
							<?php else : ?>
								<select name="product_id" id="product_id" required>
									<option value=""><?php esc_html_e( '— Seleziona un prodotto —', 'mc-ems-license-manager' ); ?></option>
									<?php foreach ( $wc_products as $product ) : ?>
										<option value="<?php echo esc_attr( $product->get_id() ); ?>">
											<?php echo esc_html( $product->get_name() . ' (#' . $product->get_id() . ')' ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							<?php endif; ?>
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
	</div>

	<hr />

	<!-- Product list -->
	<h2><?php esc_html_e( 'Prodotti Associati', 'mc-ems-license-manager' ); ?></h2>

	<form id="mc-ems-products-form" method="post">
		<?php $list_table->display(); ?>
	</form>

	<!-- Inline edit form (hidden by default) -->
	<div id="mc-ems-edit-modal" class="mc-ems-edit-modal" style="display:none;">
		<div class="mc-ems-edit-modal__backdrop"></div>
		<div class="mc-ems-edit-modal__box">
			<h3><?php esc_html_e( 'Modifica Durata Licenza', 'mc-ems-license-manager' ); ?></h3>

			<form method="post" action="" id="mc-ems-edit-product-form">
				<?php wp_nonce_field( 'mc_ems_edit_product', 'mc_ems_edit_product_nonce' ); ?>
				<input type="hidden" name="edit_product_id" id="edit_product_id" value="" />

				<p>
					<label for="edit_duration_days"><?php esc_html_e( 'Durata (giorni):', 'mc-ems-license-manager' ); ?></label>
					<input
						type="number"
						name="edit_duration_days"
						id="edit_duration_days"
						min="1"
						value="365"
						class="small-text"
						required
					/>
				</p>

				<p class="mc-ems-edit-modal__actions">
					<?php submit_button( __( 'Salva', 'mc-ems-license-manager' ), 'primary', 'submit_edit_product', false ); ?>
					<button type="button" class="button mc-ems-edit-modal__cancel"><?php esc_html_e( 'Annulla', 'mc-ems-license-manager' ); ?></button>
				</p>
			</form>
		</div>
	</div>

</div>
