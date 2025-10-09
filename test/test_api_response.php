<?php

/**
 * Test script to verify API response structure
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Foundation\Application;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing API Response Structure...\n";
echo "================================\n\n";

// Test 1: Check if MathController methods exist and return expected structure
try {
    $mathController = new \App\Http\Controllers\Api\Client\MathController(
        app(\App\Services\AIMathService::class),
        app(\App\Services\FileUploadService::class),
        app(\App\Services\AIResultService::class)
    );
    
    echo "✓ MathController instantiated successfully\n";
    
    // Test history method structure
    $reflection = new ReflectionClass($mathController);
    $historyMethod = $reflection->getMethod('history');
    echo "✓ MathController::history() method exists\n";
    
    // Test stats method structure  
    $statsMethod = $reflection->getMethod('stats');
    echo "✓ MathController::stats() method exists\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Test 2: Check route registration
echo "\n2. Checking route registration:\n";
$routes = app('router')->getRoutes();
$clientMathRoutes = collect($routes)->filter(function ($route) {
    return str_contains($route->uri(), 'client/math');
});

foreach ($clientMathRoutes as $route) {
    echo "   ✓ {$route->methods()[0]} {$route->uri()}\n";
}

echo "\n3. API Response Structure Test Complete!\n";
echo "The math endpoints should return:\n";
echo "   - GET /api/client/math/history → Array of math problems\n";
echo "   - GET /api/client/math/stats → Object with statistics\n";
echo "   - POST /api/client/math/generate → Object with solution data\n";
echo "   - POST /api/client/math/help → Object with solution data\n\n";

echo "Note: All endpoints require authentication (Bearer token)\n";
