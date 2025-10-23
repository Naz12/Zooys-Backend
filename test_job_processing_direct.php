<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing Job Processing Directly (Bypassing Authentication)\n";
echo "============================================================\n\n";

// Test the UniversalJobService directly
use App\Services\UniversalJobService;

$universalJobService = app(UniversalJobService::class);

echo "🔍 Testing UniversalJobService directly...\n";

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
                'data' => 'This is a comprehensive test of the text summarization system. It should process this text and return a summary using the AI Manager service or fallback mechanism.'
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

// Test 2: YouTube Summarization
echo "\n🎥 Test 2: YouTube Summarization\n";
echo "================================\n";

try {
    $job = $universalJobService->createJob(
        'summarize',
        [
            'content_type' => 'link',
            'source' => [
                'type' => 'url',
                'data' => 'https://www.youtube.com/watch?v=XDNeGenHIM0'
            ]
        ],
        [
            'format' => 'detailed',
            'language' => 'en'
        ],
        1
    );
    
    echo "✅ Job created: {$job['id']}\n";
    echo "📊 Job status: {$job['status']}\n";
    
    // Process the job
    $result = $universalJobService->processJob($job['id']);
    
    echo "📈 Processing result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "❌ YouTube summarization failed: " . $e->getMessage() . "\n";
}

// Test 3: Check AI Manager Service
echo "\n🤖 Test 3: AI Manager Service Check\n";
echo "===================================\n";

try {
    $aiManagerService = app(\App\Services\AIManagerService::class);
    
    // Test health check
    $health = $aiManagerService->checkHealth();
    echo "🏥 AI Manager Health: " . json_encode($health, JSON_PRETTY_PRINT) . "\n";
    
    // Test fallback processing
    $fallbackResult = $aiManagerService->summarize('This is a test of the fallback mechanism.', [
        'format' => 'detailed',
        'language' => 'en'
    ]);
    
    echo "🔄 Fallback Result: " . json_encode($fallbackResult, JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "❌ AI Manager test failed: " . $e->getMessage() . "\n";
}

// Test 4: Check Job Status
echo "\n📊 Test 4: Job Status Check\n";
echo "===========================\n";

try {
    $jobs = $universalJobService->getAllJobs();
    echo "📋 Total jobs: " . count($jobs) . "\n";
    
    foreach ($jobs as $job) {
        echo "🔍 Job {$job['id']}: {$job['status']} - {$job['tool_type']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Job status check failed: " . $e->getMessage() . "\n";
}

echo "\n✨ Direct testing completed!\n";
