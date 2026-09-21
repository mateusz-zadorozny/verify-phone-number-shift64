<?php
/**
 * Validation result object for phone number validation.
 *
 * @package Shift64\SmartPhoneValidation\Validation
 */

declare(strict_types=1);

namespace Shift64\SmartPhoneValidation\Validation;

use libphonenumber\PhoneNumber;

/**
 * Represents the result of a phone number validation.
 */
class ValidationResult {

	/**
	 * Error code: empty input.
	 *
	 * @var string
	 */
	const ERROR_EMPTY = 'empty';

	/**
	 * Error code: number without '+' in "International only" mode.
	 *
	 * @var string
	 */
	const ERROR_MISSING_INTERNATIONAL_PREFIX = 'missing_international_prefix';

	/**
	 * Error code: parsed correctly but not a valid number for its region.
	 *
	 * @var string
	 */
	const ERROR_INVALID_NUMBER = 'invalid_number';

	/**
	 * Error code: unknown country calling code.
	 *
	 * @var string
	 */
	const ERROR_INVALID_COUNTRY_CODE = 'invalid_country_code';

	/**
	 * Error code: input is not a phone number at all.
	 *
	 * @var string
	 */
	const ERROR_NOT_A_NUMBER = 'not_a_number';

	/**
	 * Error code: number is too short.
	 *
	 * @var string
	 */
	const ERROR_TOO_SHORT = 'too_short';

	/**
	 * Error code: number is too long.
	 *
	 * @var string
	 */
	const ERROR_TOO_LONG = 'too_long';

	/**
	 * Error code: any other parse failure.
	 *
	 * @var string
	 */
	const ERROR_PARSE_ERROR = 'parse_error';

	/**
	 * Whether the validation was successful.
	 *
	 * @var bool
	 */
	private bool $is_valid;

	/**
	 * Error message if validation failed.
	 *
	 * @var string|null
	 */
	private ?string $error_message;

	/**
	 * Parsed phone number object if validation succeeded.
	 *
	 * @var PhoneNumber|null
	 */
	private ?PhoneNumber $phone_number;

	/**
	 * Machine-readable error code if validation failed (one of the ERROR_* constants).
	 *
	 * Logic must branch on this code, never on the translated message.
	 *
	 * @var string|null
	 */
	private ?string $error_code;

	/**
	 * Constructor.
	 *
	 * @param bool             $is_valid      Whether validation succeeded.
	 * @param string|null      $error_message Error message if validation failed.
	 * @param PhoneNumber|null $phone_number  Parsed phone number if validation succeeded.
	 * @param string|null      $error_code    Error code if validation failed.
	 */
	public function __construct( bool $is_valid, ?string $error_message = null, ?PhoneNumber $phone_number = null, ?string $error_code = null ) {
		$this->is_valid      = $is_valid;
		$this->error_message = $error_message;
		$this->phone_number  = $phone_number;
		$this->error_code    = $error_code;
	}

	/**
	 * Create a successful validation result.
	 *
	 * @param PhoneNumber $phone_number The parsed phone number.
	 * @return self
	 */
	public static function success( PhoneNumber $phone_number ): self {
		return new self( true, null, $phone_number );
	}

	/**
	 * Create a failed validation result.
	 *
	 * @param string $error_code    One of the ERROR_* constants.
	 * @param string $error_message The (translated) error message.
	 * @return self
	 */
	public static function failure( string $error_code, string $error_message ): self {
		return new self( false, $error_message, null, $error_code );
	}

	/**
	 * Check if the validation was successful.
	 *
	 * @return bool
	 */
	public function is_valid(): bool {
		return $this->is_valid;
	}

	/**
	 * Get the machine-readable error code.
	 *
	 * @return string|null
	 */
	public function get_error_code(): ?string {
		return $this->error_code;
	}

	/**
	 * Get the error message.
	 *
	 * @return string|null
	 */
	public function get_error_message(): ?string {
		return $this->error_message;
	}

	/**
	 * Get the parsed phone number.
	 *
	 * @return PhoneNumber|null
	 */
	public function get_phone_number(): ?PhoneNumber {
		return $this->phone_number;
	}
}
