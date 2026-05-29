<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment gateway connection that will be
    | used when processing checkouts. This can be configured in your .env.
    |
    */

    'default' => env('PAYMENT_GATEWAY_DRIVER', 'stripe'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configurations
    |--------------------------------------------------------------------------
    |
    | Here you can configure the connection details for each payment gateway
    | supported by the application.
    |
    */

    'gateways' => [

        'stripe' => [
            'public_key' => env('STRIPE_KEY'),
            'secret_key' => env('STRIPE_SECRET'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],

        'mock' => [],

    ],

];
