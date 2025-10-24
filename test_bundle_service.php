<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\YouTubeTranscriberService;

echo "=== Testing Updated YouTubeTranscriberService with Bundle Format ===\n\n";

$transcriberService = app(YouTubeTranscriberService::class);

// Test with the video that we know works
$videoUrl = 'https://www.youtube.com/watch?v=phlTuEnBdco';

echo "Testing with video: {$videoUrl}\n";
echo "Format: bundle\n\n";

$startTime = microtime(true);

try {
    $result = $transcriberService->transcribe($videoUrl, [
        'format' => 'bundle',
        'language' => 'auto'
    ]);
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    echo "Duration: {$duration} seconds\n\n";
    
    if ($result['success']) {
        echo "✅ SUCCESS!\n";
        echo "   Video ID: " . ($result['video_id'] ?? 'Unknown') . "\n";
        echo "   Language: " . ($result['language'] ?? 'Unknown') . "\n";
        echo "   Format: " . ($result['format'] ?? 'Unknown') . "\n";
        echo "   Article Length: " . strlen($result['article'] ?? '') . " characters\n";
        echo "   Word Count: " . str_word_count($result['article'] ?? '') . " words\n";
        
        if (isset($result['json']['segments'])) {
            echo "   JSON Segments: " . count($result['json']['segments']) . "\n";
            
            // Show first few segments
            echo "\n📝 First 3 JSON Segments:\n";
            $segments = array_slice($result['json']['segments'], 0, 3);
            foreach ($segments as $i => $segment) {
                echo "   " . ($i + 1) . ". [" . round($segment['start'], 1) . "s - " . round($segment['start'] + $segment['duration'], 1) . "s] " . substr($segment['text'], 0, 100) . "...\n";
            }
        }
        
        if (!empty($result['article'])) {
            echo "\n📄 Article Preview (first 300 characters):\n";
            echo substr($result['article'], 0, 300) . "...\n";
        }
        
        if (isset($result['meta'])) {
            echo "\n📊 Meta Information:\n";
            foreach ($result['meta'] as $key => $value) {
                echo "   {$key}: {$value}\n";
            }
        }
        
    } else {
        echo "❌ FAILED: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (\Exception $e) {
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    echo "❌ Exception after {$duration} seconds: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";








