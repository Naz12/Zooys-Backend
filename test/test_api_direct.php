<?php

/**
 * Test script to directly test the API endpoints
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Foundation\Application;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing API Endpoints Directly...\n";
echo "==================================\n\n";

// Test 1: Check if we can create a request
try {
    $request = new \Illuminate\Http\Request();
    $request->setMethod('GET');
    $request->headers->set('Accept', 'application/json');
    
    echo "✓ Request object created\n";
    
    // Test 2: Check if MathController can be instantiated
    $mathController = new \App\Http\Controllers\Api\Client\MathController(
        app(\App\Services\AIMathService::class),
        app(\App\Services\FileUploadService::class),
        app(\App\Services\AIResultService::class)
    );
    
    echo "✓ MathController instantiated\n";
    
    // Test 3: Check what getUserProblems returns
    $aiMathService = app(\App\Services\AIMathService::class);
    $problems = $aiMathService->getUserProblems(1, []);
    
    echo "✓ AIMathService::getUserProblems() called\n";
    echo "   - Type: " . get_class($problems) . "\n";
    echo "   - Items type: " . gettype($problems->items()) . "\n";
    echo "   - Items count: " . count($problems->items()) . "\n";
    
    if (count($problems->items()) > 0) {
        echo "   - First item: " . json_encode($problems->items()[0]) . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\nTest completed!\n";
