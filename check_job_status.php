<?php

require_once 'vendor/autoload.php';

// Check job status
$jobId = '6d9ed556-1b8b-4553-b704-643aae8e3177';
$baseUrl = 'http://localhost:8000';
$token = '1|VSwn9FCqLFivSUGJKMukhq7kcrnXDK8h6JQmleJX97994aca';

$statusUrl = $baseUrl . '/api/summarize/status/' . $jobId;

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $statusUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Accept: application/json'
    ],
    CURLOPT_TIMEOUT => 10
]);

echo "Checking job status: $statusUrl\n";

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);

curl_close($curl);

echo "Status HTTP Code: $httpCode\n";

if ($error) {
    echo "cURL Error: $error\n";
    exit(1);
}

echo "Status Response:\n";
$responseData = json_decode($response, true);
if ($responseData) {
    echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
    
    // If job is completed, get the result
    if (isset($responseData['status']) && $responseData['status'] === 'completed') {
        echo "\n=== Getting Job Result ===\n";
        
        $resultUrl = $baseUrl . '/api/summarize/result/' . $jobId;
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $resultUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 10
        ]);
        
        echo "Getting job result: $resultUrl\n";
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        echo "Result HTTP Code: $httpCode\n";
        
        if ($error) {
            echo "cURL Error: $error\n";
        } else {
            echo "Result Response:\n";
            $responseData = json_decode($response, true);
            if ($responseData) {
                echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
            } else {
                echo $response . "\n";
            }
        }
    }
} else {
    echo $response . "\n";
}

?>
