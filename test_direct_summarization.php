<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing Direct Text Summarization\n";
echo "===================================\n\n";

// User's text to summarize
$userText = "Born into a wealthy family in New York City, Trump graduated from the University of Pennsylvania in 1968 with a bachelor's degree in economics. He became the president of his family's real estate business in 1971, renamed it the Trump Organization, and began acquiring and building skyscrapers, hotels, casinos, and golf courses. He launched side ventures, many licensing the Trump name, and filed for six business bankruptcies in the 1990s and 2000s";

echo "📝 User's Text to Summarize:\n";
echo "============================\n";
echo $userText . "\n\n";

try {
    // Test the UniversalJobService directly
    $universalJobService = app(\App\Services\UniversalJobService::class);
    
    echo "🔍 Testing job processing directly...\n\n";
    
    // Create a text summarization job
    $job = $universalJobService->createJob(
        'summarize',
        [
            'content_type' => 'text',
            'source' => [
                'type' => 'text',
                'data' => $userText
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
        echo "✅ Job processing successful!\n\n";
        
        // Check job status after processing
        $updatedJob = $universalJobService->getJob($job['id']);
        echo "📊 Updated job status: " . ($updatedJob['status'] ?? 'unknown') . "\n";
        echo "📊 Updated job stage: " . ($updatedJob['stage'] ?? 'unknown') . "\n";
        echo "📊 Updated job progress: " . ($updatedJob['progress'] ?? 0) . "%\n\n";
        
        if (isset($updatedJob['result'])) {
            echo "🎯 FINAL SUMMARIZATION RESULT\n";
            echo "============================\n";
            
            $summary = $updatedJob['result']['summary'] ?? 'No summary available';
            echo "📝 SUMMARY:\n";
            echo "-----------\n";
            echo $summary . "\n\n";
            
            if (isset($updatedJob['result']['key_points'])) {
                $keyPoints = $updatedJob['result']['key_points'];
                echo "🔑 KEY POINTS:\n";
                echo "--------------\n";
                foreach ($keyPoints as $point) {
                    echo "• " . $point . "\n";
                }
                echo "\n";
            }
            
            if (isset($updatedJob['result']['confidence_score'])) {
                echo "📊 CONFIDENCE SCORE: " . $updatedJob['result']['confidence_score'] . "\n";
            }
            
            if (isset($updatedJob['result']['model_used'])) {
                echo "🤖 MODEL USED: " . $updatedJob['result']['model_used'] . "\n";
            }
            
            echo "\n✅ SUCCESS: Text summarization completed successfully!\n";
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

echo "\n✨ Direct summarization test completed!\n";
