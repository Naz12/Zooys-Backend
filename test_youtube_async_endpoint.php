<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing YouTube Async Endpoint with Short Videos\n";
echo "==================================================\n\n";

// Test videos (all under 10 minutes)
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

foreach ($testVideos as $index => $video) {
    echo "🎥 Test " . ($index + 1) . ": {$video['title']}\n";
    echo "URL: {$video['url']}\n";
    echo "Duration: {$video['duration']}\n";
    echo str_repeat("-", 60) . "\n";
    
    try {
        // Create job
        echo "📡 Creating job...\n";
        $createResponse = Http::withHeaders($headers)
            ->timeout(30)
            ->post('http://localhost:8000/api/summarize/async/youtube', [
                'url' => $video['url'],
                'options' => ['mode' => 'detailed']
            ]);
        
        echo "📊 Create Response: " . $createResponse->status() . "\n";
        
        if ($createResponse->successful()) {
            $createData = $createResponse->json();
            $jobId = $createData['job_id'] ?? null;
            
            if ($jobId) {
                echo "✅ Job created successfully!\n";
                echo "Job ID: {$jobId}\n";
                
                // Monitor job status
                echo "\n🔍 Monitoring job progress...\n";
                $maxAttempts = 20; // 20 attempts = 2 minutes max
                $attempt = 0;
                $completed = false;
                
                while ($attempt < $maxAttempts && !$completed) {
                    $attempt++;
                    echo "Attempt {$attempt}/{$maxAttempts}...\n";
                    
                    $statusResponse = Http::withHeaders($headers)
                        ->get("http://localhost:8000/api/summarize/status/{$jobId}");
                    
                    if ($statusResponse->successful()) {
                        $statusData = $statusResponse->json();
                        $status = $statusData['status'] ?? 'unknown';
                        $stage = $statusData['stage'] ?? 'unknown';
                        $progress = $statusData['progress'] ?? 0;
                        
                        echo "  Status: {$status}\n";
                        echo "  Stage: {$stage}\n";
                        echo "  Progress: {$progress}%\n";
                        
                        if ($status === 'completed') {
                            echo "✅ Job completed!\n";
                            
                            // Get result
                            $resultResponse = Http::withHeaders($headers)
                                ->get("http://localhost:8000/api/summarize/result/{$jobId}");
                            
                            if ($resultResponse->successful()) {
                                $resultData = $resultResponse->json();
                                echo "📊 Result retrieved successfully!\n";
                                
                                $results[] = [
                                    'video' => $video,
                                    'job_id' => $jobId,
                                    'status' => 'SUCCESS',
                                    'result' => $resultData
                                ];
                                
                                // Display result summary
                                if (isset($resultData['data']['summary'])) {
                                    echo "📝 Summary: " . substr($resultData['data']['summary'], 0, 100) . "...\n";
                                }
                                if (isset($resultData['data']['metadata'])) {
                                    $metadata = $resultData['data']['metadata'];
                                    echo "📊 Metadata: " . json_encode($metadata, JSON_PRETTY_PRINT) . "\n";
                                }
                                
                            } else {
                                echo "❌ Failed to get result: " . $resultResponse->body() . "\n";
                                $results[] = [
                                    'video' => $video,
                                    'job_id' => $jobId,
                                    'status' => 'RESULT_FAILED',
                                    'error' => $resultResponse->body()
                                ];
                            }
                            
                            $completed = true;
                            
                        } elseif ($status === 'failed') {
                            echo "❌ Job failed!\n";
                            $error = $statusData['error'] ?? 'Unknown error';
                            echo "Error: {$error}\n";
                            
                            $results[] = [
                                'video' => $video,
                                'job_id' => $jobId,
                                'status' => 'FAILED',
                                'error' => $error
                            ];
                            
                            $completed = true;
                            
                        } else {
                            // Still processing, wait 6 seconds
                            echo "⏳ Still processing... waiting 6 seconds\n";
                            sleep(6);
                        }
                        
                    } else {
                        echo "❌ Failed to get status: " . $statusResponse->body() . "\n";
                        $results[] = [
                            'video' => $video,
                            'job_id' => $jobId,
                            'status' => 'STATUS_FAILED',
                            'error' => $statusResponse->body()
                        ];
                        $completed = true;
                    }
                }
                
                if (!$completed) {
                    echo "⏰ Timeout: Job didn't complete within 2 minutes\n";
                    $results[] = [
                        'video' => $video,
                        'job_id' => $jobId,
                        'status' => 'TIMEOUT',
                        'error' => 'Job timeout after 2 minutes'
                    ];
                }
                
            } else {
                echo "❌ No job ID returned\n";
                $results[] = [
                    'video' => $video,
                    'status' => 'NO_JOB_ID',
                    'error' => 'No job ID in response'
                ];
            }
            
        } else {
            echo "❌ Job creation failed!\n";
            echo "Error: " . $createResponse->body() . "\n";
            
            $results[] = [
                'video' => $video,
                'status' => 'CREATE_FAILED',
                'error' => $createResponse->body()
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
echo "📊 TEST RESULTS SUMMARY\n";
echo "========================\n\n";

$successCount = 0;
$failedCount = 0;
$timeoutCount = 0;

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
        case 'TIMEOUT':
            echo "⏰ TIMEOUT";
            $timeoutCount++;
            break;
        default:
            echo "💥 ERROR";
            $failedCount++;
    }
    
    echo "\n";
    
    if (isset($result['job_id'])) {
        echo "Job ID: {$result['job_id']}\n";
    }
    
    if (isset($result['error'])) {
        echo "Error: " . substr($result['error'], 0, 100) . "...\n";
    }
    
    if (isset($result['result']['data']['summary'])) {
        echo "Summary: " . substr($result['result']['data']['summary'], 0, 150) . "...\n";
    }
    
    echo "\n";
}

echo "📈 FINAL STATISTICS\n";
echo "===================\n";
echo "Total Tests: " . count($results) . "\n";
echo "✅ Successful: {$successCount}\n";
echo "❌ Failed: {$failedCount}\n";
echo "⏰ Timeout: {$timeoutCount}\n";
echo "Success Rate: " . round(($successCount / count($results)) * 100, 2) . "%\n\n";

if ($successCount === count($results)) {
    echo "🎉 ALL TESTS PASSED!\n";
} elseif ($successCount > 0) {
    echo "✅ Some tests passed!\n";
} else {
    echo "❌ All tests failed!\n";
}

echo "\n🔧 RECOMMENDATIONS\n";
echo "==================\n";
if ($failedCount > 0) {
    echo "- Check transcriber service connectivity\n";
    echo "- Verify AI Manager service status\n";
    echo "- Check queue worker is running\n";
}
if ($timeoutCount > 0) {
    echo "- Increase timeout values for long videos\n";
    echo "- Consider using direct processing for short videos\n";
}
echo "- Monitor job processing times\n";
echo "- Test with different video lengths\n";



