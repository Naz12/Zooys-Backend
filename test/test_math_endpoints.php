<?php

/**
 * Test script for Math API endpoints
 * This script tests the newly implemented math history and stats endpoints
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Math API Endpoints...\n";
echo "==============================\n\n";

// Test 1: Check if routes are registered
echo "1. Checking route registration:\n";
$routes = app('router')->getRoutes();
$mathRoutes = collect($routes)->filter(function ($route) {
    return str_contains($route->uri(), 'math');
});

foreach ($mathRoutes as $route) {
    echo "   - {$route->methods()[0]} {$route->uri()}\n";
}

echo "\n2. Testing endpoint availability:\n";

// Test 2: Check if controllers exist and methods are callable
try {
    $mathController = new \App\Http\Controllers\Api\Client\MathController(
        app(\App\Services\AIMathService::class),
        app(\App\Services\FileUploadService::class),
        app(\App\Services\AIResultService::class)
    );
    
    if (method_exists($mathController, 'history')) {
        echo "   ✓ MathController::history() method exists\n";
    } else {
        echo "   ✗ MathController::history() method missing\n";
    }
    
    if (method_exists($mathController, 'stats')) {
        echo "   ✓ MathController::stats() method exists\n";
    } else {
        echo "   ✗ MathController::stats() method missing\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Error creating MathController: " . $e->getMessage() . "\n";
}

// Test 3: Check if AIMathService has getUserStats method
try {
    $aiMathService = app(\App\Services\AIMathService::class);
    
    if (method_exists($aiMathService, 'getUserStats')) {
        echo "   ✓ AIMathService::getUserStats() method exists\n";
    } else {
        echo "   ✗ AIMathService::getUserStats() method missing\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Error creating AIMathService: " . $e->getMessage() . "\n";
}

echo "\n3. Route testing complete!\n";
echo "The math endpoints should now be available at:\n";
echo "   - GET /api/math/history\n";
echo "   - GET /api/math/stats\n\n";

echo "Note: These endpoints require authentication (auth:sanctum middleware)\n";
echo "To test them, you'll need to make authenticated requests with a valid token.\n";
