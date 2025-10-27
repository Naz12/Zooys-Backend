<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

echo "=== Testing Bundle Format (Article + JSON + Meta) ===\n\n";

$apiUrl = 'https://transcriber.akmicroservice.com';
$key = 'dev-local';

// Test with different videos
$testVideos = [
    'https://www.youtube.com/watch?v=dQw4w9WgXcQ', // Rick Roll - very short
    'https://www.youtube.com/watch?v=x9B02pFKpJo', // Another short video
    'https://www.youtube.com/watch?v=Bpa16gQt9ok'  // The long video you mentioned
];

foreach ($testVideos as $index => $url) {
    echo "=== TEST " . ($index + 1) . ": " . basename($url) . " ===\n";
    echo "URL: {$url}\n";
    
    $startTime = microtime(true);
    
    try {
        $response = Http::timeout(300) // 5 minutes timeout
            ->withHeaders([
                'X-Client-Key' => $key,
            ])
            ->get($apiUrl . '/subtitles', [
                'url' => $url,
                'format' => 'bundle',
                'include_meta' => '1'
            ]);

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        echo "HTTP Status: " . $response->status() . "\n";
        echo "Duration: {$duration} seconds\n";

        if ($response->successful()) {
            $data = $response->json();
            echo "✅ Success!\n";
            
            // Check what we got
            if (isset($data['article'])) {
                echo "📄 Article content: " . strlen($data['article']) . " characters\n";
                echo "Preview: " . substr($data['article'], 0, 100) . "...\n";
            }
            
            if (isset($data['json'])) {
                $segments = $data['json']['segments'] ?? [];
                echo "📊 JSON segments: " . count($segments) . "\n";
                if (!empty($segments)) {
                    echo "First segment: " . json_encode($segments[0]) . "\n";
                }
            }
            
            if (isset($data['meta'])) {
                echo "📋 Metadata: " . json_encode($data['meta']) . "\n";
            }
            
            // Check if it's a job response
            if (isset($data['job_key'])) {
                echo "🔄 This is a job response, job_key: " . $data['job_key'] . "\n";
            }
            
        } else {
            echo "❌ Failed: " . $response->body() . "\n";
        }

    } catch (\Exception $e) {
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        echo "❌ Exception after {$duration} seconds: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat("-", 80) . "\n\n";
}

echo "=== BUNDLE FORMAT TEST COMPLETE ===\n";










