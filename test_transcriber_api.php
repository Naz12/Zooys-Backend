<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

echo "=== Testing YouTube Transcriber API Directly ===\n\n";

$apiUrl = 'https://transcriber.akmicroservice.com';
$key = 'dev-local';
$url = 'https://www.youtube.com/watch?v=x9B02pFKpJo'; // Short video for testing

echo "API URL: {$apiUrl}\n";
echo "Video URL: {$url}\n";
echo "Client Key: {$key}\n\n";

// Test 1: Direct API call with bundle format
echo "=== TEST 1: Bundle Format (Article + JSON + Meta) ===\n";
$startTime = microtime(true);

try {
    $response = Http::timeout(120)
        ->withHeaders([
            'X-Client-Key' => $key,
        ])
        ->get($apiUrl . '/subtitles', [
            'url' => $url,
            'format' => 'bundle',
            'include_meta' => '1'
        ]);

    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);

    echo "HTTP Status: " . $response->status() . "\n";
    echo "Duration: {$duration} seconds\n";

    if ($response->successful()) {
        $data = $response->json();
        echo "✅ Success!\n";
        echo "Response structure:\n";
        echo json_encode(array_keys($data), JSON_PRETTY_PRINT) . "\n";
        
        if (isset($data['article'])) {
            echo "\n📄 Article content (first 200 chars):\n";
            echo substr($data['article'], 0, 200) . "...\n";
        }
        
        if (isset($data['json'])) {
            echo "\n📊 JSON segments count: " . count($data['json']['segments'] ?? []) . "\n";
        }
        
        if (isset($data['meta'])) {
            echo "\n📋 Metadata:\n";
            echo json_encode($data['meta'], JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "❌ Failed: " . $response->body() . "\n";
    }

} catch (\Exception $e) {
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    echo "❌ Exception after {$duration} seconds: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("-", 80) . "\n\n";

// Test 2: Async job approach
echo "=== TEST 2: Async Job Approach ===\n";
$startTime = microtime(true);

try {
    // A) Start job
    echo "A) Starting job...\n";
    $jobResponse = Http::timeout(30)
        ->withHeaders([
            'X-Client-Key' => $key,
        ])
        ->get($apiUrl . '/subtitles', [
            'url' => $url,
            'format' => 'article',
            'include_meta' => '1'
        ]);

    echo "Job HTTP Status: " . $jobResponse->status() . "\n";

    if ($jobResponse->status() === 202) {
        $jobData = $jobResponse->json();
        $jobKey = $jobData['job_key'] ?? $jobData['jobKey'] ?? $jobData['job'] ?? null;
        
        if ($jobKey) {
            echo "✅ Job started: {$jobKey}\n";
            
            // B) Poll status
            echo "B) Polling status...\n";
            $maxAttempts = 60; // 1 minute max
            $attempt = 0;
            
            while ($attempt < $maxAttempts) {
                $attempt++;
                echo "Attempt {$attempt}: ";
                
                $statusResponse = Http::timeout(10)
                    ->withHeaders([
                        'X-Client-Key' => $key,
                    ])
                    ->get($apiUrl . '/status', [
                        'job_key' => $jobKey
                    ]);
                
                if ($statusResponse->successful()) {
                    $statusData = $statusResponse->json();
                    $status = $statusData['status'] ?? 'unknown';
                    echo "Status: {$status}\n";
                    
                    if ($status === 'completed') {
                        echo "✅ Job completed!\n";
                        break;
                    } elseif ($status === 'failed') {
                        echo "❌ Job failed\n";
                        break;
                    }
                } else {
                    echo "❌ Status check failed\n";
                }
                
                if ($attempt < $maxAttempts) {
                    sleep(1);
                }
            }
            
            // C) Fetch result
            if ($attempt < $maxAttempts) {
                echo "C) Fetching result...\n";
                $resultResponse = Http::timeout(30)
                    ->withHeaders([
                        'X-Client-Key' => $key,
                    ])
                    ->get($apiUrl . '/result', [
                        'job_key' => $jobKey
                    ]);
                
                if ($resultResponse->successful()) {
                    $resultData = $resultResponse->json();
                    echo "✅ Result fetched!\n";
                    echo "Result structure:\n";
                    echo json_encode(array_keys($resultData), JSON_PRETTY_PRINT) . "\n";
                    
                    if (isset($resultData['subtitle_text'])) {
                        echo "\n📝 Content length: " . strlen($resultData['subtitle_text']) . " characters\n";
                        echo "Content preview: " . substr($resultData['subtitle_text'], 0, 200) . "...\n";
                    }
                } else {
                    echo "❌ Failed to fetch result: " . $resultResponse->body() . "\n";
                }
            }
        } else {
            echo "❌ No job key received\n";
        }
    } else {
        echo "❌ Job start failed: " . $jobResponse->body() . "\n";
    }

    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    echo "\nTotal duration: {$duration} seconds\n";

} catch (\Exception $e) {
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    echo "❌ Exception after {$duration} seconds: " . $e->getMessage() . "\n";
}

echo "\n=== API TEST COMPLETE ===\n";







