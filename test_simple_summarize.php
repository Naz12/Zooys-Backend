<?php
/**
 * Simple Test Script for /api/summarize/async endpoint
 * Tests basic functionality without authentication
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

echo "🚀 Testing Summarize Async Endpoint\n";
echo "=" . str_repeat("=", 40) . "\n";

// Test 1: Text Input
echo "\n📝 Testing Text Input...\n";
$textData = [
    'content_type' => 'text',
    'source' => [
        'data' => 'Artificial intelligence (AI) is intelligence demonstrated by machines, in contrast to the natural intelligence displayed by humans and animals.'
    ],
    'options' => [
        'summary_length' => 'medium'
    ]
];

$response = makeRequest('POST', 'http://localhost:8000/api/admin/summarize/async', $textData);
echo "HTTP Code: " . $response['http_code'] . "\n";
echo "Response: " . json_encode($response['response'], JSON_PRETTY_PRINT) . "\n";

// Test 2: Web Link
echo "\n🌐 Testing Web Link...\n";
$linkData = [
    'content_type' => 'link',
    'source' => [
        'data' => 'https://en.wikipedia.org/wiki/Artificial_intelligence'
    ],
    'options' => [
        'summary_length' => 'medium'
    ]
];

$response = makeRequest('POST', 'http://localhost:8000/api/admin/summarize/async', $linkData);
echo "HTTP Code: " . $response['http_code'] . "\n";
echo "Response: " . json_encode($response['response'], JSON_PRETTY_PRINT) . "\n";

// Test 3: YouTube Link
echo "\n📺 Testing YouTube Link...\n";
$youtubeData = [
    'content_type' => 'link',
    'source' => [
        'data' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'
    ],
    'options' => [
        'summary_length' => 'medium'
    ]
];

$response = makeRequest('POST', 'http://localhost:8000/api/admin/summarize/async', $youtubeData);
echo "HTTP Code: " . $response['http_code'] . "\n";
echo "Response: " . json_encode($response['response'], JSON_PRETTY_PRINT) . "\n";

echo "\n✅ Basic endpoint tests completed!\n";
