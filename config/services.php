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
        'inbound_address' => env('POSTMARK_INBOUND_ADDRESS'),
        'inbound_webhook_token' => env('POSTMARK_INBOUND_WEBHOOK_TOKEN'),
        // Comma-separated override for Postmark's published webhook source
        // IPs, in case they add/change IPs before this app's list is updated.
        // See PostmarkInboundController::POSTMARK_WEBHOOK_IPS for the default.
        'webhook_ips' => env('POSTMARK_WEBHOOK_IPS'),
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

];
