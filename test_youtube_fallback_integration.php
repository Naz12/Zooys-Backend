<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use App\Services\Modules\UnifiedProcessingService;
use App\Services\ContentExtractionService;
use App\Services\ContentChunkingService;
use App\Services\AISummarizationService;
use App\Services\AIResultService;
use App\Services\Modules\ModuleRegistry;
use App\Services\YouTubeTranscriberService;
use App\Services\AIManagerService;
use App\Services\YouTubeFallbackService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing YouTube Fallback Integration\n";
echo "=====================================\n\n";

// Test the updated UnifiedProcessingService with fallback
$videoUrl = 'https://www.youtube.com/watch?v=tXGooH_cbGA';

echo "🎥 Testing Video: {$videoUrl}\n";
echo "📋 Strategy: UnifiedProcessingService with fallback\n";
echo str_repeat("-", 80) . "\n";

try {
    // Create service instances
    $contentExtractionService = new ContentExtractionService();
    $contentChunkingService = new ContentChunkingService();
    $aiSummarizationService = new AISummarizationService();
    $aiResultService = new AIResultService();
    $moduleRegistry = new ModuleRegistry();
    $youtubeTranscriberService = new YouTubeTranscriberService();
    $aiManagerService = new AIManagerService();
    $youtubeFallbackService = new YouTubeFallbackService();
    
    // Create UnifiedProcessingService
    $unifiedService = new UnifiedProcessingService(
        $contentExtractionService,
        $contentChunkingService,
        $aiSummarizationService,
        $aiResultService,
        $moduleRegistry,
        $youtubeTranscriberService,
        $aiManagerService,
        $youtubeFallbackService
    );
    
    echo "📡 Testing UnifiedProcessingService with fallback...\n";
    
    $result = $unifiedService->processYouTubeVideo($videoUrl, [
        'format' => 'bundle',
        'language' => 'en',
        'mode' => 'detailed'
    ], 1);
    
    if ($result['success']) {
        echo "✅ YouTube processing successful!\n";
        echo "Video ID: " . ($result['metadata']['video_id'] ?? 'N/A') . "\n";
        echo "Title: " . ($result['metadata']['title'] ?? 'N/A') . "\n";
        echo "Channel: " . ($result['metadata']['channel'] ?? 'N/A') . "\n";
        echo "Processing Method: " . ($result['metadata']['processing_method'] ?? 'N/A') . "\n";
        echo "Summary Length: " . strlen($result['summary'] ?? '') . " characters\n";
        echo "AI Model Used: " . ($result['metadata']['ai_model_used'] ?? 'N/A') . "\n";
        echo "AI Tokens Used: " . ($result['metadata']['ai_tokens_used'] ?? 0) . "\n";
        
        echo "\n📋 Summary:\n";
        echo $result['summary'] . "\n";
        
        echo "\n📊 Bundle Data:\n";
        echo "Article Length: " . strlen($result['bundle']['article'] ?? '') . " characters\n";
        echo "JSON Segments: " . count($result['bundle']['json']['segments'] ?? []) . "\n";
        
    } else {
        echo "❌ YouTube processing failed!\n";
        echo "Error: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception occurred: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "💡 FALLBACK INTEGRATION TEST\n";
echo "============================\n";
echo "✅ If successful: Fallback strategy is working\n";
echo "❌ If failed: Check service dependencies and configuration\n";
echo "🔍 The fallback should activate when transcriber fails\n";

