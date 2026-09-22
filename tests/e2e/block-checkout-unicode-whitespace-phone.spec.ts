import { test } from '@playwright/test';
import {
	addProductToCart,
	expectOrderReceived,
	fillBlockCheckout,
	placeOrder,
	VALID_PL_PHONE_E164,
} from './helpers/checkout';

// Regression for #24: a number pasted with non-breaking spaces used to be
// rejected by WooCommerce's own phone check before the plugin could clean it.
const NBSP_PHONE = '500 123 456';

test( 'block checkout accepts a Polish phone typed with non-breaking spaces', async ( {
	page,
} ) => {
	await addProductToCart( page );
	await fillBlockCheckout( page, NBSP_PHONE );
	await placeOrder( page );
	await expectOrderReceived( page, VALID_PL_PHONE_E164 );
} );
