=== Verify Phone Number Shift64 ===
Contributors: shift64
Tags: woocommerce, phone, validation, checkout, libphonenumber
Requires at least: 5.0
Tested up to: 6.8.3
Requires PHP: 7.4
Stable tag: 1.1.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Phone number validation and formatting for WooCommerce checkout, powered by Google's libphonenumber.

== Description ==

Validates billing and shipping phone numbers when an order is placed and optionally rewrites valid numbers into one consistent format before they are saved on the order.

* Works with classic (shortcode) checkout and block checkout (Store API)
* Uses the address country as context, so national numbers without a prefix are understood
* Normalizes input: strips spaces, dashes, dots and parentheses, treats leading `00` as `+`
* Validation modes: default country + international, or international only (`+` required)
* Output formats: E.164, international, national
* Highlights the invalid phone field on checkout
* English and Polish translations, Polylang / WPML aware error messages
* Self-updates from GitHub releases

Requires WooCommerce.

== Installation ==

1. Download `verify-phone-number-shift64.zip` from the latest GitHub release (not the "Download ZIP" source archive - it does not contain the required `vendor` directory).
1. Upload it through 'Plugins > Add New > Upload Plugin' and activate.
1. Configure the plugin under 'WooCommerce > Settings > Phone Validation'.

== Frequently Asked Questions ==

= Where are the settings? =

WooCommerce > Settings > Phone Validation tab.

= I see "Composer autoloader not found" =

The plugin was installed from a source archive. Install the release ZIP instead, or run `composer install --no-dev` in the plugin directory.

= Is the shipping phone required? =

No. It is validated only when the customer fills it in.

== Changelog ==

See CHANGELOG.md in the GitHub repository: https://github.com/mateusz-zadorozny/verify-phone-number-shift64/blob/master/CHANGELOG.md
