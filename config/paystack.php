<?php

return [
    'mode' => env('PAYSTACK_MODE', 'sandbox'),
    'public_key' => env('PAYSTACK_PUBLIC_KEY'),
    'secret_key' => env('PAYSTACK_SECRET_KEY'),
    'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET'),
    'payment_url' => rtrim(env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'), '/'),
    'currency' => env('PAYSTACK_CURRENCY', 'NGN'),
];
