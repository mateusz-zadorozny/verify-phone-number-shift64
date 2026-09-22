import { defineConfig, devices } from '@playwright/test';

// The e2e suite runs against the wp-env instance from .wp-env.json by default
// (port 8888, overridable with WP_ENV_PORT, the variable wp-env itself honors).
// Set WP_BASE_URL to attach to any other running WordPress instance.
const baseURL =
	process.env.WP_BASE_URL ??
	`http://localhost:${ process.env.WP_ENV_PORT ?? '8888' }`;

export default defineConfig( {
	testDir: './tests/e2e',
	testMatch: /.*\.spec\.ts$/,
	outputDir: 'test-results',
	timeout: 60_000,
	expect: { timeout: 10_000 },
	fullyParallel: true,
	retries: process.env.CI ? 1 : 0,
	reporter: process.env.CI
		? [ [ 'github' ], [ 'html', { open: 'never' } ] ]
		: [ [ 'list' ], [ 'html', { open: 'never' } ] ],
	use: {
		baseURL,
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
		...devices[ 'Desktop Chrome' ],
	},
} );
