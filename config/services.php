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

    'document_converter' => [
        'url' => env('DOCUMENT_CONVERTER_URL', 'http://localhost:8004'),
        'api_key' => env('DOCUMENT_CONVERTER_API_KEY', 'test-api-key-123'),
        'timeout' => env('DOCUMENT_CONVERTER_TIMEOUT', 300),
    ],

    'ai_manager' => [
        'url' => env('AI_MANAGER_URL', 'https://aimanager.akmicroservice.com'),
        'api_key' => env('AI_MANAGER_API_KEY', '8eebab3587a5719950dfb3ee348737c6e244c13a5d6b3d35161071ee6a9d8c43'),
        'timeout' => env('AI_MANAGER_TIMEOUT', 180), // Increased to 3 minutes
        'default_model' => env('AI_MANAGER_DEFAULT_MODEL', 'deepseek-chat'), // Default model for summarization
    ],

    'stripe' => [
        'public' => env('STRIPE_PUBLIC_KEY'),
        'secret' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'youtube_transcriber' => [
        'url' => env('YOUTUBE_TRANSCRIBER_URL', 'https://transcriber.akmicroservice.com'),
        'client_key' => env('YOUTUBE_TRANSCRIBER_CLIENT_KEY', 'dev-local'),
        'timeout' => env('YOUTUBE_TRANSCRIBER_TIMEOUT', 600),
        'default_format' => env('YOUTUBE_TRANSCRIBER_DEFAULT_FORMAT', 'bundle'),
    ],

    'document_intelligence' => [
        'url' => env('DOC_INTELLIGENCE_URL', 'https://doc.akmicroservice.com'),
        'tenant' => env('DOC_INTELLIGENCE_TENANT', 'dagu'),
        'client_id' => env('DOC_INTELLIGENCE_CLIENT_ID', 'dev'),
        'key_id' => env('DOC_INTELLIGENCE_KEY_ID', 'local'),
        'secret' => env('DOC_INTELLIGENCE_SECRET', 'change_me'),
        'timeout' => env('DOC_INTELLIGENCE_TIMEOUT', 120),
    ],

    'sms_gateway' => [
        'url' => env('SMS_GATEWAY_URL', 'http://127.0.0.1:9000/api/internal/v1'),
        'client_id' => env('SMS_GATEWAY_CLIENT_ID', 'zooys'),
        'key_id' => env('SMS_GATEWAY_KEY_ID', 'k_demo_zooys'),
        'secret' => env('SMS_GATEWAY_SECRET', 's_XXXXXXXXXXXXXXXXXXXXXXXXXXXX'),
        'timeout' => env('SMS_GATEWAY_TIMEOUT', 30),
    ],

];