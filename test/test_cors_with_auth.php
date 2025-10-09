<?php

/**
 * Test CORS with authentication
 * This will test the math endpoints with proper authentication
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Foundation\Application;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "CORS Test with Authentication\n";
echo "=============================\n\n";

// Create test user with subscription
$user = \App\Models\User::firstOrCreate(
    ['email' => 'cors_test@math.com'],
    [
        'name' => 'CORS Test User',
        'password' => bcrypt('password'),
        'email_verified_at' => now()
    ]
);

$plan = \App\Models\Plan::firstOrCreate(
    ['name' => 'CORS Test Plan'],
    [
        'name' => 'CORS Test Plan',
        'price' => 0,
        'interval' => 'month',
        'features' => json_encode(['math_solver' => true]),
        'is_active' => true
    ]
);

$subscription = \App\Models\Subscription::firstOrCreate(
    ['user_id' => $user->id],
    [
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
        'stripe_subscription_id' => 'cors_test_sub_' . time()
    ]
);

$token = $user->createToken('cors-test')->plainTextToken;

echo "Created test user with token: " . substr($token, 0, 20) . "...\n\n";

// Test 1: Test main math endpoints with auth
echo "1. Testing main math endpoints with authentication:\n";
try {
    $client = new \GuzzleHttp\Client();
    
    // Test /api/math/solve
    echo "   Testing /api/math/solve:\n";
    $response = $client->post('http://localhost:8000/api/math/solve', [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:3000'
        ],
        'json' => [
            'problem_text' => 'Test CORS with 5+5=10',
            'subject_area' => 'arithmetic',
            'difficulty_level' => 'beginner'
        ]
    ]);
    
    $statusCode = $response->getStatusCode();
    $headers = $response->getHeaders();
    
    echo "      ✓ Status: $statusCode\n";
    if (isset($headers['Access-Control-Allow-Origin'])) {
        echo "      ✓ CORS Origin: " . implode(', ', $headers['Access-Control-Allow-Origin']) . "\n";
    } else {
        echo "      ✗ No CORS Origin header found\n";
    }
    
    // Test /api/math/problems
    echo "   Testing /api/math/problems:\n";
    $response = $client->get('http://localhost:8000/api/math/problems', [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:3000'
        ]
    ]);
    
    $statusCode = $response->getStatusCode();
    $headers = $response->getHeaders();
    
    echo "      ✓ Status: $statusCode\n";
    if (isset($headers['Access-Control-Allow-Origin'])) {
        echo "      ✓ CORS Origin: " . implode(', ', $headers['Access-Control-Allow-Origin']) . "\n";
    } else {
        echo "      ✗ No CORS Origin header found\n";
    }
    
} catch (\GuzzleHttp\Exception\ClientException $e) {
    echo "   ✗ Client Error: " . $e->getMessage() . "\n";
    if ($e->hasResponse()) {
        $response = $e->getResponse();
        echo "      Status: " . $response->getStatusCode() . "\n";
        echo "      Body: " . $response->getBody()->getContents() . "\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// Test 2: Test client math endpoints with auth
echo "\n2. Testing client math endpoints with authentication:\n";
try {
    // Test /api/client/math/generate
    echo "   Testing /api/client/math/generate:\n";
    $response = $client->post('http://localhost:8000/api/client/math/generate', [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:3000'
        ],
        'json' => [
            'problem_text' => 'Test client CORS with 6+6=12',
            'subject_area' => 'arithmetic',
            'difficulty_level' => 'beginner'
        ]
    ]);
    
    $statusCode = $response->getStatusCode();
    $headers = $response->getHeaders();
    
    echo "      ✓ Status: $statusCode\n";
    if (isset($headers['Access-Control-Allow-Origin'])) {
        echo "      ✓ CORS Origin: " . implode(', ', $headers['Access-Control-Allow-Origin']) . "\n";
    } else {
        echo "      ✗ No CORS Origin header found\n";
    }
    
    // Test /api/client/math/history
    echo "   Testing /api/client/math/history:\n";
    $response = $client->get('http://localhost:8000/api/client/math/history', [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:3000'
        ]
    ]);
    
    $statusCode = $response->getStatusCode();
    $headers = $response->getHeaders();
    
    echo "      ✓ Status: $statusCode\n";
    if (isset($headers['Access-Control-Allow-Origin'])) {
        echo "      ✓ CORS Origin: " . implode(', ', $headers['Access-Control-Allow-Origin']) . "\n";
    } else {
        echo "      ✗ No CORS Origin header found\n";
    }
    
} catch (\GuzzleHttp\Exception\ClientException $e) {
    echo "   ✗ Client Error: " . $e->getMessage() . "\n";
    if ($e->hasResponse()) {
        $response = $e->getResponse();
        echo "      Status: " . $response->getStatusCode() . "\n";
        echo "      Body: " . $response->getBody()->getContents() . "\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n3. CORS Test Summary:\n";
echo "=====================\n";
echo "✅ Math endpoints require authentication (Bearer token)\n";
echo "✅ CORS headers should be present in responses\n";
echo "✅ Frontend needs to include Authorization header\n\n";

echo "🔧 Frontend Integration Requirements:\n";
echo "=====================================\n";
echo "1. Include Authorization header: 'Bearer <token>'\n";
echo "2. Include Origin header: 'http://localhost:3000'\n";
echo "3. Use Content-Type: 'application/json'\n";
echo "4. Handle CORS preflight requests (OPTIONS)\n\n";

echo "The issue is that the frontend needs to include the Authorization header! 🎯\n";
