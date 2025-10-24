<?php

/**
 * Test File Extraction and Conversion Endpoints
 * 
 * This script demonstrates how to use the new file extraction and conversion endpoints
 * that integrate with the document converter microservice.
 */

require_once 'vendor/autoload.php';

// Configuration
$baseUrl = 'http://localhost:8000/api';
$authToken = 'YOUR_AUTH_TOKEN_HERE'; // Replace with actual token from login

// Helper function to make authenticated requests
function makeRequest($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init();
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => $data ? json_encode($data) : null,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status_code' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

// Helper function to upload file
function uploadFile($url, $filePath, $token = null) {
    $ch = curl_init();
    
    $headers = [];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    $postData = [
        'file' => new CURLFile($filePath),
        'target_format' => 'pdf'
    ];
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status_code' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

echo "=== File Extraction and Conversion API Test ===\n\n";

// 1. Check microservice health
echo "1. Checking microservice health...\n";
$healthResponse = makeRequest($baseUrl . '/file-processing/health', 'GET', null, $authToken);
echo "Status: " . $healthResponse['status_code'] . "\n";
echo "Response: " . json_encode($healthResponse['body'], JSON_PRETTY_PRINT) . "\n\n";

// 2. Get capabilities
echo "2. Getting conversion capabilities...\n";
$capabilitiesResponse = makeRequest($baseUrl . '/file-processing/conversion-capabilities', 'GET', null, $authToken);
echo "Status: " . $capabilitiesResponse['status_code'] . "\n";
echo "Response: " . json_encode($capabilitiesResponse['body'], JSON_PRETTY_PRINT) . "\n\n";

// 3. Get extraction capabilities
echo "3. Getting extraction capabilities...\n";
$extractionCapabilitiesResponse = makeRequest($baseUrl . '/file-processing/extraction-capabilities', 'GET', null, $authToken);
echo "Status: " . $extractionCapabilitiesResponse['status_code'] . "\n";
echo "Response: " . json_encode($extractionCapabilitiesResponse['body'], JSON_PRETTY_PRINT) . "\n\n";

// 4. Test document conversion (if you have a test file)
if (file_exists('test_pdf.pdf')) {
    echo "4. Testing document conversion...\n";
    $convertResponse = uploadFile($baseUrl . '/file-processing/convert', 'test_pdf.pdf', $authToken);
    echo "Status: " . $convertResponse['status_code'] . "\n";
    echo "Response: " . json_encode($convertResponse['body'], JSON_PRETTY_PRINT) . "\n\n";
    
    if (isset($convertResponse['body']['job_id'])) {
        $jobId = $convertResponse['body']['job_id'];
        echo "Job ID: " . $jobId . "\n";
        
        // Poll for status
        echo "5. Polling job status...\n";
        $maxAttempts = 10;
        $attempt = 0;
        
        while ($attempt < $maxAttempts) {
            sleep(2);
            $attempt++;
            
            $statusResponse = makeRequest($baseUrl . '/status/' . $jobId, 'GET', null, $authToken);
            echo "Attempt {$attempt}: Status " . $statusResponse['status_code'] . "\n";
            echo "Response: " . json_encode($statusResponse['body'], JSON_PRETTY_PRINT) . "\n";
            
            if (isset($statusResponse['body']['status']) && $statusResponse['body']['status'] === 'completed') {
                echo "Job completed! Getting result...\n";
                $resultResponse = makeRequest($baseUrl . '/result/' . $jobId, 'GET', null, $authToken);
                echo "Result: " . json_encode($resultResponse['body'], JSON_PRETTY_PRINT) . "\n";
                break;
            } elseif (isset($statusResponse['body']['status']) && $statusResponse['body']['status'] === 'failed') {
                echo "Job failed!\n";
                break;
            }
        }
    }
}

// 5. Test content extraction (if you have a test file)
if (file_exists('test_pdf.pdf')) {
    echo "\n6. Testing content extraction...\n";
    $extractResponse = uploadFile($baseUrl . '/file-processing/extract', 'test_pdf.pdf', $authToken);
    echo "Status: " . $extractResponse['status_code'] . "\n";
    echo "Response: " . json_encode($extractResponse['body'], JSON_PRETTY_PRINT) . "\n\n";
    
    if (isset($extractResponse['body']['job_id'])) {
        $jobId = $extractResponse['body']['job_id'];
        echo "Job ID: " . $jobId . "\n";
        
        // Poll for status
        echo "7. Polling extraction job status...\n";
        $maxAttempts = 10;
        $attempt = 0;
        
        while ($attempt < $maxAttempts) {
            sleep(2);
            $attempt++;
            
            $statusResponse = makeRequest($baseUrl . '/status/' . $jobId, 'GET', null, $authToken);
            echo "Attempt {$attempt}: Status " . $statusResponse['status_code'] . "\n";
            echo "Response: " . json_encode($statusResponse['body'], JSON_PRETTY_PRINT) . "\n";
            
            if (isset($statusResponse['body']['status']) && $statusResponse['body']['status'] === 'completed') {
                echo "Extraction completed! Getting result...\n";
                $resultResponse = makeRequest($baseUrl . '/result/' . $jobId, 'GET', null, $authToken);
                echo "Result: " . json_encode($resultResponse['body'], JSON_PRETTY_PRINT) . "\n";
                break;
            } elseif (isset($statusResponse['body']['status']) && $statusResponse['body']['status'] === 'failed') {
                echo "Extraction failed!\n";
                break;
            }
        }
    }
}

echo "\n=== Test Complete ===\n";
