<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Finding AI vs Human Presentation\n";
echo "==================================\n\n";

// Search for AI vs Human presentations
$aiResults = \App\Models\AIResult::where('tool_type', 'presentation')
    ->where(function($query) {
        $query->where('result_data->title', 'like', '%AI vs Human%')
              ->orWhere('result_data->title', 'like', '%AI vs Humans%')
              ->orWhere('result_data->title', 'like', '%AI and Human%')
              ->orWhere('result_data->title', 'like', '%AI and Humans%');
    })
    ->orderBy('id', 'desc')
    ->get();

if ($aiResults->isEmpty()) {
    echo "❌ No AI vs Human presentations found\n";
    echo "\nLet me check all recent presentations...\n\n";
    
    // Get all recent presentations
    $allResults = \App\Models\AIResult::where('tool_type', 'presentation')
        ->orderBy('id', 'desc')
        ->limit(10)
        ->get();
    
    echo "📋 Recent Presentations:\n";
    echo "=======================\n";
    
    foreach ($allResults as $result) {
        $title = $result->result_data['title'] ?? 'Unknown Title';
        echo "ID: {$result->id} | Title: $title | Created: {$result->created_at}\n";
    }
    
    exit(1);
}

echo "Found " . $aiResults->count() . " AI vs Human presentation(s):\n\n";

foreach ($aiResults as $aiResult) {
    echo "AI Result ID: " . $aiResult->id . "\n";
    echo "User ID: " . $aiResult->user_id . "\n";
    echo "Title: " . ($aiResult->result_data['title'] ?? 'Unknown') . "\n";
    echo "Created: " . $aiResult->created_at . "\n";
    echo "Step: " . ($aiResult->result_data['step'] ?? 'Unknown') . "\n";
    
    // Check if PowerPoint file exists
    if (isset($aiResult->result_data['powerpoint_file'])) {
        $filePath = $aiResult->result_data['powerpoint_file'];
        echo "📁 PowerPoint File: " . basename($filePath) . "\n";
        
        if (file_exists($filePath)) {
            $fileSize = filesize($filePath);
            $fileTime = filemtime($filePath);
            
            echo "✅ File exists: YES\n";
            echo "📊 File size: " . number_format($fileSize) . " bytes\n";
            echo "📅 Last modified: " . date('Y-m-d H:i:s', $fileTime) . "\n";
            
            // Show Windows path
            $windowsPath = str_replace('/', '\\', $filePath);
            echo "\n🪟 Windows Path:\n";
            echo "===============\n";
            echo "$windowsPath\n";
            
            // Check content status
            $slides = $aiResult->result_data['slides'] ?? [];
            $contentSlides = 0;
            $totalContentItems = 0;
            
            foreach ($slides as $slide) {
                if ($slide['slide_type'] === 'content') {
                    $contentSlides++;
                    if (isset($slide['content']) && is_array($slide['content'])) {
                        $totalContentItems += count($slide['content']);
                    }
                }
            }
            
            echo "\n📊 Content Analysis:\n";
            echo "===================\n";
            echo "Content slides: $contentSlides\n";
            echo "Total content items: $totalContentItems\n";
            
            if ($totalContentItems > 0) {
                echo "✅ Has detailed content\n";
            } else {
                echo "⚠️  Only has subheaders (outline)\n";
            }
            
        } else {
            echo "❌ File does not exist at the specified path\n";
        }
    } else {
        echo "❌ No PowerPoint file found\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n\n";
}

echo "📋 Instructions:\n";
echo "===============\n";
echo "1. Copy the Windows path from the most recent AI vs Human presentation above\n";
echo "2. Open File Explorer\n";
echo "3. Paste the path in the address bar\n";
echo "4. Press Enter to navigate to the file\n";
echo "5. Double-click the .pptx file to open it\n";

?>
