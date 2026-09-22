import { expect, test } from '@playwright/test';
import { newAdminPage, setValidationEnabled } from './helpers/admin';
import {
	addProductToCart,
	fillBlockCheckout,
	INVALID_PL_PHONE,
	placeOrder,
} from './helpers/checkout';

test( 'disabling validation in admin lets an invalid phone number through', async ( {
	page,
	browser,
} ) => {
	const admin = await newAdminPage( browser );
	try {
		await setValidationEnabled( admin, false );

		await addProductToCart( page );
		await fillBlockCheckout( page, INVALID_PL_PHONE );
		await placeOrder( page );
		await expect( page ).toHaveURL( /order-received/, { timeout: 30_000 } );
		await expect(
			page.getByText( /your order has been received/i )
		).toBeVisible();
	} finally {
		await setValidationEnabled( admin, true );
		await admin.context().close();
	}
} );
