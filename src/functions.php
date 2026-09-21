<?php
/**
 * Public helper functions for themes and integrations.
 *
 * Guard calls with function_exists() so the caller keeps working without the plugin.
 *
 * @package Shift64\SmartPhoneValidation
 */

declare(strict_types=1);

namespace Shift64\SmartPhoneValidation;

use Shift64\SmartPhoneValidation\Formatter\PhoneFormatter;
use Shift64\SmartPhoneValidation\Validation\PhoneValidator;

/**
 * Validate a raw phone number and return it in the requested format.
 *
 * Lets an integration use a different format than the one stored on the order,
 * e.g. the order keeps E.164 while an export needs the national format.
 *
 * @param string      $raw_phone    Phone number as entered / stored.
 * @param string|null $country_code ISO-2 country used for numbers without '+'. Null = default country from settings.
 * @param string      $format       PhoneFormatter::FORMAT_E164, FORMAT_INTERNATIONAL or FORMAT_NATIONAL.
 * @return string|null Formatted number, or null when the number is not valid.
 */
function format_phone( string $raw_phone, ?string $country_code = null, string $format = PhoneFormatter::FORMAT_E164 ): ?string {
	$result = PhoneValidator::validate( $raw_phone, $country_code );

	if ( ! $result->is_valid() ) {
		return null;
	}

	return PhoneFormatter::format_to( $result->get_phone_number(), $format );
}
