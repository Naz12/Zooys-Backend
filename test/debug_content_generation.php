<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Debugging Content Generation Process\n";
echo "======================================\n\n";

// Get the most recent presentation
$aiResult = \App\Models\AIResult::where('tool_type', 'presentation')
    ->where('id', 147)
    ->first();

if (!$aiResult) {
    echo "❌ AI Result 147 not found\n";
    exit(1);
}

echo "AI Result ID: " . $aiResult->id . "\n";
echo "Current step: " . ($aiResult->result_data['step'] ?? 'unknown') . "\n";
echo "Slides count: " . count($aiResult->result_data['slides'] ?? []) . "\n\n";

// Check if content generation was actually called
$service = app(\App\Services\AIPresentationService::class);

echo "1. Testing content generation for this specific presentation...\n";

// Clear any cache first
\Illuminate\Support\Facades\Cache::forget("content_generation_147_5");
\Illuminate\Support\Facades\Cache::forget("content_result_147_5");

try {
    // Call the generateContent method
    $result = $service->generateContent(147, 5);
    
    echo "✅ Content generation completed\n";
    echo "Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
    
    if ($result['success']) {
        echo "Slides returned: " . count($result['data']['slides'] ?? []) . "\n";
        
        if (isset($result['data']['slides'][0])) {
            $firstSlide = $result['data']['slides'][0];
            echo "First slide has content: " . (isset($firstSlide['content']) ? 'YES' : 'NO') . "\n";
            if (isset($firstSlide['content'])) {
                echo "Content items: " . count($firstSlide['content']) . "\n";
                echo "First content: " . substr($firstSlide['content'][0] ?? '', 0, 50) . "...\n";
            }
        }
    } else {
        echo "Error: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Content generation failed: " . $e->getMessage() . "\n";
}

// Check the database again
echo "\n2. Checking database after content generation...\n";
$aiResult->refresh();
echo "Updated step: " . ($aiResult->result_data['step'] ?? 'unknown') . "\n";

if (isset($aiResult->result_data['slides'][0])) {
    $firstSlide = $aiResult->result_data['slides'][0];
    echo "First slide has content: " . (isset($firstSlide['content']) ? 'YES' : 'NO') . "\n";
    if (isset($firstSlide['content'])) {
        echo "Content items: " . count($firstSlide['content']) . "\n";
        echo "First content: " . substr($firstSlide['content'][0] ?? '', 0, 50) . "...\n";
    }
}

echo "\n📋 Analysis:\n";
echo "============\n";
echo "This will help identify where the content generation is failing.\n";

?>
