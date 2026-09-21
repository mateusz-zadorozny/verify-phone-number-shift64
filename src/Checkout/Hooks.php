<?php
/**
 * Extension points for themes and integrations.
 *
 * @package Shift64\SmartPhoneValidation\Checkout
 */

declare(strict_types=1);

namespace Shift64\SmartPhoneValidation\Checkout;

use libphonenumber\PhoneNumber;
use Shift64\SmartPhoneValidation\Formatter\PhoneFormatter;

/**
 * Single place where the checkout filters are applied, shared by classic and block checkout.
 */
class Hooks {

	/**
	 * Whether a phone field should be validated (and formatted) in this request.
	 *
	 * @param string $field   ErrorMessages::FIELD_BILLING or ErrorMessages::FIELD_SHIPPING.
	 * @param mixed  $context Classic checkout: posted data array. Otherwise: the WC_Order.
	 * @return bool
	 */
	public static function should_validate( string $field, $context ): bool {
		/**
		 * Filters whether a phone field is validated and formatted.
		 *
		 * Return false to skip the plugin for specific flows (e.g. prefilled offers,
		 * orders placed by staff). Skipped fields are stored exactly as entered.
		 *
		 * @param bool   $validate Default true.
		 * @param string $field    'billing' or 'shipping'.
		 * @param mixed  $context  Posted checkout data (array) or WC_Order.
		 */
		return (bool) apply_filters( 'shift64_phone_validation_should_validate', true, $field, $context );
	}

	/**
	 * Format a valid number for storage on the order.
	 *
	 * @param PhoneNumber $phone_number The parsed phone number.
	 * @param string      $field        ErrorMessages::FIELD_BILLING or ErrorMessages::FIELD_SHIPPING.
	 * @param \WC_Order   $order        The order being saved.
	 * @return string
	 */
	public static function format_for_order( PhoneNumber $phone_number, string $field, $order ): string {
		$formatted = PhoneFormatter::format( $phone_number );

		/**
		 * Filters the phone number stored on the order.
		 *
		 * @param string      $formatted    Number in the format selected in settings.
		 * @param PhoneNumber $phone_number Parsed number - format it differently with PhoneFormatter::format_to().
		 * @param string      $field        'billing' or 'shipping'.
		 * @param \WC_Order   $order        The order being saved.
		 */
		return (string) apply_filters( 'shift64_phone_validation_formatted_phone', $formatted, $phone_number, $field, $order );
	}
}
