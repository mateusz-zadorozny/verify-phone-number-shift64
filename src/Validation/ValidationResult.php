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
	 * Constructor.
	 *
	 * @param bool             $is_valid      Whether validation succeeded.
	 * @param string|null      $error_message Error message if validation failed.
	 * @param PhoneNumber|null $phone_number  Parsed phone number if validation succeeded.
	 */
	public function __construct( bool $is_valid, ?string $error_message = null, ?PhoneNumber $phone_number = null ) {
		$this->is_valid      = $is_valid;
		$this->error_message = $error_message;
		$this->phone_number  = $phone_number;
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
	 * @param string $error_message The error message.
	 * @return self
	 */
	public static function failure( string $error_message ): self {
		return new self( false, $error_message, null );
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
