import { test } from '@playwright/test';
import {
	addProductToCart,
	expectPhoneRejected,
	fillClassicCheckout,
	INVALID_PL_PHONE,
	placeOrder,
} from './helpers/checkout';

test( 'classic checkout rejects an invalid Polish phone number', async ( {
	page,
} ) => {
	await addProductToCart( page );
	await fillClassicCheckout( page, INVALID_PL_PHONE );
	await placeOrder( page );
	await expectPhoneRejected( page );
} );
