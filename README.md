# Verify Phone Number Shift64

Phone number validation and formatting for WooCommerce checkout, powered by Google's [libphonenumber](https://github.com/giggsey/libphonenumber-for-php-lite) (v9).

The plugin checks billing and shipping phone numbers when an order is placed, rejects numbers that are not real for the given country, and (optionally) rewrites valid numbers into one consistent format before they are stored on the order.

## Features

- Validates **billing** and **shipping** phone numbers (shipping phone is optional – validated only when filled in)
- Works with both **classic checkout** (shortcode) and **block checkout** (Store API)
- Uses the address country as parsing context – `600 123 456` with country `PL` is understood as `+48600123456`
- Input normalization: spaces (including non-breaking ones pasted from documents), dashes, dots, slashes and parentheses are stripped, a leading `00` is treated as `+`
- Two validation modes: *default country + international* or *international only* (number must start with `+`)
- Output formats: E.164 (`+48600123456`), international (`+48 600 123 456`), national (`600 123 456`)
- Highlights the offending phone field on checkout (small JS helpers, no build step; can be switched off for themes with their own error UX)
- Translations: English and Polish; error messages in block checkout follow Polylang / WPML language
- Self-updates from GitHub releases (one-click update in WP Admin)

## Requirements

- WordPress 5.0+
- WooCommerce 7.2+ (declared compatible with High-Performance Order Storage and Cart & Checkout blocks)
- PHP 8.3+

## Installation

**Use the release ZIP, not GitHub's "Download ZIP" button.**

1. Download `verify-phone-number-shift64.zip` from the [latest release](https://github.com/mateusz-zadorozny/verify-phone-number-shift64/releases/latest).
2. In WP Admin go to *Plugins → Add New → Upload Plugin* and upload the file.
3. Activate the plugin.

Why it matters: the release ZIP contains the `vendor/` directory with libphonenumber. A source download (folder named `verify-phone-number-shift64-master`) does not – the plugin will only show a "Composer autoloader not found" notice and do nothing. The auto-updater works from any folder name, but it still needs `vendor/`.

If you do install from source, run inside the plugin directory:

```bash
composer install --no-dev --optimize-autoloader
```

## Settings

**WooCommerce → Settings → Phone Validation** tab
(`wp-admin/admin.php?page=wc-settings&tab=phone_validation`)

| Setting | Option name | Default | Description |
| --- | --- | --- | --- |
| Enable Validation | `shift64_phone_validation_enabled` | `yes` | Master switch for validation and formatting |
| Default Country | `shift64_phone_validation_default_country` | `PL` | Fallback only: region used for numbers without `+` **when the address has no country**. See [Country context](#country-context) |
| Validation Mode | `shift64_phone_validation_validation_mode` | `default_and_international` | `international_only` rejects every number without a `+` prefix |
| Output Format | `shift64_phone_validation_output_format` | `E164` | `E164`, `INTERNATIONAL` or `NATIONAL` |
| Enable Formatting on Save | `shift64_phone_validation_format_on_save` | `yes` | Rewrite valid numbers to the output format before saving the order |

Settings are stored as regular `wp_options` rows. They are kept on deactivation and removed when the plugin is deleted (`uninstall.php`); phone numbers already saved on orders are never touched.

## Country context

A number typed **with** a prefix (`+48…` or `0048…`) is unambiguous and is validated as such, whatever the address says.

A number typed **without** a prefix is read in the country of the billing / shipping address; the *Default Country* setting is used only when the address has no country. The library checks whether the digits form a valid number *in that country* – it cannot know whose number it is:

| Input | Address country | Result |
| --- | --- | --- |
| `600 100 200` | PL | valid → `+48600100200` |
| `600 100 200` | GB, CZ, US | rejected |
| `600 100 200` | DE, FR | **valid** → stored as `+49600100200` / `+33600100200` |

The last row is the limitation to be aware of on stores selling abroad: a customer with a foreign address and a domestic phone must type the prefix. If that is a concern, use the *International only* validation mode (every number must start with `+`), or tell customers in the field description to include the prefix. Single-country stores are not affected.

## How it works

```
raw input ──► Normalizer ──► PhoneValidator ──► ValidationResult ──► PhoneFormatter ──► order
"0048 600-123-456"  "+48600123456"   libphonenumber parse + isValidNumber      "+48600123456"
```

| Checkout type | Validation hook | Formatting hook |
| --- | --- | --- |
| Classic | `woocommerce_after_checkout_validation` | `woocommerce_checkout_create_order` |
| Block (Store API) | `woocommerce_store_api_checkout_update_order_from_request`, **POST (place order) only** – throws `RouteException`, HTTP 400 with `field` and error `code` | same hook; WooCommerce saves the order |

Before either hook runs, WooCommerce itself checks phone fields with `WC_Validation::is_phone()`, whose pattern only knows ASCII whitespace: a number with a non-breaking space (pasted from Word, Outlook or a PDF) would be rejected with WooCommerce's own message before the plugin sees it. `Checkout\WhitespaceFilter` therefore collapses Unicode whitespace to plain spaces first, on `woocommerce_process_checkout_field_billing_phone` / `..._shipping_phone` (classic) and on `rest_pre_dispatch` for `/wc/store/*` requests (Store API). Only the kind of space changes, separators stay.

Code layout:

```
verify-phone-number-shift64.php   bootstrap, constants, hook registration
src/Admin/Settings.php            WooCommerce settings tab + option getters
src/Admin/GitHubUpdater.php       update check against GitHub releases (cached 12 h)
src/Admin/DependencyChecker.php   "WooCommerce missing" notice
src/Validation/                   Normalizer, PhoneValidator, ValidationResult
src/Formatter/PhoneFormatter.php  E.164 / international / national output
src/Checkout/                     classic + block checkout integration, whitespace pre-clean, error messages, asset loading
assets/js/                        field highlighting for both checkout types
languages/                        .pot, en_US, pl_PL
```

## Developers

Validation logic lives in the plugin, presentation can live in the theme. All extension points are WordPress filters, so a theme that uses them keeps working when the plugin is deactivated (an unused `add_filter()` is a no-op).

| Filter | Arguments | Purpose |
| --- | --- | --- |
| `shift64_phone_validation_enqueue_assets` | `bool $enqueue` | Return `false` to skip the highlighting scripts when the theme has its own checkout error UX. Validation is server-side and keeps working. |
| `shift64_phone_validation_error_message` | `string $message, ?string $code, string $field, string $context` | Reword the customer-facing message. `$code`: `missing_international_prefix`, `invalid_number`, `too_short`, `too_long`, `not_a_number`, `invalid_country_code`, `parse_error`. `$field`: `billing` / `shipping`. `$context`: `classic` / `block`. |
| `shift64_phone_validation_should_validate` | `bool $validate, string $field, array\|WC_Order $context` | Return `false` to skip validation **and** formatting for a field in this request (e.g. staff orders); the whitespace clean-up still runs. `$context` is the posted data on classic checkout, the order otherwise. |
| `shift64_phone_validation_formatted_phone` | `string $formatted, PhoneNumber $number, string $field, WC_Order $order` | Change the value stored on the order. |

```php
// Theme owns the error UX and the wording.
add_filter( 'shift64_phone_validation_enqueue_assets', '__return_false' );
add_filter(
	'shift64_phone_validation_error_message',
	function ( $message, $code ) {
		return 'missing_international_prefix' === $code
			? 'Podaj numer telefonu z prefiksem kraju, np. +48.'
			: 'Podaj poprawny numer telefonu.';
	},
	10,
	2
);
```

An integration that needs a different format than the stored one can use the helper (guard it – it only exists while the plugin is active):

```php
if ( function_exists( 'Shift64\\SmartPhoneValidation\\format_phone' ) ) {
	$national = \Shift64\SmartPhoneValidation\format_phone( $order->get_billing_phone(), 'PL', 'NATIONAL' ); // null when invalid.
}
```

Classic checkout errors are added with the field id (`data-id="billing_phone"` / `shipping_phone` on the notice `<li>`), and every default message contains the word "phone" (pl_PL: "telefon"), so both id-based and keyword-based theme mappers can attach them to the field.

**Before enabling "Formatting on Save"** check every consumer of the order phone (ERP / order export, courier labels, SMS gateway): the stored value changes shape, e.g. `600 100 200` → `+48600100200`.

## Development

```bash
composer install      # PHP dependencies + PHPCS tooling
npm ci                # semantic-release tooling

composer phpcs        # WordPress Coding Standards + PHPCompatibilityWP
composer phpcbf       # auto-fix what can be fixed
composer makepot      # regenerate languages/*.pot
```

### Tests

```bash
composer test         # PHPUnit unit suite (tests/Unit), no WordPress required
```

The unit suite covers the pure logic: `Normalizer`, `PhoneValidator` (including error codes), `PhoneFormatter`, `ValidationResult` and the checkout error-message mapping in English and Polish. WordPress is not loaded; `get_option()` and `__()` are replaced by small stubs in `tests/Unit/bootstrap.php`.

There are **no integration tests yet** – the WooCommerce hooks (classic checkout, Store API) are not exercised automatically. CI (`.github/workflows/code-quality.yml`) runs PHPCS and the unit suite on every PR. `tests/bootstrap.php` and `bin/install-wp-tests.sh` are kept for a future WordPress integration suite.

### Releases

Releases are fully automated with [semantic-release](https://semantic-release.gitbook.io/) on every push to `master`:

- commit messages / PR titles follow [Conventional Commits](https://www.conventionalcommits.org/) (`fix:` → patch, `feat:` → minor, `BREAKING CHANGE` → major); PR titles are linted
- `scripts/update-version.sh` bumps the plugin header, the `SHIFT64_PHONE_VALIDATION_VERSION` constant and `Stable tag` in `readme.txt`
- the workflow builds `verify-phone-number-shift64.zip` (with production `vendor/`) and attaches it to the GitHub release
- installed sites pick the new version up through `GitHubUpdater`

Full walkthrough of the pipeline: [docs/CI-CD-SETUP.md](docs/CI-CD-SETUP.md). Version history: [CHANGELOG.md](CHANGELOG.md).

## Credits

Created and maintained by Mateusz Zadorożny at **SHIFT64**.

Sponsored by SHIFT64 – [Custom WooCommerce Coding](https://shift64.com/services/custom-woocommerce-coding).

## License

GPL-2.0-or-later
