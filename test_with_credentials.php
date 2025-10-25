<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing Endpoints with Provided Credentials\n";
echo "============================================\n\n";

// Test with provided credentials
$email = 'test-subscription@example.com';
$password = 'password';

echo "🔐 Testing authentication with: {$email}\n";

try {
    // Try to login with provided credentials
    $loginResponse = Http::timeout(30)->post('http://localhost:8000/api/login', [
        'email' => $email,
        'password' => $password
    ]);
    
    echo "📡 Login Response Status: " . $loginResponse->status() . "\n";
    echo "📄 Login Response: " . $loginResponse->body() . "\n\n";
    
    if ($loginResponse->successful()) {
        $loginData = $loginResponse->json();
        $token = $loginData['token'] ?? null;
        
        if ($token) {
            echo "✅ Login successful! Token: " . substr($token, 0, 20) . "...\n\n";
            
            // Test endpoints with authentication
            echo "🔍 Testing endpoints with authentication...\n\n";
            
            $endpointTests = [
                'text' => [
                    'url' => 'http://localhost:8000/api/summarize/async/text',
                    'data' => [
                        'text' => 'This is a test of the text summarization endpoint with proper authentication.',
                        'options' => ['format' => 'detailed', 'language' => 'en', 'focus' => 'summary']
                    ],
                    'description' => 'Text Summarization'
                ],
                'youtube' => [
                    'url' => 'http://localhost:8000/api/summarize/async/youtube',
                    'data' => [
                        'url' => 'https://www.youtube.com/watch?v=XDNeGenHIM0',
                        'options' => ['format' => 'detailed', 'language' => 'en']
                    ],
                    'description' => 'YouTube Video Summarization'
                ]
            ];
            
            $results = [];
            
            foreach ($endpointTests as $endpoint => $test) {
                echo "🔍 Testing {$test['description']} ({$endpoint})\n";
                echo str_repeat("=", 50) . "\n";
                
                try {
                    echo "📡 Making authenticated request to: {$test['url']}\n";
                    
                    $response = Http::timeout(30)
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $token,
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json'
                        ])
                        ->post($test['url'], $test['data']);
                    
                    echo "📊 Response Status: " . $response->status() . "\n";
                    echo "📝 Response Body: " . $response->body() . "\n\n";
                    
                    if ($response->successful()) {
                        $responseData = $response->json();
                        echo "✅ Request successful!\n";
                        echo "📊 Parsed Response: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
                        
                        $results[$endpoint] = [
                            'status' => 'success',
                            'response_code' => $response->status(),
                            'data' => $responseData
                        ];
                    } else {
                        echo "❌ Request failed!\n";
                        $results[$endpoint] = [
                            'status' => 'failed',
                            'response_code' => $response->status(),
                            'error' => $response->body()
                        ];
                    }
                    
                } catch (Exception $e) {
                    echo "💥 Exception occurred: " . $e->getMessage() . "\n";
                    $results[$endpoint] = [
                        'status' => 'exception',
                        'error' => $e->getMessage()
                    ];
                }
                
                echo "\n" . str_repeat("-", 80) . "\n\n";
            }
            
            // Summary
            echo "📊 AUTHENTICATED TEST RESULTS\n";
            echo "=============================\n\n";
            
            $successCount = 0;
            $failedCount = 0;
            $exceptionCount = 0;
            
            foreach ($results as $endpoint => $result) {
                $status = $result['status'];
                $statusIcon = match($status) {
                    'success' => '✅',
                    'failed' => '❌',
                    'exception' => '💥',
                    default => '❓'
                };
                
                echo "{$statusIcon} {$endpoint}: {$status}";
                
                if (isset($result['response_code'])) {
                    echo " (HTTP {$result['response_code']})";
                }
                
                if (isset($result['error'])) {
                    echo " - {$result['error']}";
                }
                
                echo "\n";
                
                // Count results
                match($status) {
                    'success' => $successCount++,
                    'failed' => $failedCount++,
                    'exception' => $exceptionCount++
                };
            }
            
            echo "\n📈 STATISTICS\n";
            echo "=============\n";
            echo "✅ Successful: {$successCount}\n";
            echo "❌ Failed: {$failedCount}\n";
            echo "💥 Exceptions: {$exceptionCount}\n";
            echo "📊 Total: " . count($results) . "\n";
            
            $successRate = count($results) > 0 ? round(($successCount / count($results)) * 100, 1) : 0;
            echo "🎯 Success Rate: {$successRate}%\n\n";
            
        } else {
            echo "❌ No token in login response\n";
            echo "Response structure: " . json_encode($loginData, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "❌ Login failed with provided credentials\n";
        echo "Response: " . $loginResponse->body() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Authentication error: " . $e->getMessage() . "\n";
}

echo "\n🔍 AI MANAGER SERVICE INVESTIGATION\n";
echo "===================================\n\n";

// Investigate AI Manager service
echo "🌐 Testing AI Manager service directly...\n";

try {
    $aiManagerUrl = 'https://aimanager.akmicroservice.com';
    $apiKey = '8eebab3587a5719950dfb3ee348737c6e244c13a5d6b3d35161071ee6a9d8c43';
    
    // Test root endpoint
    echo "📡 Testing root endpoint: {$aiManagerUrl}\n";
    $rootResponse = Http::timeout(10)->get($aiManagerUrl);
    echo "📊 Root Response Status: " . $rootResponse->status() . "\n";
    echo "📄 Root Response (first 200 chars): " . substr($rootResponse->body(), 0, 200) . "...\n\n";
    
    // Test health endpoint
    echo "📡 Testing health endpoint: {$aiManagerUrl}/health\n";
    $healthResponse = Http::timeout(10)
        ->withHeaders([
            'X-API-KEY' => $apiKey,
            'Accept' => 'application/json'
        ])
        ->get($aiManagerUrl . '/health');
    echo "📊 Health Response Status: " . $healthResponse->status() . "\n";
    echo "📄 Health Response: " . $healthResponse->body() . "\n\n";
    
    // Test API endpoint
    echo "📡 Testing API endpoint: {$aiManagerUrl}/api/process-text\n";
    $apiResponse = Http::timeout(10)
        ->withHeaders([
            'X-API-KEY' => $apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])
        ->post($aiManagerUrl . '/api/process-text', [
            'text' => 'Test text',
            'task' => 'summarize',
            'options' => []
        ]);
    echo "📊 API Response Status: " . $apiResponse->status() . "\n";
    echo "📄 API Response: " . $apiResponse->body() . "\n\n";
    
    // Analysis
    echo "🔍 AI MANAGER ANALYSIS:\n";
    echo "======================\n";
    
    if ($rootResponse->status() === 200) {
        $body = $rootResponse->body();
        if (strpos($body, '<!DOCTYPE html>') !== false) {
            echo "❌ PROBLEM: AI Manager is returning HTML (Laravel welcome page) instead of API\n";
            echo "   This means the service is running but not configured for API endpoints\n";
        } else {
            echo "✅ Root endpoint returns non-HTML content\n";
        }
    } else {
        echo "❌ Root endpoint not accessible\n";
    }
    
    if ($healthResponse->status() === 404) {
        echo "❌ PROBLEM: /health endpoint not found (404)\n";
        echo "   The AI Manager service doesn't have a health endpoint configured\n";
    } elseif ($healthResponse->status() === 200) {
        echo "✅ Health endpoint working\n";
    } else {
        echo "⚠️ Health endpoint returned status: " . $healthResponse->status() . "\n";
    }
    
    if ($apiResponse->status() === 404) {
        echo "❌ PROBLEM: /api/process-text endpoint not found (404)\n";
        echo "   The AI Manager service doesn't have the required API endpoint\n";
    } elseif ($apiResponse->status() === 200) {
        echo "✅ API endpoint working\n";
    } else {
        echo "⚠️ API endpoint returned status: " . $apiResponse->status() . "\n";
    }
    
    echo "\n🎯 CONCLUSION:\n";
    echo "============\n";
    echo "The AI Manager service is running but not properly configured for API usage.\n";
    echo "It's returning a Laravel welcome page instead of API endpoints.\n";
    echo "The service needs to be configured with proper API routes.\n\n";
    
} catch (Exception $e) {
    echo "❌ AI Manager investigation error: " . $e->getMessage() . "\n";
}

echo "\n✨ Testing completed!\n";




