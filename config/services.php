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

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_DEFAULT_MODEL', 'claude-sonnet-4-6'),
    ],

    'moderation' => [
        'enabled'      => env('MODERATION_ENABLED', true),
        'fail_open'    => env('MODERATION_FAIL_OPEN', true),
        'model'        => env('MODERATION_MODEL', 'claude-haiku-4-5-20251001'),
        // Max Claude-moderated images per calendar month; beyond this the
        // local NSFWJS fallback takes over. 2000 imgs ≈ ₱120/month worst case.
        'monthly_cap'  => env('MODERATION_MONTHLY_CAP', 2000),
        'fallback_url' => env('MODERATION_FALLBACK_URL', 'http://127.0.0.1:3310'),
    ],

    'paymongo' => [
        'secret_key'     => env('PAYMONGO_SECRET_KEY'),
        'public_key'     => env('PAYMONGO_PUBLIC_KEY'),
        'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET'),
        'api_url'        => env('PAYMONGO_API_URL', 'https://api.paymongo.com/v1'),
    ],

];
