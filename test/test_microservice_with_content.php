<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing Microservice with Updated Content\n";
echo "==========================================\n\n";

// Get the most recent presentation with updated content
$aiResult = \App\Models\AIResult::where('tool_type', 'presentation')
    ->where('id', 147)
    ->first();

if (!$aiResult) {
    echo "❌ AI Result 147 not found\n";
    exit(1);
}

echo "AI Result ID: " . $aiResult->id . "\n";
echo "Step: " . ($aiResult->result_data['step'] ?? 'unknown') . "\n";

// Check if the first content slide has content
$slides = $aiResult->result_data['slides'] ?? [];
$firstContentSlide = null;

foreach ($slides as $slide) {
    if ($slide['slide_type'] !== 'title') {
        $firstContentSlide = $slide;
        break;
    }
}

if ($firstContentSlide) {
    echo "First content slide: " . $firstContentSlide['header'] . "\n";
    echo "Has content: " . (isset($firstContentSlide['content']) ? 'YES' : 'NO') . "\n";
    if (isset($firstContentSlide['content'])) {
        echo "Content items: " . count($firstContentSlide['content']) . "\n";
        echo "First content: " . substr($firstContentSlide['content'][0] ?? '', 0, 50) . "...\n";
    }
}

// Test the microservice with this data
echo "\n1. Testing microservice with updated content...\n";

$testData = [
    'presentation_data' => [
        'title' => $aiResult->result_data['title'] ?? 'Test Presentation',
        'slides' => $slides
    ],
    'user_id' => 1,
    'ai_result_id' => 147,
    'template' => 'corporate_blue',
    'color_scheme' => 'blue',
    'font_style' => 'modern'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8001/export');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$startTime = time();
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$executionTime = time() - $startTime;
curl_close($ch);

echo "⏱️  Execution time: {$executionTime}s\n";
echo "📊 HTTP Code: $httpCode\n";

if ($httpCode === 200) {
    $result = json_decode($response, true);
    if ($result && $result['success']) {
        echo "✅ Microservice export successful\n";
        echo "📁 File: " . basename($result['data']['file_path']) . "\n";
        echo "📊 File size: " . number_format($result['data']['file_size']) . " bytes\n";
        
        // Check if file exists and has reasonable size
        if (file_exists($result['data']['file_path'])) {
            $actualSize = filesize($result['data']['file_path']);
            echo "📊 Actual file size: " . number_format($actualSize) . " bytes\n";
            
            if ($actualSize > 50000) { // PowerPoint files with full content should be larger
                echo "✅ File size indicates full content was included\n";
            } else {
                echo "⚠️  File size suggests only outline was included\n";
            }
        } else {
            echo "⚠️  Generated file not found at expected location\n";
        }
    } else {
        echo "❌ Microservice export failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "❌ Microservice export failed with HTTP $httpCode\n";
    echo "Response: " . substr($response, 0, 200) . "...\n";
}

echo "\n📋 Analysis:\n";
echo "============\n";
echo "This test will show if the microservice is properly using the\n";
echo "updated content from the database.\n";

?>
