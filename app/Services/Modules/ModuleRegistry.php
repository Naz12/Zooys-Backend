<?php

namespace App\Services\Modules;

use Illuminate\Support\Facades\Log;

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
        self::registerModule('content_chunking', [
            'class' => ContentChunkingService::class,
            'description' => 'Smart content chunking for large texts',
            'dependencies' => [],
            'config' => [
                'max_chunk_size' => 3000,
                'overlap_size' => 200,
                'min_chunk_size' => 500,
            ]
        ]);

        self::registerModule('ai_summarization', [
            'class' => AISummarizationService::class,
            'description' => 'AI-powered content summarization',
            'dependencies' => ['content_chunking', 'ai_processing'],
            'config' => [
                'max_tokens' => 1000,
                'temperature' => 0.7,
            ]
        ]);

        self::registerModule('content_extraction', [
            'class' => ContentExtractionService::class,
            'description' => 'Unified content extraction from various sources',
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
            'dependencies' => ['content_extraction', 'transcription'],
            'config' => [
                'api_key' => config('services.youtube.api_key'),
                'transcriber_enabled' => true,
            ]
        ]);

        // Register PDF module
        self::registerModule('pdf', [
            'class' => \App\Services\PythonPDFProcessingService::class,
            'description' => 'PDF document processing and text extraction',
            'dependencies' => ['content_extraction'],
            'config' => [
                'max_file_size' => '10MB',
                'supported_formats' => ['pdf'],
            ]
        ]);

        // Register Web Scraping module
        self::registerModule('web_scraping', [
            'class' => \App\Services\WebScrapingService::class,
            'description' => 'Web content scraping and extraction',
            'dependencies' => ['content_extraction'],
            'config' => [
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (compatible; AIBot/1.0)',
            ]
        ]);

        // Register AI Maths module
        self::registerModule('ai_math', [
            'class' => \App\Services\AIMathService::class,
            'description' => 'AI-powered mathematical problem solving',
            'dependencies' => [],
            'config' => [
                'supported_subjects' => ['algebra', 'geometry', 'calculus', 'statistics', 'trigonometry', 'arithmetic'],
                'difficulty_levels' => ['beginner', 'intermediate', 'advanced'],
                'max_image_size' => '10MB',
                'supported_formats' => ['text', 'image'],
            ]
        ]);

        // Register AI Presentation module
        self::registerModule('ai_presentation', [
            'class' => \App\Services\AIPresentationService::class,
            'description' => 'AI-powered presentation generation with PowerPoint creation',
            'dependencies' => ['content_extraction'],
            'config' => [
                'supported_input_types' => ['text', 'file', 'url', 'youtube'],
                'supported_templates' => ['corporate_blue', 'modern_white', 'creative_colorful', 'minimalist_gray', 'academic_formal'],
                'supported_languages' => ['English', 'Spanish', 'French', 'German', 'Italian', 'Portuguese', 'Chinese', 'Japanese'],
                'supported_tones' => ['Professional', 'Casual', 'Academic', 'Creative', 'Formal'],
                'supported_lengths' => ['Short', 'Medium', 'Long'],
                'python_script_path' => 'python/generate_presentation.py',
            ]
        ]);

        // Register AI Processing Module
        self::registerModule('ai_processing', [
            'class' => AIProcessingModule::class,
            'description' => 'AI text processing via AI API Manager microservice',
            'dependencies' => [],
            'config' => [
                'api_url' => config('services.ai_manager.url'),
                'timeout' => config('services.ai_manager.timeout'),
                'supported_tasks' => ['summarize', 'generate', 'qa', 'translate', 'sentiment', 'code-review'],
            ]
        ]);

        // Register Transcription Module
        self::registerModule('transcription', [
            'class' => TranscriptionModule::class,
            'description' => 'YouTube video transcription via Transcriber microservice',
            'dependencies' => [],
            'config' => [
                'api_url' => config('services.youtube_transcriber.url'),
                'timeout' => config('services.youtube_transcriber.timeout'),
                'supported_formats' => ['plain', 'json', 'srt', 'article'],
            ]
        ]);

        // Register Universal File Management Module
        self::registerModule('universal_file_management', [
            'class' => UniversalFileManagementModule::class,
            'description' => 'Universal file upload, processing, and management for all AI tools',
            'dependencies' => ['content_extraction', 'ai_processing', 'transcription'],
            'config' => [
                'supported_types' => ['pdf', 'doc', 'docx', 'txt', 'audio', 'video', 'image'],
                'max_file_size' => '100MB',
                'processing_pipeline' => ['upload', 'extract', 'process', 'store'],
                'supported_tools' => ['summarize', 'math', 'document_chat', 'flashcards', 'presentations']
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
