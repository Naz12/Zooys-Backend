<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing Text Endpoint with User's Text\n";
echo "======================================\n\n";

// Test credentials
$email = 'test-subscription@example.com';
$password = 'password';

// User's text to summarize
$userText = "Born into a wealthy family in New York City, Trump graduated from the University of Pennsylvania in 1968 with a bachelor's degree in economics. He became the president of his family's real estate business in 1971, renamed it the Trump Organization, and began acquiring and building skyscrapers, hotels, casinos, and golf courses. He launched side ventures, many licensing the Trump name, and filed for six business bankruptcies in the 1990s and 2000s";

echo "📝 User's Text to Summarize:\n";
echo "============================\n";
echo $userText . "\n\n";

try {
    // Login to get token
    echo "🔐 Getting authentication token...\n";
    $loginResponse = Http::timeout(30)->post('http://localhost:8000/api/login', [
        'email' => $email,
        'password' => $password
    ]);
    
    if ($loginResponse->successful()) {
        $loginData = $loginResponse->json();
        $token = $loginData['token'] ?? null;
        
        if ($token) {
            echo "✅ Authentication successful!\n\n";
            
            // Test text endpoint with user's text
            echo "🔍 Testing Text Summarization Endpoint\n";
            echo "=====================================\n";
            
            $textData = [
                'text' => $userText,
                'options' => [
                    'format' => 'detailed',
                    'language' => 'en',
                    'focus' => 'summary'
                ]
            ];
            
            echo "📦 Sending request to text endpoint...\n";
            
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
                    
                    // Process the job manually since queue worker might not be running
                    echo "⚙️ Processing job manually...\n";
                    $processResult = shell_exec('php artisan queue:work --once 2>&1');
                    echo "📊 Queue processing result: " . $processResult . "\n\n";
                    
                    // Poll for job completion
                    echo "⏳ Checking job status...\n";
                    $maxAttempts = 5;
                    $attempt = 0;
                    $completed = false;
                    
                    while ($attempt < $maxAttempts && !$completed) {
                        sleep(2);
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
                                    echo "✅ Result retrieved successfully!\n\n";
                                    
                                    // Show the final result
                                    echo "🎯 FINAL SUMMARIZATION RESULT\n";
                                    echo "============================\n";
                                    
                                    $success = $resultData['data']['success'] ?? false;
                                    if ($success) {
                                        echo "✅ SUCCESS: Text summarization completed!\n\n";
                                        
                                        $summary = $resultData['data']['summary'] ?? 'No summary available';
                                        echo "📝 SUMMARY:\n";
                                        echo "-----------\n";
                                        echo $summary . "\n\n";
                                        
                                        if (isset($resultData['data']['ai_result'])) {
                                            $aiResult = $resultData['data']['ai_result'];
                                            echo "🤖 AI RESULT DETAILS:\n";
                                            echo "---------------------\n";
                                            echo "ID: " . ($aiResult['id'] ?? 'N/A') . "\n";
                                            echo "Title: " . ($aiResult['title'] ?? 'N/A') . "\n";
                                            echo "File URL: " . ($aiResult['file_url'] ?? 'N/A') . "\n";
                                        }
                                        
                                        if (isset($resultData['data']['bundle'])) {
                                            $bundle = $resultData['data']['bundle'];
                                            echo "\n📦 BUNDLE DETAILS:\n";
                                            echo "------------------\n";
                                            echo "Format: " . ($bundle['format'] ?? 'N/A') . "\n";
                                            echo "Language: " . ($bundle['language'] ?? 'N/A') . "\n";
                                            
                                            if (isset($bundle['meta'])) {
                                                $meta = $bundle['meta'];
                                                echo "Model Used: " . ($meta['ai_model_used'] ?? 'N/A') . "\n";
                                                echo "Tokens Used: " . ($meta['ai_tokens_used'] ?? 'N/A') . "\n";
                                                echo "Confidence Score: " . ($meta['ai_confidence_score'] ?? 'N/A') . "\n";
                                            }
                                        }
                                    } else {
                                        echo "❌ FAILED: Text summarization failed!\n";
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

echo "\n✨ Text summarization test completed!\n";




