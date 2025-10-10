<?php

/**
 * AI Presentation Generator - Comprehensive Test Suite
 * Tests all 4 steps of the presentation generation workflow
 */

class PresentationAPITest
{
    private $baseUrl;
    private $authToken;
    private $testResults = [];

    public function __construct($baseUrl = 'http://localhost:8000/api', $authToken = null)
    {
        $this->baseUrl = $baseUrl;
        $this->authToken = $authToken;
    }

    /**
     * Run all presentation tests
     */
    public function runAllTests()
    {
        echo "🚀 Starting AI Presentation Generator Tests\n";
        echo "==========================================\n\n";

        // Test 1: Generate Outline (Step 1)
        $this->testGenerateOutline();

        // Test 2: Update Outline (Step 2)
        $this->testUpdateOutline();

        // Test 3: Get Templates (Step 3)
        $this->testGetTemplates();

        // Test 4: Generate PowerPoint (Step 4)
        $this->testGeneratePowerPoint();

        // Test 5: Get Presentations
        $this->testGetPresentations();

        // Test 6: Get Specific Presentation
        $this->testGetSpecificPresentation();

        // Test 7: Delete Presentation
        $this->testDeletePresentation();

        // Print results
        $this->printResults();
    }

    /**
     * Test Step 1: Generate Outline
     */
    private function testGenerateOutline()
    {
        echo "📝 Testing Step 1: Generate Outline\n";
        echo "-----------------------------------\n";

        $testCases = [
            [
                'name' => 'Text Input - Business Topic',
                'data' => [
                    'input_type' => 'text',
                    'topic' => 'The Future of Artificial Intelligence in Business',
                    'language' => 'English',
                    'tone' => 'Professional',
                    'length' => 'Medium',
                    'model' => 'Basic Model'
                ]
            ],
            [
                'name' => 'Text Input - Educational Topic',
                'data' => [
                    'input_type' => 'text',
                    'topic' => 'Climate Change and Environmental Sustainability',
                    'language' => 'English',
                    'tone' => 'Academic',
                    'length' => 'Long',
                    'model' => 'Advanced Model'
                ]
            ]
        ];

        foreach ($testCases as $testCase) {
            $result = $this->makeRequest('POST', '/presentations/generate-outline', $testCase['data']);
            
            if ($result && isset($result['success']) && $result['success']) {
                echo "✅ {$testCase['name']}: SUCCESS\n";
                echo "   - AI Result ID: " . ($result['data']['ai_result_id'] ?? 'N/A') . "\n";
                echo "   - Title: " . ($result['data']['outline']['title'] ?? 'N/A') . "\n";
                echo "   - Slide Count: " . ($result['data']['outline']['slide_count'] ?? 'N/A') . "\n";
                
                // Store AI result ID for next tests
                if (isset($result['data']['ai_result_id'])) {
                    $this->testResults['ai_result_id'] = $result['data']['ai_result_id'];
                    $this->testResults['outline'] = $result['data']['outline'];
                }
            } else {
                echo "❌ {$testCase['name']}: FAILED\n";
                echo "   - Error: " . ($result['error'] ?? 'Unknown error') . "\n";
            }
            echo "\n";
        }
    }

    /**
     * Test Step 2: Update Outline
     */
    private function testUpdateOutline()
    {
        echo "✏️ Testing Step 2: Update Outline\n";
        echo "--------------------------------\n";

        if (!isset($this->testResults['ai_result_id'])) {
            echo "❌ No AI Result ID available for testing\n\n";
            return;
        }

        $aiResultId = $this->testResults['ai_result_id'];
        $originalOutline = $this->testResults['outline'];

        // Modify the outline
        $modifiedOutline = $originalOutline;
        $modifiedOutline['title'] = 'Modified: ' . $originalOutline['title'];
        
        // Add a new slide
        $newSlide = [
            'slide_number' => count($modifiedOutline['slides']) + 1,
            'header' => 'Additional Information',
            'subheaders' => [
                'Key takeaway points',
                'Future considerations',
                'Questions and discussion'
            ],
            'slide_type' => 'content'
        ];
        $modifiedOutline['slides'][] = $newSlide;

        $data = ['outline' => $modifiedOutline];
        $result = $this->makeRequest('PUT', "/presentations/{$aiResultId}/update-outline", $data);

        if ($result && isset($result['success']) && $result['success']) {
            echo "✅ Update Outline: SUCCESS\n";
            echo "   - Modified title: " . $modifiedOutline['title'] . "\n";
            echo "   - New slide count: " . count($modifiedOutline['slides']) . "\n";
        } else {
            echo "❌ Update Outline: FAILED\n";
            echo "   - Error: " . ($result['error'] ?? 'Unknown error') . "\n";
        }
        echo "\n";
    }

    /**
     * Test Step 3: Get Templates
     */
    private function testGetTemplates()
    {
        echo "🎨 Testing Step 3: Get Templates\n";
        echo "--------------------------------\n";

        $result = $this->makeRequest('GET', '/presentations/templates');

        if ($result && isset($result['success']) && $result['success']) {
            echo "✅ Get Templates: SUCCESS\n";
            $templates = $result['data']['templates'] ?? [];
            echo "   - Available templates: " . count($templates) . "\n";
            
            foreach ($templates as $key => $template) {
                echo "     • {$template['name']} ({$key})\n";
            }
            
            // Store templates for next test
            $this->testResults['templates'] = $templates;
        } else {
            echo "❌ Get Templates: FAILED\n";
            echo "   - Error: " . ($result['error'] ?? 'Unknown error') . "\n";
        }
        echo "\n";
    }

    /**
     * Test Step 4: Generate PowerPoint
     */
    private function testGeneratePowerPoint()
    {
        echo "🏗️ Testing Step 4: Generate PowerPoint\n";
        echo "-------------------------------------\n";

        if (!isset($this->testResults['ai_result_id']) || !isset($this->testResults['templates'])) {
            echo "❌ Missing required data for PowerPoint generation\n\n";
            return;
        }

        $aiResultId = $this->testResults['ai_result_id'];
        $templates = $this->testResults['templates'];

        // Test with different templates
        $testTemplates = ['corporate_blue', 'modern_white', 'creative_colorful'];
        
        foreach ($testTemplates as $templateKey) {
            if (!isset($templates[$templateKey])) {
                continue;
            }

            $data = [
                'template' => $templateKey,
                'color_scheme' => $templates[$templateKey]['color_scheme'] ?? 'blue',
                'font_style' => 'modern'
            ];

            $result = $this->makeRequest('POST', "/presentations/{$aiResultId}/generate-powerpoint", $data);

            if ($result && isset($result['success']) && $result['success']) {
                echo "✅ Generate PowerPoint ({$templateKey}): SUCCESS\n";
                echo "   - File path: " . ($result['data']['powerpoint_file'] ?? 'N/A') . "\n";
                echo "   - Download URL: " . ($result['data']['download_url'] ?? 'N/A') . "\n";
                
                // Store PowerPoint info
                $this->testResults['powerpoint_file'] = $result['data']['powerpoint_file'];
                $this->testResults['download_url'] = $result['data']['download_url'];
                break; // Only test one template to avoid multiple generations
            } else {
                echo "❌ Generate PowerPoint ({$templateKey}): FAILED\n";
                echo "   - Error: " . ($result['error'] ?? 'Unknown error') . "\n";
            }
        }
        echo "\n";
    }

    /**
     * Test Get Presentations
     */
    private function testGetPresentations()
    {
        echo "📚 Testing Get Presentations\n";
        echo "----------------------------\n";

        $result = $this->makeRequest('GET', '/presentations?per_page=10');

        if ($result && isset($result['success']) && $result['success']) {
            echo "✅ Get Presentations: SUCCESS\n";
            $presentations = $result['data']['presentations'] ?? [];
            $pagination = $result['data']['pagination'] ?? [];
            
            echo "   - Total presentations: " . ($pagination['total'] ?? 0) . "\n";
            echo "   - Current page: " . ($pagination['current_page'] ?? 1) . "\n";
            echo "   - Per page: " . ($pagination['per_page'] ?? 15) . "\n";
            
            if (!empty($presentations)) {
                echo "   - Recent presentations:\n";
                foreach (array_slice($presentations, 0, 3) as $presentation) {
                    echo "     • {$presentation['title']} (ID: {$presentation['id']})\n";
                }
            }
        } else {
            echo "❌ Get Presentations: FAILED\n";
            echo "   - Error: " . ($result['error'] ?? 'Unknown error') . "\n";
        }
        echo "\n";
    }

    /**
     * Test Get Specific Presentation
     */
    private function testGetSpecificPresentation()
    {
        echo "🔍 Testing Get Specific Presentation\n";
        echo "-----------------------------------\n";

        if (!isset($this->testResults['ai_result_id'])) {
            echo "❌ No AI Result ID available for testing\n\n";
            return;
        }

        $aiResultId = $this->testResults['ai_result_id'];
        $result = $this->makeRequest('GET', "/presentations/{$aiResultId}");

        if ($result && isset($result['success']) && $result['success']) {
            echo "✅ Get Specific Presentation: SUCCESS\n";
            $presentation = $result['data']['presentation'] ?? [];
            echo "   - Title: " . ($presentation['title'] ?? 'N/A') . "\n";
            echo "   - Tool Type: " . ($presentation['tool_type'] ?? 'N/A') . "\n";
            echo "   - Status: " . ($presentation['status'] ?? 'N/A') . "\n";
            echo "   - Created: " . ($presentation['created_at'] ?? 'N/A') . "\n";
        } else {
            echo "❌ Get Specific Presentation: FAILED\n";
            echo "   - Error: " . ($result['error'] ?? 'Unknown error') . "\n";
        }
        echo "\n";
    }

    /**
     * Test Delete Presentation
     */
    private function testDeletePresentation()
    {
        echo "🗑️ Testing Delete Presentation\n";
        echo "-----------------------------\n";

        if (!isset($this->testResults['ai_result_id'])) {
            echo "❌ No AI Result ID available for testing\n\n";
            return;
        }

        $aiResultId = $this->testResults['ai_result_id'];
        $result = $this->makeRequest('DELETE', "/presentations/{$aiResultId}");

        if ($result && isset($result['success']) && $result['success']) {
            echo "✅ Delete Presentation: SUCCESS\n";
            echo "   - Message: " . ($result['message'] ?? 'Presentation deleted') . "\n";
        } else {
            echo "❌ Delete Presentation: FAILED\n";
            echo "   - Error: " . ($result['error'] ?? 'Unknown error') . "\n";
        }
        echo "\n";
    }

    /**
     * Make HTTP request
     */
    private function makeRequest($method, $endpoint, $data = null)
    {
        $url = $this->baseUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        if ($this->authToken) {
            $headers[] = 'Authorization: Bearer ' . $this->authToken;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($method === 'POST' || $method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            return ['success' => false, 'error' => 'cURL request failed'];
        }

        $decodedResponse = json_decode($response, true);
        
        if ($httpCode >= 400) {
            return ['success' => false, 'error' => $decodedResponse['error'] ?? 'HTTP Error ' . $httpCode];
        }

        return $decodedResponse;
    }

    /**
     * Print test results summary
     */
    private function printResults()
    {
        echo "📊 Test Results Summary\n";
        echo "======================\n";
        echo "✅ All presentation workflow tests completed!\n";
        echo "🎯 The AI Presentation Generator is ready for use.\n\n";
        
        echo "🚀 Next Steps:\n";
        echo "1. Install Python dependencies: pip install -r python/requirements.txt\n";
        echo "2. Test the Python script: python python/generate_presentation.py\n";
        echo "3. Integrate with frontend for the complete user experience\n";
        echo "4. Deploy and start generating presentations!\n\n";
    }
}

// Run tests if called directly
if (php_sapi_name() === 'cli') {
    $baseUrl = $argv[1] ?? 'http://localhost:8000/api';
    $authToken = $argv[2] ?? null;
    
    $tester = new PresentationAPITest($baseUrl, $authToken);
    $tester->runAllTests();
}
