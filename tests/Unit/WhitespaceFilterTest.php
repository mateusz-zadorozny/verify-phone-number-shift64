<?php

namespace Shift64\SmartPhoneValidation\Tests\Unit;

use Shift64\SmartPhoneValidation\Checkout\WhitespaceFilter;

/**
 * Runs the whitespace filters the way WooCommerce and the REST server call them:
 * classic checkout gets the field value, the Store API gets the whole request.
 */
class WhitespaceFilterTest extends TestCase {

	const NBSP_NUMBER = "600\u{00A0}100\u{00A0}200";

	public function test_posted_phone_gets_plain_spaces(): void {
		$this->assertSame( '600 100 200', WhitespaceFilter::filter_posted_phone( self::NBSP_NUMBER ) );
	}

	public function test_posted_phone_is_left_alone_when_the_plugin_is_disabled(): void {
		$this->set_option( 'enabled', 'no' );

		$this->assertSame( self::NBSP_NUMBER, WhitespaceFilter::filter_posted_phone( self::NBSP_NUMBER ) );
	}

	public function test_store_api_addresses_get_plain_spaces_and_dispatch_continues(): void {
		$request = new \WP_REST_Request(
			'POST',
			'/wc/store/v1/checkout',
			array(
				'billing_address'  => array(
					'phone'   => self::NBSP_NUMBER,
					'country' => 'PL',
				),
				'shipping_address' => array( 'phone' => "+48\u{202F}600\u{202F}100\u{202F}200" ),
				'payment_method'   => 'cod',
			)
		);

		$this->assertNull( WhitespaceFilter::filter_store_api_request( null, null, $request ) );

		$this->assertSame(
			array(
				'phone'   => '600 100 200',
				'country' => 'PL',
			),
			$request->get_param( 'billing_address' )
		);
		$this->assertSame( array( 'phone' => '+48 600 100 200' ), $request->get_param( 'shipping_address' ) );
		$this->assertSame( 'cod', $request->get_param( 'payment_method' ) );
	}

	/**
	 * The block checkout validates the address on every change through cart/update-customer,
	 * so a paste with non-breaking spaces must pass there as well, not only on place order.
	 */
	public function test_update_customer_requests_are_cleaned_too(): void {
		$request = new \WP_REST_Request(
			'POST',
			'/wc/store/v1/cart/update-customer',
			array( 'billing_address' => array( 'phone' => self::NBSP_NUMBER ) )
		);

		WhitespaceFilter::filter_store_api_request( null, null, $request );

		$this->assertSame( '600 100 200', $request->get_param( 'billing_address' )['phone'] );
	}

	public function test_store_api_request_without_phones_is_untouched(): void {
		$request = new \WP_REST_Request(
			'POST',
			'/wc/store/v1/cart/update-customer',
			array(
				'billing_address'  => array( 'country' => 'PL' ),
				'shipping_address' => null,
			)
		);

		WhitespaceFilter::filter_store_api_request( null, null, $request );

		$this->assertSame( array( 'country' => 'PL' ), $request->get_param( 'billing_address' ) );
		$this->assertNull( $request->get_param( 'shipping_address' ) );
	}

	public function test_other_rest_routes_are_untouched(): void {
		$request = new \WP_REST_Request(
			'POST',
			'/wp/v2/comments',
			array( 'billing_address' => array( 'phone' => self::NBSP_NUMBER ) )
		);

		WhitespaceFilter::filter_store_api_request( null, null, $request );

		$this->assertSame( self::NBSP_NUMBER, $request->get_param( 'billing_address' )['phone'] );
	}

	public function test_short_circuited_dispatch_is_passed_through_unchanged(): void {
		$request  = new \WP_REST_Request(
			'POST',
			'/wc/store/v1/checkout',
			array( 'billing_address' => array( 'phone' => self::NBSP_NUMBER ) )
		);
		$response = new \stdClass();

		$this->assertSame( $response, WhitespaceFilter::filter_store_api_request( $response, null, $request ) );
		$this->assertSame( self::NBSP_NUMBER, $request->get_param( 'billing_address' )['phone'] );
	}

	public function test_store_api_request_is_left_alone_when_the_plugin_is_disabled(): void {
		$this->set_option( 'enabled', 'no' );

		$request = new \WP_REST_Request(
			'POST',
			'/wc/store/v1/checkout',
			array( 'billing_address' => array( 'phone' => self::NBSP_NUMBER ) )
		);

		WhitespaceFilter::filter_store_api_request( null, null, $request );

		$this->assertSame( self::NBSP_NUMBER, $request->get_param( 'billing_address' )['phone'] );
	}
}
