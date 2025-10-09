<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "🧪 Testing Math API with Image Upload\n";
echo "=====================================\n\n";

// Create or get test user
$user = User::firstOrCreate(
    ['email' => 'test@example.com'],
    [
        'name' => 'Test User',
        'password' => Hash::make('password'),
        'email_verified_at' => now()
    ]
);

echo "👤 Test User: {$user->name} ({$user->email})\n";

// Create auth token
$token = $user->createToken('test-token')->plainTextToken;
echo "🔑 Auth Token: " . substr($token, 0, 20) . "...\n\n";

// Check if test image exists
$imagePath = 'test image.jpg';
if (!file_exists($imagePath)) {
    echo "❌ Test image '$imagePath' not found\n";
    exit(1);
}

echo "📁 Image file: $imagePath\n";
echo "📏 File size: " . filesize($imagePath) . " bytes\n\n";

// Test the API
$apiUrl = 'http://localhost:8000/api/math/solve';

echo "🚀 Testing Math API...\n";
echo "=====================\n";

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
    CURLOPT_TIMEOUT => 30
]);

$startTime = microtime(true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$endTime = microtime(true);
$duration = round(($endTime - $startTime) * 1000, 2);

curl_close($ch);

echo "⏱️  Request duration: {$duration}ms\n";
echo "📊 HTTP Status: $httpCode\n\n";

if ($error) {
    echo "❌ cURL Error: $error\n";
    exit(1);
}

if ($httpCode === 200) {
    echo "✅ Success! Math problem solved from image\n";
    echo "==========================================\n";
    
    $responseData = json_decode($response, true);
    
    if ($responseData) {
        echo "📋 Math Problem:\n";
        echo "  - ID: " . ($responseData['math_problem']['id'] ?? 'N/A') . "\n";
        echo "  - Subject: " . ($responseData['math_problem']['subject_area'] ?? 'N/A') . "\n";
        echo "  - Difficulty: " . ($responseData['math_problem']['difficulty_level'] ?? 'N/A') . "\n";
        echo "  - Type: " . ($responseData['math_problem']['problem_type'] ?? 'N/A') . "\n";
        echo "  - Image: " . ($responseData['math_problem']['problem_image'] ?? 'N/A') . "\n\n";
        
        echo "🧮 Solution:\n";
        echo "  - Method: " . ($responseData['math_solution']['solution_method'] ?? 'N/A') . "\n";
        echo "  - Final Answer: " . ($responseData['math_solution']['final_answer'] ?? 'N/A') . "\n";
        echo "  - Explanation: " . ($responseData['math_solution']['explanation'] ?? 'N/A') . "\n\n";
        
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

echo "\n✅ Test completed!\n";
