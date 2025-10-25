<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing Transcriber Scraper Endpoint Directly\n";
echo "================================================\n\n";

// Test videos (same as before)
$testVideos = [
    [
        'title' => 'Rick Astley - Never Gonna Give You Up',
        'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'duration' => '3:32'
    ],
    [
        'title' => 'Short Tech Tutorial',
        'url' => 'https://www.youtube.com/watch?v=z5YgnzFv3h4',
        'duration' => '5:45'
    ],
    [
        'title' => 'Quick News Update',
        'url' => 'https://www.youtube.com/watch?v=woo-rVRDP0g',
        'duration' => '4:20'
    ]
];

$results = [];

foreach ($testVideos as $index => $video) {
    echo "🎥 Test " . ($index + 1) . ": {$video['title']}\n";
    echo "URL: {$video['url']}\n";
    echo "Duration: {$video['duration']}\n";
    echo str_repeat("-", 60) . "\n";
    
    try {
        // Test Smartproxy endpoint directly
        echo "📡 Testing Smartproxy endpoint...\n";
        $smartproxyUrl = "https://transcriber.akmicroservice.com/scraper/smartproxy/subtitles";
        
        $response = Http::timeout(600) // 10 minutes
            ->connectTimeout(30)
            ->get($smartproxyUrl, [
                'url' => $video['url'],
                'format' => 'bundle'
            ]);
        
        echo "📊 Response Status: " . $response->status() . "\n";
        echo "📊 Response Time: " . $response->transferStats->getHandlerStat('total_time') . " seconds\n";
        
        if ($response->successful()) {
            $data = $response->json();
            echo "✅ Smartproxy endpoint working!\n";
            
            // Display key information
            if (isset($data['video_id'])) {
                echo "Video ID: " . $data['video_id'] . "\n";
            }
            if (isset($data['language'])) {
                echo "Language: " . $data['language'] . "\n";
            }
            if (isset($data['format'])) {
                echo "Format: " . $data['format'] . "\n";
            }
            if (isset($data['article_text'])) {
                $articleLength = strlen($data['article_text']);
                echo "Article Text Length: {$articleLength} characters\n";
                echo "Article Preview: " . substr($data['article_text'], 0, 200) . "...\n";
            }
            if (isset($data['meta'])) {
                echo "Metadata: " . json_encode($data['meta'], JSON_PRETTY_PRINT) . "\n";
            }
            
            $results[] = [
                'video' => $video,
                'status' => 'SUCCESS',
                'response_code' => $response->status(),
                'response_time' => $response->transferStats->getHandlerStat('total_time'),
                'data' => $data
            ];
            
        } else {
            echo "❌ Smartproxy endpoint failed!\n";
            echo "Error: " . $response->body() . "\n";
            
            $results[] = [
                'video' => $video,
                'status' => 'FAILED',
                'response_code' => $response->status(),
                'error' => $response->body()
            ];
        }
        
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
        
        $results[] = [
            'video' => $video,
            'status' => 'EXCEPTION',
            'error' => $e->getMessage()
        ];
    }
    
    echo "\n" . str_repeat("=", 80) . "\n\n";
}

// Summary Report
echo "📊 DIRECT TRANSCRIBER TEST RESULTS\n";
echo "===================================\n\n";

$successCount = 0;
$failedCount = 0;
$exceptionCount = 0;

foreach ($results as $index => $result) {
    $video = $result['video'];
    $status = $result['status'];
    
    echo "🎥 Test " . ($index + 1) . ": {$video['title']}\n";
    echo "URL: {$video['url']}\n";
    echo "Duration: {$video['duration']}\n";
    echo "Status: ";
    
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
        echo "Response Code: {$result['response_code']}\n";
    }
    
    if (isset($result['response_time'])) {
        echo "Response Time: {$result['response_time']} seconds\n";
    }
    
    if (isset($result['error'])) {
        echo "Error: " . substr($result['error'], 0, 100) . "...\n";
    }
    
    if (isset($result['data']['article_text'])) {
        $articleLength = strlen($result['data']['article_text']);
        echo "Article Length: {$articleLength} characters\n";
    }
    
    echo "\n";
}

echo "📈 FINAL STATISTICS\n";
echo "===================\n";
echo "Total Tests: " . count($results) . "\n";
echo "✅ Successful: {$successCount}\n";
echo "❌ Failed: {$failedCount}\n";
echo "💥 Exceptions: {$exceptionCount}\n";
echo "Success Rate: " . round(($successCount / count($results)) * 100, 2) . "%\n\n";

if ($successCount === count($results)) {
    echo "🎉 ALL DIRECT TESTS PASSED!\n";
    echo "The Smartproxy endpoint is working perfectly!\n";
} elseif ($successCount > 0) {
    echo "✅ Some direct tests passed!\n";
    echo "The Smartproxy endpoint is partially working.\n";
} else {
    echo "❌ All direct tests failed!\n";
    echo "The Smartproxy endpoint is not working.\n";
}

echo "\n🔧 RECOMMENDATIONS\n";
echo "==================\n";
if ($successCount > 0) {
    echo "✅ Use Smartproxy endpoint for YouTube processing\n";
    echo "✅ Implement direct processing instead of queue\n";
    echo "✅ This will solve the queue worker issues\n";
} else {
    echo "❌ Check transcriber service connectivity\n";
    echo "❌ Verify service is running\n";
    echo "❌ Check network configuration\n";
}

echo "\n💡 NEXT STEPS\n";
echo "==============\n";
if ($successCount > 0) {
    echo "1. Implement direct YouTube processing using Smartproxy\n";
    echo "2. Remove queue dependency for YouTube videos\n";
    echo "3. Test with AI Manager integration\n";
} else {
    echo "1. Check transcriber service status\n";
    echo "2. Verify network connectivity\n";
    echo "3. Test with different endpoints\n";
}



