<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing All 7 Specialized Endpoints - Detailed Analysis\n";
echo "========================================================\n\n";

// Test data for each endpoint
$endpointTests = [
    'youtube' => [
        'url' => 'http://localhost:8000/api/summarize/async/youtube',
        'data' => [
            'url' => 'https://www.youtube.com/watch?v=XDNeGenHIM0',
            'options' => ['format' => 'detailed', 'language' => 'en']
        ],
        'description' => 'YouTube Video Summarization'
    ],
    'text' => [
        'url' => 'http://localhost:8000/api/summarize/async/text',
        'data' => [
            'text' => 'This is a comprehensive test of the text summarization endpoint. It should process this text and return a summary.',
            'options' => ['format' => 'detailed', 'language' => 'en', 'focus' => 'summary']
        ],
        'description' => 'Text Summarization'
    ],
    'audiovideo' => [
        'url' => 'http://localhost:8000/api/summarize/async/audiovideo',
        'data' => [
            'file' => 'test files/test video.mp4',
            'options' => ['format' => 'detailed', 'language' => 'en']
        ],
        'description' => 'Audio/Video File Summarization'
    ],
    'file' => [
        'url' => 'http://localhost:8000/api/summarize/async/file',
        'data' => [
            'file' => 'test files/test.pdf',
            'options' => ['format' => 'detailed', 'language' => 'en']
        ],
        'description' => 'File Upload Summarization'
    ],
    'link' => [
        'url' => 'http://localhost:8000/api/summarize/link',
        'data' => [
            'url' => 'https://example.com',
            'options' => ['format' => 'detailed', 'language' => 'en']
        ],
        'description' => 'Web Link Summarization'
    ],
    'image' => [
        'url' => 'http://localhost:8000/api/summarize/async/image',
        'data' => [
            'file' => 'test files/test.png',
            'options' => ['format' => 'detailed', 'language' => 'en']
        ],
        'description' => 'Image Summarization'
    ]
];

$results = [];

// Test each endpoint
foreach ($endpointTests as $endpoint => $test) {
    echo "🔍 Testing {$test['description']} ({$endpoint})\n";
    echo str_repeat("=", 50) . "\n";
    
    try {
        echo "📡 Making request to: {$test['url']}\n";
        echo "📦 Request data: " . json_encode($test['data'], JSON_PRETTY_PRINT) . "\n\n";
        
        $response = Http::timeout(30)->post($test['url'], $test['data']);
        
        echo "📊 Response Status: " . $response->status() . "\n";
        echo "📄 Response Headers: " . json_encode($response->headers(), JSON_PRETTY_PRINT) . "\n";
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

// Summary Report
echo "📊 DETAILED TEST RESULTS SUMMARY\n";
echo "================================\n\n";

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

// Detailed Analysis
echo "🔍 DETAILED ANALYSIS\n";
echo "====================\n\n";

foreach ($results as $endpoint => $result) {
    echo "📋 {$endpoint} Endpoint Analysis:\n";
    echo "   Status: {$result['status']}\n";
    
    if (isset($result['response_code'])) {
        echo "   HTTP Code: {$result['response_code']}\n";
        
        // Analyze HTTP status codes
        switch ($result['response_code']) {
            case 200:
                echo "   ✅ Success - Endpoint working correctly\n";
                break;
            case 401:
                echo "   🔐 Authentication Required - Bearer token needed\n";
                break;
            case 404:
                echo "   🚫 Not Found - Route not defined or incorrect URL\n";
                break;
            case 422:
                echo "   📝 Validation Error - Request data format issue\n";
                break;
            case 500:
                echo "   💥 Server Error - Internal application error\n";
                break;
            default:
                echo "   ❓ Unknown HTTP status\n";
        }
    }
    
    if (isset($result['error'])) {
        echo "   Error: {$result['error']}\n";
    }
    
    echo "\n";
}

// Recommendations
echo "🔧 RECOMMENDATIONS\n";
echo "==================\n\n";

if ($failedCount > 0) {
    echo "• Authentication Issues:\n";
    echo "  - All endpoints require Bearer token authentication\n";
    echo "  - Need to implement proper token validation\n";
    echo "  - Consider making some endpoints public for testing\n\n";
}

if ($exceptionCount > 0) {
    echo "• Connection Issues:\n";
    echo "  - Check if Laravel server is running on port 8000\n";
    echo "  - Verify route definitions in routes/api.php\n";
    echo "  - Check for any middleware blocking requests\n\n";
}

echo "• Next Steps:\n";
echo "  1. Fix authentication issues for testing\n";
echo "  2. Verify all routes are properly defined\n";
echo "  3. Test with proper authentication tokens\n";
echo "  4. Check AI Manager service availability\n\n";

echo "✨ Detailed testing completed!\n";



