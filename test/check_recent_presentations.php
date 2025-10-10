<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Checking Recent Presentations\n";
echo "================================\n\n";

// Get the two most recent presentations
$aiResults = \App\Models\AIResult::where('tool_type', 'presentation')
    ->whereIn('id', [146, 147])
    ->orderBy('id', 'desc')
    ->get(['id', 'result_data', 'metadata', 'created_at']);

foreach($aiResults as $result) {
    echo "AI Result ID: " . $result->id . "\n";
    echo "Created: " . $result->created_at . "\n";
    echo "Step: " . ($result->result_data['step'] ?? 'unknown') . "\n";
    
    if(isset($result->result_data['slides'][0])) {
        $firstSlide = $result->result_data['slides'][0];
        echo "First slide header: " . ($firstSlide['header'] ?? 'N/A') . "\n";
        echo "Has content in first slide: " . (isset($firstSlide['content']) ? 'YES' : 'NO') . "\n";
        
        if(isset($firstSlide['content'])) {
            echo "Content count: " . count($firstSlide['content']) . "\n";
            echo "First content item: " . substr($firstSlide['content'][0] ?? '', 0, 50) . "...\n";
        }
        
        echo "Has subheaders in first slide: " . (isset($firstSlide['subheaders']) ? 'YES' : 'NO') . "\n";
        if(isset($firstSlide['subheaders'])) {
            echo "Subheaders count: " . count($firstSlide['subheaders']) . "\n";
            echo "First subheader: " . ($firstSlide['subheaders'][0] ?? 'N/A') . "\n";
        }
    }
    
    echo "---\n";
}

echo "\n📊 Analysis:\n";
echo "============\n";
echo "If 'Has content' is NO and 'Has subheaders' is YES, then the PowerPoint\n";
echo "was generated with only outline data instead of full content.\n";

?>
