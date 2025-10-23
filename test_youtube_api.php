<?php

echo "Testing YouTube endpoint via HTTP API...\n";

// Test the async YouTube endpoint
$url = 'http://localhost:8000/api/summarize/async/youtube';
$data = [
    'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'options' => [
        'language' => 'en',
        'format' => 'detailed',
        'focus' => 'summary'
    ]
];

$headers = [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer 178|llkCJsMgmEs3ObYQio1xHjzyRRrct30R6mE8a3ae14717449'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "=== INITIAL RESPONSE ===\n";
echo $response . "\n\n";

if ($httpCode === 200) {
    $responseData = json_decode($response, true);
    
    if (isset($responseData['job_id'])) {
        $jobId = $responseData['job_id'];
        echo "Job ID: $jobId\n";
        echo "Waiting for processing...\n";
        
        // Poll for status
        for ($i = 1; $i <= 15; $i++) {
            sleep(3);
            echo "Status check $i...\n";
            
            $statusUrl = "http://localhost:8000/api/summarize/status/$jobId";
            $statusCh = curl_init();
            curl_setopt($statusCh, CURLOPT_URL, $statusUrl);
            curl_setopt($statusCh, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($statusCh, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($statusCh, CURLOPT_TIMEOUT, 10);
            
            $statusResponse = curl_exec($statusCh);
            $statusHttpCode = curl_getinfo($statusCh, CURLINFO_HTTP_CODE);
            curl_close($statusCh);
            
            if ($statusHttpCode === 200) {
                $statusData = json_decode($statusResponse, true);
                echo "Status: " . $statusData['status'] . " (Progress: " . $statusData['progress'] . "%)\n";
                
                if ($statusData['status'] === 'completed') {
                    echo "\n=== FINAL RESULT ===\n";
                    $resultUrl = "http://localhost:8000/api/summarize/result/$jobId";
                    $resultCh = curl_init();
                    curl_setopt($resultCh, CURLOPT_URL, $resultUrl);
                    curl_setopt($resultCh, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($resultCh, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($resultCh, CURLOPT_TIMEOUT, 10);
                    
                    $resultResponse = curl_exec($resultCh);
                    $resultHttpCode = curl_getinfo($resultCh, CURLINFO_HTTP_CODE);
                    curl_close($resultCh);
                    
                    if ($resultHttpCode === 200) {
                        echo $resultResponse . "\n";
                    } else {
                        echo "Failed to get result: HTTP $resultHttpCode\n";
                    }
                    break;
                } elseif ($statusData['status'] === 'failed') {
                    echo "Job failed: " . ($statusData['error'] ?? 'Unknown error') . "\n";
                    break;
                }
            } else {
                echo "Status check failed: HTTP $statusHttpCode\n";
            }
        }
    }
} else {
    echo "Request failed: HTTP $httpCode\n";
}

