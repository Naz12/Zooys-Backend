<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "📁 Latest PowerPoint File Path\n";
echo "=============================\n\n";

// Get the most recent presentation
$aiResult = \App\Models\AIResult::where('tool_type', 'presentation')
    ->orderBy('id', 'desc')
    ->first();

if (!$aiResult) {
    echo "❌ No presentations found\n";
    exit(1);
}

echo "AI Result ID: " . $aiResult->id . "\n";
echo "User ID: " . $aiResult->id . "\n";
echo "Title: " . ($aiResult->result_data['title'] ?? 'Unknown') . "\n";
echo "Created: " . $aiResult->created_at . "\n\n";

// Check if PowerPoint file exists
if (isset($aiResult->result_data['powerpoint_file'])) {
    $filePath = $aiResult->result_data['powerpoint_file'];
    echo "📁 PowerPoint File Path:\n";
    echo "========================\n";
    echo "Full Path: $filePath\n";
    echo "File Name: " . basename($filePath) . "\n";
    
    // Check if file exists
    if (file_exists($filePath)) {
        $fileSize = filesize($filePath);
        $fileTime = filemtime($filePath);
        
        echo "✅ File exists: YES\n";
        echo "📊 File size: " . number_format($fileSize) . " bytes\n";
        echo "📅 Last modified: " . date('Y-m-d H:i:s', $fileTime) . "\n";
        
        // Convert to Windows path format
        $windowsPath = str_replace('/', '\\', $filePath);
        echo "\n🪟 Windows Path:\n";
        echo "===============\n";
        echo "$windowsPath\n";
        
        // Also show the storage path
        $storagePath = storage_path('app/presentations/' . basename($filePath));
        echo "\n📂 Storage Path:\n";
        echo "===============\n";
        echo "$storagePath\n";
        
        $windowsStoragePath = str_replace('/', '\\', $storagePath);
        echo "\n🪟 Windows Storage Path:\n";
        echo "=======================\n";
        echo "$windowsStoragePath\n";
        
    } else {
        echo "❌ File does not exist at the specified path\n";
        
        // Try to find the file in the storage directory
        $fileName = basename($filePath);
        $storageDir = storage_path('app/presentations');
        
        if (is_dir($storageDir)) {
            $files = glob($storageDir . '/*.pptx');
            echo "\n📂 Available PowerPoint files in storage:\n";
            echo "========================================\n";
            
            foreach ($files as $file) {
                $fileSize = filesize($file);
                $fileTime = filemtime($file);
                echo basename($file) . " (" . number_format($fileSize) . " bytes, " . date('Y-m-d H:i:s', $fileTime) . ")\n";
            }
            
            // Get the most recent file
            if (!empty($files)) {
                $mostRecent = max($files, function($a, $b) {
                    return filemtime($a) - filemtime($b);
                });
                
                echo "\n🕒 Most Recent File:\n";
                echo "==================\n";
                echo "Path: $mostRecent\n";
                echo "Windows Path: " . str_replace('/', '\\', $mostRecent) . "\n";
            }
        }
    }
} else {
    echo "❌ No PowerPoint file path found in result data\n";
}

echo "\n📋 Instructions:\n";
echo "===============\n";
echo "1. Copy the Windows path above\n";
echo "2. Open File Explorer\n";
echo "3. Paste the path in the address bar\n";
echo "4. Press Enter to navigate to the file\n";
echo "5. Double-click the .pptx file to open it\n";

?>
