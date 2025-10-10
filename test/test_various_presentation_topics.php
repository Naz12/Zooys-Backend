<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing PowerPoint Tool with Various Topics\n";
echo "============================================\n\n";

$service = app(\App\Services\AIPresentationService::class);

// Test different types of presentations
$testTopics = [
    [
        'title' => 'The Future of Artificial Intelligence',
        'topic' => 'Explore the latest developments in AI technology and its impact on society',
        'slides_count' => 6,
        'target_audience' => 'Tech professionals',
        'presentation_style' => 'Technical'
    ],
    [
        'title' => 'Sustainable Business Practices',
        'topic' => 'How companies can implement eco-friendly strategies for long-term success',
        'slides_count' => 8,
        'target_audience' => 'Business executives',
        'presentation_style' => 'Professional'
    ],
    [
        'title' => 'Digital Marketing Strategies 2024',
        'topic' => 'Latest trends and effective strategies in digital marketing',
        'slides_count' => 7,
        'target_audience' => 'Marketing professionals',
        'presentation_style' => 'Modern'
    ],
    [
        'title' => 'Mental Health in the Workplace',
        'topic' => 'Addressing mental health challenges and creating supportive work environments',
        'slides_count' => 6,
        'target_audience' => 'HR professionals',
        'presentation_style' => 'Sensitive'
    ],
    [
        'title' => 'Space Exploration and Colonization',
        'topic' => 'Current space missions and future plans for human colonization of other planets',
        'slides_count' => 9,
        'target_audience' => 'General audience',
        'presentation_style' => 'Educational'
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
            echo "❌ Outline generation failed: " . ($outlineResult['error'] ?? 'Unknown error') . "\n";
            continue;
        }
        
        $aiResultId = $outlineResult['data']['ai_result_id'];
        echo "✅ Outline generated (ID: $aiResultId)\n";
        
        // Generate content
        echo "2. Generating content...\n";
        $contentResult = $service->generateContent($aiResultId, 5);
        
        if (!$contentResult['success']) {
            echo "❌ Content generation failed: " . ($contentResult['error'] ?? 'Unknown error') . "\n";
            continue;
        }
        
        echo "✅ Content generated\n";
        
        // Generate PowerPoint
        echo "3. Generating PowerPoint...\n";
        $templateData = [
            'template' => 'corporate_blue',
            'color_scheme' => 'blue',
            'font_style' => 'modern'
        ];
        
        $powerPointResult = $service->generatePowerPointWithMicroservice($aiResultId, $templateData, 5);
        
        if (!$powerPointResult['success']) {
            echo "❌ PowerPoint generation failed: " . ($powerPointResult['error'] ?? 'Unknown error') . "\n";
            continue;
        }
        
        echo "✅ PowerPoint generated\n";
        
        // Analyze the result
        $aiResult = \App\Models\AIResult::find($aiResultId);
        $slides = $aiResult->result_data['slides'] ?? [];
        
        $contentSlides = array_filter($slides, function($slide) {
            return $slide['slide_type'] === 'content';
        });
        
        $totalContentItems = 0;
        $qualityIssues = 0;
        $genericPhrases = 0;
        $shortContent = 0;
        $duplicateContent = 0;
        
        foreach ($contentSlides as $slide) {
            if (isset($slide['content']) && is_array($slide['content'])) {
                $totalContentItems += count($slide['content']);
                
                foreach ($slide['content'] as $item) {
                    $item = trim($item);
                    
                    // Check for generic phrases
                    if (strpos($item, 'Important aspects and key features') !== false ||
                        strpos($item, 'Current status and future potential') !== false ||
                        strpos($item, 'Specific examples and real-world applications') !== false ||
                        strpos($item, 'Detailed analysis and comprehensive coverage') !== false) {
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
        
        // Check file size
        $fileSize = 0;
        if (file_exists($powerPointResult['data']['powerpoint_file'])) {
            $fileSize = filesize($powerPointResult['data']['powerpoint_file']);
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
            'file_path' => $powerPointResult['data']['powerpoint_file']
        ];
        
        echo "📊 Results:\n";
        echo "  Slides: " . count($slides) . " (Content: " . count($contentSlides) . ")\n";
        echo "  Content items: $totalContentItems\n";
        echo "  Quality issues: $qualityIssues\n";
        echo "  Quality score: $qualityScore%\n";
        echo "  File size: " . number_format($fileSize) . " bytes\n";
        echo "  Execution time: {$executionTime}s\n";
        echo "  File: " . basename($powerPointResult['data']['powerpoint_file']) . "\n";
        
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n\n";
}

// Summary analysis
echo "📋 SUMMARY ANALYSIS\n";
echo "==================\n\n";

if (!empty($results)) {
    $totalTests = count($results);
    $avgQualityScore = array_sum(array_column($results, 'quality_score')) / $totalTests;
    $avgFileSize = array_sum(array_column($results, 'file_size')) / $totalTests;
    $avgExecutionTime = array_sum(array_column($results, 'execution_time')) / $totalTests;
    $totalQualityIssues = array_sum(array_column($results, 'quality_issues'));
    $totalGenericPhrases = array_sum(array_column($results, 'generic_phrases'));
    
    echo "Total tests: $totalTests\n";
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
    
    echo "\n🔍 Issues Found:\n";
    echo "===============\n";
    if ($totalGenericPhrases > 0) {
        echo "❌ Generic phrases still appearing: $totalGenericPhrases instances\n";
    }
    if ($avgQualityScore < 90) {
        echo "⚠️  Quality score below 90%: " . round($avgQualityScore, 1) . "%\n";
    }
    if ($totalQualityIssues > 5) {
        echo "⚠️  High number of quality issues: $totalQualityIssues\n";
    }
    
    if ($totalGenericPhrases === 0 && $avgQualityScore >= 90) {
        echo "✅ Excellent results! No major issues found.\n";
    } else {
        echo "\n💡 Recommendations for prompt improvement:\n";
        echo "=======================================\n";
        if ($totalGenericPhrases > 0) {
            echo "- Add stronger restrictions against generic phrases\n";
        }
        if ($avgQualityScore < 90) {
            echo "- Improve content specificity requirements\n";
            echo "- Add more detailed examples in the prompt\n";
        }
        echo "- Consider adding topic-specific content guidelines\n";
        echo "- Enhance the quality validation in the prompt\n";
    }
}

echo "\n📁 Generated Files:\n";
echo "==================\n";
foreach ($results as $result) {
    echo $result['topic'] . ": " . basename($result['file_path']) . "\n";
}

?>
