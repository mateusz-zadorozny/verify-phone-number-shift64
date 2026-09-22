import { expect, type Browser, type Page } from '@playwright/test';

export const SETTINGS_PATH =
	'/wp-admin/admin.php?page=wc-settings&tab=phone_validation';

export type OutputFormatLabel =
	| 'E.164 (e.g., +48123456789)'
	| 'International (e.g., +48 123 456 789)'
	| 'National (e.g., 123 456 789)';

/**
 * Admin credentials come from the environment, never from the test files:
 * `.ai/qa/test-env.env` (written by the test-env entrypoint and loaded by
 * playwright.config.ts) or the CI job's environment.
 */
function adminCredentials(): { user: string; password: string } {
	const user = process.env.TEST_ADMIN_USER ?? 'admin';
	const password = process.env.TEST_ADMIN_PASSWORD;
	if ( ! password ) {
		throw new Error(
			'TEST_ADMIN_PASSWORD is not set. Run `sh .ai/scripts/test-env-up.sh` (writes .ai/qa/test-env.env) or export it.'
		);
	}
	return { user, password };
}

export async function loginAsAdmin( page: Page ): Promise< void > {
	const { user, password } = adminCredentials();
	await page.goto( '/wp-login.php' );
	await page.getByLabel( 'Username or Email Address' ).fill( user );
	await page.getByLabel( 'Password', { exact: true } ).fill( password );
	await page.getByRole( 'button', { name: 'Log In' } ).click();
	await expect( page ).toHaveURL( /wp-admin/ );
}

/** A separate browser context, so the shopper's session stays a guest. */
export async function newAdminPage( browser: Browser ): Promise< Page > {
	const context = await browser.newContext();
	const page = await context.newPage();
	await loginAsAdmin( page );
	return page;
}

export async function openPhoneValidationSettings( page: Page ): Promise< void > {
	await page.goto( SETTINGS_PATH );
	await expect(
		page.getByRole( 'heading', { name: 'Phone Validation Settings' } )
	).toBeVisible();
}

export async function saveSettings( page: Page ): Promise< void > {
	await page.getByRole( 'button', { name: 'Save changes' } ).click();
	await expect(
		page.getByText( 'Your settings have been saved.' )
	).toBeVisible();
}

export async function setOutputFormat(
	page: Page,
	label: OutputFormatLabel
): Promise< void > {
	await openPhoneValidationSettings( page );
	// WooCommerce renders the select as a selectWoo combobox; the native
	// <select> stays in the DOM, so pick the option through it.
	const row = page.getByRole( 'row', { name: /Output Format/ } );
	await row.locator( 'select' ).selectOption( { label }, { force: true } );
	await saveSettings( page );
}

export async function setValidationEnabled(
	page: Page,
	enabled: boolean
): Promise< void > {
	await openPhoneValidationSettings( page );
	await page
		.getByRole( 'checkbox', {
			name: 'Enable phone number validation globally',
		} )
		.setChecked( enabled );
	await saveSettings( page );
}
