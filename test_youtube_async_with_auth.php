<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use App\Services\YouTubeTranscriberService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing YouTube Async Endpoint with Authentication\n";
echo "==================================================\n\n";

// Test the YouTube async endpoint
$testUrl = "http://localhost:8000/api/summarize/async/youtube";

echo "🎥 Testing YouTube Async Endpoint\n";
echo "URL: {$testUrl}\n";
echo str_repeat("-", 80) . "\n";

try {
    // First, get authentication token
    echo "🔐 Getting authentication token...\n";
    
    $authResponse = Http::post('http://localhost:8000/api/register', [
        'name' => 'Test User',
        'email' => 'test-user@example.com',
        'password' => 'password',
        'password_confirmation' => 'password'
    ]);
    
    if ($authResponse->successful()) {
        $authData = $authResponse->json();
        $token = $authData['token'] ?? null;
        
        if ($token) {
            echo "✅ Authentication successful\n";
            echo "Token: " . substr($token, 0, 20) . "...\n\n";
            
            // Test YouTube async endpoint
            echo "📡 Testing YouTube async endpoint...\n";
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->post($testUrl, [
                'url' => 'https://www.youtube.com/watch?v=tXGooH_cbGA'
            ]);
            
            echo "📊 Response Status: " . $response->status() . "\n";
            echo "📊 Response Body: " . $response->body() . "\n";
            
            if ($response->successful()) {
                $data = $response->json();
                $jobId = $data['job_id'] ?? null;
                
                if ($jobId) {
                    echo "✅ Job created successfully!\n";
                    echo "Job ID: {$jobId}\n\n";
                    
                    // Monitor job status
                    echo "📊 Monitoring job status...\n";
                    echo str_repeat("-", 60) . "\n";
                    
                    for ($i = 1; $i <= 20; $i++) {
                        $statusResponse = Http::withHeaders([
                            'Authorization' => 'Bearer ' . $token,
                            'Accept' => 'application/json'
                        ])->get("http://localhost:8000/api/summarize/status/{$jobId}");
                        
                        if ($statusResponse->successful()) {
                            $statusData = $statusResponse->json();
                            $status = $statusData['status'] ?? 'unknown';
                            $progress = $statusData['progress'] ?? 0;
                            $stage = $statusData['stage'] ?? 'unknown';
                            
                            echo "Check {$i}: Status={$status}, Progress={$progress}%, Stage={$stage}\n";
                            
                            if ($status === 'completed') {
                                echo "✅ Job completed successfully!\n";
                                
                                // Get result
                                $resultResponse = Http::withHeaders([
                                    'Authorization' => 'Bearer ' . $token,
                                    'Accept' => 'application/json'
                                ])->get("http://localhost:8000/api/summarize/result/{$jobId}");
                                
                                if ($resultResponse->successful()) {
                                    $resultData = $resultResponse->json();
                                    echo "📋 Result: " . json_encode($resultData, JSON_PRETTY_PRINT) . "\n";
                                }
                                break;
                            } elseif ($status === 'failed') {
                                echo "❌ Job failed!\n";
                                echo "Error: " . ($statusData['error'] ?? 'Unknown error') . "\n";
                                break;
                            }
                        } else {
                            echo "❌ Status check failed: " . $statusResponse->status() . "\n";
                        }
                        
                        sleep(3); // Wait 3 seconds between checks
                    }
                } else {
                    echo "❌ No job ID returned\n";
                }
            } else {
                echo "❌ YouTube async endpoint failed!\n";
                echo "Status: " . $response->status() . "\n";
                echo "Error: " . $response->body() . "\n";
            }
        } else {
            echo "❌ No token in response\n";
        }
    } else {
        echo "❌ Authentication failed!\n";
        echo "Status: " . $authResponse->status() . "\n";
        echo "Error: " . $authResponse->body() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception occurred: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 80) . "\n";

// Test direct transcriber service
echo "\n🧪 Testing Direct Transcriber Service\n";
echo "=====================================\n\n";

try {
    echo "📡 Testing YouTubeTranscriberService directly...\n";
    
    $transcriber = new YouTubeTranscriberService();
    $result = $transcriber->transcribe('https://www.youtube.com/watch?v=tXGooH_cbGA', ['format' => 'bundle']);
    
    echo "📊 Direct transcriber result:\n";
    echo "Success: " . ($result['success'] ? 'true' : 'false') . "\n";
    
    if ($result['success']) {
        echo "Video ID: " . ($result['data']['video_id'] ?? 'N/A') . "\n";
        echo "Article Length: " . strlen($result['data']['article'] ?? '') . " characters\n";
        echo "✅ Direct transcriber service is working!\n";
    } else {
        echo "❌ Direct transcriber service failed!\n";
        echo "Error: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Direct transcriber exception: " . $e->getMessage() . "\n";
}

echo "\n💡 SUMMARY\n";
echo "==========\n";
echo "✅ If YouTube async endpoint works: Authentication is properly configured\n";
echo "✅ If direct transcriber works: Service is working independently\n";
echo "❌ If both fail: There's a configuration or service issue\n";



