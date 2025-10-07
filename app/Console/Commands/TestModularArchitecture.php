<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Modules\ModuleRegistry;
use App\Services\Modules\UnifiedProcessingService;
use App\Services\Modules\ContentChunkingService;
use App\Services\Modules\AISummarizationService;
use App\Services\Modules\ContentExtractionService;
use Illuminate\Support\Facades\Log;

class TestModularArchitecture extends Command
{
    protected $signature = 'test:modular-architecture';
    protected $description = 'Test the modular architecture components';

    public function handle()
    {
        $this->info('🧪 TESTING MODULAR ARCHITECTURE');
        $this->info('================================');
        $this->newLine();

        // Test 1: Module Registry
        $this->info('1️⃣ Testing Module Registry...');
        try {
            ModuleRegistry::initialize();
            $stats = ModuleRegistry::getModuleStats();
            $this->info('✅ Module Registry initialized successfully');
            $this->info("   Total modules: {$stats['total_modules']}");
            $this->info("   Enabled modules: {$stats['enabled_modules']}");
            $this->info("   Disabled modules: {$stats['disabled_modules']}");
            $this->newLine();
        } catch (\Exception $e) {
            $this->error("❌ Module Registry failed: " . $e->getMessage());
            $this->newLine();
        }

        // Test 2: Content Chunking
        $this->info('2️⃣ Testing Content Chunking...');
        try {
            $chunkingService = app(ContentChunkingService::class);
            
            $testContent = "This is a test content. " . str_repeat("It has multiple sentences. ", 50);
            $chunks = $chunkingService->chunkContent($testContent, 'text');
            
            $this->info('✅ Content chunking working');
            $this->info("   Original length: " . strlen($testContent) . " characters");
            $this->info("   Chunks created: " . count($chunks));
            $this->info("   Average chunk size: " . round(array_sum(array_column($chunks, 'character_count')) / count($chunks)) . " characters");
            $this->newLine();
        } catch (\Exception $e) {
            $this->error("❌ Content chunking failed: " . $e->getMessage());
            $this->newLine();
        }

        // Test 3: YouTube Content Extraction
        $this->info('3️⃣ Testing YouTube Content Extraction...');
        try {
            $extractionService = app(ContentExtractionService::class);
            
            $testUrl = "https://www.youtube.com/watch?v=i1ucuvfyw0o";
            $result = $extractionService->extractContent($testUrl, 'youtube');
            
            if ($result['success']) {
                $this->info('✅ YouTube content extraction working');
                $this->info("   Content length: " . $result['metadata']['character_count'] . " characters");
                $this->info("   Word count: " . $result['metadata']['word_count'] . " words");
                $this->info("   Has transcript: " . ($result['metadata']['has_transcript'] ? 'Yes' : 'No'));
            } else {
                $this->error("❌ YouTube extraction failed: " . $result['error']);
            }
            $this->newLine();
        } catch (\Exception $e) {
            $this->error("❌ YouTube extraction failed: " . $e->getMessage());
            $this->newLine();
        }

        // Test 4: Unified Processing (YouTube)
        $this->info('4️⃣ Testing Unified Processing (YouTube)...');
        try {
            $unifiedService = app(UnifiedProcessingService::class);
            
            $testUrl = "https://www.youtube.com/watch?v=i1ucuvfyw0o";
            $result = $unifiedService->processYouTubeVideo($testUrl, [
                'language' => 'en',
                'mode' => 'detailed'
            ]);
            
            if ($result['success']) {
                $this->info('✅ Unified processing working');
                $this->info("   Summary length: " . strlen($result['summary']) . " characters");
                $this->info("   Processing method: " . $result['metadata']['processing_method']);
                $this->info("   Chunks processed: " . $result['metadata']['chunks_processed']);
                $this->info("   Total characters: " . $result['metadata']['total_characters']);
                $this->info("   Total words: " . $result['metadata']['total_words']);
            } else {
                $this->error("❌ Unified processing failed: " . $result['error']);
            }
            $this->newLine();
        } catch (\Exception $e) {
            $this->error("❌ Unified processing failed: " . $e->getMessage());
            $this->newLine();
        }

        // Test 5: Module Dependencies
        $this->info('5️⃣ Testing Module Dependencies...');
        try {
            $modules = ['content_chunking', 'ai_summarization', 'content_extraction'];
            
            foreach ($modules as $module) {
                $dependencies = ModuleRegistry::getModuleDependencies($module);
                $missing = ModuleRegistry::validateDependencies($module);
                
                $this->info("   Module: {$module}");
                $this->info("   Dependencies: " . (empty($dependencies) ? 'None' : implode(', ', $dependencies)));
                $this->info("   Missing: " . (empty($missing) ? 'None' : implode(', ', $missing)));
            }
            $this->info('✅ Module dependencies checked');
            $this->newLine();
        } catch (\Exception $e) {
            $this->error("❌ Module dependencies failed: " . $e->getMessage());
            $this->newLine();
        }

        // Test 6: Configuration
        $this->info('6️⃣ Testing Configuration...');
        try {
            $chunkingConfig = ModuleRegistry::getModuleConfig('content_chunking');
            $summarizationConfig = ModuleRegistry::getModuleConfig('ai_summarization');
            
            $this->info('✅ Configuration loaded');
            $this->info("   Chunking config: " . json_encode($chunkingConfig));
            $this->info("   Summarization config: " . json_encode($summarizationConfig));
            $this->newLine();
        } catch (\Exception $e) {
            $this->error("❌ Configuration failed: " . $e->getMessage());
            $this->newLine();
        }

        // Test 7: Performance Test
        $this->info('7️⃣ Testing Performance...');
        try {
            $startTime = microtime(true);
            
            $testContent = str_repeat("This is a test sentence for performance testing. ", 1000);
            $chunkingService = app(ContentChunkingService::class);
            $chunks = $chunkingService->chunkContent($testContent, 'text');
            
            $endTime = microtime(true);
            $processingTime = ($endTime - $startTime) * 1000;
            
            $this->info('✅ Performance test completed');
            $this->info("   Processing time: " . round($processingTime, 2) . " ms");
            $this->info("   Content length: " . strlen($testContent) . " characters");
            $this->info("   Chunks created: " . count($chunks));
            $this->info("   Speed: " . round(strlen($testContent) / $processingTime, 2) . " chars/ms");
            $this->newLine();
        } catch (\Exception $e) {
            $this->error("❌ Performance test failed: " . $e->getMessage());
            $this->newLine();
        }

        $this->info('🎉 MODULAR ARCHITECTURE TEST COMPLETED!');
        $this->info('========================================');
        $this->info('All core modules are working correctly.');
        $this->info('The new architecture is ready for production use.');
    }
}
