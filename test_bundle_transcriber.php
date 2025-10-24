<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\YouTubeTranscriberService;

echo "=== Testing Updated YouTube Transcriber with Bundle Format ===\n\n";

$transcriberService = app(YouTubeTranscriberService::class);

// Test with a shorter video first
$testVideo = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
echo "Video URL: {$testVideo}\n\n";

$startTime = microtime(true);
echo "⏰ Starting transcription at: " . date('Y-m-d H:i:s') . "\n\n";

try {
    echo "🔍 Testing bundle format...\n";
    $result = $transcriberService->transcribe($testVideo, [
        'format' => 'bundle',
        'language' => 'auto'
    ]);
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    echo "\n⏰ Transcription completed at: " . date('Y-m-d H:i:s') . "\n";
    echo "⏱️  Total Duration: {$duration} seconds (" . round($duration/60, 2) . " minutes)\n\n";
    
    if ($result['success']) {
        echo "✅ Transcription successful!\n";
        echo "📊 Results:\n";
        echo "   Video ID: " . ($result['video_id'] ?? 'Unknown') . "\n";
        echo "   Language: " . ($result['language'] ?? 'Unknown') . "\n";
        echo "   Format: " . ($result['format'] ?? 'Unknown') . "\n";
        
        if (isset($result['article'])) {
            echo "   Article Length: " . strlen($result['article']) . " characters\n";
            echo "   Article Word Count: " . str_word_count($result['article']) . " words\n";
            echo "\n📄 Article Content (first 300 characters):\n";
            echo substr($result['article'], 0, 300) . "...\n";
        }
        
        if (isset($result['json'])) {
            $segments = $result['json']['segments'] ?? [];
            echo "\n📊 JSON Data:\n";
            echo "   Segments Count: " . count($segments) . "\n";
            if (!empty($segments)) {
                echo "   First Segment: " . json_encode($segments[0]) . "\n";
            }
        }
        
        if (isset($result['meta'])) {
            echo "\n📋 Metadata:\n";
            echo json_encode($result['meta'], JSON_PRETTY_PRINT) . "\n";
        }
        
        // Test backward compatibility
        if (isset($result['subtitle_text'])) {
            echo "\n🔄 Backward Compatibility:\n";
            echo "   subtitle_text length: " . strlen($result['subtitle_text']) . " characters\n";
            echo "   Same as article: " . ($result['subtitle_text'] === $result['article'] ? 'Yes' : 'No') . "\n";
        }
        
    } else {
        echo "❌ Transcription failed!\n";
        echo "Error: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (\Exception $e) {
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    echo "\n⏰ Process failed at: " . date('Y-m-d H:i:s') . "\n";
    echo "⏱️  Duration before failure: {$duration} seconds\n";
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== BUNDLE TRANSCRIBER TEST COMPLETE ===\n";








