<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Debugging Database Save Process\n";
echo "=================================\n\n";

// Get the most recent presentation
$aiResult = \App\Models\AIResult::where('tool_type', 'presentation')
    ->where('id', 147)
    ->first();

if (!$aiResult) {
    echo "❌ AI Result 147 not found\n";
    exit(1);
}

echo "AI Result ID: " . $aiResult->id . "\n";

// Simulate the content generation process
$outline = $aiResult->result_data;
$slides = $outline['slides'] ?? [];

// Separate title slides from content slides
$contentSlides = [];
$slidesToProcess = [];

foreach ($slides as $slide) {
    if ($slide['slide_type'] === 'title') {
        $contentSlides[] = $slide;
    } else {
        $slidesToProcess[] = $slide;
    }
}

echo "Slides to process: " . count($slidesToProcess) . "\n";

if (!empty($slidesToProcess)) {
    $service = app(\App\Services\AIPresentationService::class);
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('generateAllSlideContent');
    $method->setAccessible(true);
    
    $allContent = $method->invoke($service, $slidesToProcess, $outline['title']);
    
    echo "Generated content for " . count($allContent) . " slides\n";
    
    foreach ($slidesToProcess as $index => $slide) {
        $content = $allContent[$index] ?? null;
        $contentSlides[] = array_merge($slide, ['content' => $content]);
    }
}

echo "\n1. Before database update:\n";
echo "Total content slides: " . count($contentSlides) . "\n";
if (isset($contentSlides[1])) {
    $firstContentSlide = $contentSlides[1];
    echo "First content slide has content: " . (isset($firstContentSlide['content']) ? 'YES' : 'NO') . "\n";
    if (isset($firstContentSlide['content'])) {
        echo "Content items: " . count($firstContentSlide['content']) . "\n";
    }
}

// Update the result with full content
$resultData = $aiResult->result_data;
$resultData['slides'] = $contentSlides;
$resultData['step'] = 'content_generated';

echo "\n2. Updating database...\n";

try {
    $aiResult->update([
        'result_data' => $resultData,
        'metadata' => array_merge($aiResult->metadata ?? [], [
            'content_generated_at' => now()->toISOString()
        ])
    ]);
    
    echo "✅ Database update completed\n";
    
} catch (Exception $e) {
    echo "❌ Database update failed: " . $e->getMessage() . "\n";
}

// Check the database after update
echo "\n3. After database update:\n";
$aiResult->refresh();
$updatedSlides = $aiResult->result_data['slides'] ?? [];

echo "Updated slides count: " . count($updatedSlides) . "\n";

if (isset($updatedSlides[1])) {
    $firstContentSlide = $updatedSlides[1];
    echo "First content slide has content: " . (isset($firstContentSlide['content']) ? 'YES' : 'NO') . "\n";
    if (isset($firstContentSlide['content'])) {
        echo "Content items: " . count($firstContentSlide['content']) . "\n";
        echo "First content: " . substr($firstContentSlide['content'][0] ?? '', 0, 50) . "...\n";
    }
}

echo "\n📋 Analysis:\n";
echo "============\n";
echo "This will show if the content is being lost during database save.\n";

?>
