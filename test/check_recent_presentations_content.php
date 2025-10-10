<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Checking Recent Presentations Content\n";
echo "======================================\n\n";

// Get the 5 most recent presentations
$aiResults = \App\Models\AIResult::where('tool_type', 'presentation')
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();

foreach($aiResults as $aiResult) {
    echo "AI Result ID: " . $aiResult->id . "\n";
    echo "User ID: " . $aiResult->user_id . "\n";
    echo "Created: " . $aiResult->created_at . "\n";
    echo "Step: " . ($aiResult->result_data['step'] ?? 'Unknown') . "\n";
    
    $slides = $aiResult->result_data['slides'] ?? [];
    echo "Slides count: " . count($slides) . "\n";
    
    if (count($slides) > 0) {
        $firstSlide = $slides[0];
        echo "First slide header: " . ($firstSlide['header'] ?? 'No header') . "\n";
        echo "First slide type: " . ($firstSlide['slide_type'] ?? 'Unknown') . "\n";
        
        // Check if it has content or just subheaders
        $hasContent = isset($firstSlide['content']) && is_array($firstSlide['content']) && count($firstSlide['content']) > 0;
        $hasSubheaders = isset($firstSlide['subheaders']) && is_array($firstSlide['subheaders']) && count($firstSlide['subheaders']) > 0;
        
        echo "Has content: " . ($hasContent ? 'YES' : 'NO') . "\n";
        echo "Has subheaders: " . ($hasSubheaders ? 'YES' : 'NO') . "\n";
        
        if ($hasContent) {
            echo "Content items: " . count($firstSlide['content']) . "\n";
            echo "First content: " . substr($firstSlide['content'][0] ?? '', 0, 50) . "...\n";
        } elseif ($hasSubheaders) {
            echo "Subheader items: " . count($firstSlide['subheaders']) . "\n";
            echo "First subheader: " . substr($firstSlide['subheaders'][0] ?? '', 0, 50) . "...\n";
        }
        
        // Check if PowerPoint was generated
        $hasPowerPoint = isset($aiResult->result_data['powerpoint_file']);
        echo "Has PowerPoint: " . ($hasPowerPoint ? 'YES' : 'NO') . "\n";
        
        if ($hasPowerPoint) {
            echo "PowerPoint file: " . basename($aiResult->result_data['powerpoint_file']) . "\n";
        }
    }
    
    echo "---\n";
}

echo "\n📋 Analysis:\n";
echo "============\n";
echo "This will help identify if the recent presentations have proper content\n";
echo "or if they're only showing outlines.\n";

?>
