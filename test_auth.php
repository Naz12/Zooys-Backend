<?php
/**
 * Test authentication and basic endpoint access
 */

function makeRequest($method, $url, $data = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer 1|VSwn9FCqLFivSUGJKMukhq7kcrnXDK8h6JQmleJX97994aca'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'http_code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

echo "🔐 Testing Authentication...\n";

// Test 1: Try to access a simple endpoint
echo "\n📋 Testing /api/plans (public endpoint)...\n";
$response = makeRequest('GET', 'http://localhost:8000/api/plans');
echo "HTTP Code: " . $response['http_code'] . "\n";
echo "Response: " . json_encode($response['response'], JSON_PRETTY_PRINT) . "\n";

// Test 2: Try to access an authenticated endpoint
echo "\n🔒 Testing /api/admin/dashboard (authenticated endpoint)...\n";
$response = makeRequest('GET', 'http://localhost:8000/api/admin/dashboard');
echo "HTTP Code: " . $response['http_code'] . "\n";
echo "Response: " . json_encode($response['response'], JSON_PRETTY_PRINT) . "\n";

// Test 3: Try to access the summarize endpoint
echo "\n📝 Testing /api/admin/summarize/async (summarize endpoint)...\n";
$textData = [
    'content_type' => 'text',
    'source' => [
        'data' => 'Test text for summarization'
    ],
    'options' => [
        'summary_length' => 'medium'
    ]
];
$response = makeRequest('POST', 'http://localhost:8000/api/admin/summarize/async', $textData);
echo "HTTP Code: " . $response['http_code'] . "\n";
echo "Response: " . json_encode($response['response'], JSON_PRETTY_PRINT) . "\n";

echo "\n✅ Authentication tests completed!\n";






