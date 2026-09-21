# Verify Phone Number Shift64

Phone number validation and formatting for WooCommerce checkout, powered by Google's [libphonenumber](https://github.com/giggsey/libphonenumber-for-php-lite).

The plugin checks billing and shipping phone numbers when an order is placed, rejects numbers that are not real for the given country, and (optionally) rewrites valid numbers into one consistent format before they are stored on the order.

## Features

- Validates **billing** and **shipping** phone numbers (shipping phone is optional – validated only when filled in)
- Works with both **classic checkout** (shortcode) and **block checkout** (Store API)
- Uses the address country as parsing context – `600 123 456` with country `PL` is understood as `+48600123456`
- Input normalization: spaces, dashes, dots and parentheses are stripped, a leading `00` is treated as `+`
- Two validation modes: *default country + international* or *international only* (number must start with `+`)
- Output formats: E.164 (`+48600123456`), international (`+48 600 123 456`), national (`600 123 456`)
- Highlights the offending phone field on checkout (small JS helpers, no build step)
- Translations: English and Polish; error messages in block checkout follow Polylang / WPML language
- Self-updates from GitHub releases (one-click update in WP Admin)

## Requirements

- WordPress 5.0+
- WooCommerce (block checkout support requires WooCommerce 5.3+)
- PHP 7.4+

## Installation

**Use the release ZIP, not GitHub's "Download ZIP" button.**

1. Download `verify-phone-number-shift64.zip` from the [latest release](https://github.com/mateusz-zadorozny/verify-phone-number-shift64/releases/latest).
2. In WP Admin go to *Plugins → Add New → Upload Plugin* and upload the file.
3. Activate the plugin.

Why it matters: the release ZIP contains the `vendor/` directory with libphonenumber. A source download (folder named `verify-phone-number-shift64-master`) does not – the plugin will only show a "Composer autoloader not found" notice and do nothing. The auto-updater also expects the plugin to live in a folder called exactly `verify-phone-number-shift64`.

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
| Default Country | `shift64_phone_validation_default_country` | `PL` | Region used for numbers without `+` **when the address has no country** |
| Validation Mode | `shift64_phone_validation_validation_mode` | `default_and_international` | `international_only` rejects every number without a `+` prefix |
| Output Format | `shift64_phone_validation_output_format` | `E164` | `E164`, `INTERNATIONAL` or `NATIONAL` |
| Enable Formatting on Save | `shift64_phone_validation_format_on_save` | `yes` | Rewrite valid numbers to the output format before saving the order |

Settings are stored as regular `wp_options` rows and are kept on deactivation.

## How it works

```
raw input ──► Normalizer ──► PhoneValidator ──► ValidationResult ──► PhoneFormatter ──► order
"0048 600-123-456"  "+48600123456"   libphonenumber parse + isValidNumber      "+48600123456"
```

| Checkout type | Validation hook | Formatting hook |
| --- | --- | --- |
| Classic | `woocommerce_after_checkout_validation` | `woocommerce_checkout_create_order` |
| Block (Store API) | `woocommerce_store_api_checkout_order_processed` (throws `RouteException`, HTTP 400) | same hook, then `$order->save()` |

Code layout:

```
verify-phone-number-shift64.php   bootstrap, constants, hook registration
src/Admin/Settings.php            WooCommerce settings tab + option getters
src/Admin/GitHubUpdater.php       update check against GitHub releases (cached 12 h)
src/Admin/DependencyChecker.php   "WooCommerce missing" notice
src/Validation/                   Normalizer, PhoneValidator, ValidationResult
src/Formatter/PhoneFormatter.php  E.164 / international / national output
src/Checkout/                     classic + block checkout integration, asset loading
assets/js/                        field highlighting for both checkout types
languages/                        .pot, en_US, pl_PL
```

## Development

```bash
composer install      # PHP dependencies + PHPCS tooling
npm ci                # semantic-release tooling

composer phpcs        # WordPress Coding Standards + PHPCompatibilityWP
composer phpcbf       # auto-fix what can be fixed
composer makepot      # regenerate languages/*.pot
```

### Tests

There is currently **no automated test coverage**. `tests/` contains only the WP-CLI scaffold sample test, and `phpunit.xml.dist` excludes it; PHPUnit is not part of `require-dev`. The only automated check in CI is PHPCS (`.github/workflows/code-quality.yml`). The `.circleci/config.yml` file is an unused scaffold leftover.

### Releases

Releases are fully automated with [semantic-release](https://semantic-release.gitbook.io/) on every push to `master`:

- commit messages / PR titles follow [Conventional Commits](https://www.conventionalcommits.org/) (`fix:` → patch, `feat:` → minor, `BREAKING CHANGE` → major); PR titles are linted
- `scripts/update-version.sh` bumps the plugin header, the `SHIFT64_PHONE_VALIDATION_VERSION` constant and `Stable tag` in `readme.txt`
- the workflow builds `verify-phone-number-shift64.zip` (with production `vendor/`) and attaches it to the GitHub release
- installed sites pick the new version up through `GitHubUpdater`

Full walkthrough of the pipeline: [docs/CI-CD-SETUP.md](docs/CI-CD-SETUP.md). Version history: [CHANGELOG.md](CHANGELOG.md).

## License

GPL-2.0-or-later
