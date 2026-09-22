import { test } from '@playwright/test';
import {
	addProductToCart,
	expectOrderReceived,
	fillBlockCheckout,
	placeOrder,
	VALID_PL_PHONE,
	VALID_PL_PHONE_E164,
} from './helpers/checkout';

test( 'block checkout accepts a valid Polish phone number and stores it as E.164', async ( {
	page,
} ) => {
	await addProductToCart( page );
	await fillBlockCheckout( page, VALID_PL_PHONE );
	await placeOrder( page );
	await expectOrderReceived( page, VALID_PL_PHONE_E164 );
} );
