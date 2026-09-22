<?php
/**
 * Whitespace clean-up of posted phone numbers, ahead of WooCommerce's own check.
 *
 * @package Shift64\SmartPhoneValidation\Checkout
 */

declare(strict_types=1);

namespace Shift64\SmartPhoneValidation\Checkout;

use Shift64\SmartPhoneValidation\Admin\Settings;
use Shift64\SmartPhoneValidation\Validation\Normalizer;
use WP_REST_Request;

/**
 * Collapses Unicode whitespace in posted phone numbers before WooCommerce validates them.
 *
 * WooCommerce checks every phone field with WC_Validation::is_phone(), whose pattern only
 * knows ASCII whitespace. A number with a non-breaking space (pasted from Word, Outlook or
 * a PDF) is rejected with WooCommerce's own message before the plugin's validators run,
 * so the plugin's normalizer and error message never apply. Both checkouts check early:
 *
 * - classic: WC_Checkout::validate_posted_data(), right after get_posted_data() applied
 *   the woocommerce_process_checkout_field_{key} filters,
 * - Store API: the billing_address / shipping_address argument validators, run by
 *   WP_REST_Server::dispatch() before any route callback or rest_request_before_callbacks.
 *
 * Only the kind of space changes, separators stay, so the value is still readable when the
 * plugin later leaves it alone (shift64_phone_validation_should_validate, formatting off).
 */
class WhitespaceFilter {

	/**
	 * Route prefix of the Store API, whose requests carry the checkout addresses.
	 *
	 * @var string
	 */
	const STORE_API_ROUTE_PREFIX = '/wc/store/';

	/**
	 * Store API request parameters that may hold a phone number.
	 *
	 * @var string[]
	 */
	const STORE_API_ADDRESS_PARAMS = array( 'billing_address', 'shipping_address' );

	/**
	 * Register the filters for both checkouts.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Classic checkout: applied in WC_Checkout::get_posted_data() after wc_clean(), before validation.
		add_filter( 'woocommerce_process_checkout_field_billing_phone', array( self::class, 'filter_posted_phone' ) );
		add_filter( 'woocommerce_process_checkout_field_shipping_phone', array( self::class, 'filter_posted_phone' ) );

		// Store API: the only hook that runs before the REST server validates the request arguments.
		add_filter( 'rest_pre_dispatch', array( self::class, 'filter_store_api_request' ), 10, 3 );
	}

	/**
	 * Clean up a phone field posted to the classic checkout.
	 *
	 * @param mixed $value Field value after wc_clean().
	 * @return mixed
	 */
	public static function filter_posted_phone( $value ) {
		if ( ! Settings::is_validation_enabled() ) {
			return $value;
		}

		return Normalizer::collapse_whitespace( $value );
	}

	/**
	 * Clean up the phone numbers in the addresses of a Store API request.
	 *
	 * The request is changed in place; the dispatch result is always returned untouched.
	 *
	 * @param mixed           $result  Response to send instead of dispatching, null to dispatch normally.
	 * @param \WP_REST_Server $server  REST server instance.
	 * @param WP_REST_Request $request The request about to be dispatched.
	 * @return mixed
	 */
	public static function filter_store_api_request( $result, $server, $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed -- Filter signature.
		if ( null !== $result || ! $request instanceof WP_REST_Request ) {
			return $result;
		}

		if ( ! str_starts_with( (string) $request->get_route(), self::STORE_API_ROUTE_PREFIX ) || ! Settings::is_validation_enabled() ) {
			return $result;
		}

		foreach ( self::STORE_API_ADDRESS_PARAMS as $param ) {
			$address = $request->get_param( $param );

			if ( ! is_array( $address ) || ! isset( $address['phone'] ) ) {
				continue;
			}

			$phone = Normalizer::collapse_whitespace( $address['phone'] );

			if ( $phone !== $address['phone'] ) {
				$address['phone'] = $phone;
				$request->set_param( $param, $address );
			}
		}

		return $result;
	}
}
