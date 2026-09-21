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

// --- Stubs used by the updater tests -----------------------------------------
if ( ! defined( 'SHIFT64_PHONE_VALIDATION_FILE' ) ) {
	define( 'SHIFT64_PHONE_VALIDATION_FILE', dirname( __DIR__, 2 ) . '/verify-phone-number-shift64.php' );
	define( 'SHIFT64_PHONE_VALIDATION_VERSION', '1.0.0' );
}

$GLOBALS['shift64_test_plugin_basename'] = 'verify-phone-number-shift64/verify-phone-number-shift64.php';
$GLOBALS['shift64_test_transients']      = array();

if ( ! function_exists( 'plugin_basename' ) ) {
	function plugin_basename( $file ) {
		return $GLOBALS['shift64_test_plugin_basename'];
	}
	function get_transient( $key ) {
		return $GLOBALS['shift64_test_transients'][ $key ] ?? false;
	}
	function set_transient( $key, $value, $expiration = 0 ) {
		$GLOBALS['shift64_test_transients'][ $key ] = $value;
		return true;
	}
	function trailingslashit( $value ) {
		return rtrim( $value, '/' ) . '/';
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal stand-in: records what the checkout validators add.
	 */
	class WP_Error {
		public $errors     = array();
		public $error_data = array();

		public function __construct( $code = '', $message = '', $data = '' ) {
			if ( '' !== $code ) {
				$this->add( $code, $message, $data );
			}
		}

		public function add( $code, $message, $data = '' ) {
			$this->errors[ $code ][]   = $message;
			$this->error_data[ $code ] = $data;
		}
	}
}

// --- Stubs used by the block checkout tests ----------------------------------
$GLOBALS['shift64_test_locale'] = array(
	'current'  => 'en_US',
	'switches' => array(),
);

if ( ! function_exists( 'get_locale' ) ) {
	function get_locale() {
		return $GLOBALS['shift64_test_locale']['current'];
	}
	function determine_locale() {
		return $GLOBALS['shift64_test_locale']['current'];
	}
	function switch_to_locale( $locale ) {
		$GLOBALS['shift64_test_locale']['switches'][] = $GLOBALS['shift64_test_locale']['current'];
		$GLOBALS['shift64_test_locale']['current']    = $locale;
		return true;
	}
	function restore_previous_locale() {
		$GLOBALS['shift64_test_locale']['current'] = array_pop( $GLOBALS['shift64_test_locale']['switches'] );
		return $GLOBALS['shift64_test_locale']['current'];
	}
	function get_available_languages() {
		return array( 'pl_PL', 'de_DE' );
	}
	function home_url( $path = '' ) {
		return 'https://shop.test' . $path;
	}
	function unload_textdomain( $domain ) {
		return true;
	}
	function load_plugin_textdomain( $domain, $deprecated = false, $rel_path = false ) {
		return true;
	}
	function esc_html( $text ) {
		return $text;
	}
	function esc_url_raw( $url ) {
		return $url;
	}
	function wp_unslash( $value ) {
		return $value;
	}
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( $url, $component );
	}
}

require_once __DIR__ . '/stubs-woocommerce.php';
require_once __DIR__ . '/TestCase.php';
