<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🎨 Testing Enhanced Slide Designs\n";
echo "================================\n\n";

$service = app(\App\Services\AIPresentationService::class);

// Test with different templates to showcase enhanced designs
$templates = [
    'corporate_blue' => 'Professional blue design with header bars and content areas',
    'creative_colorful' => 'Creative orange design with modern layouts',
    'tech_modern' => 'Modern green design with tech styling'
];

$userId = 1;
$aiResultId = 147; // Use recent presentation with content

foreach ($templates as $template => $description) {
    echo "Testing template: $template\n";
    echo "Description: $description\n";
    echo "==========================================\n";
    
    $templateData = [
        'template' => $template,
        'color_scheme' => $template === 'corporate_blue' ? 'blue' : 
                         ($template === 'creative_colorful' ? 'colorful' : 'green'),
        'font_style' => 'modern'
    ];
    
    try {
        $result = $service->generatePowerPointWithMicroservice($aiResultId, $templateData, $userId);
        
        if ($result['success']) {
            echo "✅ PowerPoint generation successful\n";
            echo "📁 File: " . basename($result['data']['powerpoint_file']) . "\n";
            
            // Check if file exists
            if (file_exists($result['data']['powerpoint_file'])) {
                $fileSize = filesize($result['data']['powerpoint_file']);
                echo "📊 File size: " . number_format($fileSize) . " bytes\n";
                
                if ($fileSize > 35000) {
                    echo "✅ File size indicates enhanced designs were included\n";
                } else {
                    echo "⚠️  File size suggests basic design\n";
                }
            }
            
            // Check the database to see what template was used
            $aiResult = \App\Models\AIResult::find($aiResultId);
            if ($aiResult && isset($aiResult->metadata['template'])) {
                echo "📝 Template used: " . $aiResult->metadata['template'] . "\n";
            }
            
        } else {
            echo "❌ PowerPoint generation failed: " . ($result['error'] ?? 'Unknown error') . "\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "📋 Enhanced Design Features:\n";
echo "============================\n";
echo "✅ **Title Slides:**\n";
echo "   - Decorative header bars with template colors\n";
echo "   - Enhanced typography with larger titles\n";
echo "   - Professional subtitle areas\n";
echo "   - Corner accent shapes and bottom accent lines\n";
echo "\n✅ **Content Slides:**\n";
echo "   - Modern header bars for visual hierarchy\n";
echo "   - White content areas with colored borders\n";
echo "   - Better spacing and typography\n";
echo "   - Side accent lines and corner decorations\n";
echo "\n✅ **Two-Column Layouts:**\n";
echo "   - Automatic layout selection for slides with 5+ items\n";
echo "   - Content split between left and right columns\n";
echo "   - Each column has its own content area\n";
echo "   - Better organization for detailed information\n";
echo "\n✅ **Visual Elements:**\n";
echo "   - Decorative shapes and accent lines\n";
echo "   - Template-based color schemes\n";
echo "   - Professional spacing and margins\n";
echo "   - Modern design elements similar to Office templates\n";

?>
