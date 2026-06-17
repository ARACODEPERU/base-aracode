<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'mercadopago' => [
        'key' => env('MERCADOPAGO_KEY'),
        'token' => env('MERCADOPAGO_TOKEN')
    ],

    'internal_api' => [
        'key' => env('INTERNAL_API_KEY'),
    ],

    'socket_io' => [
        'public_url' => env('VITE_SOCKET_IO_SERVER', 'http://localhost:3000'),
        'internal_url' => env('SOCKET_IO_INTERNAL_URL', env('VITE_SOCKET_IO_SERVER', 'http://127.0.0.1:3000')),
    ],
];
