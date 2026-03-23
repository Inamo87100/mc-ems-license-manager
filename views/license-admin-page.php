<?php
/**
 * Admin page template for MC EMS License Manager – Licenses list (read-only)
 *
 * Variables available:
 *   $list_table MC_EMS_License_List_Table
 *   $notices    array|false  { message, type }
 *
 * @package MC_EMS_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap mc-ems-admin">

	<h1 class="wp-heading-inline"><?php esc_html_e( 'Gestisci Licenze', 'mc-ems-license-manager' ); ?></h1>

	<?php if ( $notices ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notices['type'] ); ?> is-dismissible">
			<p><?php echo esc_html( $notices['message'] ); ?></p>
		</div>
	<?php endif; ?>

	<p class="description">
		<?php esc_html_e( 'Le licenze vengono generate automaticamente al completamento di un ordine WooCommerce per i prodotti associati.', 'mc-ems-license-manager' ); ?>
	</p>

	<!-- License list -->
	<form id="mc-ems-licenses-form" method="post">
		<?php
		$list_table->search_box( __( 'Search Licenses', 'mc-ems-license-manager' ), 'mc-ems-license-search' );

		// Status filter.
		$current_status = isset( $_GET['status_filter'] ) ? sanitize_key( $_GET['status_filter'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		?>
		<div class="mc-ems-status-filter">
			<label for="status_filter"><?php esc_html_e( 'Status:', 'mc-ems-license-manager' ); ?></label>
			<select name="status_filter" id="status_filter">
				<option value=""><?php esc_html_e( 'All', 'mc-ems-license-manager' ); ?></option>
				<option value="active" <?php selected( $current_status, 'active' ); ?>><?php esc_html_e( 'Active', 'mc-ems-license-manager' ); ?></option>
				<option value="inactive" <?php selected( $current_status, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'mc-ems-license-manager' ); ?></option>
				<option value="expired" <?php selected( $current_status, 'expired' ); ?>><?php esc_html_e( 'Expired', 'mc-ems-license-manager' ); ?></option>
			</select>
		</div>

		<?php $list_table->display(); ?>
	</form>

</div>
