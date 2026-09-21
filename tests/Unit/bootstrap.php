<?php
/**
 * Bootstrap for unit tests that run WITHOUT WordPress.
 *
 * Only the two WordPress functions the validation layer touches are stubbed:
 * - get_option(): backed by $GLOBALS['shift64_test_options'] (set per test),
 * - apply_filters(): backed by $GLOBALS['shift64_test_filters'] (hook => callable),
 * - __(): backed by $GLOBALS['shift64_test_translations'] (empty = English passthrough).
 *
 * @package Shift64\SmartPhoneValidation\Tests
 */

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

$GLOBALS['shift64_test_options']      = array();
$GLOBALS['shift64_test_translations'] = array();
$GLOBALS['shift64_test_filters']      = array();

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook, $value, ...$args ) {
		if ( isset( $GLOBALS['shift64_test_filters'][ $hook ] ) ) {
			return $GLOBALS['shift64_test_filters'][ $hook ]( $value, ...$args );
		}
		return $value;
	}
}

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

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal stand-in: records what the checkout validators add.
	 */
	class WP_Error {
		public $errors     = array();
		public $error_data = array();

		public function add( $code, $message, $data = '' ) {
			$this->errors[ $code ][]   = $message;
			$this->error_data[ $code ] = $data;
		}
	}
}

require_once __DIR__ . '/TestCase.php';
