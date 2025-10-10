<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🚀 Testing Improved Prompt (Final Version)\n";
echo "=========================================\n\n";

$service = app(\App\Services\AIPresentationService::class);

// Test with a challenging topic that previously had issues
$testTopic = [
    'title' => 'Blockchain Technology in Healthcare',
    'topic' => 'Explore how blockchain technology is revolutionizing healthcare data management and patient care',
    'slides_count' => 8,
    'target_audience' => 'Healthcare professionals',
    'presentation_style' => 'Technical'
];

echo "🎯 Testing Topic: " . $testTopic['title'] . "\n";
echo "==========================================\n\n";

$startTime = microtime(true);

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
    echo "2. Generating content with improved prompt...\n";
    $contentResult = $service->generateContent($aiResultId, 5);
    
    if (!$contentResult['success']) {
        echo "❌ Content generation failed: " . ($contentResult['error'] ?? 'Unknown error') . "\n";
        exit(1);
    }
    
    echo "✅ Content generated\n";
    
    // Analyze content quality
    echo "3. Analyzing content quality...\n";
    $aiResult = \App\Models\AIResult::find($aiResultId);
    $slides = $aiResult->result_data['slides'] ?? [];
    
    $contentSlides = array_filter($slides, function($slide) {
        return $slide['slide_type'] === 'content';
    });
    
    $totalContentItems = 0;
    $qualityIssues = 0;
    $genericPhrases = 0;
    $shortContent = 0;
    $excellentContent = 0;
    
    echo "\n📊 Content Quality Analysis:\n";
    echo "============================\n\n";
    
    foreach ($contentSlides as $index => $slide) {
        echo "Slide " . ($index + 1) . ": " . $slide['header'] . "\n";
        
        if (isset($slide['content']) && is_array($slide['content'])) {
            echo "Content items: " . count($slide['content']) . "\n";
            $totalContentItems += count($slide['content']);
            
            foreach ($slide['content'] as $i => $item) {
                echo "  " . ($i + 1) . ". " . substr($item, 0, 80) . "...\n";
                
                // Check for quality issues
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
                    echo "    ❌ GENERIC PHRASE\n";
                }
                
                // Check for short content
                if (strlen($item) < 30) {
                    $shortContent++;
                    $qualityIssues++;
                    echo "    ❌ TOO SHORT (" . strlen($item) . " chars)\n";
                }
                
                // Check for excellent content (long, specific, with numbers or examples)
                if (strlen($item) > 40 && 
                    (preg_match('/\d+/', $item) || 
                     strpos($item, 'example') !== false || 
                     strpos($item, 'case study') !== false ||
                     strpos($item, 'research') !== false)) {
                    $excellentContent++;
                    echo "    ✅ EXCELLENT CONTENT\n";
                }
            }
        } else {
            echo "❌ No content found\n";
        }
        
        echo "\n";
    }
    
    $qualityScore = $totalContentItems > 0 ? round((($totalContentItems - $qualityIssues) / $totalContentItems) * 100, 1) : 0;
    
    echo "📋 Quality Summary:\n";
    echo "==================\n";
    echo "Total content items: $totalContentItems\n";
    echo "Generic phrases: $genericPhrases\n";
    echo "Short content: $shortContent\n";
    echo "Excellent content: $excellentContent\n";
    echo "Total quality issues: $qualityIssues\n";
    echo "Quality score: $qualityScore%\n";
    
    if ($qualityScore >= 90) {
        echo "✅ EXCELLENT! Quality score above 90%\n";
    } elseif ($qualityScore >= 75) {
        echo "✅ GOOD! Quality score above 75%\n";
    } elseif ($qualityScore >= 50) {
        echo "⚠️  FAIR! Quality score above 50%\n";
    } else {
        echo "❌ POOR! Quality score below 50%\n";
    }
    
    // Generate PowerPoint
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
    } else {
        echo "❌ PowerPoint generation failed: " . ($powerPointResult['error'] ?? 'Unknown error') . "\n";
    }
    
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);
    echo "⏱️  Total execution time: {$executionTime}s\n";
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n📋 Improvements Made:\n";
echo "====================\n";
echo "✅ Upgraded to GPT-4 for better content generation\n";
echo "✅ Added strict validation against generic phrases\n";
echo "✅ Improved fallback content with specific examples\n";
echo "✅ Enhanced prompt with stricter requirements\n";
echo "✅ Added content quality validation\n";
echo "✅ Increased minimum content length to 25-50 words\n";

echo "\n🎯 Expected Results:\n";
echo "===================\n";
echo "- Quality score should be 90% or higher\n";
echo "- No generic phrases should appear\n";
echo "- Content should be specific and detailed\n";
echo "- Each bullet point should be 25-50 words\n";
echo "- Content should include specific examples or data\n";

?>
