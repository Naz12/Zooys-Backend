<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing Comprehensive Content Generation\n";
echo "==========================================\n\n";

// Test data with comprehensive content
$testData = [
    'presentation_data' => [
        'title' => 'Comprehensive Test Presentation',
        'slides' => [
            [
                'slide_number' => 1,
                'header' => 'Introduction to Comprehensive Testing',
                'subheaders' => ['Overview', 'Objectives', 'Agenda', 'Expected Outcomes'],
                'content' => [
                    '• Comprehensive overview of the testing methodology and its importance in software development',
                    '• Clear objectives that will be achieved by the end of this comprehensive testing session',
                    '• Detailed agenda showing the comprehensive flow of information and testing procedures',
                    '• Expected outcomes and comprehensive benefits for the audience and stakeholders',
                    '• Comprehensive timeline and milestones for the testing process'
                ],
                'slide_type' => 'content'
            ],
            [
                'slide_number' => 2,
                'header' => 'Comprehensive Testing Strategies',
                'subheaders' => ['Unit Testing', 'Integration Testing', 'System Testing', 'User Acceptance Testing'],
                'content' => [
                    '• Comprehensive unit testing strategies that form the foundation of quality assurance',
                    '• Specific integration testing approaches and comprehensive benefits of implementation',
                    '• Step-by-step system testing process and comprehensive timeline for execution',
                    '• Best practices and comprehensive guidelines for user acceptance testing',
                    '• Comprehensive risk assessment and mitigation strategies for testing phases',
                    '• Comprehensive documentation and reporting requirements for all testing activities'
                ],
                'slide_type' => 'content'
            ],
            [
                'slide_number' => 3,
                'header' => 'Comprehensive Implementation Plan',
                'subheaders' => ['Planning Phase', 'Execution Phase', 'Monitoring Phase', 'Evaluation Phase'],
                'content' => [
                    '• Comprehensive planning phase including resource allocation and timeline development',
                    '• Detailed execution phase with comprehensive monitoring and quality control measures',
                    '• Comprehensive monitoring phase with real-time tracking and performance metrics',
                    '• Thorough evaluation phase with comprehensive analysis and reporting mechanisms',
                    '• Comprehensive stakeholder communication and feedback collection processes',
                    '• Comprehensive risk management and contingency planning strategies'
                ],
                'slide_type' => 'content'
            ]
        ]
    ],
    'user_id' => 1,
    'ai_result_id' => 999,
    'template' => 'corporate_blue',
    'color_scheme' => 'blue',
    'font_style' => 'modern'
];

echo "1. Testing microservice with comprehensive content...\n";
echo "   Slides: " . count($testData['presentation_data']['slides']) . "\n";
echo "   Total content items: " . array_sum(array_map(function($slide) {
    return count($slide['content'] ?? []);
}, $testData['presentation_data']['slides'])) . "\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8001/export');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$startTime = time();
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$executionTime = time() - $startTime;
curl_close($ch);

echo "⏱️  Execution time: {$executionTime}s\n";
echo "📊 HTTP Code: $httpCode\n";

if ($httpCode === 200) {
    $result = json_decode($response, true);
    if ($result && $result['success']) {
        echo "✅ Microservice export successful\n";
        echo "📁 File: " . basename($result['data']['file_path']) . "\n";
        echo "📊 File size: " . number_format($result['data']['file_size']) . " bytes\n";
        
        // Check if file exists and has reasonable size
        if (file_exists($result['data']['file_path'])) {
            $actualSize = filesize($result['data']['file_path']);
            echo "📊 Actual file size: " . number_format($actualSize) . " bytes\n";
            
            if ($actualSize > 60000) { // PowerPoint files with comprehensive content should be larger
                echo "✅ File size indicates comprehensive content was included\n";
            } elseif ($actualSize > 40000) {
                echo "✅ File size indicates content was included\n";
            } else {
                echo "⚠️  File size suggests only outline was included\n";
            }
        } else {
            echo "⚠️  Generated file not found at expected location\n";
        }
    } else {
        echo "❌ Microservice export failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "❌ Microservice export failed with HTTP $httpCode\n";
    echo "Response: " . substr($response, 0, 200) . "...\n";
}

echo "\n📋 Analysis:\n";
echo "============\n";
echo "This test uses comprehensive content with 6+ items per slide to\n";
echo "verify that the microservice properly handles extensive content.\n";

?>
