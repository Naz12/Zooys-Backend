<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing Transcriber Scraper with Authentication\n";
echo "==================================================\n\n";

// Get the API key from environment
$apiKey = env('YOUTUBE_TRANSCRIBER_API_KEY', 'dev-local');
echo "🔑 Using API Key: {$apiKey}\n\n";

// Test the specific URL you provided with authentication
$testUrl = "https://transcriber.akmicroservice.com/scraper/smartproxy/subtitles?url=https://www.youtube.com/watch?v=tXGooH_cbGA&format=bundle";

echo "🎥 Testing URL: {$testUrl}\n";
echo str_repeat("-", 80) . "\n";

try {
    echo "📡 Making authenticated request to transcriber...\n";
    
    $response = Http::timeout(600) // 10 minutes
        ->connectTimeout(30)
        ->withHeaders([
            'X-Client-Key' => $apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])
        ->get($testUrl);
    
    echo "📊 Response Status: " . $response->status() . "\n";
    echo "📊 Response Time: " . $response->transferStats->getHandlerStat('total_time') . " seconds\n";
    echo "📊 Response Size: " . strlen($response->body()) . " bytes\n";
    
    if ($response->successful()) {
        echo "✅ Request successful!\n";
        
        $data = $response->json();
        
        // Display key information
        echo "\n📋 RESPONSE DATA:\n";
        echo "================\n";
        
        if (isset($data['video_id'])) {
            echo "Video ID: " . $data['video_id'] . "\n";
        }
        
        if (isset($data['language'])) {
            echo "Language: " . $data['language'] . "\n";
        }
        
        if (isset($data['format'])) {
            echo "Format: " . $data['format'] . "\n";
        }
        
        if (isset($data['article_text'])) {
            $articleLength = strlen($data['article_text']);
            echo "Article Text Length: {$articleLength} characters\n";
            echo "Article Preview: " . substr($data['article_text'], 0, 300) . "...\n";
        }
        
        if (isset($data['meta'])) {
            echo "\n📊 METADATA:\n";
            echo "============\n";
            foreach ($data['meta'] as $key => $value) {
                echo "{$key}: " . (is_array($value) ? json_encode($value) : $value) . "\n";
            }
        }
        
        if (isset($data['json'])) {
            echo "\n📄 JSON DATA:\n";
            echo "=============\n";
            echo "JSON Length: " . strlen(json_encode($data['json'])) . " characters\n";
        }
        
        echo "\n✅ TRANSCRIBER SERVICE IS WORKING WITH AUTHENTICATION!\n";
        echo "The endpoint is accessible and returning data.\n";
        
    } else {
        echo "❌ Request failed!\n";
        echo "Status Code: " . $response->status() . "\n";
        echo "Error Response: " . $response->body() . "\n";
        
        if ($response->status() === 401) {
            echo "\n🔐 AUTHENTICATION ISSUE:\n";
            echo "The API key might be invalid or expired.\n";
            echo "Error: " . $response->json()['error']['message'] ?? 'Unknown error' . "\n";
        } elseif ($response->status() === 404) {
            echo "\n🔍 NOT FOUND:\n";
            echo "The endpoint or video might not exist.\n";
        } elseif ($response->status() >= 500) {
            echo "\n🚨 SERVER ERROR:\n";
            echo "The transcriber service is experiencing issues.\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Exception occurred: " . $e->getMessage() . "\n";
    echo "This might be a network connectivity issue.\n";
}

echo "\n" . str_repeat("=", 80) . "\n";

// Test with additional videos for comparison
echo "\n🧪 Testing Additional Videos with Authentication\n";
echo "==============================================\n\n";

$additionalVideos = [
    'https://www.youtube.com/watch?v=dQw4w9WgXcQ', // Rick Astley
    'https://www.youtube.com/watch?v=z5YgnzFv3h4', // Tech tutorial
    'https://www.youtube.com/watch?v=woo-rVRDP0g'  // News update
];

foreach ($additionalVideos as $index => $videoUrl) {
    echo "🎥 Test " . ($index + 1) . ": {$videoUrl}\n";
    echo str_repeat("-", 60) . "\n";
    
    try {
        $testUrl = "https://transcriber.akmicroservice.com/scraper/smartproxy/subtitles?url=" . urlencode($videoUrl) . "&format=bundle";
        
        $response = Http::timeout(300) // 5 minutes
            ->connectTimeout(30)
            ->withHeaders([
                'X-Client-Key' => $apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])
            ->get($testUrl);
        
        echo "Status: " . $response->status() . "\n";
        echo "Time: " . $response->transferStats->getHandlerStat('total_time') . "s\n";
        
        if ($response->successful()) {
            $data = $response->json();
            $articleLength = isset($data['article_text']) ? strlen($data['article_text']) : 0;
            echo "✅ Success - Article length: {$articleLength} chars\n";
        } else {
            echo "❌ Failed - " . $response->status() . "\n";
            if ($response->status() === 401) {
                echo "   Authentication failed\n";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "📊 SUMMARY\n";
echo "==========\n";
echo "✅ If the first test worked, the transcriber service is accessible with auth\n";
echo "✅ If additional tests worked, the service is reliable\n";
echo "❌ If all tests failed, there's still an authentication or service issue\n";
echo "\n💡 NEXT STEPS:\n";
echo "1. If working: Update YouTubeTranscriberService to use X-Client-Key header\n";
echo "2. If failing: Check if API key is valid or needs renewal\n";
echo "3. If network issues: Check connectivity to transcriber service\n";

