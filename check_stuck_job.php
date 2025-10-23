<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Checking Stuck Job: cdd3e549-4ae5-45ce-83c6-9d2800dc8552\n";
echo "============================================================\n\n";

// Get authentication token
$loginResponse = \Illuminate\Support\Facades\Http::post('http://localhost:8000/api/login', [
    'email' => 'test-subscription@example.com',
    'password' => 'password'
]);

if (!$loginResponse->successful()) {
    echo "❌ Failed to authenticate\n";
    exit(1);
}

$token = $loginResponse->json()['token'];
$headers = [
    'Authorization' => 'Bearer ' . $token,
    'Accept' => 'application/json'
];

$jobId = 'cdd3e549-4ae5-45ce-83c6-9d2800dc8552';

echo "📊 Checking Job Status...\n";
$statusResponse = \Illuminate\Support\Facades\Http::withHeaders($headers)
    ->get("http://localhost:8000/api/summarize/status/{$jobId}");

echo "Status Response Code: " . $statusResponse->status() . "\n";

if ($statusResponse->successful()) {
    $statusData = $statusResponse->json();
    echo "✅ Job Status Retrieved\n\n";
    
    echo "📋 JOB DETAILS:\n";
    echo "================\n";
    echo "Job ID: " . ($statusData['job_id'] ?? 'N/A') . "\n";
    echo "Status: " . ($statusData['status'] ?? 'N/A') . "\n";
    echo "Stage: " . ($statusData['stage'] ?? 'N/A') . "\n";
    echo "Progress: " . ($statusData['progress'] ?? 'N/A') . "%\n";
    echo "Error: " . ($statusData['error'] ?? 'None') . "\n";
    
    if (isset($statusData['logs']) && is_array($statusData['logs'])) {
        echo "\n📝 LOGS:\n";
        echo "--------\n";
        foreach ($statusData['logs'] as $log) {
            if (isset($log['timestamp']) && isset($log['message'])) {
                echo "[" . $log['timestamp'] . "] " . $log['message'] . "\n";
            } else {
                echo json_encode($log) . "\n";
            }
        }
    }
    
    if (isset($statusData['metadata'])) {
        echo "\n🔧 METADATA:\n";
        echo "------------\n";
        foreach ($statusData['metadata'] as $key => $value) {
            echo "{$key}: " . (is_array($value) ? json_encode($value) : $value) . "\n";
        }
    }
    
} else {
    echo "❌ Failed to get job status: " . $statusResponse->body() . "\n";
}

echo "\n🔍 Checking Job Result...\n";
$resultResponse = \Illuminate\Support\Facades\Http::withHeaders($headers)
    ->get("http://localhost:8000/api/summarize/result/{$jobId}");

echo "Result Response Code: " . $resultResponse->status() . "\n";

if ($resultResponse->successful()) {
    $resultData = $resultResponse->json();
    echo "✅ Job Result Retrieved\n\n";
    
    echo "📊 RESULT DETAILS:\n";
    echo "==================\n";
    echo "Success: " . ($resultData['success'] ?? 'N/A') . "\n";
    echo "Data: " . json_encode($resultData['data'] ?? [], JSON_PRETTY_PRINT) . "\n";
    
} else {
    echo "❌ Failed to get job result: " . $resultResponse->body() . "\n";
}

echo "\n🔧 RECOMMENDATIONS:\n";
echo "===================\n";
echo "1. Check if the job is stuck in 'running' status\n";
echo "2. Look for error messages in logs\n";
echo "3. Check if the job is being re-queued\n";
echo "4. Consider manually failing the job if it's stuck\n";
echo "5. Check queue worker logs for processing errors\n";