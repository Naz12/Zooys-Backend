<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing YouTube Tool with: https://www.youtube.com/watch?v=SUMa2uj6v9w\n\n";

$videoUrl = 'https://www.youtube.com/watch?v=SUMa2uj6v9w';
$videoId = 'SUMa2uj6v9w';

try {
    echo "=== STEP 1: Testing YouTube Transcriber Service ===\n";
    $transcriberService = app(\App\Services\YouTubeTranscriberService::class);
    
    echo "Making request to YouTube Transcriber...\n";
    $transcriberResult = $transcriberService->transcribe($videoUrl, [
        'format' => 'article',
        'headings' => true
    ]);
    
    if ($transcriberResult['success']) {
        echo "✅ YouTube Transcriber: SUCCESS\n";
        echo "   Video ID: " . ($transcriberResult['video_id'] ?? 'unknown') . "\n";
        echo "   Language: " . ($transcriberResult['language'] ?? 'unknown') . "\n";
        echo "   Content length: " . strlen($transcriberResult['subtitle_text'] ?? '') . " characters\n";
        if (!empty($transcriberResult['subtitle_text'])) {
            echo "   First 300 chars: " . substr($transcriberResult['subtitle_text'], 0, 300) . "...\n";
        }
    } else {
        echo "❌ YouTube Transcriber: FAILED\n";
        echo "   Error: " . ($transcriberResult['error'] ?? 'Unknown error') . "\n";
    }
    
    echo "\n=== STEP 2: Testing AI Manager Service ===\n";
    $aiService = app(\App\Services\AIManagerService::class);
    
    if ($transcriberResult['success'] && !empty($transcriberResult['subtitle_text'])) {
        echo "Summarizing transcript with AI Manager...\n";
        $summaryResult = $aiService->processText($transcriberResult['subtitle_text'], 'summarize');
        
        if ($summaryResult['success']) {
            echo "✅ AI Manager Summarization: SUCCESS\n";
            echo "   Model used: " . ($summaryResult['model_used'] ?? 'unknown') . "\n";
            echo "   Summary: " . ($summaryResult['insights'] ?? 'N/A') . "\n";
        } else {
            echo "❌ AI Manager Summarization: FAILED\n";
            echo "   Error: " . ($summaryResult['error'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "⚠️  Skipping AI Manager test - no transcript available\n";
    }
    
    echo "\n=== STEP 3: Testing Complete YouTube Service Flow ===\n";
    $youtubeService = app(\App\Services\YouTubeService::class);
    
    echo "Testing YouTube Service getVideoContentWithCaptions...\n";
    $transcript = $youtubeService->getVideoContentWithCaptions($videoId);
    
    if ($transcript) {
        echo "✅ YouTube Service: SUCCESS\n";
        echo "   Transcript length: " . strlen($transcript) . " characters\n";
        echo "   First 300 chars: " . substr($transcript, 0, 300) . "...\n";
    } else {
        echo "❌ YouTube Service: FAILED - No transcript returned\n";
    }
    
    echo "\n=== STEP 4: Testing Content Extraction Service ===\n";
    $contentExtractionService = app(\App\Services\Modules\ContentExtractionService::class);
    
    echo "Testing ContentExtractionService extractContent...\n";
    $extractionResult = $contentExtractionService->extractContent($videoUrl, 'youtube');
    
    if ($extractionResult['success']) {
        echo "✅ ContentExtractionService: SUCCESS\n";
        echo "   Content length: " . strlen($extractionResult['content']) . " characters\n";
        echo "   Word count: " . $extractionResult['metadata']['word_count'] . "\n";
        echo "   First 300 chars: " . substr($extractionResult['content'], 0, 300) . "...\n";
    } else {
        echo "❌ ContentExtractionService: FAILED\n";
        echo "   Error: " . $extractionResult['error'] . "\n";
    }
    
    echo "\n=== STEP 5: Testing Unified Processing Service ===\n";
    $unifiedService = app(\App\Services\Modules\UnifiedProcessingService::class);
    
    echo "Testing UnifiedProcessingService processYouTubeVideo...\n";
    $unifiedResult = $unifiedService->processYouTubeVideo($videoUrl, [
        'language' => 'en',
        'mode' => 'detailed'
    ]);
    
    if ($unifiedResult['success']) {
        echo "✅ UnifiedProcessingService: SUCCESS\n";
        echo "   Summary length: " . strlen($unifiedResult['summary'] ?? '') . " characters\n";
        echo "   Summary: " . substr($unifiedResult['summary'] ?? '', 0, 300) . "...\n";
    } else {
        echo "❌ UnifiedProcessingService: FAILED\n";
        echo "   Error: " . $unifiedResult['error'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== TEST COMPLETED ===\n";

