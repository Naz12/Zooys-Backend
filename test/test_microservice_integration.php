<?php

/**
 * Test file for Microservice Integration
 * 
 * This file tests the integration between Laravel and the enhanced Python microservices
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Http;

class MicroserviceIntegrationTest
{
    private $presentationMicroserviceUrl;
    private $mathMicroserviceUrl;
    private $testResults = [];

    public function __construct()
    {
        $this->presentationMicroserviceUrl = 'http://localhost:8001';
        $this->mathMicroserviceUrl = 'http://localhost:8002';
    }

    /**
     * Run all tests
     */
    public function runAllTests()
    {
        echo "🔧 Microservice Integration Tests\n";
        echo "=================================\n\n";

        $this->testPresentationMicroserviceHealth();
        $this->testMathMicroserviceHealth();
        $this->testPresentationOutlineGeneration();
        $this->testPresentationContentGeneration();
        $this->testMathProblemSolving();

        $this->displayResults();
    }

    /**
     * Test presentation microservice health
     */
    private function testPresentationMicroserviceHealth()
    {
        echo "Testing Presentation Microservice Health...\n";
        
        try {
            $response = Http::timeout(10)->get($this->presentationMicroserviceUrl . '/health');
            
            if ($response->successful()) {
                $data = $response->json();
                $this->testResults['presentation_health'] = [
                    'status' => 'PASS',
                    'message' => 'Presentation microservice is healthy',
                    'services' => $data['services'] ?? []
                ];
                echo "✅ PASS: Presentation microservice is healthy\n";
            } else {
                $this->testResults['presentation_health'] = [
                    'status' => 'FAIL',
                    'message' => 'Health check failed: ' . $response->status()
                ];
                echo "❌ FAIL: Health check failed\n";
            }
        } catch (\Exception $e) {
            $this->testResults['presentation_health'] = [
                'status' => 'FAIL',
                'message' => 'Connection failed: ' . $e->getMessage()
            ];
            echo "❌ FAIL: Connection failed\n";
        }
        
        echo "\n";
    }

    /**
     * Test math microservice health
     */
    private function testMathMicroserviceHealth()
    {
        echo "Testing Math Microservice Health...\n";
        
        try {
            $response = Http::timeout(10)->get($this->mathMicroserviceUrl . '/health');
            
            if ($response->successful()) {
                $data = $response->json();
                $this->testResults['math_health'] = [
                    'status' => 'PASS',
                    'message' => 'Math microservice is healthy',
                    'services' => $data['services'] ?? []
                ];
                echo "✅ PASS: Math microservice is healthy\n";
            } else {
                $this->testResults['math_health'] = [
                    'status' => 'FAIL',
                    'message' => 'Health check failed: ' . $response->status()
                ];
                echo "❌ FAIL: Health check failed\n";
            }
        } catch (\Exception $e) {
            $this->testResults['math_health'] = [
                'status' => 'FAIL',
                'message' => 'Connection failed: ' . $e->getMessage()
            ];
            echo "❌ FAIL: Connection failed\n";
        }
        
        echo "\n";
    }

    /**
     * Test presentation outline generation
     */
    private function testPresentationOutlineGeneration()
    {
        echo "Testing Presentation Outline Generation...\n";
        
        try {
            $requestData = [
                'content' => 'Artificial Intelligence and Machine Learning in Modern Business',
        'language' => 'English',
        'tone' => 'Professional',
                'length' => 'Medium'
            ];

            $response = Http::timeout(30)->post(
                $this->presentationMicroserviceUrl . '/generate-outline',
                $requestData
            );
            
            if ($response->successful()) {
                $data = $response->json();
                if ($data['success'] && isset($data['data']['title']) && isset($data['data']['slides'])) {
                    $this->testResults['presentation_outline'] = [
                        'status' => 'PASS',
                        'message' => 'Outline generated successfully',
                        'title' => $data['data']['title'],
                        'slide_count' => count($data['data']['slides'])
                    ];
                    echo "✅ PASS: Outline generated successfully\n";
                    echo "   Title: " . $data['data']['title'] . "\n";
                    echo "   Slides: " . count($data['data']['slides']) . "\n";
                } else {
                    $this->testResults['presentation_outline'] = [
                        'status' => 'FAIL',
                        'message' => 'Invalid response format'
                    ];
                    echo "❌ FAIL: Invalid response format\n";
                }
            } else {
                $this->testResults['presentation_outline'] = [
                    'status' => 'FAIL',
                    'message' => 'Request failed: ' . $response->status()
                ];
                echo "❌ FAIL: Request failed\n";
            }
        } catch (\Exception $e) {
            $this->testResults['presentation_outline'] = [
                'status' => 'FAIL',
                'message' => 'Exception: ' . $e->getMessage()
            ];
            echo "❌ FAIL: Exception occurred\n";
        }
        
        echo "\n";
    }

    /**
     * Test presentation content generation
     */
    private function testPresentationContentGeneration()
    {
        echo "Testing Presentation Content Generation...\n";
        
        try {
            $outline = [
                'title' => 'Test Presentation',
                'slides' => [
                    [
                        'slide_number' => 1,
                        'header' => 'Introduction',
                        'subheaders' => ['Welcome', 'Overview'],
                        'slide_type' => 'title'
                    ],
                    [
                        'slide_number' => 2,
                        'header' => 'Main Topic',
                        'subheaders' => ['Key Point 1', 'Key Point 2'],
                        'slide_type' => 'content'
                    ]
                ]
            ];

            $requestData = [
                'outline' => $outline,
                'language' => 'English',
                'tone' => 'Professional',
                'detail_level' => 'detailed'
            ];

            $response = Http::timeout(60)->post(
                $this->presentationMicroserviceUrl . '/generate-content',
                $requestData
            );
            
            if ($response->successful()) {
                $data = $response->json();
                if ($data['success'] && isset($data['data']['slides'])) {
                    $hasContent = false;
                    foreach ($data['data']['slides'] as $slide) {
                        if (isset($slide['content']) && !empty($slide['content'])) {
                            $hasContent = true;
                            break;
                        }
                    }
                    
                    if ($hasContent) {
                        $this->testResults['presentation_content'] = [
                            'status' => 'PASS',
                            'message' => 'Content generated successfully'
                        ];
                        echo "✅ PASS: Content generated successfully\n";
                    } else {
                        $this->testResults['presentation_content'] = [
                            'status' => 'FAIL',
                            'message' => 'No content generated for slides'
                        ];
                        echo "❌ FAIL: No content generated\n";
                    }
                } else {
                    $this->testResults['presentation_content'] = [
                        'status' => 'FAIL',
                        'message' => 'Invalid response format'
                    ];
                    echo "❌ FAIL: Invalid response format\n";
                }
            } else {
                $this->testResults['presentation_content'] = [
                    'status' => 'FAIL',
                    'message' => 'Request failed: ' . $response->status()
                ];
                echo "❌ FAIL: Request failed\n";
            }
        } catch (\Exception $e) {
            $this->testResults['presentation_content'] = [
                'status' => 'FAIL',
                'message' => 'Exception: ' . $e->getMessage()
            ];
            echo "❌ FAIL: Exception occurred\n";
        }
        
        echo "\n";
    }

    /**
     * Test math problem solving
     */
    private function testMathProblemSolving()
    {
        echo "Testing Math Problem Solving...\n";
        
        try {
            $requestData = [
                'problem_text' => 'Solve for x: 2x + 5 = 13',
                'subject_area' => 'algebra',
                'difficulty_level' => 'intermediate',
                'include_explanation' => true
            ];

            $response = Http::timeout(30)->post(
                $this->mathMicroserviceUrl . '/explain',
                $requestData
            );
            
            if ($response->successful()) {
                $data = $response->json();
                if ($data['success'] && isset($data['solution']['answer'])) {
                    $this->testResults['math_solving'] = [
                        'status' => 'PASS',
                        'message' => 'Math problem solved successfully',
                        'answer' => $data['solution']['answer']
                    ];
                    echo "✅ PASS: Math problem solved successfully\n";
                    echo "   Answer: " . $data['solution']['answer'] . "\n";
                } else {
                    $this->testResults['math_solving'] = [
                        'status' => 'FAIL',
                        'message' => 'Invalid response format'
                    ];
                    echo "❌ FAIL: Invalid response format\n";
                }
            } else {
                $this->testResults['math_solving'] = [
                    'status' => 'FAIL',
                    'message' => 'Request failed: ' . $response->status()
                ];
                echo "❌ FAIL: Request failed\n";
            }
        } catch (\Exception $e) {
            $this->testResults['math_solving'] = [
                'status' => 'FAIL',
                'message' => 'Exception: ' . $e->getMessage()
            ];
            echo "❌ FAIL: Exception occurred\n";
        }
        
        echo "\n";
    }

    /**
     * Display test results summary
     */
    private function displayResults()
    {
        echo "📊 Test Results Summary\n";
        echo "======================\n\n";

        $passed = 0;
        $failed = 0;

        foreach ($this->testResults as $testName => $result) {
            $status = $result['status'];
            $message = $result['message'];
            
            if ($status === 'PASS') {
                echo "✅ {$testName}: {$message}\n";
                $passed++;
            } else {
                echo "❌ {$testName}: {$message}\n";
                $failed++;
            }
        }

        echo "\n";
        echo "Total Tests: " . ($passed + $failed) . "\n";
        echo "Passed: {$passed}\n";
        echo "Failed: {$failed}\n";
        echo "Success Rate: " . round(($passed / ($passed + $failed)) * 100, 1) . "%\n";

        if ($failed === 0) {
            echo "\n🎉 All tests passed! Microservices are working correctly.\n";
        } else {
            echo "\n⚠️  Some tests failed. Please check the microservice configurations.\n";
        }
    }
}

// Run tests if called directly
if (php_sapi_name() === 'cli') {
    $test = new MicroserviceIntegrationTest();
    $test->runAllTests();
}