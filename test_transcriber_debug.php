<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use App\Services\YouTubeTranscriberService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Debugging YouTubeTranscriberService\n";
echo "=====================================\n\n";

// Test the transcriber service step by step
$videoUrl = 'https://www.youtube.com/watch?v=tXGooH_cbGA';

echo "🎥 Testing Video: {$videoUrl}\n";
echo str_repeat("-", 80) . "\n";

try {
    $transcriber = new YouTubeTranscriberService();
    
    echo "📡 Testing Smartproxy availability...\n";
    $isAvailable = $transcriber->isSmartproxyAvailable();
    echo "Smartproxy Available: " . ($isAvailable ? 'YES' : 'NO') . "\n\n";
    
    echo "📡 Testing Smartproxy method directly...\n";
    $smartproxyResult = $transcriber->transcribeWithSmartproxy($videoUrl, ['format' => 'bundle']);
    echo "Smartproxy Result:\n";
    echo "Success: " . ($smartproxyResult['success'] ? 'true' : 'false') . "\n";
    if (!$smartproxyResult['success']) {
        echo "Error: " . ($smartproxyResult['error'] ?? 'Unknown error') . "\n";
    } else {
        echo "Video ID: " . ($smartproxyResult['video_id'] ?? 'N/A') . "\n";
        echo "Article Length: " . strlen($smartproxyResult['article'] ?? '') . " characters\n";
    }
    echo "\n";
    
    echo "📡 Testing main transcribe method...\n";
    $mainResult = $transcriber->transcribe($videoUrl, ['format' => 'bundle']);
    echo "Main Result:\n";
    echo "Success: " . ($mainResult['success'] ? 'true' : 'false') . "\n";
    if (!$mainResult['success']) {
        echo "Error: " . ($mainResult['error'] ?? 'Unknown error') . "\n";
    } else {
        echo "Video ID: " . ($mainResult['video_id'] ?? 'N/A') . "\n";
        echo "Article Length: " . strlen($mainResult['article'] ?? '') . " characters\n";
    }
    echo "\n";
    
    // Check configuration
    echo "🔧 Configuration Check:\n";
    echo "API URL: " . config('services.youtube_transcriber.url') . "\n";
    echo "Client Key: " . config('services.youtube_transcriber.client_key') . "\n";
    echo "Timeout: " . config('services.youtube_transcriber.timeout') . "\n";
    echo "Default Format: " . config('services.youtube_transcriber.default_format') . "\n\n";
    
    // Test direct HTTP call to Smartproxy
    echo "📡 Testing direct HTTP call to Smartproxy...\n";
    $apiUrl = config('services.youtube_transcriber.url');
    $clientKey = config('services.youtube_transcriber.client_key');
    
    $response = Http::timeout(600)
        ->connectTimeout(30)
        ->withHeaders([
            'Accept' => 'application/json',
            'X-Client-Key' => $clientKey,
        ])
        ->get($apiUrl . '/scraper/smartproxy/subtitles', [
            'url' => $videoUrl,
            'format' => 'bundle'
        ]);
    
    echo "Direct HTTP Status: " . $response->status() . "\n";
    echo "Direct HTTP Success: " . ($response->successful() ? 'YES' : 'NO') . "\n";
    
    if ($response->successful()) {
        $data = $response->json();
        echo "Direct HTTP Video ID: " . ($data['video_id'] ?? 'N/A') . "\n";
        echo "Direct HTTP Article Length: " . strlen($data['article_text'] ?? '') . " characters\n";
    } else {
        echo "Direct HTTP Error: " . $response->body() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception occurred: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "💡 SUMMARY\n";
echo "==========\n";
echo "✅ If Smartproxy works: The service should work in jobs\n";
echo "❌ If Smartproxy fails: There's a configuration issue\n";
echo "🔍 Check the error messages above to identify the problem\n";



