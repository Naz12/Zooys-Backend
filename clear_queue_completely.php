<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧹 Complete Queue Cleanup\n";
echo "========================\n\n";

try {
    $redis = \Illuminate\Support\Facades\Redis::connection();
    
    echo "📊 Current Queue Status:\n";
    echo "------------------------\n";
    
    // Check all queue-related keys
    $queueKeys = [
        'queues:default',
        'queues:failed',
        'queues:universal:process-job'
    ];
    
    foreach ($queueKeys as $key) {
        $size = $redis->llen($key);
        echo "{$key}: {$size} jobs\n";
    }
    
    echo "\n🧹 Clearing All Queues...\n";
    echo "-------------------------\n";
    
    // Clear all queue keys
    foreach ($queueKeys as $key) {
        $deleted = $redis->del($key);
        echo "Cleared {$key}: {$deleted} items\n";
    }
    
    // Clear any job-related cache keys
    echo "\n🧹 Clearing Job Cache...\n";
    echo "------------------------\n";
    
    $pattern = "job:*";
    $keys = $redis->keys($pattern);
    if (!empty($keys)) {
        $deleted = $redis->del($keys);
        echo "Cleared {$deleted} job cache keys\n";
    } else {
        echo "No job cache keys found\n";
    }
    
    // Clear any status-related cache
    $statusPattern = "*status*";
    $statusKeys = $redis->keys($statusPattern);
    if (!empty($statusKeys)) {
        $deleted = $redis->del($statusKeys);
        echo "Cleared {$deleted} status cache keys\n";
    } else {
        echo "No status cache keys found\n";
    }
    
    echo "\n📊 Final Queue Status:\n";
    echo "----------------------\n";
    
    foreach ($queueKeys as $key) {
        $size = $redis->llen($key);
        echo "{$key}: {$size} jobs\n";
    }
    
    echo "\n✅ Queue cleanup completed!\n";
    echo "\n🔧 NEXT STEPS:\n";
    echo "==============\n";
    echo "1. Restart the queue worker: .\\start_queue_worker.bat\n";
    echo "2. Test with a new job to ensure it works\n";
    echo "3. Monitor for any new issues\n";
    
} catch (Exception $e) {
    echo "❌ Error during cleanup: " . $e->getMessage() . "\n";
}


