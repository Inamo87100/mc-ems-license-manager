<?php
/**
 * WooCommerce Integration for MC EMS License Manager
 *
 * Hooks into WooCommerce order completion to automatically generate or renew
 * licenses for products that have a license association.
 *
 * @package MC_EMS_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MC_EMS_WooCommerce_Integration {

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

		add_action( 'woocommerce_order_status_completed', array( $this, 'handle_order_completed' ), 10, 1 );
	}

	/**
	 * Handle a completed WooCommerce order.
	 *
	 * For each line item, if the product has a license association:
	 *   - If the customer already owns a license for that product → extend it.
	 *   - Otherwise → create a new license.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return void
	 */
	public function handle_order_completed( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$user_id = (int) $order->get_customer_id();
		if ( ! $user_id ) {
			// Guest checkout – we cannot associate a license without a user account.
			return;
		}

		$logger  = wc_get_logger();
		$context = array( 'source' => 'mc-ems-license-manager' );

		foreach ( $order->get_items() as $item ) {
			$product_id = (int) $item->get_product_id();

			if ( ! $this->product_manager->is_product_licensed( $product_id ) ) {
				continue;
			}

			$duration = $this->product_manager->get_product_duration( $product_id );

			try {
				$existing = $this->license_manager->get_license_by_user_product( $user_id, $product_id );

				if ( $existing ) {
					// Extend the existing license.
					$extended = $this->license_manager->extend_license( (int) $existing['id'], $duration );
					if ( $extended ) {
						$logger->info(
							sprintf(
								'Extended license #%d for user #%d (product #%d) by %d days (order #%d).',
								$existing['id'],
								$user_id,
								$product_id,
								$duration,
								$order_id
							),
							$context
						);
					} else {
						$logger->error(
							sprintf(
								'Failed to extend license #%d for user #%d (product #%d, order #%d).',
								$existing['id'],
								$user_id,
								$product_id,
								$order_id
							),
							$context
						);
					}
				} else {
					// Create a new license for this product.
					$new_id = $this->license_manager->create_license( $user_id, $duration, null, $product_id );
					if ( $new_id ) {
						$logger->info(
							sprintf(
								'Created license #%d for user #%d (product #%d, duration %d days, order #%d).',
								$new_id,
								$user_id,
								$product_id,
								$duration,
								$order_id
							),
							$context
						);
					} else {
						$logger->error(
							sprintf(
								'Failed to create license for user #%d (product #%d, order #%d).',
								$user_id,
								$product_id,
								$order_id
							),
							$context
						);
					}
				}
			} catch ( Exception $e ) {
				$logger->error(
					sprintf(
						'Exception while processing license for product #%d, order #%d: %s',
						$product_id,
						$order_id,
						$e->getMessage()
					),
					$context
				);
			}
		}
	}
}
