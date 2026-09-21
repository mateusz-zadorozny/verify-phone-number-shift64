<?php

namespace Shift64\SmartPhoneValidation\Tests\Unit;

use Shift64\SmartPhoneValidation\Checkout\ErrorMessages;
use Shift64\SmartPhoneValidation\Validation\PhoneValidator;
use Shift64\SmartPhoneValidation\Validation\ValidationResult;

class ErrorMessagesTest extends TestCase {

	/**
	 * @dataProvider classic_messages
	 */
	public function test_classic_checkout_messages( ?string $code, string $field, string $expected_en, string $expected_pl ): void {
		$this->assertSame( $expected_en, ErrorMessages::for_classic_checkout( $code, $field ) );

		$this->use_polish();
		$this->assertSame( $expected_pl, ErrorMessages::for_classic_checkout( $code, $field ) );
	}

	public function classic_messages(): array {
		$prefix = ValidationResult::ERROR_MISSING_INTERNATIONAL_PREFIX;

		return array(
			'billing prefix'   => array( $prefix, ErrorMessages::FIELD_BILLING, 'Phone number must contain country prefix.', 'Numer telefonu musi zawierać prefiks kraju.' ),
			'billing invalid'  => array( ValidationResult::ERROR_INVALID_NUMBER, ErrorMessages::FIELD_BILLING, 'Please enter a valid phone number.', 'Proszę podać prawidłowy numer telefonu.' ),
			'billing too long' => array( ValidationResult::ERROR_TOO_LONG, ErrorMessages::FIELD_BILLING, 'Please enter a valid phone number.', 'Proszę podać prawidłowy numer telefonu.' ),
			'billing null'     => array( null, ErrorMessages::FIELD_BILLING, 'Please enter a valid phone number.', 'Proszę podać prawidłowy numer telefonu.' ),
			'shipping prefix'  => array( $prefix, ErrorMessages::FIELD_SHIPPING, 'Shipping phone number must contain country prefix.', 'Numer telefonu do wysyłki musi zawierać prefiks kraju.' ),
			'shipping invalid' => array( ValidationResult::ERROR_INVALID_NUMBER, ErrorMessages::FIELD_SHIPPING, 'Please enter a valid shipping phone number.', 'Proszę podać prawidłowy numer telefonu do wysyłki.' ),
		);
	}

	/**
	 * @dataProvider block_messages
	 */
	public function test_block_checkout_messages( ?string $code, string $field, string $expected_en, string $expected_pl ): void {
		$this->assertSame( $expected_en, ErrorMessages::for_block_checkout( $code, $field ) );

		$this->use_polish();
		$this->assertSame( $expected_pl, ErrorMessages::for_block_checkout( $code, $field ) );
	}

	public function block_messages(): array {
		$prefix = ValidationResult::ERROR_MISSING_INTERNATIONAL_PREFIX;

		return array(
			'billing prefix'   => array( $prefix, ErrorMessages::FIELD_BILLING, 'Billing phone must contain country prefix.', 'Telefon do płatności musi zawierać prefiks kraju.' ),
			'billing invalid'  => array( ValidationResult::ERROR_INVALID_NUMBER, ErrorMessages::FIELD_BILLING, 'Billing phone is not a valid phone number.', 'Telefon do płatności nie jest prawidłowym numerem telefonu.' ),
			'shipping prefix'  => array( $prefix, ErrorMessages::FIELD_SHIPPING, 'Shipping phone must contain country prefix.', 'Telefon do wysyłki musi zawierać prefiks kraju.' ),
			'shipping invalid' => array( ValidationResult::ERROR_INVALID_NUMBER, ErrorMessages::FIELD_SHIPPING, 'Shipping phone is not a valid phone number.', 'Telefon do wysyłki nie jest prawidłowym numerem telefonu.' ),
		);
	}

	/**
	 * The acceptance scenario of issue #2, end to end: Polish site,
	 * "International only" mode, customer types a number without "+".
	 */
	public function test_polish_customer_gets_prefix_message_not_generic_one(): void {
		$this->set_option( 'validation_mode', 'international_only' );
		$this->use_polish();

		$result = PhoneValidator::validate( '600100200', 'PL' );

		$this->assertSame(
			'Numer telefonu musi zawierać prefiks kraju.',
			ErrorMessages::for_classic_checkout( $result->get_error_code(), ErrorMessages::FIELD_BILLING )
		);
	}

	/**
	 * Themes often map a notice to a field by keyword ("phone" / "telefon").
	 * Every customer-facing message must therefore name the phone (issue #5).
	 *
	 * @dataProvider all_codes_and_fields
	 */
	public function test_every_message_mentions_the_phone( ?string $code, string $field ): void {
		$this->assertStringContainsStringIgnoringCase( 'phone', ErrorMessages::for_classic_checkout( $code, $field ) );
		$this->assertStringContainsStringIgnoringCase( 'phone', ErrorMessages::for_block_checkout( $code, $field ) );

		$this->use_polish();
		$this->assertStringContainsStringIgnoringCase( 'telefon', ErrorMessages::for_classic_checkout( $code, $field ) );
		$this->assertStringContainsStringIgnoringCase( 'telefon', ErrorMessages::for_block_checkout( $code, $field ) );
	}

	public function all_codes_and_fields(): array {
		$cases = array();
		foreach ( array( ErrorMessages::FIELD_BILLING, ErrorMessages::FIELD_SHIPPING ) as $field ) {
			foreach ( array( ValidationResult::ERROR_MISSING_INTERNATIONAL_PREFIX, ValidationResult::ERROR_INVALID_NUMBER, null ) as $code ) {
				$cases[ $field . ' / ' . var_export( $code, true ) ] = array( $code, $field );
			}
		}
		return $cases;
	}

	public function test_block_checkout_message_list_matches_single_messages(): void {
		$this->use_polish();

		$this->assertSame(
			array(
				'billing'  => array( 'Telefon do płatności musi zawierać prefiks kraju.', 'Telefon do płatności nie jest prawidłowym numerem telefonu.' ),
				'shipping' => array( 'Telefon do wysyłki musi zawierać prefiks kraju.', 'Telefon do wysyłki nie jest prawidłowym numerem telefonu.' ),
			),
			ErrorMessages::all_for_block_checkout()
		);
	}
}
