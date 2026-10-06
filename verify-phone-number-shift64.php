<?php
/**
 * Plugin Name:     Verify Phone Number Shift64
 * Plugin URI:      https://github.com/mateusz-zadorozny/verify-phone-number-shift64
 * Description:     Validates and formats WooCommerce checkout phone numbers with Google's libphonenumber library.
 * Author:          Mateusz Zadorożny (SHIFT64)
 * Author URI:      https://shift64.com
 * License:         GPLv2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     verify-phone-number-shift64
 * Domain Path:     /languages
 * Version:         1.5.0
 * Requires PHP:    8.3
 * Requires at least: 5.0
 * Requires Plugins: woocommerce
 * WC requires at least: 7.2
 * WC tested up to: 11.1
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
define( 'SHIFT64_PHONE_VALIDATION_VERSION', '1.5.0' );
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

// Load the bundled Composer autoloader (libphonenumber and the plugin classes).
$shift64_autoloader = SHIFT64_PHONE_VALIDATION_PATH . 'vendor/autoload.php';

if ( file_exists( $shift64_autoloader ) ) {
	require_once $shift64_autoloader;

	// Public helpers such as format_phone(). Required here instead of through Composer's
	// "files" autoload: their ABSPATH guard would otherwise stop dev tools that include
	// vendor/autoload.php outside WordPress (PHPUnit, WP-CLI) before they start.
	require_once SHIFT64_PHONE_VALIDATION_PATH . 'src/functions.php';
} else {
	// The vendor/ folder ships inside every release package; it is only missing from an
	// incomplete upload or a source checkout. Tell the people who can fix it, and stop.
	add_action(
		'admin_notices',
		function () {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			echo '<div class="notice notice-error"><p>';
			echo esc_html__(
				'Verify Phone Number Shift64 cannot run because its bundled vendor folder is missing. Reinstall the plugin from WordPress.org or from the release ZIP on GitHub.',
				'verify-phone-number-shift64'
			);
			echo '</p></div>';
		}
	);
	return;
}

// Register the bundled languages/ folder for WordPress versions before 6.8, which do not
// read Domain Path on their own. Translations from translate.wordpress.org take precedence.
// Runs early so the strings are ready before Store API requests are handled.
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

		// Collapse Unicode whitespace in posted phone numbers before WooCommerce's own
		// phone check rejects them (classic checkout and Store API).
		Checkout\WhitespaceFilter::init();

		// Initialize checkout phone validation.
		Checkout\BillingPhoneValidator::init();
		Checkout\ShippingPhoneValidator::init();

		// Initialize block checkout validation (Store API).
		Checkout\BlockCheckoutValidator::init();

		// Initialize checkout assets (JS for field highlighting).
		Checkout\Assets::init();
	}
);
