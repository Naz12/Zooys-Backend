<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing Specialized Endpoints (Simple Test)\n";
echo "============================================\n\n";

// Test the text endpoint directly
echo "🔍 Testing /summarize/async/text endpoint...\n";

try {
    $response = Http::timeout(30)->post('http://localhost:8000/api/summarize/async/text', [
        'text' => 'This is a test of the text summarization endpoint. It should process this text and return a summary using the job scheduler.',
        'options' => [
            'format' => 'detailed',
            'language' => 'en',
            'focus' => 'summary'
        ]
    ]);
    
    echo "📡 Response Status: " . $response->status() . "\n";
    echo "📄 Response Body: " . $response->body() . "\n\n";
    
    if ($response->successful()) {
        $responseData = $response->json();
        echo "✅ Request successful!\n";
        echo "📊 Response Data: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ Request failed with status: " . $response->status() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n🔍 Testing /summarize/async/youtube endpoint...\n";

try {
    $response = Http::timeout(30)->post('http://localhost:8000/api/summarize/async/youtube', [
        'url' => 'https://www.youtube.com/watch?v=XDNeGenHIM0',
        'options' => [
            'format' => 'detailed',
            'language' => 'en'
        ]
    ]);
    
    echo "📡 Response Status: " . $response->status() . "\n";
    echo "📄 Response Body: " . $response->body() . "\n\n";
    
    if ($response->successful()) {
        $responseData = $response->json();
        echo "✅ Request successful!\n";
        echo "📊 Response Data: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ Request failed with status: " . $response->status() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n✨ Test completed!\n";



