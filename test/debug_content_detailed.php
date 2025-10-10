<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Detailed Content Generation Debug\n";
echo "===================================\n\n";

// Get the most recent presentation
$aiResult = \App\Models\AIResult::where('tool_type', 'presentation')
    ->where('id', 147)
    ->first();

if (!$aiResult) {
    echo "❌ AI Result 147 not found\n";
    exit(1);
}

echo "AI Result ID: " . $aiResult->id . "\n";
$outline = $aiResult->result_data;
$slides = $outline['slides'] ?? [];

echo "Total slides: " . count($slides) . "\n";

// Separate title slides from content slides
$contentSlides = [];
$slidesToProcess = [];

foreach ($slides as $slide) {
    if ($slide['slide_type'] === 'title') {
        $contentSlides[] = $slide;
        echo "Title slide: " . $slide['header'] . "\n";
    } else {
        $slidesToProcess[] = $slide;
        echo "Content slide: " . $slide['header'] . "\n";
    }
}

echo "\nSlides to process: " . count($slidesToProcess) . "\n";
echo "Title slides: " . count($contentSlides) . "\n";

if (!empty($slidesToProcess)) {
    echo "\n1. Testing generateAllSlideContent...\n";
    
    $service = app(\App\Services\AIPresentationService::class);
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('generateAllSlideContent');
    $method->setAccessible(true);
    
    try {
        $allContent = $method->invoke($service, $slidesToProcess, $outline['title']);
        
        echo "✅ generateAllSlideContent completed\n";
        echo "Generated content count: " . count($allContent) . "\n";
        
        foreach ($allContent as $index => $content) {
            echo "   Slide $index: " . (is_array($content) ? count($content) . ' items' : 'not array') . "\n";
            if (is_array($content) && !empty($content)) {
                echo "   First item: " . substr($content[0], 0, 50) . "...\n";
            }
        }
        
        echo "\n2. Testing content merging...\n";
        
        foreach ($slidesToProcess as $index => $slide) {
            $content = $allContent[$index] ?? null;
            echo "Slide $index (" . $slide['header'] . "):\n";
            echo "   Has generated content: " . ($content ? 'YES' : 'NO') . "\n";
            if ($content) {
                echo "   Content items: " . count($content) . "\n";
            }
            
            $mergedSlide = array_merge($slide, ['content' => $content]);
            echo "   Merged slide has content: " . (isset($mergedSlide['content']) ? 'YES' : 'NO') . "\n";
            
            $contentSlides[] = $mergedSlide;
        }
        
        echo "\n3. Final content slides count: " . count($contentSlides) . "\n";
        
        // Check first content slide
        foreach ($contentSlides as $index => $slide) {
            if ($slide['slide_type'] !== 'title') {
                echo "First content slide ($index):\n";
                echo "   Header: " . $slide['header'] . "\n";
                echo "   Has content: " . (isset($slide['content']) ? 'YES' : 'NO') . "\n";
                if (isset($slide['content'])) {
                    echo "   Content items: " . count($slide['content']) . "\n";
                    echo "   First content: " . substr($slide['content'][0] ?? '', 0, 50) . "...\n";
                }
                break;
            }
        }
        
    } catch (Exception $e) {
        echo "❌ generateAllSlideContent failed: " . $e->getMessage() . "\n";
    }
}

echo "\n📋 Analysis:\n";
echo "============\n";
echo "This will show exactly where the content is being lost.\n";

?>
