<?php

/**
 * Test AI Manager Service Endpoints
 * Find the correct API endpoints for the AI Manager service
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Testing AI Manager Service Endpoints\n";
echo "=====================================\n\n";

$aiManagerUrl = config('services.ai_manager.url');
$aiManagerKey = config('services.ai_manager.api_key');

echo "AI Manager URL: {$aiManagerUrl}\n";
echo "API Key: " . substr($aiManagerKey, 0, 10) . "...\n\n";

// Test common API endpoints
$endpoints = [
    '/',
    '/api',
    '/api/health',
    '/api/status',
    '/api/v1',
    '/api/v1/health',
    '/api/v1/status',
    '/summarize',
    '/api/summarize',
    '/api/v1/summarize',
    '/health',
    '/status',
    '/ping',
    '/info'
];

foreach ($endpoints as $endpoint) {
    echo "📡 Testing endpoint: {$endpoint}\n";
    
    try {
        $response = \Illuminate\Support\Facades\Http::timeout(10)->get($aiManagerUrl . $endpoint);
        
        if ($response->successful()) {
            echo "✅ SUCCESS - Status: " . $response->status() . "\n";
            echo "Response: " . substr($response->body(), 0, 200) . "...\n";
        } else {
            echo "❌ FAILED - Status: " . $response->status() . "\n";
        }
    } catch (Exception $e) {
        echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// Test with API key
echo "🔑 Testing with API key...\n";
$apiEndpoints = [
    '/api/summarize',
    '/api/v1/summarize',
    '/summarize'
];

foreach ($apiEndpoints as $endpoint) {
    echo "📡 Testing authenticated endpoint: {$endpoint}\n";
    
    try {
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $aiManagerKey,
            'Content-Type' => 'application/json'
        ])->timeout(10)->get($aiManagerUrl . $endpoint);
        
        if ($response->successful()) {
            echo "✅ SUCCESS - Status: " . $response->status() . "\n";
            echo "Response: " . substr($response->body(), 0, 200) . "...\n";
        } else {
            echo "❌ FAILED - Status: " . $response->status() . "\n";
        }
    } catch (Exception $e) {
        echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo str_repeat("=", 50) . "\n";




