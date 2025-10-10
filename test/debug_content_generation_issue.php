<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Debugging Content Generation Issue\n";
echo "====================================\n\n";

// Test with the most recent presentation (ID 151)
$aiResultId = 151;
$userId = 5;

echo "Testing AI Result ID: $aiResultId\n";
echo "User ID: $userId\n\n";

$aiResult = \App\Models\AIResult::find($aiResultId);

if (!$aiResult) {
    echo "❌ AI Result not found\n";
    exit(1);
}

echo "Current step: " . ($aiResult->result_data['step'] ?? 'Unknown') . "\n";
echo "Slides count: " . count($aiResult->result_data['slides'] ?? []) . "\n";

// Check if content generation was attempted
$slides = $aiResult->result_data['slides'] ?? [];
$hasContent = false;
$hasSubheaders = false;

foreach ($slides as $slide) {
    if ($slide['slide_type'] !== 'title') {
        if (isset($slide['content']) && is_array($slide['content']) && count($slide['content']) > 0) {
            $hasContent = true;
            break;
        }
        if (isset($slide['subheaders']) && is_array($slide['subheaders']) && count($slide['subheaders']) > 0) {
            $hasSubheaders = true;
        }
    }
}

echo "Has content: " . ($hasContent ? 'YES' : 'NO') . "\n";
echo "Has subheaders: " . ($hasSubheaders ? 'YES' : 'NO') . "\n";

if (!$hasContent && $hasSubheaders) {
    echo "\n⚠️  ISSUE IDENTIFIED: Only subheaders present, no content generated\n";
    echo "This means the content generation process failed or was skipped.\n\n";
    
    // Try to force content generation
    echo "Attempting to force content generation...\n";
    
    $service = app(\App\Services\AIPresentationService::class);
    
    // Clear any cache
    \Illuminate\Support\Facades\Cache::forget("content_generation_{$aiResultId}_{$userId}");
    \Illuminate\Support\Facades\Cache::forget("content_result_{$aiResultId}_{$userId}");
    
    // Reset the step to force content generation
    $resultData = $aiResult->result_data;
    $resultData['step'] = 'outline_generated'; // Reset to force content generation
    
    $aiResult->update([
        'result_data' => $resultData
    ]);
    
    echo "Reset step to: outline_generated\n";
    
    try {
        $result = $service->generateContent($aiResultId, $userId);
        
        if ($result['success']) {
            echo "✅ Content generation successful\n";
            
            // Check if content was actually generated
            $aiResult->refresh();
            $slides = $aiResult->result_data['slides'] ?? [];
            
            $hasContent = false;
            foreach ($slides as $slide) {
                if ($slide['slide_type'] !== 'title' && isset($slide['content'])) {
                    $hasContent = true;
                    echo "📝 Slide '{$slide['header']}' has " . count($slide['content']) . " content items\n";
                    break;
                }
            }
            
            if ($hasContent) {
                echo "✅ Content was generated and saved\n";
            } else {
                echo "❌ Content was not generated\n";
            }
            
        } else {
            echo "❌ Content generation failed: " . ($result['error'] ?? 'Unknown error') . "\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Content generation exception: " . $e->getMessage() . "\n";
    }
}

echo "\n📋 Summary:\n";
echo "===========\n";
echo "This debug will help identify why content generation is not working\n";
echo "and attempt to fix it by forcing content generation.\n";

?>
