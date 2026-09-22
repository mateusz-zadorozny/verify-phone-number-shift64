import { test } from '@playwright/test';
import {
	addProductToCart,
	expectPhoneRejected,
	fillBlockCheckout,
	INVALID_PL_PHONE,
	placeOrder,
} from './helpers/checkout';

test( 'block checkout rejects an invalid Polish phone number', async ( {
	page,
} ) => {
	await addProductToCart( page );
	await fillBlockCheckout( page, INVALID_PL_PHONE );
	await placeOrder( page );
	await expectPhoneRejected( page );
} );
