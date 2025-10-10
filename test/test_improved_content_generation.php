<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Testing Improved Content Generation\n";
echo "=====================================\n\n";

$service = app(\App\Services\AIPresentationService::class);

// Test with a different topic to see if the improvements work
$testTopic = [
    'title' => 'Artificial Intelligence in Healthcare',
    'topic' => 'Explore how AI is transforming healthcare delivery and patient outcomes',
    'slides_count' => 8,
    'target_audience' => 'Healthcare professionals',
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
    $aiResult = \App\Models\AIResult::find($aiResultId);
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
                if (strlen($item) < 30) {
                    $incompleteItems++;
                    echo "    ❌ INCOMPLETE CONTENT (" . strlen($item) . " chars)\n";
                }
                
                // Check for generic phrases
                $genericPhrasesList = [
                    'Industry best practices',
                    'Success metrics',
                    'Key performance indicators',
                    'Important aspects',
                    'Current status',
                    'Specific examples',
                    'Detailed analysis',
                    'Practical applications',
                    'Measurable outcomes',
                    'Real-world applications',
                    'Comprehensive analysis',
                    'Examination of current trends'
                ];
                
                foreach ($genericPhrasesList as $phrase) {
                    if (stripos($item, $phrase) !== false) {
                        $genericPhrases++;
                        echo "    ❌ GENERIC PHRASE: $phrase\n";
                        break;
                    }
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
    
    echo "\n🔍 Analysis Results:\n";
    echo "===================\n";
    if ($qualityScore >= 90) {
        echo "✅ EXCELLENT: Content generation is working perfectly!\n";
    } elseif ($qualityScore >= 70) {
        echo "⚠️  GOOD: Content generation is mostly working with minor issues\n";
    } elseif ($qualityScore >= 50) {
        echo "⚠️  FAIR: Content generation has some issues that need attention\n";
    } else {
        echo "❌ POOR: Content generation has significant issues that need fixing\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

?>