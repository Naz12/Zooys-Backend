<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing Job Processing Directly\n";
echo "=================================\n\n";

// Test the UniversalJobService directly
use App\Services\UniversalJobService;

$universalJobService = app(UniversalJobService::class);

echo "🔍 Testing job processing directly...\n\n";

try {
    // Create a text summarization job
    $job = $universalJobService->createJob(
        'summarize',
        [
            'content_type' => 'text',
            'source' => [
                'type' => 'text',
                'data' => 'This is a test of the text summarization system. It should process this text and return a summary using the AI Manager service.'
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
    echo "📊 Job stage: {$job['stage']}\n";
    echo "📊 Job progress: {$job['progress']}%\n\n";
    
    // Process the job directly
    echo "🔄 Processing job directly...\n";
    $result = $universalJobService->processJob($job['id']);
    
    echo "📈 Processing result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    if ($result['success']) {
        echo "✅ Job processing successful!\n";
        
        // Check job status after processing
        $updatedJob = $universalJobService->getJob($job['id']);
        echo "📊 Updated job status: " . ($updatedJob['status'] ?? 'unknown') . "\n";
        echo "📊 Updated job stage: " . ($updatedJob['stage'] ?? 'unknown') . "\n";
        echo "📊 Updated job progress: " . ($updatedJob['progress'] ?? 0) . "%\n";
        
        if (isset($updatedJob['result'])) {
            echo "📊 Job result: " . json_encode($updatedJob['result'], JSON_PRETTY_PRINT) . "\n";
        }
        
        if (isset($updatedJob['error'])) {
            echo "❌ Job error: " . $updatedJob['error'] . "\n";
        }
    } else {
        echo "❌ Job processing failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n🔍 Testing AI Manager Service Directly\n";
echo "=====================================\n\n";

try {
    $aiManagerService = app(\App\Services\AIManagerService::class);
    
    echo "🔄 Testing AI Manager service directly...\n";
    $result = $aiManagerService->summarize('This is a test of the AI Manager service.', [
        'format' => 'detailed',
        'language' => 'en'
    ]);
    
    echo "📊 AI Manager result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    
    if ($result['success']) {
        echo "✅ AI Manager service is working!\n";
    } else {
        echo "❌ AI Manager service failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ AI Manager exception: " . $e->getMessage() . "\n";
}

echo "\n✨ Direct job processing test completed!\n";


