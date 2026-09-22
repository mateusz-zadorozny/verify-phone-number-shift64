# AGENTS.md

## Project overview

Verify Phone Number Shift64 is a WordPress/WooCommerce plugin that validates and formats billing and shipping phone numbers at checkout, using Google's libphonenumber (`giggsey/libphonenumber-for-php-lite` v9). It parses a number against the address country, rejects numbers that are not real for that country, and optionally rewrites valid numbers into one consistent output format (E.164, international, or national) before they are stored on the order. It supports both the classic shortcode checkout and the block checkout (Store API), ships English and Polish translations, and self-updates from GitHub releases.

The plugin is PHP 8.3+, PSR-4 autoloaded under `Shift64\SmartPhoneValidation` from `src/`, with no JS build step — the two files in `assets/js/` are shipped as-is.

## Task routing

| When the task involves… | Read first | Key rules |
|---|---|---|
| Parsing, validating, or normalizing a number | `src/Validation/PhoneValidator.php`, `src/Validation/Normalizer.php`, `src/Validation/ValidationResult.php` | The validator returns a `ValidationResult` value object, never a bare bool or an exception — callers branch on it. `Normalizer` runs first and is where input cleanup lives (non-breaking spaces, dashes, dots, slashes, parentheses, leading `00` → `+`); do not scatter cleanup into callers. libphonenumber is reached only through these classes. |
| Output formatting | `src/Formatter/PhoneFormatter.php` | Formats are the `FORMAT_*` constants; a new format means a new constant plus a settings option, not a raw string passed through. |
| Classic (shortcode) checkout | `src/Checkout/BillingPhoneValidator.php`, `src/Checkout/ShippingPhoneValidator.php`, `src/Checkout/Hooks.php` | Shipping phone is optional: validate only when non-empty. Hook signatures come from WooCommerce, so unused parameters are expected and are excluded from the unused-parameter sniff in `.phpcs.xml.dist`. |
| Block checkout / Store API | `src/Checkout/BlockCheckoutValidator.php` | Errors are returned through the Store API error shape, not echoed. Language for messages follows Polylang / WPML (`wpml_current_language`); do not assume the site locale. |
| Error messages and i18n | `src/Checkout/ErrorMessages.php`, `languages/` | Every user-facing string is translated with the `verify-phone-number-shift64` text domain — PHPCS enforces the domain. After changing strings run `composer makepot` and update the `.po`/`.mo` files. Messages are filterable via `shift64_phone_validation_error_message`. |
| Checkout JS / field highlighting | `assets/js/checkout-validation.js`, `assets/js/block-checkout-validation.js`, `src/Checkout/Assets.php` | No build step — edit the shipped files directly. Enqueueing is opt-out through `shift64_phone_validation_enqueue_assets` for themes with their own error UX; keep that escape hatch working. |
| Settings screen or plugin options | `src/Admin/Settings.php`, `uninstall.php` | Options are prefixed `shift64_phone_validation_*`. A new option must also be deleted in `uninstall.php`, or it leaks after uninstall. |
| The self-updater | `src/Admin/GitHubUpdater.php`, `src/Admin/DependencyChecker.php` | The updater reads GitHub releases and depends on the release ZIP containing `vendor/`; a source checkout has no autoloader and `DependencyChecker` is what surfaces that. |
| Plugin bootstrap, constants, WooCommerce compatibility | `verify-phone-number-shift64.php` | The version lives in three places kept in sync by `scripts/update-version.sh` (plugin header, `SHIFT64_PHONE_VALIDATION_VERSION`, `readme.txt`) — never bump them by hand; semantic-release does it. HPOS and Cart & Checkout Blocks compatibility is declared here. |
| Tests | `tests/Unit/`, `tests/Unit/TestCase.php`, `tests/Unit/stubs-woocommerce.php` | Unit tests run without WordPress: WooCommerce and WP functions are stubbed in `stubs-woocommerce.php`. A test needing a new WP function adds a stub there rather than booting WordPress. Every bug fix ships a regression test. |
| CI, release, or versioning | `.github/workflows/`, `.releaserc.json`, `scripts/update-version.sh` | `pr-lint.yml` validates the PR title against Conventional Commits, and because merges are squashed that title drives the release: `feat:` → minor, `fix:`/`perf:` → patch, anything else → no release. |
| Packaging / what ships in the ZIP | `.distignore` | Anything added at the repo root that is not plugin runtime code belongs in `.distignore`. |

## Validation gate

Run in this order; any non-zero exit blocks the PR:

- `composer install --prefer-dist --no-interaction`
- `composer phpcs`
- `composer test`

`vendor/` is git-ignored, so the install step is genuinely required in a fresh clone or worktree. PHPCS runs `WordPress-Extra` plus `PHPCompatibilityWP` at `testVersion 8.3-`; `tests/` and `vendor/` are excluded from the scan. Indentation is tabs, per the WordPress standard — match the file you are editing.

## Process and contracts

- `SDLC.md` — how work flows from ticket to merged PR: stages, label state machine, QA gate, claim protocol.
- `CODE_REVIEW.md` — the review checklist `om-code-review` applies automatically.
- `BACKWARD_COMPATIBILITY.md` — the protected contract surfaces (filters, options, order meta) and what changing one requires.
- `.ai/agentic.config.json` — the machine-readable configuration every `om-*` skill reads.
- `README.md` — user-facing feature and installation documentation.
