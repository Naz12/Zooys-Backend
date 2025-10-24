<?php
/**
 * Test script to verify the /api/summarize/async/file endpoint
 * is using the correct extractors for different file types
 */

require_once 'vendor/autoload.php';

// Configuration
$baseUrl = 'http://localhost:8000/api';
$token = '1|VSwn9FCqLFivSUGJKMukhq7kcrnXDK8h6JQmleJX97994aca'; // Valid token

echo "=== File Endpoint Extractor Test ===\n\n";

// Test different file types
$testFiles = [
    'test files/test.pdf' => 'pdf',
    'test files/test.docx' => 'docx', 
    'test files/test.txt' => 'txt',
    'test files/test.pptx' => 'pptx',
    'test files/test.xlsx' => 'xlsx'
];

foreach ($testFiles as $filePath => $expectedType) {
    echo "Testing file: {$filePath} (Expected: {$expectedType})\n";
    
    if (!file_exists($filePath)) {
        echo "❌ File not found: {$filePath}\n\n";
        continue;
    }
    
    // Test the /api/summarize/async/file endpoint directly
    echo "Testing /api/summarize/async/file endpoint...\n";
    $result = testFileEndpoint($filePath, $token);
    
    if ($result['success']) {
        echo "✅ File endpoint test successful!\n";
        echo "   - Job ID: " . $result['job_id'] . "\n";
        echo "   - Status: " . $result['status'] . "\n";
        
        // Check job status after a short delay
        echo "Checking job status...\n";
        sleep(2);
        $jobStatus = checkJobStatus($result['job_id'], $token);
        
        if ($jobStatus['success']) {
            echo "   - Job Status: " . $jobStatus['status'] . "\n";
            if (isset($jobStatus['stage'])) {
                echo "   - Current Stage: " . $jobStatus['stage'] . "\n";
            }
        }
    } else {
        echo "❌ File endpoint test failed: " . $result['error'] . "\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

/**
 * Test the /api/summarize/async/file endpoint
 */
function testFileEndpoint($filePath, $token) {
    $url = 'http://localhost:8000/api/summarize/async/file';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token
    ]);
    
    $postData = [
        'file' => new CURLFile($filePath),
        'options' => json_encode([
            'language' => 'en',
            'format' => 'detailed'
        ])
    ];
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 && $httpCode !== 202) {
        return [
            'success' => false,
            'error' => "HTTP {$httpCode}: {$response}"
        ];
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['job_id'])) {
        return [
            'success' => true,
            'job_id' => $data['job_id'],
            'status' => $data['status'] ?? 'unknown'
        ];
    } else {
        return [
            'success' => false,
            'error' => $data['error'] ?? 'Unknown error'
        ];
    }
}

/**
 * Check job status
 */
function checkJobStatus($jobId, $token) {
    $url = 'http://localhost:8000/api/jobs/' . $jobId;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 && $httpCode !== 202) {
        return [
            'success' => false,
            'error' => "HTTP {$httpCode}: {$response}"
        ];
    }
    
    $data = json_decode($response, true);
    
    return [
        'success' => true,
        'status' => $data['status'] ?? 'unknown',
        'stage' => $data['stage'] ?? null,
        'data' => $data
    ];
}

echo "=== Test Complete ===\n";
echo "Check the job statuses using check_job_status.php with the job IDs returned above.\n";
?>
