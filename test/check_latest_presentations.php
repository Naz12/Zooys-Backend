<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Checking Latest Presentations for Issues\n";
echo "==========================================\n\n";

// Get the 5 most recent AI results
$recentResults = \App\Models\AIResult::orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

echo "📊 Recent Presentations Analysis:\n";
echo "================================\n\n";

foreach ($recentResults as $index => $result) {
    echo "Presentation " . ($index + 1) . " (ID: {$result->id})\n";
    echo "Created: " . $result->created_at . "\n";
    echo "Title: " . ($result->result_data['title'] ?? 'Unknown') . "\n";
    echo "Step: " . ($result->result_data['step'] ?? 'Unknown') . "\n";
    
    $slides = $result->result_data['slides'] ?? [];
    echo "Total slides: " . count($slides) . "\n";
    
    // Analyze content slides
    $contentSlides = array_filter($slides, function($slide) {
        return ($slide['slide_type'] ?? '') === 'content';
    });
    
    echo "Content slides: " . count($contentSlides) . "\n";
    
    if (!empty($contentSlides)) {
        echo "\n📋 Content Analysis:\n";
        echo "-------------------\n";
        
        $allContent = [];
        $duplicateCount = 0;
        $shortContentCount = 0;
        $genericPhraseCount = 0;
        
        foreach ($contentSlides as $slideIndex => $slide) {
            echo "Slide " . ($slideIndex + 1) . ": " . $slide['header'] . "\n";
            
            if (isset($slide['content']) && is_array($slide['content'])) {
                echo "  Content items: " . count($slide['content']) . "\n";
                
                foreach ($slide['content'] as $i => $item) {
                    echo "    " . ($i + 1) . ". " . substr($item, 0, 80) . (strlen($item) > 80 ? "..." : "") . "\n";
                    
                    // Check for duplicates
                    if (in_array($item, $allContent)) {
                        $duplicateCount++;
                        echo "      ❌ DUPLICATE\n";
                    }
                    $allContent[] = $item;
                    
                    // Check for short content
                    if (strlen(trim($item)) < 30) {
                        $shortContentCount++;
                        echo "      ❌ TOO SHORT (" . strlen(trim($item)) . " chars)\n";
                    }
                    
                    // Check for generic phrases
                    $genericPhrases = [
                        'Industry best practices',
                        'Success metrics',
                        'Key performance indicators',
                        'Important aspects',
                        'Current status',
                        'Specific examples',
                        'Detailed analysis',
                        'Practical applications',
                        'Measurable outcomes',
                        'Real-world applications'
                    ];
                    
                    foreach ($genericPhrases as $phrase) {
                        if (stripos($item, $phrase) !== false) {
                            $genericPhraseCount++;
                            echo "      ❌ GENERIC PHRASE: $phrase\n";
                            break;
                        }
                    }
                }
            } else {
                echo "  ❌ NO CONTENT FOUND\n";
            }
            echo "\n";
        }
        
        echo "📊 Summary for this presentation:\n";
        echo "  Total content items: " . count($allContent) . "\n";
        echo "  Duplicates: $duplicateCount\n";
        echo "  Short content: $shortContentCount\n";
        echo "  Generic phrases: $genericPhraseCount\n";
        
        $qualityScore = count($allContent) > 0 ? 
            round((count($allContent) - $duplicateCount - $shortContentCount - $genericPhraseCount) / count($allContent) * 100, 1) : 0;
        echo "  Quality score: $qualityScore%\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n\n";
}

// Check if there are any PowerPoint files generated recently
echo "📁 Recent PowerPoint Files:\n";
echo "===========================\n";

$presentationDir = storage_path('app/presentations');
if (is_dir($presentationDir)) {
    $files = glob($presentationDir . '/*.pptx');
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    $recentFiles = array_slice($files, 0, 3);
    
    foreach ($recentFiles as $file) {
        echo "File: " . basename($file) . "\n";
        echo "Size: " . number_format(filesize($file)) . " bytes\n";
        echo "Modified: " . date('Y-m-d H:i:s', filemtime($file)) . "\n";
        echo "Path: " . $file . "\n\n";
    }
} else {
    echo "❌ Presentations directory not found\n";
}

?>

