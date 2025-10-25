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
            $this->error("Error processing job: " . $e->getMessage());
            Log::error("Universal job processing error", [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}







