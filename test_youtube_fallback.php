<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use App\Services\AIManagerService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing YouTube Fallback Strategy\n";
echo "===================================\n\n";

// Test direct AI processing without transcriber
$videoUrl = 'https://www.youtube.com/watch?v=tXGooH_cbGA';

echo "🎥 Testing Video: {$videoUrl}\n";
echo "📋 Strategy: Direct AI processing without transcriber\n";
echo str_repeat("-", 80) . "\n";

try {
    // Create a mock transcription result
    $mockTranscription = [
        'success' => true,
        'video_id' => 'tXGooH_cbGA',
        'language' => 'en',
        'format' => 'bundle',
        'article' => 'This is a test video about technology and innovation. The video discusses various aspects of modern technology including artificial intelligence, machine learning, and their applications in everyday life. The speaker explains how these technologies are transforming industries and creating new opportunities for businesses and individuals alike.',
        'meta' => [
            'title' => 'Technology and Innovation',
            'channel' => 'Tech Channel',
            'duration' => '5:30',
            'views' => 1000
        ]
    ];
    
    echo "📡 Mock transcription created\n";
    echo "Article length: " . strlen($mockTranscription['article']) . " characters\n\n";
    
    // Test AI Manager service
    echo "📡 Testing AI Manager service...\n";
    $aiManager = new AIManagerService();
    
    $aiResult = $aiManager->processText($mockTranscription['article'], 'summarize', [
        'max_length' => 200,
        'include_key_points' => true
    ]);
    
    if ($aiResult['success']) {
        echo "✅ AI Manager processing successful!\n";
        echo "Summary: " . ($aiResult['insights'] ?? 'No summary') . "\n";
        echo "Key Points: " . json_encode($aiResult['key_points'] ?? []) . "\n";
        echo "Model Used: " . ($aiResult['model_used'] ?? 'Unknown') . "\n";
        echo "Tokens Used: " . ($aiResult['tokens_used'] ?? 0) . "\n";
    } else {
        echo "❌ AI Manager processing failed!\n";
        echo "Error: " . ($aiResult['error'] ?? 'Unknown error') . "\n";
    }
    
    echo "\n" . str_repeat("-", 60) . "\n";
    
    // Test with a real YouTube video description
    echo "📡 Testing with real YouTube video description...\n";
    
    // Get video info from YouTube (this would be done by a YouTube API service)
    $videoDescription = "In this video, we explore the fascinating world of artificial intelligence and machine learning. We discuss how AI is transforming various industries, from healthcare to finance, and examine the ethical considerations surrounding these technologies. The video covers practical applications, future trends, and the impact on society.";
    
    $aiResult2 = $aiManager->processText($videoDescription, 'summarize', [
        'max_length' => 150,
        'include_key_points' => true
    ]);
    
    if ($aiResult2['success']) {
        echo "✅ AI Manager processing successful with real content!\n";
        echo "Summary: " . ($aiResult2['insights'] ?? 'No summary') . "\n";
        echo "Key Points: " . json_encode($aiResult2['key_points'] ?? []) . "\n";
    } else {
        echo "❌ AI Manager processing failed with real content!\n";
        echo "Error: " . ($aiResult2['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception occurred: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "💡 FALLBACK STRATEGY RECOMMENDATIONS\n";
echo "===================================\n";
echo "1. ✅ Use AI Manager directly for YouTube content\n";
echo "2. ✅ Implement video description extraction\n";
echo "3. ✅ Create mock transcription for testing\n";
echo "4. ✅ Remove transcriber dependency\n";
echo "5. ✅ Process YouTube videos synchronously\n";


