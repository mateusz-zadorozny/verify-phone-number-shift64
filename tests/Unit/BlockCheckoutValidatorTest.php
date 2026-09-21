<?php

namespace Shift64\SmartPhoneValidation\Tests\Unit;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Shift64\SmartPhoneValidation\Checkout\BlockCheckoutValidator;

class BlockCheckoutValidatorTest extends TestCase {

	public function test_invalid_billing_phone_rejects_the_place_order_request(): void {
		$order = new \WC_Order( array( 'billing_phone' => '1234' ) );

		try {
			BlockCheckoutValidator::validate_on_update_order( $order, new \WP_REST_Request( 'POST' ) );
			$this->fail( 'RouteException expected.' );
		} catch ( RouteException $e ) {
			$this->assertSame( 'invalid_billing_phone', $e->error_code );
			$this->assertSame( 400, $e->getCode() );
			$this->assertSame( 'Billing phone is not a valid phone number.', $e->getMessage() );
			$this->assertSame( array( 'field' => 'billing_phone', 'code' => 'invalid_number' ), $e->additional_data );
		}

		$this->assertSame( '1234', $order->data['billing_phone'] );
	}

	/**
	 * The hook also fires for PUT/PATCH while the customer is typing - a half-typed
	 * number must not be rejected (or reformatted under their fingers).
	 *
	 * @dataProvider non_post_methods
	 */
	public function test_form_interaction_requests_are_ignored( string $method ): void {
		$order = new \WC_Order( array( 'billing_phone' => '60' ) );

		BlockCheckoutValidator::validate_on_update_order( $order, new \WP_REST_Request( $method ) );

		$this->assertSame( '60', $order->data['billing_phone'] );
	}

	public function non_post_methods(): array {
		return array( array( 'PATCH' ), array( 'PUT' ) );
	}

	public function test_valid_phones_are_formatted_and_woocommerce_does_the_saving(): void {
		$order = new \WC_Order(
			array(
				'billing_phone'  => '600 100 200',
				'shipping_phone' => '0048 22 410 05 00',
			)
		);

		BlockCheckoutValidator::validate_on_update_order( $order, new \WP_REST_Request( 'POST' ) );

		$this->assertSame( '+48600100200', $order->data['billing_phone'] );
		$this->assertSame( '+48224100500', $order->data['shipping_phone'] );
		$this->assertSame( 0, $order->saves );
	}

	public function test_legacy_hook_saves_only_when_something_changed(): void {
		$changed = new \WC_Order( array( 'billing_phone' => '600 100 200' ) );
		BlockCheckoutValidator::validate_on_order_processed( $changed );
		$this->assertSame( 1, $changed->saves );

		$already_formatted = new \WC_Order( array( 'billing_phone' => '+48600100200' ) );
		BlockCheckoutValidator::validate_on_order_processed( $already_formatted );
		$this->assertSame( 0, $already_formatted->saves );

		$this->set_option( 'format_on_save', 'no' );
		$not_formatting = new \WC_Order( array( 'billing_phone' => '600 100 200' ) );
		BlockCheckoutValidator::validate_on_order_processed( $not_formatting );
		$this->assertSame( 0, $not_formatting->saves );
		$this->assertSame( '600 100 200', $not_formatting->data['billing_phone'] );
	}

	public function test_empty_phones_and_disabled_plugin_are_skipped(): void {
		$this->assertFalse( BlockCheckoutValidator::validate_phones( new \WC_Order() ) );

		$this->set_option( 'enabled', 'no' );
		$this->assertFalse( BlockCheckoutValidator::validate_phones( new \WC_Order( array( 'billing_phone' => '1234' ) ) ) );
	}

	public function test_invalid_shipping_phone_is_reported_for_the_shipping_field(): void {
		$order = new \WC_Order(
			array(
				'billing_phone'  => '600 100 200',
				'shipping_phone' => '1234',
			)
		);

		try {
			BlockCheckoutValidator::validate_phones( $order );
			$this->fail( 'RouteException expected.' );
		} catch ( RouteException $e ) {
			$this->assertSame( 'invalid_shipping_phone', $e->error_code );
			$this->assertSame( 'shipping_phone', $e->additional_data['field'] );
		}
	}

	public function test_locale_is_switched_for_the_message_and_restored_afterwards(): void {
		$_SERVER['HTTP_REFERER'] = 'https://shop.test/pl/zamowienie/';
		$this->use_polish();

		try {
			BlockCheckoutValidator::validate_phones( new \WC_Order( array( 'billing_phone' => '1234' ) ) );
			$this->fail( 'RouteException expected.' );
		} catch ( RouteException $e ) {
			$this->assertSame( 'Telefon do płatności nie jest prawidłowym numerem telefonu.', $e->getMessage() );
		} finally {
			unset( $_SERVER['HTTP_REFERER'] );
		}

		$this->assertSame( 'en_US', get_locale() );
		$this->assertSame( array(), $GLOBALS['shift64_test_locale']['switches'] );
	}

	/**
	 * @dataProvider referers
	 */
	public function test_locale_from_referer( string $referer, string $home, ?string $expected ): void {
		$this->assertSame( $expected, BlockCheckoutValidator::locale_from_referer( $referer, $home, array( 'pl_PL', 'de_DE' ) ) );
	}

	public function referers(): array {
		return array(
			'language directory'             => array( 'https://shop.test/pl/zamowienie/', 'https://shop.test/', 'pl_PL' ),
			'language directory only'        => array( 'https://shop.test/de', 'https://shop.test/', 'de_DE' ),
			'english needs no language pack' => array( 'https://shop.test/en/checkout/', 'https://shop.test/', 'en_US' ),
			'wordpress in a subdirectory'    => array( 'https://shop.test/sklep/pl/zamowienie/', 'https://shop.test/sklep/', 'pl_PL' ),
			'two letters deeper in the path' => array( 'https://shop.test/shop/it/checkout/', 'https://shop.test/', null ),
			'language not installed'         => array( 'https://shop.test/it/checkout/', 'https://shop.test/', null ),
			'no language directory'          => array( 'https://shop.test/zamowienie/', 'https://shop.test/', null ),
			'query string is not a path'     => array( 'https://shop.test/checkout/?lang=pl', 'https://shop.test/', null ),
			'empty referer'                  => array( '', 'https://shop.test/', null ),
		);
	}
}
