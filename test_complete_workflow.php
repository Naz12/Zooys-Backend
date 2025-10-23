<?php

echo "=== Testing Complete Bundle Format Workflow ===\n\n";

$apiUrl = 'https://transcriber.akmicroservice.com';
$clientKey = 'dev-local';
$videoUrl = 'https://www.youtube.com/watch?v=phlTuEnBdco';

echo "API URL: {$apiUrl}\n";
echo "Video URL: {$videoUrl}\n";
echo "Format: bundle\n\n";

// Step 1: Start the job
echo "Step 1: Starting transcription job...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl . '/subtitles?url=' . urlencode($videoUrl) . '&format=bundle&include_meta=1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Client-Key: ' . $clientKey,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 202) {
    echo "❌ Failed to start job. HTTP Code: {$httpCode}\n";
    echo "Response: {$response}\n";
    exit(1);
}

$data = json_decode($response, true);
$jobKey = $data['job_key'] ?? null;

if (!$jobKey) {
    echo "❌ No job key received\n";
    exit(1);
}

echo "✅ Job started successfully\n";
echo "Job Key: {$jobKey}\n\n";

// Step 2: Poll for completion
echo "Step 2: Polling for job completion...\n";
$maxAttempts = 60; // 10 minutes max
$attempt = 0;

while ($attempt < $maxAttempts) {
    $attempt++;
    sleep(10); // Wait 10 seconds between polls
    
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
    
    if ($statusResponse && $statusCode === 200) {
        $statusData = json_decode($statusResponse, true);
        $status = $statusData['status'] ?? 'unknown';
        $stage = $statusData['stage'] ?? 'unknown';
        $progress = $statusData['progress'] ?? 0;
        
        echo "Poll {$attempt}: Status = {$status}, Stage = {$stage}, Progress = {$progress}%\n";
        
        if ($status === 'completed') {
            echo "✅ Job completed successfully!\n\n";
            break;
        } elseif ($status === 'failed') {
            echo "❌ Job failed: " . ($statusData['stage'] ?? 'unknown error') . "\n";
            if (isset($statusData['logs'])) {
                echo "Logs:\n";
                foreach ($statusData['logs'] as $log) {
                    echo "  - " . ($log['stage'] ?? 'unknown') . " at " . date('Y-m-d H:i:s', $log['ts'] ?? 0) . "\n";
                }
            }
            exit(1);
        } elseif ($status === 'aborted') {
            echo "❌ Job was aborted\n";
            exit(1);
        }
    } else {
        echo "Poll {$attempt}: Status check failed (HTTP {$statusCode})\n";
    }
}

if ($attempt >= $maxAttempts) {
    echo "❌ Job timed out after {$maxAttempts} attempts\n";
    exit(1);
}

// Step 3: Fetch the result
echo "Step 3: Fetching job result...\n";
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

if ($resultCode !== 200) {
    echo "❌ Failed to fetch result. HTTP Code: {$resultCode}\n";
    echo "Response: {$resultResponse}\n";
    exit(1);
}

$resultData = json_decode($resultResponse, true);

echo "✅ Result fetched successfully!\n\n";

// Step 4: Analyze the result
echo "Step 4: Analyzing result structure...\n";
echo "Top-level keys: " . implode(', ', array_keys($resultData)) . "\n";

if (isset($resultData['result_payload'])) {
    $payload = $resultData['result_payload'];
    echo "Result payload keys: " . implode(', ', array_keys($payload)) . "\n";
    
    if (isset($payload['video_id'])) {
        echo "Video ID: " . $payload['video_id'] . "\n";
    }
    
    if (isset($payload['language'])) {
        echo "Language: " . $payload['language'] . "\n";
    }
    
    if (isset($payload['format'])) {
        echo "Format: " . $payload['format'] . "\n";
    }
    
    if (isset($payload['article_text'])) {
        echo "Article text length: " . strlen($payload['article_text']) . " characters\n";
        echo "Article word count: " . str_word_count($payload['article_text']) . " words\n";
        
        echo "\nArticle preview (first 200 characters):\n";
        echo substr($payload['article_text'], 0, 200) . "...\n";
    }
    
    if (isset($payload['json_items'])) {
        echo "JSON items count: " . count($payload['json_items']) . "\n";
        
        if (count($payload['json_items']) > 0) {
            echo "\nFirst 3 JSON segments:\n";
            $segments = array_slice($payload['json_items'], 0, 3);
            foreach ($segments as $i => $segment) {
                $start = round($segment['start'] ?? 0, 1);
                $duration = round($segment['duration'] ?? 0, 1);
                $end = $start + $duration;
                $text = substr($segment['text'] ?? '', 0, 80);
                echo "  " . ($i + 1) . ". [{$start}s - {$end}s] {$text}...\n";
            }
        }
    }
} else {
    echo "❌ No result_payload found in response\n";
    echo "Available keys: " . implode(', ', array_keys($resultData)) . "\n";
}

echo "\n=== WORKFLOW TEST COMPLETE ===\n";







