<?php
/**
 * License List Table for WP Admin
 *
 * Extends WP_List_Table to render licenses in the admin dashboard.
 *
 * @package MC_EMS_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class MC_EMS_License_List_Table extends WP_List_Table {

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

		parent::__construct(
			array(
				'singular' => __( 'License', 'mc-ems-license-manager' ),
				'plural'   => __( 'Licenses', 'mc-ems-license-manager' ),
				'ajax'     => false,
			)
		);
	}

	/**
	 * Define table columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'          => '<input type="checkbox" />',
			'user'        => __( 'User', 'mc-ems-license-manager' ),
			'product'     => __( 'Product', 'mc-ems-license-manager' ),
			'license_key' => __( 'License Key', 'mc-ems-license-manager' ),
			'site_url'    => __( 'Site URL', 'mc-ems-license-manager' ),
			'status'      => __( 'Status', 'mc-ems-license-manager' ),
			'created_at'  => __( 'Created', 'mc-ems-license-manager' ),
			'expires_at'  => __( 'Expires', 'mc-ems-license-manager' ),
			'actions'     => __( 'Actions', 'mc-ems-license-manager' ),
		);
	}

	/**
	 * Sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'user'        => array( 'user_id', false ),
			'license_key' => array( 'license_key', false ),
			'status'      => array( 'status', false ),
			'created_at'  => array( 'created_at', true ),
			'expires_at'  => array( 'expires_at', false ),
		);
	}

	/**
	 * Checkbox column.
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="license_ids[]" value="%d" />',
			(int) $item['id']
		);
	}

	/**
	 * User column.
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	public function column_user( $item ) {
		$user = get_userdata( (int) $item['user_id'] );
		if ( $user ) {
			return esc_html( $user->display_name ) . ' <small>(' . esc_html( $user->user_email ) . ')</small>';
		}
		return esc_html__( 'Unknown user', 'mc-ems-license-manager' );
	}

	/**
	 * Product column.
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	public function column_product( $item ) {
		if ( empty( $item['product_id'] ) ) {
			return '&mdash;';
		}
		$product_id   = (int) $item['product_id'];
		$product_name = get_the_title( $product_id );
		if ( $product_name ) {
			return esc_html( $product_name ) . ' <small>(#' . $product_id . ')</small>';
		}
		return '#' . $product_id;
	}

	/**
	 * License key column.
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	public function column_license_key( $item ) {
		return '<code>' . esc_html( $item['license_key'] ) . '</code>';
	}

	/**
	 * Site URL column.
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	public function column_site_url( $item ) {
		return $item['site_url'] ? esc_html( $item['site_url'] ) : '&mdash;';
	}

	/**
	 * Status column.
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	public function column_status( $item ) {
		$status_labels = array(
			'active'   => __( 'Active', 'mc-ems-license-manager' ),
			'inactive' => __( 'Inactive', 'mc-ems-license-manager' ),
			'expired'  => __( 'Expired', 'mc-ems-license-manager' ),
		);
		$label = isset( $status_labels[ $item['status'] ] ) ? $status_labels[ $item['status'] ] : esc_html( $item['status'] );
		$class = 'mc-ems-status mc-ems-status--' . esc_attr( $item['status'] );
		return '<span class="' . $class . '">' . esc_html( $label ) . '</span>';
	}

	/**
	 * Created at column.
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	public function column_created_at( $item ) {
		return esc_html( $item['created_at'] );
	}

	/**
	 * Expires at column.
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	public function column_expires_at( $item ) {
		return $item['expires_at'] ? esc_html( $item['expires_at'] ) : esc_html__( 'Never', 'mc-ems-license-manager' );
	}

	/**
	 * Actions column.
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	public function column_actions( $item ) {
		$id      = (int) $item['id'];
		$nonce   = wp_create_nonce( 'mc_ems_license_action' );
		$base    = admin_url( 'admin.php?page=mc-ems-licenses&nonce=' . $nonce );
		$buttons = '';

		if ( 'active' === $item['status'] ) {
			$buttons .= sprintf(
				'<a href="%s" class="button button-small">%s</a> ',
				esc_url( $base . '&action=deactivate&license_id=' . $id ),
				esc_html__( 'Deactivate', 'mc-ems-license-manager' )
			);
		} elseif ( 'inactive' === $item['status'] || 'expired' === $item['status'] ) {
			$buttons .= sprintf(
				'<a href="%s" class="button button-small button-primary">%s</a> ',
				esc_url( $base . '&action=activate&license_id=' . $id ),
				esc_html__( 'Activate', 'mc-ems-license-manager' )
			);
		}

		$buttons .= sprintf(
			'<a href="%s" class="button button-small button-link-delete" onclick="return confirm(\'%s\')">%s</a>',
			esc_url( $base . '&action=delete&license_id=' . $id ),
			esc_js( __( 'Are you sure you want to delete this license?', 'mc-ems-license-manager' ) ),
			esc_html__( 'Delete', 'mc-ems-license-manager' )
		);

		return $buttons;
	}

	/**
	 * Default column renderer.
	 *
	 * @param array  $item        Row data.
	 * @param string $column_name Column name.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '';
	}

	/**
	 * Bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'bulk_activate'   => __( 'Activate', 'mc-ems-license-manager' ),
			'bulk_deactivate' => __( 'Deactivate', 'mc-ems-license-manager' ),
			'bulk_delete'     => __( 'Delete', 'mc-ems-license-manager' ),
		);
	}

	/**
	 * Prepare items for display.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$per_page = 20;
		$paged    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification

		$args = array(
			'per_page' => $per_page,
			'paged'    => $paged,
		);

		if ( ! empty( $_GET['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$args['search'] = sanitize_text_field( wp_unslash( $_GET['s'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		if ( ! empty( $_GET['status_filter'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$args['status'] = sanitize_text_field( wp_unslash( $_GET['status_filter'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		if ( ! empty( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$args['orderby'] = sanitize_key( $_GET['orderby'] ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		if ( ! empty( $_GET['order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$args['order'] = sanitize_key( $_GET['order'] ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		$total_items  = $this->license_manager->count_licenses( $args );
		$this->items  = $this->license_manager->get_licenses( $args );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );
	}
}
