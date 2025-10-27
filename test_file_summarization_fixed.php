<?php

// Test the fixed file summarization endpoint
$baseUrl = 'http://localhost:8000/api';
$token = '1|your-token-here'; // Replace with actual token

echo "=== Testing Fixed File Summarization ===\n\n";

// Step 1: Upload a test file
echo "Step 1: Upload test file\n";
$testContent = "This is a comprehensive test document for file summarization. It contains multiple paragraphs with detailed information about various topics. The document discusses technology, business strategies, and implementation approaches. This content should be properly extracted and summarized by the AI system.";
file_put_contents('test_summarization.txt', $testContent);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/files/upload");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'file' => new CURLFile('test_summarization.txt', 'text/plain', 'test_summarization.txt'),
    'metadata' => '{}'
]);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$uploadResponse = curl_exec($ch);
$uploadHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Upload Response (HTTP $uploadHttpCode):\n";
echo $uploadResponse . "\n\n";

if ($uploadHttpCode === 200) {
    $uploadResult = json_decode($uploadResponse, true);
    $fileId = $uploadResult['file_id'];
    
    echo "Step 2: Start file summarization\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$baseUrl/summarize/async/file");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'file_id' => $fileId,
        'options' => json_encode([
            'language' => 'en',
            'format' => 'detailed',
            'focus' => 'summary',
            'include_formatting' => true,
            'max_pages' => 10
        ])
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $summarizeResponse = curl_exec($ch);
    $summarizeHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "Summarization Response (HTTP $summarizeHttpCode):\n";
    echo $summarizeResponse . "\n\n";

    if ($summarizeHttpCode === 200) {
        $summarizeResult = json_decode($summarizeResponse, true);
        $jobId = $summarizeResult['job_id'];
        
        echo "Step 3: Poll for completion\n";
        $maxAttempts = 30;
        $attempt = 0;
        
        while ($attempt < $maxAttempts) {
            sleep(3);
            $attempt++;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "$baseUrl/status?job_id=$jobId");
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $statusResponse = curl_exec($ch);
            $statusHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            echo "Status Check $attempt (HTTP $statusHttpCode):\n";
            echo $statusResponse . "\n\n";

            if ($statusHttpCode === 200) {
                $status = json_decode($statusResponse, true);
                if ($status['status'] === 'completed') {
                    echo "Step 4: Get final result\n";
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "$baseUrl/result?job_id=$jobId");
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Bearer ' . $token
                    ]);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                    $resultResponse = curl_exec($ch);
                    $resultHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    echo "Final Result (HTTP $resultHttpCode):\n";
                    echo $resultResponse . "\n\n";
                    break;
                } elseif ($status['status'] === 'failed') {
                    echo "❌ Job failed: " . ($status['error'] ?? 'Unknown error') . "\n";
                    break;
                }
            }
        }
    }
}

// Clean up
unlink('test_summarization.txt');

echo "=== Test Complete ===\n";
