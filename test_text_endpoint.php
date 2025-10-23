<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing Text Endpoint Specifically\n";
echo "====================================\n\n";

// Test credentials
$email = 'test-subscription@example.com';
$password = 'password';

echo "🔐 Getting authentication token...\n";

try {
    // Login to get token
    $loginResponse = Http::timeout(30)->post('http://localhost:8000/api/login', [
        'email' => $email,
        'password' => $password
    ]);
    
    if ($loginResponse->successful()) {
        $loginData = $loginResponse->json();
        $token = $loginData['token'] ?? null;
        
        if ($token) {
            echo "✅ Authentication successful! Token: " . substr($token, 0, 20) . "...\n\n";
            
            // Test text endpoint
            echo "🔍 Testing Text Summarization Endpoint\n";
            echo "=====================================\n";
            
            $textData = [
                'text' => 'Artificial intelligence (AI) is intelligence demonstrated by machines, in contrast to the natural intelligence displayed by humans and animals. Leading AI textbooks define the field as the study of "intelligent agents": any device that perceives its environment and takes actions that maximize its chance of successfully achieving its goals. The term "artificial intelligence" is often used to describe machines that mimic "cognitive" functions that humans associate with the human mind, such as "learning" and "problem solving".',
                'options' => [
                    'format' => 'detailed',
                    'language' => 'en',
                    'focus' => 'summary'
                ]
            ];
            
            echo "📦 Request Data:\n";
            echo "Text: " . substr($textData['text'], 0, 100) . "...\n";
            echo "Options: " . json_encode($textData['options'], JSON_PRETTY_PRINT) . "\n\n";
            
            // Make request to text endpoint
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])
                ->post('http://localhost:8000/api/summarize/async/text', $textData);
            
            echo "📡 Response Status: " . $response->status() . "\n";
            echo "📄 Response Body: " . $response->body() . "\n\n";
            
            if ($response->successful()) {
                $responseData = $response->json();
                echo "✅ Text endpoint request successful!\n";
                echo "📊 Parsed Response: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n\n";
                
                $jobId = $responseData['job_id'] ?? null;
                if ($jobId) {
                    echo "🔄 Job created: {$jobId}\n";
                    echo "📊 Poll URL: " . ($responseData['poll_url'] ?? 'N/A') . "\n";
                    echo "📊 Result URL: " . ($responseData['result_url'] ?? 'N/A') . "\n\n";
                    
                    // Poll for job completion
                    echo "⏳ Polling for job completion...\n";
                    $maxAttempts = 20;
                    $attempt = 0;
                    $completed = false;
                    
                    while ($attempt < $maxAttempts && !$completed) {
                        sleep(3);
                        $attempt++;
                        
                        echo "🔄 Attempt {$attempt}/{$maxAttempts} - Checking job status...\n";
                        
                        $statusResponse = Http::timeout(10)
                            ->withHeaders([
                                'Authorization' => 'Bearer ' . $token,
                                'Accept' => 'application/json'
                            ])
                            ->get("http://localhost:8000/api/summarize/status/{$jobId}");
                        
                        if ($statusResponse->successful()) {
                            $statusData = $statusResponse->json();
                            $status = $statusData['data']['status'] ?? 'unknown';
                            $progress = $statusData['data']['progress'] ?? 0;
                            $stage = $statusData['data']['stage'] ?? 'unknown';
                            
                            echo "   📊 Status: {$status} | Progress: {$progress}% | Stage: {$stage}\n";
                            
                            if ($status === 'completed') {
                                $completed = true;
                                
                                // Get result
                                echo "🎉 Job completed! Getting result...\n";
                                $resultResponse = Http::timeout(10)
                                    ->withHeaders([
                                        'Authorization' => 'Bearer ' . $token,
                                        'Accept' => 'application/json'
                                    ])
                                    ->get("http://localhost:8000/api/summarize/result/{$jobId}");
                                
                                if ($resultResponse->successful()) {
                                    $resultData = $resultResponse->json();
                                    echo "✅ Result retrieved successfully!\n";
                                    echo "📊 Final Result: " . json_encode($resultData, JSON_PRETTY_PRINT) . "\n";
                                    
                                    // Check if result is successful
                                    $success = $resultData['data']['success'] ?? false;
                                    if ($success) {
                                        echo "\n🎉 TEXT SUMMARIZATION SUCCESSFUL!\n";
                                        echo "================================\n";
                                        
                                        $summary = $resultData['data']['summary'] ?? 'No summary available';
                                        echo "📝 Summary: {$summary}\n";
                                        
                                        if (isset($resultData['data']['ai_result'])) {
                                            $aiResult = $resultData['data']['ai_result'];
                                            echo "🤖 AI Result ID: " . ($aiResult['id'] ?? 'N/A') . "\n";
                                            echo "📄 AI Result Title: " . ($aiResult['title'] ?? 'N/A') . "\n";
                                        }
                                    } else {
                                        echo "\n❌ TEXT SUMMARIZATION FAILED!\n";
                                        echo "============================\n";
                                        $error = $resultData['data']['error'] ?? 'Unknown error';
                                        echo "💥 Error: {$error}\n";
                                    }
                                } else {
                                    echo "❌ Failed to get result: " . $resultResponse->status() . "\n";
                                    echo "Response: " . $resultResponse->body() . "\n";
                                }
                            } elseif ($status === 'failed') {
                                $error = $statusData['data']['error'] ?? 'Unknown error';
                                echo "❌ Job failed: {$error}\n";
                                $completed = true;
                            }
                        } else {
                            echo "❌ Failed to check status: " . $statusResponse->status() . "\n";
                            echo "Response: " . $statusResponse->body() . "\n";
                            $completed = true;
                        }
                    }
                    
                    if (!$completed) {
                        echo "⏰ Timeout waiting for job completion after {$maxAttempts} attempts\n";
                    }
                } else {
                    echo "❌ No job ID returned\n";
                }
            } else {
                echo "❌ Text endpoint request failed!\n";
                echo "Status: " . $response->status() . "\n";
                echo "Response: " . $response->body() . "\n";
            }
        } else {
            echo "❌ No token in login response\n";
        }
    } else {
        echo "❌ Login failed: " . $loginResponse->status() . "\n";
        echo "Response: " . $loginResponse->body() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n✨ Text endpoint testing completed!\n";
