import { test } from '@playwright/test';
import {
	addProductToCart,
	expectOrderReceived,
	fillClassicCheckout,
	placeOrder,
	VALID_PL_PHONE,
	VALID_PL_PHONE_E164,
} from './helpers/checkout';

test( 'classic checkout accepts a valid Polish phone number and stores it as E.164', async ( {
	page,
} ) => {
	await addProductToCart( page );
	await fillClassicCheckout( page, VALID_PL_PHONE );
	await placeOrder( page );
	await expectOrderReceived( page, VALID_PL_PHONE_E164 );
} );
