<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🤖 Direct AI Manager Test\n";
echo "========================\n\n";

// The article text (shortened for testing)
$articleText = "Is President Trump on the verge of essentially paying himself 230 million in taxpayer money? All kinds of ethical questions are being raised after the president appeared to confirm a New York Times report that he is seeking damages from his own Justice Department over past investigations involving himself specifically Robert Mueller's investigation into Russian election interference and the probe into the classified documents the FBI seized from Mar A Lago.";

echo "📄 Article Text Length: " . strlen($articleText) . " characters\n";
echo "📊 Word Count: " . str_word_count($articleText) . " words\n\n";

// AI Manager configuration
$apiKey = config('services.ai_manager.api_key');
$apiUrl = config('services.ai_manager.api_url');

echo "🔧 AI Manager Configuration:\n";
echo "API URL: {$apiUrl}\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n\n";

// Prepare request data
$requestData = [
    'text' => $articleText,
    'task' => 'summarize',
    'options' => [
        'mode' => 'detailed',
        'language' => 'en',
        'max_tokens' => 500,
        'temperature' => 0.7
    ]
];

echo "📤 Sending request to AI Manager...\n";
echo "Request Data:\n";
echo "- Text Length: " . strlen($requestData['text']) . " characters\n";
echo "- Task: {$requestData['task']}\n";
echo "- Mode: {$requestData['options']['mode']}\n";
echo "- Max Tokens: {$requestData['options']['max_tokens']}\n\n";

try {
    // Make direct HTTP request
    $response = Http::timeout(30)
        ->connectTimeout(10)
        ->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-API-KEY' => $apiKey,
        ])
        ->post($apiUrl . '/api/process-text', $requestData);
    
    echo "📡 Response Status: " . $response->status() . "\n";
    echo "📡 Response Headers: " . json_encode($response->headers()) . "\n\n";
    
    if ($response->successful()) {
        $responseData = $response->json();
        
        echo "✅ AI Manager Response Received!\n";
        echo "================================\n\n";
        
        echo "📋 Full Response:\n";
        echo str_repeat("-", 50) . "\n";
        echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n\n";
        
        if (isset($responseData['success']) && $responseData['success']) {
            echo "📋 Summary:\n";
            echo str_repeat("-", 50) . "\n";
            echo $responseData['data']['insights'] . "\n\n";
            
            echo "🔍 Key Points:\n";
            echo str_repeat("-", 50) . "\n";
            if (isset($responseData['data']['key_points'])) {
                foreach ($responseData['data']['key_points'] as $index => $point) {
                    echo ($index + 1) . ". " . $point . "\n";
                }
            } else {
                echo "No key points provided\n";
            }
            
            echo "\n📊 Metadata:\n";
            echo str_repeat("-", 50) . "\n";
            echo "Model Used: " . ($responseData['data']['model_used'] ?? 'Unknown') . "\n";
            echo "Tokens Used: " . ($responseData['data']['tokens_used'] ?? 'Unknown') . "\n";
            echo "Confidence Score: " . ($responseData['data']['confidence_score'] ?? 'Unknown') . "\n";
            echo "Processing Time: " . ($responseData['data']['processing_time'] ?? 'Unknown') . " seconds\n";
        } else {
            echo "❌ AI Manager returned error:\n";
            echo "Error: " . ($responseData['error'] ?? 'Unknown error') . "\n";
        }
        
    } else {
        echo "❌ HTTP Error:\n";
        echo "Status: " . $response->status() . "\n";
        echo "Body: " . $response->body() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception occurred:\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n🎉 Direct AI Manager Test Complete!\n";

