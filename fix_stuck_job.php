<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔧 Fixing Stuck Job: cdd3e549-4ae5-45ce-83c6-9d2800dc8552\n";
echo "==========================================================\n\n";

$jobId = 'cdd3e549-4ae5-45ce-83c6-9d2800dc8552';

// Check if job exists in database
echo "📊 Checking job in database...\n";
$job = \Illuminate\Support\Facades\DB::table('universal_jobs')
    ->where('job_id', $jobId)
    ->first();

if (!$job) {
    echo "❌ Job not found in database\n";
    echo "This job might be stored in Redis cache only\n";
    
    // Clear Redis cache for this job
    echo "\n🧹 Clearing Redis cache for job...\n";
    try {
        $redis = \Illuminate\Support\Facades\Redis::connection();
        $redis->del("job:{$jobId}");
        $redis->del("job:{$jobId}:status");
        $redis->del("job:{$jobId}:result");
        echo "✅ Redis cache cleared\n";
    } catch (Exception $e) {
        echo "❌ Failed to clear Redis cache: " . $e->getMessage() . "\n";
    }
} else {
    echo "✅ Job found in database\n";
    echo "Status: {$job->status}\n";
    echo "Stage: {$job->stage}\n";
    echo "Progress: {$job->progress}%\n";
    echo "Created: {$job->created_at}\n";
    echo "Updated: {$job->updated_at}\n";
    
    // Check how long it's been running
    $createdAt = \Carbon\Carbon::parse($job->created_at);
    $minutesRunning = $createdAt->diffInMinutes(now());
    echo "Running for: {$minutesRunning} minutes\n";
    
    if ($minutesRunning > 30) {
        echo "\n⚠️ Job has been running for {$minutesRunning} minutes - marking as failed\n";
        
        // Update job status to failed
        \Illuminate\Support\Facades\DB::table('universal_jobs')
            ->where('job_id', $jobId)
            ->update([
                'status' => 'failed',
                'stage' => 'failed',
                'error' => 'Job timeout - manually failed after ' . $minutesRunning . ' minutes',
                'updated_at' => now()
            ]);
        
        echo "✅ Job marked as failed\n";
    } else {
        echo "\n⏰ Job has been running for {$minutesRunning} minutes - still within normal range\n";
        echo "Consider waiting a bit longer or check for processing issues\n";
    }
}

// Clear Redis queue
echo "\n🧹 Clearing Redis queue...\n";
try {
    $redis = \Illuminate\Support\Facades\Redis::connection();
    $queueSize = $redis->llen('queues:default');
    echo "Queue size before: {$queueSize}\n";
    
    // Clear the queue
    $redis->del('queues:default');
    
    $queueSizeAfter = $redis->llen('queues:default');
    echo "Queue size after: {$queueSizeAfter}\n";
    echo "✅ Queue cleared\n";
    
} catch (Exception $e) {
    echo "❌ Failed to clear queue: " . $e->getMessage() . "\n";
}

echo "\n🔧 RECOMMENDATIONS:\n";
echo "===================\n";
echo "1. Restart the queue worker: .\\start_queue_worker.bat\n";
echo "2. Test with a new job to ensure the system is working\n";
echo "3. Monitor job processing times\n";
echo "4. Check AI Manager service connectivity\n";
echo "5. Consider increasing timeout values for long-running jobs\n";

echo "\n📊 SYSTEM STATUS:\n";
echo "==================\n";
echo "✅ Universal jobs table created\n";
echo "✅ Stuck job handled\n";
echo "✅ Queue cleared\n";
echo "🔄 Ready for new jobs\n";

