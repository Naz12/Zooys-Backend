<?php

require_once 'vendor/autoload.php';

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

// Test the file endpoint with PDF upload
function testFileEndpointWithPDF() {
    echo "=== Testing File Endpoint with PDF ===\n\n";
    
    // Test data
    $baseUrl = 'http://localhost:8000';
    $token = '1|VSwn9FCqLFivSUGJKMukhq7kcrnXDK8h6JQmleJX97994aca'; // Valid token from test_token.php
    
    // Create a test PDF file if it doesn't exist
    $testPdfPath = 'test_pdf.pdf';
    if (!file_exists($testPdfPath)) {
        echo "Creating test PDF file...\n";
        // Create a simple PDF content for testing
        $pdfContent = "%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj

2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj

3 0 obj
<<
/Type /Page
/Parent 2 0 R
/MediaBox [0 0 612 792]
/Contents 4 0 R
>>
endobj

4 0 obj
<<
/Length 44
>>
stream
BT
/F1 12 Tf
100 700 Td
(Hello World - Test PDF Content) Tj
ET
endstream
endobj

xref
0 5
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000204 00000 n 
trailer
<<
/Size 5
/Root 1 0 R
>>
startxref
297
%%EOF";
        
        file_put_contents($testPdfPath, $pdfContent);
        echo "Test PDF created at: $testPdfPath\n";
    }
    
    // Test the file endpoint
    $url = $baseUrl . '/api/summarize/async/file';
    
    // Prepare the request
    $curl = curl_init();
    
    $postData = [
        'file' => new CURLFile($testPdfPath, 'application/pdf', 'test.pdf'),
        'options' => json_encode([
            'language' => 'en',
            'format' => 'detailed',
            'focus' => 'summary'
        ])
    ];
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 30
    ]);
    
    echo "Sending request to: $url\n";
    echo "File: $testPdfPath\n";
    echo "Token: " . substr($token, 0, 20) . "...\n\n";
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    
    curl_close($curl);
    
    echo "HTTP Code: $httpCode\n";
    
    if ($error) {
        echo "cURL Error: $error\n";
        return;
    }
    
    echo "Response:\n";
    $responseData = json_decode($response, true);
    if ($responseData) {
        echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
        
        // If we get a job ID, test the status endpoint
        if (isset($responseData['job_id'])) {
            $jobId = $responseData['job_id'];
            echo "\n=== Testing Job Status ===\n";
            testJobStatus($baseUrl, $jobId, $token);
        }
    } else {
        echo $response . "\n";
    }
}

function testJobStatus($baseUrl, $jobId, $token) {
    $statusUrl = $baseUrl . '/api/summarize/status/' . $jobId;
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $statusUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 10
    ]);
    
    echo "Checking job status: $statusUrl\n";
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    
    curl_close($curl);
    
    echo "Status HTTP Code: $httpCode\n";
    
    if ($error) {
        echo "cURL Error: $error\n";
        return;
    }
    
    echo "Status Response:\n";
    $responseData = json_decode($response, true);
    if ($responseData) {
        echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
        
        // If job is completed, get the result
        if (isset($responseData['status']) && $responseData['status'] === 'completed') {
            echo "\n=== Getting Job Result ===\n";
            testJobResult($baseUrl, $jobId, $token);
        }
    } else {
        echo $response . "\n";
    }
}

function testJobResult($baseUrl, $jobId, $token) {
    $resultUrl = $baseUrl . '/api/summarize/result/' . $jobId;
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $resultUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 10
    ]);
    
    echo "Getting job result: $resultUrl\n";
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    
    curl_close($curl);
    
    echo "Result HTTP Code: $httpCode\n";
    
    if ($error) {
        echo "cURL Error: $error\n";
        return;
    }
    
    echo "Result Response:\n";
    $responseData = json_decode($response, true);
    if ($responseData) {
        echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo $response . "\n";
    }
}

// Run the test
testFileEndpointWithPDF();

?>
