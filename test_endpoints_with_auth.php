<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing Endpoints with Authentication\n";
echo "=======================================\n\n";

// First, let's try to create a test user and get a token
echo "🔐 Attempting to create test user and get token...\n";

try {
    // Try to register a test user
    $registerResponse = Http::timeout(30)->post('http://localhost:8000/api/register', [
        'name' => 'Test User',
        'email' => 'testuser@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123'
    ]);
    
    echo "📡 Register Response Status: " . $registerResponse->status() . "\n";
    echo "📄 Register Response: " . $registerResponse->body() . "\n\n";
    
    if ($registerResponse->successful()) {
        $registerData = $registerResponse->json();
        $token = $registerData['token'] ?? null;
        
        if ($token) {
            echo "✅ Registration successful! Token: " . substr($token, 0, 20) . "...\n\n";
        } else {
            echo "❌ No token in registration response\n";
            echo "Response structure: " . json_encode($registerData, JSON_PRETTY_PRINT) . "\n";
            exit(1);
        }
    } else {
        echo "❌ Registration failed, trying login...\n";
        
        // Try to login with existing user
        $loginResponse = Http::timeout(30)->post('http://localhost:8000/api/login', [
            'email' => 'testuser@example.com',
            'password' => 'password123'
        ]);
        
        echo "📡 Login Response Status: " . $loginResponse->status() . "\n";
        echo "📄 Login Response: " . $loginResponse->body() . "\n\n";
        
        if ($loginResponse->successful()) {
            $loginData = $loginResponse->json();
            $token = $loginData['token'] ?? null;
            
            if ($token) {
                echo "✅ Login successful! Token: " . substr($token, 0, 20) . "...\n\n";
            } else {
                echo "❌ No token in login response\n";
                echo "Response structure: " . json_encode($loginData, JSON_PRETTY_PRINT) . "\n";
                exit(1);
            }
        } else {
            echo "❌ Both registration and login failed\n";
            exit(1);
        }
    }
    
} catch (Exception $e) {
    echo "❌ Authentication error: " . $e->getMessage() . "\n";
    exit(1);
}

// Now test the endpoints with authentication
echo "🔍 Testing endpoints with authentication...\n\n";

$endpointTests = [
    'text' => [
        'url' => 'http://localhost:8000/api/summarize/async/text',
        'data' => [
            'text' => 'This is a test of the text summarization endpoint with authentication.',
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

if ($successCount > 0) {
    echo "🎉 AUTHENTICATION WORKING!\n";
    echo "The endpoints are accessible with proper authentication.\n";
} else {
    echo "❌ AUTHENTICATION ISSUES\n";
    echo "Even with authentication, the endpoints are still failing.\n";
}

echo "\n✨ Authenticated testing completed!\n";
