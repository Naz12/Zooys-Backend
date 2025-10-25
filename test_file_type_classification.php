<?php
/**
 * Test script to verify file type classification and extraction
 * Tests the /api/summarize/async/file endpoint with different file types
 */

require_once 'vendor/autoload.php';

// Configuration
$baseUrl = 'http://localhost:8000/api';
$token = '1|VSwn9FCqLFivSUGJKMukhq7kcrnXDK8h6JQmleJX97994aca'; // Valid token

echo "=== File Type Classification Test ===\n\n";

// Test file types and their expected classifications
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
    
    // Step 1: Upload file
    echo "Step 1: Uploading file...\n";
    $uploadResult = uploadFile($filePath, $token);
    
    if (!$uploadResult['success']) {
        echo "❌ Upload failed: " . $uploadResult['error'] . "\n\n";
        continue;
    }
    
    $fileId = $uploadResult['file_id'];
    echo "✅ File uploaded successfully. File ID: {$fileId}\n";
    
    // Step 2: Test file type classification
    echo "Step 2: Testing file type classification...\n";
    $classificationResult = testFileClassification($fileId, $token);
    
    if ($classificationResult['success']) {
        $detectedType = $classificationResult['file_type'];
        echo "✅ File type detected: {$detectedType}\n";
        
        if ($detectedType === $expectedType) {
            echo "✅ Type classification correct!\n";
        } else {
            echo "⚠️ Type classification mismatch. Expected: {$expectedType}, Got: {$detectedType}\n";
        }
    } else {
        echo "❌ Classification failed: " . $classificationResult['error'] . "\n";
    }
    
    // Step 3: Test content extraction
    echo "Step 3: Testing content extraction...\n";
    $extractionResult = testContentExtraction($fileId, $token);
    
    if ($extractionResult['success']) {
        echo "✅ Content extraction successful!\n";
        echo "   - Word count: " . $extractionResult['word_count'] . "\n";
        echo "   - Character count: " . $extractionResult['character_count'] . "\n";
        echo "   - Content preview: " . substr($extractionResult['content'], 0, 100) . "...\n";
    } else {
        echo "❌ Content extraction failed: " . $extractionResult['error'] . "\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

/**
 * Upload file to get file ID
 */
function uploadFile($filePath, $token) {
    $url = 'http://localhost:8000/api/files/upload';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token
    ]);
    
    $postData = [
        'file' => new CURLFile($filePath)
    ];
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return [
            'success' => false,
            'error' => "HTTP {$httpCode}: {$response}"
        ];
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['file_id'])) {
        return [
            'success' => true,
            'file_id' => $data['file_id']
        ];
    } else {
        return [
            'success' => false,
            'error' => $data['error'] ?? 'Unknown error'
        ];
    }
}

/**
 * Test file type classification
 */
function testFileClassification($fileId, $token) {
    $url = 'http://localhost:8000/api/files/' . $fileId;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return [
            'success' => false,
            'error' => "HTTP {$httpCode}: {$response}"
        ];
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['file_type'])) {
        return [
            'success' => true,
            'file_type' => $data['file_type']
        ];
    } else {
        return [
            'success' => false,
            'error' => 'File type not found in response'
        ];
    }
}

/**
 * Test content extraction
 */
function testContentExtraction($fileId, $token) {
    $url = 'http://localhost:8000/api/summarize/async/file';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    
    $postData = json_encode([
        'file_id' => $fileId,
        'options' => [
            'language' => 'en',
            'format' => 'detailed'
        ]
    ]);
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
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
            'message' => 'Job created successfully'
        ];
    } else {
        return [
            'success' => false,
            'error' => $data['error'] ?? 'Unknown error'
        ];
    }
}

echo "=== Test Complete ===\n";
echo "Check the job status using check_job_status.php with the job IDs returned above.\n";
?>


