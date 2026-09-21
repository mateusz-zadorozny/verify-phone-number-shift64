<?php

namespace Shift64\SmartPhoneValidation\Tests\Unit;

use libphonenumber\PhoneNumber;
use Shift64\SmartPhoneValidation\Validation\ValidationResult;

class ValidationResultTest extends TestCase {

	public function test_success(): void {
		$number = new PhoneNumber();
		$result = ValidationResult::success( $number );

		$this->assertTrue( $result->is_valid() );
		$this->assertSame( $number, $result->get_phone_number() );
		$this->assertNull( $result->get_error_code() );
		$this->assertNull( $result->get_error_message() );
	}

	public function test_failure(): void {
		$result = ValidationResult::failure( ValidationResult::ERROR_TOO_SHORT, 'Too short.' );

		$this->assertFalse( $result->is_valid() );
		$this->assertSame( 'too_short', $result->get_error_code() );
		$this->assertSame( 'Too short.', $result->get_error_message() );
		$this->assertNull( $result->get_phone_number() );
	}
}
