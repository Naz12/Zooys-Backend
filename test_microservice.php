<?php
/**
 * Test script for Document Extraction Microservice
 */

require_once 'vendor/autoload.php';

use App\Services\DocumentExtractionMicroservice;

echo "=== Document Extraction Microservice Test ===\n\n";

// Initialize the service
$service = new DocumentExtractionMicroservice();

// Test 1: Health Check
echo "1. Testing Health Check...\n";
$health = $service->healthCheck();
if ($health['healthy']) {
    echo "✅ Microservice is healthy\n";
    echo "   Status: " . $health['status'] . "\n";
    echo "   Version: " . $health['version'] . "\n";
} else {
    echo "❌ Microservice is not healthy\n";
    echo "   Error: " . $health['error'] . "\n";
}
echo "\n";

// Test 2: Service Info
echo "2. Testing Service Info...\n";
$info = $service->getServiceInfo();
if (isset($info['service'])) {
    echo "✅ Service info retrieved\n";
    echo "   Service: " . $info['service'] . "\n";
    echo "   Version: " . $info['version'] . "\n";
} else {
    echo "❌ Failed to get service info\n";
    echo "   Error: " . ($info['error'] ?? 'Unknown error') . "\n";
}
echo "\n";

// Test 3: PDF Extraction
echo "3. Testing PDF Extraction...\n";
$pdfFile = 'test files/test.pdf';
if (file_exists($pdfFile)) {
    $result = $service->extractText($pdfFile, 'pdf', ['language' => 'en']);
    if ($result['success']) {
        echo "✅ PDF extraction successful\n";
        echo "   Word count: " . $result['word_count'] . "\n";
        echo "   Character count: " . $result['character_count'] . "\n";
        echo "   Text preview: " . substr($result['text'], 0, 100) . "...\n";
    } else {
        echo "❌ PDF extraction failed\n";
        echo "   Error: " . $result['error'] . "\n";
    }
} else {
    echo "❌ Test PDF file not found: {$pdfFile}\n";
}
echo "\n";

// Test 4: Test with different file types
$testFiles = [
    'test files/test.pdf' => 'pdf',
    'test files/test.docx' => 'docx',
    'test files/test.txt' => 'txt',
    'test files/test.pptx' => 'pptx',
    'test files/test.xlsx' => 'xlsx'
];

echo "4. Testing Different File Types...\n";
foreach ($testFiles as $filePath => $fileType) {
    echo "   Testing {$fileType}: ";
    if (file_exists($filePath)) {
        $result = $service->extractText($filePath, $fileType);
        if ($result['success']) {
            echo "✅ Success (Words: {$result['word_count']})\n";
        } else {
            echo "❌ Failed - {$result['error']}\n";
        }
    } else {
        echo "❌ File not found\n";
    }
}

echo "\n=== Test Complete ===\n";
?>

