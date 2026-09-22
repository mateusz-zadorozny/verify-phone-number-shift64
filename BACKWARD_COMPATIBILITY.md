# Backward compatibility

This plugin runs inside other people's stores. Its protected contract surfaces are the things a site owner, a theme, or an integration can already depend on and cannot see us change. A PR that alters one without following the required path below is a **blocker** in review, not a nit.

Versioning is semantic and automated: a breaking change to any surface here needs a `!` (or a `BREAKING CHANGE:` footer) on the squash-merge subject, so `semantic-release` cuts a major release.

## Protected surfaces

### 1. Filters (`src/Checkout/`)

Themes and integrations hook these. Names, parameter order, parameter types, and return type are the contract.

| Filter | Signature | Defined in |
|---|---|---|
| `shift64_phone_validation_should_validate` | `bool $validate, string $field, mixed $context` | `src/Checkout/Hooks.php` |
| `shift64_phone_validation_formatted_phone` | `string $formatted, PhoneNumber $phone_number, string $field, WC_Order $order` | `src/Checkout/Hooks.php` |
| `shift64_phone_validation_error_message` | `string $message, string $error_code, string $field, mixed $context` | `src/Checkout/ErrorMessages.php` |
| `shift64_phone_validation_enqueue_assets` | `bool $enqueue` | `src/Checkout/Assets.php` |

**Breaking:** renaming or removing a filter; reordering, removing, or retyping a parameter; changing the return type; changing the default value passed in; no longer applying the filter on a path where it used to fire.

**Not breaking:** appending a new parameter at the end; adding a new filter.

**Required path:** appending a parameter is allowed and must be documented in the docblock and `README.md`. Anything else keeps the old filter firing alongside the new one for at least one minor release, with a `_deprecated_hook()` notice, and a `CHANGELOG.md` migration note.

### 2. Options (`shift64_phone_validation_*`)

Stored in `wp_options` and readable by any code on the site.

| Option | Default | Accepted values |
|---|---|---|
| `shift64_phone_validation_enabled` | `yes` | `yes` / `no` |
| `shift64_phone_validation_default_country` | `PL` | ISO 3166-1 alpha-2 |
| `shift64_phone_validation_validation_mode` | `default_and_international` | `default_and_international` / `international_only` |
| `shift64_phone_validation_output_format` | `E164` | `E164` / `INTERNATIONAL` / `NATIONAL` |
| `shift64_phone_validation_format_on_save` | `yes` | `yes` / `no` |

**Breaking:** renaming an option; changing its stored shape; changing a default in a way that alters behavior on an existing install; removing an accepted value.

**Not breaking:** adding a new option with a default that preserves current behavior; adding a new accepted value.

**Required path:** a rename or reshape ships with a one-time migration that reads the old key and writes the new one, and the old key stays readable for one minor release. Every new option is also added to `uninstall.php` — an option that is not deleted there leaks after uninstall, which is itself a bug.

### 3. Order data

The plugin rewrites the billing and shipping phone stored on the order via `WC_Order` CRUD methods (`set_billing_phone()` / `set_shipping_phone()`), gated by `format_on_save`. Orders are read and written only through CRUD, which is why both legacy post storage and HPOS work.

**Breaking:** writing to a different field; writing custom meta a site would then depend on; bypassing CRUD (it breaks HPOS); changing the stored format without the `output_format` option saying so.

**Required path:** the stored format is user-visible data. Changing what lands on the order requires the setting to drive it, and a `CHANGELOG.md` note, because existing orders will not match new ones.

### 4. Public PHP API

| Surface | Contract |
|---|---|
| `Shift64\SmartPhoneValidation\format_phone( string $raw, ?string $country = null, string $format = PhoneFormatter::FORMAT_E164 ): ?string` | The documented helper for other code. Signature and `null`-on-failure behavior are frozen. |
| `ValidationResult` | `is_valid()`, `get_error_code()`, `get_error_message()`, `get_phone_number()` and the `ERROR_*` constants. Error codes are strings integrations match on. |
| `PhoneFormatter::FORMAT_*` constants | Their string values are stored in the `output_format` option. |

**Breaking:** changing a method signature or return type; removing or renaming an `ERROR_*` constant or changing its string value; changing a `FORMAT_*` string value.

**Not breaking:** adding a new `ERROR_*` constant — but note that an integration matching on codes will not know it, so new codes belong in the changelog.

### 5. Environment requirements

Declared in `verify-phone-number-shift64.php` and `composer.json`: PHP 8.3+, WordPress 5.0+, WooCommerce 7.2+.

**Breaking:** raising any minimum — it makes the plugin refuse to run on sites where it currently works.

**Required path:** a major release, with the reason in `CHANGELOG.md`. `DependencyChecker` must still degrade gracefully rather than fatal.

### 6. Translation text domain

`verify-phone-number-shift64`, enforced by PHPCS. Changing it invalidates every existing translation, including third-party ones.

**Breaking:** changing the text domain. **Required path:** don't.

## What is not protected

Everything under `src/` that is not listed above: internal class structure, private and protected methods, the `Normalizer` cleanup rules, the JS in `assets/js/`, and the WooCommerce hook wiring. These may be refactored freely as long as the surfaces above and the test suite hold.

## Reviewing a change against this file

`om-code-review` reads this document. When a diff touches a surface above, the review states which one, whether the change is breaking under the rules here, and whether the required path is present in the same PR. Absent that, the finding is a blocker.
