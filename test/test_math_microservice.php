<?php

/**
 * Test file for Math Microservice Integration
 * 
 * This file tests the integration between Laravel and the Python Math Microservice
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Http;

class MathMicroserviceTest
{
    private $microserviceUrl;
    private $testResults = [];

    public function __construct()
    {
        $this->microserviceUrl = 'http://localhost:8002';
    }

    /**
     * Run all tests
     */
    public function runAllTests()
    {
        echo "🧮 Math Microservice Integration Tests\n";
        echo "=====================================\n\n";

        $this->testHealthCheck();
        $this->testSolversEndpoint();
        $this->testSolveEndpoint();
        $this->testExplainEndpoint();
        $this->testLatexEndpoint();
        $this->testErrorHandling();

        $this->displayResults();
    }

    /**
     * Test health check endpoint
     */
    private function testHealthCheck()
    {
        echo "1. Testing Health Check...\n";
        
        try {
            $response = Http::timeout(5)->get($this->microserviceUrl . '/health');
            
            if ($response->successful()) {
                $data = $response->json();
                if ($data['status'] === 'healthy') {
                    $this->testResults['health_check'] = '✅ PASS';
                    echo "   ✅ Health check passed\n";
                } else {
                    $this->testResults['health_check'] = '❌ FAIL - Status not healthy';
                    echo "   ❌ Health check failed - Status: {$data['status']}\n";
                }
            } else {
                $this->testResults['health_check'] = '❌ FAIL - HTTP ' . $response->status();
                echo "   ❌ Health check failed - HTTP {$response->status()}\n";
            }
        } catch (Exception $e) {
            $this->testResults['health_check'] = '❌ FAIL - ' . $e->getMessage();
            echo "   ❌ Health check failed - {$e->getMessage()}\n";
        }
        
        echo "\n";
    }

    /**
     * Test solvers endpoint
     */
    private function testSolversEndpoint()
    {
        echo "2. Testing Solvers Endpoint...\n";
        
        try {
            $response = Http::timeout(5)->get($this->microserviceUrl . '/solvers');
            
            if ($response->successful()) {
                $data = $response->json();
                $solvers = $data['available_solvers'] ?? [];
                
                if (count($solvers) >= 5) {
                    $this->testResults['solvers'] = '✅ PASS';
                    echo "   ✅ Solvers endpoint passed - Found " . count($solvers) . " solvers\n";
                    echo "   📋 Available solvers: " . implode(', ', $solvers) . "\n";
                } else {
                    $this->testResults['solvers'] = '❌ FAIL - Insufficient solvers';
                    echo "   ❌ Solvers endpoint failed - Only " . count($solvers) . " solvers found\n";
                }
            } else {
                $this->testResults['solvers'] = '❌ FAIL - HTTP ' . $response->status();
                echo "   ❌ Solvers endpoint failed - HTTP {$response->status()}\n";
            }
        } catch (Exception $e) {
            $this->testResults['solvers'] = '❌ FAIL - ' . $e->getMessage();
            echo "   ❌ Solvers endpoint failed - {$e->getMessage()}\n";
        }
        
        echo "\n";
    }

    /**
     * Test solve endpoint
     */
    private function testSolveEndpoint()
    {
        echo "3. Testing Solve Endpoint...\n";
        
        $testProblems = [
            'algebra' => 'Solve for x: 2x + 5 = 13',
            'arithmetic' => 'What is 15 + 27?',
            'geometry' => 'Find the area of a circle with radius 5',
            'statistics' => 'Find the mean of: 1, 2, 3, 4, 5'
        ];

        $passed = 0;
        $total = count($testProblems);

        foreach ($testProblems as $type => $problem) {
            try {
                $response = Http::timeout(30)->post($this->microserviceUrl . '/solve', [
                    'problem_text' => $problem,
                    'subject_area' => $type,
                    'difficulty_level' => 'intermediate'
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if ($data['success'] && !empty($data['solution']['answer'])) {
                        $passed++;
                        echo "   ✅ {$type}: {$data['solution']['answer']}\n";
                    } else {
                        echo "   ❌ {$type}: No solution provided\n";
                    }
                } else {
                    echo "   ❌ {$type}: HTTP {$response->status()}\n";
                }
            } catch (Exception $e) {
                echo "   ❌ {$type}: {$e->getMessage()}\n";
            }
        }

        if ($passed === $total) {
            $this->testResults['solve'] = '✅ PASS';
            echo "   ✅ Solve endpoint passed - {$passed}/{$total} problems solved\n";
        } else {
            $this->testResults['solve'] = "❌ FAIL - {$passed}/{$total} problems solved";
            echo "   ❌ Solve endpoint failed - {$passed}/{$total} problems solved\n";
        }
        
        echo "\n";
    }

    /**
     * Test explain endpoint
     */
    private function testExplainEndpoint()
    {
        echo "4. Testing Explain Endpoint...\n";
        
        try {
            $response = Http::timeout(60)->post($this->microserviceUrl . '/explain', [
                'problem_text' => 'Solve for x: 3x - 7 = 14',
                'subject_area' => 'algebra',
                'difficulty_level' => 'intermediate',
                'include_explanation' => true
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['success'] && !empty($data['explanation']['content'])) {
                    $this->testResults['explain'] = '✅ PASS';
                    echo "   ✅ Explain endpoint passed\n";
                    echo "   📝 Explanation length: " . strlen($data['explanation']['content']) . " characters\n";
                } else {
                    $this->testResults['explain'] = '❌ FAIL - No explanation provided';
                    echo "   ❌ Explain endpoint failed - No explanation provided\n";
                }
            } else {
                $this->testResults['explain'] = '❌ FAIL - HTTP ' . $response->status();
                echo "   ❌ Explain endpoint failed - HTTP {$response->status()}\n";
            }
        } catch (Exception $e) {
            $this->testResults['explain'] = '❌ FAIL - ' . $e->getMessage();
            echo "   ❌ Explain endpoint failed - {$e->getMessage()}\n";
        }
        
        echo "\n";
    }

    /**
     * Test LaTeX endpoint
     */
    private function testLatexEndpoint()
    {
        echo "5. Testing LaTeX Endpoint...\n";
        
        try {
            $response = Http::timeout(10)->post($this->microserviceUrl . '/latex', [
                'problem_text' => 'x^2 + 2x + 1 = 0',
                'solution' => 'x = -1'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['success'] && !empty($data['latex']['input'])) {
                    $this->testResults['latex'] = '✅ PASS';
                    echo "   ✅ LaTeX endpoint passed\n";
                    echo "   📄 LaTeX input: {$data['latex']['input']}\n";
                } else {
                    $this->testResults['latex'] = '❌ FAIL - No LaTeX output';
                    echo "   ❌ LaTeX endpoint failed - No LaTeX output\n";
                }
            } else {
                $this->testResults['latex'] = '❌ FAIL - HTTP ' . $response->status();
                echo "   ❌ LaTeX endpoint failed - HTTP {$response->status()}\n";
            }
        } catch (Exception $e) {
            $this->testResults['latex'] = '❌ FAIL - ' . $e->getMessage();
            echo "   ❌ LaTeX endpoint failed - {$e->getMessage()}\n";
        }
        
        echo "\n";
    }

    /**
     * Test error handling
     */
    private function testErrorHandling()
    {
        echo "6. Testing Error Handling...\n";
        
        try {
            // Test with invalid problem
            $response = Http::timeout(10)->post($this->microserviceUrl . '/solve', [
                'problem_text' => '', // Empty problem
                'subject_area' => 'invalid'
            ]);

            if ($response->status() === 400 || $response->status() === 422) {
                $this->testResults['error_handling'] = '✅ PASS';
                echo "   ✅ Error handling passed - Properly rejected invalid input\n";
            } else {
                $this->testResults['error_handling'] = '❌ FAIL - Should reject invalid input';
                echo "   ❌ Error handling failed - Should reject invalid input\n";
            }
        } catch (Exception $e) {
            $this->testResults['error_handling'] = '❌ FAIL - ' . $e->getMessage();
            echo "   ❌ Error handling failed - {$e->getMessage()}\n";
        }
        
        echo "\n";
    }

    /**
     * Display test results summary
     */
    private function displayResults()
    {
        echo "📊 Test Results Summary\n";
        echo "======================\n";
        
        $passed = 0;
        $total = count($this->testResults);
        
        foreach ($this->testResults as $test => $result) {
            echo "{$test}: {$result}\n";
            if (strpos($result, '✅') === 0) {
                $passed++;
            }
        }
        
        echo "\n";
        echo "Overall: {$passed}/{$total} tests passed\n";
        
        if ($passed === $total) {
            echo "🎉 All tests passed! Math microservice is working correctly.\n";
        } else {
            echo "⚠️  Some tests failed. Please check the microservice configuration.\n";
        }
        
        echo "\n";
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $tester = new MathMicroserviceTest();
    $tester->runAllTests();
}







