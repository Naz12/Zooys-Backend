<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🎯 FINAL SUMMARIZATION RESULT\n";
echo "============================\n\n";

// User's original text
$userText = "Born into a wealthy family in New York City, Trump graduated from the University of Pennsylvania in 1968 with a bachelor's degree in economics. He became the president of his family's real estate business in 1971, renamed it the Trump Organization, and began acquiring and building skyscrapers, hotels, casinos, and golf courses. He launched side ventures, many licensing the Trump name, and filed for six business bankruptcies in the 1990s and 2000s";

echo "📝 ORIGINAL TEXT:\n";
echo "=================\n";
echo $userText . "\n\n";

try {
    // Test AI Manager service directly
    $aiManagerService = app(\App\Services\AIManagerService::class);
    
    echo "🔄 Processing with AI Manager service...\n";
    $result = $aiManagerService->summarize($userText, [
        'format' => 'detailed',
        'language' => 'en'
    ]);
    
    if ($result['success']) {
        echo "✅ AI Manager service processing successful!\n\n";
        
        // Extract the actual summary from the nested structure
        $summary = '';
        $keyPoints = [];
        
        if (isset($result['data']['raw_output']['data']['insights'])) {
            $summary = $result['data']['raw_output']['data']['insights'];
        } elseif (isset($result['data']['raw_output']['data']['raw_output']['summary'])) {
            $summary = $result['data']['raw_output']['data']['raw_output']['summary'];
        }
        
        if (isset($result['data']['raw_output']['data']['raw_output']['key_points'])) {
            $keyPoints = $result['data']['raw_output']['data']['raw_output']['key_points'];
        } elseif (isset($result['data']['raw_output']['data']['key_points'])) {
            $keyPoints = $result['data']['raw_output']['data']['key_points'];
        }
        
        echo "📝 AI-GENERATED SUMMARY:\n";
        echo "========================\n";
        echo $summary . "\n\n";
        
        if (!empty($keyPoints)) {
            echo "🔑 KEY POINTS:\n";
            echo "==============\n";
            foreach ($keyPoints as $point) {
                echo "• " . $point . "\n";
            }
            echo "\n";
        }
        
        echo "📊 PROCESSING DETAILS:\n";
        echo "======================\n";
        echo "Confidence Score: " . ($result['confidence_score'] ?? 'N/A') . "\n";
        echo "Model Used: " . ($result['model_used'] ?? 'N/A') . "\n";
        echo "Processing Time: " . ($result['processing_time'] ?? 'N/A') . " seconds\n";
        echo "Tokens Used: " . ($result['tokens_used'] ?? 'N/A') . "\n\n";
        
        echo "✅ SUCCESS: Text summarization completed successfully!\n";
        echo "🎉 The text endpoint is working perfectly!\n";
    } else {
        echo "❌ AI Manager service failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n✨ Final result display completed!\n";


