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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'paystack' => [
        'secret_key' => env('PAYSTACK_SECRET_KEY', ''),
    ],

    'stellar' => [
        'network' => env('STELLAR_NETWORK', 'testnet'),
        'horizon_url' => env('STELLAR_HORIZON_URL', 'https://horizon-testnet.stellar.org'),

        // Our SEP-24 anchor (docker/anchor). SEP-10 + SEP-24 live under this base.
        'anchor_base_url' => env('STELLAR_ANCHOR_BASE_URL', 'http://localhost:8080'),
        // The anchor's SEP-10 SIGNING_KEY (from docker/anchor/accounts.generated.env).
        'anchor_sep10_signing_key' => env('STELLAR_ANCHOR_SEP10_SIGNING', ''),
        'anchor_home_domain' => env('STELLAR_ANCHOR_HOME_DOMAIN', 'localhost:8080'),

        'ngnc_asset_code' => env('STELLAR_NGNC_ASSET_CODE', 'NGNC'),
        'ngnc_issuer' => env('STELLAR_NGNC_ISSUER', ''),

        'distribution_public' => env('STELLAR_PLATFORM_DISTRIBUTION_PUBLIC', ''),

        // Jiidaa's wallet that receives Anchor (SEP-24) subscription payments. The platform
        // authenticates SEP-10 as this wallet and the deposit settles NGNC into it.
        'platform_wallet_public' => env('STELLAR_PLATFORM_WALLET_PUBLIC', ''),
        'platform_wallet_secret' => env('STELLAR_PLATFORM_WALLET_SECRET', ''),
    ],

    'google_maps' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'whatsapp' => [
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'platform_number' => env('PLATFORM_WHATSAPP_NUMBER', ''),
    ],

    'claude' => [
        'api_key' => env('CLAUDE_API_KEY'),
        'model' => env('CLAUDE_MODEL', 'claude-haiku-4-5-20251001'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
    ],

    'ai_bot' => [
        'driver' => env('AI_BOT_DRIVER', 'gemini'),
    ],

    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@shoppingcomplex.com'),
        'name' => env('ADMIN_NAME', 'Super Admin'),
        'password' => env('ADMIN_PASSWORD', ''),
    ],

];
