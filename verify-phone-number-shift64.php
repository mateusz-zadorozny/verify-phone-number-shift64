<?php
/**
 * Plugin Name:     Verify Phone Number Shift64
 * Plugin URI:      https://shift64.com/plugins/verify-phone-number
 * Description:     Smart phone number validation and formatting for WordPress using Google's libphonenumber library.
 * Author:          Shift64
 * Author URI:      https://shift64.com
 * Text Domain:     verify-phone-number-shift64
 * Domain Path:     /languages
 * Version:         0.1.0
 * Requires PHP:    7.4
 * Requires at least: 5.0
 *
 * @package Shift64\SmartPhoneValidation
 */

declare(strict_types=1);

namespace Shift64\SmartPhoneValidation;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'SHIFT64_PHONE_VALIDATION_VERSION', '0.1.0' );
define( 'SHIFT64_PHONE_VALIDATION_FILE', __FILE__ );
define( 'SHIFT64_PHONE_VALIDATION_PATH', plugin_dir_path( __FILE__ ) );
define( 'SHIFT64_PHONE_VALIDATION_URL', plugin_dir_url( __FILE__ ) );

// Load Composer autoloader.
$autoloader = SHIFT64_PHONE_VALIDATION_PATH . 'vendor/autoload.php';

if ( file_exists( $autoloader ) ) {
	require_once $autoloader;
} else {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__(
				'Verify Phone Number Shift64: Composer autoloader not found. Please run "composer install" in the plugin directory.',
				'verify-phone-number-shift64'
			);
			echo '</p></div>';
		}
	);
	return;
}

// Load plugin text domain for translations.
add_action(
	'init',
	function () {
		load_plugin_textdomain(
			'verify-phone-number-shift64',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}
);

// Check WooCommerce dependency.
add_action(
	'plugins_loaded',
	function () {
		if ( ! Admin\DependencyChecker::is_woocommerce_active() ) {
			Admin\DependencyChecker::display_woocommerce_missing_notice();
			return;
		}

		// Initialize plugin settings.
		Admin\Settings::init();

		// Initialize checkout phone validation.
		Checkout\BillingPhoneValidator::init();
		Checkout\ShippingPhoneValidator::init();
	}
);
