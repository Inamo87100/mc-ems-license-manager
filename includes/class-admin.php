<?php
/**
 * Admin class for MC EMS License Manager
 *
 * Registers the admin menu page, handles form submissions and AJAX actions.
 *
 * @package MC_EMS_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MC_EMS_Admin {

	/**
	 * @var MC_EMS_License_Manager
	 */
	private $license_manager;

	/**
	 * Constructor.
	 *
	 * @param MC_EMS_License_Manager $license_manager License manager instance.
	 */
	public function __construct( MC_EMS_License_Manager $license_manager ) {
		$this->license_manager = $license_manager;

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_mc_ems_revoke_license', array( $this, 'ajax_revoke_license' ) );
	}

	/**
	 * Add admin menu page.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'MC EMS Licenses', 'mc-ems-license-manager' ),
			__( 'MC EMS Licenses', 'mc-ems-license-manager' ),
			'manage_options',
			'mc-ems-licenses',
			array( $this, 'render_page' ),
			'dashicons-admin-network',
			56
		);
	}

	/**
	 * Enqueue admin styles and scripts.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'toplevel_page_mc-ems-licenses' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'mc-ems-admin',
			MC_EMS_LICENSE_MANAGER_URL . 'assets/css/admin.css',
			array(),
			MC_EMS_LICENSE_MANAGER_VERSION
		);

		wp_enqueue_script(
			'mc-ems-admin',
			MC_EMS_LICENSE_MANAGER_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			MC_EMS_LICENSE_MANAGER_VERSION,
			true
		);

		wp_localize_script(
			'mc-ems-admin',
			'mcEmsAdmin',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'mc_ems_license_action' ),
				'i18n'    => array(
					'confirm_revoke' => __( 'Are you sure you want to revoke this license?', 'mc-ems-license-manager' ),
					'revoked'        => __( 'License revoked successfully.', 'mc-ems-license-manager' ),
					'error'          => __( 'An error occurred. Please try again.', 'mc-ems-license-manager' ),
				),
			)
		);
	}

	/**
	 * Process actions before rendering the page.
	 *
	 * @return void
	 */
	private function process_action() {
		// Process create license form.
		if ( isset( $_POST['mc_ems_create_license_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_key( $_POST['mc_ems_create_license_nonce'] ), 'mc_ems_create_license' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'mc-ems-license-manager' ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to do this.', 'mc-ems-license-manager' ) );
			}

			$user_id  = isset( $_POST['license_user_id'] ) ? absint( $_POST['license_user_id'] ) : 0;
			$duration = isset( $_POST['license_duration'] ) && '' !== $_POST['license_duration']
				? absint( $_POST['license_duration'] )
				: null;
			$site_url = isset( $_POST['license_site_url'] ) ? sanitize_text_field( wp_unslash( $_POST['license_site_url'] ) ) : '';

			$result = $this->license_manager->create_license( $user_id, $duration, $site_url ?: null );

			if ( $result ) {
				$this->add_notice( __( 'License created successfully.', 'mc-ems-license-manager' ), 'success' );
			} else {
				$this->add_notice( __( 'Failed to create license. Please check the user ID.', 'mc-ems-license-manager' ), 'error' );
			}

			wp_safe_redirect( admin_url( 'admin.php?page=mc-ems-licenses' ) );
			exit;
		}

		// Process single row actions (activate / deactivate / delete).
		if ( ! empty( $_GET['action'] ) && ! empty( $_GET['license_id'] ) ) {
			if ( empty( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['nonce'] ), 'mc_ems_license_action' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'mc-ems-license-manager' ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to do this.', 'mc-ems-license-manager' ) );
			}

			$action     = sanitize_key( $_GET['action'] );
			$license_id = absint( $_GET['license_id'] );

			switch ( $action ) {
				case 'activate':
					$this->license_manager->update_status( $license_id, 'active' );
					$this->add_notice( __( 'License activated.', 'mc-ems-license-manager' ), 'success' );
					break;

				case 'deactivate':
					$this->license_manager->update_status( $license_id, 'inactive' );
					$this->add_notice( __( 'License deactivated.', 'mc-ems-license-manager' ), 'success' );
					break;

				case 'delete':
					$this->license_manager->delete_license( $license_id );
					$this->add_notice( __( 'License deleted.', 'mc-ems-license-manager' ), 'success' );
					break;
			}

			wp_safe_redirect( admin_url( 'admin.php?page=mc-ems-licenses' ) );
			exit;
		}

		// Process bulk actions.
		if ( ! empty( $_POST['action'] ) || ! empty( $_POST['action2'] ) ) {
			$bulk_action = ! empty( $_POST['action'] ) && '-1' !== $_POST['action']
				? sanitize_key( $_POST['action'] )
				: ( isset( $_POST['action2'] ) ? sanitize_key( $_POST['action2'] ) : '' );

			if ( $bulk_action && ! empty( $_POST['license_ids'] ) ) {
				if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'bulk-licenses' ) ) {
					wp_die( esc_html__( 'Security check failed.', 'mc-ems-license-manager' ) );
				}

				if ( ! current_user_can( 'manage_options' ) ) {
					wp_die( esc_html__( 'You do not have permission to do this.', 'mc-ems-license-manager' ) );
				}

				$ids = array_map( 'absint', (array) $_POST['license_ids'] );

				foreach ( $ids as $id ) {
					switch ( $bulk_action ) {
						case 'bulk_activate':
							$this->license_manager->update_status( $id, 'active' );
							break;
						case 'bulk_deactivate':
							$this->license_manager->update_status( $id, 'inactive' );
							break;
						case 'bulk_delete':
							$this->license_manager->delete_license( $id );
							break;
					}
				}

				$this->add_notice( __( 'Bulk action applied.', 'mc-ems-license-manager' ), 'success' );
				wp_safe_redirect( admin_url( 'admin.php?page=mc-ems-licenses' ) );
				exit;
			}
		}
	}

	/**
	 * Render the admin page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->process_action();

		$list_table = new MC_EMS_License_List_Table( $this->license_manager );
		$list_table->prepare_items();

		$users = get_users( array( 'fields' => array( 'ID', 'display_name', 'user_email' ) ) );

		$notices = get_transient( 'mc_ems_admin_notices_' . get_current_user_id() );
		if ( $notices ) {
			delete_transient( 'mc_ems_admin_notices_' . get_current_user_id() );
		}

		include MC_EMS_LICENSE_MANAGER_DIR . 'views/license-admin-page.php';
	}

	/**
	 * AJAX handler: revoke license.
	 *
	 * @return void
	 */
	public function ajax_revoke_license() {
		check_ajax_referer( 'mc_ems_license_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'mc-ems-license-manager' ) ) );
		}

		$license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';

		if ( empty( $license_key ) ) {
			wp_send_json_error( array( 'message' => __( 'License key is required.', 'mc-ems-license-manager' ) ) );
		}

		global $wpdb;
		$license = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT id FROM ' . MC_EMS_Database::table_name() . ' WHERE license_key = %s',
				$license_key
			)
		);

		if ( ! $license ) {
			wp_send_json_error( array( 'message' => __( 'License not found.', 'mc-ems-license-manager' ) ) );
		}

		$this->license_manager->update_status( (int) $license->id, 'inactive' );
		wp_send_json_success( array( 'message' => __( 'License revoked.', 'mc-ems-license-manager' ) ) );
	}

	/**
	 * Store an admin notice in a transient.
	 *
	 * @param string $message Notice message.
	 * @param string $type    Notice type: 'success', 'error', 'warning', 'info'.
	 * @return void
	 */
	private function add_notice( $message, $type = 'success' ) {
		set_transient(
			'mc_ems_admin_notices_' . get_current_user_id(),
			array(
				'message' => $message,
				'type'    => $type,
			),
			30
		);
	}
}
