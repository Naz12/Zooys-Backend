<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UniversalJobService
{
    private $universalFileModule;
    private $aiResultService;

    public function __construct(
        AIResultService $aiResultService
    ) {
        // Removed UniversalFileManagementModule from constructor to break circular dependency
        // It will be resolved manually only when needed via getUniversalFileModule()
        $this->aiResultService = $aiResultService;
    }

    /**
     * Get UniversalFileManagementModule with lazy loading
     * Resolves the module only when needed, breaking circular dependency at startup
     * Registers current instance in container to break circular dependency during resolution
     */
    private function getUniversalFileModule()
    {
        if ($this->universalFileModule === null) {
            // Register this instance in the container before resolving dependencies
            // This breaks the circular dependency: when UniversalFileManagementModule
            // tries to resolve AIPresentationService, which tries to resolve UniversalJobService,
            // Laravel will return this already-constructed instance instead of creating a new one
            $container = app();
            $container->instance(\App\Services\UniversalJobService::class, $this);
            
            // Now resolve UniversalFileManagementModule - it can safely resolve AIPresentationService
            // which can resolve UniversalJobService (returns the instance we just registered)
            $this->universalFileModule = $container->make(\App\Services\Modules\UniversalFileManagementModule::class);
        }
        return $this->universalFileModule;
    }

    /**
     * Create a new universal job
     */
    public function createJob($toolType, $input, $options = [], $userId = null)
    {
        $jobId = Str::uuid()->toString();
        
        $job = [
            'id' => $jobId,
            'tool_type' => $toolType,
            'input' => $input,
            'options' => $options,
            'user_id' => $userId,
            'status' => 'pending',
            'stage' => 'initializing',
            'progress' => 0,
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
            'logs' => [],
            'result' => null,
            'error' => null,
            'metadata' => [
                'processing_started_at' => null,
                'processing_completed_at' => null,
                'total_processing_time' => null,
                'file_count' => 0,
                'tokens_used' => 0,
                'confidence_score' => 0.0
            ]
        ];

        // Store job in cache
        Cache::put("universal_job_{$jobId}", $job, 7200); // 2 hours TTL
        
        // Also persist to database for long-term storage
        try {
            \Illuminate\Support\Facades\DB::table('universal_jobs')->insert([
                'job_id' => $jobId,
                'tool_type' => $toolType,
                'input' => json_encode($input),
                'options' => json_encode($options),
                'user_id' => $userId,
                'status' => 'pending',
                'stage' => 'initializing',
                'progress' => 0,
                'logs' => json_encode([]),
                'result' => null,
                'error' => null,
                'metadata' => json_encode($job['metadata']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // If database insert fails, log but don't fail the job creation
            Log::warning("Failed to persist job to database", [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);
        }
        
        Log::info("Universal job created", [
            'job_id' => $jobId,
            'tool_type' => $toolType,
            'user_id' => $userId
        ]);

        return $job;
    }

    /**
     * Get job status
     */
    public function getJob($jobId)
    {
        // First, try to get from cache
        $job = Cache::get("universal_job_{$jobId}");
        if ($job) {
            return $job;
        }
        
        // If not in cache, try to get from database
        $dbJob = \Illuminate\Support\Facades\DB::table('universal_jobs')
            ->where('job_id', $jobId)
            ->first();
            
        if ($dbJob) {
            // Convert database record to job array format
            $job = [
                'id' => $dbJob->job_id,
                'tool_type' => $dbJob->tool_type,
                'input' => is_string($dbJob->input) ? json_decode($dbJob->input, true) : $dbJob->input,
                'options' => is_string($dbJob->options) ? json_decode($dbJob->options, true) : $dbJob->options,
                'user_id' => $dbJob->user_id,
                'status' => $dbJob->status,
                'stage' => $dbJob->stage,
                'progress' => $dbJob->progress,
                'created_at' => $dbJob->created_at ? date('c', strtotime($dbJob->created_at)) : null,
                'updated_at' => $dbJob->updated_at ? date('c', strtotime($dbJob->updated_at)) : null,
                'logs' => is_string($dbJob->logs) ? json_decode($dbJob->logs, true) : $dbJob->logs,
                'result' => is_string($dbJob->result) ? json_decode($dbJob->result, true) : $dbJob->result,
                'error' => $dbJob->error,
                'metadata' => is_string($dbJob->metadata) ? json_decode($dbJob->metadata, true) : $dbJob->metadata,
            ];
            
            // Restore to cache for faster access
            Cache::put("universal_job_{$jobId}", $job, 7200);
            
            return $job;
        }
        
        return null;
    }

    /**
     * Update job status
     */
    public function updateJob($jobId, $updates)
    {
        $job = $this->getJob($jobId);
        if (!$job) {
            return false;
        }

        $job = array_merge($job, $updates);
        $job['updated_at'] = now()->toISOString();
        
        Cache::put("universal_job_{$jobId}", $job, 7200);
        
        // Also update database
        try {
            $dbUpdates = [];
            if (isset($updates['status'])) $dbUpdates['status'] = $updates['status'];
            if (isset($updates['stage'])) $dbUpdates['stage'] = $updates['stage'];
            if (isset($updates['progress'])) $dbUpdates['progress'] = $updates['progress'];
            if (isset($updates['result'])) $dbUpdates['result'] = json_encode($updates['result']);
            if (isset($updates['error'])) $dbUpdates['error'] = $updates['error'];
            if (isset($updates['metadata'])) $dbUpdates['metadata'] = json_encode($updates['metadata']);
            if (isset($updates['logs'])) $dbUpdates['logs'] = json_encode($updates['logs']);
            if (isset($updates['processing_started_at'])) $dbUpdates['processing_started_at'] = $updates['processing_started_at'];
            if (isset($updates['processing_completed_at'])) $dbUpdates['processing_completed_at'] = $updates['processing_completed_at'];
            
            $dbUpdates['updated_at'] = now();
            
            \Illuminate\Support\Facades\DB::table('universal_jobs')
                ->where('job_id', $jobId)
                ->update($dbUpdates);
        } catch (\Exception $e) {
            Log::warning("Failed to update job in database", [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);
        }
        
        return true;
    }

    /**
     * Add log entry to job
     */
    public function addLog($jobId, $message, $level = 'info', $data = [])
    {
        $job = $this->getJob($jobId);
        if (!$job) {
            return false;
        }

        $logEntry = [
            'timestamp' => now()->toISOString(),
            'level' => $level,
            'message' => $message,
            'data' => $data
        ];

        $job['logs'][] = $logEntry;
        $job['updated_at'] = now()->toISOString();
        
        Cache::put("universal_job_{$jobId}", $job, 7200);
        
        Log::log($level, "Universal Job {$jobId}: {$message}", $data);
        
        return true;
    }

    /**
     * Complete job with result
     */
    public function completeJob($jobId, $result, $metadata = [])
    {
        $job = $this->getJob($jobId);
        if (!$job) {
            return false;
        }

        $job['status'] = 'completed';
        $job['stage'] = 'completed';
        $job['progress'] = 100;
        $job['result'] = $result;
        $job['updated_at'] = now()->toISOString();
        $job['metadata'] = array_merge($job['metadata'], $metadata, [
            'processing_completed_at' => now()->toISOString(),
            'total_processing_time' => $this->calculateProcessingTime($job)
        ]);

        Cache::put("universal_job_{$jobId}", $job, 7200);
        
        $this->addLog($jobId, 'Job completed successfully', 'info');
        
        return true;
    }

    /**
     * Fail job with error
     */
    public function failJob($jobId, $error, $metadata = [], $result = null)
    {
        $job = $this->getJob($jobId);
        if (!$job) {
            return false;
        }

        $job['status'] = 'failed';
        $job['stage'] = 'failed';
        $job['error'] = $error;
        $job['updated_at'] = now()->toISOString();
        
        // Store result data if provided (e.g., doc_id, conversation_id for failed Document Intelligence jobs)
        if ($result !== null) {
            $job['result'] = $result;
        }
        
        $job['metadata'] = array_merge($job['metadata'], $metadata, [
            'processing_completed_at' => now()->toISOString(),
            'total_processing_time' => $this->calculateProcessingTime($job)
        ]);

        Cache::put("universal_job_{$jobId}", $job, 7200);
        
        $this->addLog($jobId, "Job failed: {$error}", 'error');
        
        return true;
    }

    /**
     * Queue a job for background processing
     */
    public function queueJob($jobId)
    {
        // Check if job is already running/completed/failed before queuing
        $job = $this->getJob($jobId);
        if ($job) {
            $currentStatus = $job['status'] ?? 'pending';
            if (in_array($currentStatus, ['running', 'completed', 'failed'])) {
                Log::info("Skipping queue for job that is already {$currentStatus}", [
                    'job_id' => $jobId,
                    'status' => $currentStatus
                ]);
                return;
            }
        }

        try {
            // Try to dispatch as a job first (if queue is configured)
            if (config('queue.default') !== 'sync') {
                \Illuminate\Support\Facades\Artisan::queue('universal:process-job', [
                    'jobId' => $jobId
                ]);
            } else {
                // If queue is sync, process immediately in background using exec
                // This prevents blocking the HTTP response
                if (PHP_OS_FAMILY === 'Windows') {
                    // Windows: use start /B to run in background
                    $command = "php artisan universal:process-job {$jobId}";
                    pclose(popen("start /B " . $command, "r"));
                } else {
                    // Linux/Unix: use nohup to run in background
                    $command = "php artisan universal:process-job {$jobId} > /dev/null 2>&1 &";
                    exec($command);
                }
            }
        } catch (\Exception $e) {
            Log::warning("Failed to queue job, processing in background thread", [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);
            
            // Use exec to run in background
            if (PHP_OS_FAMILY === 'Windows') {
                $command = "php artisan universal:process-job {$jobId}";
                pclose(popen("start /B " . $command, "r"));
            } else {
                $command = "php artisan universal:process-job {$jobId} > /dev/null 2>&1 &";
                exec($command);
            }
        }
    }

    /**
     * Process a universal job with detailed stage tracking
     */
    public function processJob($jobId)
    {
        $job = $this->getJob($jobId);
        if (!$job) {
            throw new \Exception("Job not found: {$jobId}");
        }

        // Prevent processing if job is already running, completed, or failed
        $currentStatus = $job['status'] ?? 'pending';
        if ($currentStatus === 'running') {
            Log::warning("Job is already being processed", [
                'job_id' => $jobId,
                'status' => $currentStatus,
                'stage' => $job['stage'] ?? 'unknown'
            ]);
            return [
                'success' => false,
                'error' => 'Job is already being processed',
                'status' => $currentStatus
            ];
        }

        if ($currentStatus === 'completed') {
            Log::info("Job already completed", ['job_id' => $jobId]);
            return [
                'success' => true,
                'data' => $job['result'] ?? null,
                'status' => 'completed',
                'message' => 'Job was already completed'
            ];
        }

        if ($currentStatus === 'failed') {
            Log::info("Job already failed", ['job_id' => $jobId]);
            return [
                'success' => false,
                'error' => $job['error'] ?? 'Job already failed',
                'status' => 'failed'
            ];
        }

        try {
            // Atomically update status to running to prevent concurrent processing
            $this->updateJob($jobId, [
                'status' => 'running',
                'stage' => 'initializing',
                'progress' => 5,
                'metadata' => array_merge($job['metadata'], [
                    'processing_started_at' => now()->toISOString()
                ])
            ]);

            $this->addLog($jobId, "Starting {$job['tool_type']} processing", 'info', [
                'tool_type' => $job['tool_type'],
                'content_type' => $job['input']['content_type'] ?? 'unknown'
            ]);

            // Set a maximum processing time
            $maxProcessingTime = 900; // 15 minutes for Smartproxy
            $startTime = time();
            
            $result = $this->processByToolTypeWithStages($job, $job['user_id']);
            
            // Check if processing took too long
            $processingTime = time() - $startTime;
            if ($processingTime > $maxProcessingTime) {
                $this->failJob($jobId, "Job processing timeout after {$processingTime} seconds");
                return ['success' => false, 'error' => 'Processing timeout'];
            }
            
            if ($result['success']) {
                $this->completeJob($jobId, $result['data'], $result['metadata'] ?? []);
            } else {
                // Store result data even on failure (for doc_id, conversation_id, etc.)
                $errorResult = [
                    'success' => false,
                    'error' => $result['error'],
                    'error_details' => $result['error_details'] ?? null,
                    'doc_id' => $result['doc_id'] ?? null,
                    'conversation_id' => $result['conversation_id'] ?? null
                ];
                $this->failJob($jobId, $result['error'], $result['metadata'] ?? [], $errorResult);
            }

            return $result;

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $errorDetails = [
                'exception_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
            
            Log::error("Universal Job processing exception", [
                'job_id' => $jobId,
                'error' => $errorMessage,
                'details' => $errorDetails
            ]);
            
            $this->failJob($jobId, $errorMessage);
            $this->addLog($jobId, "Job processing exception: {$errorMessage}", 'error', $errorDetails);
            
            // Don't re-throw to prevent command from failing - error is already logged
            return [
                'success' => false,
                'error' => $errorMessage,
                'error_details' => $errorDetails
            ];
        }
    }

    /**
     * Process job based on tool type with detailed stage tracking
     */
    private function processByToolTypeWithStages($job, $userId = null)
    {
        $toolType = $job['tool_type'];
        $input = $job['input'];
        $options = $job['options'];

        switch ($toolType) {
            case 'summarize':
                return $this->processSummarizeJobWithStages($job['id'], $input, $options, $userId);
            
            case 'math':
                return $this->processMathJobWithStages($job['id'], $input, $options);
            
            case 'flashcards':
                return $this->processFlashcardsJobWithStages($job['id'], $input, $options);
            
            case 'presentations':
                return $this->processPresentationsJobWithStages($job['id'], $input, $options);
            
            case 'presentation_outline':
            case 'presentation_content':
            case 'presentation_export':
                // Use closure to defer resolution and break circular dependency
                return $this->processPresentationJob($job, $toolType);
            
            case 'diagram':
                return $this->processDiagramJobWithStages($job['id'], $input, $options, $userId);
            
            case 'document_chat':
                return $this->processDocumentChatJobWithStages($job['id'], $input, $options);
            
            case 'document_conversion':
                return $this->processDocumentConversionJobWithStages($job['id'], $input, $options);
            
            case 'content_extraction':
                return $this->processContentExtractionJobWithStages($job['id'], $input, $options);
            
            case 'pdf_edit':
                return $this->processPdfEditJobWithStages($job['id'], $input, $options);
            
            case 'document_intelligence':
                return $this->processDocumentIntelligenceJobWithStages($job['id'], $input, $options);
            
            case 'content_write':
            case 'content_rewrite':
                return $this->processContentWriterJob($job, $toolType);
            
            default:
                throw new \Exception("Unsupported tool type: {$toolType}");
        }
    }

    /**
     * Process job based on tool type (legacy method)
     */
    private function processByToolType($job, $userId = null)
    {
        $toolType = $job['tool_type'];
        $input = $job['input'];
        $options = $job['options'];

        switch ($toolType) {
            case 'summarize':
                return $this->processSummarizeJob($input, $options, $userId);
            
            case 'math':
                return $this->processMathJob($input, $options);
            
            case 'flashcards':
                return $this->processFlashcardsJob($input, $options);
            
            case 'presentations':
                return $this->processPresentationsJob($input, $options);
            
            case 'document_chat':
                return $this->processDocumentChatJob($input, $options);
            
            default:
                throw new \Exception("Unsupported tool type: {$toolType}");
        }
    }

    /**
     * Process summarize job with detailed stage tracking
     */
    private function processSummarizeJobWithStages($jobId, $input, $options, $userId = null)
    {
        try {
            $contentType = $input['content_type'];
            $source = $input['source'];

            // Stage 1: Content Analysis
            $this->updateJob($jobId, [
                'stage' => 'analyzing_content',
                'progress' => 10
            ]);
            $this->addLog($jobId, "Analyzing content type: {$contentType}", 'info');

            switch ($contentType) {
                case 'text':
                    // Pass userId in options
                    $options['user_id'] = $userId;
                    return $this->processTextSummarizationWithStages($jobId, $source['data'], $options);
                
                case 'link':
                    return $this->processLinkSummarizationWithStages($jobId, $source['data'], $options, $userId);
                
                case 'pdf':
                case 'image':
                case 'audio':
                case 'video':
                    return $this->processFileSummarizationWithStages($jobId, $source['data'], $options);
                
                default:
                    $this->failJob($jobId, "Unsupported content type: {$contentType}");
                    return ['success' => false, 'error' => "Unsupported content type: {$contentType}"];
            }
        } catch (\Exception $e) {
            $this->failJob($jobId, $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process summarize job (legacy method)
     */
    private function processSummarizeJob($input, $options, $userId = null)
    {
        try {
            $contentType = $input['content_type'];
            $source = $input['source'];

            switch ($contentType) {
                case 'text':
                    return $this->processTextSummarization($source['data'], $options);
                
                case 'link':
                    return $this->processLinkSummarization($source['data'], $options, $userId);
                
                case 'pdf':
                case 'image':
                case 'audio':
                case 'video':
                    return $this->processFileSummarization($source['data'], $options);
                
                default:
                    return ['success' => false, 'error' => "Unsupported content type: {$contentType}"];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process text summarization
     */
    private function processTextSummarization($text, $options)
    {
        $result = $this->getUniversalFileModule()->getAIProcessingModule()->summarize($text, $options);
        
        return [
            'success' => true,
            'data' => $result,
            'metadata' => [
                'file_count' => 0,
                'tokens_used' => strlen($text) / 4,
                'confidence_score' => $result['confidence_score'] ?? 0.8
            ]
        ];
    }

    /**
     * Process link summarization (web or YouTube)
     */
    private function processLinkSummarization($url, $options, $userId = null)
    {
        // Check if it's a YouTube URL
        if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
            return $this->processYouTubeSummarization($url, $options, $userId);
        } else {
            return $this->processWebLinkSummarization($url, $options);
        }
    }

    /**
     * Process YouTube video summarization
     */
    private function processYouTubeSummarization($url, $options, $userId = null)
    {
        try {
            // Set timeout for YouTube processing
            set_time_limit(120); // 2 minutes timeout
            
            // Use UnifiedProcessingService for YouTube processing
            $unifiedService = app(\App\Services\Modules\UnifiedProcessingService::class);
            $result = $unifiedService->processYouTubeVideo($url, $options, $userId);
            
            return [
                'success' => true,
                'data' => $result,
                'metadata' => [
                    'file_count' => 0,
                    'tokens_used' => $result['metadata']['tokens_used'] ?? 0,
                    'confidence_score' => $result['metadata']['confidence'] ?? 0.8,
                    'source_type' => 'youtube'
                ]
            ];
        } catch (\Exception $e) {
            Log::error('YouTube processing error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'YouTube processing failed: ' . $e->getMessage(),
                'metadata' => [
                    'file_count' => 0,
                    'tokens_used' => 0,
                    'confidence_score' => 0,
                    'source_type' => 'youtube'
                ]
            ];
        }
    }

    /**
     * Process web link summarization
     */
    private function processWebLinkSummarization($url, $options)
    {
        // Use WebScrapingService for web content
        $webScrapingService = app(\App\Services\WebScrapingService::class);
        $extractionResult = $webScrapingService->extractContent($url);
        
        if (!$extractionResult['success']) {
            return ['success' => false, 'error' => 'Failed to extract content from URL: ' . ($extractionResult['error'] ?? 'Unknown error')];
        }

        $content = $extractionResult['content'];
        if (empty($content)) {
            return ['success' => false, 'error' => 'No content found on the webpage'];
        }

        $result = $this->getUniversalFileModule()->getAIProcessingModule()->summarize($content, $options);
        
        return [
            'success' => true,
            'data' => $result,
            'metadata' => [
                'file_count' => 0,
                'tokens_used' => strlen($content) / 4,
                'confidence_score' => $result['confidence_score'] ?? 0.8,
                'source_type' => 'web'
            ]
        ];
    }

    /**
     * Process file summarization
     */
    private function processFileSummarization($fileId, $options)
    {
        $result = $this->getUniversalFileModule()->processFile($fileId, 'summarize', $options);
        
        if (!$result['success']) {
            return ['success' => false, 'error' => $result['error']];
        }

        return [
            'success' => true,
            'data' => $result['result'],
            'metadata' => [
                'file_count' => 1,
                'tokens_used' => $result['metadata']['tokens_used'] ?? 0,
                'confidence_score' => $result['metadata']['confidence'] ?? 0.8
            ]
        ];
    }

    /**
     * Process math job
     */
    private function processMathJob($input, $options)
    {
        try {
            if (isset($input['file_id'])) {
                // Image-based math problem
                $result = $this->getUniversalFileModule()->processFile($input['file_id'], 'math', $options);
                
                if (!$result['success']) {
                    return ['success' => false, 'error' => $result['error']];
                }

                return [
                    'success' => true,
                    'data' => $result['result'],
                    'metadata' => [
                        'file_count' => 1,
                        'problem_type' => 'image',
                        'confidence_score' => 0.9
                    ]
                ];
            } else {
                // Text-based math problem
                $problemData = [
                    'problem_text' => $input['text'],
                    'problem_type' => 'text',
                    'subject_area' => $options['subject_area'] ?? 'general',
                    'difficulty_level' => $options['difficulty_level'] ?? 'intermediate'
                ];
                $result = $this->getUniversalFileModule()->aiMathService->solveMathProblem($problemData, $input['user_id'] ?? 1);
                
                return [
                    'success' => true,
                    'data' => $result,
                    'metadata' => [
                        'file_count' => 0,
                        'problem_type' => 'text',
                        'confidence_score' => 0.95
                    ]
                ];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process flashcards job
     */
    private function processFlashcardsJob($input, $options)
    {
        try {
            if (isset($input['file_id'])) {
                // File-based flashcard generation
                $result = $this->getUniversalFileModule()->processFile($input['file_id'], 'flashcards', $options);
                
                if (!$result['success']) {
                    return ['success' => false, 'error' => $result['error']];
                }

                return [
                    'success' => true,
                    'data' => $result['result'],
                    'metadata' => [
                        'file_count' => 1,
                        'flashcard_count' => count($result['result']['flashcards'] ?? []),
                        'confidence_score' => 0.9
                    ]
                ];
            } else {
                // Text-based flashcard generation
                $count = $options['count'] ?? 5;
                $result = $this->getUniversalFileModule()->flashcardService->generateFlashcards($input['text'], $count, $options);
                
                return [
                    'success' => true,
                    'data' => $result,
                    'metadata' => [
                        'file_count' => 0,
                        'flashcard_count' => count($result['flashcards'] ?? []),
                        'confidence_score' => 0.9
                    ]
                ];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process presentations job
     */
    private function processPresentationsJob($input, $options)
    {
        try {
            if (isset($input['file_id'])) {
                // File-based presentation generation
                $result = $this->getUniversalFileModule()->processFile($input['file_id'], 'presentations', $options);
                
                if (!$result['success']) {
                    return ['success' => false, 'error' => $result['error']];
                }

                return [
                    'success' => true,
                    'data' => $result['result'],
                    'metadata' => [
                        'file_count' => 1,
                        'slide_count' => count($result['result']['presentation_data']['slides'] ?? []),
                        'confidence_score' => 0.9
                    ]
                ];
            } else {
                // Text-based presentation generation
                // For text-based presentations, we don't need to create an AI result
                // Just process the outline generation directly
                
                $result = $this->getUniversalFileModule()->presentationService->generateOutline([
                    'text' => $input['text'],
                    'title' => $options['title'] ?? 'Generated Presentation',
                    'slides_count' => $options['slides_count'] ?? 5
                ], $input['user_id'] ?? 1);
                
                return [
                    'success' => true,
                    'data' => $result,
                    'metadata' => [
                        'file_count' => 0,
                        'slide_count' => count($result['presentation']['slides'] ?? []),
                        'confidence_score' => 0.9
                    ]
                ];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process document chat job
     */
    private function processDocumentChatJob($input, $options)
    {
        try {
            // Document chat requires a file_id
            if (!isset($input['file_id'])) {
                return ['success' => false, 'error' => 'Document chat requires a file_id'];
            }
            
            // Document chat doesn't need file processing, just content extraction
            $file = \App\Models\FileUpload::find($input['file_id']);
            if (!$file) {
                return ['success' => false, 'error' => 'File not found'];
            }
            
            $result = $this->getUniversalFileModule()->extractContent($file, $options);
            
            if (!$result['success']) {
                return ['success' => false, 'error' => $result['error']];
            }

            return [
                'success' => true,
                'data' => [
                    'document_content' => $result['data']['text'],
                    'metadata' => $result['data']['metadata']
                ],
                'metadata' => [
                    'file_count' => 1,
                    'word_count' => str_word_count($result['data']['text']),
                    'confidence_score' => 0.95
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get user's jobs
     */
    public function getUserJobs($userId, $toolType = null, $status = null, $perPage = 15)
    {
        // This would typically query a database, but for now we'll use cache
        $jobs = [];
        $pattern = "universal_job_*";
        
        // In a real implementation, you'd store job IDs in a user index
        // For now, we'll return a mock response
        return [
            'jobs' => $jobs,
            'pagination' => [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $perPage,
                'total' => count($jobs)
            ]
        ];
    }

    /**
     * Delete job
     */
    public function deleteJob($jobId)
    {
        return Cache::forget("universal_job_{$jobId}");
    }

    /**
     * Get job statistics
     */
    public function getJobStats($userId = null)
    {
        // This would typically query a database for real statistics
        return [
            'total_jobs' => 0,
            'completed_jobs' => 0,
            'failed_jobs' => 0,
            'running_jobs' => 0,
            'average_processing_time' => 0,
            'success_rate' => 0.0
        ];
    }

    /**
     * Process text summarization with stages
     */
    private function processTextSummarizationWithStages($jobId, $text, $options)
    {
        try {
            $userId = $options['user_id'] ?? null;
            
            // Stage 2: Text Processing
            $this->updateJob($jobId, [
                'stage' => 'processing_text',
                'progress' => 20
            ]);
            $this->addLog($jobId, "Processing text content", 'info', [
                'text_length' => strlen($text),
                'word_count' => str_word_count($text)
            ]);

            // Stage 3: Ingest text into Document Intelligence
            $this->updateJob($jobId, [
                'stage' => 'ingesting_text',
                'progress' => 40
            ]);
            $this->addLog($jobId, "Ingesting text into Document Intelligence", 'info', [
                'text_length' => strlen($text)
            ]);
            
            $docIntelligenceModule = app(\App\Services\Modules\DocumentIntelligenceModule::class);
            
            // Note: filename must be "summary.txt" as per Document Intelligence API requirements
            $ingestResult = $docIntelligenceModule->ingestText($text, [
                'filename' => 'summary.txt',
                'metadata' => [
                    'source' => 'text',
                    'user_id' => $userId
                ],
                'force_fallback' => true,
                'llm_model' => $options['llm_model'] ?? 'llama3'
            ]);
            
            if (!$ingestResult['success'] || empty($ingestResult['doc_id'])) {
                $errorType = $ingestResult['error_type'] ?? 'unknown_error';
                $originalError = $ingestResult['error'] ?? 'No doc_id returned';
                $originalErrorDetails = $ingestResult['original_error'] ?? null;
                
                $errorMessage = $this->buildIngestionErrorMessage($errorType, $originalError, $originalErrorDetails, 'text');
                
                $this->addLog($jobId, $errorMessage, 'error', [
                    'error_type' => $errorType,
                    'ingest_result' => $ingestResult,
                    'text_length' => strlen($text),
                    'text_preview' => substr($text, 0, 200),
                    'has_doc_id' => !empty($ingestResult['doc_id']),
                    'has_job_id' => !empty($ingestResult['job_id']),
                    'transcript_source' => 'text'
                ]);
                
                $this->failJob($jobId, $errorMessage);
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'error_type' => $errorType,
                    'error_details' => [
                        'error_type' => $errorType,
                        'original_error' => $originalError,
                        'text_length' => strlen($text),
                        'has_content' => !empty(trim($text))
                    ],
                    'metadata' => [
                        'file_count' => 0,
                        'tokens_used' => 0,
                        'confidence_score' => 0,
                        'source_type' => 'text'
                    ]
                ];
            }
            
            $docId = $ingestResult['doc_id'];
            $ingestJobId = $ingestResult['job_id'] ?? null;
            
            // Poll ingestion job until complete
            if ($ingestJobId) {
                $this->updateJob($jobId, [
                    'stage' => 'polling_ingestion',
                    'progress' => 50
                ]);
                $pollResult = $docIntelligenceModule->pollJobCompletion($ingestJobId, 300, 2);
                if (($pollResult['status'] ?? '') !== 'completed' && ($pollResult['status'] ?? '') !== 'succeeded') {
                    $this->addLog($jobId, "Ingestion job did not complete successfully", 'warning', [
                        'status' => $pollResult['status'] ?? 'unknown'
                    ]);
                }
            }
            
            // Stage 4: Extract summary using Document Intelligence
            $this->updateJob($jobId, [
                'stage' => 'extracting_summary',
                'progress' => 60
            ]);
            $this->addLog($jobId, "Extracting summary from Document Intelligence", 'info', ['doc_id' => $docId]);
            
            $llmModel = $options['llm_model'] ?? 'deepseek-chat';
            $summaryQuery = "Please provide a comprehensive summary of this content. " .
                          "Include key points, main themes, and important details. " .
                          "Format the response as follows:\n" .
                          "1. Start with a summary paragraph (2-3 sentences)\n" .
                          "2. Then provide a bulleted list of key points (use • or - for bullets)\n" .
                          "3. Each key point should be a single sentence or short phrase";
            
            $summaryResult = $docIntelligenceModule->answer(
                $summaryQuery,
                [
                    'doc_ids' => [$docId],
                    'llm_model' => $llmModel,
                    'max_tokens' => 2000,
                    'top_k' => 10,
                    'temperature' => 0.7,
                    'force_fallback' => true
                ]
            );
            
            if (!$summaryResult['success'] || empty($summaryResult['answer'])) {
                $errorMessage = "Summary extraction failed: " . ($summaryResult['error'] ?? 'No answer returned');
                $this->failJob($jobId, $errorMessage);
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'doc_id' => $docId,
                    'conversation_id' => $this->getOrCreateConversationId($docId, $userId),
                    'metadata' => [
                        'file_count' => 0,
                        'tokens_used' => 0,
                        'confidence_score' => 0,
                        'source_type' => 'text'
                    ]
                ];
            }
            
            // Extract summary and key points from answer
            $summaryText = $summaryResult['answer'] ?? '';
            $keyPoints = [];
            
            // Try to extract key points from summary text
            if (preg_match_all('/[•\-\*]\s*(.+?)(?=\n[•\-\*]|\n\n|$)/s', $summaryText, $matches)) {
                $keyPoints = array_map('trim', $matches[1]);
            } elseif (preg_match_all('/\d+\.\s*(.+?)(?=\n\d+\.|\n\n|$)/s', $summaryText, $matches)) {
                $keyPoints = array_map('trim', $matches[1]);
            }
            
            // If no key points extracted, split summary into sentences
            if (empty($keyPoints) && !empty($summaryText)) {
                $sentences = preg_split('/[.!?]+\s+/', $summaryText);
                $keyPoints = array_slice(array_filter(array_map('trim', $sentences)), 0, 10);
            }
            
            // Stage 5: Extract chapters
            $chapters = $this->extractChaptersFromDocumentIntelligence($docId, $jobId, ['llm_model' => $llmModel]);
            
            // Get conversation ID
            $conversationId = $this->getOrCreateConversationId($docId, $userId);
            
            // Stage 6: Finalizing
            $this->updateJob($jobId, [
                'stage' => 'finalizing',
                'progress' => 95
            ]);
            $this->addLog($jobId, "Text summarization completed", 'info', [
                'summary_length' => strlen($summaryText),
                'chapters_count' => count($chapters)
            ]);
            
            // Use standardized response
            return $this->standardizeSummarizationResponse(
                [
                    'summary' => $summaryText,
                    'key_points' => $keyPoints,
                    'confidence_score' => 0.9,
                    'model_used' => $llmModel,
                    'sources' => $summaryResult['sources'] ?? []
                ],
                'text',
                ['article_text' => $text], // Bundle is just the input text
                $docId,
                $conversationId,
                $chapters,
                [
                    'text_length' => strlen($text),
                    'word_count' => str_word_count($text)
                ]
            );
        } catch (\Exception $e) {
            $this->failJob($jobId, "Text processing failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process link summarization with stages
     */
    private function processLinkSummarizationWithStages($jobId, $url, $options, $userId = null)
    {
        try {
            // Stage 2: URL Analysis
            $this->updateJob($jobId, [
                'stage' => 'analyzing_url',
                'progress' => 20
            ]);
            $this->addLog($jobId, "Analyzing URL: {$url}", 'info');

            // Check if it's a YouTube URL
            if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
                return $this->processYouTubeSummarizationWithStages($jobId, $url, $options, $userId);
            } else {
                return $this->processWebLinkSummarizationWithStages($jobId, $url, $options, $userId);
            }
        } catch (\Exception $e) {
            $this->failJob($jobId, "Link processing failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process YouTube video summarization with stages
     */
    private function processYouTubeSummarizationWithStages($jobId, $url, $options, $userId = null)
    {
        try {
            Log::info("Processing YouTube summarization", [
                'job_id' => $jobId,
                'url' => $url,
                'options' => $options
            ]);
            // Stage 2: Video Processing
            $this->updateJob($jobId, [
                'stage' => 'processing_video',
                'progress' => 20
            ]);
            $this->addLog($jobId, "Processing YouTube video", 'info', ['url' => $url]);

            // Stage 3: Transcription
            $this->updateJob($jobId, [
                'stage' => 'transcribing',
                'progress' => 40
            ]);
            $this->addLog($jobId, "Transcribing video content", 'info');

            // Use TranscriberModule to get transcript
            // Use 'bundle' format as it reliably returns article_text from BrightData API
            $transcriberModule = app(\App\Services\Modules\TranscriberModule::class);
            
            // Ensure format is valid (API only accepts: plain, json, srt, article, bundle)
            $requestFormat = $options['format'] ?? 'bundle';
            $validFormats = ['plain', 'json', 'srt', 'article', 'bundle'];
            if (!in_array($requestFormat, $validFormats)) {
                $this->addLog($jobId, "Invalid format '{$requestFormat}', defaulting to 'bundle'", 'warning');
                $requestFormat = 'bundle';
            }
            
            $transcriptionResult = $transcriberModule->transcribeVideo($url, [
                'format' => $requestFormat, // Default to 'bundle' for better compatibility
                'language' => $options['language'] ?? 'auto'
            ]);

            if (!$transcriptionResult['success']) {
                $errorMessage = $transcriptionResult['error'] ?? 'Unknown error';
                $errorDetails = $transcriptionResult['error_details'] ?? [
                    'error_type' => 'transcription_error',
                    'error_source' => 'TranscriberModule',
                    'context' => ['video_url' => $url]
                ];
                
                // Merge error_details if available
                if (isset($transcriptionResult['error_details'])) {
                    $errorDetails = array_merge($errorDetails, $transcriptionResult['error_details']);
                }
                
                $fullErrorMessage = "Transcription failed: " . $errorMessage;
                $this->failJob($jobId, $fullErrorMessage);
                $this->addLog($jobId, $fullErrorMessage, 'error', $errorDetails);
                
                return [
                    'success' => false,
                    'error' => 'Failed to transcribe YouTube video: ' . $errorMessage,
                    'error_details' => $errorDetails,
                    'metadata' => [
                        'file_count' => 0,
                        'tokens_used' => 0,
                        'confidence_score' => 0,
                        'source_type' => 'youtube'
                    ]
                ];
            }

            // Log transcription result for debugging
            $this->addLog($jobId, "Transcription result received", 'info', [
                'has_transcript' => isset($transcriptionResult['transcript']),
                'has_article_text' => isset($transcriptionResult['article_text']),
                'transcript_length' => strlen($transcriptionResult['transcript'] ?? ''),
                'article_text_length' => strlen($transcriptionResult['article_text'] ?? ''),
                'result_keys' => array_keys($transcriptionResult),
                'has_job_key' => isset($transcriptionResult['job_key'])
            ]);
            
            $transcript = $transcriptionResult['transcript'] ?? '';
            if (empty(trim($transcript))) {
                $errorMessage = "No transcript content available from video. The video may not have captions/transcripts available, or the transcriber service was unable to extract them.";
                $this->failJob($jobId, $errorMessage);
                $this->addLog($jobId, $errorMessage, 'error', [
                    'video_url' => $url,
                    'video_id' => $transcriptionResult['video_id'] ?? null,
                    'transcription_result_keys' => array_keys($transcriptionResult)
                ]);
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'error_details' => [
                        'error_type' => 'empty_transcript',
                        'error_source' => 'TranscriberModule',
                        'possible_causes' => [
                            'Video does not have captions/transcripts enabled',
                            'Video captions are not available in the requested language',
                            'Transcriber service returned empty content',
                            'Video may be private or restricted'
                        ],
                        'context' => [
                            'video_url' => $url,
                            'video_id' => $transcriptionResult['video_id'] ?? null
                        ]
                    ],
                    'metadata' => [
                        'file_count' => 0,
                        'tokens_used' => 0,
                        'confidence_score' => 0,
                        'source_type' => 'youtube'
                    ]
                ];
            }

            // Get article text for ingestion (prefer article_text from bundle, fallback to transcript)
            $articleText = $transcriptionResult['article_text'] ?? $transcript;
            
            // Ensure we have actual content before proceeding
            $articleTextTrimmed = trim($articleText);
            if (empty($articleTextTrimmed)) {
                $errorMessage = "No article text or transcript available for ingestion. Transcript length: " . strlen($transcript) . ", Article text length: " . strlen($transcriptionResult['article_text'] ?? '');
                $this->addLog($jobId, $errorMessage, 'error', [
                    'transcription_result' => $transcriptionResult,
                    'transcript' => substr($transcript, 0, 100),
                    'article_text' => substr($transcriptionResult['article_text'] ?? '', 0, 100)
                ]);
                $this->failJob($jobId, $errorMessage);
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'metadata' => [
                        'file_count' => 0,
                        'tokens_used' => 0,
                        'confidence_score' => 0,
                        'source_type' => 'youtube'
                    ]
                ];
            }
            
            // Validate minimum content length (at least 10 characters)
            if (strlen($articleTextTrimmed) < 10) {
                $errorMessage = "Transcript content too short for ingestion. Length: " . strlen($articleTextTrimmed);
                $this->addLog($jobId, $errorMessage, 'error', [
                    'content_preview' => substr($articleTextTrimmed, 0, 200)
                ]);
                $this->failJob($jobId, $errorMessage);
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'metadata' => [
                        'file_count' => 0,
                        'tokens_used' => 0,
                        'confidence_score' => 0,
                        'source_type' => 'youtube'
                    ]
                ];
            }
            
            // Stage 4: Ingest text into Document Intelligence
            $this->updateJob($jobId, [
                'stage' => 'ingesting_text',
                'progress' => 50
            ]);
            $this->addLog($jobId, "Ingesting transcript into Document Intelligence", 'info', [
                'text_length' => strlen($articleTextTrimmed),
                'text_preview' => substr($articleTextTrimmed, 0, 100) . '...'
            ]);
            
            $docIntelligenceModule = app(\App\Services\Modules\DocumentIntelligenceModule::class);
            $videoId = $transcriptionResult['video_id'] ?? 'unknown';
            
            // Use trimmed content for ingestion
            // Note: filename must be "summary.txt" as per Document Intelligence API requirements
            $ingestResult = $docIntelligenceModule->ingestText($articleTextTrimmed, [
                'filename' => 'summary.txt',
                'metadata' => [
                    'source' => 'youtube',
                    'video_id' => $videoId,
                    'user_id' => $userId,
                    'language' => $transcriptionResult['language'] ?? null
                ],
                'force_fallback' => true,
                'llm_model' => $options['llm_model'] ?? 'llama3'
            ]);
            
            if (!$ingestResult['success'] || empty($ingestResult['doc_id'])) {
                $errorType = $ingestResult['error_type'] ?? 'unknown_error';
                $originalError = $ingestResult['error'] ?? 'No doc_id returned';
                $originalErrorDetails = $ingestResult['original_error'] ?? null;
                
                // Build descriptive error message
                $errorMessage = $this->buildIngestionErrorMessage($errorType, $originalError, $originalErrorDetails, 'youtube transcript');
                
                $this->addLog($jobId, $errorMessage, 'error', [
                    'error_type' => $errorType,
                    'ingest_result' => $ingestResult,
                    'text_length' => strlen($articleTextTrimmed),
                    'text_preview' => substr($articleTextTrimmed, 0, 200),
                    'has_doc_id' => !empty($ingestResult['doc_id']),
                    'has_job_id' => !empty($ingestResult['job_id']),
                    'video_id' => $videoId,
                    'transcript_source' => 'youtube'
                ]);
                
                $this->failJob($jobId, $errorMessage);
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'error_type' => $errorType,
                    'error_details' => [
                        'error_type' => $errorType,
                        'original_error' => $originalError,
                        'text_length' => strlen($articleTextTrimmed),
                        'has_content' => !empty($articleTextTrimmed),
                        'video_id' => $videoId
                    ],
                    'metadata' => [
                        'file_count' => 0,
                        'tokens_used' => 0,
                        'confidence_score' => 0,
                        'source_type' => 'youtube'
                    ]
                ];
            }
            
            $docId = $ingestResult['doc_id'];
            $ingestJobId = $ingestResult['job_id'] ?? null;
            
            // Poll ingestion job until complete
            if ($ingestJobId) {
                $this->updateJob($jobId, [
                    'stage' => 'polling_ingestion',
                    'progress' => 60
                ]);
                $this->addLog($jobId, "Polling ingestion job until complete", 'info', [
                    'ingest_job_id' => $ingestJobId
                ]);
                
                $pollResult = $docIntelligenceModule->pollJobCompletion($ingestJobId, 300, 2);
                if (($pollResult['status'] ?? '') !== 'completed' && ($pollResult['status'] ?? '') !== 'succeeded') {
                    $this->addLog($jobId, "Ingestion job did not complete successfully", 'warning', [
                        'status' => $pollResult['status'] ?? 'unknown'
                    ]);
                }
            }
            
            // Stage 5: Extract summary using Document Intelligence
            $this->updateJob($jobId, [
                'stage' => 'extracting_summary',
                'progress' => 70
            ]);
            $this->addLog($jobId, "Extracting summary from Document Intelligence", 'info', ['doc_id' => $docId]);
            
            $llmModel = $options['llm_model'] ?? 'deepseek-chat';
            $summaryQuery = "Please provide a comprehensive summary of this content. " .
                          "Include key points, main themes, and important details. " .
                          "Format the response as follows:\n" .
                          "1. Start with a summary paragraph (2-3 sentences)\n" .
                          "2. Then provide a bulleted list of key points (use • or - for bullets)\n" .
                          "3. Each key point should be a single sentence or short phrase";
            
            $summaryResult = $docIntelligenceModule->answer(
                $summaryQuery,
                [
                    'doc_ids' => [$docId],
                    'llm_model' => $llmModel,
                    'max_tokens' => 2000,
                    'top_k' => 10,
                    'temperature' => 0.7,
                    'force_fallback' => true
                ]
            );
            
            if (!$summaryResult['success'] || empty($summaryResult['answer'])) {
                $errorMessage = "Summary extraction failed: " . ($summaryResult['error'] ?? 'No answer returned');
                $this->failJob($jobId, $errorMessage);
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'doc_id' => $docId,
                    'conversation_id' => $this->getOrCreateConversationId($docId, $userId),
                    'metadata' => [
                        'file_count' => 0,
                        'tokens_used' => 0,
                        'confidence_score' => 0,
                        'source_type' => 'youtube'
                    ]
                ];
            }
            
            // Extract summary and key points from answer
            $summaryText = $summaryResult['answer'] ?? '';
            $keyPoints = [];
            
            // Try to extract key points from summary text (look for bullet points)
            if (preg_match_all('/[•\-\*]\s*(.+?)(?=\n[•\-\*]|\n\n|$)/s', $summaryText, $matches)) {
                $keyPoints = array_map('trim', $matches[1]);
            } elseif (preg_match_all('/\d+\.\s*(.+?)(?=\n\d+\.|\n\n|$)/s', $summaryText, $matches)) {
                $keyPoints = array_map('trim', $matches[1]);
            }
            
            // If no key points extracted, split summary into sentences for key points
            if (empty($keyPoints) && !empty($summaryText)) {
                $sentences = preg_split('/[.!?]+\s+/', $summaryText);
                $keyPoints = array_slice(array_filter(array_map('trim', $sentences)), 0, 10);
            }
            
            // Stage 6: Extract chapters
            $chapters = $this->extractChaptersFromDocumentIntelligence($docId, $jobId, ['llm_model' => $llmModel]);
            
            // Get conversation ID
            $conversationId = $this->getOrCreateConversationId($docId, $userId);
            
            // Prepare bundle data
            $bundleData = [];
            if (isset($transcriptionResult['article_text'])) {
                $bundleData['article_text'] = $transcriptionResult['article_text'];
            } elseif (!empty($transcript)) {
                $bundleData['article_text'] = $transcript;
            }
            if (isset($transcriptionResult['json_items'])) {
                $bundleData['json_items'] = $transcriptionResult['json_items'];
            }
            if (isset($transcriptionResult['transcript_json'])) {
                $bundleData['transcript_json'] = $transcriptionResult['transcript_json'];
            }
            
            // Stage 7: Finalizing
            $this->updateJob($jobId, [
                'stage' => 'finalizing',
                'progress' => 95
            ]);
            $this->addLog($jobId, "YouTube processing completed", 'info', [
                'video_id' => $videoId,
                'summary_length' => strlen($summaryText),
                'chapters_count' => count($chapters)
            ]);
            
            // Use standardized response
            return $this->standardizeSummarizationResponse(
                [
                    'summary' => $summaryText,
                    'key_points' => $keyPoints,
                    'confidence_score' => 0.9, // Document Intelligence typically has high confidence
                    'model_used' => $llmModel,
                    'sources' => $summaryResult['sources'] ?? []
                ],
                'youtube',
                !empty($bundleData) ? $bundleData : null,
                $docId,
                $conversationId,
                $chapters,
                [
                    'video_id' => $videoId,
                    'language' => $transcriptionResult['language'] ?? null,
                    'format' => $transcriptionResult['format'] ?? null,
                    'transcript_length' => strlen($articleText)
                ]
            );
        } catch (\Exception $e) {
            $errorMessage = "YouTube processing failed: " . $e->getMessage();
            $errorDetails = [
                'error_type' => 'processing_error',
                'error_source' => 'processYouTubeSummarizationWithStages',
                'exception_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'context' => ['video_url' => $url]
            ];
            
            Log::error("YouTube summarization exception", [
                'job_id' => $jobId,
                'error' => $errorMessage,
                'details' => $errorDetails,
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->failJob($jobId, $errorMessage);
            $this->addLog($jobId, $errorMessage, 'error', $errorDetails);
            
            return [
                'success' => false,
                'error' => $errorMessage,
                'error_details' => $errorDetails,
                'metadata' => [
                    'file_count' => 0,
                    'tokens_used' => 0,
                    'confidence_score' => 0,
                    'source_type' => 'youtube'
                ]
            ];
        }
    }

    /**
     * Process web link summarization with stages
     */
    private function processWebLinkSummarizationWithStages($jobId, $url, $options, $userId = null)
    {
        try {
            // Stage 3: Web Scraping
            $this->updateJob($jobId, [
                'stage' => 'scraping_content',
                'progress' => 40
            ]);
            $this->addLog($jobId, "Scraping web content from URL", 'info', ['url' => $url]);

            // Use WebScrapingService for web content
            $webScrapingService = app(\App\Services\WebScrapingService::class);
            $extractionResult = $webScrapingService->extractContent($url);
            
            if (!$extractionResult['success']) {
                $this->failJob($jobId, "Failed to extract content from URL: " . ($extractionResult['error'] ?? 'Unknown error'));
                return ['success' => false, 'error' => 'Failed to extract content from URL: ' . ($extractionResult['error'] ?? 'Unknown error')];
            }

            $content = $extractionResult['content'];
            if (empty($content)) {
                $this->failJob($jobId, "No content found on the webpage");
                return ['success' => false, 'error' => 'No content found on the webpage'];
            }

            $userId = $options['user_id'] ?? null;

            // Stage 4: Ingest content into Document Intelligence
            $this->updateJob($jobId, [
                'stage' => 'ingesting_text',
                'progress' => 50
            ]);
            $this->addLog($jobId, "Ingesting scraped content into Document Intelligence", 'info', [
                'content_length' => strlen($content)
            ]);
            
            $docIntelligenceModule = app(\App\Services\Modules\DocumentIntelligenceModule::class);
            
            $ingestResult = $docIntelligenceModule->ingestText($content, [
                'filename' => 'web_content.txt',
                'metadata' => [
                    'source' => 'web',
                    'url' => $url,
                    'user_id' => $userId
                ]
            ]);
            
            if (!$ingestResult['success'] || empty($ingestResult['doc_id'])) {
                $errorType = $ingestResult['error_type'] ?? 'unknown_error';
                $originalError = $ingestResult['error'] ?? 'No doc_id returned';
                $originalErrorDetails = $ingestResult['original_error'] ?? null;
                
                $errorMessage = $this->buildIngestionErrorMessage($errorType, $originalError, $originalErrorDetails, 'web link');
                
                $this->addLog($jobId, $errorMessage, 'error', [
                    'error_type' => $errorType,
                    'ingest_result' => $ingestResult,
                    'content_length' => strlen($content),
                    'content_preview' => substr($content, 0, 200),
                    'has_doc_id' => !empty($ingestResult['doc_id']),
                    'has_job_id' => !empty($ingestResult['job_id']),
                    'url' => $url,
                    'transcript_source' => 'web'
                ]);
                
                $this->failJob($jobId, $errorMessage);
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'error_type' => $errorType,
                    'error_details' => [
                        'error_type' => $errorType,
                        'original_error' => $originalError,
                        'content_length' => strlen($content),
                        'has_content' => !empty(trim($content)),
                        'url' => $url
                    ],
                    'metadata' => [
                        'file_count' => 0,
                        'tokens_used' => 0,
                        'confidence_score' => 0,
                        'source_type' => 'web'
                    ]
                ];
            }
            
            $docId = $ingestResult['doc_id'];
            $ingestJobId = $ingestResult['job_id'] ?? null;
            
            // Poll ingestion job until complete
            if ($ingestJobId) {
                $this->updateJob($jobId, [
                    'stage' => 'polling_ingestion',
                    'progress' => 60
                ]);
                $pollResult = $docIntelligenceModule->pollJobCompletion($ingestJobId, 300, 2);
                if (($pollResult['status'] ?? '') !== 'completed' && ($pollResult['status'] ?? '') !== 'succeeded') {
                    $this->addLog($jobId, "Ingestion job did not complete successfully", 'warning', [
                        'status' => $pollResult['status'] ?? 'unknown'
                    ]);
                }
            }
            
            // Stage 5: Extract summary using Document Intelligence
            $this->updateJob($jobId, [
                'stage' => 'extracting_summary',
                'progress' => 70
            ]);
            $this->addLog($jobId, "Extracting summary from Document Intelligence", 'info', ['doc_id' => $docId]);
            
            $llmModel = $options['llm_model'] ?? 'deepseek-chat';
            $summaryQuery = "Please provide a comprehensive summary of this content. " .
                          "Include key points, main themes, and important details. " .
                          "Format the response as follows:\n" .
                          "1. Start with a summary paragraph (2-3 sentences)\n" .
                          "2. Then provide a bulleted list of key points (use • or - for bullets)\n" .
                          "3. Each key point should be a single sentence or short phrase";
            
            $summaryResult = $docIntelligenceModule->answer(
                $summaryQuery,
                [
                    'doc_ids' => [$docId],
                    'llm_model' => $llmModel,
                    'max_tokens' => 2000,
                    'top_k' => 10,
                    'temperature' => 0.7,
                    'force_fallback' => true
                ]
            );
            
            if (!$summaryResult['success'] || empty($summaryResult['answer'])) {
                $errorMessage = "Summary extraction failed: " . ($summaryResult['error'] ?? 'No answer returned');
                $this->failJob($jobId, $errorMessage);
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'doc_id' => $docId,
                    'conversation_id' => $this->getOrCreateConversationId($docId, $userId),
                    'metadata' => [
                        'file_count' => 0,
                        'tokens_used' => 0,
                        'confidence_score' => 0,
                        'source_type' => 'web'
                    ]
                ];
            }
            
            // Extract summary and key points from answer
            $summaryText = $summaryResult['answer'] ?? '';
            $keyPoints = [];
            
            // Try to extract key points from summary text
            if (preg_match_all('/[•\-\*]\s*(.+?)(?=\n[•\-\*]|\n\n|$)/s', $summaryText, $matches)) {
                $keyPoints = array_map('trim', $matches[1]);
            } elseif (preg_match_all('/\d+\.\s*(.+?)(?=\n\d+\.|\n\n|$)/s', $summaryText, $matches)) {
                $keyPoints = array_map('trim', $matches[1]);
            }
            
            // If no key points extracted, split summary into sentences
            if (empty($keyPoints) && !empty($summaryText)) {
                $sentences = preg_split('/[.!?]+\s+/', $summaryText);
                $keyPoints = array_slice(array_filter(array_map('trim', $sentences)), 0, 10);
            }
            
            // Stage 6: Extract chapters
            $chapters = $this->extractChaptersFromDocumentIntelligence($docId, $jobId, ['llm_model' => $llmModel]);
            
            // Get conversation ID
            $conversationId = $this->getOrCreateConversationId($docId, $userId);
            
            // Stage 7: Finalizing
            $this->updateJob($jobId, [
                'stage' => 'finalizing',
                'progress' => 95
            ]);
            $this->addLog($jobId, "Web content summarization completed", 'info', [
                'summary_length' => strlen($summaryText),
                'chapters_count' => count($chapters)
            ]);
            
            // Use standardized response
            return $this->standardizeSummarizationResponse(
                [
                    'summary' => $summaryText,
                    'key_points' => $keyPoints,
                    'confidence_score' => 0.9,
                    'model_used' => $llmModel,
                    'sources' => $summaryResult['sources'] ?? []
                ],
                'link',
                ['article_text' => $content], // Bundle is the scraped content
                $docId,
                $conversationId,
                $chapters,
                [
                    'url' => $url,
                    'content_length' => strlen($content),
                    'word_count' => str_word_count($content)
                ]
            );
        } catch (\Exception $e) {
            $this->failJob($jobId, "Web link processing failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process file summarization with stages
     * All file types now use Document Intelligence:
     * - Audio/Video: TranscriberModule → Document Intelligence (ingest text → summary + chapters)
     * - PDF/Doc/Image: Document Intelligence (ingest document → summary + chapters)
     */
    private function processFileSummarizationWithStages($jobId, $fileId, $options)
    {
        try {
            // Get file record
            $file = \App\Models\FileUpload::find($fileId);
            if (!$file) {
                $this->failJob($jobId, "File not found: {$fileId}");
                return ['success' => false, 'error' => "File not found: {$fileId}"];
            }

            $fileType = strtolower($file->file_type ?? '');
            $isAudioVideo = in_array($fileType, ['audio', 'video']);

            // Stage 2: File Processing
            $this->updateJob($jobId, [
                'stage' => 'processing_file',
                'progress' => 20
            ]);
            $this->addLog($jobId, "Processing uploaded file", 'info', [
                'file_id' => $fileId,
                'file_type' => $fileType,
                'processing_method' => 'document_intelligence' // All file types now use Document Intelligence
            ]);

            if ($isAudioVideo) {
                // Audio/Video flow: TranscriberModule → Document Intelligence
                return $this->processAudioVideoSummarizationWithStages($jobId, $file, $options);
            } else {
                // PDF/Doc/Image flow: Document Intelligence
                return $this->processDocumentFileSummarizationWithStages($jobId, $file, $options);
            }
        } catch (\Exception $e) {
            $this->failJob($jobId, "File processing failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_details' => [
                    'error_type' => 'processing_error',
                    'error_source' => 'processFileSummarizationWithStages',
                    'context' => ['file_id' => $fileId]
                ]
            ];
        }
    }

    /**
     * Process audio/video file summarization: TranscriberModule → Document Intelligence
     */
    private function processAudioVideoSummarizationWithStages($jobId, $file, $options)
    {
        try {
            // Stage 3: Transcription
            $this->updateJob($jobId, [
                'stage' => 'transcribing',
                'progress' => 40
            ]);
            $this->addLog($jobId, "Transcribing audio/video content", 'info', [
                'file_type' => $file->file_type,
                'file_name' => $file->original_name
            ]);

            // Use TranscriberModule to transcribe
            $transcriberModule = app(\App\Services\Modules\TranscriberModule::class);
            
            // Use production-ready file path resolution
            $filePath = $this->resolveFilePath($file, $jobId);
            
            if (!$filePath || !file_exists($filePath)) {
                $this->failJob($jobId, "File not found on disk. File path: {$file->file_path}");
                return [
                    'success' => false,
                    'error' => "File not found on disk. Please ensure the file exists and is accessible.",
                    'error_details' => [
                        'error_type' => 'file_not_found',
                        'error_source' => 'processAudioVideoSummarizationWithStages',
                        'file_path_column' => $file->file_path,
                        'file_id' => $file->id,
                        'stored_name' => $file->stored_name ?? null,
                        'original_name' => $file->original_name ?? null,
                        'hint' => 'Check if the file exists in storage/app/public or storage/app directories'
                    ]
                ];
            }

            // Use TranscriberModule's new transcribeFile method for direct file uploads
            $transcriptionResult = $transcriberModule->transcribeFile($filePath, [
                'format' => $options['format'] ?? 'bundle',
                'lang' => $options['language'] ?? 'auto',
                'include_meta' => $options['include_meta'] ?? true
            ]);

            if (!$transcriptionResult['success']) {
                $this->failJob($jobId, "Transcription failed: " . ($transcriptionResult['error'] ?? 'Unknown error'));
                return [
                    'success' => false,
                    'error' => 'Failed to transcribe audio/video: ' . ($transcriptionResult['error'] ?? 'Unknown error'),
                    'error_details' => [
                        'error_type' => 'transcription_error',
                        'error_source' => 'TranscriberModule',
                        'context' => ['file_id' => $file->id, 'file_name' => $file->original_name]
                    ]
                ];
            }

            // Check if transcription returned a job_key indicating async processing
            if (isset($transcriptionResult['job_key']) && empty($transcriptionResult['transcript'] ?? $transcriptionResult['subtitle_text'] ?? '')) {
                $this->updateJob($jobId, [
                    'stage' => 'polling_transcription',
                    'progress' => 45
                ]);
                $this->addLog($jobId, "Transcription is async, polling for completion", 'info', [
                    'job_key' => $transcriptionResult['job_key']
                ]);
                
                // Poll transcription job until complete
                $fileFormat = $options['format'] ?? 'bundle';
                $transcriptionResult = $this->pollTranscriptionJob($transcriptionResult['job_key'], $filePath, $fileFormat, $jobId);
                
                if (!$transcriptionResult['success']) {
                    $errorMessage = "Transcription polling failed: " . ($transcriptionResult['error'] ?? 'Unknown error');
                    $this->failJob($jobId, $errorMessage);
                    return [
                        'success' => false,
                        'error' => $errorMessage,
                        'metadata' => [
                            'file_count' => 1,
                            'tokens_used' => 0,
                            'confidence_score' => 0,
                            'source_type' => $file->file_type
                        ]
                    ];
                }
            }

            $transcript = $transcriptionResult['transcript'] ?? $transcriptionResult['subtitle_text'] ?? '';
            if (empty($transcript)) {
                // Check if we have a job_key for manual retry
                $jobKey = $transcriptionResult['error_details']['job_key'] ?? null;
                $videoId = $transcriptionResult['video_id'] ?? null;
                $format = $transcriptionResult['format'] ?? null;
                
                $errorMessage = 'No transcript content available';
                $errorDetails = [
                    'error_type' => 'transcription_error',
                    'error_source' => 'processAudioVideoSummarizationWithStages',
                    'video_id' => $videoId,
                    'format' => $format,
                    'has_article_text' => isset($transcriptionResult['article_text']),
                    'has_subtitle_text' => isset($transcriptionResult['subtitle_text']),
                    'has_json_items' => isset($transcriptionResult['json_items']),
                    'result_keys' => array_keys($transcriptionResult)
                ];
                
                if ($jobKey) {
                    $errorMessage .= '. The transcription job may still be processing.';
                    $errorDetails['job_key'] = $jobKey;
                    $errorDetails['status_url'] = config('services.youtube_transcriber.url') . '/status?job_key=' . $jobKey;
                    $errorDetails['hint'] = 'You can check the job status manually using the transcriber service status endpoint';
                } else {
                    $errorDetails['hint'] = 'The transcriber service returned a successful response but with no transcript content. This may indicate an issue with the audio file or the transcription service.';
                }
                
                $this->failJob($jobId, $errorMessage);
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'error_details' => $errorDetails
                ];
            }

            // Get article text for ingestion (prefer article_text from bundle, fallback to transcript)
            $articleText = $transcriptionResult['article_text'] ?? $transcript;
            
            // Ensure we have actual content before proceeding
            $articleTextTrimmed = trim($articleText);
            if (empty($articleTextTrimmed)) {
                $errorMessage = "No article text or transcript available for ingestion. Transcript length: " . strlen($transcript) . ", Article text length: " . strlen($transcriptionResult['article_text'] ?? '');
                $this->addLog($jobId, $errorMessage, 'error', [
                    'transcription_result' => $transcriptionResult,
                    'transcript' => substr($transcript, 0, 100),
                    'article_text' => substr($transcriptionResult['article_text'] ?? '', 0, 100)
                ]);
                $this->failJob($jobId, $errorMessage);
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'metadata' => [
                        'file_count' => 1,
                        'tokens_used' => 0,
                        'confidence_score' => 0,
                        'source_type' => $file->file_type
                    ]
                ];
            }
            
            // Validate minimum content length (at least 10 characters)
            if (strlen($articleTextTrimmed) < 10) {
                $errorMessage = "Transcript content too short for ingestion. Length: " . strlen($articleTextTrimmed);
                $this->addLog($jobId, $errorMessage, 'error', [
                    'content_preview' => substr($articleTextTrimmed, 0, 200)
                ]);
                $this->failJob($jobId, $errorMessage);
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'metadata' => [
                        'file_count' => 1,
                        'tokens_used' => 0,
                        'confidence_score' => 0,
                        'source_type' => $file->file_type
                    ]
                ];
            }
            
            // Stage 4: Ingest text into Document Intelligence
            $this->updateJob($jobId, [
                'stage' => 'ingesting_text',
                'progress' => 50
            ]);
            $this->addLog($jobId, "Ingesting transcript into Document Intelligence", 'info', [
                'text_length' => strlen($articleTextTrimmed),
                'text_preview' => substr($articleTextTrimmed, 0, 100) . '...'
            ]);
            
            $docIntelligenceModule = app(\App\Services\Modules\DocumentIntelligenceModule::class);
            $userId = $file->user_id ?? null;
            
            // Use trimmed content for ingestion
            // Note: filename must be "summary.txt" as per Document Intelligence API requirements
            $ingestResult = $docIntelligenceModule->ingestText($articleTextTrimmed, [
                'filename' => 'summary.txt',
                'metadata' => [
                    'source' => $file->file_type,
                    'file_id' => $file->id,
                    'user_id' => $userId,
                    'language' => $transcriptionResult['language'] ?? null
                ],
                'force_fallback' => true,
                'llm_model' => $options['llm_model'] ?? 'llama3'
            ]);
            
            if (!$ingestResult['success'] || empty($ingestResult['doc_id'])) {
                $errorType = $ingestResult['error_type'] ?? 'unknown_error';
                $originalError = $ingestResult['error'] ?? 'No doc_id returned';
                $originalErrorDetails = $ingestResult['original_error'] ?? null;
                
                // Build descriptive error message
                $errorMessage = $this->buildIngestionErrorMessage($errorType, $originalError, $originalErrorDetails, "{$file->file_type} file transcript");
                
                $this->addLog($jobId, $errorMessage, 'error', [
                    'error_type' => $errorType,
                    'ingest_result' => $ingestResult,
                    'text_length' => strlen($articleTextTrimmed),
                    'text_preview' => substr($articleTextTrimmed, 0, 200),
                    'has_doc_id' => !empty($ingestResult['doc_id']),
                    'has_job_id' => !empty($ingestResult['job_id']),
                    'file_id' => $file->id,
                    'file_type' => $file->file_type,
                    'transcript_source' => 'file_upload'
                ]);
                
                $this->failJob($jobId, $errorMessage);
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'error_type' => $errorType,
                    'error_details' => [
                        'error_type' => $errorType,
                        'original_error' => $originalError,
                        'text_length' => strlen($articleTextTrimmed),
                        'has_content' => !empty($articleTextTrimmed),
                        'file_id' => $file->id
                    ],
                    'metadata' => [
                        'file_count' => 1,
                        'tokens_used' => 0,
                        'confidence_score' => 0,
                        'source_type' => $file->file_type
                    ]
                ];
            }
            
            $docId = $ingestResult['doc_id'];
            $ingestJobId = $ingestResult['job_id'] ?? null;
            
            // Poll ingestion job until complete
            if ($ingestJobId) {
                $this->updateJob($jobId, [
                    'stage' => 'polling_ingestion',
                    'progress' => 60
                ]);
                $pollResult = $docIntelligenceModule->pollJobCompletion($ingestJobId, 300, 2);
                if (($pollResult['status'] ?? '') !== 'completed' && ($pollResult['status'] ?? '') !== 'succeeded') {
                    $this->addLog($jobId, "Ingestion job did not complete successfully", 'warning', [
                        'status' => $pollResult['status'] ?? 'unknown'
                    ]);
                }
            }
            
            // Stage 5: Extract summary using Document Intelligence
            $this->updateJob($jobId, [
                'stage' => 'extracting_summary',
                'progress' => 70
            ]);
            $this->addLog($jobId, "Extracting summary from Document Intelligence", 'info', ['doc_id' => $docId]);
            
            $llmModel = $options['llm_model'] ?? 'deepseek-chat';
            $summaryQuery = "Please provide a comprehensive summary of this content. " .
                          "Include key points, main themes, and important details. " .
                          "Format the response as follows:\n" .
                          "1. Start with a summary paragraph (2-3 sentences)\n" .
                          "2. Then provide a bulleted list of key points (use • or - for bullets)\n" .
                          "3. Each key point should be a single sentence or short phrase";
            
            $summaryResult = $docIntelligenceModule->answer(
                $summaryQuery,
                [
                    'doc_ids' => [$docId],
                    'llm_model' => $llmModel,
                    'max_tokens' => 2000,
                    'top_k' => 10,
                    'temperature' => 0.7,
                    'force_fallback' => true
                ]
            );
            
            if (!$summaryResult['success'] || empty($summaryResult['answer'])) {
                $errorMessage = "Summary extraction failed: " . ($summaryResult['error'] ?? 'No answer returned');
                $this->failJob($jobId, $errorMessage);
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'doc_id' => $docId,
                    'conversation_id' => $this->getOrCreateConversationId($docId, $userId),
                    'metadata' => [
                        'file_count' => 1,
                        'tokens_used' => 0,
                        'confidence_score' => 0,
                        'source_type' => $file->file_type
                    ]
                ];
            }
            
            // Extract summary and key points from answer
            $summaryText = $summaryResult['answer'] ?? '';
            $keyPoints = [];
            
            // Try to extract key points from summary text
            if (preg_match_all('/[•\-\*]\s*(.+?)(?=\n[•\-\*]|\n\n|$)/s', $summaryText, $matches)) {
                $keyPoints = array_map('trim', $matches[1]);
            } elseif (preg_match_all('/\d+\.\s*(.+?)(?=\n\d+\.|\n\n|$)/s', $summaryText, $matches)) {
                $keyPoints = array_map('trim', $matches[1]);
            }
            
            // If no key points extracted, split summary into sentences
            if (empty($keyPoints) && !empty($summaryText)) {
                $sentences = preg_split('/[.!?]+\s+/', $summaryText);
                $keyPoints = array_slice(array_filter(array_map('trim', $sentences)), 0, 10);
            }
            
            // Stage 6: Extract chapters
            $chapters = $this->extractChaptersFromDocumentIntelligence($docId, $jobId, ['llm_model' => $llmModel]);
            
            // Get conversation ID
            $conversationId = $this->getOrCreateConversationId($docId, $userId);
            
            // Prepare bundle data
            $bundleData = [];
            if (isset($transcriptionResult['article_text'])) {
                $bundleData['article_text'] = $transcriptionResult['article_text'];
            } elseif (!empty($transcript)) {
                $bundleData['article_text'] = $transcript;
            }
            if (isset($transcriptionResult['json_items'])) {
                $bundleData['json_items'] = $transcriptionResult['json_items'];
            }
            if (isset($transcriptionResult['transcript_json'])) {
                $bundleData['transcript_json'] = $transcriptionResult['transcript_json'];
            }
            
            // Stage 7: Finalizing
            $this->updateJob($jobId, [
                'stage' => 'finalizing',
                'progress' => 95
            ]);
            $this->addLog($jobId, "Audio/video processing completed", 'info', [
                'summary_length' => strlen($summaryText),
                'chapters_count' => count($chapters)
            ]);
            
            // Use standardized response
            return $this->standardizeSummarizationResponse(
                [
                    'summary' => $summaryText,
                    'key_points' => $keyPoints,
                    'confidence_score' => 0.9,
                    'model_used' => $llmModel,
                    'sources' => $summaryResult['sources'] ?? []
                ],
                $file->file_type, // 'audio' or 'video'
                !empty($bundleData) ? $bundleData : null,
                $docId,
                $conversationId,
                $chapters,
                [
                    'file_type' => $file->file_type,
                    'file_id' => $file->id,
                    'video_id' => $transcriptionResult['video_id'] ?? null,
                    'language' => $transcriptionResult['language'] ?? null,
                    'format' => $transcriptionResult['format'] ?? null,
                    'transcript_length' => strlen($articleText)
                ]
            );
        } catch (\Exception $e) {
            $this->failJob($jobId, "Audio/video processing failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Audio/video processing failed: ' . $e->getMessage(),
                'error_details' => [
                    'error_type' => 'processing_error',
                    'error_source' => 'processAudioVideoSummarizationWithStages',
                    'context' => ['file_id' => $file->id]
                ]
            ];
        }
    }

    /**
     * Build descriptive error message for ingestion failures
     * 
     * @param string $errorType Error type from Document Intelligence
     * @param string $originalError Original error message
     * @param string|null $originalErrorDetails Additional error details
     * @param string $sourceType Source type (youtube, text, web link, etc.)
     * @return string Descriptive error message
     */
    private function buildIngestionErrorMessage($errorType, $originalError, $originalErrorDetails = null, $sourceType = 'content')
    {
        $errorMessage = "Failed to ingest {$sourceType} into Document Intelligence. ";
        
        // Add context based on error type
        switch ($errorType) {
            case 'endpoint_not_found':
                $errorMessage .= "The Document Intelligence service endpoint '/v1/ingest/text' was not found. ";
                $errorMessage .= "This may indicate a service configuration issue. ";
                $errorMessage .= "Please verify that the Document Intelligence service is running and the endpoint is correct.";
                break;
            case 'authentication_error':
                $errorMessage .= "Authentication with Document Intelligence service failed. ";
                $errorMessage .= "Please check your API credentials in the service configuration.";
                break;
            case 'service_unavailable':
                $errorMessage .= "The Document Intelligence service is temporarily unavailable. ";
                $errorMessage .= "Please try again in a few moments. If the problem persists, check the service status.";
                break;
            case 'connection_error':
                $errorMessage .= "Unable to connect to Document Intelligence service. ";
                $errorMessage .= "Please check your network connection and service URL configuration.";
                break;
            case 'validation_error':
                $errorMessage .= "The content failed validation. ";
                $errorMessage .= "Original error: " . $originalError;
                break;
            default:
                $errorMessage .= $originalError;
                if ($originalErrorDetails) {
                    $errorMessage .= " (Technical details: " . $originalErrorDetails . ")";
                }
        }
        
        return $errorMessage;
    }

    /**
     * Poll transcription job until completion
     * 
     * @param string $jobKey Job key from transcriber
     * @param string $videoUrl Video URL
     * @param string $format Format requested
     * @param string $jobId Universal job ID for logging
     * @return array Transcription result
     */
    private function pollTranscriptionJob($jobKey, $videoUrl, $format, $jobId)
    {
        try {
            $transcriberModule = app(\App\Services\Modules\TranscriberModule::class);
            $maxAttempts = 120; // 20 minutes max
            $pollInterval = 10; // seconds
            
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                // Check job status via transcriber service
                $transcriberService = app(\App\Services\YouTubeTranscriberService::class);
                $statusResponse = \Illuminate\Support\Facades\Http::timeout(30)
                    ->withHeaders([
                        'X-Client-Key' => config('services.youtube_transcriber.client_key'),
                    ])->get(config('services.youtube_transcriber.url') . '/status', [
                        'job_key' => $jobKey
                    ]);
                
                if ($statusResponse->successful()) {
                    $statusData = $statusResponse->json();
                    $status = $statusData['status'] ?? 'unknown';
                    
                    $this->addLog($jobId, "Transcription job status check", 'info', [
                        'job_key' => $jobKey,
                        'attempt' => $attempt,
                        'status' => $status,
                        'progress' => $statusData['progress'] ?? 0
                    ]);
                    
                    if ($status === 'completed') {
                        // Fetch the result - check if videoUrl is a file path or URL
                        if (file_exists($videoUrl)) {
                            // It's a file path
                            $result = $transcriberModule->transcribeFile($videoUrl, [
                                'format' => $format
                            ]);
                        } else {
                            // It's a video URL
                            $result = $transcriberModule->transcribeVideo($videoUrl, [
                                'format' => $format
                            ]);
                        }
                        
                        if ($result['success'] && !empty($result['transcript'] ?? $result['subtitle_text'] ?? '')) {
                            $this->addLog($jobId, "Transcription job completed successfully", 'info', [
                                'job_key' => $jobKey,
                                'transcript_length' => strlen($result['transcript'] ?? $result['subtitle_text'] ?? '')
                            ]);
                            return $result;
                        }
                    } elseif ($status === 'failed') {
                        return [
                            'success' => false,
                            'error' => 'Transcription job failed: ' . ($statusData['stage'] ?? 'unknown error')
                        ];
                    }
                }
                
                // Wait before next poll
                if ($attempt < $maxAttempts) {
                    sleep($pollInterval);
                }
            }
            
            return [
                'success' => false,
                'error' => 'Transcription job polling timeout after ' . ($maxAttempts * $pollInterval) . ' seconds'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Transcription polling error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Extract chapters from Document Intelligence
     * 
     * @param string $docId Document ID
     * @param string $jobId Job ID for logging
     * @param array $options Options for chapter extraction
     * @return array Chapters array
     */
    private function extractChaptersFromDocumentIntelligence($docId, $jobId, $options = [])
    {
        try {
            $docIntelligenceModule = app(\App\Services\Modules\DocumentIntelligenceModule::class);
            
            $this->updateJob($jobId, [
                'stage' => 'extracting_chapters',
                'progress' => 85
            ]);
            $this->addLog($jobId, "Extracting chapters from document", 'info', ['doc_id' => $docId]);
            
            $chaptersQuery = "Please analyze this content and identify all chapters or major sections. " .
                           "For each chapter, provide:\n" .
                           "1. Chapter title (clear and descriptive)\n" .
                           "2. Start timestamp (if available in the content, format as HH:MM:SS or MM:SS)\n" .
                           "3. Brief description (1-2 sentences)\n\n" .
                           "Format your response as a valid JSON array of objects, where each object has: " .
                           "{\"title\": \"...\", \"timestamp\": \"...\" or null, \"description\": \"...\"}. " .
                           "If timestamps are not available, use null for the timestamp field. " .
                           "Return only the JSON array, no markdown formatting.";
            
            $llmModel = $options['llm_model'] ?? 'deepseek-chat';
            $chaptersResult = $docIntelligenceModule->answer($chaptersQuery, [
                'doc_ids' => [$docId],
                'llm_model' => $llmModel,
                'max_tokens' => 2000,
                'top_k' => 10,
                'temperature' => 0.7,
                'force_fallback' => true
            ]);
            
            $chapters = [];
            if ($chaptersResult['success'] && !empty($chaptersResult['answer'])) {
                $chaptersJson = $chaptersResult['answer'];
                
                // Try to extract JSON from markdown if wrapped
                if (preg_match('/```json\s*(\[.*?\])\s*```/s', $chaptersJson, $matches)) {
                    $chaptersJson = $matches[1];
                } elseif (preg_match('/\[.*?\]/s', $chaptersJson, $matches)) {
                    $chaptersJson = $matches[0];
                }
                
                $decodedChapters = json_decode($chaptersJson, true);
                if (is_array($decodedChapters)) {
                    $chapters = $decodedChapters;
                    $this->addLog($jobId, "Chapters extracted successfully", 'info', [
                        'chapters_count' => count($chapters)
                    ]);
                } else {
                    $this->addLog($jobId, "Failed to parse chapters JSON", 'warning', [
                        'chapters_json' => $chaptersJson
                    ]);
                }
            } else {
                $this->addLog($jobId, "Chapter extraction failed or returned empty", 'warning', [
                    'error' => $chaptersResult['error'] ?? 'Unknown error'
                ]);
            }
            
            return $chapters;
        } catch (\Exception $e) {
            $this->addLog($jobId, "Chapter extraction exception: " . $e->getMessage(), 'warning');
            return [];
        }
    }

    /**
     * Create or get conversation ID for document chat
     * 
     * @param string $docId Document ID
     * @param int|null $userId User ID
     * @return string|null Conversation ID
     */
    private function getOrCreateConversationId($docId, $userId = null)
    {
        if (!$userId || !$docId) {
            return null;
        }
        
        try {
            $conversation = \App\Models\DocumentConversation::findOrCreateForDoc($docId, $userId);
            return $conversation->conversation_id;
        } catch (\Exception $e) {
            Log::warning("Failed to create conversation_id", [
                'doc_id' => $docId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Standardize summarization response structure
     * 
     * @param array $data Response data
     * @param string $sourceType Source type (youtube, text, file, audiovideo, link)
     * @param array $bundle Bundle data from transcriber
     * @param string|null $docId Document ID
     * @param string|null $conversationId Conversation ID
     * @param array $chapters Chapters array
     * @param array $metadata Additional metadata
     * @return array Standardized response
     */
    private function standardizeSummarizationResponse($data, $sourceType, $bundle = null, $docId = null, $conversationId = null, $chapters = [], $metadata = [])
    {
        // Extract summary and key_points from data
        $summary = $data['summary'] ?? $data['answer'] ?? '';
        $keyPoints = $data['key_points'] ?? [];
        $confidenceScore = $data['confidence_score'] ?? 0.8;
        $modelUsed = $data['model_used'] ?? 'deepseek-chat';
        $sources = $data['sources'] ?? [];
        
        // Prepare bundle - use article_text from bundle if available, otherwise use transcript
        $bundleData = null;
        if ($bundle !== null) {
            $bundleData = [
                'article_text' => $bundle['article_text'] ?? $bundle['transcript'] ?? '',
            ];
            if (isset($bundle['json_items'])) {
                $bundleData['json_items'] = $bundle['json_items'];
            }
            if (isset($bundle['transcript_json'])) {
                $bundleData['transcript_json'] = $bundle['transcript_json'];
            }
        } elseif (!empty($summary)) {
            // If no bundle but we have content, create minimal bundle
            $bundleData = [
                'article_text' => $data['transcript'] ?? $data['content'] ?? ''
            ];
        }
        
        // Merge metadata
        $finalMetadata = array_merge([
            'source_type' => $sourceType,
            'chapters_extracted' => count($chapters) > 0,
            'chapters_count' => count($chapters),
            'chat_enabled' => !empty($docId),
            'sources_count' => count($sources)
        ], $metadata);
        
        return [
            'success' => true,
            'data' => [
                'summary' => $summary,
                'key_points' => $keyPoints,
                'chapters' => $chapters,
                'bundle' => $bundleData,
                'doc_id' => $docId,
                'conversation_id' => $conversationId,
                'confidence_score' => $confidenceScore,
                'model_used' => $modelUsed,
                'sources' => $sources,
                'metadata' => $finalMetadata
            ]
        ];
    }

    /**
     * Resolve file path from FileUpload model - production-ready solution
     * Handles Windows/Unix paths, different storage disks, and various path formats
     */
    private function resolveFilePath($file, $jobId = null)
    {
        $filePath = $file->file_path;
        
        // If file_path is already an absolute path, check it directly
        if ($this->is_absolute_path($filePath) && file_exists($filePath)) {
            return $filePath;
        }
        
        // Normalize the file_path (remove leading slashes, handle backslashes on Windows)
        $normalizedPath = ltrim(str_replace('\\', '/', $filePath), '/');
        
        // Try different storage disks and locations
        $storage = \Illuminate\Support\Facades\Storage::class;
        $disks = ['public', 'local'];
        
        foreach ($disks as $disk) {
            try {
                // Use Storage facade to get path for the disk
                $diskPath = \Illuminate\Support\Facades\Storage::disk($disk)->path($normalizedPath);
                
                // Normalize path separators for current OS
                $diskPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $diskPath);
                
                if (file_exists($diskPath)) {
                    if ($jobId) {
                        $this->addLog($jobId, "File found using disk '{$disk}'", 'info', [
                            'disk' => $disk,
                            'resolved_path' => $diskPath,
                            'original_path' => $filePath
                        ]);
                    }
                    return $diskPath;
                }
            } catch (\Exception $e) {
                // Disk might not be configured, continue to next
                continue;
            }
        }
        
        // Fallback: Try common storage path patterns
        $basePaths = [
            storage_path('app/public'),  // Most common: public disk
            storage_path('app'),         // Local storage
            public_path('storage'),      // Public storage symlink
        ];
        
        foreach ($basePaths as $basePath) {
            $fullPath = $basePath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $normalizedPath);
            
            if (file_exists($fullPath)) {
                if ($jobId) {
                    $this->addLog($jobId, "File found using base path", 'info', [
                        'base_path' => $basePath,
                        'resolved_path' => $fullPath,
                        'original_path' => $filePath
                    ]);
                }
                return $fullPath;
            }
        }
        
        // Log all attempted paths for debugging
        $attemptedPaths = [];
        foreach ($disks as $disk) {
            try {
                $attemptedPaths[] = \Illuminate\Support\Facades\Storage::disk($disk)->path($normalizedPath);
            } catch (\Exception $e) {
                $attemptedPaths[] = "Disk '{$disk}' not available";
            }
        }
        foreach ($basePaths as $basePath) {
            $attemptedPaths[] = $basePath . DIRECTORY_SEPARATOR . $normalizedPath;
        }
        
        if ($jobId) {
            $this->addLog($jobId, "File path resolution failed", 'error', [
                'file_path_column' => $filePath,
                'normalized_path' => $normalizedPath,
                'attempted_paths' => $attemptedPaths,
                'stored_name' => $file->stored_name ?? null,
                'original_name' => $file->original_name ?? null
            ]);
        }
        
        return null;
    }
    
    /**
     * Check if a path is absolute
     */
    private function is_absolute_path($path)
    {
        // Windows absolute path (C:\, D:\, etc.)
        if (preg_match('/^[A-Z]:[\\\\\/]/i', $path)) {
            return true;
        }
        // Unix absolute path (/)
        if (strpos($path, '/') === 0) {
            return true;
        }
        return false;
    }

    /**
     * Process PDF/Doc/Image file summarization: Document Intelligence
     */
    private function processDocumentFileSummarizationWithStages($jobId, $file, $options)
    {
        try {
            $docIntelligenceService = app(\App\Services\DocumentIntelligenceService::class);
            
            // Use production-ready file path resolution
            $filePath = $this->resolveFilePath($file, $jobId);
            
            if (!$filePath || !file_exists($filePath)) {
                $this->failJob($jobId, "File not found on disk. File path: {$file->file_path}");
                return [
                    'success' => false,
                    'error' => "File not found on disk. Please ensure the file exists and is accessible.",
                    'error_details' => [
                        'error_type' => 'file_not_found',
                        'error_source' => 'processDocumentFileSummarizationWithStages',
                        'file_path_column' => $file->file_path,
                        'file_id' => $file->id,
                        'stored_name' => $file->stored_name ?? null,
                        'original_name' => $file->original_name ?? null,
                        'hint' => 'Check if the file exists in storage/app/public or storage/app directories'
                    ]
                ];
            }

            // Stage 3: Check if already ingested
            $this->updateJob($jobId, [
                'stage' => 'checking_ingestion',
                'progress' => 30
            ]);
            $this->addLog($jobId, "Checking if file is already ingested", 'info');

            $docId = $file->doc_id;
            $needsIngestion = empty($docId);

            if ($needsIngestion) {
                // Stage 4: Ingesting
                $this->updateJob($jobId, [
                    'stage' => 'ingesting',
                    'progress' => 40
                ]);
                $this->addLog($jobId, "Ingesting document to Document Intelligence", 'info', [
                    'file_name' => $file->original_name,
                    'file_type' => $file->file_type
                ]);

                try {
                    $ingestResult = $docIntelligenceService->ingestDocument($filePath, [
                        'ocr' => $options['ocr'] ?? 'auto',
                        'lang' => $options['lang'] ?? 'eng',
                        'metadata' => [
                            'file_id' => $file->id,
                            'user_id' => $file->user_id,
                            'original_name' => $file->original_name
                        ]
                    ]);

                    $docId = $ingestResult['doc_id'] ?? null;
                    $ingestJobId = $ingestResult['job_id'] ?? null;

                    if (empty($docId)) {
                        $this->failJob($jobId, "Document ingestion failed: No doc_id returned");
                        return [
                            'success' => false,
                            'error' => 'Document ingestion failed: No doc_id returned',
                            'error_details' => [
                                'error_type' => 'ingestion_error',
                                'error_source' => 'DocumentIntelligenceService'
                            ]
                        ];
                    }

                    // Store doc_id in file record
                    $file->doc_id = $docId;
                    $file->save();

                    // Stage 5: Polling ingestion
                    if (!empty($ingestJobId)) {
                        $this->updateJob($jobId, [
                            'stage' => 'polling_ingestion',
                            'progress' => 50
                        ]);
                        $this->addLog($jobId, "Polling ingestion job until complete", 'info', [
                            'job_id' => $ingestJobId,
                            'max_attempts' => 300, // 300 attempts * 2 seconds = 10 minutes max
                            'poll_interval' => 2
                        ]);

                        // Poll with longer timeout for large files (300 attempts * 2 seconds = 10 minutes)
                        $pollResult = $docIntelligenceService->pollJobCompletion($ingestJobId, 300, 2);
                        
                        // Log the polling result
                        $pollStatus = $pollResult['status'] ?? 'unknown';
                        $this->addLog($jobId, "Polling completed", 'info', [
                            'job_id' => $ingestJobId,
                            'status' => $pollStatus,
                            'error' => $pollResult['error'] ?? null
                        ]);

                        // Handle different polling outcomes
                        if ($pollStatus === 'failed') {
                            // Job explicitly failed - don't proceed
                            $errorMessage = "Ingestion job failed: " . ($pollResult['error'] ?? 'Unknown error');
                            $this->failJob($jobId, $errorMessage);
                            return [
                                'success' => false,
                                'error' => 'Document ingestion job failed.',
                                'error_details' => [
                                    'error_type' => 'ingestion_polling_error',
                                    'error_source' => 'DocumentIntelligenceService',
                                    'job_id' => $ingestJobId,
                                    'status' => $pollStatus,
                                    'error' => $pollResult['error'] ?? null
                                ],
                                'doc_id' => $docId
                            ];
                        } elseif ($pollStatus === 'timeout') {
                            // Timeout - but we have doc_id, so proceed anyway (document may still be processing)
                            $this->addLog($jobId, "Polling timed out, but proceeding with doc_id", 'warning', [
                                'job_id' => $ingestJobId,
                                'doc_id' => $docId,
                                'note' => 'Document may still be processing in background, but we can attempt summarization'
                            ]);
                            // Continue to summarization - don't fail
                        } elseif (in_array($pollStatus, ['completed', 'succeeded'])) {
                            $this->addLog($jobId, "Document ingestion completed successfully", 'info', [
                                'job_id' => $ingestJobId,
                                'doc_id' => $docId
                            ]);
                        } else {
                            // Unknown or other status - proceed anyway if we have doc_id
                            $this->addLog($jobId, "Polling returned status: {$pollStatus}, proceeding with doc_id", 'info', [
                                'job_id' => $ingestJobId,
                                'status' => $pollStatus,
                                'doc_id' => $docId
                            ]);
                        }
                    } else {
                        // No job_id means ingestion was synchronous (completed immediately)
                        $this->addLog($jobId, "Document ingestion completed synchronously (no polling needed)", 'info', [
                            'doc_id' => $docId
                        ]);
                    }
                } catch (\Exception $e) {
                    $this->failJob($jobId, "Document ingestion failed: " . $e->getMessage());
                    return [
                        'success' => false,
                        'error' => 'Document ingestion failed: ' . $e->getMessage(),
                        'error_details' => [
                            'error_type' => 'ingestion_error',
                            'error_source' => 'DocumentIntelligenceService',
                            'context' => ['file_id' => $file->id, 'file_name' => $file->original_name]
                        ]
                    ];
                }
            } else {
                $this->addLog($jobId, "File already ingested, using existing doc_id", 'info', ['doc_id' => $docId]);
            }

            // Stage 6: Summarizing
            $this->updateJob($jobId, [
                'stage' => 'summarizing',
                'progress' => 70
            ]);
            $this->addLog($jobId, "Generating summary using Document Intelligence", 'info', [
                'doc_id' => $docId
            ]);

            try {
                // Cap parameters to prevent service errors
                $maxTokens = min($options['max_tokens'] ?? 512, 512);
                $topK = min($options['top_k'] ?? 3, 3);
                $llmModel = $options['llm_model'] ?? 'llama3';
                $temperature = $options['temperature'] ?? 0.7;
                $forceFallback = true; // Always true for Document Intelligence microservice
                
                $this->addLog($jobId, "Calling Document Intelligence answer method", 'info', [
                    'doc_id' => $docId,
                    'llm_model' => $llmModel,
                    'max_tokens' => $maxTokens,
                    'top_k' => $topK,
                    'temperature' => $temperature,
                    'force_fallback' => $forceFallback
                ]);
                
                $summaryResult = $docIntelligenceService->answer(
                    "Please provide a comprehensive summary of this document. Include key points, main themes, and important details.",
                    [
                        'doc_ids' => [$docId],
                        'llm_model' => $llmModel,
                        'max_tokens' => $maxTokens,
                        'top_k' => $topK,
                        'temperature' => $temperature,
                        'force_fallback' => $forceFallback
                    ]
                );
                
                $this->addLog($jobId, "Document Intelligence answer method returned", 'info', [
                    'result_keys' => array_keys($summaryResult),
                    'has_answer' => isset($summaryResult['answer']),
                    'has_error' => isset($summaryResult['error']),
                    'has_sources' => isset($summaryResult['sources'])
                ]);

                // Get or create conversation_id for document chat (before error handling)
                $conversationId = null;
                if ($file->user_id && $docId) {
                    try {
                        $conversation = \App\Models\DocumentConversation::findOrCreateForDoc(
                            $docId,
                            $file->user_id
                        );
                        $conversationId = $conversation->conversation_id;
                    } catch (\Exception $e) {
                        // Log but don't fail - conversation_id is optional
                        $this->addLog($jobId, "Failed to create conversation_id: " . $e->getMessage(), 'warning');
                    }
                }

                // Check for errors in the result
                if (isset($summaryResult['error'])) {
                    $errorMessage = $summaryResult['error'];
                    
                    // Check if it's a service unavailable error
                    if (stripos($errorMessage, 'unavailable') !== false || stripos($errorMessage, '500') !== false || stripos($errorMessage, 'LLM service') !== false) {
                        $baseUrl = config('app.url', 'http://localhost:8000');
                        $chatEndpoint = rtrim($baseUrl, '/') . '/api/document/chat';
                        
                        $errorResult = [
                            'success' => false,
                            'error' => 'Document Intelligence LLM service is currently unavailable. Please try again later.',
                            'error_details' => [
                                'error_type' => 'llm_service_unavailable',
                                'error_source' => 'DocumentIntelligenceService',
                                'error_message' => $errorMessage,
                                'hint' => 'The LLM service may be temporarily down. Your document has been ingested successfully. You can: 1) Retry this job later, or 2) Use the chat endpoint to get a summary now.',
                                'doc_id' => $docId,
                                'conversation_id' => $conversationId,
                                'chat_endpoint' => $chatEndpoint,
                                'retry_job_id' => $jobId
                            ],
                            'doc_id' => $docId,
                            'conversation_id' => $conversationId
                        ];
                        $this->failJob($jobId, "Document Intelligence LLM service is currently unavailable", [], $errorResult);
                        return $errorResult;
                    }
                    
                    // Other errors
                    $errorResult = [
                        'success' => false,
                        'error' => 'Summarization failed: ' . $errorMessage,
                        'error_details' => [
                            'error_type' => 'summarization_error',
                            'error_source' => 'DocumentIntelligenceService',
                            'error' => $errorMessage,
                            'doc_id' => $docId,
                            'conversation_id' => $conversationId
                        ],
                        'doc_id' => $docId,
                        'conversation_id' => $conversationId
                    ];
                    $this->failJob($jobId, "Summarization failed: " . $errorMessage, [], $errorResult);
                    return $errorResult;
                }

                $summaryText = $summaryResult['answer'] ?? '';
                if (empty($summaryText)) {
                    $errorResult = [
                        'success' => false,
                        'error' => 'Summarization failed: No answer in response',
                        'error_details' => [
                            'error_type' => 'summarization_error',
                            'error_source' => 'DocumentIntelligenceService',
                            'response_keys' => array_keys($summaryResult),
                            'doc_id' => $docId,
                            'conversation_id' => $conversationId
                        ],
                        'doc_id' => $docId, // Still return doc_id for manual chat
                        'conversation_id' => $conversationId
                    ];
                    $this->failJob($jobId, "Summarization failed: No answer in response", [], $errorResult);
                    return $errorResult;
                }

                // Extract key points from summary
                $keyPoints = [];
                if (preg_match_all('/[•\-\*]\s*(.+?)(?=\n[•\-\*]|\n\n|$)/s', $summaryText, $matches)) {
                    $keyPoints = array_map('trim', $matches[1]);
                } elseif (preg_match_all('/\d+\.\s*(.+?)(?=\n\d+\.|\n\n|$)/s', $summaryText, $matches)) {
                    $keyPoints = array_map('trim', $matches[1]);
                }
                
                // If no key points extracted, split summary into sentences
                if (empty($keyPoints) && !empty($summaryText)) {
                    $sentences = preg_split('/[.!?]+\s+/', $summaryText);
                    $keyPoints = array_slice(array_filter(array_map('trim', $sentences)), 0, 10);
                }

                // Stage 7: Extract chapters
                $chapters = $this->extractChaptersFromDocumentIntelligence($docId, $jobId, ['llm_model' => $llmModel]);

                // Stage 8: Finalizing
                $this->updateJob($jobId, [
                    'stage' => 'finalizing',
                    'progress' => 95
                ]);
                
                $this->addLog($jobId, "Document processing completed", 'info', [
                    'summary_length' => strlen($summaryText),
                    'chapters_count' => count($chapters),
                    'doc_id' => $docId,
                    'conversation_id' => $conversationId
                ]);

                // Use standardized response
                return $this->standardizeSummarizationResponse(
                    [
                        'summary' => $summaryText,
                        'key_points' => $keyPoints,
                        'confidence_score' => 0.9,
                        'model_used' => $llmModel,
                        'sources' => $summaryResult['sources'] ?? []
                    ],
                    $file->file_type, // 'pdf', 'doc', 'docx', 'jpg', 'png', etc.
                    null, // No bundle for PDF/Doc/Image (document content is in Document Intelligence)
                    $docId,
                    $conversationId,
                    $chapters,
                    [
                        'file_type' => $file->file_type,
                        'file_id' => $file->id,
                        'sources_count' => count($summaryResult['sources'] ?? [])
                    ]
                );
            } catch (\RuntimeException $e) {
                // Handle exceptions from DocumentIntelligenceService (thrown by answer method)
                $errorMessage = $e->getMessage();
                
                // Get or create conversation_id for error responses (if not already created)
                $conversationId = null;
                if ($file->user_id && isset($docId) && $docId) {
                    try {
                        $conversation = \App\Models\DocumentConversation::findOrCreateForDoc(
                            $docId,
                            $file->user_id
                        );
                        $conversationId = $conversation->conversation_id;
                    } catch (\Exception $ex) {
                        // Log but don't fail - conversation_id is optional
                        $this->addLog($jobId, "Failed to create conversation_id in exception handler: " . $ex->getMessage(), 'warning');
                    }
                }
                
                // Check if it's a service unavailable error
                if (stripos($errorMessage, 'unavailable') !== false || stripos($errorMessage, '500') !== false || stripos($errorMessage, 'LLM service') !== false) {
                    $baseUrl = config('app.url', 'http://localhost:8000');
                    $chatEndpoint = rtrim($baseUrl, '/') . '/api/document/chat';
                    
                    $errorResult = [
                        'success' => false,
                        'error' => 'Document Intelligence LLM service is currently unavailable. Please try again later.',
                        'error_details' => [
                            'error_type' => 'llm_service_unavailable',
                            'error_source' => 'DocumentIntelligenceService',
                            'error_message' => $errorMessage,
                            'hint' => 'The LLM service may be temporarily down. Your document has been ingested successfully (doc_id: ' . ($docId ?? 'unknown') . '). You can: 1) Retry this job later, or 2) Use the chat endpoint to get a summary now.',
                            'doc_id' => $docId ?? null,
                            'conversation_id' => $conversationId,
                            'chat_endpoint' => $chatEndpoint,
                            'retry_job_id' => $jobId
                        ],
                        'doc_id' => $docId ?? null,
                        'conversation_id' => $conversationId
                    ];
                    $this->failJob($jobId, "Document Intelligence LLM service is currently unavailable", [], $errorResult);
                    return $errorResult;
                }
                
                // Other exceptions
                $errorResult = [
                    'success' => false,
                    'error' => 'Summarization failed: ' . $errorMessage,
                    'error_details' => [
                        'error_type' => 'summarization_error',
                        'error_source' => 'DocumentIntelligenceService',
                        'exception_type' => get_class($e),
                        'error' => $errorMessage,
                        'doc_id' => $docId ?? null,
                        'conversation_id' => $conversationId
                    ],
                    'doc_id' => $docId ?? null,
                    'conversation_id' => $conversationId
                ];
                $this->failJob($jobId, "Summarization failed: " . $errorMessage, [], $errorResult);
                return $errorResult;
            } catch (\Exception $e) {
                // Catch any other exceptions
                $this->failJob($jobId, "Summarization exception: " . $e->getMessage());
                return [
                    'success' => false,
                    'error' => 'Summarization failed: ' . $e->getMessage(),
                    'error_details' => [
                        'error_type' => 'summarization_exception',
                        'error_source' => 'DocumentIntelligenceService',
                        'exception_type' => get_class($e),
                        'error' => $e->getMessage()
                    ],
                    'doc_id' => $docId
                ];
            }
        } catch (\Exception $e) {
            $this->failJob($jobId, "Document file processing failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Document file processing failed: ' . $e->getMessage(),
                'error_details' => [
                    'error_type' => 'processing_error',
                    'error_source' => 'processDocumentFileSummarizationWithStages',
                    'context' => ['file_id' => $file->id]
                ]
            ];
        }
    }

    /**
     * Process math job with stages
     */
    private function processMathJobWithStages($jobId, $input, $options)
    {
        try {
            // Stage 2: Problem Analysis
            $this->updateJob($jobId, [
                'stage' => 'analyzing_problem',
                'progress' => 20
            ]);
            $this->addLog($jobId, "Analyzing math problem", 'info');

            if (isset($input['file_id'])) {
                // Image-based math problem
                $this->updateJob($jobId, [
                    'stage' => 'processing_image',
                    'progress' => 40
                ]);
                $this->addLog($jobId, "Processing math problem image", 'info');

                $result = $this->getUniversalFileModule()->processFile($input['file_id'], 'math', $options);
                
                if (!$result['success']) {
                    $this->failJob($jobId, "Math processing failed: " . $result['error']);
                    return ['success' => false, 'error' => $result['error']];
                }

                $this->updateJob($jobId, [
                    'stage' => 'finalizing',
                    'progress' => 90
                ]);
                $this->addLog($jobId, "Math problem solved", 'info');

                return [
                    'success' => true,
                    'data' => $result['result'],
                    'metadata' => [
                        'file_count' => 1,
                        'problem_type' => 'image',
                        'confidence_score' => 0.9,
                        'processing_stages' => ['analyzing_problem', 'processing_image', 'finalizing']
                    ]
                ];
            } else {
                // Text-based math problem
                $this->updateJob($jobId, [
                    'stage' => 'solving_problem',
                    'progress' => 60
                ]);
                $this->addLog($jobId, "Solving text-based math problem", 'info');

                $problemData = [
                    'problem_text' => $input['text'],
                    'problem_type' => 'text',
                    'subject_area' => $options['subject_area'] ?? 'general',
                    'difficulty_level' => $options['difficulty_level'] ?? 'intermediate'
                ];
                $result = $this->getUniversalFileModule()->aiMathService->solveMathProblem($problemData, $input['user_id'] ?? 1);
                
                $this->updateJob($jobId, [
                    'stage' => 'finalizing',
                    'progress' => 90
                ]);
                $this->addLog($jobId, "Math problem solved", 'info');
                
                return [
                    'success' => true,
                    'data' => $result,
                    'metadata' => [
                        'file_count' => 0,
                        'problem_type' => 'text',
                        'confidence_score' => 0.95,
                        'processing_stages' => ['analyzing_problem', 'solving_problem', 'finalizing']
                    ]
                ];
            }
        } catch (\Exception $e) {
            $this->failJob($jobId, "Math processing failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process flashcards job with stages using FlashcardModule
     */
    private function processFlashcardsJobWithStages($jobId, $input, $options)
    {
        try {
            $flashcardModule = app(\App\Services\Modules\FlashcardModule::class);
            $job = $this->getJob($jobId);
            $userId = $job['user_id'] ?? null;
            $user = $userId ? \App\Models\User::find($userId) : null;
            
            if (!$user) {
                $this->failJob($jobId, "User not found");
                return ['success' => false, 'error' => 'User not found'];
            }
            
            // Stage 1: Content Analysis
            $this->updateJob($jobId, [
                'stage' => 'analyzing_content',
                'progress' => 10
            ]);
            $this->addLog($jobId, "Analyzing content for flashcard generation", 'info');

            $inputType = $options['input_type'] ?? 'text';
            $inputValue = null;
            $fileUpload = null;

            if (isset($input['file_id'])) {
                // File-based flashcard generation
                $fileId = $input['file_id'];
                $this->updateJob($jobId, [
                    'stage' => 'validating_file',
                    'progress' => 20
                ]);
                $this->addLog($jobId, "Validating file for flashcard generation", 'info');

                $fileResult = $this->getUniversalFileModule()->getFile($fileId);
                
                if (!$fileResult['success']) {
                    $this->failJob($jobId, "File not found: " . ($fileResult['error'] ?? 'Unknown error'));
                    return ['success' => false, 'error' => 'File not found'];
                }

                $fileUpload = $fileResult['file'];
                $inputType = 'file';
                $inputValue = $fileId;
            } else {
                // Text/URL/YouTube-based flashcard generation
                $inputValue = $input['input'] ?? null;
                
                if (empty($inputValue)) {
                    $this->failJob($jobId, "Input is required");
                    return ['success' => false, 'error' => 'Input is required'];
                }

                // Auto-detect input type if not specified
                if ($inputType === 'text') {
                    $inputType = $flashcardModule->detectInputType($inputValue);
                }
            }

            // Stage 2: Validate input
            $this->updateJob($jobId, [
                'stage' => 'validating_input',
                'progress' => 30
            ]);
            $this->addLog($jobId, "Validating input", 'info', [
                'input_type' => $inputType
            ]);

            try {
                $flashcardModule->validateInput($inputValue, $inputType);
            } catch (\Exception $e) {
                $this->failJob($jobId, "Input validation failed: " . $e->getMessage());
                return ['success' => false, 'error' => $e->getMessage()];
            }

            // Stage 3: Extract content
            $this->updateJob($jobId, [
                'stage' => 'extracting_content',
                'progress' => 40
            ]);
            $this->addLog($jobId, "Extracting content from {$inputType}", 'info');

            $extractionResult = $flashcardModule->extractContent($inputValue, $inputType, $options);
            
            if (!$extractionResult['success']) {
                $this->failJob($jobId, "Content extraction failed: " . ($extractionResult['error'] ?? 'Unknown error'));
                return ['success' => false, 'error' => $extractionResult['error']];
            }

            // Stage 4: Validate content
            $this->updateJob($jobId, [
                'stage' => 'validating_content',
                'progress' => 50
            ]);
            $this->addLog($jobId, "Validating content for flashcard generation", 'info');

            $contentValidation = $flashcardModule->validateContent($extractionResult['content']);
            
            if (!$contentValidation['valid']) {
                $this->failJob($jobId, "Content validation failed: " . ($contentValidation['error'] ?? 'Unknown error'));
                return ['success' => false, 'error' => $contentValidation['error']];
            }

            // Stage 5: Generate flashcards
            $this->updateJob($jobId, [
                'stage' => 'generating_flashcards',
                'progress' => 60
            ]);
            $this->addLog($jobId, "Generating flashcards using AI", 'info', [
                'content_length' => strlen($extractionResult['content']),
                'word_count' => str_word_count($extractionResult['content']),
                'count' => $options['count'] ?? 5
            ]);

            $count = $options['count'] ?? 5;
            $generationResult = $flashcardModule->generateFlashcards(
                $extractionResult['content'],
                $count,
                [
                    'difficulty' => $options['difficulty'] ?? 'intermediate',
                    'style' => $options['style'] ?? 'mixed',
                    'model' => $options['model'] ?? config('services.ai_manager.default_model', 'deepseek-chat')
                ]
            );

            if (!$generationResult['success']) {
                $this->failJob($jobId, "Flashcard generation failed: " . ($generationResult['error'] ?? 'Unknown error'));
                return ['success' => false, 'error' => $generationResult['error']];
            }

            // Stage 6: Save to database
            $this->updateJob($jobId, [
                'stage' => 'saving_flashcards',
                'progress' => 80
            ]);
            $this->addLog($jobId, "Saving flashcards to database", 'info');

            $flashcardSet = $this->saveFlashcardSetToDatabase(
                $user,
                $inputValue,
                $inputType,
                $options['difficulty'] ?? 'intermediate',
                $options['style'] ?? 'mixed',
                $generationResult['flashcards'],
                $extractionResult['metadata']
            );

            // Save AI result
            $aiResultService = app(\App\Services\AIResultService::class);
            $aiResult = $aiResultService->saveResult(
                $user->id,
                'flashcards',
                $flashcardSet->title,
                $flashcardSet->description,
                [
                    'input' => $inputValue,
                    'input_type' => $inputType,
                    'count' => $count,
                    'difficulty' => $options['difficulty'] ?? 'intermediate',
                    'style' => $options['style'] ?? 'mixed'
                ],
                $generationResult['flashcards'],
                array_merge($generationResult['metadata'], [
                    'input_type' => $inputType,
                    'source_metadata' => $extractionResult['metadata'],
                    'flashcard_set_id' => $flashcardSet->id
                ]),
                $fileUpload ? $fileUpload->id : null
            );

            // Stage 7: Finalizing
            $this->updateJob($jobId, [
                'stage' => 'finalizing',
                'progress' => 90
            ]);
            $this->addLog($jobId, "Flashcard generation completed", 'info', [
                'flashcard_count' => count($generationResult['flashcards']),
                'flashcard_set_id' => $flashcardSet->id
            ]);

            return [
                'success' => true,
                'data' => [
                    'flashcards' => $generationResult['flashcards'],
                    'flashcard_set' => [
                        'id' => $flashcardSet->id,
                        'title' => $flashcardSet->title,
                        'description' => $flashcardSet->description,
                        'total_cards' => $flashcardSet->total_cards,
                        'created_at' => $flashcardSet->created_at
                    ],
                    'ai_result' => [
                        'id' => $aiResult['ai_result']->id,
                        'title' => $aiResult['ai_result']->title,
                        'file_url' => $aiResult['ai_result']->file_url,
                        'created_at' => $aiResult['ai_result']->created_at
                    ]
                ],
                'metadata' => array_merge($generationResult['metadata'], [
                    'input_type' => $inputType,
                    'source_metadata' => $extractionResult['metadata'],
                    'flashcard_set_id' => $flashcardSet->id,
                    'file_count' => $fileUpload ? 1 : 0,
                    'confidence_score' => 0.9,
                    'processing_stages' => [
                        'analyzing_content',
                        'validating_input',
                        'extracting_content',
                        'validating_content',
                        'generating_flashcards',
                        'saving_flashcards',
                        'finalizing'
                    ]
                ])
            ];

        } catch (\Exception $e) {
            $this->failJob($jobId, "Flashcard generation failed: " . $e->getMessage());
            Log::error('Flashcard job processing error', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Save flashcard set to database
     */
    private function saveFlashcardSetToDatabase($user, $input, $inputType, $difficulty, $style, $flashcards, $sourceMetadata)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($user, $input, $inputType, $difficulty, $style, $flashcards, $sourceMetadata) {
            // Create flashcard set
            $flashcardSet = \App\Models\FlashcardSet::create([
                'user_id' => $user->id,
                'title' => $this->generateFlashcardTitle($input, $inputType),
                'description' => $this->generateFlashcardDescription($input, $inputType, $sourceMetadata),
                'input_type' => $inputType,
                'input_content' => $input,
                'difficulty' => $difficulty,
                'style' => $style,
                'total_cards' => count($flashcards),
                'source_metadata' => $sourceMetadata,
                'is_public' => false
            ]);

            // Create individual flashcards
            foreach ($flashcards as $index => $card) {
                \App\Models\Flashcard::create([
                    'flashcard_set_id' => $flashcardSet->id,
                    'question' => $card['question'],
                    'answer' => $card['answer'],
                    'order_index' => $index
                ]);
            }

            return $flashcardSet->load('flashcards');
        });
    }

    /**
     * Generate title for flashcard set
     */
    private function generateFlashcardTitle($input, $inputType)
    {
        $maxLength = 50;
        
        switch ($inputType) {
            case 'youtube':
                return 'YouTube Video Flashcards';
            case 'url':
                return 'Web Page Flashcards';
            case 'file':
                return 'Document Flashcards';
            default:
                $title = trim($input);
                if (strlen($title) > $maxLength) {
                    $title = substr($title, 0, $maxLength) . '...';
                }
                return $title ?: 'Text Flashcards';
        }
    }

    /**
     * Generate description for flashcard set
     */
    private function generateFlashcardDescription($input, $inputType, $sourceMetadata)
    {
        switch ($inputType) {
            case 'youtube':
                return "Flashcards generated from YouTube video: " . ($sourceMetadata['title'] ?? 'Unknown Video');
            case 'url':
                return "Flashcards generated from web page: " . ($sourceMetadata['title'] ?? 'Web Content');
            case 'file':
                return "Flashcards generated from document: " . ($sourceMetadata['source_type'] ?? 'File');
            default:
                return "Flashcards generated from text input";
        }
    }

    /**
     * Process presentations job with stages
     */
    private function processPresentationsJobWithStages($jobId, $input, $options)
    {
        try {
            // Stage 2: Content Analysis
            $this->updateJob($jobId, [
                'stage' => 'analyzing_content',
                'progress' => 20
            ]);
            $this->addLog($jobId, "Analyzing content for presentation generation", 'info');

            if (isset($input['file_id'])) {
                // File-based presentation generation
                $this->updateJob($jobId, [
                    'stage' => 'processing_file',
                    'progress' => 40
                ]);
                $this->addLog($jobId, "Processing file for presentation generation", 'info');

                $result = $this->getUniversalFileModule()->processFile($input['file_id'], 'presentations', $options);
                
                if (!$result['success']) {
                    $this->failJob($jobId, "Presentation generation failed: " . $result['error']);
                    return ['success' => false, 'error' => $result['error']];
                }

                $this->updateJob($jobId, [
                    'stage' => 'finalizing',
                    'progress' => 90
                ]);
                $this->addLog($jobId, "Presentation generated successfully", 'info', [
                    'slide_count' => count($result['result']['presentation_data']['slides'] ?? [])
                ]);

                return [
                    'success' => true,
                    'data' => $result['result'],
                    'metadata' => [
                        'file_count' => 1,
                        'slide_count' => count($result['result']['presentation_data']['slides'] ?? []),
                        'confidence_score' => 0.9,
                        'processing_stages' => ['analyzing_content', 'processing_file', 'finalizing']
                    ]
                ];
            } else {
                // Text-based presentation generation
                $this->updateJob($jobId, [
                    'stage' => 'generating_outline',
                    'progress' => 60
                ]);
                $this->addLog($jobId, "Generating presentation outline from text", 'info');

                $result = $this->getUniversalFileModule()->presentationService->generateOutline([
                    'text' => $input['text'],
                    'title' => $options['title'] ?? 'Generated Presentation',
                    'slides_count' => $options['slides_count'] ?? 5
                ], $input['user_id'] ?? 1);
                
                $this->updateJob($jobId, [
                    'stage' => 'finalizing',
                    'progress' => 90
                ]);
                $this->addLog($jobId, "Presentation outline generated successfully", 'info', [
                    'slide_count' => count($result['presentation']['slides'] ?? [])
                ]);
                
                return [
                    'success' => true,
                    'data' => $result,
                    'metadata' => [
                        'file_count' => 0,
                        'slide_count' => count($result['presentation']['slides'] ?? []),
                        'confidence_score' => 0.9,
                        'processing_stages' => ['analyzing_content', 'generating_outline', 'finalizing']
                    ]
                ];
            }
        } catch (\Exception $e) {
            $this->failJob($jobId, "Presentation generation failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process diagram job with stages
     */
    private function processDiagramJobWithStages($jobId, $input, $options, $userId = null)
    {
        try {
            $aiDiagramService = app(\App\Services\AIDiagramService::class);
            
            // Stage 1: Generate diagram job
            $this->updateJob($jobId, [
                'stage' => 'generating_diagram',
                'progress' => 20
            ]);
            $this->addLog($jobId, "Generating diagram", 'info', [
                'diagram_type' => $input['diagram_type'] ?? 'unknown',
                'prompt' => substr($input['prompt'] ?? '', 0, 100)
            ]);

            // Generate diagram (creates job on microservice)
            $result = $aiDiagramService->generateDiagram($input, $userId);

            if (!$result['success']) {
                $this->failJob($jobId, "Diagram generation failed: " . $result['error']);
                return ['success' => false, 'error' => $result['error']];
            }

            $aiResultId = $result['ai_result_id'];
            $microserviceJobId = $result['microservice_job_id'];

            // Stage 2: Poll microservice for completion
            $this->updateJob($jobId, [
                'stage' => 'polling_microservice',
                'progress' => 40
            ]);
            $this->addLog($jobId, "Polling microservice for diagram completion", 'info', [
                'microservice_job_id' => $microserviceJobId
            ]);

            // Poll microservice until completed
            $maxAttempts = 60; // 2 minutes max (60 * 2 seconds)
            $intervalSeconds = 2;
            $status = 'queued';

            for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
                $statusResult = $aiDiagramService->checkJobStatus($microserviceJobId);

                if (!$statusResult['success']) {
                    $this->failJob($jobId, "Failed to check microservice status: " . $statusResult['error']);
                    return ['success' => false, 'error' => $statusResult['error']];
                }

                $status = $statusResult['status'];

                $this->updateJob($jobId, [
                    'progress' => 40 + (($attempt / $maxAttempts) * 40), // 40-80%
                    'stage' => 'polling_microservice'
                ]);

                if ($status === 'completed') {
                    break;
                } elseif ($status === 'failed') {
                    $this->failJob($jobId, "Microservice job failed: " . ($statusResult['error'] ?? 'Unknown error'));
                    return ['success' => false, 'error' => $statusResult['error'] ?? 'Job failed'];
                }

                sleep($intervalSeconds);
            }

            if ($status !== 'completed') {
                $this->failJob($jobId, "Diagram generation timeout after " . ($maxAttempts * $intervalSeconds) . " seconds");
                return ['success' => false, 'error' => 'Job processing timeout'];
            }

            // Stage 3: Download image
            $this->updateJob($jobId, [
                'stage' => 'downloading_image',
                'progress' => 85
            ]);
            $this->addLog($jobId, "Downloading diagram image from microservice", 'info');

            $resultData = $aiDiagramService->getJobResult($microserviceJobId, $aiResultId);

            if (!$resultData['success']) {
                $this->failJob($jobId, "Failed to download diagram: " . $resultData['error']);
                return ['success' => false, 'error' => $resultData['error']];
            }

            // Stage 4: Finalize
            $this->updateJob($jobId, [
                'stage' => 'finalizing',
                'progress' => 95
            ]);
            $this->addLog($jobId, "Diagram generated and downloaded successfully", 'info', [
                'image_url' => $resultData['image_url'] ?? null
            ]);

            return [
                'success' => true,
                'data' => [
                    'ai_result_id' => $aiResultId,
                    'image_url' => $resultData['image_url'],
                    'image_path' => $resultData['image_path'],
                    'image_filename' => $resultData['image_filename'],
                    'diagram_type' => $input['diagram_type'],
                    'prompt' => $input['prompt']
                ],
                'metadata' => [
                    'diagram_type' => $input['diagram_type'],
                    'language' => $input['language'] ?? 'en',
                    'microservice_job_id' => $microserviceJobId,
                    'processing_stages' => ['generating_diagram', 'polling_microservice', 'downloading_image', 'finalizing']
                ]
            ];

        } catch (\Exception $e) {
            $this->failJob($jobId, "Diagram generation failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process document chat job with stages
     */
    private function processDocumentChatJobWithStages($jobId, $input, $options)
    {
        try {
            // Stage 2: File Validation
            $this->updateJob($jobId, [
                'stage' => 'validating_file',
                'progress' => 20
            ]);
            $this->addLog($jobId, "Validating document file", 'info');

            // Document chat requires a file_id
            if (!isset($input['file_id'])) {
                $this->failJob($jobId, "Document chat requires a file_id");
                return ['success' => false, 'error' => 'Document chat requires a file_id'];
            }
            
            // Stage 3: File Processing
            $this->updateJob($jobId, [
                'stage' => 'processing_document',
                'progress' => 40
            ]);
            $this->addLog($jobId, "Processing document for chat", 'info');

            $file = \App\Models\FileUpload::find($input['file_id']);
            if (!$file) {
                $this->failJob($jobId, "File not found");
                return ['success' => false, 'error' => 'File not found'];
            }
            
            // Stage 4: Content Extraction
            $this->updateJob($jobId, [
                'stage' => 'extracting_content',
                'progress' => 70
            ]);
            $this->addLog($jobId, "Extracting content from document", 'info');

            $result = $this->getUniversalFileModule()->extractContent($file, $options);
            
            if (!$result['success']) {
                $this->failJob($jobId, "Content extraction failed: " . $result['error']);
                return ['success' => false, 'error' => $result['error']];
            }

            // Stage 5: Finalizing
            $this->updateJob($jobId, [
                'stage' => 'finalizing',
                'progress' => 90
            ]);
            $this->addLog($jobId, "Document processing completed", 'info', [
                'word_count' => str_word_count($result['data']['text'])
            ]);

            return [
                'success' => true,
                'data' => [
                    'document_content' => $result['data']['text'],
                    'metadata' => $result['data']['metadata']
                ],
                'metadata' => [
                    'file_count' => 1,
                    'word_count' => str_word_count($result['data']['text']),
                    'confidence_score' => 0.95,
                    'processing_stages' => ['validating_file', 'processing_document', 'extracting_content', 'finalizing']
                ]
            ];
        } catch (\Exception $e) {
            $this->failJob($jobId, "Document chat processing failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process document conversion job with stages
     */
    private function processDocumentConversionJobWithStages($jobId, $input, $options)
    {
        try {
            // Stage 1: File Validation
            $this->updateJob($jobId, [
                'stage' => 'validating_file',
                'progress' => 10
            ]);
            $this->addLog($jobId, "Validating file for conversion", 'info');

            if (!isset($input['file_id'])) {
                $this->failJob($jobId, "Document conversion requires a file_id");
                return ['success' => false, 'error' => 'Document conversion requires a file_id'];
            }

            // Stage 2: File Processing
            $this->updateJob($jobId, [
                'stage' => 'processing_file',
                'progress' => 30
            ]);
            $this->addLog($jobId, "Processing file for conversion", 'info');

            // Get file from storage
            $file = \App\Models\FileUpload::find($input['file_id']);
            if (!$file) {
                $this->failJob($jobId, "File not found");
                return ['success' => false, 'error' => 'File not found'];
            }

            // Stage 3: Document Conversion
            $this->updateJob($jobId, [
                'stage' => 'converting_document',
                'progress' => 60
            ]);
            $this->addLog($jobId, "Converting document to {$input['target_format']}", 'info');

            // Use DocumentConverterService
            $documentConverterService = app(\App\Services\DocumentConverterService::class);
            
            // Use Storage facade to get the correct path (includes 'public' directory)
            $filePath = \Illuminate\Support\Facades\Storage::path($file->file_path);
            
            $this->addLog($jobId, "Using file path: {$filePath}", 'info');
            
            Log::info('Calling microservice with options', [
                'options' => $options,
                'options_type' => gettype($options)
            ]);
            
            $conversionResult = $documentConverterService->convertDocument(
                $filePath,
                $input['target_format'],
                $options
            );

            if (!$conversionResult || !isset($conversionResult['job_id'])) {
                $this->failJob($jobId, "Conversion failed to start");
                return ['success' => false, 'error' => 'Conversion failed to start'];
            }

            // Stage 4: Monitoring Conversion
            $this->updateJob($jobId, [
                'stage' => 'monitoring_conversion',
                'progress' => 80
            ]);
            $this->addLog($jobId, "Monitoring conversion progress", 'info');

            // Poll for completion
            $maxAttempts = 60; // 5 minutes max
            $attempt = 0;
            
            $finalStatus = null;
            while ($attempt < $maxAttempts) {
                sleep(5); // Wait 5 seconds between checks
                $attempt++;
                
                $status = $documentConverterService->checkConversionStatus($conversionResult['job_id']);
                
                if (isset($status['status']) && $status['status'] === 'completed') {
                    $finalStatus = $status; // Store the final status
                    break;
                } elseif (isset($status['status']) && $status['status'] === 'failed') {
                    $this->failJob($jobId, "Conversion failed: " . ($status['error'] ?? 'Unknown error'));
                    return ['success' => false, 'error' => 'Conversion failed'];
                }
            }

            if ($attempt >= $maxAttempts) {
                $this->failJob($jobId, "Conversion timeout");
                return ['success' => false, 'error' => 'Conversion timeout'];
            }

            // Stage 5: Finalizing
            $this->updateJob($jobId, [
                'stage' => 'finalizing',
                'progress' => 90
            ]);
            $this->addLog($jobId, "Finalizing conversion result", 'info');

            // Use the final status response (since result endpoint returns null)
            $result = $finalStatus;
            
            if (!$result || !isset($result['status']) || $result['status'] !== 'completed') {
                $this->failJob($jobId, "Failed to get conversion result");
                return ['success' => false, 'error' => 'Failed to get conversion result'];
            }

            // Prefer remote download URL from microservice; do not read microservice filesystem paths locally
            $downloadUrl = $result['download_urls'][0] ?? null;
            if (!$downloadUrl) {
                // Fallback: if only file_paths/output_files exist, we cannot access microservice FS here
                $this->failJob($jobId, 'No downloadable URL returned by microservice');
                return ['success' => false, 'error' => 'No downloadable URL returned by microservice'];
            }
            
            $convertedFile = $documentConverterService->downloadAndStore(
                $downloadUrl,
                $input['original_filename'] . '.' . $input['target_format']
            );

            return [
                'success' => true,
                'data' => [
                    'conversion_result' => $result,
                    'converted_file' => $convertedFile,
                    'original_file' => [
                        'id' => $file->id,
                        'filename' => $file->original_filename,
                        'size' => $file->file_size
                    ]
                ],
                'metadata' => [
                    'file_count' => 1,
                    'conversion_format' => $input['target_format'],
                    'confidence_score' => 1.0,
                    'processing_stages' => ['validating_file', 'processing_file', 'converting_document', 'monitoring_conversion', 'finalizing']
                ]
            ];

        } catch (\Exception $e) {
            $this->failJob($jobId, "Document conversion failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process content extraction job with stages
     */
    private function processContentExtractionJobWithStages($jobId, $input, $options)
    {
        try {
            // Stage 1: File Validation
            $this->updateJob($jobId, [
                'stage' => 'validating_file',
                'progress' => 10
            ]);
            $this->addLog($jobId, "Validating file for content extraction", 'info');

            if (!isset($input['file_id'])) {
                $this->failJob($jobId, "Content extraction requires a file_id");
                return ['success' => false, 'error' => 'Content extraction requires a file_id'];
            }

            // Stage 2: File Processing
            $this->updateJob($jobId, [
                'stage' => 'processing_file',
                'progress' => 30
            ]);
            $this->addLog($jobId, "Processing file for content extraction", 'info');

            // Get file from storage
            $file = \App\Models\FileUpload::find($input['file_id']);
            if (!$file) {
                $this->failJob($jobId, "File not found");
                return ['success' => false, 'error' => 'File not found'];
            }

            // Stage 3: Content Extraction
            $this->updateJob($jobId, [
                'stage' => 'extracting_content',
                'progress' => 60
            ]);
            $this->addLog($jobId, "Extracting content from document", 'info');

            // Use DocumentConverterService
            $documentConverterService = app(\App\Services\DocumentConverterService::class);
            
            // Use Storage facade to get the correct path (includes 'public' directory)
            $filePath = \Illuminate\Support\Facades\Storage::path($file->file_path);
            
            $this->addLog($jobId, "Using file path: {$filePath}", 'info');
            
            $extractionResult = $documentConverterService->extractContent(
                $filePath,
                $input['extraction_type'],
                $input['language'],
                $options
            );

            if (!$extractionResult || !isset($extractionResult['job_id'])) {
                $this->failJob($jobId, "Content extraction failed to start");
                return ['success' => false, 'error' => 'Content extraction failed to start'];
            }

            // Stage 4: Monitoring Extraction
            $this->updateJob($jobId, [
                'stage' => 'monitoring_extraction',
                'progress' => 80
            ]);
            $this->addLog($jobId, "Monitoring content extraction progress", 'info');

            // Poll for completion
            $maxAttempts = 60; // 5 minutes max
            $attempt = 0;
            
            $finalStatus = null;
            while ($attempt < $maxAttempts) {
                sleep(5); // Wait 5 seconds between checks
                $attempt++;
                
                $status = $documentConverterService->checkExtractionStatus($extractionResult['job_id']);
                
                if (isset($status['status']) && $status['status'] === 'completed') {
                    $finalStatus = $status; // Store the final status
                    break;
                } elseif (isset($status['status']) && $status['status'] === 'failed') {
                    $this->failJob($jobId, "Content extraction failed: " . ($status['error'] ?? 'Unknown error'));
                    return ['success' => false, 'error' => 'Content extraction failed'];
                }
            }

            if ($attempt >= $maxAttempts) {
                $this->failJob($jobId, "Content extraction timeout");
                return ['success' => false, 'error' => 'Content extraction timeout'];
            }

            // Stage 5: Finalizing
            $this->updateJob($jobId, [
                'stage' => 'finalizing',
                'progress' => 90
            ]);
            $this->addLog($jobId, "Finalizing content extraction result", 'info');

            // Use the final status response (since result endpoint returns null)
            $result = $finalStatus;
            
            if (!$result || !isset($result['status']) || $result['status'] !== 'completed') {
                $this->failJob($jobId, "Failed to get extraction result");
                return ['success' => false, 'error' => 'Failed to get extraction result'];
            }

            return [
                'success' => true,
                'data' => [
                    'extraction_result' => $result,
                    'original_file' => [
                        'id' => $file->id,
                        'filename' => $file->original_filename,
                        'size' => $file->file_size
                    ],
                    'extracted_content' => $result['content'] ?? '',
                    'metadata' => $result['metadata'] ?? [],
                    'word_count' => $result['word_count'] ?? 0,
                    'page_count' => $result['page_count'] ?? 0,
                    'language_detected' => $result['language_detected'] ?? $input['language'],
                    'extraction_method' => $result['extraction_method'] ?? 'unknown'
                ],
                'metadata' => [
                    'file_count' => 1,
                    'extraction_type' => $input['extraction_type'],
                    'language' => $input['language'],
                    'confidence_score' => 0.9,
                    'processing_stages' => ['validating_file', 'processing_file', 'extracting_content', 'monitoring_extraction', 'finalizing']
                ]
            ];

        } catch (\Exception $e) {
            $this->failJob($jobId, "Content extraction failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process PDF edit job with stages
     */
    private function processPdfEditJobWithStages($jobId, $input, $options)
    {
        try {
            $operation = $input['operation'] ?? null;
            $fileIds = $input['file_ids'] ?? [];
            $params = $input['params'] ?? [];

            if (!$operation) {
                $this->failJob($jobId, 'Operation is required');
                return ['success' => false, 'error' => 'Operation is required'];
            }

            $this->updateJob($jobId, [
                'stage' => 'validating',
                'progress' => 10
            ]);
            $this->addLog($jobId, 'Validating PDF operation input', 'info', [
                'operation' => $operation,
                'file_ids' => $fileIds
            ]);

            if (in_array($operation, ['merge','batch'])) {
                if (count($fileIds) < 2) {
                    $this->failJob($jobId, 'At least two files are required');
                    return ['success' => false, 'error' => 'At least two files are required'];
                }
            } else {
                if (count($fileIds) < 1) {
                    $this->failJob($jobId, 'file_id is required');
                    return ['success' => false, 'error' => 'file_id is required'];
                }
            }

            $this->updateJob($jobId, [
                'stage' => 'preparing_files',
                'progress' => 30
            ]);
            $this->addLog($jobId, 'Resolving file paths', 'info');

            $files = [];
            foreach ($fileIds as $fid) {
                $file = \App\Models\FileUpload::find($fid);
                if (!$file) {
                    $this->failJob($jobId, 'File not found: ' . $fid);
                    return ['success' => false, 'error' => 'File not found: ' . $fid];
                }
                $files[] = \Illuminate\Support\Facades\Storage::path($file->file_path);
            }

            $this->updateJob($jobId, [
                'stage' => 'starting_microservice_job',
                'progress' => 50
            ]);
            $this->addLog($jobId, 'Submitting operation to PDF microservice', 'info', [
                'operation' => $operation
            ]);

            $pdfService = app(\App\Services\PdfOperationsService::class);
            $start = $pdfService->startOperation($operation, $files, $params);

            if (!isset($start['job_id'])) {
                $this->failJob($jobId, 'Failed to start microservice job');
                return ['success' => false, 'error' => 'Failed to start microservice job'];
            }

            $remoteJobId = $start['job_id'];

            $this->updateJob($jobId, [
                'stage' => 'monitoring',
                'progress' => 70
            ]);
            $this->addLog($jobId, 'Monitoring PDF operation', 'info', [
                'remote_job_id' => $remoteJobId
            ]);

            // Keep total wait under PHP 600s CLI limit (10m) on some setups
            // 100 attempts * 5s = ~500s maximum
            $maxAttempts = 100;
            $attempt = 0;
            while ($attempt < $maxAttempts) {
                sleep(5);
                $attempt++;
                $status = $pdfService->getStatus($operation, $remoteJobId);
                if (($status['status'] ?? '') === 'completed') {
                    break;
                }
                if (($status['status'] ?? '') === 'failed') {
                    $this->failJob($jobId, 'PDF operation failed: ' . ($status['error'] ?? 'Unknown error'));
                    return ['success' => false, 'error' => 'PDF operation failed'];
                }
            }

            if ($attempt >= $maxAttempts) {
                $this->failJob($jobId, 'PDF operation timeout');
                return ['success' => false, 'error' => 'PDF operation timeout'];
            }

            $this->updateJob($jobId, [
                'stage' => 'fetching_result',
                'progress' => 90
            ]);
            $result = $pdfService->getResult($operation, $remoteJobId);

            $this->completeJob($jobId, [
                'remote_job_id' => $remoteJobId,
                'operation' => $operation,
                'result' => $result
            ], [
                'file_count' => count($files)
            ]);

            return [
                'success' => true,
                'data' => [
                    'remote_job_id' => $remoteJobId,
                    'operation' => $operation,
                    'result' => $result
                ],
                'metadata' => [
                    'file_count' => count($files),
                    'processing_stages' => ['validating','preparing_files','starting_microservice_job','monitoring','fetching_result']
                ]
            ];
        } catch (\Exception $e) {
            $this->failJob($jobId, 'PDF operation error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process Document Intelligence job with stages
     */
    private function processDocumentIntelligenceJobWithStages($jobId, $input, $options)
    {
        try {
            $action = $input['action'] ?? null; // 'ingest', 'search', 'answer', 'chat'
            $fileId = $input['file_id'] ?? null;
            $query = $input['query'] ?? null;
            $docIds = $input['doc_ids'] ?? [];
            $params = $input['params'] ?? [];

            if (!$action) {
                $this->failJob($jobId, 'Action is required (ingest, search, answer, chat)');
                return ['success' => false, 'error' => 'Action is required'];
            }

            $this->updateJob($jobId, [
                'stage' => 'validating',
                'progress' => 10
            ]);
            $this->addLog($jobId, 'Validating document intelligence input', 'info', [
                'action' => $action,
                'file_id' => $fileId,
                'doc_ids' => $docIds
            ]);

            // Get document intelligence service
            $docService = app(\App\Services\DocumentIntelligenceService::class);

            // Handle different actions
            if ($action === 'ingest') {
                // Ingest document
                if (!$fileId) {
                    $this->failJob($jobId, 'file_id is required for ingestion');
                    return ['success' => false, 'error' => 'file_id is required'];
                }

                $this->updateJob($jobId, [
                    'stage' => 'preparing_file',
                    'progress' => 20
                ]);
                $this->addLog($jobId, 'Preparing file for ingestion', 'info');

                // Start ingestion
                $this->updateJob($jobId, [
                    'stage' => 'starting_ingestion',
                    'progress' => 30
                ]);
                $this->addLog($jobId, 'Starting document ingestion', 'info');

                $ingestionOptions = [
                    'ocr' => $params['ocr'] ?? 'auto',
                    'lang' => $params['lang'] ?? 'eng',
                    'metadata' => $params['metadata'] ?? []
                ];

                $ingestionResult = $docService->ingestFromFileId($fileId, $ingestionOptions);
                $remoteJobId = $ingestionResult['job_id'] ?? null;
                $docId = $ingestionResult['doc_id'] ?? null;

                if (!$remoteJobId) {
                    $this->failJob($jobId, 'Failed to start ingestion: no job_id returned');
                    return ['success' => false, 'error' => 'Failed to start ingestion'];
                }

                $this->updateJob($jobId, [
                    'stage' => 'monitoring',
                    'progress' => 50,
                    'metadata' => array_merge($this->getJob($jobId)['metadata'], [
                        'remote_job_id' => $remoteJobId,
                        'doc_id' => $docId
                    ])
                ]);
                $this->addLog($jobId, 'Monitoring ingestion job', 'info', [
                    'remote_job_id' => $remoteJobId,
                    'doc_id' => $docId
                ]);

                // Poll until completion
                $finalStatus = $docService->pollJobCompletion($remoteJobId, 60, 2);

                if ($finalStatus['status'] !== 'completed') {
                    $this->failJob($jobId, 'Ingestion failed: ' . ($finalStatus['error'] ?? 'Unknown error'));
                    return ['success' => false, 'error' => 'Ingestion failed'];
                }

                $this->updateJob($jobId, [
                    'stage' => 'completed',
                    'progress' => 100,
                    'status' => 'completed',
                    'result' => [
                        'doc_id' => $docId,
                        'job_id' => $remoteJobId,
                        'status' => 'completed'
                    ]
                ]);
                $this->addLog($jobId, 'Document ingestion completed', 'success');

                return [
                    'success' => true,
                    'data' => [
                        'doc_id' => $docId,
                        'remote_job_id' => $remoteJobId,
                        'action' => 'ingest'
                    ]
                ];

            } elseif ($action === 'ingest_text') {
                // Ingest text content directly
                $text = $input['text'] ?? null;
                if (!$text) {
                    $this->failJob($jobId, 'text is required for text ingestion');
                    return ['success' => false, 'error' => 'text is required'];
                }

                $this->updateJob($jobId, [
                    'stage' => 'preparing_text',
                    'progress' => 20
                ]);
                $this->addLog($jobId, 'Preparing text for ingestion', 'info', [
                    'text_length' => strlen($text)
                ]);

                // Start text ingestion
                $this->updateJob($jobId, [
                    'stage' => 'starting_ingestion',
                    'progress' => 30
                ]);
                $this->addLog($jobId, 'Starting text ingestion', 'info');

                $ingestionOptions = [
                    'filename' => $params['filename'] ?? 'summary.txt',
                    'lang' => $params['lang'] ?? 'eng',
                    'llm_model' => $params['llm_model'] ?? 'llama3',
                    'force_fallback' => $params['force_fallback'] ?? true,
                    'metadata' => $params['metadata'] ?? []
                ];

                $ingestionResult = $docService->ingestText($text, $ingestionOptions);
                $remoteJobId = $ingestionResult['job_id'] ?? null;
                $docId = $ingestionResult['doc_id'] ?? null;

                if (!$remoteJobId) {
                    $this->failJob($jobId, 'Failed to start text ingestion: no job_id returned');
                    return ['success' => false, 'error' => 'Failed to start text ingestion'];
                }

                $this->updateJob($jobId, [
                    'stage' => 'monitoring',
                    'progress' => 50,
                    'metadata' => array_merge($this->getJob($jobId)['metadata'], [
                        'remote_job_id' => $remoteJobId,
                        'doc_id' => $docId
                    ])
                ]);
                $this->addLog($jobId, 'Monitoring text ingestion job', 'info', [
                    'remote_job_id' => $remoteJobId,
                    'doc_id' => $docId
                ]);

                // Poll until completion
                $finalStatus = $docService->pollJobCompletion($remoteJobId, 60, 2);

                if ($finalStatus['status'] !== 'completed') {
                    $this->failJob($jobId, 'Text ingestion failed: ' . ($finalStatus['error'] ?? 'Unknown error'));
                    return ['success' => false, 'error' => 'Text ingestion failed'];
                }

                $this->updateJob($jobId, [
                    'stage' => 'completed',
                    'progress' => 100,
                    'status' => 'completed',
                    'result' => [
                        'doc_id' => $docId,
                        'job_id' => $remoteJobId,
                        'status' => 'completed'
                    ]
                ]);
                $this->addLog($jobId, 'Text ingestion completed', 'success');

                return [
                    'success' => true,
                    'data' => [
                        'doc_id' => $docId,
                        'remote_job_id' => $remoteJobId,
                        'action' => 'ingest_text'
                    ]
                ];

            } elseif ($action === 'search') {
                // Semantic search
                if (!$query) {
                    $this->failJob($jobId, 'query is required for search');
                    return ['success' => false, 'error' => 'query is required'];
                }

                $this->updateJob($jobId, [
                    'stage' => 'searching',
                    'progress' => 50
                ]);
                $this->addLog($jobId, 'Performing semantic search', 'info');

                $searchOptions = [
                    'doc_ids' => $docIds,
                    'top_k' => $params['top_k'] ?? 5,
                    'filters' => $params['filters'] ?? []
                ];

                $searchResult = $docService->search($query, $searchOptions);

                $this->updateJob($jobId, [
                    'stage' => 'completed',
                    'progress' => 100,
                    'status' => 'completed',
                    'result' => $searchResult
                ]);
                $this->addLog($jobId, 'Search completed', 'success', [
                    'result_count' => count($searchResult['results'] ?? [])
                ]);

                return [
                    'success' => true,
                    'data' => [
                        'action' => 'search',
                        'result' => $searchResult
                    ]
                ];

            } elseif ($action === 'answer') {
                // RAG-powered Q&A
                if (!$query) {
                    $this->failJob($jobId, 'query is required for answer');
                    return ['success' => false, 'error' => 'query is required'];
                }

                if (empty($docIds)) {
                    $this->failJob($jobId, 'doc_ids are required for answer');
                    return ['success' => false, 'error' => 'doc_ids are required'];
                }

                $this->updateJob($jobId, [
                    'stage' => 'generating_answer',
                    'progress' => 50
                ]);
                $this->addLog($jobId, 'Generating RAG answer', 'info');

                $answerOptions = [
                    'doc_ids' => $docIds,
                    'llm_model' => $params['llm_model'] ?? 'llama3',
                    'max_tokens' => $params['max_tokens'] ?? 512,
                    'top_k' => $params['top_k'] ?? 3,
                    'temperature' => $params['temperature'] ?? null,
                    'filters' => $params['filters'] ?? []
                ];

                $answerResult = $docService->answer($query, $answerOptions);

                $this->updateJob($jobId, [
                    'stage' => 'completed',
                    'progress' => 100,
                    'status' => 'completed',
                    'result' => $answerResult
                ]);
                $this->addLog($jobId, 'Answer generated', 'success');

                return [
                    'success' => true,
                    'data' => [
                        'action' => 'answer',
                        'result' => $answerResult
                    ]
                ];

            } elseif ($action === 'chat') {
                // Conversational chat
                if (!$query) {
                    $this->failJob($jobId, 'query is required for chat');
                    return ['success' => false, 'error' => 'query is required'];
                }

                if (empty($docIds)) {
                    $this->failJob($jobId, 'doc_ids are required for chat');
                    return ['success' => false, 'error' => 'doc_ids are required'];
                }

                $this->updateJob($jobId, [
                    'stage' => 'chatting',
                    'progress' => 50
                ]);
                $this->addLog($jobId, 'Processing chat message', 'info');

                $chatOptions = [
                    'doc_ids' => $docIds,
                    'conversation_id' => $params['conversation_id'] ?? null,
                    'llm_model' => $params['llm_model'] ?? 'llama3',
                    'max_tokens' => $params['max_tokens'] ?? 512,
                    'top_k' => $params['top_k'] ?? 3,
                    'filters' => $params['filters'] ?? []
                ];

                $chatResult = $docService->chat($query, $chatOptions);

                $this->updateJob($jobId, [
                    'stage' => 'completed',
                    'progress' => 100,
                    'status' => 'completed',
                    'result' => $chatResult
                ]);
                $this->addLog($jobId, 'Chat response generated', 'success');

                return [
                    'success' => true,
                    'data' => [
                        'action' => 'chat',
                        'result' => $chatResult
                    ]
                ];

            } else {
                $this->failJob($jobId, "Unsupported action: {$action}");
                return ['success' => false, 'error' => "Unsupported action: {$action}"];
            }

        } catch (\Exception $e) {
            $errorMessage = 'Document intelligence error: ' . $e->getMessage();
            $this->failJob($jobId, $errorMessage);
            Log::error("Universal Job {$jobId}: Document intelligence exception", [
                'error' => $e->getMessage(),
                'exception_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'error' => $errorMessage,
                'error_details' => [
                    'exception_type' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ];
        }
    }

    /**
     * Process presentation job (outline, content, or export)
     * Manually constructs AIPresentationService to avoid circular dependency
     */
    private function processPresentationJob($job, $toolType)
    {
        try {
            // Manually construct dependencies to avoid circular dependency
            // These services don't depend on UniversalFileManagementModule
            $aiManagerService = new \App\Services\AIManagerService();
            $aiProcessingModule = new \App\Services\Modules\AIProcessingModule($aiManagerService);
            $aiResultService = new \App\Services\AIResultService();
            
            // ContentExtractionService dependencies
            // Build from bottom up to avoid circular dependencies
            $youtubeTranscriberService = new \App\Services\YouTubeTranscriberService();
            $webScrapingService = new \App\Services\WebScrapingService();
            $transcriberModule = new \App\Services\Modules\TranscriberModule(
                $youtubeTranscriberService,
                $webScrapingService
            );
            $youtubeService = new \App\Services\YouTubeService($transcriberModule);
            $documentConverterService = new \App\Services\DocumentConverterService();
            $contentExtractionService = new \App\Services\Modules\ContentExtractionService(
                $youtubeService,
                $documentConverterService,
                $webScrapingService
            );
            
            // Manually construct AIPresentationService with $this to break cycle
            $presentationService = new \App\Services\AIPresentationService(
                $aiProcessingModule,
                $aiResultService,
                $contentExtractionService,
                $this // Pass current instance to break cycle
            );
            
            // Call appropriate method based on tool type
            switch ($toolType) {
                case 'presentation_outline':
                    return $presentationService->processOutlineJob($job['id'], $job);
                case 'presentation_content':
                    return $presentationService->processContentJob($job['id'], $job);
                case 'presentation_export':
                    return $presentationService->processExportJob($job['id'], $job);
                default:
                    throw new \Exception("Unknown presentation tool type: {$toolType}");
            }
        } catch (\Exception $e) {
            Log::error('Presentation job processing failed', [
                'error' => $e->getMessage(),
                'job_id' => $job['id'] ?? null,
                'tool_type' => $toolType
            ]);
            
            $this->failJob($job['id'] ?? 'unknown', 'Presentation job failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process content writer job (write or rewrite)
     */
    private function processContentWriterJob($job, $toolType)
    {
        try {
            $jobId = $job['id'];
            $input = $job['input'];
            $options = $job['options'];
            $userId = $job['user_id'] ?? null;

            // Initialize ModuleRegistry if not already initialized
            \App\Services\Modules\ModuleRegistry::initialize();
            
            // Get Content Writer Module from ModuleRegistry
            $writerModule = \App\Services\Modules\ModuleRegistry::getModule('content');

            // Stage 1: Initializing
            $this->updateJob($jobId, [
                'stage' => 'initializing',
                'progress' => 0,
                'metadata' => array_merge($job['metadata'] ?? [], [
                    'processing_started_at' => now()->toISOString()
                ])
            ]);
            $this->addLog($jobId, "Initializing content writer job", 'info');

            // Stage 2: Validating input
            $this->updateJob($jobId, [
                'stage' => 'validating_input',
                'progress' => 10
            ]);
            $this->addLog($jobId, "Validating input", 'info');

            // Extract parameters
            $mode = $options['mode'] ?? 'professional';

            // Stage 3: Generating content
            $this->updateJob($jobId, [
                'stage' => 'generating_content',
                'progress' => 30
            ]);
            $this->addLog($jobId, "Generating content with AI", 'info');

            $result = null;
            if ($toolType === 'content_write') {
                // Write new content
                $prompt = $input['prompt'] ?? '';
                if (empty($prompt)) {
                    throw new \Exception('Prompt is required for content writing');
                }

                $result = $writerModule->generateContent($prompt, $mode);

                // Update progress during generation
                $this->updateJob($jobId, [
                    'progress' => 60
                ]);
                $this->addLog($jobId, "Content generation in progress", 'info');

            } else if ($toolType === 'content_rewrite') {
                // Rewrite existing content
                $previousContent = $input['previous_content'] ?? '';
                $newPrompt = $input['prompt'] ?? '';

                if (empty($previousContent)) {
                    throw new \Exception('Previous content is required for rewriting');
                }
                if (empty($newPrompt)) {
                    throw new \Exception('Rewrite prompt is required');
                }

                $result = $writerModule->rewriteContent($previousContent, $newPrompt, $mode);

                // Update progress during rewriting
                $this->updateJob($jobId, [
                    'progress' => 60
                ]);
                $this->addLog($jobId, "Content rewriting in progress", 'info');
            } else {
                throw new \Exception("Unknown content writer tool type: {$toolType}");
            }

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Content generation/rewriting failed');
            }

            // Stage 4: Refining content
            $this->updateJob($jobId, [
                'stage' => 'refining_content',
                'progress' => 80
            ]);
            $this->addLog($jobId, "Refining generated content", 'info');

            // Stage 5: Finalizing
            $this->updateJob($jobId, [
                'stage' => 'finalizing',
                'progress' => 95
            ]);
            $this->addLog($jobId, "Finalizing content", 'info');

            // Prepare result data
            $resultData = [
                'content' => $result['content'],
                'word_count' => $result['word_count'] ?? 0,
                'character_count' => $result['character_count'] ?? 0,
                'mode' => $mode,
                'metadata' => $result['metadata'] ?? []
            ];

            // Stage 6: Completed
            $this->updateJob($jobId, [
                'status' => 'completed',
                'stage' => 'completed',
                'progress' => 100,
                'result' => $resultData,
                'metadata' => array_merge($job['metadata'] ?? [], [
                    'processing_completed_at' => now()->toISOString(),
                    'tool_type' => $toolType,
                    'mode' => $mode,
                    'model_used' => $result['metadata']['model_used'] ?? 'deepseek-chat'
                ])
            ]);
            $this->addLog($jobId, "Content generation completed successfully", 'info');

            // Calculate processing time
            $processingTime = $this->calculateProcessingTime($this->getJob($jobId));
            if ($processingTime) {
                $this->updateJob($jobId, [
                    'metadata' => array_merge($this->getJob($jobId)['metadata'] ?? [], [
                        'total_processing_time' => $processingTime
                    ])
                ]);
            }

            return [
                'success' => true,
                'data' => $resultData
            ];

        } catch (\Exception $e) {
            Log::error('Content writer job processing failed', [
                'error' => $e->getMessage(),
                'job_id' => $job['id'] ?? null,
                'tool_type' => $toolType
            ]);
            
            $this->failJob($job['id'] ?? 'unknown', 'Content writer job failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Calculate processing time
     */
    private function calculateProcessingTime($job)
    {
        $started = $job['metadata']['processing_started_at'] ?? null;
        $completed = $job['metadata']['processing_completed_at'] ?? now()->toISOString();
        
        if ($started) {
            $start = \Carbon\Carbon::parse($started);
            $end = \Carbon\Carbon::parse($completed);
            return $start->diffInSeconds($end);
        }
        
        return null;
    }
}
