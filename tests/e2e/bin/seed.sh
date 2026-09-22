#!/bin/sh
# Seed the wp-env WordPress instance with the minimum WooCommerce setup
# needed to place an order at checkout. Idempotent: safe to run on every
# `wp-env start` (wired through lifecycleScripts.afterStart in .wp-env.json).
#
# Usage: sh tests/e2e/bin/seed.sh [cli ...]
#   No arguments seeds the default `cli` instance.
set -eu

ROOT="$(cd "$(dirname "$0")/../../.." && pwd)"
cd "$ROOT"

if [ ! -f "$ROOT/vendor/autoload.php" ]; then
	echo "seed: vendor/autoload.php is missing - run 'composer install' first, the plugin cannot boot without it." >&2
	exit 1
fi

INSTANCES="${*:-cli}"

seed_instance() {
	instance="$1"
	wp() { npx --no-install wp-env run "$instance" wp "$@"; }

	echo "seed: [$instance] store settings"
	wp rewrite structure '/%postname%/' --hard >/dev/null
	wp option update woocommerce_coming_soon no >/dev/null
	wp option update woocommerce_onboarding_profile '{"skipped":true,"completed":true}' --format=json >/dev/null
	wp option update woocommerce_store_address 'ul. Testowa 1' >/dev/null
	wp option update woocommerce_store_city 'Warszawa' >/dev/null
	wp option update woocommerce_store_postcode '00-001' >/dev/null
	wp option update woocommerce_default_country 'PL' >/dev/null
	wp option update woocommerce_currency 'PLN' >/dev/null
	wp option update woocommerce_enable_guest_checkout yes >/dev/null
	wp option update woocommerce_enable_checkout_login_reminder no >/dev/null
	wp option update woocommerce_enable_signup_and_login_from_checkout no >/dev/null

	echo "seed: [$instance] payment (cash on delivery) and shipping (flat rate, rest of world)"
	wp wc payment_gateway update cod --enabled=true --user=admin >/dev/null
	if ! wp wc shipping_zone_method list 0 --user=admin --format=ids | grep -q .; then
		wp wc shipping_zone_method create 0 --method_id=flat_rate --settings='{"cost":"10"}' --user=admin >/dev/null
	fi

	echo "seed: [$instance] product"
	if [ -z "$(wp post list --post_type=product --name=e2e-test-product --field=ID)" ]; then
		wp wc product create --name='E2E Test Product' --slug=e2e-test-product --type=simple --regular_price=10 --user=admin >/dev/null
	fi

	echo "seed: [$instance] classic (shortcode) checkout page"
	if [ -z "$(wp post list --post_type=page --name=classic-checkout --field=ID)" ]; then
		wp post create --post_type=page --post_status=publish --post_title='Classic Checkout' --post_name=classic-checkout --post_content='[woocommerce_checkout]' >/dev/null
	fi

	echo "seed: [$instance] plugin defaults"
	wp option update shift64_phone_validation_enabled yes >/dev/null
	wp option update shift64_phone_validation_default_country PL >/dev/null
	wp option update shift64_phone_validation_validation_mode default_and_international >/dev/null
	wp option update shift64_phone_validation_output_format E164 >/dev/null

	echo "seed: [$instance] done"
}

for i in $INSTANCES; do
	seed_instance "$i"
done
