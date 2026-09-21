<?php

namespace Shift64\SmartPhoneValidation\Tests\Unit;

use Shift64\SmartPhoneValidation\Formatter\PhoneFormatter;
use Shift64\SmartPhoneValidation\Validation\PhoneValidator;

class PhoneFormatterTest extends TestCase {

	private function number() {
		return PhoneValidator::validate( '600 100 200', 'PL' )->get_phone_number();
	}

	public function test_explicit_formats(): void {
		$this->assertSame( '+48600100200', PhoneFormatter::format_to( $this->number(), PhoneFormatter::FORMAT_E164 ) );
		$this->assertSame( '+48 600 100 200', PhoneFormatter::format_to( $this->number(), PhoneFormatter::FORMAT_INTERNATIONAL ) );
		$this->assertSame( '600 100 200', PhoneFormatter::format_to( $this->number(), PhoneFormatter::FORMAT_NATIONAL ) );
	}

	public function test_unknown_format_falls_back_to_e164(): void {
		$this->assertSame( '+48600100200', PhoneFormatter::format_to( $this->number(), 'whatever' ) );
	}

	public function test_format_uses_setting_and_defaults_to_e164(): void {
		$this->assertSame( '+48600100200', PhoneFormatter::format( $this->number() ) );

		$this->set_option( 'output_format', 'NATIONAL' );
		$this->assertSame( '600 100 200', PhoneFormatter::format( $this->number() ) );
	}
}
