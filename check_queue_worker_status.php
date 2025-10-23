<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Queue Worker Status Check\n";
echo "============================\n\n";

// Check Redis connection
echo "📡 Checking Redis Connection...\n";
try {
    $redis = \Illuminate\Support\Facades\Redis::connection();
    $redis->ping();
    echo "✅ Redis is connected\n";
    
    // Check queue size
    $queueSize = $redis->llen('queues:default');
    echo "📊 Queue Size: {$queueSize} jobs\n";
    
    // Check failed jobs
    $failedJobs = $redis->llen('queues:failed');
    echo "❌ Failed Jobs: {$failedJobs}\n";
    
} catch (Exception $e) {
    echo "❌ Redis connection failed: " . $e->getMessage() . "\n";
}

echo "\n🔍 Checking Job Processing Status...\n";

// Check if there are any running jobs
$runningJobs = \Illuminate\Support\Facades\DB::table('universal_jobs')
    ->where('status', 'running')
    ->get();

echo "🏃 Running Jobs: " . $runningJobs->count() . "\n";

foreach ($runningJobs as $job) {
    echo "\n📋 Job: {$job->id}\n";
    echo "   Status: {$job->status}\n";
    echo "   Stage: {$job->stage}\n";
    echo "   Progress: {$job->progress}%\n";
    echo "   Created: {$job->created_at}\n";
    echo "   Updated: {$job->updated_at}\n";
    
    // Check how long it's been running
    $createdAt = \Carbon\Carbon::parse($job->created_at);
    $updatedAt = \Carbon\Carbon::parse($job->updated_at);
    $minutesRunning = $createdAt->diffInMinutes(now());
    $minutesSinceUpdate = $updatedAt->diffInMinutes(now());
    
    echo "   Running for: {$minutesRunning} minutes\n";
    echo "   Last update: {$minutesSinceUpdate} minutes ago\n";
    
    if ($minutesSinceUpdate > 10) {
        echo "   ⚠️ WARNING: Job hasn't updated in {$minutesSinceUpdate} minutes!\n";
    }
    
    if ($minutesRunning > 30) {
        echo "   ⚠️ WARNING: Job has been running for {$minutesRunning} minutes!\n";
    }
}

echo "\n🔍 Checking for Stuck Jobs...\n";

// Check for jobs that have been running too long
$stuckJobs = \Illuminate\Support\Facades\DB::table('universal_jobs')
    ->where('status', 'running')
    ->where('updated_at', '<', now()->subMinutes(15))
    ->get();

if ($stuckJobs->count() > 0) {
    echo "⚠️ Found {$stuckJobs->count()} potentially stuck jobs:\n";
    
    foreach ($stuckJobs as $job) {
        echo "\n🚨 Stuck Job: {$job->id}\n";
        echo "   Status: {$job->status}\n";
        echo "   Stage: {$job->stage}\n";
        echo "   Progress: {$job->progress}%\n";
        echo "   Last update: " . \Carbon\Carbon::parse($job->updated_at)->diffForHumans() . "\n";
        
        // Suggest manual failure
        echo "   💡 Suggestion: This job should be manually failed\n";
    }
} else {
    echo "✅ No stuck jobs found\n";
}

echo "\n🔧 RECOMMENDATIONS:\n";
echo "===================\n";
echo "1. Check if queue worker is running continuously\n";
echo "2. Look for timeout issues in job processing\n";
echo "3. Check AI Manager service connectivity\n";
echo "4. Consider restarting the queue worker\n";
echo "5. Monitor job processing times\n";

echo "\n📊 QUEUE WORKER COMMANDS:\n";
echo "==========================\n";
echo "Start Queue Worker: .\\start_queue_worker.bat\n";
echo "Stop Queue Worker: Ctrl+C in the queue worker terminal\n";

