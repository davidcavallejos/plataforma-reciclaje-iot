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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | ESP32 de la máquina de jabón
    |--------------------------------------------------------------------------
    |
    | El firmware expone un servidor HTTP con autenticación básica en /cmd y
    | /status. El panel actúa como intermediario para no exponer las
    | credenciales de la placa en el navegador.
    |
    */

    'esp32' => [
        'url' => env('ESP32_BASE_URL', 'http://192.168.1.50'),
        'username' => env('ESP32_USERNAME', 'admin'),
        'password' => env('ESP32_PASSWORD', 'jabon123'),
        'timeout' => (int) env('ESP32_TIMEOUT', 2),
    ],

];
