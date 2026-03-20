<?php
/**
 * Admin page template for MC EMS License Manager
 *
 * Variables available:
 *   $list_table MC_EMS_License_List_Table
 *   $users      array of WP_User objects (ID, display_name, user_email)
 *   $notices    array|false  { message, type }
 *
 * @package MC_EMS_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap mc-ems-admin">

	<h1 class="wp-heading-inline"><?php esc_html_e( 'MC EMS Licenses', 'mc-ems-license-manager' ); ?></h1>

	<?php if ( $notices ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notices['type'] ); ?> is-dismissible">
			<p><?php echo esc_html( $notices['message'] ); ?></p>
		</div>
	<?php endif; ?>

	<!-- Create new license -->
	<div class="mc-ems-create-license card">
		<h2><?php esc_html_e( 'Create New License', 'mc-ems-license-manager' ); ?></h2>

		<form method="post" action="">
			<?php wp_nonce_field( 'mc_ems_create_license', 'mc_ems_create_license_nonce' ); ?>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="license_user_id"><?php esc_html_e( 'User', 'mc-ems-license-manager' ); ?> <span class="required">*</span></label>
						</th>
						<td>
							<select name="license_user_id" id="license_user_id" required>
								<option value=""><?php esc_html_e( '— Select a user —', 'mc-ems-license-manager' ); ?></option>
								<?php foreach ( $users as $user ) : ?>
									<option value="<?php echo esc_attr( $user->ID ); ?>">
										<?php echo esc_html( $user->display_name . ' (' . $user->user_email . ')' ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="license_duration"><?php esc_html_e( 'Duration (days)', 'mc-ems-license-manager' ); ?></label>
						</th>
						<td>
							<input
								type="number"
								name="license_duration"
								id="license_duration"
								min="1"
								value=""
								class="small-text"
							/>
							<p class="description"><?php esc_html_e( 'Leave empty for a license that never expires.', 'mc-ems-license-manager' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="license_site_url"><?php esc_html_e( 'Site URL', 'mc-ems-license-manager' ); ?></label>
						</th>
						<td>
							<input
								type="url"
								name="license_site_url"
								id="license_site_url"
								value=""
								class="regular-text"
								placeholder="https://example.com"
							/>
							<p class="description"><?php esc_html_e( 'Optional. Bind this license to a specific site URL.', 'mc-ems-license-manager' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>

			<?php submit_button( __( 'Create License', 'mc-ems-license-manager' ), 'primary', 'submit_create_license' ); ?>
		</form>
	</div>

	<hr />

	<!-- License list -->
	<h2><?php esc_html_e( 'All Licenses', 'mc-ems-license-manager' ); ?></h2>

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
