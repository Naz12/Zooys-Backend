<?php
/**
 * Comprehensive Test Script for /api/summarize/async endpoint
 * Tests all 7 input types: text, web link, YouTube, PDF, audio, video, image
 */

require_once 'vendor/autoload.php';

class SummarizeAsyncTester
{
    private $baseUrl;
    private $authToken;
    private $testResults = [];

    public function __construct($baseUrl = 'http://localhost:8000')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Authenticate and get token
     */
    public function authenticate()
    {
        echo "🔐 Authenticating...\n";
        
        $response = $this->makeRequest('POST', '/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);

        if ($response['success'] && isset($response['data']['token'])) {
            $this->authToken = $response['data']['token'];
            echo "✅ Authentication successful\n";
            return true;
        }

        echo "❌ Authentication failed: " . ($response['error'] ?? 'Unknown error') . "\n";
        return false;
    }

    /**
     * Test 1: Text Input
     */
    public function testTextInput()
    {
        echo "\n📝 Testing Text Input...\n";
        
        $data = [
            'content_type' => 'text',
            'source' => [
                'data' => 'Artificial intelligence (AI) is intelligence demonstrated by machines, in contrast to the natural intelligence displayed by humans and animals. Leading AI textbooks define the field as the study of "intelligent agents": any device that perceives its environment and takes actions that maximize its chance of successfully achieving its goals.'
            ],
            'options' => [
                'summary_length' => 'medium',
                'style' => 'academic'
            ]
        ];

        return $this->testJobCreation('text', $data);
    }

    /**
     * Test 2: Web Link
     */
    public function testWebLink()
    {
        echo "\n🌐 Testing Web Link...\n";
        
        $data = [
            'content_type' => 'link',
            'source' => [
                'data' => 'https://en.wikipedia.org/wiki/Artificial_intelligence'
            ],
            'options' => [
                'summary_length' => 'medium'
            ]
        ];

        return $this->testJobCreation('web_link', $data);
    }

    /**
     * Test 3: YouTube Link
     */
    public function testYouTubeLink()
    {
        echo "\n📺 Testing YouTube Link...\n";
        
        $data = [
            'content_type' => 'link',
            'source' => [
                'data' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'
            ],
            'options' => [
                'summary_length' => 'medium'
            ]
        ];

        return $this->testJobCreation('youtube', $data);
    }

    /**
     * Test 4: PDF File
     */
    public function testPdfFile()
    {
        echo "\n📄 Testing PDF File...\n";
        
        // First upload a PDF file
        $fileId = $this->uploadTestFile('pdf', 'test_document.pdf');
        if (!$fileId) {
            return false;
        }

        $data = [
            'content_type' => 'pdf',
            'source' => [
                'data' => $fileId
            ],
            'options' => [
                'summary_length' => 'medium'
            ]
        ];

        return $this->testJobCreation('pdf', $data);
    }

    /**
     * Test 5: Audio File
     */
    public function testAudioFile()
    {
        echo "\n🎵 Testing Audio File...\n";
        
        $fileId = $this->uploadTestFile('audio', 'test_audio.mp3');
        if (!$fileId) {
            return false;
        }

        $data = [
            'content_type' => 'audio',
            'source' => [
                'data' => $fileId
            ],
            'options' => [
                'summary_length' => 'medium'
            ]
        ];

        return $this->testJobCreation('audio', $data);
    }

    /**
     * Test 6: Video File
     */
    public function testVideoFile()
    {
        echo "\n🎬 Testing Video File...\n";
        
        $fileId = $this->uploadTestFile('video', 'test_video.mp4');
        if (!$fileId) {
            return false;
        }

        $data = [
            'content_type' => 'video',
            'source' => [
                'data' => $fileId
            ],
            'options' => [
                'summary_length' => 'medium'
            ]
        ];

        return $this->testJobCreation('video', $data);
    }

    /**
     * Test 7: Image File
     */
    public function testImageFile()
    {
        echo "\n🖼️ Testing Image File...\n";
        
        $fileId = $this->uploadTestFile('image', 'test_image.jpg');
        if (!$fileId) {
            return false;
        }

        $data = [
            'content_type' => 'image',
            'source' => [
                'data' => $fileId
            ],
            'options' => [
                'summary_length' => 'medium'
            ]
        ];

        return $this->testJobCreation('image', $data);
    }

    /**
     * Upload test file
     */
    private function uploadTestFile($type, $filename)
    {
        echo "📤 Uploading {$type} file: {$filename}\n";
        
        // Create a dummy file for testing
        $filePath = sys_get_temp_dir() . '/' . $filename;
        file_put_contents($filePath, 'Test file content for ' . $type);
        
        $response = $this->makeRequest('POST', '/api/files/upload', [
            'file' => new CURLFile($filePath),
            'content_type' => $type
        ], true);

        if ($response['success'] && isset($response['data']['file_id'])) {
            echo "✅ File uploaded successfully: {$response['data']['file_id']}\n";
            return $response['data']['file_id'];
        }

        echo "❌ File upload failed: " . ($response['error'] ?? 'Unknown error') . "\n";
        return null;
    }

    /**
     * Test job creation and processing
     */
    private function testJobCreation($testType, $data)
    {
        echo "🚀 Creating job for {$testType}...\n";
        
        $response = $this->makeRequest('POST', '/api/summarize/async', $data);
        
        if (!$response['success'] || !isset($response['job_id'])) {
            echo "❌ Job creation failed: " . ($response['error'] ?? 'Unknown error') . "\n";
            $this->testResults[$testType] = ['status' => 'failed', 'error' => $response['error'] ?? 'Job creation failed'];
            return false;
        }

        $jobId = $response['job_id'];
        echo "✅ Job created: {$jobId}\n";

        // Poll job status
        return $this->pollJobStatus($testType, $jobId);
    }

    /**
     * Poll job status until completion
     */
    private function pollJobStatus($testType, $jobId)
    {
        echo "⏳ Polling job status...\n";
        
        $maxAttempts = 60; // 5 minutes max
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $response = $this->makeRequest('GET', "/api/summarize/status/{$jobId}");
            
            if (!$response['success']) {
                echo "❌ Status check failed: " . ($response['error'] ?? 'Unknown error') . "\n";
                $this->testResults[$testType] = ['status' => 'failed', 'error' => 'Status check failed'];
                return false;
            }

            $status = $response['data']['status'];
            echo "📊 Status: {$status} (attempt " . ($attempt + 1) . "/{$maxAttempts})\n";

            if ($status === 'completed') {
                return $this->getJobResult($testType, $jobId);
            } elseif ($status === 'failed') {
                echo "❌ Job failed\n";
                $this->testResults[$testType] = ['status' => 'failed', 'error' => 'Job processing failed'];
                return false;
            }

            sleep(5); // Wait 5 seconds before next check
            $attempt++;
        }

        echo "⏰ Job timed out after 5 minutes\n";
        $this->testResults[$testType] = ['status' => 'timeout', 'error' => 'Job timed out'];
        return false;
    }

    /**
     * Get job result
     */
    private function getJobResult($testType, $jobId)
    {
        echo "📋 Getting job result...\n";
        
        $response = $this->makeRequest('GET', "/api/summarize/result/{$jobId}");
        
        if (!$response['success']) {
            echo "❌ Result retrieval failed: " . ($response['error'] ?? 'Unknown error') . "\n";
            $this->testResults[$testType] = ['status' => 'failed', 'error' => 'Result retrieval failed'];
            return false;
        }

        $result = $response['data'];
        echo "✅ Job completed successfully!\n";
        
        // Validate result structure
        $validation = $this->validateResult($testType, $result);
        
        $this->testResults[$testType] = [
            'status' => $validation ? 'passed' : 'failed',
            'result' => $result,
            'validation' => $validation
        ];

        return $validation;
    }

    /**
     * Validate result structure
     */
    private function validateResult($testType, $result)
    {
        echo "🔍 Validating result structure...\n";
        
        $requiredFields = ['summary', 'metadata'];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (!isset($result[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            echo "❌ Missing required fields: " . implode(', ', $missingFields) . "\n";
            return false;
        }

        // Check for bundle data in YouTube results
        if ($testType === 'youtube' && !isset($result['bundle'])) {
            echo "⚠️ YouTube result missing bundle data\n";
        }

        echo "✅ Result validation passed\n";
        return true;
    }

    /**
     * Make HTTP request
     */
    private function makeRequest($method, $endpoint, $data = null, $isFileUpload = false)
    {
        $url = $this->baseUrl . $endpoint;
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json'
        ];

        if ($this->authToken) {
            $headers[] = 'Authorization: Bearer ' . $this->authToken;
        }

        if ($isFileUpload) {
            $headers = array_filter($headers, function($header) {
                return !str_starts_with($header, 'Content-Type');
            });
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($isFileUpload) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            return ['success' => false, 'error' => 'cURL error'];
        }

        $decoded = json_decode($response, true);
        return $decoded ?: ['success' => false, 'error' => 'Invalid JSON response'];
    }

    /**
     * Run all tests
     */
    public function runAllTests()
    {
        echo "🚀 Starting Comprehensive Summarize Async Tests\n";
        echo "=" . str_repeat("=", 50) . "\n";

        if (!$this->authenticate()) {
            echo "❌ Cannot proceed without authentication\n";
            return;
        }

        // Run all tests
        $this->testTextInput();
        $this->testWebLink();
        $this->testYouTubeLink();
        $this->testPdfFile();
        $this->testAudioFile();
        $this->testVideoFile();
        $this->testImageFile();

        // Print summary
        $this->printSummary();
    }

    /**
     * Print test summary
     */
    private function printSummary()
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 TEST SUMMARY\n";
        echo str_repeat("=", 60) . "\n";

        $passed = 0;
        $failed = 0;
        $timeout = 0;

        foreach ($this->testResults as $testType => $result) {
            $status = $result['status'];
            $icon = $status === 'passed' ? '✅' : ($status === 'timeout' ? '⏰' : '❌');
            
            echo "{$icon} {$testType}: {$status}\n";
            
            if ($status === 'passed') $passed++;
            elseif ($status === 'timeout') $timeout++;
            else $failed++;

            if (isset($result['error'])) {
                echo "   Error: {$result['error']}\n";
            }
        }

        echo "\n📈 RESULTS:\n";
        echo "✅ Passed: {$passed}\n";
        echo "❌ Failed: {$failed}\n";
        echo "⏰ Timeout: {$timeout}\n";
        echo "📊 Total: " . count($this->testResults) . "\n";

        if ($failed === 0 && $timeout === 0) {
            echo "\n🎉 ALL TESTS PASSED! 🎉\n";
        } else {
            echo "\n⚠️ Some tests failed or timed out\n";
        }
    }
}

// Run tests
$tester = new SummarizeAsyncTester();
$tester->runAllTests();






