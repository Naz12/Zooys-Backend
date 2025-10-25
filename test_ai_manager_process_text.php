<?php

/**
 * Test AI Manager Service /api/process-text endpoint
 * This is the actual endpoint used by the application
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Testing AI Manager /api/process-text endpoint\n";
echo "=============================================\n\n";

$aiManagerUrl = config('services.ai_manager.url');
$aiManagerKey = config('services.ai_manager.api_key');

echo "AI Manager URL: {$aiManagerUrl}\n";
echo "API Key: " . substr($aiManagerKey, 0, 10) . "...\n\n";

// Test the actual endpoint used by the application
$endpoint = '/api/process-text';

echo "📡 Testing endpoint: {$endpoint}\n";

try {
    $requestData = [
        'text' => 'This is a test text for summarization.',
        'task' => 'summarize',
        'options' => [
            'style' => 'detailed',
            'language' => 'en',
            'focus' => 'summary'
        ]
    ];

    echo "Request data: " . json_encode($requestData, JSON_PRETTY_PRINT) . "\n\n";

    $response = \Illuminate\Support\Facades\Http::timeout(30)
        ->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-API-KEY' => $aiManagerKey,
        ])->post($aiManagerUrl . $endpoint, $requestData);

    echo "Response status: " . $response->status() . "\n";
    echo "Response headers: " . json_encode($response->headers(), JSON_PRETTY_PRINT) . "\n";
    echo "Response body: " . $response->body() . "\n";

    if ($response->successful()) {
        echo "✅ SUCCESS - AI Manager is working!\n";
    } else {
        echo "❌ FAILED - AI Manager is not responding correctly\n";
    }

} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";




