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

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT_URI'),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME'),
    ],

    'nova_poshta' => [
        'key' => env('NOVA_POSHTA_API_KEY'),
        'endpoint' => env('NOVA_POSHTA_API_ENDPOINT', 'https://api.novaposhta.ua/v2.0/json/'),
    ],

    'ukrposhta' => [
        'token' => env('UKRPOSHTA_API_TOKEN'),
        'endpoint' => env('UKRPOSHTA_API_ENDPOINT', 'https://www.ukrposhta.ua/ecom/0.0.1'),
        // Status-tracking API uses a separate bearer token and base URL.
        'status_token' => env('UKRPOSHTA_STATUS_TOKEN', env('UKRPOSHTA_API_TOKEN')),
        'status_endpoint' => env('UKRPOSHTA_STATUS_ENDPOINT', 'https://www.ukrposhta.ua/status-tracking/0.0.1'),
    ],

    'didit' => [
        'endpoint' => env('DIDIT_API_ENDPOINT', 'https://verification.didit.me'),
        'api_key' => env('DIDIT_API_KEY'),
        'api_secret' => env('DIDIT_API_SECRET'),
        'workflow_id' => env('DIDIT_WORKFLOW_ID'),
        'webhook_secret' => env('DIDIT_WEBHOOK_SECRET'),
    ],

];
