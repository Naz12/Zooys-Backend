<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing YouTube Async Endpoint with Fallback\n";
echo "=============================================\n\n";

// Test the YouTube async endpoint with fallback
$testUrl = "http://localhost:8000/api/summarize/async/youtube";

echo "🎥 Testing YouTube Async Endpoint with Fallback\n";
echo "URL: {$testUrl}\n";
echo str_repeat("-", 80) . "\n";

try {
    // First, get authentication token
    echo "🔐 Getting authentication token...\n";
    
    $authResponse = Http::post('http://localhost:8000/api/register', [
        'name' => 'Test User Fallback',
        'email' => 'test-fallback@example.com',
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
            echo "📡 Testing YouTube async endpoint with fallback...\n";
            
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
                    echo "📊 Monitoring job status with fallback...\n";
                    echo str_repeat("-", 60) . "\n";
                    
                    for ($i = 1; $i <= 30; $i++) {
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
                                    echo "📋 Result Summary:\n";
                                    echo "Success: " . ($resultData['success'] ? 'true' : 'false') . "\n";
                                    echo "Processing Method: " . ($resultData['metadata']['processing_method'] ?? 'N/A') . "\n";
                                    echo "AI Model: " . ($resultData['metadata']['ai_model_used'] ?? 'N/A') . "\n";
                                    echo "Summary Length: " . strlen($resultData['summary'] ?? '') . " characters\n";
                                    
                                    if (isset($resultData['bundle']['article'])) {
                                        echo "Article Length: " . strlen($resultData['bundle']['article']) . " characters\n";
                                    }
                                    
                                    echo "\n📋 Full Result:\n";
                                    echo json_encode($resultData, JSON_PRETTY_PRINT) . "\n";
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
                        
                        sleep(2); // Wait 2 seconds between checks
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
echo "💡 FALLBACK INTEGRATION TEST\n";
echo "============================\n";
echo "✅ If successful: Fallback strategy is working in production\n";
echo "❌ If failed: Check queue worker and service configuration\n";
echo "🔍 The fallback should activate when transcriber fails\n";



