<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing Improved Content Generation\n";
echo "====================================\n\n";

$service = app(\App\Services\AIPresentationService::class);

// Test with a new presentation to see the improved content quality
$testData = [
    'title' => 'Test Improved Content Generation',
    'slides' => [
        [
            'slide_number' => 1,
            'header' => 'Introduction',
            'subheaders' => ['Overview', 'Objectives'],
            'slide_type' => 'title'
        ],
        [
            'slide_number' => 2,
            'header' => 'AI Advantages',
            'subheaders' => ['Speed', 'Accuracy', 'Efficiency'],
            'slide_type' => 'content'
        ],
        [
            'slide_number' => 3,
            'header' => 'AI Disadvantages',
            'subheaders' => ['Lack of Empathy', 'Limited Creativity', 'Dependency'],
            'slide_type' => 'content'
        ],
        [
            'slide_number' => 4,
            'header' => 'Conclusion',
            'subheaders' => ['Summary', 'Future Outlook'],
            'slide_type' => 'content'
        ]
    ]
];

echo "1. Testing improved content generation...\n";
echo "   Slides to process: " . count($testData['slides']) . "\n\n";

try {
    // Test the generateAllSlideContent method directly
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('generateAllSlideContent');
    $method->setAccessible(true);
    
    $contentSlides = array_filter($testData['slides'], function($slide) {
        return $slide['slide_type'] === 'content';
    });
    
    $result = $method->invoke($service, $contentSlides, $testData['title']);
    
    echo "✅ Content generation successful\n\n";
    
    // Analyze the generated content
    echo "📊 Content Quality Analysis:\n";
    echo "============================\n";
    
    $qualityIssues = 0;
    $totalItems = 0;
    
    foreach ($result as $index => $content) {
        $slide = $contentSlides[$index];
        echo "Slide " . ($index + 1) . ": " . $slide['header'] . "\n";
        echo "Content items: " . count($content) . "\n";
        
        foreach ($content as $i => $item) {
            $totalItems++;
            echo "  " . ($i + 1) . ". " . $item . "\n";
            
            // Check for quality issues
            $item = trim($item);
            
            // Check for generic phrases
            if (strpos($item, 'Important aspects and key features') !== false ||
                strpos($item, 'Current status and future potential') !== false) {
                $qualityIssues++;
                echo "    ⚠️  Generic content detected\n";
            }
            
            // Check for too short content
            if (strlen($item) < 30) {
                $qualityIssues++;
                echo "    ⚠️  Too short (" . strlen($item) . " chars)\n";
            }
            
            // Check for single words
            $words = explode(' ', str_replace(['•', ',', '.', '!', '?'], '', $item));
            if (count($words) <= 3) {
                $qualityIssues++;
                echo "    ⚠️  Too few words (" . count($words) . " words)\n";
            }
        }
        echo "\n";
    }
    
    echo "📋 Quality Summary:\n";
    echo "==================\n";
    echo "Total content items: $totalItems\n";
    echo "Quality issues: $qualityIssues\n";
    echo "Quality score: " . round((($totalItems - $qualityIssues) / $totalItems) * 100, 1) . "%\n";
    
    if ($qualityIssues === 0) {
        echo "✅ Excellent! No quality issues found\n";
    } elseif ($qualityIssues <= 2) {
        echo "✅ Good! Minimal quality issues\n";
    } else {
        echo "⚠️  Some quality issues remain\n";
    }
    
} catch (Exception $e) {
    echo "❌ Content generation failed: " . $e->getMessage() . "\n";
}

echo "\n📋 Improvements Made:\n";
echo "====================\n";
echo "✅ Enhanced prompt with specific requirements\n";
echo "✅ Added content length requirements (15-40 words)\n";
echo "✅ Eliminated generic phrases\n";
echo "✅ Added topic-specific content guidelines\n";
echo "✅ Improved fallback content generation\n";
echo "✅ Added quality standards and examples\n";

?>
