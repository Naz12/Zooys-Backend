<?php
/**
 * Test script to debug Python path issues
 */

echo "=== Python Path Debug ===\n\n";

// Test different Python commands
$pythonCommands = [
    'python3',
    'python', 
    'py',
    'C:\\Python311\\python.exe',
    'C:\\Users\\nazrawi\\AppData\\Local\\Programs\\Python\\Python311\\python.exe'
];

foreach ($pythonCommands as $cmd) {
    echo "Testing: {$cmd}\n";
    $output = shell_exec("{$cmd} --version 2>&1");
    if ($output && strpos($output, 'Python') !== false) {
        echo "✅ Found: {$output}\n";
        echo "Full path: " . realpath($cmd) . "\n";
    } else {
        echo "❌ Not found or error: " . ($output ?: 'No output') . "\n";
    }
    echo "\n";
}

// Test the actual script
echo "=== Testing Python Script ===\n";
$scriptPath = __DIR__ . '/python_document_extractors/pdf_extractor.py';
$testFile = 'test files/test.pdf';

echo "Script path: {$scriptPath}\n";
echo "Test file: {$testFile}\n";

if (file_exists($scriptPath)) {
    echo "✅ Script exists\n";
} else {
    echo "❌ Script not found\n";
}

if (file_exists($testFile)) {
    echo "✅ Test file exists\n";
} else {
    echo "❌ Test file not found\n";
}

// Try to run the script directly
$cmd = "python \"{$scriptPath}\" \"{$testFile}\"";
echo "Command: {$cmd}\n";
$output = shell_exec($cmd . ' 2>&1');
echo "Output: " . ($output ?: 'No output') . "\n";
?>
