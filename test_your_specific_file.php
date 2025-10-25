<?php

echo "=== Test Your Specific PPTX File ===\n\n";

$pptxFile = __DIR__ . '/your_file.pptx';

if (!file_exists($pptxFile)) {
    echo "❌ your_file.pptx not found.\n";
    echo "Please:\n";
    echo "1. Copy your PPTX file to: " . __DIR__ . "\n";
    echo "2. Rename it to: your_file.pptx\n";
    echo "3. Run this script again\n";
    exit(1);
}

echo "✅ Found your PPTX file!\n\n";

// Analyze the file
echo "=== File Analysis ===\n";
echo "File: $pptxFile\n";
echo "Size: " . filesize($pptxFile) . " bytes\n";
echo "MIME type: " . mime_content_type($pptxFile) . "\n";
echo "Extension: " . pathinfo($pptxFile, PATHINFO_EXTENSION) . "\n";

// Check if it's a valid PPTX file
$zip = new ZipArchive();
$isValidPptx = false;
if ($zip->open($pptxFile) === TRUE) {
    $isValidPptx = true;
    $zip->close();
    echo "✅ File is a valid ZIP/PPTX structure\n";
} else {
    echo "❌ File is NOT a valid ZIP/PPTX structure\n";
}

echo "\n=== MIME Type Check ===\n";
$mimeType = mime_content_type($pptxFile);
$expectedMimeTypes = [
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
    'application/vnd.ms-powerpoint.slide.macroEnabled.12',
    'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
    'application/vnd.ms-powerpoint.template.macroEnabled.12',
    'application/zip',
    'application/x-zip-compressed'
];

echo "Detected MIME type: $mimeType\n";
echo "Expected MIME types:\n";
foreach ($expectedMimeTypes as $expected) {
    echo "- $expected\n";
}

$mimeMatches = in_array($mimeType, $expectedMimeTypes);
echo "\nMIME type matches expected: " . ($mimeMatches ? 'YES' : 'NO') . "\n";

if (!$mimeMatches) {
    echo "⚠️  WARNING: Your file's MIME type doesn't match expected PPTX types!\n";
    echo "This might be why validation is failing.\n";
}

echo "\n=== Test with Your File ===\n";

// Test the endpoint with your file
$baseUrl = 'http://localhost:8000/api';
$token = '201|EickXRnyYCModAsvt98WuWAmLmYXjjFRAoAjZTaq8cec5373';

echo "Testing convert endpoint with your PPTX file...\n";

$ch = curl_init();
$postData = [
    'file' => new CURLFile($pptxFile, $mimeType, 'your_file.pptx'),
    'target_format' => 'pdf'
];

curl_setopt_array($ch, [
    CURLOPT_URL => "$baseUrl/file-processing/convert",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Accept: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
if ($curlError) {
    echo "CURL Error: $curlError\n";
}
echo "Response:\n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT) . "\n\n";

if ($httpCode == 202) {
    echo "✅ SUCCESS! Your PPTX file works with the endpoint!\n";
    echo "The issue is with your Postman request or token.\n";
} else {
    echo "❌ FAILED! Your PPTX file is causing validation errors.\n";
    echo "This might be due to:\n";
    echo "1. File MIME type not recognized\n";
    echo "2. File size too large\n";
    echo "3. File corruption\n";
}

echo "\n=== Test Complete ===\n";