<?php
/**
 * Phone number normalizer for input cleaning.
 *
 * @package Shift64\SmartPhoneValidation\Validation
 */

declare(strict_types=1);

namespace Shift64\SmartPhoneValidation\Validation;

/**
 * Normalizes phone number input before parsing.
 */
class Normalizer {

	/**
	 * Normalize a phone number string.
	 *
	 * Removes whitespace, dashes, parentheses, and dots.
	 * Replaces '00' prefix with '+' for international format.
	 * Trims the input.
	 *
	 * @param string $phone_number Raw phone number input.
	 * @return string Normalized phone number.
	 */
	public static function normalize( string $phone_number ): string {
		// Trim whitespace from beginning and end.
		$normalized = trim( $phone_number );

		// Remove whitespace, dashes, parentheses, and dots.
		$normalized = preg_replace( '/[\s\-\(\)\.]+/', '', $normalized );

		// Replace '00' prefix with '+' for international format.
		if ( 0 === strpos( $normalized, '00' ) ) {
			$normalized = '+' . substr( $normalized, 2 );
		}

		return $normalized;
	}
}
