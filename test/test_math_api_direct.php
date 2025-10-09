<?php
/**
 * Direct test of Math API endpoints
 * This script tests the math API directly without any frontend interference
 */

// Test data
$testData = [
    'problem_text' => '2+2',
    'subject_area' => 'general',
    'difficulty_level' => 'intermediate',
    'problem_type' => 'text'
];

// Test URL
$url = 'http://localhost:8000/api/math/solve';

// Test with invalid token (should get 401)
echo "=== Testing Math API with Invalid Token ===\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer invalid-token'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

echo "HTTP Code: $httpCode\n";
echo "Headers:\n$headers\n";
echo "Body: $body\n";

curl_close($ch);

echo "\n=== Testing Math API without Token ===\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

echo "HTTP Code: $httpCode\n";
echo "Headers:\n$headers\n";
echo "Body: $body\n";

curl_close($ch);

echo "\n=== Testing Math History ===\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/math/history');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer invalid-token'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

echo "HTTP Code: $httpCode\n";
echo "Headers:\n$headers\n";
echo "Body: $body\n";

curl_close($ch);

echo "\n=== Test Complete ===\n";
echo "If you see 401 responses with proper JSON error messages, the API is working correctly.\n";
echo "The frontend redirect issue is likely a frontend configuration problem.\n";
?>


