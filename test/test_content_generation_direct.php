<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing Content Generation Directly\n";
echo "=====================================\n\n";

// Test the generateAllSlideContent method directly
$service = app(\App\Services\AIPresentationService::class);

// Create test slides data
$testSlides = [
    [
        'slide_number' => 1,
        'header' => 'Introduction to Testing',
        'subheaders' => ['Overview', 'Objectives', 'Agenda'],
        'slide_type' => 'content'
    ],
    [
        'slide_number' => 2,
        'header' => 'Main Topic',
        'subheaders' => ['Key Points', 'Benefits', 'Implementation'],
        'slide_type' => 'content'
    ]
];

$presentationTitle = 'Test Presentation';

echo "1. Testing generateAllSlideContent method...\n";
echo "   Slides to process: " . count($testSlides) . "\n";
echo "   Presentation title: $presentationTitle\n\n";

// Use reflection to access the private method
$reflection = new ReflectionClass($service);
$method = $reflection->getMethod('generateAllSlideContent');
$method->setAccessible(true);

try {
    $result = $method->invoke($service, $testSlides, $presentationTitle);
    
    echo "✅ Method executed successfully\n";
    echo "📊 Result type: " . gettype($result) . "\n";
    echo "📊 Result count: " . (is_array($result) ? count($result) : 'N/A') . "\n";
    
    if (is_array($result)) {
        foreach ($result as $index => $content) {
            echo "   Slide $index: " . (is_array($content) ? count($content) . ' items' : 'not array') . "\n";
            if (is_array($content) && !empty($content)) {
                echo "   First item: " . substr($content[0], 0, 50) . "...\n";
            }
        }
    } else {
        echo "   Result: " . substr($result, 0, 100) . "...\n";
    }
    
} catch (Exception $e) {
    echo "❌ Method failed: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n2. Testing OpenAI service availability...\n";
try {
    $openAIService = app(\App\Services\OpenAIService::class);
    echo "✅ OpenAI service is available\n";
    
    // Test a simple prompt
    $testPrompt = "Generate 2 bullet points about testing:";
    $response = $openAIService->generateResponse($testPrompt, 'gpt-3.5-turbo');
    
    if (!empty($response)) {
        echo "✅ OpenAI response received: " . substr($response, 0, 50) . "...\n";
    } else {
        echo "❌ OpenAI returned empty response\n";
    }
    
} catch (Exception $e) {
    echo "❌ OpenAI service failed: " . $e->getMessage() . "\n";
}

echo "\n📋 Analysis:\n";
echo "============\n";
echo "If generateAllSlideContent returns empty or fails, that's why\n";
echo "the PowerPoint files only contain outline data.\n";

?>
