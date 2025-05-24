<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the payment gateway integration.
    |
    */

    'default' => env('PAYMENT_GATEWAY', 'stripe'),

    'gateways' => [
        'stripe' => [
            'key' => env('STRIPE_KEY'),
            'secret' => env('STRIPE_SECRET'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],
        
        'paypal' => [
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'client_secret' => env('PAYPAL_CLIENT_SECRET'),
            'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox or live
        ],
    ],

    'currency' => env('PAYMENT_CURRENCY', 'EUR'),
    
    'payment_types' => [
        'card' => true,
        'bank_transfer' => true,
        'digital_wallet' => true,
    ],

    'logging' => [
        'enabled' => true,
        'channel' => 'payment',
    ],
]; 