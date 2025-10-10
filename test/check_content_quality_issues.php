<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Checking Content Quality Issues\n";
echo "=================================\n\n";

// Get the AI vs Humans presentation (ID 151)
$aiResultId = 151;
$aiResult = \App\Models\AIResult::find($aiResultId);

if (!$aiResult) {
    echo "❌ AI Result not found\n";
    exit(1);
}

echo "AI Result ID: $aiResultId\n";
echo "Title: " . ($aiResult->result_data['title'] ?? 'Unknown') . "\n\n";

$slides = $aiResult->result_data['slides'] ?? [];

// Check the last slide for duplicated bullet points
$lastSlide = end($slides);
echo "📋 Last Slide Analysis:\n";
echo "======================\n";
echo "Header: " . ($lastSlide['header'] ?? 'No header') . "\n";
echo "Type: " . ($lastSlide['slide_type'] ?? 'Unknown') . "\n";

if (isset($lastSlide['content']) && is_array($lastSlide['content'])) {
    echo "Content items: " . count($lastSlide['content']) . "\n\n";
    
    echo "📝 All Content Items:\n";
    echo "====================\n";
    foreach ($lastSlide['content'] as $i => $item) {
        echo ($i + 1) . ". " . $item . "\n";
    }
    
    // Check for duplicates
    echo "\n🔍 Duplicate Check:\n";
    echo "==================\n";
    $contentItems = $lastSlide['content'];
    $duplicates = [];
    
    for ($i = 0; $i < count($contentItems); $i++) {
        for ($j = $i + 1; $j < count($contentItems); $j++) {
            if (trim($contentItems[$i]) === trim($contentItems[$j])) {
                $duplicates[] = [
                    'item1_index' => $i + 1,
                    'item2_index' => $j + 1,
                    'content' => $contentItems[$i]
                ];
            }
        }
    }
    
    if (!empty($duplicates)) {
        echo "❌ Found " . count($duplicates) . " duplicate(s):\n";
        foreach ($duplicates as $dup) {
            echo "  - Items " . $dup['item1_index'] . " and " . $dup['item2_index'] . ": " . substr($dup['content'], 0, 60) . "...\n";
        }
    } else {
        echo "✅ No exact duplicates found\n";
    }
    
    // Check for similar content
    echo "\n🔍 Similar Content Check:\n";
    echo "========================\n";
    $similar = [];
    
    for ($i = 0; $i < count($contentItems); $i++) {
        for ($j = $i + 1; $j < count($contentItems); $j++) {
            $similarity = similar_text($contentItems[$i], $contentItems[$j], $percent);
            if ($percent > 80 && $percent < 100) { // Similar but not identical
                $similar[] = [
                    'item1_index' => $i + 1,
                    'item2_index' => $j + 1,
                    'similarity' => round($percent, 1),
                    'content1' => substr($contentItems[$i], 0, 50) . "...",
                    'content2' => substr($contentItems[$j], 0, 50) . "..."
                ];
            }
        }
    }
    
    if (!empty($similar)) {
        echo "⚠️  Found " . count($similar) . " similar content(s):\n";
        foreach ($similar as $sim) {
            echo "  - Items " . $sim['item1_index'] . " and " . $sim['item2_index'] . " (" . $sim['similarity'] . "% similar)\n";
            echo "    Item " . $sim['item1_index'] . ": " . $sim['content1'] . "\n";
            echo "    Item " . $sim['item2_index'] . ": " . $sim['content2'] . "\n\n";
        }
    } else {
        echo "✅ No similar content found\n";
    }
    
} else {
    echo "❌ No content found in last slide\n";
}

// Check a few other slides for quality issues
echo "\n📋 Content Quality Analysis (All Slides):\n";
echo "========================================\n";

$qualityIssues = [];
$totalSlides = 0;
$totalContentItems = 0;

foreach ($slides as $index => $slide) {
    if ($slide['slide_type'] === 'content') {
        $totalSlides++;
        
        if (isset($slide['content']) && is_array($slide['content'])) {
            $contentItems = $slide['content'];
            $totalContentItems += count($contentItems);
            
            // Check for generic content
            foreach ($contentItems as $i => $item) {
                $item = trim($item);
                
                // Check for generic phrases
                if (strpos($item, 'Important aspects and key features') !== false ||
                    strpos($item, 'Current status and future potential') !== false ||
                    strpos($item, 'Key takeaways and important information') !== false) {
                    $qualityIssues[] = [
                        'slide' => $index + 1,
                        'header' => $slide['header'] ?? 'Unknown',
                        'item' => $i + 1,
                        'issue' => 'Generic content',
                        'content' => $item
                    ];
                }
                
                // Check for very short content
                if (strlen($item) < 30) {
                    $qualityIssues[] = [
                        'slide' => $index + 1,
                        'header' => $slide['header'] ?? 'Unknown',
                        'item' => $i + 1,
                        'issue' => 'Too short',
                        'content' => $item
                    ];
                }
            }
        }
    }
}

echo "Total content slides: $totalSlides\n";
echo "Total content items: $totalContentItems\n";
echo "Quality issues found: " . count($qualityIssues) . "\n\n";

if (!empty($qualityIssues)) {
    echo "⚠️  Quality Issues:\n";
    echo "==================\n";
    foreach ($qualityIssues as $issue) {
        echo "Slide " . $issue['slide'] . " (" . $issue['header'] . ") - Item " . $issue['item'] . ": " . $issue['issue'] . "\n";
        echo "  Content: " . $issue['content'] . "\n\n";
    }
} else {
    echo "✅ No major quality issues found\n";
}

echo "\n📋 Recommendations:\n";
echo "==================\n";
echo "1. Improve content generation prompt to avoid generic phrases\n";
echo "2. Add more specific and detailed content for each slide\n";
echo "3. Ensure content is unique and not duplicated\n";
echo "4. Make content more relevant to the specific topic\n";

?>
