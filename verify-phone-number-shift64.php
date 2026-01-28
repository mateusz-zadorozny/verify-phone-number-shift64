<?php
/**
 * Plugin Name:     Verify Phone Number Shift64
 * Plugin URI:      https://shift64.com/plugins/verify-phone-number
 * Description:     Smart phone number validation and formatting for WordPress using Google's libphonenumber library.
 * Author:          Shift64
 * Author URI:      https://shift64.com
 * Text Domain:     verify-phone-number-shift64
 * Domain Path:     /languages
 * Version:         1.0.0
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
define( 'SHIFT64_PHONE_VALIDATION_VERSION', '1.0.0' );
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

// Load plugin text domain for translations (early, before REST API processing).
add_action(
	'plugins_loaded',
	function () {
		load_plugin_textdomain(
			'verify-phone-number-shift64',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	},
	5 // Early priority to ensure translations are available for Store API.
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

		// Initialize block checkout validation (Store API).
		Checkout\BlockCheckoutValidator::init();

		// Initialize checkout assets (JS for field highlighting).
		Checkout\Assets::init();
	}
);

/**
 * Plugin deactivation callback.
 *
 * Removes all validation hooks to restore default WooCommerce behavior.
 * Note: Settings are intentionally NOT deleted to preserve configuration for reactivation.
 *
 * @return void
 */
function shift64_phone_validation_deactivate(): void {
	// Remove Settings hooks.
	remove_filter( 'woocommerce_settings_tabs_array', array( Admin\Settings::class, 'add_settings_tab' ), 50 );
	remove_action( 'woocommerce_settings_tabs_' . Admin\Settings::TAB_ID, array( Admin\Settings::class, 'render_settings_page' ) );
	remove_action( 'woocommerce_update_options_' . Admin\Settings::TAB_ID, array( Admin\Settings::class, 'save_settings' ) );

	// Remove BillingPhoneValidator hooks.
	remove_action( 'woocommerce_after_checkout_validation', array( Checkout\BillingPhoneValidator::class, 'validate_billing_phone' ), 10 );
	remove_action( 'woocommerce_checkout_create_order', array( Checkout\BillingPhoneValidator::class, 'format_billing_phone_on_order' ), 10 );

	// Remove ShippingPhoneValidator hooks.
	remove_action( 'woocommerce_after_checkout_validation', array( Checkout\ShippingPhoneValidator::class, 'validate_shipping_phone' ), 10 );
	remove_action( 'woocommerce_checkout_create_order', array( Checkout\ShippingPhoneValidator::class, 'format_shipping_phone_on_order' ), 10 );

	// Remove BlockCheckoutValidator hooks.
	remove_action( 'woocommerce_store_api_checkout_order_processed', array( Checkout\BlockCheckoutValidator::class, 'validate_phones' ), 10 );

	// Remove Assets hooks.
	remove_action( 'wp_enqueue_scripts', array( Checkout\Assets::class, 'enqueue_checkout_scripts' ) );
}

// Register deactivation hook.
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\shift64_phone_validation_deactivate' );
