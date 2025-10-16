<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\AIResult;

echo "=== RESTORING PRESENTATION DATA ===\n\n";

// Get the original "Exploring Canada" data from ID 218
echo "1. Getting original data from presentation 218...\n";
$originalPresentation = AIResult::where('id', 218)->where('tool_type', 'presentation')->first();

if (!$originalPresentation) {
    echo "❌ Original presentation 218 not found!\n";
    exit(1);
}

echo "✅ Found original presentation:\n";
echo "Title: " . ($originalPresentation->result_data['title'] ?? 'No title') . "\n";
echo "Slides: " . count($originalPresentation->result_data['slides'] ?? []) . "\n";

// Update presentation 219 with the original data
echo "\n2. Restoring data to presentation 219...\n";
$corruptedPresentation = AIResult::where('id', 219)->where('tool_type', 'presentation')->first();

if (!$corruptedPresentation) {
    echo "❌ Presentation 219 not found!\n";
    exit(1);
}

// Restore the original data but keep the ID 219
$corruptedPresentation->result_data = $originalPresentation->result_data;
$corruptedPresentation->metadata = $originalPresentation->metadata;
$corruptedPresentation->save();

echo "✅ Data restored successfully!\n";

// Verify the restoration
echo "\n3. Verifying restoration...\n";
$restoredPresentation = AIResult::where('id', 219)->where('tool_type', 'presentation')->first();

if ($restoredPresentation) {
    echo "Title: " . ($restoredPresentation->result_data['title'] ?? 'No title') . "\n";
    echo "Slides: " . count($restoredPresentation->result_data['slides'] ?? []) . "\n";
    echo "Updated at: " . $restoredPresentation->updated_at . "\n";
    
    if (count($restoredPresentation->result_data['slides'] ?? []) >= 10) {
        echo "✅ Restoration successful - full presentation restored!\n";
    } else {
        echo "❌ Restoration failed - still showing corrupted data\n";
    }
}

echo "\n=== RESTORATION COMPLETE ===\n";

