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

    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'url' => env('OPENAI_URL', 'https://api.openai.com/v1/chat/completions'),
        'model' => env('OPENAI_MODEL', 'gpt-3.5-turbo'),
        'vision_model' => env('OPENAI_VISION_MODEL', 'gpt-4o'),
        'max_tokens' => env('OPENAI_MAX_TOKENS', 1000),
        'temperature' => env('OPENAI_TEMPERATURE', 0.7),
    ],

    'youtube' => [
        'api_key' => env('YOUTUBE_API_KEY'),
    ],

    'ai_manager' => [
        'url' => env('AI_MANAGER_URL', 'https://aimanager.akmicroservice.com'),
        'api_key' => env('AI_MANAGER_API_KEY', '8eebab3587a5719950dfb3ee348737c6e244c13a5d6b3d35161071ee6a9d8c43'),
        'timeout' => env('AI_MANAGER_TIMEOUT', 30),
    ],

    'youtube_transcriber' => [
        'url' => env('YOUTUBE_TRANSCRIBER_URL', 'https://transcriber.akmicroservice.com'),
        'client_key' => env('YOUTUBE_TRANSCRIBER_API_KEY', 'dev-local'),
        'timeout' => env('YOUTUBE_TRANSCRIBER_TIMEOUT', 600), // 10 minutes for Smartproxy
        'default_format' => env('YOUTUBE_TRANSCRIBER_FORMAT', 'bundle'),
    ],

    'webhooks' => [
        'processing_url' => env('WEBHOOK_PROCESSING_URL'),
        'secret' => env('WEBHOOK_SECRET'),
        'timeout' => env('WEBHOOK_TIMEOUT', 10),
        'retry_attempts' => env('WEBHOOK_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('WEBHOOK_RETRY_DELAY', 60),
    ],

];