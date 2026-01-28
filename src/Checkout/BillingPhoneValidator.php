<?php
/**
 * Billing phone validation for WooCommerce checkout.
 *
 * @package Shift64\SmartPhoneValidation\Checkout
 */

declare(strict_types=1);

namespace Shift64\SmartPhoneValidation\Checkout;

use Shift64\SmartPhoneValidation\Admin\Settings;
use Shift64\SmartPhoneValidation\Formatter\PhoneFormatter;
use Shift64\SmartPhoneValidation\Validation\PhoneValidator;

/**
 * Handles billing phone validation and formatting during WooCommerce checkout.
 */
class BillingPhoneValidator {

	/**
	 * Initialize checkout validation hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Validate billing phone during checkout process.
		add_action( 'woocommerce_checkout_process', array( self::class, 'validate_billing_phone' ) );

		// Format billing phone before saving order.
		add_action( 'woocommerce_checkout_create_order', array( self::class, 'format_billing_phone_on_order' ), 10, 2 );
	}

	/**
	 * Validate billing phone number during checkout.
	 *
	 * @return void
	 */
	public static function validate_billing_phone(): void {
		// Only validate if plugin is enabled.
		if ( ! Settings::is_validation_enabled() ) {
			return;
		}

		// Get billing phone from POST data.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce handles nonce verification.
		$billing_phone = isset( $_POST['billing_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_phone'] ) ) : '';

		// Skip validation if phone is empty (WooCommerce handles required field validation).
		if ( '' === $billing_phone ) {
			return;
		}

		// Get country for validation context.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce handles nonce verification.
		$billing_country = isset( $_POST['billing_country'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_country'] ) ) : null;

		// Use billing country if provided, otherwise use default from settings.
		$country_code = ! empty( $billing_country ) ? $billing_country : null;

		// Validate the phone number.
		$result = PhoneValidator::validate( $billing_phone, $country_code );

		if ( ! $result->is_valid() ) {
			$error_message = self::get_user_friendly_error( $result->get_error_message() );
			wc_add_notice( $error_message, 'error' );
		}
	}

	/**
	 * Format billing phone number before saving to order.
	 *
	 * @param \WC_Order $order    The order object.
	 * @param array     $data     The checkout data.
	 * @return void
	 */
	public static function format_billing_phone_on_order( \WC_Order $order, array $data ): void {
		// Only format if plugin is enabled and format on save is enabled.
		if ( ! Settings::is_validation_enabled() || ! Settings::is_format_on_save_enabled() ) {
			return;
		}

		$billing_phone = $order->get_billing_phone();

		// Skip if phone is empty.
		if ( '' === $billing_phone ) {
			return;
		}

		// Get billing country for validation context.
		$billing_country = $order->get_billing_country();
		$country_code    = ! empty( $billing_country ) ? $billing_country : null;

		// Validate and format the phone number.
		$result = PhoneValidator::validate( $billing_phone, $country_code );

		if ( $result->is_valid() ) {
			$formatted = PhoneFormatter::format( $result->get_phone_number() );
			$order->set_billing_phone( $formatted );
		}
	}

	/**
	 * Get user-friendly error message for checkout display.
	 *
	 * Maps internal validation errors to user-facing messages as specified in acceptance criteria.
	 *
	 * @param string|null $internal_message The internal validation error message.
	 * @return string The user-friendly error message.
	 */
	private static function get_user_friendly_error( ?string $internal_message ): string {
		// Check if the error is about missing international prefix.
		if ( null !== $internal_message && false !== strpos( $internal_message, 'international prefix' ) ) {
			return __( 'Number must contain country prefix.', 'verify-phone-number-shift64' );
		}

		// Default error message for invalid numbers.
		return __( 'Please enter a valid phone number.', 'verify-phone-number-shift64' );
	}
}
