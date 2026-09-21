<?php

namespace Shift64\SmartPhoneValidation\Tests\Unit;

use Shift64\SmartPhoneValidation\Validation\PhoneValidator;
use Shift64\SmartPhoneValidation\Validation\ValidationResult;

class PhoneValidatorTest extends TestCase {

	public function test_valid_national_number_with_country_context(): void {
		$result = PhoneValidator::validate( '600 100 200', 'PL' );

		$this->assertTrue( $result->is_valid() );
		$this->assertNull( $result->get_error_code() );
		$this->assertNull( $result->get_error_message() );
		$this->assertSame( 48, $result->get_phone_number()->getCountryCode() );
		$this->assertSame( '600100200', $result->get_phone_number()->getNationalNumber() );
	}

	public function test_valid_international_number_ignores_country_context(): void {
		$result = PhoneValidator::validate( '+48 600 100 200', 'DE' );

		$this->assertTrue( $result->is_valid() );
		$this->assertSame( 48, $result->get_phone_number()->getCountryCode() );
	}

	/**
	 * Characterization test (see issue #3): a number without "+" is read in the
	 * context of the given country. A Polish mobile typed next to a German
	 * address is a well-formed German number, so it passes as +49, not +48.
	 */
	public function test_national_number_is_interpreted_in_the_given_country(): void {
		$result = PhoneValidator::validate( '600100200', 'DE' );

		$this->assertTrue( $result->is_valid() );
		$this->assertSame( 49, $result->get_phone_number()->getCountryCode() );
	}

	public function test_double_zero_prefix_is_treated_as_international(): void {
		$this->assertTrue( PhoneValidator::validate( '0048600100200', 'DE' )->is_valid() );
	}

	public function test_default_country_setting_is_used_when_no_country_given(): void {
		$this->set_option( 'default_country', 'PL' );
		$this->assertTrue( PhoneValidator::validate( '600100200' )->is_valid() );

		$this->set_option( 'default_country', 'US' );
		$this->assertFalse( PhoneValidator::validate( '600100200' )->is_valid() );
	}

	public function test_default_country_falls_back_to_pl_without_saved_option(): void {
		$this->assertTrue( PhoneValidator::validate( '600100200' )->is_valid() );
	}

	/**
	 * @dataProvider failures
	 */
	public function test_failure_codes( string $input, ?string $country, string $expected_code ): void {
		$result = PhoneValidator::validate( $input, $country );

		$this->assertFalse( $result->is_valid() );
		$this->assertSame( $expected_code, $result->get_error_code() );
		$this->assertNotSame( '', (string) $result->get_error_message() );
		$this->assertNull( $result->get_phone_number() );
	}

	public function failures(): array {
		return array(
			'empty'                  => array( '', 'PL', ValidationResult::ERROR_EMPTY ),
			'only separators'        => array( ' - ', 'PL', ValidationResult::ERROR_EMPTY ),
			'not a number'           => array( 'abc', 'PL', ValidationResult::ERROR_NOT_A_NUMBER ),
			'unknown calling code'   => array( '+999123456789', 'PL', ValidationResult::ERROR_INVALID_COUNTRY_CODE ),
			'too long'               => array( '+48600100200600100200', 'PL', ValidationResult::ERROR_TOO_LONG ),
			'parses but not valid'   => array( '111 111 111', 'PL', ValidationResult::ERROR_INVALID_NUMBER ),
			'PL number, GB context'  => array( '600100200', 'GB', ValidationResult::ERROR_INVALID_NUMBER ),
		);
	}

	public function test_international_only_mode_rejects_number_without_plus(): void {
		$this->set_option( 'validation_mode', 'international_only' );

		$result = PhoneValidator::validate( '600100200', 'PL' );

		$this->assertFalse( $result->is_valid() );
		$this->assertSame( ValidationResult::ERROR_MISSING_INTERNATIONAL_PREFIX, $result->get_error_code() );
	}

	public function test_international_only_mode_accepts_number_with_plus(): void {
		$this->set_option( 'validation_mode', 'international_only' );

		$this->assertTrue( PhoneValidator::validate( '+48600100200', 'PL' )->is_valid() );
	}

	/**
	 * Regression for issue #2: the code must not depend on the active language.
	 */
	public function test_error_code_is_the_same_in_polish(): void {
		$this->set_option( 'validation_mode', 'international_only' );
		$this->use_polish();

		$result = PhoneValidator::validate( '600100200', 'PL' );

		$this->assertSame( ValidationResult::ERROR_MISSING_INTERNATIONAL_PREFIX, $result->get_error_code() );
		$this->assertSame( 'Numer telefonu musi zawierać międzynarodowy prefiks (+).', $result->get_error_message() );
	}
}
