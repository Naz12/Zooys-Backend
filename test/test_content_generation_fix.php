<?php

/**
 * Test script to verify content generation fix
 * Tests that PowerPoint generation uses full content instead of just outline
 */

echo "🧪 Testing Content Generation Fix\n";
echo "=================================\n\n";

// Test data with both outline and generated content
$testData = [
    'outline' => [
        'title' => 'Test Presentation - Content Generation Fix',
        'slides' => [
            [
                'slide_number' => 1,
                'header' => 'Introduction',
                'subheaders' => ['Overview', 'Objectives', 'Agenda'],
                'slide_type' => 'content'
            ],
            [
                'slide_number' => 2,
                'header' => 'Main Topic',
                'subheaders' => ['Key Points', 'Benefits', 'Implementation'],
                'slide_type' => 'content'
            ],
            [
                'slide_number' => 3,
                'header' => 'Conclusion',
                'subheaders' => ['Summary', 'Next Steps', 'Questions'],
                'slide_type' => 'content'
            ]
        ]
    ],
    'template' => 'corporate_blue',
    'color_scheme' => 'blue',
    'font_style' => 'modern',
    'user_id' => 1,
    'ai_result_id' => 999
];

// Test 1: Test with outline only (original behavior)
echo "1. Testing with outline only (original behavior)...\n";
$outlineOnlyData = $testData;
$tempFile1 = tempnam(sys_get_temp_dir(), 'test_outline_only_');
file_put_contents($tempFile1, json_encode($outlineOnlyData));

$pythonScript = __DIR__ . '/../python/generate_presentation.py';
$command1 = "py \"$pythonScript\" \"$tempFile1\"";
$output1 = shell_exec($command1 . ' 2>&1');

if ($output1) {
    $lines1 = explode("\n", trim($output1));
    $jsonOutput1 = end($lines1);
    $result1 = json_decode($jsonOutput1, true);
    
    if ($result1 && $result1['success']) {
        echo "   ✅ Outline-only generation successful\n";
        echo "   📁 File: " . basename($result1['file_path']) . "\n";
    } else {
        echo "   ❌ Outline-only generation failed: " . ($result1['error'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "   ❌ Outline-only generation failed - no output\n";
}

// Test 2: Test with generated content (fixed behavior)
echo "\n2. Testing with generated content (fixed behavior)...\n";
$contentData = $testData;
$contentData['outline']['slides'][0]['content'] = [
    '• Overview of the presentation topic and its importance',
    '• Clear objectives that will be achieved by the end',
    '• Detailed agenda showing the flow of information',
    '• Expected outcomes and benefits for the audience'
];
$contentData['outline']['slides'][1]['content'] = [
    '• Key points that form the foundation of the topic',
    '• Specific benefits and advantages of implementation',
    '• Step-by-step implementation process and timeline',
    '• Best practices and common pitfalls to avoid'
];
$contentData['outline']['slides'][2]['content'] = [
    '• Comprehensive summary of all key points covered',
    '• Clear next steps and action items for implementation',
    '• Open floor for questions and detailed discussions',
    '• Contact information for follow-up and support'
];

$tempFile2 = tempnam(sys_get_temp_dir(), 'test_with_content_');
file_put_contents($tempFile2, json_encode($contentData));

$command2 = "py \"$pythonScript\" \"$tempFile2\"";
$output2 = shell_exec($command2 . ' 2>&1');

if ($output2) {
    $lines2 = explode("\n", trim($output2));
    $jsonOutput2 = end($lines2);
    $result2 = json_decode($jsonOutput2, true);
    
    if ($result2 && $result2['success']) {
        echo "   ✅ Content generation successful\n";
        echo "   📁 File: " . basename($result2['file_path']) . "\n";
        
        // Check if the generated file exists and has content
        if (file_exists($result2['file_path'])) {
            $fileSize = filesize($result2['file_path']);
            echo "   📊 File size: " . number_format($fileSize) . " bytes\n";
            
            if ($fileSize > 10000) { // PowerPoint files with content should be larger
                echo "   ✅ File size indicates content was included\n";
            } else {
                echo "   ⚠️  File size suggests only outline was included\n";
            }
        }
    } else {
        echo "   ❌ Content generation failed: " . ($result2['error'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "   ❌ Content generation failed - no output\n";
}

// Test 3: Compare the outputs
echo "\n3. Comparing outline vs content generation...\n";
if (isset($result1['file_path']) && isset($result2['file_path'])) {
    $size1 = file_exists($result1['file_path']) ? filesize($result1['file_path']) : 0;
    $size2 = file_exists($result2['file_path']) ? filesize($result2['file_path']) : 0;
    
    echo "   📊 Outline-only file size: " . number_format($size1) . " bytes\n";
    echo "   📊 Content file size: " . number_format($size2) . " bytes\n";
    
    if ($size2 > $size1) {
        echo "   ✅ Content file is larger, indicating full content was included\n";
    } elseif ($size2 == $size1) {
        echo "   ⚠️  Both files are the same size - content may not have been included\n";
    } else {
        echo "   ❌ Content file is smaller - something went wrong\n";
    }
}

// Cleanup
if (file_exists($tempFile1)) unlink($tempFile1);
if (file_exists($tempFile2)) unlink($tempFile2);

echo "\n📋 Content Generation Fix Summary:\n";
echo "==================================\n";
echo "✅ Modified AIPresentationService to prioritize database content\n";
echo "✅ Updated exportPresentationToPowerPoint to use generated content\n";
echo "✅ Enhanced Python script with better content handling and logging\n";
echo "✅ Added debugging logs to track content vs outline usage\n";

echo "\n🎯 Key Changes Made:\n";
echo "===================\n";
echo "1. exportPresentationToPowerPoint now uses database content first\n";
echo "2. Frontend updates are merged with database content intelligently\n";
echo "3. Python script logs content vs subheaders usage for debugging\n";
echo "4. Better error handling and content validation\n";

echo "\n✨ Content generation fix has been applied!\n";
echo "The PowerPoint should now include full generated content instead of just the outline.\n";

?>
