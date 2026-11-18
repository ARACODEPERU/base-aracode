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
        'token' => env('MERCADOPAGO_TOKEN'),
        'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
        'max_installments' => (int) env('MERCADOPAGO_MAX_INSTALLMENTS', 12),
        // Días que se mantiene el acceso mientras MercadoPago reintenta un cobro
        // rechazado antes de suspender la suscripción.
        'subscription_grace_days' => (int) env('MERCADOPAGO_SUBSCRIPTION_GRACE_DAYS', 3),
        // Credenciales de PRUEBA del modo prueba de suscripciones. Nunca se
        // usan las de producción: si el token coincide, el panel se bloquea.
        'test_token' => env('MERCADOPAGO_TEST_TOKEN'),
        'test_public_key' => env('MERCADOPAGO_TEST_PUBLIC_KEY'),
        'test_email' => env('MERCADOPAGO_TEST_EMAIL'),
        // Tarjeta de prueba con la que se tokeniza el cobro simulado.
        'test_card' => [
            'number' => env('MERCADOPAGO_TEST_CARD_NUMBER', '5031 7557 3453 0604'),
            'cvv' => env('MERCADOPAGO_TEST_CARD_CVV', '123'),
            'expiration_month' => env('MERCADOPAGO_TEST_CARD_MONTH', '11'),
            'expiration_year' => env('MERCADOPAGO_TEST_CARD_YEAR', '2030'),
            'holder_name' => env('MERCADOPAGO_TEST_CARD_NAME', 'APRO'),
            'identification_type' => env('MERCADOPAGO_TEST_CARD_DOC_TYPE', 'DNI'),
            'identification_number' => env('MERCADOPAGO_TEST_CARD_DOC_NUMBER', '12345678'),
        ],
    ],
    'whatsapp' => [
        'token' => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    ],
    'recaptcha' => [
    'site_key' => env('RECAPTCHA_SITE_KEY'),
    'secret_key' => env('RECAPTCHA_SECRET_KEY'),
    ],
    'email' => [
        'mail_from_address' => env('MAIL_FROM_ADDRESS'),
        'mail_from_name' => env('MAIL_FROM_NAME'),
        'app_name' => env('APP_NAME'),
        // Buzon de administracion (MAIL_ADMIN): recibe los avisos operativos, como
        // la negociacion confirmada del modulo Commercial. MailSender::adminAddress()
        // lo lee y aplica el respaldo si el .env no trae un correo util.
        'admin_address' => env('MAIL_ADMIN'),
    ],
];
