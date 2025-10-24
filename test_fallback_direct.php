<?php

require_once 'vendor/autoload.php';

use App\Services\YouTubeFallbackService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing YouTube Fallback Service Directly\n";
echo "==========================================\n\n";

// Test the fallback service directly
$videoUrl = 'https://www.youtube.com/watch?v=tXGooH_cbGA';

echo "🎥 Testing Video: {$videoUrl}\n";
echo "📋 Strategy: Direct fallback service test\n";
echo str_repeat("-", 80) . "\n";

try {
    $fallbackService = new YouTubeFallbackService();
    
    echo "📡 Testing YouTubeFallbackService directly...\n";
    
    $result = $fallbackService->processYouTubeVideo($videoUrl, [
        'format' => 'bundle',
        'language' => 'en'
    ], 1);
    
    if ($result['success']) {
        echo "✅ YouTube fallback processing successful!\n";
        echo "Video ID: " . ($result['video_id'] ?? 'N/A') . "\n";
        echo "Language: " . ($result['language'] ?? 'N/A') . "\n";
        echo "Format: " . ($result['format'] ?? 'N/A') . "\n";
        echo "Article Length: " . strlen($result['article'] ?? '') . " characters\n";
        echo "Summary Length: " . strlen($result['summary'] ?? '') . " characters\n";
        
        echo "\n📋 Article Content:\n";
        echo substr($result['article'], 0, 200) . "...\n";
        
        echo "\n📋 Summary:\n";
        echo $result['summary'] . "\n";
        
        echo "\n📊 Metadata:\n";
        foreach ($result['meta'] as $key => $value) {
            echo "{$key}: " . (is_array($value) ? json_encode($value) : $value) . "\n";
        }
        
        echo "\n📊 JSON Segments:\n";
        echo "Count: " . count($result['json']['segments'] ?? []) . "\n";
        if (!empty($result['json']['segments'])) {
            echo "First segment: " . json_encode($result['json']['segments'][0]) . "\n";
        }
        
    } else {
        echo "❌ YouTube fallback processing failed!\n";
        echo "Error: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception occurred: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "💡 FALLBACK SERVICE TEST\n";
echo "=======================\n";
echo "✅ If successful: Fallback service is working independently\n";
echo "❌ If failed: Check AI Manager service and configuration\n";
echo "🔍 This bypasses the transcriber completely\n";


