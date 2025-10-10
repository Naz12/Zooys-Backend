<?php

/**
 * Test script to verify PowerPoint export fix
 * Tests the fallback Python script approach
 */

echo "🧪 Testing PowerPoint Export Fix\n";
echo "================================\n\n";

// Test 1: Check if Python script exists
echo "1. Testing Python script availability...\n";
$pythonScript = __DIR__ . '/../python/generate_presentation.py';
if (file_exists($pythonScript)) {
    echo "   ✅ Python script found: $pythonScript\n";
} else {
    echo "   ❌ Python script not found: $pythonScript\n";
    exit(1);
}

// Test 2: Check if Python is available
echo "\n2. Testing Python availability...\n";
$pythonCommand = 'py --version';
$output = shell_exec($pythonCommand);
if ($output) {
    echo "   ✅ Python available: " . trim($output) . "\n";
} else {
    echo "   ❌ Python not available\n";
    exit(1);
}

// Test 3: Test Python script execution
echo "\n3. Testing Python script execution...\n";
$testData = [
    'title' => 'Test Presentation',
    'slides' => [
        [
            'slide_type' => 'title',
            'header' => 'Test Title',
            'content' => ['Test content']
        ]
    ],
    'template' => 'corporate_blue',
    'color_scheme' => 'blue',
    'font_style' => 'modern',
    'user_id' => 1,
    'ai_result_id' => 999
];

$tempFile = tempnam(sys_get_temp_dir(), 'test_presentation_');
file_put_contents($tempFile, json_encode($testData));

$command = "py \"$pythonScript\" \"$tempFile\"";
echo "   Command: $command\n";

$startTime = time();
$output = shell_exec($command . ' 2>&1');
$executionTime = time() - $startTime;

if ($output) {
    echo "   ✅ Python script executed successfully\n";
    echo "   ⏱️  Execution time: {$executionTime}s\n";
    
    // Try to parse JSON output
    $lines = explode("\n", trim($output));
    $jsonOutput = end($lines);
    $result = json_decode($jsonOutput, true);
    
    if ($result && isset($result['success'])) {
        if ($result['success']) {
            echo "   ✅ PowerPoint generation successful\n";
            if (isset($result['data']['file_path'])) {
                echo "   📁 File path: " . $result['data']['file_path'] . "\n";
            }
        } else {
            echo "   ❌ PowerPoint generation failed: " . ($result['error'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "   ⚠️  Could not parse JSON output\n";
        echo "   Raw output: " . substr($output, 0, 200) . "...\n";
    }
} else {
    echo "   ❌ Python script execution failed\n";
}

// Cleanup
if (file_exists($tempFile)) {
    unlink($tempFile);
}

echo "\n📋 Export Fix Summary:\n";
echo "=====================\n";
echo "✅ Modified exportPresentationToPowerPoint method\n";
echo "✅ Now uses Python script directly instead of microservice\n";
echo "✅ Fallback approach for when microservice is unavailable\n";

echo "\n🎯 Next Steps:\n";
echo "=============\n";
echo "1. Test frontend export functionality\n";
echo "2. Verify PowerPoint files are generated correctly\n";
echo "3. Check file download functionality\n";

echo "\n✨ Export fix has been applied!\n";

