<?php
/**
 * Phone number formatter using libphonenumber.
 *
 * @package Shift64\SmartPhoneValidation\Formatter
 */

declare(strict_types=1);

namespace Shift64\SmartPhoneValidation\Formatter;

use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Shift64\SmartPhoneValidation\Admin\Settings;

/**
 * Formats phone numbers according to various standards.
 */
class PhoneFormatter {

	/**
	 * Format constant for E.164 format.
	 *
	 * @var string
	 */
	const FORMAT_E164 = 'E164';

	/**
	 * Format constant for international format.
	 *
	 * @var string
	 */
	const FORMAT_INTERNATIONAL = 'INTERNATIONAL';

	/**
	 * Format constant for national format.
	 *
	 * @var string
	 */
	const FORMAT_NATIONAL = 'NATIONAL';

	/**
	 * Format a phone number to E.164 format (e.g., +48224100500).
	 *
	 * @param PhoneNumber $phone_number The parsed phone number object.
	 * @return string The formatted phone number.
	 */
	public static function to_e164( PhoneNumber $phone_number ): string {
		$util = PhoneNumberUtil::getInstance();
		return $util->format( $phone_number, PhoneNumberFormat::E164 );
	}

	/**
	 * Format a phone number to international format (e.g., +48 22 410 05 00).
	 *
	 * @param PhoneNumber $phone_number The parsed phone number object.
	 * @return string The formatted phone number.
	 */
	public static function to_international( PhoneNumber $phone_number ): string {
		$util = PhoneNumberUtil::getInstance();
		return $util->format( $phone_number, PhoneNumberFormat::INTERNATIONAL );
	}

	/**
	 * Format a phone number to national format (e.g., 22 410 05 00).
	 *
	 * @param PhoneNumber $phone_number The parsed phone number object.
	 * @return string The formatted phone number.
	 */
	public static function to_national( PhoneNumber $phone_number ): string {
		$util = PhoneNumberUtil::getInstance();
		return $util->format( $phone_number, PhoneNumberFormat::NATIONAL );
	}

	/**
	 * Format a phone number using the format from plugin settings.
	 *
	 * @param PhoneNumber $phone_number The parsed phone number object.
	 * @return string The formatted phone number.
	 */
	public static function format( PhoneNumber $phone_number ): string {
		$output_format = Settings::get_output_format();

		return self::format_to( $phone_number, $output_format );
	}

	/**
	 * Format a phone number to a specific format.
	 *
	 * @param PhoneNumber $phone_number The parsed phone number object.
	 * @param string      $format       The format to use (E164, INTERNATIONAL, NATIONAL).
	 * @return string The formatted phone number.
	 */
	public static function format_to( PhoneNumber $phone_number, string $format ): string {
		switch ( $format ) {
			case self::FORMAT_E164:
				return self::to_e164( $phone_number );

			case self::FORMAT_INTERNATIONAL:
				return self::to_international( $phone_number );

			case self::FORMAT_NATIONAL:
				return self::to_national( $phone_number );

			default:
				// Default to E.164 for unknown formats.
				return self::to_e164( $phone_number );
		}
	}
}
