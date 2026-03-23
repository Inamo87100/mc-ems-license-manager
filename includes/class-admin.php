<?php
/**
 * Admin class for MC EMS License Manager
 *
 * Registers the admin menu pages, handles form submissions and AJAX actions.
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
	 * @var MC_EMS_Product_Manager
	 */
	private $product_manager;

	/**
	 * Constructor.
	 *
	 * @param MC_EMS_License_Manager $license_manager License manager instance.
	 * @param MC_EMS_Product_Manager $product_manager Product manager instance.
	 */
	public function __construct( MC_EMS_License_Manager $license_manager, MC_EMS_Product_Manager $product_manager ) {
		$this->license_manager = $license_manager;
		$this->product_manager = $product_manager;

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_mc_ems_revoke_license', array( $this, 'ajax_revoke_license' ) );
	}

	/**
	 * Add admin menu pages and submenus.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Licenze', 'mc-ems-license-manager' ),
			__( 'Licenze', 'mc-ems-license-manager' ),
			'manage_options',
			'mc-ems-licenses',
			array( $this, 'render_page' ),
			'dashicons-admin-network',
			56
		);

		add_submenu_page(
			'mc-ems-licenses',
			__( 'Gestisci Licenze', 'mc-ems-license-manager' ),
			__( 'Gestisci Licenze', 'mc-ems-license-manager' ),
			'manage_options',
			'mc-ems-licenses',
			array( $this, 'render_page' )
		);

		add_submenu_page(
			'mc-ems-licenses',
			__( 'Prodotti', 'mc-ems-license-manager' ),
			__( 'Prodotti', 'mc-ems-license-manager' ),
			'manage_options',
			'mc-ems-products',
			array( $this, 'render_products_page' )
		);
	}

	/**
	 * Enqueue admin styles and scripts.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {
		$our_hooks = array(
			'toplevel_page_mc-ems-licenses',
			'licenze_page_mc-ems-products',
		);

		if ( ! in_array( $hook, $our_hooks, true ) ) {
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
					'confirm_revoke'        => __( 'Are you sure you want to revoke this license?', 'mc-ems-license-manager' ),
					'revoked'               => __( 'License revoked successfully.', 'mc-ems-license-manager' ),
					'error'                 => __( 'An error occurred. Please try again.', 'mc-ems-license-manager' ),
					'save'                  => __( 'Save', 'mc-ems-license-manager' ),
					'cancel'                => __( 'Cancel', 'mc-ems-license-manager' ),
					'duration_label'        => __( 'Duration (days):', 'mc-ems-license-manager' ),
					'confirm_delete_product' => __( 'Are you sure you want to remove this product association?', 'mc-ems-license-manager' ),
				),
			)
		);
	}

	/**
	 * Process license row/bulk actions.
	 *
	 * @return void
	 */
	private function process_action() {
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
	 * Process product CRUD actions.
	 *
	 * @return void
	 */
	private function process_product_action() {
		// Add product association.
		if ( isset( $_POST['mc_ems_add_product_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_key( $_POST['mc_ems_add_product_nonce'] ), 'mc_ems_add_product' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'mc-ems-license-manager' ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to do this.', 'mc-ems-license-manager' ) );
			}

			$product_id    = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
			$duration_days = isset( $_POST['duration_days'] ) ? absint( $_POST['duration_days'] ) : 365;

			if ( ! $product_id ) {
				$this->add_notice( __( 'Please select a valid product.', 'mc-ems-license-manager' ), 'error' );
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

		// Edit product association.
		if ( isset( $_POST['mc_ems_edit_product_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_key( $_POST['mc_ems_edit_product_nonce'] ), 'mc_ems_edit_product' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'mc-ems-license-manager' ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to do this.', 'mc-ems-license-manager' ) );
			}

			$product_id    = isset( $_POST['edit_product_id'] ) ? absint( $_POST['edit_product_id'] ) : 0;
			$duration_days = isset( $_POST['edit_duration_days'] ) ? absint( $_POST['edit_duration_days'] ) : 0;

			if ( ! $product_id || ! $duration_days ) {
				$this->add_notice( __( 'Invalid product or duration.', 'mc-ems-license-manager' ), 'error' );
				wp_safe_redirect( admin_url( 'admin.php?page=mc-ems-products' ) );
				exit;
			}

			$result = $this->product_manager->update_product( $product_id, $duration_days );

			if ( $result ) {
				$this->add_notice( __( 'Product updated successfully.', 'mc-ems-license-manager' ), 'success' );
			} else {
				$this->add_notice( __( 'Failed to update product.', 'mc-ems-license-manager' ), 'error' );
			}

			wp_safe_redirect( admin_url( 'admin.php?page=mc-ems-products' ) );
			exit;
		}

		// Delete product association.
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
				$this->add_notice( __( 'Product association removed.', 'mc-ems-license-manager' ), 'success' );
			} else {
				$this->add_notice( __( 'Failed to remove product association.', 'mc-ems-license-manager' ), 'error' );
			}

			wp_safe_redirect( admin_url( 'admin.php?page=mc-ems-products' ) );
			exit;
		}
	}

	/**
	 * Render the licenses admin page (read-only list).
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

		$notices = get_transient( 'mc_ems_admin_notices_' . get_current_user_id() );
		if ( $notices ) {
			delete_transient( 'mc_ems_admin_notices_' . get_current_user_id() );
		}

		include MC_EMS_LICENSE_MANAGER_DIR . 'views/license-admin-page.php';
	}

	/**
	 * Render the products admin page.
	 *
	 * @return void
	 */
	public function render_products_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->process_product_action();

		$list_table = new MC_EMS_Product_List_Table( $this->product_manager );
		$list_table->prepare_items();

		// Get WooCommerce products for the add-product select.
		$wc_products = array();
		if ( function_exists( 'wc_get_products' ) ) {
			$wc_products = wc_get_products(
				array(
					'status' => 'publish',
					'limit'  => -1,
					'return' => 'objects',
				)
			);
		}

		$notices = get_transient( 'mc_ems_admin_notices_' . get_current_user_id() );
		if ( $notices ) {
			delete_transient( 'mc_ems_admin_notices_' . get_current_user_id() );
		}

		include MC_EMS_LICENSE_MANAGER_DIR . 'views/product-admin-page.php';
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
