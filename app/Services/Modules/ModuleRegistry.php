<?php

namespace App\Services\Modules;

use Illuminate\Support\Facades\Log;
use App\Services\Modules\SubscriptionBillingModule;
use App\Services\Modules\MathModule;
use App\Services\Modules\PresentationModule;
use App\Services\Modules\TranscriberModule;
use App\Services\Modules\FileOperationsModule;
use App\Services\Modules\DocumentIntelligenceModule;
use App\Services\Modules\FlashcardModule;

class ModuleRegistry
{
    private static $modules = [];
    private static $initialized = false;

    /**
     * Initialize the module registry
     */
    public static function initialize()
    {
        if (self::$initialized) {
            return;
        }

        self::registerCoreModules();
        self::registerCustomModules();
        self::$initialized = true;
        
        Log::info('Module registry initialized with ' . count(self::$modules) . ' modules');
    }

    /**
     * Register core modules
     */
    private static function registerCoreModules()
    {
        // Content chunking now handled by Document Intelligence microservice
        // AI summarization now handled by AI Manager microservice
        
        self::registerModule('content_extraction', [
            'class' => ContentExtractionService::class,
            'description' => 'Unified content extraction from various sources using microservices',
            'dependencies' => [],
            'config' => [
                'supported_types' => ['text', 'youtube', 'pdf', 'url', 'document'],
            ]
        ]);
    }

    /**
     * Register custom modules
     */
    private static function registerCustomModules()
    {
        // Register YouTube module
        self::registerModule('youtube', [
            'class' => \App\Services\YouTubeService::class,
            'description' => 'YouTube video processing and caption extraction',
            'dependencies' => ['content_extraction', 'transcriber'],
            'config' => [
                'api_key' => config('services.youtube.api_key'),
                'transcriber_enabled' => true,
            ]
        ]);

        // PDF processing now handled by PDF microservice via DocumentConverterService
        // No local PDF module needed

        // Web Scraping is now part of TranscriberModule (BrightData microservice)
        // Removed duplicate registration - web scraping handled by transcriber module

        // Register Math Module (wraps AIMathService)
        self::registerModule('math', [
            'class' => MathModule::class,
            'description' => 'Mathematical problem solving via Math microservice',
            'dependencies' => [],
            'config' => [
                'api_url' => env('MATH_MICROSERVICE_URL', 'http://localhost:8002'),
                'timeout' => env('MATH_MICROSERVICE_TIMEOUT', 60),
                'supported_subjects' => ['algebra', 'geometry', 'calculus', 'statistics', 'trigonometry', 'arithmetic', 'general'],
                'difficulty_levels' => ['beginner', 'intermediate', 'advanced'],
                'max_image_size' => '10MB',
                'supported_formats' => ['text', 'image'],
            ]
        ]);

        // Register Presentation Module (wraps AIPresentationService)
        self::registerModule('presentation', [
            'class' => PresentationModule::class,
            'description' => 'AI-powered presentation generation via Presentation microservice',
            'dependencies' => ['content_extraction'],
            'config' => [
                'api_url' => env('PRESENTATION_MICROSERVICE_URL', 'http://localhost:8001'),
                'supported_input_types' => ['text', 'file', 'url', 'youtube'],
                'supported_templates' => ['corporate_blue', 'modern_white', 'creative_colorful', 'minimalist_gray', 'academic_formal'],
                'supported_languages' => ['English', 'Spanish', 'French', 'German', 'Italian', 'Portuguese', 'Chinese', 'Japanese'],
                'supported_tones' => ['Professional', 'Casual', 'Academic', 'Creative', 'Formal'],
                'supported_lengths' => ['Short', 'Medium', 'Long'],
            ]
        ]);

        // Register AI Processing Module
        self::registerModule('ai_processing', [
            'class' => AIProcessingModule::class,
            'description' => 'AI text processing via AI API Manager microservice with multi-model support',
            'dependencies' => [],
            'config' => [
                'api_url' => config('services.ai_manager.url'),
                'timeout' => config('services.ai_manager.timeout'),
                'default_model' => config('services.ai_manager.default_model', 'ollama:llama3'),
                'supported_tasks' => [
                    'summarize',
                    'generate',
                    'qa',
                    'translate',
                    'sentiment',
                    'code-review',
                    'ppt-generate',
                    'flashcard'
                ],
                'supported_features' => [
                    'model_selection',
                    'topic_chat',
                    'model_discovery',
                    'multi_backend_routing'
                ],
                'supported_models' => [
                    'ollama:llama3',
                    'ollama:mistral',
                    'gpt-4o',
                    'auto' // Workload-aware routing
                ]
            ]
        ]);

        // Register Transcriber Module (BrightData microservice - YouTube + Web Scraping)
        self::registerModule('transcriber', [
            'class' => TranscriberModule::class,
            'description' => 'Content transcription and extraction: YouTube videos, web scraping via BrightData microservice',
            'dependencies' => [],
            'config' => [
                'api_url' => config('services.youtube_transcriber.url'),
                'client_key' => config('services.youtube_transcriber.client_key'),
                'timeout' => config('services.youtube_transcriber.timeout'),
                'supported_operations' => ['youtube_transcribe', 'web_scrape'],
                'supported_formats' => ['plain', 'json', 'srt', 'article'],
                'providers' => ['brightdata', 'smartproxy'],
            ]
        ]);

        // Register File Operations Module (PDF microservice - convert, extract, edit operations)
        self::registerModule('file_operations', [
            'class' => FileOperationsModule::class,
            'description' => 'File manipulation: document conversion, PDF operations, content extraction via PDF microservice',
            'dependencies' => [],
            'config' => [
                'api_url' => config('services.document_converter.url'),
                'api_key' => config('services.document_converter.api_key'),
                'timeout' => config('services.document_converter.timeout'),
                'supported_operations' => [
                    // Conversion
                    'convert', 'extract',
                    // PDF Operations
                    'merge', 'split', 'compress', 'watermark', 'page_numbers',
                    'annotate', 'protect', 'unlock', 'preview', 'batch', 'edit_pdf'
                ],
                'supported_formats' => [
                    'input' => ['pdf', 'docx', 'doc', 'jpg', 'jpeg', 'png', 'gif', 'html', 'txt'],
                    'output' => ['pdf', 'png', 'jpg', 'jpeg', 'docx', 'txt', 'html']
                ]
            ]
        ]);

        // Register Universal File Management Module
        self::registerModule('universal_file_management', [
            'class' => UniversalFileManagementModule::class,
            'description' => 'Universal file upload, processing, and management for all AI tools',
            'dependencies' => ['content_extraction', 'ai_processing', 'transcriber'],
            'config' => [
                'supported_types' => ['pdf', 'doc', 'docx', 'txt', 'audio', 'video', 'image'],
                'max_file_size' => '100MB',
                'processing_pipeline' => ['upload', 'extract', 'process', 'store'],
                'supported_tools' => ['summarize', 'math', 'document_chat', 'flashcards', 'presentations']
            ]
        ]);

        // Register Subscription Billing Module
        self::registerModule('subscription_billing', [
            'class' => SubscriptionBillingModule::class,
            'description' => 'Subscription and billing management with Stripe integration',
            'dependencies' => [],
            'config' => [
                'grace_period_days' => 3,
                'usage_alert_thresholds' => [80, 100],
                'stripe_enabled' => true,
                'monthly_billing_cycle' => true,
                'auto_reset_usage' => true,
            ]
        ]);

        // Register Document Intelligence Module
        self::registerModule('document_intelligence', [
            'class' => DocumentIntelligenceModule::class,
            'description' => 'Document ingestion, semantic search, RAG-powered Q&A, and conversational chat',
            'dependencies' => [],
            'config' => [
                'api_url' => config('services.document_intelligence.url'),
                'tenant' => config('services.document_intelligence.tenant'),
                'timeout' => config('services.document_intelligence.timeout'),
                'supported_actions' => ['ingest', 'search', 'answer', 'chat'],
                'supported_llm_models' => ['llama3', 'deepseek-chat', 'mistral:latest', 'gpt-4'],
                'default_ocr' => 'auto',
                'default_language' => 'eng',
                'default_force_fallback' => true,
            ]
        ]);

        // Register Flashcard Module
        self::registerModule('flashcard', [
            'class' => FlashcardModule::class,
            'description' => 'AI-powered flashcard generation from text, files, URLs, and YouTube videos',
            'dependencies' => ['ai_processing', 'document_intelligence', 'transcriber', 'universal_file_management'],
            'config' => [
                'supported_input_types' => ['text', 'file', 'url', 'youtube'],
                'supported_difficulties' => ['beginner', 'intermediate', 'advanced'],
                'supported_styles' => ['definition', 'application', 'analysis', 'comparison', 'mixed'],
                'min_count' => 1,
                'max_count' => 40,
                'default_count' => 5,
                'default_difficulty' => 'intermediate',
                'default_style' => 'mixed',
                'content_validation' => [
                    'min_words' => 5,
                    'max_words' => 50000
                ]
            ]
        ]);

        // Register SMS Gateway Module
        self::registerModule('sms_gateway', [
            'class' => \App\Services\SmsGatewayService::class,
            'description' => 'Universal SMS gateway for OTP, transactional, marketing, alert, and service messages',
            'dependencies' => [],
            'config' => [
                'api_url' => config('services.sms_gateway.url'),
                'client_id' => config('services.sms_gateway.client_id'),
                'timeout' => config('services.sms_gateway.timeout'),
                'supported_types' => ['otp', 'transactional', 'marketing', 'alert', 'service'],
                'providers' => ['twilio', 'local'],
                'idempotency_enabled' => true,
                'multi_app' => ['zooys', 'akili', 'dagu'],
            ]
        ]);
    }

    /**
     * Register a module
     */
    public static function registerModule($name, $config)
    {
        self::$modules[$name] = array_merge([
            'enabled' => true,
            'priority' => 100,
            'version' => '1.0.0',
        ], $config);
        
        Log::info("Registered module: {$name}");
    }

    /**
     * Get a module instance
     */
    public static function getModule($name)
    {
        if (!isset(self::$modules[$name])) {
            throw new \Exception("Module '{$name}' not found");
        }

        $module = self::$modules[$name];
        
        if (!$module['enabled']) {
            throw new \Exception("Module '{$name}' is disabled");
        }

        return app($module['class']);
    }

    /**
     * Get module configuration
     */
    public static function getModuleConfig($name)
    {
        if (!isset(self::$modules[$name])) {
            return null;
        }

        return self::$modules[$name]['config'] ?? [];
    }

    /**
     * Get all registered modules
     */
    public static function getAllModules()
    {
        return self::$modules;
    }

    /**
     * Get enabled modules
     */
    public static function getEnabledModules()
    {
        return array_filter(self::$modules, function($module) {
            return $module['enabled'];
        });
    }

    /**
     * Enable a module
     */
    public static function enableModule($name)
    {
        if (isset(self::$modules[$name])) {
            self::$modules[$name]['enabled'] = true;
            Log::info("Enabled module: {$name}");
        }
    }

    /**
     * Disable a module
     */
    public static function disableModule($name)
    {
        if (isset(self::$modules[$name])) {
            self::$modules[$name]['enabled'] = false;
            Log::info("Disabled module: {$name}");
        }
    }

    /**
     * Check if module exists
     */
    public static function hasModule($name)
    {
        return isset(self::$modules[$name]);
    }

    /**
     * Check if module is enabled
     */
    public static function isModuleEnabled($name)
    {
        return isset(self::$modules[$name]) && self::$modules[$name]['enabled'];
    }

    /**
     * Get module dependencies
     */
    public static function getModuleDependencies($name)
    {
        if (!isset(self::$modules[$name])) {
            return [];
        }

        return self::$modules[$name]['dependencies'] ?? [];
    }

    /**
     * Validate module dependencies
     */
    public static function validateDependencies($name)
    {
        $dependencies = self::getModuleDependencies($name);
        $missing = [];

        foreach ($dependencies as $dependency) {
            if (!self::hasModule($dependency) || !self::isModuleEnabled($dependency)) {
                $missing[] = $dependency;
            }
        }

        return $missing;
    }

    /**
     * Get module statistics
     */
    public static function getModuleStats()
    {
        $total = count(self::$modules);
        $enabled = count(self::getEnabledModules());
        $disabled = $total - $enabled;

        return [
            'total_modules' => $total,
            'enabled_modules' => $enabled,
            'disabled_modules' => $disabled,
            'modules' => array_keys(self::$modules),
        ];
    }
}
