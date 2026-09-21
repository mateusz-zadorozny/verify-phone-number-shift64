<?php
/**
 * Plugin Name:     Verify Phone Number Shift64
 * Plugin URI:      https://shift64.com/plugins/verify-phone-number
 * Description:     Smart phone number validation and formatting for WordPress using Google's libphonenumber library.
 * Author:          Shift64
 * Author URI:      https://shift64.com
 * Text Domain:     verify-phone-number-shift64
 * Domain Path:     /languages
 * Version:         1.4.0
 * Requires PHP:    8.3
 * Requires at least: 5.0
 * Requires Plugins: woocommerce
 * WC requires at least: 7.2
 * WC tested up to: 10.9
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
define( 'SHIFT64_PHONE_VALIDATION_VERSION', '1.4.0' );
define( 'SHIFT64_PHONE_VALIDATION_FILE', __FILE__ );
define( 'SHIFT64_PHONE_VALIDATION_PATH', plugin_dir_path( __FILE__ ) );
define( 'SHIFT64_PHONE_VALIDATION_URL', plugin_dir_url( __FILE__ ) );

// Declare compatibility with WooCommerce features. Orders are only accessed through
// WC_Order CRUD methods, so both order storages work.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

// Load Composer autoloader.
$shift64_autoloader = SHIFT64_PHONE_VALIDATION_PATH . 'vendor/autoload.php';

if ( file_exists( $shift64_autoloader ) ) {
	require_once $shift64_autoloader;
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
		// Initialize GitHub updater (runs regardless of WooCommerce).
		Admin\GitHubUpdater::init();

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
 * Hooks live for a single request, so there is nothing to unhook - only cached data is cleared.
 * Settings are intentionally NOT deleted here (see uninstall.php) so they survive reactivation.
 *
 * @return void
 */
function shift64_phone_validation_deactivate(): void {
	Admin\GitHubUpdater::clear_cache();
}

// Register deactivation hook.
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\shift64_phone_validation_deactivate' );
