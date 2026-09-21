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
	 * Removes whitespace (including non-breaking spaces pasted from documents),
	 * dashes, parentheses, dots and slashes.
	 * Replaces '00' prefix with '+' for international format.
	 * Trims the input.
	 *
	 * @param string $phone_number Raw phone number input.
	 * @return string Normalized phone number.
	 */
	public static function normalize( string $phone_number ): string {
		// Trim whitespace from beginning and end.
		$normalized = trim( $phone_number );

		// Remove separators. The "u" flag makes \s cover non-breaking spaces, but it returns
		// null for invalid UTF-8 - fall back to the byte-wise pattern in that case.
		$stripped = preg_replace( '/[\s\-\(\)\.\/]+/u', '', $normalized );
		if ( null === $stripped ) {
			$stripped = preg_replace( '/[\s\-\(\)\.\/]+/', '', $normalized );
		}
		$normalized = (string) $stripped;

		// Replace '00' prefix with '+' for international format.
		if ( 0 === strpos( $normalized, '00' ) ) {
			$normalized = '+' . substr( $normalized, 2 );
		}

		return $normalized;
	}
}
