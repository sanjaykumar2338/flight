<?php

return [
    'mode' => env('STRIPE_MODE', 'test'),
    'public_key' => env('STRIPE_PUBLIC_KEY'),
    'secret_key' => env('STRIPE_SECRET_KEY'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    'success_url' => env('STRIPE_SUCCESS_URL', rtrim(env('APP_URL', 'http://localhost'), '/').'/payments/stripe/success'),
    'cancel_url' => env('STRIPE_CANCEL_URL', rtrim(env('APP_URL', 'http://localhost'), '/').'/bookings'),
];
