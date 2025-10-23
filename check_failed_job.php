<?php

/**
 * Check the failed job details
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Checking failed job details\n";
echo "============================\n\n";

$jobId = '90876fff-753f-4606-9db2-a01acf162463';

try {
    $universalJobService = app(\App\Services\UniversalJobService::class);
    $job = $universalJobService->getJob($jobId);
    
    if ($job) {
        echo "Job ID: {$jobId}\n";
        echo "Status: " . ($job['status'] ?? 'Unknown') . "\n";
        echo "Progress: " . ($job['progress'] ?? 'Unknown') . "\n";
        echo "Stage: " . ($job['stage'] ?? 'Unknown') . "\n";
        echo "Error: " . ($job['error'] ?? 'No error') . "\n";
        echo "Created: " . ($job['created_at'] ?? 'Unknown') . "\n";
        echo "Updated: " . ($job['updated_at'] ?? 'Unknown') . "\n";
        
        if (isset($job['logs']) && is_array($job['logs'])) {
            echo "\nLogs:\n";
            foreach ($job['logs'] as $log) {
                echo "- " . $log . "\n";
            }
        }
    } else {
        echo "❌ Job not found\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
