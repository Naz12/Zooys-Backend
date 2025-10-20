<?php

echo "=== Testing Direct Transcriber API ===\n\n";

$apiUrl = 'https://transcriber.akmicroservice.com';
$clientKey = 'dev-local';
$videoUrl = 'https://www.youtube.com/watch?v=phlTuEnBdco';

echo "API URL: {$apiUrl}\n";
echo "Video URL: {$videoUrl}\n";
echo "Format: bundle\n\n";

// Test direct API call
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl . '/subtitles?url=' . urlencode($videoUrl) . '&format=bundle&include_meta=1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Client-Key: ' . $clientKey,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

echo "Making direct API call...\n";
$startTime = microtime(true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

echo "Duration: {$duration} seconds\n";
echo "HTTP Code: {$httpCode}\n";

if ($error) {
    echo "cURL Error: {$error}\n";
}

if ($response) {
    $data = json_decode($response, true);
    
    if ($httpCode === 200) {
        echo "✅ Direct API call successful!\n";
        echo "Response structure:\n";
        print_r(array_keys($data));
        
        if (isset($data['result_payload'])) {
            $payload = $data['result_payload'];
            echo "\nResult payload structure:\n";
            print_r(array_keys($payload));
            
            if (isset($payload['article_text'])) {
                echo "\nArticle text length: " . strlen($payload['article_text']) . " characters\n";
            }
            
            if (isset($payload['json_items'])) {
                echo "JSON items count: " . count($payload['json_items']) . "\n";
            }
        }
    } elseif ($httpCode === 202) {
        echo "🔄 Job started (async)\n";
        if (isset($data['job_key'])) {
            echo "Job Key: " . $data['job_key'] . "\n";
            
            // Test polling the job
            echo "\nTesting job polling...\n";
            $jobKey = $data['job_key'];
            
            for ($i = 1; $i <= 5; $i++) {
                sleep(2);
                
                $statusCh = curl_init();
                curl_setopt($statusCh, CURLOPT_URL, $apiUrl . '/status?job_key=' . $jobKey);
                curl_setopt($statusCh, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($statusCh, CURLOPT_HTTPHEADER, [
                    'X-Client-Key: ' . $clientKey
                ]);
                curl_setopt($statusCh, CURLOPT_TIMEOUT, 10);
                
                $statusResponse = curl_exec($statusCh);
                $statusCode = curl_getinfo($statusCh, CURLINFO_HTTP_CODE);
                curl_close($statusCh);
                
                if ($statusResponse) {
                    $statusData = json_decode($statusResponse, true);
                    echo "Poll {$i}: Status = " . ($statusData['status'] ?? 'unknown') . ", Stage = " . ($statusData['stage'] ?? 'unknown') . "\n";
                    
                    if (isset($statusData['status']) && $statusData['status'] === 'completed') {
                        echo "✅ Job completed! Fetching result...\n";
                        
                        $resultCh = curl_init();
                        curl_setopt($resultCh, CURLOPT_URL, $apiUrl . '/result?job_key=' . $jobKey);
                        curl_setopt($resultCh, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($resultCh, CURLOPT_HTTPHEADER, [
                            'X-Client-Key: ' . $clientKey
                        ]);
                        curl_setopt($resultCh, CURLOPT_TIMEOUT, 30);
                        
                        $resultResponse = curl_exec($resultCh);
                        $resultCode = curl_getinfo($resultCh, CURLINFO_HTTP_CODE);
                        curl_close($resultCh);
                        
                        if ($resultResponse && $resultCode === 200) {
                            $resultData = json_decode($resultResponse, true);
                            echo "Result structure:\n";
                            print_r(array_keys($resultData));
                            
                            if (isset($resultData['result_payload'])) {
                                $payload = $resultData['result_payload'];
                                echo "\nResult payload keys:\n";
                                print_r(array_keys($payload));
                            }
                        }
                        break;
                    } elseif (isset($statusData['status']) && $statusData['status'] === 'failed') {
                        echo "❌ Job failed: " . ($statusData['stage'] ?? 'unknown error') . "\n";
                        if (isset($statusData['logs'])) {
                            echo "Logs:\n";
                            print_r($statusData['logs']);
                        }
                        break;
                    }
                }
            }
        }
    } else {
        echo "❌ API call failed with HTTP {$httpCode}\n";
        echo "Response: {$response}\n";
    }
} else {
    echo "❌ No response received\n";
}

echo "\n=== DIRECT API TEST COMPLETE ===\n";
