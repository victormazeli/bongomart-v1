<?php

return [
	
	'paystack' => [
		'publicKey'  => env('PAYSTACK_PUBLIC_KEY', ''),                         // required
		'secretKey'  => env('PAYSTACK_SECRET_KEY', ''),                         // required
		'paymentUrl' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'), // required
		/*
		 * Optional
		 * Accepted currencies: NGN, GHS, USD
		 * Enter the supported currencies separated by comma in the Paystack currency variable,
		 * in the /.env file like this: PAYSTACK_CURRENCIES="NGN,GHS"
		 * NOTE: This is related to your Paystack account configuration.
		 */
		'currencies' => env('PAYSTACK_CURRENCIES', 'NGN,GHS'),
	],
	
];
