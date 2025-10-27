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

    'document_extraction' => [
        'url' => env('DOCUMENT_EXTRACTION_URL', 'http://localhost:8003'),
        'timeout' => env('DOCUMENT_EXTRACTION_TIMEOUT', 300),
    ],

    'document_converter' => [
        'url' => env('DOCUMENT_CONVERTER_URL', 'http://localhost:8004'),
        'api_key' => env('DOCUMENT_CONVERTER_API_KEY', 'test-api-key-123'),
        'timeout' => env('DOCUMENT_CONVERTER_TIMEOUT', 300),
    ],

    'ai_manager' => [
        'url' => env('AI_MANAGER_URL', 'http://localhost:8005'),
        'api_key' => env('AI_MANAGER_API_KEY', 'test-api-key-123'),
        'timeout' => env('AI_MANAGER_TIMEOUT', 180), // Increased to 3 minutes
    ],

];