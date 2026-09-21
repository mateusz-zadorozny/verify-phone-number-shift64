<?php
/**
 * User-facing checkout error messages.
 *
 * @package Shift64\SmartPhoneValidation\Checkout
 */

declare(strict_types=1);

namespace Shift64\SmartPhoneValidation\Checkout;

use Shift64\SmartPhoneValidation\Validation\ValidationResult;

/**
 * Maps validation error codes to messages shown to the customer.
 *
 * Single place for the code → message mapping, shared by classic and block checkout.
 * The mapping is keyed on ValidationResult::ERROR_* codes, so it works in every locale.
 */
class ErrorMessages {

	/**
	 * Billing phone field.
	 *
	 * @var string
	 */
	const FIELD_BILLING = 'billing';

	/**
	 * Shipping phone field.
	 *
	 * @var string
	 */
	const FIELD_SHIPPING = 'shipping';

	/**
	 * Get the message for classic checkout notices.
	 *
	 * @param string|null $error_code One of the ValidationResult::ERROR_* constants.
	 * @param string      $field      FIELD_BILLING or FIELD_SHIPPING.
	 * @return string The translated message.
	 */
	public static function for_classic_checkout( ?string $error_code, string $field ): string {
		$is_missing_prefix = ValidationResult::ERROR_MISSING_INTERNATIONAL_PREFIX === $error_code;

		if ( self::FIELD_SHIPPING === $field ) {
			return $is_missing_prefix
				? __( 'Shipping phone number must contain country prefix.', 'verify-phone-number-shift64' )
				: __( 'Please enter a valid shipping phone number.', 'verify-phone-number-shift64' );
		}

		return $is_missing_prefix
			? __( 'Phone number must contain country prefix.', 'verify-phone-number-shift64' )
			: __( 'Please enter a valid phone number.', 'verify-phone-number-shift64' );
	}

	/**
	 * All block checkout messages grouped by field, in the current locale.
	 *
	 * Handed to the block checkout script so it can recognise our notices without
	 * hardcoding any language: PHP stays the single source of the wording.
	 *
	 * @return array<string, string[]> Messages keyed by FIELD_BILLING / FIELD_SHIPPING.
	 */
	public static function all_for_block_checkout(): array {
		$messages = array();

		foreach ( array( self::FIELD_BILLING, self::FIELD_SHIPPING ) as $field ) {
			$messages[ $field ] = array(
				self::for_block_checkout( ValidationResult::ERROR_MISSING_INTERNATIONAL_PREFIX, $field ),
				self::for_block_checkout( ValidationResult::ERROR_INVALID_NUMBER, $field ),
			);
		}

		return $messages;
	}

	/**
	 * Get the message for block checkout (Store API) responses.
	 *
	 * Block checkout shows errors in a generic banner, so the message names the field.
	 *
	 * @param string|null $error_code One of the ValidationResult::ERROR_* constants.
	 * @param string      $field      FIELD_BILLING or FIELD_SHIPPING.
	 * @return string The translated message.
	 */
	public static function for_block_checkout( ?string $error_code, string $field ): string {
		$field_label = self::FIELD_SHIPPING === $field
			? __( 'Shipping phone', 'verify-phone-number-shift64' )
			: __( 'Billing phone', 'verify-phone-number-shift64' );

		if ( ValidationResult::ERROR_MISSING_INTERNATIONAL_PREFIX === $error_code ) {
			/* translators: %s: field label (Billing phone or Shipping phone) */
			return sprintf( __( '%s must contain country prefix.', 'verify-phone-number-shift64' ), $field_label );
		}

		/* translators: %s: field label (Billing phone or Shipping phone) */
		return sprintf( __( '%s is not a valid phone number.', 'verify-phone-number-shift64' ), $field_label );
	}
}
