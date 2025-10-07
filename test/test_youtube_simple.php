<?php

use App\Services\Modules\UnifiedProcessingService;
use App\Services\Modules\ContentExtractionService;

echo "🧪 TESTING YOUTUBE PROCESSING SIMPLIFIED\n";
echo "========================================\n\n";

try {
    echo "1️⃣ Testing Content Extraction...\n";
    $extractionService = app(ContentExtractionService::class);
    
    $testUrl = "https://www.youtube.com/watch?v=i1ucuvfyw0o";
    $result = $extractionService->extractContent($testUrl, 'youtube');
    
    if ($result['success']) {
        echo "✅ Content extraction working\n";
        echo "   Content length: " . $result['metadata']['character_count'] . " characters\n";
        echo "   Word count: " . $result['metadata']['word_count'] . " words\n";
        echo "   Has transcript: " . ($result['metadata']['has_transcript'] ? 'Yes' : 'No') . "\n";
    } else {
        echo "❌ Content extraction failed: " . $result['error'] . "\n";
    }
    
    echo "\n2️⃣ Testing Unified Processing...\n";
    $unifiedService = app(UnifiedProcessingService::class);
    
    $result = $unifiedService->processYouTubeVideo($testUrl, [
        'language' => 'en',
        'mode' => 'detailed'
    ]);
    
    if ($result['success']) {
        echo "✅ Unified processing working\n";
        echo "   Summary length: " . strlen($result['summary']) . " characters\n";
        echo "   Processing method: " . $result['metadata']['processing_method'] . "\n";
        echo "   Chunks processed: " . $result['metadata']['chunks_processed'] . "\n";
    } else {
        echo "❌ Unified processing failed: " . $result['error'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n🎉 TEST COMPLETED!\n";
