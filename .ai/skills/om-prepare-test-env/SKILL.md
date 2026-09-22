# om-prepare-test-env — repo-local notes

Extends the installed `om-prepare-test-env` skill with what this repository
taught us. Safety and quality rules of the base skill still win.

## The recipe

- The environment is the repo's own: `.wp-env.json` (`@wordpress/env`, Docker)
  boots latest WordPress + latest WooCommerce with this checkout bind-mounted
  as the plugin; `lifecycleScripts.afterStart` runs `tests/e2e/bin/seed.sh`
  (idempotent) so every start yields a store that can take an order.
- Entrypoints: `sh .ai/scripts/test-env-up.sh` / `sh .ai/scripts/test-env-down.sh`
  (`--destroy` also drops the DB volume). Descriptor: `.ai/qa/test-env.json`.
- Preparation chain before `wp-env start`: `composer install` (the plugin
  cannot boot without `vendor/autoload.php`) and `npm ci` (provides `wp-env`).
- Plugin PHP is bind-mounted: code edits are live, no restart. Only
  `.wp-env.json`, the seed script, or the lockfiles invalidate reuse.
- Each worktree runs its own wp-env project (`wp-env-<dirname>-<hash>`); run
  several side by side with `WP_ENV_PORT=<port>` — Playwright reads the same
  variable.

## Lessons baked into the scripts

- 2026-09-22 — `wp-env` v11 still starts a second "tests" instance unless
  `"testsEnvironment": false` is set; the recipe sets it, so there is exactly
  one site (port 8888) and the seed targets the `cli` container only.
- 2026-09-22 — `wp option update woocommerce_task_list_hidden` errors out on
  a fresh store; dropped from the seed (WooCommerce's onboarding is skipped via
  `woocommerce_onboarding_profile` and `woocommerce_coming_soon=no` instead).
- 2026-09-22 — Under Node 26 `npx playwright install chromium` fails instantly
  with a bogus "timed out"; the Chrome-for-Testing zips download fine with
  `curl` and can be unpacked by hand into `~/Library/Caches/ms-playwright/`
  (`chromium-<rev>/` and `chromium_headless_shell-<rev>/`, each with an empty
  `INSTALLATION_COMPLETE` marker). CI on Node 22 is unaffected.
- The agent-browser CLI on this machine comes from an fnm-managed global npm
  install, so `browser.command` in the descriptor is a per-shell path; the up
  script re-resolves it with `command -v` on every boot.
