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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'yahoo_finance' => [
        'python_path' => env('YAHOO_FINANCE_PYTHON_PATH',
            base_path('python/venv/Scripts/python.exe')  // Windows-Standard
        ),
        'script_path' => env('YAHOO_FINANCE_SCRIPT_PATH',
            base_path('python/yahoo_finance.py')
        ),
        // ✅ Cache-Konfiguration
        'cache' => [
            'quote_ttl' => env('YAHOO_QUOTE_CACHE_TTL', 300), // 5 Minuten default
            'historical_ttl' => env('YAHOO_HISTORICAL_CACHE_TTL', 3600), // 1 Stunde
            'market_hours_ttl' => env('YAHOO_MARKET_HOURS_CACHE_TTL', 120), // 2 Minuten während Handelszeiten
        ],
    ],
];
