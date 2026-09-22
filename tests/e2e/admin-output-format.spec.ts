import { test } from '@playwright/test';
import { newAdminPage, setOutputFormat } from './helpers/admin';
import {
	addProductToCart,
	expectOrderReceived,
	fillBlockCheckout,
	placeOrder,
	VALID_PL_PHONE,
} from './helpers/checkout';

test( 'changing Output Format to International changes how the next order stores the phone', async ( {
	page,
	browser,
} ) => {
	const admin = await newAdminPage( browser );
	try {
		await setOutputFormat( admin, 'International (e.g., +48 123 456 789)' );

		await addProductToCart( page );
		await fillBlockCheckout( page, VALID_PL_PHONE );
		await placeOrder( page );
		await expectOrderReceived( page, '+48 500 123 456' );
	} finally {
		await setOutputFormat( admin, 'E.164 (e.g., +48123456789)' );
		await admin.context().close();
	}
} );
