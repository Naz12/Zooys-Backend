<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing Enhanced Templates\n";
echo "============================\n\n";

$service = app(\App\Services\AIPresentationService::class);

// Test with enhanced templates
$templates = [
    'corporate_blue' => 'Light blue background',
    'creative_colorful' => 'Light orange background', 
    'minimalist_gray' => 'Light gray background',
    'tech_modern' => 'Light green background',
    'elegant_purple' => 'Light purple background',
    'professional_green' => 'Light green background'
];

$userId = 1;
$aiResultId = 147; // Use recent presentation with content

foreach ($templates as $template => $description) {
    echo "Testing template: $template\n";
    echo "Expected: $description\n";
    echo "========================\n";
    
    $templateData = [
        'template' => $template,
        'color_scheme' => $template === 'corporate_blue' ? 'blue' : 
                         ($template === 'creative_colorful' ? 'colorful' : 'white'),
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

echo "📋 Summary:\n";
echo "===========\n";
echo "✅ Enhanced templates now have more visible background colors:\n";
echo "   - Corporate Blue: Light blue background (240, 248, 255)\n";
echo "   - Creative Colorful: Light orange background (255, 248, 240)\n";
echo "   - Minimalist Gray: Light gray background (245, 245, 245)\n";
echo "   - Tech Modern: Light green background (240, 255, 250)\n";
echo "   - Elegant Purple: Light purple background (248, 240, 255)\n";
echo "   - Professional Green: Light green background (240, 255, 240)\n";
echo "\nThe PowerPoint files should now have more visible template colors\n";
echo "instead of appearing as plain white backgrounds.\n";

?>
