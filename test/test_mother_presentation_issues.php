<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Testing Mother Presentation - Identifying Issues\n";
echo "================================================\n\n";

$service = app(\App\Services\AIPresentationService::class);

// Test with a "mother" topic to identify the issues
$testTopic = [
    'title' => 'The Role of Mothers in Society',
    'topic' => 'Explore the important role mothers play in families and society',
    'slides_count' => 8,
    'target_audience' => 'General audience',
    'presentation_style' => 'Educational'
];

echo "🎯 Testing Topic: " . $testTopic['title'] . "\n";
echo "==========================================\n\n";

try {
    // Generate outline
    echo "1. Generating outline...\n";
    $outlineResult = $service->generateOutline($testTopic, 5);
    
    if (!$outlineResult['success']) {
        echo "❌ Outline generation failed: " . ($outlineResult['error'] ?? 'Unknown error') . "\n";
        exit(1);
    }
    
    $aiResultId = $outlineResult['data']['ai_result_id'];
    echo "✅ Outline generated (ID: $aiResultId)\n";
    
    // Check outline first
    $aiResult = \App\Models\AIResult::find($aiResultId);
    $slides = $aiResult->result_data['slides'] ?? [];
    
    echo "\n📋 Outline Analysis:\n";
    echo "===================\n";
    foreach ($slides as $index => $slide) {
        echo "Slide " . ($index + 1) . ": " . $slide['header'] . "\n";
        echo "  Type: " . $slide['slide_type'] . "\n";
        if (isset($slide['subheaders']) && is_array($slide['subheaders'])) {
            echo "  Subheaders: " . count($slide['subheaders']) . "\n";
            foreach ($slide['subheaders'] as $i => $subheader) {
                echo "    " . ($i + 1) . ". " . $subheader . "\n";
            }
        }
        echo "\n";
    }
    
    // Generate content
    echo "2. Generating content...\n";
    $contentResult = $service->generateContent($aiResultId, 5);
    
    if (!$contentResult['success']) {
        echo "❌ Content generation failed: " . ($contentResult['error'] ?? 'Unknown error') . "\n";
        exit(1);
    }
    
    echo "✅ Content generated\n";
    
    // Analyze content quality and issues
    echo "\n3. Analyzing content for issues...\n";
    $aiResult->refresh();
    $slides = $aiResult->result_data['slides'] ?? [];
    
    $contentSlides = array_filter($slides, function($slide) {
        return $slide['slide_type'] === 'content';
    });
    
    $totalContentItems = 0;
    $duplicateItems = 0;
    $incompleteItems = 0;
    $genericPhrases = 0;
    $allContent = [];
    
    echo "\n📊 Detailed Content Analysis:\n";
    echo "============================\n\n";
    
    foreach ($contentSlides as $index => $slide) {
        echo "Slide " . ($index + 1) . ": " . $slide['header'] . "\n";
        echo "Type: " . $slide['slide_type'] . "\n";
        
        if (isset($slide['content']) && is_array($slide['content'])) {
            echo "Content items: " . count($slide['content']) . "\n\n";
            $totalContentItems += count($slide['content']);
            
            foreach ($slide['content'] as $i => $item) {
                echo "  " . ($i + 1) . ". " . $item . "\n";
                
                // Check for duplicates
                if (in_array($item, $allContent)) {
                    $duplicateItems++;
                    echo "    ❌ DUPLICATE CONTENT\n";
                }
                $allContent[] = $item;
                
                // Check for incomplete content
                $item = trim($item);
                if (strlen($item) < 20) {
                    $incompleteItems++;
                    echo "    ❌ INCOMPLETE CONTENT (" . strlen($item) . " chars)\n";
                }
                
                // Check for generic phrases
                if (strpos($item, 'Important aspects') !== false ||
                    strpos($item, 'Current status') !== false ||
                    strpos($item, 'Specific examples') !== false ||
                    strpos($item, 'Detailed analysis') !== false ||
                    strpos($item, 'Practical applications') !== false ||
                    strpos($item, 'Measurable outcomes') !== false ||
                    strpos($item, 'Industry best practices') !== false ||
                    strpos($item, 'Success metrics') !== false) {
                    $genericPhrases++;
                    echo "    ❌ GENERIC PHRASE\n";
                }
            }
        } else {
            echo "❌ No content found\n";
        }
        
        echo "\n" . str_repeat("-", 40) . "\n\n";
    }
    
    // Check for cross-slide duplicates
    echo "🔍 Cross-Slide Duplicate Analysis:\n";
    echo "=================================\n";
    $contentCounts = array_count_values($allContent);
    $crossSlideDuplicates = 0;
    
    foreach ($contentCounts as $content => $count) {
        if ($count > 1) {
            $crossSlideDuplicates += $count - 1;
            echo "Duplicate: '$content' (appears $count times)\n";
        }
    }
    
    if ($crossSlideDuplicates === 0) {
        echo "✅ No cross-slide duplicates found\n";
    } else {
        echo "❌ Found $crossSlideDuplicates cross-slide duplicates\n";
    }
    
    // Summary
    echo "\n📋 Issue Summary:\n";
    echo "================\n";
    echo "Total content items: $totalContentItems\n";
    echo "Duplicate items: $duplicateItems\n";
    echo "Cross-slide duplicates: $crossSlideDuplicates\n";
    echo "Incomplete items: $incompleteItems\n";
    echo "Generic phrases: $genericPhrases\n";
    echo "Total issues: " . ($duplicateItems + $crossSlideDuplicates + $incompleteItems + $genericPhrases) . "\n";
    
    $qualityScore = $totalContentItems > 0 ? round((($totalContentItems - ($duplicateItems + $crossSlideDuplicates + $incompleteItems + $genericPhrases)) / $totalContentItems) * 100, 1) : 0;
    echo "Quality score: $qualityScore%\n";
    
    // Generate PowerPoint to test the final output
    echo "\n4. Generating PowerPoint...\n";
    $templateData = [
        'template' => 'corporate_blue',
        'color_scheme' => 'blue',
        'font_style' => 'modern'
    ];
    
    $powerPointResult = $service->generatePowerPointWithMicroservice($aiResultId, $templateData, 5);
    
    if ($powerPointResult['success']) {
        echo "✅ PowerPoint generated successfully\n";
        echo "📁 File: " . basename($powerPointResult['data']['powerpoint_file']) . "\n";
        
        if (file_exists($powerPointResult['data']['powerpoint_file'])) {
            $fileSize = filesize($powerPointResult['data']['powerpoint_file']);
            echo "📊 File size: " . number_format($fileSize) . " bytes\n";
        }
        
        echo "\n🪟 Windows Path:\n";
        echo "===============\n";
        $windowsPath = str_replace('/', '\\', $powerPointResult['data']['powerpoint_file']);
        echo "$windowsPath\n";
        
    } else {
        echo "❌ PowerPoint generation failed: " . ($powerPointResult['error'] ?? 'Unknown error') . "\n";
    }
    
    echo "\n🔍 Root Cause Analysis:\n";
    echo "======================\n";
    if ($duplicateItems > 0 || $crossSlideDuplicates > 0) {
        echo "❌ Duplicate content issue: Content generation is creating repeated bullet points\n";
    }
    if ($incompleteItems > 0) {
        echo "❌ Incomplete content issue: Some bullet points are too short or incomplete\n";
    }
    if ($genericPhrases > 0) {
        echo "❌ Generic content issue: Fallback content is still being used\n";
    }
    
    echo "\n💡 Recommendations:\n";
    echo "==================\n";
    echo "1. Check the content generation prompt for duplication issues\n";
    echo "2. Verify the fallback content method is not being overused\n";
    echo "3. Ensure the AI is generating unique content for each slide\n";
    echo "4. Add validation to prevent duplicate content\n";
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

?>

