<?php

namespace Shift64\SmartPhoneValidation\Tests\Unit;

use Shift64\SmartPhoneValidation\Checkout\BillingPhoneValidator;
use Shift64\SmartPhoneValidation\Checkout\ShippingPhoneValidator;

/**
 * Runs both classic checkout validators the way WooCommerce does:
 * same posted data, same WP_Error object.
 */
class ClassicCheckoutValidatorsTest extends TestCase {

	private function validate( array $data ): \WP_Error {
		$errors = new \WP_Error();

		BillingPhoneValidator::validate_billing_phone( $data, $errors );
		ShippingPhoneValidator::validate_shipping_phone( $data, $errors );

		return $errors;
	}

	/**
	 * Regression: with "ship to a different address" off, WooCommerce copies
	 * billing_phone into shipping_phone. One wrong number must give one notice.
	 */
	public function test_copied_shipping_phone_does_not_duplicate_the_notice(): void {
		$errors = $this->validate(
			array(
				'billing_phone'             => '1234',
				'billing_country'           => 'PL',
				'ship_to_different_address' => false,
				'shipping_phone'            => '1234', // Copy made by WC_Checkout::get_posted_data().
				'shipping_country'          => 'PL',
			)
		);

		$this->assertSame( array( 'billing_phone_validation' ), array_keys( $errors->errors ) );
		$this->assertSame( array( 'id' => 'billing_phone' ), $errors->error_data['billing_phone_validation'] );
	}

	public function test_real_shipping_phone_is_validated_when_shipping_to_a_different_address(): void {
		$errors = $this->validate(
			array(
				'billing_phone'             => '600 100 200',
				'billing_country'           => 'PL',
				'ship_to_different_address' => true,
				'shipping_phone'            => '1234',
				'shipping_country'          => 'PL',
			)
		);

		$this->assertSame( array( 'shipping_phone_validation' ), array_keys( $errors->errors ) );
		$this->assertSame( array( 'id' => 'shipping_phone' ), $errors->error_data['shipping_phone_validation'] );
	}

	public function test_empty_shipping_phone_is_optional(): void {
		$errors = $this->validate(
			array(
				'billing_phone'             => '600 100 200',
				'billing_country'           => 'PL',
				'ship_to_different_address' => true,
				'shipping_phone'            => '',
			)
		);

		$this->assertSame( array(), $errors->errors );
	}

	public function test_nothing_is_validated_when_the_plugin_is_disabled(): void {
		$this->set_option( 'enabled', 'no' );

		$errors = $this->validate(
			array(
				'billing_phone'             => '1234',
				'ship_to_different_address' => true,
				'shipping_phone'            => '1234',
			)
		);

		$this->assertSame( array(), $errors->errors );
	}
}
