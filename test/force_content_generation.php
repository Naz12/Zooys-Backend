<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔄 Force Content Generation for Recent Presentations\n";
echo "==================================================\n\n";

// Get the two most recent presentations
$aiResults = \App\Models\AIResult::where('tool_type', 'presentation')
    ->whereIn('id', [146, 147])
    ->orderBy('id', 'desc')
    ->get();

foreach($aiResults as $aiResult) {
    echo "Processing AI Result ID: " . $aiResult->id . "\n";
    
    // Clear any cache
    \Illuminate\Support\Facades\Cache::forget("content_generation_{$aiResult->id}_5");
    \Illuminate\Support\Facades\Cache::forget("content_result_{$aiResult->id}_5");
    
    // Reset the step to force content generation
    $resultData = $aiResult->result_data;
    $resultData['step'] = 'outline_generated'; // Reset to force content generation
    
    $aiResult->update([
        'result_data' => $resultData
    ]);
    
    echo "   Reset step to: outline_generated\n";
    
    // Now call content generation
    $service = app(\App\Services\AIPresentationService::class);
    
    try {
        $result = $service->generateContent($aiResult->id, 5);
        
        if ($result['success']) {
            echo "   ✅ Content generation successful\n";
            
            // Check if content was actually generated
            $aiResult->refresh();
            $slides = $aiResult->result_data['slides'] ?? [];
            
            $hasContent = false;
            foreach ($slides as $slide) {
                if ($slide['slide_type'] !== 'title' && isset($slide['content'])) {
                    $hasContent = true;
                    echo "   📝 Slide '{$slide['header']}' has " . count($slide['content']) . " content items\n";
                    break;
                }
            }
            
            if ($hasContent) {
                echo "   ✅ Content was generated and saved\n";
            } else {
                echo "   ❌ Content was not generated\n";
            }
            
        } else {
            echo "   ❌ Content generation failed: " . ($result['error'] ?? 'Unknown error') . "\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Content generation exception: " . $e->getMessage() . "\n";
    }
    
    echo "---\n";
}

echo "\n📋 Summary:\n";
echo "===========\n";
echo "This will force content generation for the recent presentations\n";
echo "and verify that the content is actually being generated and saved.\n";

?>