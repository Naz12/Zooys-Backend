<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing All 7 Specialized Endpoints with Job Scheduler\n";
echo "========================================================\n\n";

// Test data for different endpoints
$testData = [
    'youtube' => [
        'url' => 'https://www.youtube.com/watch?v=XDNeGenHIM0',
        'options' => ['format' => 'detailed', 'language' => 'en']
    ],
    'text' => [
        'text' => 'This is a comprehensive test of the text summarization endpoint. It should process this text and return a summary using the job scheduler.',
        'options' => ['format' => 'detailed', 'language' => 'en', 'focus' => 'summary']
    ],
    'audiovideo' => [
        'file' => 'test files/test video.mp4',
        'options' => ['format' => 'detailed', 'language' => 'en']
    ],
    'file' => [
        'file' => 'test files/test.pdf',
        'options' => ['format' => 'detailed', 'language' => 'en']
    ],
    'link' => [
        'url' => 'https://example.com',
        'options' => ['format' => 'detailed', 'language' => 'en']
    ],
    'image' => [
        'file' => 'test files/test.png',
        'options' => ['format' => 'detailed', 'language' => 'en']
    ]
];

$baseUrl = 'http://localhost:8000/api';
$results = [];

// Test each specialized endpoint
foreach ($testData as $endpoint => $data) {
    echo "🔍 Testing /summarize/async/{$endpoint} endpoint...\n";
    
    try {
        // Create job
        $response = Http::timeout(30)->post("{$baseUrl}/summarize/async/{$endpoint}", $data);
        
        if ($response->successful()) {
            $responseData = $response->json();
            $jobId = $responseData['data']['job_id'] ?? null;
            
            if ($jobId) {
                echo "✅ Job created successfully: {$jobId}\n";
                
                // Poll for completion
                $maxAttempts = 30;
                $attempt = 0;
                $completed = false;
                
                while ($attempt < $maxAttempts && !$completed) {
                    sleep(2);
                    $attempt++;
                    
                    $statusResponse = Http::timeout(10)->get("{$baseUrl}/summarize/async/status/{$jobId}");
                    
                    if ($statusResponse->successful()) {
                        $statusData = $statusResponse->json();
                        $status = $statusData['data']['status'] ?? 'unknown';
                        $progress = $statusData['data']['progress'] ?? 0;
                        $stage = $statusData['data']['stage'] ?? 'unknown';
                        
                        echo "   📊 Status: {$status} | Progress: {$progress}% | Stage: {$stage}\n";
                        
                        if ($status === 'completed') {
                            $completed = true;
                            
                            // Get result
                            $resultResponse = Http::timeout(10)->get("{$baseUrl}/summarize/async/result/{$jobId}");
                            
                            if ($resultResponse->successful()) {
                                $resultData = $resultResponse->json();
                                $success = $resultData['data']['success'] ?? false;
                                
                                if ($success) {
                                    echo "✅ {$endpoint} endpoint completed successfully!\n";
                                    $results[$endpoint] = [
                                        'status' => 'success',
                                        'job_id' => $jobId,
                                        'processing_time' => $attempt * 2
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
                        echo "❌ Failed to check status for {$endpoint}\n";
                        $results[$endpoint] = [
                            'status' => 'error',
                            'job_id' => $jobId,
                            'error' => 'Failed to check status'
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
                $results[$endpoint] = [
                    'status' => 'error',
                    'error' => 'No job ID returned'
                ];
            }
        } else {
            echo "❌ Failed to create job for {$endpoint}: " . $response->status() . "\n";
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

echo "\n🔧 RECOMMENDATIONS\n";
echo "==================\n";

if ($failedCount > 0) {
    echo "• Check AI Manager service availability\n";
    echo "• Verify fallback mechanisms are working\n";
    echo "• Review job processing logs\n";
}

if ($errorCount > 0) {
    echo "• Check endpoint configurations\n";
    echo "• Verify authentication is working\n";
    echo "• Review server connectivity\n";
}

echo "\n✨ Test completed!\n";



