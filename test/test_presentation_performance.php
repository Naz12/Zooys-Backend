<?php

/**
 * Test script for AI Presentation Generator Performance
 * Tests outline generation speed and PowerPoint generation
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\AIPresentationService;
use App\Models\User;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🚀 Testing AI Presentation Generator Performance\n";
echo "================================================\n\n";

// Test data
$testData = [
    'input_type' => 'text',
    'content' => 'The Future of Artificial Intelligence in Business',
    'language' => 'English',
    'tone' => 'Professional',
    'length' => 'Medium',
    'model' => 'Basic Model'
];

$userId = 21; // Test user ID

try {
    // Get service from Laravel container
    $service = app(AIPresentationService::class);
    
    echo "📝 Testing Outline Generation Performance...\n";
    $startTime = microtime(true);
    
    $result = $service->generateOutline($testData, $userId);
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    if ($result['success']) {
        echo "✅ Outline generation successful!\n";
        echo "⏱️  Duration: {$duration} seconds\n";
        echo "📊 AI Result ID: {$result['data']['ai_result_id']}\n";
        echo "📋 Title: " . ($result['data']['title'] ?? 'N/A') . "\n";
        echo "📄 Slides: " . ($result['data']['slide_count'] ?? 'N/A') . "\n";
        echo "📝 Response structure: " . json_encode(array_keys($result['data'])) . "\n\n";
        
        $aiResultId = $result['data']['ai_result_id'];
        
        // Test PowerPoint generation
        echo "🎨 Testing PowerPoint Generation...\n";
        $templateData = [
            'template' => 'minimalist_gray',
            'color_scheme' => 'gray',
            'font_style' => 'modern'
        ];
        
        $startTime = microtime(true);
        $powerPointResult = $service->generatePowerPoint($aiResultId, $templateData, $userId);
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        if ($powerPointResult['success']) {
            echo "✅ PowerPoint generation successful!\n";
            echo "⏱️  Duration: {$duration} seconds\n";
            echo "📁 File: {$powerPointResult['data']['powerpoint_file']}\n";
            echo "🔗 Download URL: {$powerPointResult['data']['download_url']}\n";
        } else {
            echo "❌ PowerPoint generation failed: {$powerPointResult['error']}\n";
        }
        
    } else {
        echo "❌ Outline generation failed: {$result['error']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Test failed with exception: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n🏁 Performance test completed!\n";
