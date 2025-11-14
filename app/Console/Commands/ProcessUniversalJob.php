<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\UniversalJobService;
use Illuminate\Support\Facades\Log;

class ProcessUniversalJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'universal:process-job {jobId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process a universal job by ID';

    private $universalJobService;

    /**
     * Create a new command instance.
     */
    public function __construct(UniversalJobService $universalJobService)
    {
        parent::__construct();
        $this->universalJobService = $universalJobService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Increase memory limit for long-running jobs
        ini_set('memory_limit', '1024M');
        
        $jobId = $this->argument('jobId');
        
        $this->info("Processing universal job: {$jobId}");
        
        try {
            // Get job details
            $job = $this->universalJobService->getJob($jobId);
            
            if (!$job) {
                $this->error("Job not found: {$jobId}");
                return 1;
            }

            $this->info("Job details:");
            $this->line("- Tool Type: {$job['tool_type']}");
            $this->line("- Status: {$job['status']}");
            $this->line("- Stage: {$job['stage']}");
            $this->line("- Progress: {$job['progress']}%");
            $this->line("- Created: {$job['created_at']}");

            // Process the job
            $this->info("Starting job processing...");
            $result = $this->universalJobService->processJob($jobId);
            
            if ($result['success']) {
                $this->info("Job completed successfully!");
                $this->line("Result: " . json_encode($result['data'], JSON_PRETTY_PRINT));
            } else {
                $this->error("Job failed: " . $result['error']);
                return 1;
            }

            return 0;

        } catch (\Exception $e) {
            $errorMessage = "Error processing job: " . $e->getMessage();
            $this->error($errorMessage);
            
            Log::error("Universal job processing error", [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
                'exception_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Ensure job is marked as failed even if processJob didn't catch it
            try {
                $universalJobService = app(UniversalJobService::class);
                $job = $universalJobService->getJob($jobId);
                if ($job && ($job['status'] ?? '') !== 'failed') {
                    $universalJobService->failJob($jobId, $e->getMessage());
                }
            } catch (\Exception $failException) {
                Log::error("Failed to mark job as failed", [
                    'job_id' => $jobId,
                    'error' => $failException->getMessage()
                ]);
            }
            
            return 1;
        }
    }
}




















