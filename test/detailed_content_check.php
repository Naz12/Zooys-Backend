<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Detailed Content Check\n";
echo "========================\n\n";

// Check the most recent presentation (ID 151)
$aiResultId = 151;
$aiResult = \App\Models\AIResult::find($aiResultId);

if (!$aiResult) {
    echo "❌ AI Result not found\n";
    exit(1);
}

echo "AI Result ID: $aiResultId\n";
echo "Step: " . ($aiResult->result_data['step'] ?? 'Unknown') . "\n";
echo "Slides count: " . count($aiResult->result_data['slides'] ?? []) . "\n\n";

$slides = $aiResult->result_data['slides'] ?? [];

foreach ($slides as $index => $slide) {
    echo "Slide " . ($index + 1) . ": " . ($slide['header'] ?? 'No header') . "\n";
    echo "  Type: " . ($slide['slide_type'] ?? 'Unknown') . "\n";
    
    // Check content
    if (isset($slide['content']) && is_array($slide['content'])) {
        echo "  Content items: " . count($slide['content']) . "\n";
        if (count($slide['content']) > 0) {
            echo "  First content: " . substr($slide['content'][0] ?? '', 0, 60) . "...\n";
        }
    } else {
        echo "  Content: NO\n";
    }
    
    // Check subheaders
    if (isset($slide['subheaders']) && is_array($slide['subheaders'])) {
        echo "  Subheader items: " . count($slide['subheaders']) . "\n";
        if (count($slide['subheaders']) > 0) {
            echo "  First subheader: " . substr($slide['subheaders'][0] ?? '', 0, 60) . "...\n";
        }
    } else {
        echo "  Subheaders: NO\n";
    }
    
    echo "\n";
}

// Now test PowerPoint generation to see what's actually being used
echo "Testing PowerPoint generation...\n";
echo "===============================\n";

$service = app(\App\Services\AIPresentationService::class);

$templateData = [
    'template' => 'corporate_blue',
    'color_scheme' => 'blue',
    'font_style' => 'modern'
];

try {
    $result = $service->generatePowerPointWithMicroservice($aiResultId, $templateData, 5);
    
    if ($result['success']) {
        echo "✅ PowerPoint generation successful\n";
        echo "📁 File: " . basename($result['data']['powerpoint_file']) . "\n";
        
        if (file_exists($result['data']['powerpoint_file'])) {
            $fileSize = filesize($result['data']['powerpoint_file']);
            echo "📊 File size: " . number_format($fileSize) . " bytes\n";
            
            if ($fileSize > 40000) {
                echo "✅ File size suggests content was included\n";
            } else {
                echo "⚠️  File size suggests only outline was included\n";
            }
        }
    } else {
        echo "❌ PowerPoint generation failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n📋 Analysis:\n";
echo "============\n";
echo "This detailed check will show exactly what content is available\n";
echo "and test the PowerPoint generation to see what's being used.\n";

?>
