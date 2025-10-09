<?php

// Test script for Math API with image upload
$apiUrl = 'http://localhost:8000/api/math/solve';
$token = 'your_auth_token_here'; // You'll need to replace this with a valid token

// Check if test image exists
$imagePath = 'test_image.jpg';
if (!file_exists($imagePath)) {
    echo "❌ Test image 'test_image.jpg' not found in current directory\n";
    echo "Please make sure the test image is in the same directory as this script.\n";
    exit(1);
}

echo "🧪 Testing Math API with Image Upload\n";
echo "=====================================\n";
echo "📁 Image file: $imagePath\n";
echo "📏 File size: " . filesize($imagePath) . " bytes\n";
echo "🔗 API URL: $apiUrl\n\n";

// Create cURL request
$ch = curl_init();

// Prepare the multipart form data
$postData = [
    'problem_image' => new CURLFile($imagePath, 'image/jpeg', 'test_image.jpg'),
    'subject_area' => 'maths',
    'difficulty_level' => 'intermediate'
];

curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
        'Origin: http://localhost:3000'
    ],
    CURLOPT_VERBOSE => true,
    CURLOPT_STDERR => fopen('php://temp', 'w+')
]);

echo "🚀 Sending request...\n";
$startTime = microtime(true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

$endTime = microtime(true);
$duration = round(($endTime - $startTime) * 1000, 2);

// Get verbose output
rewind(curl_getinfo($ch, CURLOPT_STDERR));
$verboseOutput = stream_get_contents(curl_getinfo($ch, CURLOPT_STDERR));

curl_close($ch);

echo "⏱️  Request duration: {$duration}ms\n";
echo "📊 HTTP Status: $httpCode\n\n";

if ($error) {
    echo "❌ cURL Error: $error\n";
    exit(1);
}

echo "📥 Response:\n";
echo "============\n";

if ($httpCode === 200) {
    echo "✅ Success! Response received:\n";
    $responseData = json_decode($response, true);
    
    if ($responseData) {
        echo "📋 Math Problem ID: " . ($responseData['math_problem']['id'] ?? 'N/A') . "\n";
        echo "📋 Subject Area: " . ($responseData['math_problem']['subject_area'] ?? 'N/A') . "\n";
        echo "📋 Difficulty: " . ($responseData['math_problem']['difficulty_level'] ?? 'N/A') . "\n";
        echo "📋 Problem Type: " . ($responseData['math_problem']['problem_type'] ?? 'N/A') . "\n";
        echo "📋 Image URL: " . ($responseData['math_problem']['problem_image'] ?? 'N/A') . "\n\n";
        
        echo "🧮 Solution:\n";
        echo "============\n";
        echo "📋 Method: " . ($responseData['math_solution']['solution_method'] ?? 'N/A') . "\n";
        echo "📋 Final Answer: " . ($responseData['math_solution']['final_answer'] ?? 'N/A') . "\n";
        echo "📋 Explanation: " . ($responseData['math_solution']['explanation'] ?? 'N/A') . "\n";
        echo "📋 Verification: " . ($responseData['math_solution']['verification'] ?? 'N/A') . "\n\n";
        
        echo "📝 Step-by-step Solution:\n";
        echo "========================\n";
        echo $responseData['math_solution']['step_by_step_solution'] ?? 'N/A';
        echo "\n\n";
        
        echo "🔗 AI Result ID: " . ($responseData['ai_result']['id'] ?? 'N/A') . "\n";
        echo "🔗 File URL: " . ($responseData['ai_result']['file_url'] ?? 'N/A') . "\n";
    } else {
        echo "⚠️  Response is not valid JSON\n";
        echo "Raw response: $response\n";
    }
} else {
    echo "❌ Error! HTTP $httpCode\n";
    echo "Response: $response\n";
}

echo "\n🔍 Verbose cURL Output:\n";
echo "======================\n";
echo $verboseOutput;

echo "\n✅ Test completed!\n";
