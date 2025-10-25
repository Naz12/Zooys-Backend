<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\YouTubeTranscriberService;

echo "=== Testing with Different Video Types ===\n\n";

$transcriberService = app(YouTubeTranscriberService::class);

// Test with different types of videos
$testVideos = [
    'https://www.youtube.com/watch?v=jNQXAC9IVRw', // "Me at the zoo" - first YouTube video
    'https://www.youtube.com/watch?v=9bZkp7q19f0', // PSY - GANGNAM STYLE (Official Video)
    'https://www.youtube.com/watch?v=dQw4w9WgXcQ', // Rick Astley - Never Gonna Give You Up
];

foreach ($testVideos as $index => $videoUrl) {
    echo "=== TEST " . ($index + 1) . ": " . basename($videoUrl) . " ===\n";
    echo "URL: {$videoUrl}\n";
    
    $startTime = microtime(true);
    
    try {
        // Try with article format first (simpler)
        echo "Testing with article format...\n";
        $result = $transcriberService->transcribe($videoUrl, [
            'format' => 'article',
            'language' => 'auto'
        ]);
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        echo "Duration: {$duration} seconds\n";
        
        if ($result['success']) {
            echo "✅ Success with article format!\n";
            echo "   Video ID: " . ($result['video_id'] ?? 'Unknown') . "\n";
            echo "   Language: " . ($result['language'] ?? 'Unknown') . "\n";
            echo "   Content Length: " . strlen($result['subtitle_text'] ?? '') . " characters\n";
            echo "   Word Count: " . str_word_count($result['subtitle_text'] ?? '') . " words\n";
            
            if (!empty($result['subtitle_text'])) {
                echo "\n📝 Content Preview (first 200 characters):\n";
                echo substr($result['subtitle_text'], 0, 200) . "...\n";
            }
            
            // If article format works, try bundle format
            echo "\nTesting with bundle format...\n";
            $bundleResult = $transcriberService->transcribe($videoUrl, [
                'format' => 'bundle',
                'language' => 'auto'
            ]);
            
            if ($bundleResult['success']) {
                echo "✅ Bundle format also works!\n";
                if (isset($bundleResult['article'])) {
                    echo "   Article Length: " . strlen($bundleResult['article']) . " characters\n";
                }
                if (isset($bundleResult['json'])) {
                    echo "   JSON Segments: " . count($bundleResult['json']['segments'] ?? []) . "\n";
                }
            } else {
                echo "❌ Bundle format failed: " . ($bundleResult['error'] ?? 'Unknown error') . "\n";
            }
            
            break; // Found a working video, stop testing
            
        } else {
            echo "❌ Failed: " . ($result['error'] ?? 'Unknown error') . "\n";
        }
        
    } catch (\Exception $e) {
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        echo "❌ Exception after {$duration} seconds: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat("-", 80) . "\n\n";
}

echo "=== VIDEO TESTING COMPLETE ===\n";









