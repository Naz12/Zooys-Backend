<?php
/**
 * Test the Document Extraction Microservice Integration
 * This script tests the microservice without Laravel dependencies
 */

echo "=== Document Extraction Microservice Integration Test ===\n\n";

$baseUrl = 'http://localhost:8003';

// Test 1: Health Check
echo "1. Testing Health Check...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/health');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data && isset($data['status'])) {
        echo "✅ Microservice is healthy\n";
        echo "   Status: " . $data['status'] . "\n";
        echo "   Version: " . $data['version'] . "\n";
    } else {
        echo "❌ Invalid health response\n";
    }
} else {
    echo "❌ Cannot connect to microservice (HTTP $httpCode)\n";
    echo "   Make sure the microservice is running on port 8003\n";
    echo "   Start it with: python start_document_service.py\n";
}
echo "\n";

// Test 2: PDF Extraction
echo "2. Testing PDF Extraction...\n";
$pdfFile = 'test files/test.pdf';
if (file_exists($pdfFile)) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/extract');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'file_path' => realpath($pdfFile),
        'file_type' => 'pdf',
        'options' => json_encode(['language' => 'en'])
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
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
        echo "❌ HTTP Error: $httpCode\n";
        echo "   Response: $response\n";
    }
} else {
    echo "❌ Test PDF file not found: $pdfFile\n";
}
echo "\n";

echo "=== Test Complete ===\n";
echo "If the microservice is not running, start it with:\n";
echo "python start_document_service.py\n";
?>

