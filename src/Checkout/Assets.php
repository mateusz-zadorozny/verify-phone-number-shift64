<?php
/**
 * Checkout assets loader.
 *
 * @package Shift64\SmartPhoneValidation\Checkout
 */

declare(strict_types=1);

namespace Shift64\SmartPhoneValidation\Checkout;

use Shift64\SmartPhoneValidation\Admin\Settings;

/**
 * Handles loading of checkout-related assets (JS/CSS).
 */
class Assets {

	/**
	 * Initialize assets hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_checkout_scripts' ) );
	}

	/**
	 * Enqueue checkout validation scripts.
	 *
	 * @return void
	 */
	public static function enqueue_checkout_scripts(): void {
		// Only load on checkout page when validation is enabled.
		if ( ! is_checkout() || ! Settings::is_validation_enabled() ) {
			return;
		}

		// Classic checkout script (requires jQuery and wc-checkout).
		if ( ! self::is_block_checkout() ) {
			wp_enqueue_script(
				'shift64-phone-checkout-validation',
				SHIFT64_PHONE_VALIDATION_URL . 'assets/js/checkout-validation.js',
				array( 'jquery', 'wc-checkout' ),
				SHIFT64_PHONE_VALIDATION_VERSION,
				true
			);
		}

		// Block checkout script (vanilla JS, no dependencies).
		if ( self::is_block_checkout() ) {
			wp_enqueue_script(
				'shift64-phone-block-checkout-validation',
				SHIFT64_PHONE_VALIDATION_URL . 'assets/js/block-checkout-validation.js',
				array(),
				SHIFT64_PHONE_VALIDATION_VERSION,
				true
			);
		}
	}

	/**
	 * Check if the current checkout page uses block checkout.
	 *
	 * @return bool True if using block checkout.
	 */
	private static function is_block_checkout(): bool {
		// Check if WooCommerce Blocks is available.
		if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Package' ) ) {
			return false;
		}

		// Get the checkout page.
		$checkout_page_id = wc_get_page_id( 'checkout' );
		if ( ! $checkout_page_id ) {
			return false;
		}

		$checkout_page = get_post( $checkout_page_id );
		if ( ! $checkout_page ) {
			return false;
		}

		// Check if page contains the checkout block.
		return has_block( 'woocommerce/checkout', $checkout_page );
	}
}
