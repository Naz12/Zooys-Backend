<?php

/**
 * Test AI Manager Service
 * Check if the AI Manager service is accessible
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Testing AI Manager Service\n";
echo "=============================\n\n";

try {
    $aiManagerUrl = config('services.ai_manager.url');
    $aiManagerKey = config('services.ai_manager.api_key');
    $timeout = config('services.ai_manager.timeout');
    
    echo "AI Manager URL: {$aiManagerUrl}\n";
    echo "API Key: " . substr($aiManagerKey, 0, 10) . "...\n";
    echo "Timeout: {$timeout}s\n\n";
    
    // Test 1: Basic connectivity
    echo "📡 Testing basic connectivity...\n";
    $response = \Illuminate\Support\Facades\Http::timeout(10)->get($aiManagerUrl);
    
    if ($response->successful()) {
        echo "✅ AI Manager is accessible\n";
        echo "Status: " . $response->status() . "\n";
        echo "Response: " . substr($response->body(), 0, 200) . "...\n";
    } else {
        echo "❌ AI Manager is not accessible\n";
        echo "Status: " . $response->status() . "\n";
        echo "Error: " . $response->body() . "\n";
    }
    
    echo "\n";
    
    // Test 2: Health check endpoint
    echo "🏥 Testing health check endpoint...\n";
    $healthResponse = \Illuminate\Support\Facades\Http::timeout(10)->get($aiManagerUrl . '/health');
    
    if ($healthResponse->successful()) {
        echo "✅ Health check successful\n";
        echo "Status: " . $healthResponse->status() . "\n";
        echo "Response: " . $healthResponse->body() . "\n";
    } else {
        echo "❌ Health check failed\n";
        echo "Status: " . $healthResponse->status() . "\n";
        echo "Error: " . $healthResponse->body() . "\n";
    }
    
    echo "\n";
    
    // Test 3: Test with API key
    echo "🔑 Testing with API key...\n";
    $apiResponse = \Illuminate\Support\Facades\Http::withHeaders([
        'Authorization' => 'Bearer ' . $aiManagerKey,
        'Content-Type' => 'application/json'
    ])->timeout(10)->get($aiManagerUrl . '/status');
    
    if ($apiResponse->successful()) {
        echo "✅ API key authentication successful\n";
        echo "Status: " . $apiResponse->status() . "\n";
        echo "Response: " . $apiResponse->body() . "\n";
    } else {
        echo "❌ API key authentication failed\n";
        echo "Status: " . $apiResponse->status() . "\n";
        echo "Error: " . $apiResponse->body() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";




