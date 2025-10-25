<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing Specialized Endpoints with Authentication\n";
echo "==================================================\n\n";

// First, get authentication token
echo "🔐 Getting authentication token...\n";

try {
    $authResponse = Http::timeout(30)->post('http://localhost:8000/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password'
    ]);
    
    if ($authResponse->successful()) {
        $authData = $authResponse->json();
        $token = $authData['data']['token'] ?? null;
        
        if ($token) {
            echo "✅ Authentication successful\n";
            echo "🔑 Token: " . substr($token, 0, 20) . "...\n\n";
        } else {
            echo "❌ No token in response\n";
            exit(1);
        }
    } else {
        echo "❌ Authentication failed: " . $authResponse->status() . "\n";
        echo "Response: " . $authResponse->body() . "\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Authentication error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test data for different endpoints
$testData = [
    'youtube' => [
        'url' => 'https://www.youtube.com/watch?v=XDNeGenHIM0',
        'options' => ['format' => 'detailed', 'language' => 'en']
    ],
    'text' => [
        'text' => 'This is a comprehensive test of the text summarization endpoint. It should process this text and return a summary using the job scheduler.',
        'options' => ['format' => 'detailed', 'language' => 'en', 'focus' => 'summary']
    ]
];

$baseUrl = 'http://localhost:8000/api';
$results = [];

// Test each specialized endpoint with authentication
foreach ($testData as $endpoint => $data) {
    echo "🔍 Testing /summarize/async/{$endpoint} endpoint...\n";
    
    try {
        // Create job with authentication
        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])
            ->post("{$baseUrl}/summarize/async/{$endpoint}", $data);
        
        echo "📡 Response Status: " . $response->status() . "\n";
        
        if ($response->successful()) {
            $responseData = $response->json();
            $jobId = $responseData['data']['job_id'] ?? null;
            
            if ($jobId) {
                echo "✅ Job created successfully: {$jobId}\n";
                
                // Poll for completion
                $maxAttempts = 10;
                $attempt = 0;
                $completed = false;
                
                while ($attempt < $maxAttempts && !$completed) {
                    sleep(3);
                    $attempt++;
                    
                    $statusResponse = Http::timeout(10)
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $token,
                            'Accept' => 'application/json'
                        ])
                        ->get("{$baseUrl}/summarize/async/status/{$jobId}");
                    
                    if ($statusResponse->successful()) {
                        $statusData = $statusResponse->json();
                        $status = $statusData['data']['status'] ?? 'unknown';
                        $progress = $statusData['data']['progress'] ?? 0;
                        $stage = $statusData['data']['stage'] ?? 'unknown';
                        
                        echo "   📊 Status: {$status} | Progress: {$progress}% | Stage: {$stage}\n";
                        
                        if ($status === 'completed') {
                            $completed = true;
                            
                            // Get result
                            $resultResponse = Http::timeout(10)
                                ->withHeaders([
                                    'Authorization' => 'Bearer ' . $token,
                                    'Accept' => 'application/json'
                                ])
                                ->get("{$baseUrl}/summarize/async/result/{$jobId}");
                            
                            if ($resultResponse->successful()) {
                                $resultData = $resultResponse->json();
                                $success = $resultData['data']['success'] ?? false;
                                
                                if ($success) {
                                    echo "✅ {$endpoint} endpoint completed successfully!\n";
                                    $results[$endpoint] = [
                                        'status' => 'success',
                                        'job_id' => $jobId,
                                        'processing_time' => $attempt * 3
                                    ];
                                } else {
                                    echo "❌ {$endpoint} endpoint failed: " . ($resultData['data']['error'] ?? 'Unknown error') . "\n";
                                    $results[$endpoint] = [
                                        'status' => 'failed',
                                        'job_id' => $jobId,
                                        'error' => $resultData['data']['error'] ?? 'Unknown error'
                                    ];
                                }
                            } else {
                                echo "❌ Failed to get result for {$endpoint}\n";
                                $results[$endpoint] = [
                                    'status' => 'error',
                                    'job_id' => $jobId,
                                    'error' => 'Failed to get result'
                                ];
                            }
                        } elseif ($status === 'failed') {
                            $error = $statusData['data']['error'] ?? 'Unknown error';
                            echo "❌ {$endpoint} endpoint failed: {$error}\n";
                            $results[$endpoint] = [
                                'status' => 'failed',
                                'job_id' => $jobId,
                                'error' => $error
                            ];
                            $completed = true;
                        }
                    } else {
                        echo "❌ Failed to check status for {$endpoint}: " . $statusResponse->status() . "\n";
                        $results[$endpoint] = [
                            'status' => 'error',
                            'job_id' => $jobId,
                            'error' => 'Failed to check status: ' . $statusResponse->status()
                        ];
                        $completed = true;
                    }
                }
                
                if (!$completed) {
                    echo "⏰ Timeout waiting for {$endpoint} completion\n";
                    $results[$endpoint] = [
                        'status' => 'timeout',
                        'job_id' => $jobId,
                        'error' => 'Processing timeout'
                    ];
                }
            } else {
                echo "❌ No job ID returned for {$endpoint}\n";
                echo "Response: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
                $results[$endpoint] = [
                    'status' => 'error',
                    'error' => 'No job ID returned'
                ];
            }
        } else {
            echo "❌ Failed to create job for {$endpoint}: " . $response->status() . "\n";
            echo "Response: " . $response->body() . "\n";
            $results[$endpoint] = [
                'status' => 'error',
                'error' => 'Failed to create job: ' . $response->status()
            ];
        }
    } catch (Exception $e) {
        echo "❌ Exception testing {$endpoint}: " . $e->getMessage() . "\n";
        $results[$endpoint] = [
            'status' => 'error',
            'error' => $e->getMessage()
        ];
    }
    
    echo "\n";
}

// Summary
echo "📊 TEST RESULTS SUMMARY\n";
echo "=======================\n\n";

$successCount = 0;
$failedCount = 0;
$errorCount = 0;

foreach ($results as $endpoint => $result) {
    $status = $result['status'];
    $statusIcon = match($status) {
        'success' => '✅',
        'failed' => '❌',
        'error' => '⚠️',
        'timeout' => '⏰',
        default => '❓'
    };
    
    echo "{$statusIcon} {$endpoint}: {$status}";
    
    if (isset($result['job_id'])) {
        echo " (Job: {$result['job_id']})";
    }
    
    if (isset($result['error'])) {
        echo " - {$result['error']}";
    }
    
    if (isset($result['processing_time'])) {
        echo " - {$result['processing_time']}s";
    }
    
    echo "\n";
    
    // Count results
    match($status) {
        'success' => $successCount++,
        'failed' => $failedCount++,
        default => $errorCount++
    };
}

echo "\n📈 STATISTICS\n";
echo "=============\n";
echo "✅ Successful: {$successCount}\n";
echo "❌ Failed: {$failedCount}\n";
echo "⚠️ Errors: {$errorCount}\n";
echo "📊 Total: " . count($results) . "\n";

$successRate = count($results) > 0 ? round(($successCount / count($results)) * 100, 1) : 0;
echo "🎯 Success Rate: {$successRate}%\n\n";

if ($successCount === count($results)) {
    echo "🎉 ALL ENDPOINTS WORKING PERFECTLY!\n";
    echo "The specialized endpoints are properly integrated with the job scheduler.\n";
} elseif ($successCount > 0) {
    echo "⚠️ PARTIAL SUCCESS\n";
    echo "Some endpoints are working, but there are issues with others.\n";
} else {
    echo "❌ ALL ENDPOINTS FAILED\n";
    echo "There are fundamental issues with the specialized endpoints.\n";
}

echo "\n✨ Test completed!\n";




