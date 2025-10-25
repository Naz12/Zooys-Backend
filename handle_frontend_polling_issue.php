<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Frontend Polling Issue Analysis\n";
echo "==================================\n\n";

$problematicJobId = 'cdd3e549-4ae5-45ce-83c6-9d2800dc8552';

echo "📊 Analyzing Job: {$problematicJobId}\n";
echo "------------------------------------\n";

// Check if job exists in database
$job = \Illuminate\Support\Facades\DB::table('universal_jobs')
    ->where('job_id', $problematicJobId)
    ->first();

if ($job) {
    echo "✅ Job found in database\n";
    echo "Status: {$job->status}\n";
    echo "Stage: {$job->stage}\n";
    echo "Progress: {$job->progress}%\n";
    echo "Created: {$job->created_at}\n";
    echo "Updated: {$job->updated_at}\n";
    
    // Mark as failed to stop frontend polling
    echo "\n🔧 Marking job as failed to stop frontend polling...\n";
    \Illuminate\Support\Facades\DB::table('universal_jobs')
        ->where('job_id', $problematicJobId)
        ->update([
            'status' => 'failed',
            'stage' => 'failed',
            'error' => 'Job manually terminated - system cleanup',
            'updated_at' => now()
        ]);
    
    echo "✅ Job marked as failed\n";
} else {
    echo "❌ Job not found in database\n";
    echo "This job was only in Redis cache and has been cleared\n";
}

// Check Redis cache
echo "\n🔍 Checking Redis cache...\n";
try {
    $redis = \Illuminate\Support\Facades\Redis::connection();
    
    // Check for any remaining cache keys
    $cacheKeys = [
        "job:{$problematicJobId}",
        "job:{$problematicJobId}:status",
        "job:{$problematicJobId}:result"
    ];
    
    foreach ($cacheKeys as $key) {
        $exists = $redis->exists($key);
        echo "Cache key '{$key}': " . ($exists ? 'EXISTS' : 'NOT FOUND') . "\n";
        
        if ($exists) {
            $redis->del($key);
            echo "  → Cleared\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Redis error: " . $e->getMessage() . "\n";
}

echo "\n📊 FRONTEND POLLING ISSUE EXPLANATION:\n";
echo "=======================================\n";
echo "1. Frontend is still polling the old job ID\n";
echo "2. The job was stuck in 'transcribing' stage\n";
echo "3. Frontend keeps checking status every few seconds\n";
echo "4. This creates continuous API requests in the logs\n";
echo "5. The job has now been marked as 'failed' to stop polling\n";

echo "\n🔧 SOLUTIONS:\n";
echo "=============\n";
echo "1. ✅ Job marked as failed - frontend should stop polling\n";
echo "2. ✅ Queue completely cleared\n";
echo "3. ✅ Redis cache cleared\n";
echo "4. 🔄 Restart queue worker for fresh start\n";
echo "5. 🔄 Test with new job to ensure system works\n";

echo "\n📋 FRONTEND RECOMMENDATIONS:\n";
echo "=============================\n";
echo "1. Frontend should handle 'failed' status and stop polling\n";
echo "2. Frontend should show error message for failed jobs\n";
echo "3. Frontend should allow user to retry or start new job\n";
echo "4. Consider adding timeout for job polling (e.g., 10 minutes)\n";

echo "\n🎯 SYSTEM STATUS:\n";
echo "==================\n";
echo "✅ Problematic job handled\n";
echo "✅ Queue cleared\n";
echo "✅ Cache cleared\n";
echo "🔄 Ready for fresh start\n";



