<?php

namespace Shift64\SmartPhoneValidation\Tests\Unit;

use Shift64\SmartPhoneValidation\Validation\Normalizer;

class NormalizerTest extends TestCase {

	/**
	 * @dataProvider inputs
	 */
	public function test_normalize( string $input, string $expected ): void {
		$this->assertSame( $expected, Normalizer::normalize( $input ) );
	}

	public function inputs(): array {
		return array(
			'already clean'         => array( '+48600100200', '+48600100200' ),
			'spaces'                => array( '600 100 200', '600100200' ),
			'dashes'                => array( '600-100-200', '600100200' ),
			'dots'                  => array( '600.100.200', '600100200' ),
			'parentheses'           => array( '(22) 410 05 00', '224100500' ),
			'prefix in parentheses' => array( '(+48) 600 100 200', '+48600100200' ),
			'surrounding whitespace' => array( "  600100200\n", '600100200' ),
			'00 becomes plus'       => array( '0048 600 100 200', '+48600100200' ),
			'slashes'               => array( '22/410-05-00', '224100500' ),
			'non-breaking space'    => array( "600\u{00A0}100\u{00A0}200", '600100200' ),
			'narrow nbsp'           => array( "+48\u{202F}600\u{202F}100\u{202F}200", '+48600100200' ),
			'invalid utf-8 is kept' => array( "600 100\xff200", "600100\xff200" ),
			'empty'                 => array( '', '' ),
			'only separators'       => array( ' - ( ) . ', '' ),
		);
	}
}
