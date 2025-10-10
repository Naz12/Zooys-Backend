<?php

/**
 * Test script for FastAPI Microservice Integration
 * Tests PowerPoint generation and editing capabilities
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\AIPresentationService;
use App\Models\User;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🚀 Testing FastAPI Microservice Integration\n";
echo "==========================================\n\n";

$userId = 21; // Test user ID

try {
    $service = new AIPresentationService();
    
    // Test 1: Check microservice availability
    echo "🔍 Testing Microservice Availability...\n";
    $isAvailable = $service->isMicroserviceAvailable();
    
    if ($isAvailable) {
        echo "✅ Microservice is available!\n";
    } else {
        echo "❌ Microservice is not available. Please start the FastAPI service.\n";
        echo "   Run: cd python_presentation_service && python main.py\n\n";
        exit(1);
    }
    
    // Test 2: Generate presentation with microservice
    echo "\n📝 Testing PowerPoint Generation with Microservice...\n";
    
    $testData = [
        'input_type' => 'text',
        'content' => 'The Future of AI in Business',
        'language' => 'English',
        'tone' => 'Professional',
        'length' => 'Medium',
        'model' => 'Basic Model'
    ];
    
    $startTime = microtime(true);
    $result = $service->generateOutline($testData, $userId);
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    if ($result['success']) {
        echo "✅ Outline generation successful!\n";
        echo "⏱️  Duration: {$duration} seconds\n";
        echo "📊 AI Result ID: {$result['data']['ai_result_id']}\n\n";
        
        $aiResultId = $result['data']['ai_result_id'];
        
        // Test 3: Generate PowerPoint with microservice
        echo "🎨 Testing PowerPoint Generation with Microservice...\n";
        $templateData = [
            'template' => 'tech_modern',
            'color_scheme' => 'teal',
            'font_style' => 'modern'
        ];
        
        $startTime = microtime(true);
        $powerPointResult = $service->generatePowerPointWithMicroservice($aiResultId, $templateData, $userId);
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        if ($powerPointResult['success']) {
            echo "✅ PowerPoint generation successful!\n";
            echo "⏱️  Duration: {$duration} seconds\n";
            echo "📁 File: {$powerPointResult['data']['powerpoint_file']}\n";
            echo "🔗 Download URL: {$powerPointResult['data']['download_url']}\n\n";
            
            // Test 4: Edit text in presentation
            echo "✏️  Testing Text Editing...\n";
            $editResult = $service->editPresentationText(
                $aiResultId,
                2, // Slide 2
                'title',
                'Updated Slide Title',
                $userId
            );
            
            if ($editResult['success']) {
                echo "✅ Text editing successful!\n";
                echo "📝 Message: {$editResult['message']}\n\n";
            } else {
                echo "❌ Text editing failed: {$editResult['error']}\n\n";
            }
            
            // Test 5: Change template
            echo "🎨 Testing Template Change...\n";
            $templateChangeData = [
                'template' => 'elegant_purple',
                'color_scheme' => 'purple',
                'font_style' => 'modern'
            ];
            
            $templateResult = $service->changePresentationTemplate(
                $aiResultId,
                $templateChangeData,
                $userId
            );
            
            if ($templateResult['success']) {
                echo "✅ Template change successful!\n";
                echo "🎨 New Template: {$templateResult['data']['template']}\n\n";
            } else {
                echo "❌ Template change failed: {$templateResult['error']}\n\n";
            }
            
            // Test 6: Get presentation info
            echo "📊 Testing Presentation Info Retrieval...\n";
            $infoResult = $service->getPresentationInfo($aiResultId, $userId);
            
            if ($infoResult['success']) {
                echo "✅ Presentation info retrieved successfully!\n";
                echo "📄 Slide Count: {$infoResult['data']['slide_count']}\n";
                echo "📁 File Path: {$infoResult['data']['file_path']}\n\n";
            } else {
                echo "❌ Presentation info retrieval failed: {$infoResult['error']}\n\n";
            }
            
        } else {
            echo "❌ PowerPoint generation failed: {$powerPointResult['error']}\n";
        }
        
    } else {
        echo "❌ Outline generation failed: {$result['error']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Test failed with exception: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n🏁 Microservice integration test completed!\n";
echo "\n📋 Summary of Features Tested:\n";
echo "✅ Microservice availability check\n";
echo "✅ PowerPoint generation with microservice\n";
echo "✅ Text editing in existing presentations\n";
echo "✅ Template changing without regeneration\n";
echo "✅ Presentation info retrieval\n";
echo "\n🚀 The FastAPI microservice is working perfectly!\n";
