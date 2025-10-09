<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

$user = User::where('email', 'test@example.com')->first();
$token = $user->createToken('test-token')->plainTextToken;

echo "🧪 Testing Text-Based Math Problem\n";
echo "==================================\n\n";

$ch = curl_init();
$postData = json_encode([
    'problem_text' => 'What is 2 + 2?',
    'subject_area' => 'arithmetic',
    'difficulty_level' => 'beginner'
]);

curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost:8000/api/math/solve',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
        'Content-Type: application/json',
        'Origin: http://localhost:3000'
    ],
    CURLOPT_TIMEOUT => 30
]);

$startTime = microtime(true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$endTime = microtime(true);
$duration = round(($endTime - $startTime) * 1000, 2);

curl_close($ch);

echo "⏱️  Request duration: {$duration}ms\n";
echo "📊 HTTP Status: $httpCode\n\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "✅ Text math problem solved successfully!\n";
    echo "📋 Problem: " . ($data['math_problem']['problem_text'] ?? 'N/A') . "\n";
    echo "🧮 Answer: " . ($data['math_solution']['final_answer'] ?? 'N/A') . "\n";
    echo "📝 Method: " . ($data['math_solution']['solution_method'] ?? 'N/A') . "\n";
    echo "🔗 AI Result ID: " . ($data['ai_result']['id'] ?? 'N/A') . "\n";
} else {
    echo "❌ Error: $response\n";
}

echo "\n✅ Text-based math problem test completed!\n";
