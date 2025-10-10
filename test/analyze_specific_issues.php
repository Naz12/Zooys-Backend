<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Analyzing Specific Content Issues\n";
echo "===================================\n\n";

// Analyze the worst performing presentation (Digital Marketing - 42.5% quality)
$aiResultId = 155;
$aiResult = \App\Models\AIResult::find($aiResultId);

if (!$aiResult) {
    echo "❌ AI Result not found\n";
    exit(1);
}

echo "Analyzing: " . ($aiResult->result_data['title'] ?? 'Unknown') . " (ID: $aiResultId)\n";
echo "Quality Score: 42.5%\n\n";

$slides = $aiResult->result_data['slides'] ?? [];
$contentSlides = array_filter($slides, function($slide) {
    return $slide['slide_type'] === 'content';
});

echo "📊 Detailed Content Analysis:\n";
echo "============================\n\n";

$issueTypes = [
    'generic_phrases' => 0,
    'short_content' => 0,
    'repetitive_content' => 0,
    'vague_content' => 0
];

$allContent = [];

foreach ($contentSlides as $index => $slide) {
    echo "Slide " . ($index + 1) . ": " . $slide['header'] . "\n";
    echo "Type: " . $slide['slide_type'] . "\n";
    
    if (isset($slide['content']) && is_array($slide['content'])) {
        echo "Content items: " . count($slide['content']) . "\n\n";
        
        foreach ($slide['content'] as $i => $item) {
            $allContent[] = $item;
            echo "  " . ($i + 1) . ". " . $item . "\n";
            
            // Analyze specific issues
            $item = trim($item);
            
            // Check for generic phrases
            if (strpos($item, 'Specific examples and real-world applications') !== false ||
                strpos($item, 'Measurable outcomes and performance improvements') !== false ||
                strpos($item, 'Detailed analysis and comprehensive coverage') !== false ||
                strpos($item, 'Practical applications and implementation considerations') !== false) {
                $issueTypes['generic_phrases']++;
                echo "    ❌ GENERIC PHRASE\n";
            }
            
            // Check for short content
            if (strlen($item) < 30) {
                $issueTypes['short_content']++;
                echo "    ❌ TOO SHORT (" . strlen($item) . " chars)\n";
            }
            
            // Check for vague content
            if (strpos($item, 'various') !== false && strlen($item) < 50 ||
                strpos($item, 'different') !== false && strlen($item) < 50 ||
                strpos($item, 'several') !== false && strlen($item) < 50) {
                $issueTypes['vague_content']++;
                echo "    ❌ VAGUE CONTENT\n";
            }
        }
    } else {
        echo "❌ No content found\n";
    }
    
    echo "\n" . str_repeat("-", 40) . "\n\n";
}

// Check for repetitive content
$contentCounts = array_count_values($allContent);
foreach ($contentCounts as $content => $count) {
    if ($count > 1) {
        $issueTypes['repetitive_content'] += $count - 1;
    }
}

echo "📋 Issue Summary:\n";
echo "================\n";
echo "Generic phrases: " . $issueTypes['generic_phrases'] . "\n";
echo "Short content: " . $issueTypes['short_content'] . "\n";
echo "Repetitive content: " . $issueTypes['repetitive_content'] . "\n";
echo "Vague content: " . $issueTypes['vague_content'] . "\n";
echo "Total issues: " . array_sum($issueTypes) . "\n";

echo "\n🔍 Specific Generic Phrases Found:\n";
echo "==================================\n";
$genericPhrases = [
    'Specific examples and real-world applications demonstrating these advantages',
    'Measurable outcomes and performance improvements achieved',
    'Detailed analysis and comprehensive coverage of the topic',
    'Practical applications and implementation considerations'
];

foreach ($genericPhrases as $phrase) {
    $count = 0;
    foreach ($allContent as $content) {
        if (strpos($content, $phrase) !== false) {
            $count++;
        }
    }
    if ($count > 0) {
        echo "- '$phrase': Found $count times\n";
    }
}

echo "\n💡 Root Cause Analysis:\n";
echo "======================\n";
echo "1. The fallback content method is still being used frequently\n";
echo "2. Generic phrases are being added as fallback content\n";
echo "3. The AI is not generating specific enough content\n";
echo "4. The prompt needs to be more restrictive and specific\n";

echo "\n🛠️  Required Improvements:\n";
echo "=========================\n";
echo "1. Completely eliminate generic fallback phrases\n";
echo "2. Make the prompt more specific and restrictive\n";
echo "3. Add stronger validation for content quality\n";
echo "4. Improve the AI model selection (use GPT-4 instead of GPT-3.5)\n";
echo "5. Add topic-specific content generation guidelines\n";

?>
