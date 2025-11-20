<?php

namespace App\Services;

use App\Services\Modules\UniversalFileManagementModule;
use App\Services\UniversalJobService;
use App\Services\FileProcessingMetricsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class BatchFileProcessingService
{
    private $universalFileModule;
    private $universalJobService;
    private $metricsService;

    public function __construct(
        UniversalFileManagementModule $universalFileModule,
        UniversalJobService $universalJobService,
        FileProcessingMetricsService $metricsService
    ) {
        $this->universalFileModule = $universalFileModule;
        $this->universalJobService = $universalJobService;
        $this->metricsService = $metricsService;
    }

    /**
     * Create batch processing job
     */
    public function createBatchJob($toolType, $files, $options = [], $userId = null)
    {
        $batchId = \Illuminate\Support\Str::uuid()->toString();
        
        $batchJob = [
            'id' => $batchId,
            'tool_type' => $toolType,
            'files' => $files,
            'options' => $options,
            'user_id' => $userId,
            'status' => 'pending',
            'stage' => 'initializing',
            'progress' => 0,
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
            'logs' => [],
            'results' => [],
            'errors' => [],
            'metadata' => [
                'total_files' => count($files),
                'processed_files' => 0,
                'successful_files' => 0,
                'failed_files' => 0,
                'processing_started_at' => null,
                'processing_completed_at' => null,
                'total_processing_time' => null
            ]
        ];

        // Store batch job in cache
        Cache::put("batch_job_{$batchId}", $batchJob, 3600);
        
        Log::info("Batch processing job created", [
            'batch_id' => $batchId,
            'tool_type' => $toolType,
            'file_count' => count($files),
            'user_id' => $userId
        ]);

        return $batchJob;
    }

    /**
     * Process batch job
     */
    public function processBatchJob($batchId)
    {
        $batchJob = Cache::get("batch_job_{$batchId}");
        if (!$batchJob) {
            throw new \Exception("Batch job not found: {$batchId}");
        }

        try {
            $this->updateBatchJob($batchId, [
                'status' => 'running',
                'stage' => 'processing',
                'progress' => 0,
                'metadata' => array_merge($batchJob['metadata'], [
                    'processing_started_at' => now()->toISOString()
                ])
            ]);

            $this->addBatchLog($batchId, "Starting batch processing of {$batchJob['metadata']['total_files']} files");

            $results = [];
            $errors = [];
            $processedCount = 0;

            foreach ($batchJob['files'] as $index => $file) {
                try {
                    $this->addBatchLog($batchId, "Processing file " . ($index + 1) . " of {$batchJob['metadata']['total_files']}");
                    
                    $result = $this->processFileInBatch($file, $batchJob['tool_type'], $batchJob['options']);
                    
                    if ($result['success']) {
                        $results[] = [
                            'file_id' => $file['id'] ?? $file['file_id'],
                            'file_name' => $file['name'] ?? 'Unknown',
                            'result' => $result['data'],
                            'processing_time' => $result['processing_time'] ?? 0
                        ];
                        $batchJob['metadata']['successful_files']++;
                    } else {
                        $errors[] = [
                            'file_id' => $file['id'] ?? $file['file_id'],
                            'file_name' => $file['name'] ?? 'Unknown',
                            'error' => $result['error'],
                            'processing_time' => $result['processing_time'] ?? 0
                        ];
                        $batchJob['metadata']['failed_files']++;
                    }
                    
                    $processedCount++;
                    $progress = ($processedCount / $batchJob['metadata']['total_files']) * 100;
                    
                    $this->updateBatchJob($batchId, [
                        'progress' => $progress,
                        'metadata' => array_merge($batchJob['metadata'], [
                            'processed_files' => $processedCount
                        ])
                    ]);

                } catch (\Exception $e) {
                    $errors[] = [
                        'file_id' => $file['id'] ?? $file['file_id'],
                        'file_name' => $file['name'] ?? 'Unknown',
                        'error' => $e->getMessage(),
                        'processing_time' => 0
                    ];
                    $batchJob['metadata']['failed_files']++;
                    
                    $this->addBatchLog($batchId, "Error processing file: " . $e->getMessage(), 'error');
                }
            }

            // Complete batch job
            $this->completeBatchJob($batchId, $results, $errors);

            return [
                'success' => true,
                'batch_id' => $batchId,
                'results' => $results,
                'errors' => $errors,
                'metadata' => $batchJob['metadata']
            ];

        } catch (\Exception $e) {
            $this->failBatchJob($batchId, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process individual file in batch
     */
    private function processFileInBatch($file, $toolType, $options)
    {
        $startTime = microtime(true);
        
        try {
            if (isset($file['file_id'])) {
                // Process existing file
                $result = $this->universalFileModule->processFile($file['file_id'], $toolType, $options);
            } else {
                // Upload and process new file
                $uploadResult = $this->universalFileModule->uploadFile($file['file'], $file['user_id'], $toolType, $options);
                
                if (!$uploadResult['success']) {
                    throw new \Exception($uploadResult['error']);
                }
                
                $result = $this->universalFileModule->processFile($uploadResult['file_upload']->id, $toolType, $options);
            }
            
            $processingTime = microtime(true) - $startTime;
            
            // Record metrics
            $this->metricsService->recordMetrics(
                $toolType,
                $file['type'] ?? 'unknown',
                $processingTime,
                $result['success'],
                ['batch_processing' => true]
            );
            
            return [
                'success' => $result['success'],
                'data' => $result['result'] ?? null,
                'error' => $result['error'] ?? null,
                'processing_time' => $processingTime
            ];
            
        } catch (\Exception $e) {
            $processingTime = microtime(true) - $startTime;
            
            // Record failed metrics
            $this->metricsService->recordMetrics(
                $toolType,
                $file['type'] ?? 'unknown',
                $processingTime,
                false,
                ['batch_processing' => true, 'error' => $e->getMessage()]
            );
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'processing_time' => $processingTime
            ];
        }
    }

    /**
     * Get batch job status
     */
    public function getBatchJob($batchId)
    {
        return Cache::get("batch_job_{$batchId}");
    }

    /**
     * Update batch job
     */
    public function updateBatchJob($batchId, $updates)
    {
        $batchJob = Cache::get("batch_job_{$batchId}");
        if (!$batchJob) {
            return false;
        }

        $batchJob = array_merge($batchJob, $updates);
        $batchJob['updated_at'] = now()->toISOString();
        
        Cache::put("batch_job_{$batchId}", $batchJob, 3600);
        
        return true;
    }

    /**
     * Add log to batch job
     */
    public function addBatchLog($batchId, $message, $level = 'info', $data = [])
    {
        $batchJob = Cache::get("batch_job_{$batchId}");
        if (!$batchJob) {
            return false;
        }

        $logEntry = [
            'timestamp' => now()->toISOString(),
            'level' => $level,
            'message' => $message,
            'data' => $data
        ];

        $batchJob['logs'][] = $logEntry;
        $batchJob['updated_at'] = now()->toISOString();
        
        Cache::put("batch_job_{$batchId}", $batchJob, 3600);
        
        Log::log($level, "Batch Job {$batchId}: {$message}", $data);
        
        return true;
    }

    /**
     * Complete batch job
     */
    public function completeBatchJob($batchId, $results, $errors)
    {
        $batchJob = Cache::get("batch_job_{$batchId}");
        if (!$batchJob) {
            return false;
        }

        $batchJob['status'] = 'completed';
        $batchJob['stage'] = 'completed';
        $batchJob['progress'] = 100;
        $batchJob['results'] = $results;
        $batchJob['errors'] = $errors;
        $batchJob['updated_at'] = now()->toISOString();
        $batchJob['metadata'] = array_merge($batchJob['metadata'], [
            'processing_completed_at' => now()->toISOString(),
            'total_processing_time' => $this->calculateBatchProcessingTime($batchJob)
        ]);

        Cache::put("batch_job_{$batchId}", $batchJob, 3600);
        
        $this->addBatchLog($batchId, "Batch processing completed successfully");
        
        return true;
    }

    /**
     * Fail batch job
     */
    public function failBatchJob($batchId, $error)
    {
        $batchJob = Cache::get("batch_job_{$batchId}");
        if (!$batchJob) {
            return false;
        }

        $batchJob['status'] = 'failed';
        $batchJob['stage'] = 'failed';
        $batchJob['error'] = $error;
        $batchJob['updated_at'] = now()->toISOString();
        $batchJob['metadata'] = array_merge($batchJob['metadata'], [
            'processing_completed_at' => now()->toISOString(),
            'total_processing_time' => $this->calculateBatchProcessingTime($batchJob)
        ]);

        Cache::put("batch_job_{$batchId}", $batchJob, 3600);
        
        $this->addBatchLog($batchId, "Batch processing failed: {$error}", 'error');
        
        return true;
    }

    /**
     * Get user's batch jobs
     */
    public function getUserBatchJobs($userId, $status = null, $perPage = 15)
    {
        // This would typically query a database
        // For now, return mock data
        return [
            'batch_jobs' => [],
            'pagination' => [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $perPage,
                'total' => 0
            ]
        ];
    }

    /**
     * Get batch processing statistics
     */
    public function getBatchStats($userId = null)
    {
        return [
            'total_batches' => 0,
            'completed_batches' => 0,
            'failed_batches' => 0,
            'average_files_per_batch' => 0,
            'average_processing_time' => 0,
            'success_rate' => 0.0
        ];
    }

    /**
     * Cancel batch job
     */
    public function cancelBatchJob($batchId)
    {
        $batchJob = Cache::get("batch_job_{$batchId}");
        if (!$batchJob) {
            return false;
        }

        if ($batchJob['status'] === 'running') {
            $this->updateBatchJob($batchId, [
                'status' => 'cancelled',
                'stage' => 'cancelled'
            ]);
            
            $this->addBatchLog($batchId, "Batch processing cancelled by user");
            
            return true;
        }
        
        return false;
    }

    /**
     * Retry failed batch job
     */
    public function retryBatchJob($batchId)
    {
        $batchJob = Cache::get("batch_job_{$batchId}");
        if (!$batchJob) {
            return false;
        }

        if ($batchJob['status'] === 'failed') {
            // Reset batch job for retry
            $this->updateBatchJob($batchId, [
                'status' => 'pending',
                'stage' => 'initializing',
                'progress' => 0,
                'error' => null,
                'metadata' => array_merge($batchJob['metadata'], [
                    'processed_files' => 0,
                    'successful_files' => 0,
                    'failed_files' => 0,
                    'processing_started_at' => null,
                    'processing_completed_at' => null,
                    'total_processing_time' => null
                ])
            ]);
            
            $this->addBatchLog($batchId, "Batch job reset for retry");
            
            return true;
        }
        
        return false;
    }

    /**
     * Helper methods
     */
    private function calculateBatchProcessingTime($batchJob)
    {
        $started = $batchJob['metadata']['processing_started_at'] ?? null;
        $completed = $batchJob['metadata']['processing_completed_at'] ?? now()->toISOString();
        
        if ($started) {
            $start = \Carbon\Carbon::parse($started);
            $end = \Carbon\Carbon::parse($completed);
            return $start->diffInSeconds($end);
        }
        
        return null;
    }
}



































