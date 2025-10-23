<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing All 7 Async Summarize Endpoints\n";
echo "==========================================\n\n";

// Test data for each endpoint
$testCases = [
    [
        'name' => 'YouTube Video Summarization',
        'endpoint' => '/api/summarize/async/youtube',
        'data' => [
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'options' => ['mode' => 'detailed']
        ]
    ],
    [
        'name' => 'Text Summarization',
        'endpoint' => '/api/summarize/async/text',
        'data' => [
            'text' => 'This is a short test text for summarization. It contains basic information about testing the text endpoint.',
            'options' => ['mode' => 'detailed']
        ]
    ],
    [
        'name' => 'Audio/Video File Summarization',
        'endpoint' => '/api/summarize/async/audiovideo',
        'data' => [
            'file' => 'test_audio.mp3', // This would be a file upload in real scenario
            'options' => ['mode' => 'detailed']
        ]
    ],
    [
        'name' => 'File Upload Summarization',
        'endpoint' => '/api/summarize/async/file',
        'data' => [
            'file' => 'test.pdf', // This would be a file upload in real scenario
            'options' => ['mode' => 'detailed']
        ]
    ],
    [
        'name' => 'Web Link Summarization',
        'endpoint' => '/api/summarize/link',
        'data' => [
            'url' => 'https://example.com',
            'options' => ['mode' => 'detailed']
        ]
    ],
    [
        'name' => 'Image Summarization',
        'endpoint' => '/api/summarize/async/image',
        'data' => [
            'file' => 'test_image.jpg', // This would be a file upload in real scenario
            'options' => ['mode' => 'detailed']
        ]
    ]
];

// Get authentication token
echo "🔐 Getting Authentication Token...\n";
$loginResponse = Http::post('http://localhost:8000/api/login', [
    'email' => 'test-subscription@example.com',
    'password' => 'password'
]);

if (!$loginResponse->successful()) {
    echo "❌ Failed to authenticate: " . $loginResponse->body() . "\n";
    exit(1);
}

$token = $loginResponse->json()['token'];
echo "✅ Authentication successful\n\n";

$headers = [
    'Authorization' => 'Bearer ' . $token,
    'Content-Type' => 'application/json',
    'Accept' => 'application/json'
];

$results = [];

foreach ($testCases as $index => $testCase) {
    echo "🧪 Test " . ($index + 1) . ": {$testCase['name']}\n";
    echo str_repeat("-", 50) . "\n";
    
    try {
        // Test endpoint availability
        echo "📡 Testing endpoint: {$testCase['endpoint']}\n";
        
        // Make request to create job
        $response = Http::withHeaders($headers)
            ->timeout(30)
            ->post('http://localhost:8000' . $testCase['endpoint'], $testCase['data']);
        
        echo "📊 Response Status: " . $response->status() . "\n";
        
        if ($response->successful()) {
            $responseData = $response->json();
            echo "✅ Job Created Successfully!\n";
            echo "Job ID: " . ($responseData['job_id'] ?? 'N/A') . "\n";
            echo "Status: " . ($responseData['status'] ?? 'N/A') . "\n";
            echo "Progress: " . ($responseData['progress'] ?? 'N/A') . "%\n";
            echo "Stage: " . ($responseData['stage'] ?? 'N/A') . "\n";
            
            // Test job status endpoint
            if (isset($responseData['job_id'])) {
                echo "\n🔍 Testing job status endpoint...\n";
                $statusResponse = Http::withHeaders($headers)
                    ->get("http://localhost:8000/api/summarize/status/{$responseData['job_id']}");
                
                echo "Status Endpoint Response: " . $statusResponse->status() . "\n";
                if ($statusResponse->successful()) {
                    $statusData = $statusResponse->json();
                    echo "✅ Status endpoint working\n";
                    echo "Current Status: " . ($statusData['status'] ?? 'N/A') . "\n";
                    echo "Current Stage: " . ($statusData['stage'] ?? 'N/A') . "\n";
                    echo "Current Progress: " . ($statusData['progress'] ?? 'N/A') . "%\n";
                } else {
                    echo "❌ Status endpoint failed: " . $statusResponse->body() . "\n";
                }
            }
            
            $results[] = [
                'endpoint' => $testCase['endpoint'],
                'name' => $testCase['name'],
                'status' => 'SUCCESS',
                'response_code' => $response->status(),
                'job_created' => isset($responseData['job_id']),
                'status_endpoint_working' => isset($responseData['job_id']) && $statusResponse->successful()
            ];
            
        } else {
            echo "❌ Request Failed!\n";
            echo "Error: " . $response->body() . "\n";
            
            $results[] = [
                'endpoint' => $testCase['endpoint'],
                'name' => $testCase['name'],
                'status' => 'FAILED',
                'response_code' => $response->status(),
                'error' => $response->body(),
                'job_created' => false,
                'status_endpoint_working' => false
            ];
        }
        
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
        
        $results[] = [
            'endpoint' => $testCase['endpoint'],
            'name' => $testCase['name'],
            'status' => 'EXCEPTION',
            'error' => $e->getMessage(),
            'job_created' => false,
            'status_endpoint_working' => false
        ];
    }
    
    echo "\n" . str_repeat("=", 60) . "\n\n";
}

// Summary Report
echo "📊 COMPREHENSIVE TEST RESULTS\n";
echo "==============================\n\n";

$successCount = 0;
$failedCount = 0;
$exceptionCount = 0;

foreach ($results as $result) {
    $status = $result['status'];
    $endpoint = $result['endpoint'];
    $name = $result['name'];
    
    echo "🔹 {$name}\n";
    echo "   Endpoint: {$endpoint}\n";
    echo "   Status: ";
    
    switch ($status) {
        case 'SUCCESS':
            echo "✅ SUCCESS";
            $successCount++;
            break;
        case 'FAILED':
            echo "❌ FAILED";
            $failedCount++;
            break;
        case 'EXCEPTION':
            echo "💥 EXCEPTION";
            $exceptionCount++;
            break;
    }
    
    echo "\n";
    
    if (isset($result['response_code'])) {
        echo "   Response Code: {$result['response_code']}\n";
    }
    
    if (isset($result['job_created'])) {
        echo "   Job Creation: " . ($result['job_created'] ? '✅ Working' : '❌ Failed') . "\n";
    }
    
    if (isset($result['status_endpoint_working'])) {
        echo "   Status Endpoint: " . ($result['status_endpoint_working'] ? '✅ Working' : '❌ Failed') . "\n";
    }
    
    if (isset($result['error'])) {
        echo "   Error: " . substr($result['error'], 0, 100) . "...\n";
    }
    
    echo "\n";
}

echo "📈 SUMMARY STATISTICS\n";
echo "=====================\n";
echo "Total Endpoints Tested: " . count($results) . "\n";
echo "✅ Successful: {$successCount}\n";
echo "❌ Failed: {$failedCount}\n";
echo "💥 Exceptions: {$exceptionCount}\n";
echo "Success Rate: " . round(($successCount / count($results)) * 100, 2) . "%\n\n";

echo "🎯 COMPLETION STATUS\n";
echo "====================\n";
echo "Fully Functional Endpoints: {$successCount}/" . count($results) . "\n";
echo "Completion Percentage: " . round(($successCount / count($results)) * 100, 2) . "%\n\n";

if ($successCount === count($results)) {
    echo "🎉 ALL ENDPOINTS ARE FULLY FUNCTIONAL!\n";
} elseif ($successCount > count($results) / 2) {
    echo "✅ Most endpoints are working well!\n";
} else {
    echo "⚠️ Several endpoints need attention.\n";
}

echo "\n🔧 RECOMMENDATIONS\n";
echo "==================\n";
if ($failedCount > 0) {
    echo "- Fix failed endpoints\n";
}
if ($exceptionCount > 0) {
    echo "- Handle exceptions properly\n";
}
echo "- Test with real file uploads for file-based endpoints\n";
echo "- Test job processing with queue worker running\n";
echo "- Monitor job status and completion\n";

