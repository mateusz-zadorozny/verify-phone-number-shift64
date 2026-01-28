<?php
/**
 * Block checkout phone validation for WooCommerce Store API.
 *
 * @package Shift64\SmartPhoneValidation\Checkout
 */

declare(strict_types=1);

namespace Shift64\SmartPhoneValidation\Checkout;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Shift64\SmartPhoneValidation\Admin\Settings;
use Shift64\SmartPhoneValidation\Formatter\PhoneFormatter;
use Shift64\SmartPhoneValidation\Validation\PhoneValidator;
use WC_Order;

/**
 * Handles phone validation and formatting for WooCommerce block checkout (Store API).
 */
class BlockCheckoutValidator {

	/**
	 * Initialize block checkout validation hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Only register if Store API is available (WooCommerce 5.3+).
		if ( ! class_exists( 'Automattic\WooCommerce\StoreApi\Exceptions\RouteException' ) ) {
			return;
		}

		// Validate phone numbers when order is processed via Store API.
		add_action( 'woocommerce_store_api_checkout_order_processed', array( self::class, 'validate_phones' ), 10, 1 );
	}

	/**
	 * Validate billing and shipping phone numbers during block checkout.
	 *
	 * @param WC_Order $order The order object.
	 * @return void
	 * @throws RouteException When phone validation fails.
	 */
	public static function validate_phones( WC_Order $order ): void {
		// Only validate if plugin is enabled.
		if ( ! Settings::is_validation_enabled() ) {
			return;
		}

		// Ensure translations are loaded for REST API requests.
		self::load_textdomain();

		// Validate billing phone.
		$billing_phone = $order->get_billing_phone();
		if ( ! empty( $billing_phone ) ) {
			$billing_country = $order->get_billing_country();
			$country_code    = ! empty( $billing_country ) ? $billing_country : null;

			$result = PhoneValidator::validate( $billing_phone, $country_code );

			if ( ! $result->is_valid() ) {
				$error_message = self::get_user_friendly_error( $result->get_error_message(), 'billing' );
				throw new RouteException(
					'invalid_billing_phone',
					$error_message,
					400,
					array(
						'field' => 'billing_phone',
					)
				);
			}

			// Format billing phone if enabled.
			if ( Settings::is_format_on_save_enabled() ) {
				$formatted = PhoneFormatter::format( $result->get_phone_number() );
				$order->set_billing_phone( $formatted );
			}
		}

		// Validate shipping phone (only if filled - it's optional).
		$shipping_phone = $order->get_shipping_phone();
		if ( ! empty( $shipping_phone ) ) {
			$shipping_country = $order->get_shipping_country();
			$country_code     = ! empty( $shipping_country ) ? $shipping_country : null;

			$result = PhoneValidator::validate( $shipping_phone, $country_code );

			if ( ! $result->is_valid() ) {
				$error_message = self::get_user_friendly_error( $result->get_error_message(), 'shipping' );
				throw new RouteException(
					'invalid_shipping_phone',
					$error_message,
					400,
					array(
						'field' => 'shipping_phone',
					)
				);
			}

			// Format shipping phone if enabled.
			if ( Settings::is_format_on_save_enabled() ) {
				$formatted = PhoneFormatter::format( $result->get_phone_number() );
				$order->set_shipping_phone( $formatted );
			}
		}

		// Save the order if phones were formatted.
		if ( Settings::is_format_on_save_enabled() && ( ! empty( $billing_phone ) || ! empty( $shipping_phone ) ) ) {
			$order->save();
		}
	}

	/**
	 * Load plugin text domain for REST API requests.
	 *
	 * @return void
	 */
	private static function load_textdomain(): void {
		// Get the locale - try multiple methods for multilingual compatibility.
		$locale = self::get_current_locale();

		// Switch to the correct locale if needed.
		if ( function_exists( 'switch_to_locale' ) && $locale && $locale !== get_locale() ) {
			switch_to_locale( $locale );
		}

		// Unload and reload translations to ensure correct locale is used.
		unload_textdomain( 'verify-phone-number-shift64' );

		// Load translations.
		load_plugin_textdomain(
			'verify-phone-number-shift64',
			false,
			dirname( plugin_basename( SHIFT64_PHONE_VALIDATION_FILE ) ) . '/languages'
		);
	}

	/**
	 * Get current locale with multilingual plugin support.
	 *
	 * @return string The current locale.
	 */
	private static function get_current_locale(): string {
		// Language code to locale mapping.
		$locale_map = array(
			'pl' => 'pl_PL',
			'en' => 'en_US',
			'de' => 'de_DE',
			'fr' => 'fr_FR',
			'es' => 'es_ES',
			'it' => 'it_IT',
			'nl' => 'nl_NL',
			'pt' => 'pt_PT',
			'ru' => 'ru_RU',
		);

		// Try to detect language from HTTP_REFERER (most reliable for REST API).
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$referer = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';
		if ( $referer && preg_match( '#/([a-z]{2})(/|$|\?)#', $referer, $matches ) ) {
			$lang_code = $matches[1];
			if ( isset( $locale_map[ $lang_code ] ) ) {
				return $locale_map[ $lang_code ];
			}
		}

		// Try Polylang.
		if ( function_exists( 'pll_current_language' ) ) {
			$pll_locale = pll_current_language( 'locale' );
			if ( $pll_locale ) {
				return $pll_locale;
			}
		}

		// Try WPML.
		if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
			$wpml_lang = apply_filters( 'wpml_current_language', null );
			if ( $wpml_lang && isset( $locale_map[ $wpml_lang ] ) ) {
				return $locale_map[ $wpml_lang ];
			}
		}

		// Fallback to determine_locale().
		return determine_locale();
	}

	/**
	 * Get user-friendly error message for Store API response.
	 *
	 * @param string|null $internal_message The internal validation error message.
	 * @param string      $field_type       The field type ('billing' or 'shipping').
	 * @return string The user-friendly error message.
	 */
	private static function get_user_friendly_error( ?string $internal_message, string $field_type ): string {
		$field_label = 'billing' === $field_type
			? __( 'Billing phone', 'verify-phone-number-shift64' )
			: __( 'Shipping phone', 'verify-phone-number-shift64' );

		// Check if the error is about missing international prefix.
		if ( null !== $internal_message && false !== strpos( $internal_message, 'international prefix' ) ) {
			/* translators: %s: field label (Billing phone or Shipping phone) */
			return sprintf( __( '%s must contain country prefix.', 'verify-phone-number-shift64' ), $field_label );
		}

		/* translators: %s: field label (Billing phone or Shipping phone) */
		return sprintf( __( '%s is not a valid phone number.', 'verify-phone-number-shift64' ), $field_label );
	}
}
