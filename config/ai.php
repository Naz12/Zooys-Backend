<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Processing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for AI processing modules including chunking, summarization,
    | and content extraction settings.
    |
    */

    'chunking' => [
        'max_size' => env('AI_CHUNK_MAX_SIZE', 3000),
        'overlap_size' => env('AI_CHUNK_OVERLAP_SIZE', 200),
        'min_size' => env('AI_CHUNK_MIN_SIZE', 500),
        'enabled' => env('AI_CHUNKING_ENABLED', true),
    ],

    'summarization' => [
        'max_tokens' => env('AI_SUMMARY_MAX_TOKENS', 1000),
        'temperature' => env('AI_SUMMARY_TEMPERATURE', 0.7),
        'enabled' => env('AI_SUMMARIZATION_ENABLED', true),
    ],

    'content_extraction' => [
        'supported_types' => [
            'text',
            'youtube',
            'pdf',
            'url',
            'document',
            'file',
        ],
        'max_file_size' => env('AI_MAX_FILE_SIZE', '10MB'),
        'timeout' => env('AI_EXTRACTION_TIMEOUT', 30),
    ],

    'modules' => [
        'youtube' => [
            'enabled' => env('AI_YOUTUBE_ENABLED', true),
            'python_enabled' => env('AI_YOUTUBE_PYTHON_ENABLED', true),
            'fallback_enabled' => env('AI_YOUTUBE_FALLBACK_ENABLED', true),
        ],
        'pdf' => [
            'enabled' => env('AI_PDF_ENABLED', true),
            'max_file_size' => env('AI_PDF_MAX_FILE_SIZE', '10MB'),
        ],
        'web_scraping' => [
            'enabled' => env('AI_WEB_SCRAPING_ENABLED', true),
            'timeout' => env('AI_WEB_SCRAPING_TIMEOUT', 30),
        ],
    ],

    'processing' => [
        'unified_enabled' => env('AI_UNIFIED_PROCESSING_ENABLED', true),
        'chunking_threshold' => env('AI_CHUNKING_THRESHOLD', 8000),
        'parallel_processing' => env('AI_PARALLEL_PROCESSING', false),
    ],
];
