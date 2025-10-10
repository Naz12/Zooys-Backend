<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Debugging Template Issue\n";
echo "==========================\n\n";

$service = app(\App\Services\AIPresentationService::class);

// Test with different templates
$templates = [
    'corporate_blue',
    'modern_white', 
    'creative_colorful',
    'minimalist_gray'
];

$userId = 1;
$aiResultId = 147; // Use recent presentation with content

foreach ($templates as $template) {
    echo "Testing template: $template\n";
    echo "========================\n";
    
    $templateData = [
        'template' => $template,
        'color_scheme' => $template === 'corporate_blue' ? 'blue' : 
                         ($template === 'creative_colorful' ? 'colorful' : 'white'),
        'font_style' => 'modern'
    ];
    
    echo "Template data being sent:\n";
    echo json_encode($templateData, JSON_PRETTY_PRINT) . "\n";
    
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

echo "📋 Analysis:\n";
echo "============\n";
echo "This will help identify if the template data is being passed correctly\n";
echo "and if the microservice is applying the templates properly.\n";

?>
