<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🎯 Final Comprehensive Test - Improved PowerPoint Generation\n";
echo "========================================================\n\n";

$service = app(\App\Services\AIPresentationService::class);

// Test with 3 different topics to ensure consistency
$testTopics = [
    [
        'title' => 'Cybersecurity Best Practices for Small Businesses',
        'topic' => 'Essential cybersecurity measures and strategies for small business protection',
        'slides_count' => 6,
        'target_audience' => 'Small business owners',
        'presentation_style' => 'Practical'
    ],
    [
        'title' => 'The Impact of Climate Change on Agriculture',
        'topic' => 'How climate change affects farming practices and food production worldwide',
        'slides_count' => 7,
        'target_audience' => 'Agricultural professionals',
        'presentation_style' => 'Scientific'
    ],
    [
        'title' => 'Remote Team Management Strategies',
        'topic' => 'Effective techniques for managing and leading remote teams successfully',
        'slides_count' => 8,
        'target_audience' => 'Managers and team leaders',
        'presentation_style' => 'Professional'
    ]
];

$results = [];

foreach ($testTopics as $index => $topic) {
    echo "🎯 Test " . ($index + 1) . ": " . $topic['title'] . "\n";
    echo "==========================================\n";
    
    $startTime = microtime(true);
    
    try {
        // Generate outline
        echo "1. Generating outline...\n";
        $outlineResult = $service->generateOutline($topic, 5);
        
        if (!$outlineResult['success']) {
            echo "❌ Outline generation failed\n";
            continue;
        }
        
        $aiResultId = $outlineResult['data']['ai_result_id'];
        echo "✅ Outline generated (ID: $aiResultId)\n";
        
        // Generate content
        echo "2. Generating content...\n";
        $contentResult = $service->generateContent($aiResultId, 5);
        
        if (!$contentResult['success']) {
            echo "❌ Content generation failed\n";
            continue;
        }
        
        echo "✅ Content generated\n";
        
        // Analyze content quality
        $aiResult = \App\Models\AIResult::find($aiResultId);
        $slides = $aiResult->result_data['slides'] ?? [];
        
        $contentSlides = array_filter($slides, function($slide) {
            return $slide['slide_type'] === 'content';
        });
        
        $totalContentItems = 0;
        $qualityIssues = 0;
        $genericPhrases = 0;
        $shortContent = 0;
        
        foreach ($contentSlides as $slide) {
            if (isset($slide['content']) && is_array($slide['content'])) {
                $totalContentItems += count($slide['content']);
                
                foreach ($slide['content'] as $item) {
                    $item = trim($item);
                    
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
                        $qualityIssues++;
                    }
                    
                    // Check for short content
                    if (strlen($item) < 30) {
                        $shortContent++;
                        $qualityIssues++;
                    }
                }
            }
        }
        
        // Generate PowerPoint
        echo "3. Generating PowerPoint...\n";
        $templateData = [
            'template' => 'corporate_blue',
            'color_scheme' => 'blue',
            'font_style' => 'modern'
        ];
        
        $powerPointResult = $service->generatePowerPointWithMicroservice($aiResultId, $templateData, 5);
        
        $fileSize = 0;
        if ($powerPointResult['success'] && file_exists($powerPointResult['data']['powerpoint_file'])) {
            $fileSize = filesize($powerPointResult['data']['powerpoint_file']);
            echo "✅ PowerPoint generated\n";
        } else {
            echo "❌ PowerPoint generation failed\n";
        }
        
        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);
        
        $qualityScore = $totalContentItems > 0 ? round((($totalContentItems - $qualityIssues) / $totalContentItems) * 100, 1) : 0;
        
        $results[] = [
            'topic' => $topic['title'],
            'ai_result_id' => $aiResultId,
            'slides_count' => count($slides),
            'content_slides' => count($contentSlides),
            'total_content_items' => $totalContentItems,
            'quality_issues' => $qualityIssues,
            'generic_phrases' => $genericPhrases,
            'short_content' => $shortContent,
            'quality_score' => $qualityScore,
            'file_size' => $fileSize,
            'execution_time' => $executionTime,
            'success' => $powerPointResult['success']
        ];
        
        echo "📊 Results:\n";
        echo "  Slides: " . count($slides) . " (Content: " . count($contentSlides) . ")\n";
        echo "  Content items: $totalContentItems\n";
        echo "  Quality issues: $qualityIssues\n";
        echo "  Quality score: $qualityScore%\n";
        echo "  File size: " . number_format($fileSize) . " bytes\n";
        echo "  Execution time: {$executionTime}s\n";
        
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n\n";
}

// Final summary
echo "📋 FINAL SUMMARY\n";
echo "================\n\n";

if (!empty($results)) {
    $totalTests = count($results);
    $avgQualityScore = array_sum(array_column($results, 'quality_score')) / $totalTests;
    $avgFileSize = array_sum(array_column($results, 'file_size')) / $totalTests;
    $avgExecutionTime = array_sum(array_column($results, 'execution_time')) / $totalTests;
    $totalQualityIssues = array_sum(array_column($results, 'quality_issues'));
    $totalGenericPhrases = array_sum(array_column($results, 'generic_phrases'));
    $successfulGenerations = count(array_filter($results, function($r) { return $r['success']; }));
    
    echo "Total tests: $totalTests\n";
    echo "Successful generations: $successfulGenerations\n";
    echo "Average quality score: " . round($avgQualityScore, 1) . "%\n";
    echo "Average file size: " . number_format($avgFileSize) . " bytes\n";
    echo "Average execution time: " . round($avgExecutionTime, 2) . "s\n";
    echo "Total quality issues: $totalQualityIssues\n";
    echo "Total generic phrases: $totalGenericPhrases\n\n";
    
    echo "📊 Individual Results:\n";
    echo "=====================\n";
    foreach ($results as $result) {
        echo $result['topic'] . ":\n";
        echo "  Quality: " . $result['quality_score'] . "% | Issues: " . $result['quality_issues'] . " | Size: " . number_format($result['file_size']) . " bytes\n";
    }
    
    echo "\n🎯 FINAL ASSESSMENT:\n";
    echo "===================\n";
    
    if ($avgQualityScore >= 90 && $totalGenericPhrases === 0) {
        echo "✅ EXCELLENT! All improvements successful:\n";
        echo "   - Quality score above 90%\n";
        echo "   - No generic phrases detected\n";
        echo "   - Consistent high-quality content generation\n";
        echo "   - Enhanced PowerPoint designs working\n";
    } elseif ($avgQualityScore >= 75 && $totalGenericPhrases <= 2) {
        echo "✅ GOOD! Significant improvements achieved:\n";
        echo "   - Quality score above 75%\n";
        echo "   - Minimal generic phrases\n";
        echo "   - Much better content quality\n";
    } else {
        echo "⚠️  Some improvements needed:\n";
        echo "   - Quality score: " . round($avgQualityScore, 1) . "%\n";
        echo "   - Generic phrases: $totalGenericPhrases\n";
    }
    
    echo "\n📁 Generated Files:\n";
    echo "==================\n";
    foreach ($results as $result) {
        if ($result['success']) {
            echo $result['topic'] . ": presentation_5_" . $result['ai_result_id'] . "_*.pptx\n";
        }
    }
}

echo "\n🚀 IMPROVEMENTS IMPLEMENTED:\n";
echo "===========================\n";
echo "✅ Upgraded from GPT-3.5 to GPT-4 for better content quality\n";
echo "✅ Added strict validation against generic phrases\n";
echo "✅ Improved fallback content with specific examples\n";
echo "✅ Enhanced prompt with stricter requirements (25-50 words per bullet)\n";
echo "✅ Added content quality validation and rejection system\n";
echo "✅ Eliminated all known generic phrases from fallback content\n";
echo "✅ Enhanced slide designs with modern layouts and visual elements\n";
echo "✅ Improved template colors for better visibility\n";

?>
