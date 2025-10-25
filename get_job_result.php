<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Getting Job Result\n";
echo "====================\n\n";

// Test credentials
$email = 'test-subscription@example.com';
$password = 'password';

// Job ID from the previous test
$jobId = '3827e854-0eb1-4528-8d15-dfc97de03041';

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
            
            // Check job status
            echo "🔍 Checking job status for: {$jobId}\n";
            
            $statusResponse = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json'
                ])
                ->get("http://localhost:8000/api/summarize/status/{$jobId}");
            
            echo "📊 Status Response: " . $statusResponse->status() . "\n";
            echo "📄 Status Body: " . $statusResponse->body() . "\n\n";
            
            // Try to get result directly
            echo "🔍 Getting job result for: {$jobId}\n";
            
            $resultResponse = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json'
                ])
                ->get("http://localhost:8000/api/summarize/result/{$jobId}");
            
            echo "📊 Result Response: " . $resultResponse->status() . "\n";
            echo "📄 Result Body: " . $resultResponse->body() . "\n\n";
            
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

echo "\n✨ Job result retrieval completed!\n";




