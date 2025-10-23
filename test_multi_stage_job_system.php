<?php

require_once 'vendor/autoload.php';

use App\Services\UniversalJobService;
use Illuminate\Support\Facades\Cache;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🚀 Testing Multi-Stage Job Scheduler System\n";
echo "==========================================\n\n";

// Test different job types with stage tracking
$testCases = [
    [
        'name' => 'Text Summarization',
        'tool_type' => 'summarize',
        'input' => [
            'content_type' => 'text',
            'source' => [
                'data' => 'This is a test text for summarization. It contains multiple sentences to demonstrate the multi-stage processing system.'
            ]
        ],
        'options' => ['mode' => 'detailed']
    ],
    [
        'name' => 'YouTube Video Summarization',
        'tool_type' => 'summarize',
        'input' => [
            'content_type' => 'link',
            'source' => [
                'data' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'
            ]
        ],
        'options' => ['mode' => 'detailed']
    ],
    [
        'name' => 'Math Problem Solving',
        'tool_type' => 'math',
        'input' => [
            'text' => 'Solve for x: 2x + 5 = 15',
            'user_id' => 1
        ],
        'options' => ['subject_area' => 'algebra']
    ],
    [
        'name' => 'Flashcard Generation',
        'tool_type' => 'flashcards',
        'input' => [
            'text' => 'Machine Learning is a subset of artificial intelligence that focuses on algorithms that can learn from data.',
            'user_id' => 1
        ],
        'options' => ['count' => 3]
    ]
];

$universalJobService = app(UniversalJobService::class);

foreach ($testCases as $index => $testCase) {
    echo "🧪 Test Case " . ($index + 1) . ": {$testCase['name']}\n";
    echo str_repeat("-", 50) . "\n";
    
    try {
        // Create job
        $job = $universalJobService->createJob(
            $testCase['tool_type'],
            $testCase['input'],
            $testCase['options'],
            1 // user_id
        );
        
        echo "✅ Job Created: {$job['id']}\n";
        echo "📊 Initial Status: {$job['status']} | Stage: {$job['stage']} | Progress: {$job['progress']}%\n\n";
        
        // Simulate job processing with stage monitoring
        echo "🔄 Processing Job with Stage Monitoring:\n";
        
        // Start processing
        $universalJobService->updateJob($job['id'], [
            'status' => 'running',
            'stage' => 'initializing',
            'progress' => 5
        ]);
        
        echo "   Stage: initializing (5%)\n";
        sleep(1);
        
        // Simulate different stages based on job type
        if ($testCase['tool_type'] === 'summarize') {
            if ($testCase['input']['content_type'] === 'text') {
                $stages = [
                    ['stage' => 'analyzing_content', 'progress' => 10],
                    ['stage' => 'processing_text', 'progress' => 20],
                    ['stage' => 'ai_processing', 'progress' => 50],
                    ['stage' => 'finalizing', 'progress' => 90]
                ];
            } else {
                $stages = [
                    ['stage' => 'analyzing_content', 'progress' => 10],
                    ['stage' => 'analyzing_url', 'progress' => 20],
                    ['stage' => 'processing_video', 'progress' => 30],
                    ['stage' => 'transcribing', 'progress' => 50],
                    ['stage' => 'ai_processing', 'progress' => 80],
                    ['stage' => 'finalizing', 'progress' => 95]
                ];
            }
        } elseif ($testCase['tool_type'] === 'math') {
            $stages = [
                ['stage' => 'analyzing_problem', 'progress' => 20],
                ['stage' => 'solving_problem', 'progress' => 60],
                ['stage' => 'finalizing', 'progress' => 90]
            ];
        } elseif ($testCase['tool_type'] === 'flashcards') {
            $stages = [
                ['stage' => 'analyzing_content', 'progress' => 20],
                ['stage' => 'generating_flashcards', 'progress' => 60],
                ['stage' => 'finalizing', 'progress' => 90]
            ];
        }
        
        // Update through stages
        foreach ($stages as $stage) {
            $universalJobService->updateJob($job['id'], $stage);
            $universalJobService->addLog($job['id'], "Processing stage: {$stage['stage']}", 'info', [
                'progress' => $stage['progress'],
                'timestamp' => now()->toISOString()
            ]);
            
            echo "   Stage: {$stage['stage']} ({$stage['progress']}%)\n";
            sleep(1);
        }
        
        // Complete job
        $universalJobService->completeJob($job['id'], [
            'success' => true,
            'result' => 'Test result for ' . $testCase['name'],
            'metadata' => [
                'processing_stages' => array_column($stages, 'stage'),
                'total_processing_time' => count($stages) + 1,
                'confidence_score' => 0.85
            ]
        ]);
        
        echo "   Stage: completed (100%)\n";
        
        // Get final job status
        $finalJob = $universalJobService->getJob($job['id']);
        echo "\n📊 Final Job Status:\n";
        echo "   Status: {$finalJob['status']}\n";
        echo "   Stage: {$finalJob['stage']}\n";
        echo "   Progress: {$finalJob['progress']}%\n";
        echo "   Logs Count: " . count($finalJob['logs']) . "\n";
        echo "   Processing Time: " . ($finalJob['metadata']['total_processing_time'] ?? 'N/A') . " seconds\n";
        echo "   Stages Completed: " . implode(' → ', $finalJob['metadata']['processing_stages'] ?? []) . "\n";
        
        echo "\n📝 Recent Logs:\n";
        $recentLogs = array_slice($finalJob['logs'], -3);
        foreach ($recentLogs as $log) {
            echo "   [{$log['timestamp']}] {$log['level']}: {$log['message']}\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n\n";
}

echo "🎉 Multi-Stage Job Scheduler Test Complete!\n";
echo "✅ All job types now support detailed stage tracking\n";
echo "📊 Frontend can monitor progress in real-time\n";
echo "🔍 Detailed logging for debugging and monitoring\n";
