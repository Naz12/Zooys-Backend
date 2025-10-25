<?php
/**
 * Simple test script for Document Extraction Microservice
 * Tests the FastAPI service directly without Laravel dependencies
 */

echo "=== Document Extraction Microservice Test ===\n\n";

$baseUrl = 'http://localhost:8003';

// Test 1: Health Check
echo "1. Testing Health Check...\n";
$healthResponse = file_get_contents($baseUrl . '/health');
if ($healthResponse) {
    $health = json_decode($healthResponse, true);
    if ($health && isset($health['status'])) {
        echo "✅ Microservice is healthy\n";
        echo "   Status: " . $health['status'] . "\n";
        echo "   Version: " . $health['version'] . "\n";
    } else {
        echo "❌ Invalid health response\n";
    }
} else {
    echo "❌ Cannot connect to microservice\n";
}
echo "\n";

// Test 2: Service Info
echo "2. Testing Service Info...\n";
$infoResponse = file_get_contents($baseUrl . '/');
if ($infoResponse) {
    $info = json_decode($infoResponse, true);
    if ($info && isset($info['service'])) {
        echo "✅ Service info retrieved\n";
        echo "   Service: " . $info['service'] . "\n";
        echo "   Version: " . $info['version'] . "\n";
    } else {
        echo "❌ Invalid service info response\n";
    }
} else {
    echo "❌ Cannot get service info\n";
}
echo "\n";

// Test 3: PDF Extraction
echo "3. Testing PDF Extraction...\n";
$pdfFile = 'test files/test.pdf';
if (file_exists($pdfFile)) {
    // Use cURL to test the extraction endpoint
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/extract');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'file_path' => realpath($pdfFile),
        'file_type' => 'pdf',
        'options' => json_encode(['language' => 'en'])
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if ($result && $result['success']) {
            echo "✅ PDF extraction successful\n";
            echo "   Word count: " . $result['word_count'] . "\n";
            echo "   Character count: " . $result['character_count'] . "\n";
            echo "   Text preview: " . substr($result['text'], 0, 100) . "...\n";
        } else {
            echo "❌ PDF extraction failed\n";
            echo "   Error: " . ($result['error'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "❌ HTTP Error: {$httpCode}\n";
        echo "   Response: {$response}\n";
    }
} else {
    echo "❌ Test PDF file not found: {$pdfFile}\n";
}
echo "\n";

echo "=== Test Complete ===\n";
?>


