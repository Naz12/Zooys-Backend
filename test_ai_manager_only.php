<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing AI Manager Only (No Fallback)\n";
echo "========================================\n\n";

// Test the UniversalJobService directly
use App\Services\UniversalJobService;

$universalJobService = app(UniversalJobService::class);

echo "🔍 Testing UniversalJobService with AI Manager only...\n";

// Test 1: Text Summarization
echo "\n📝 Test 1: Text Summarization\n";
echo "==============================\n";

try {
    $job = $universalJobService->createJob(
        'summarize',
        [
            'content_type' => 'text',
            'source' => [
                'type' => 'text',
                'data' => 'This is a comprehensive test of the text summarization system. It should process this text and return a summary using ONLY the AI Manager service.'
            ]
        ],
        [
            'format' => 'detailed',
            'language' => 'en',
            'focus' => 'summary'
        ],
        1
    );
    
    echo "✅ Job created: {$job['id']}\n";
    echo "📊 Job status: {$job['status']}\n";
    
    // Process the job
    $result = $universalJobService->processJob($job['id']);
    
    echo "📈 Processing result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "❌ Text summarization failed: " . $e->getMessage() . "\n";
}

// Test 2: Check AI Manager Service Status
echo "\n🤖 Test 2: AI Manager Service Status\n";
echo "===================================\n";

try {
    $aiManagerService = app(\App\Services\AIManagerService::class);
    
    // Test health check
    $health = $aiManagerService->checkHealth();
    echo "🏥 AI Manager Health: " . json_encode($health, JSON_PRETTY_PRINT) . "\n";
    
    // Test direct AI Manager call
    echo "\n🔄 Testing direct AI Manager call...\n";
    $directResult = $aiManagerService->summarize('This is a test of the AI Manager service directly.', [
        'format' => 'detailed',
        'language' => 'en'
    ]);
    
    echo "📊 Direct AI Manager Result: " . json_encode($directResult, JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "❌ AI Manager test failed: " . $e->getMessage() . "\n";
}

// Test 3: Check if AI Manager is properly configured
echo "\n⚙️ Test 3: AI Manager Configuration\n";
echo "===================================\n";

try {
    $config = config('services.ai_manager');
    echo "🔧 AI Manager Config: " . json_encode($config, JSON_PRETTY_PRINT) . "\n";
    
    // Test the URL directly
    echo "\n🌐 Testing AI Manager URL directly...\n";
    $response = Http::timeout(10)->get($config['url']);
    echo "📡 Response Status: " . $response->status() . "\n";
    echo "📄 Response Body (first 200 chars): " . substr($response->body(), 0, 200) . "...\n";
    
} catch (Exception $e) {
    echo "❌ Configuration test failed: " . $e->getMessage() . "\n";
}

echo "\n✨ AI Manager Only testing completed!\n";



