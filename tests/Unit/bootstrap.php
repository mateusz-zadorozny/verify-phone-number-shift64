<?php
/**
 * Bootstrap for unit tests that run WITHOUT WordPress.
 *
 * Only the two WordPress functions the validation layer touches are stubbed:
 * - get_option(): backed by $GLOBALS['shift64_test_options'] (set per test),
 * - __(): backed by $GLOBALS['shift64_test_translations'] (empty = English passthrough).
 *
 * @package Shift64\SmartPhoneValidation\Tests
 */

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

$GLOBALS['shift64_test_options']      = array();
$GLOBALS['shift64_test_translations'] = array();

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name, $default = false ) {
		return $GLOBALS['shift64_test_options'][ $name ] ?? $default;
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $GLOBALS['shift64_test_translations'][ $text ] ?? $text;
	}
}

require_once __DIR__ . '/TestCase.php';
