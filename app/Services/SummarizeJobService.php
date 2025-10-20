<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SummarizeJobService
{
    private $cachePrefix = 'summarize_job_';
    private $defaultTtl = 3600; // 1 hour

    /**
     * Create a new summarize job
     */
    public function createJob($userId, $contentType, $source, $options = [])
    {
        $jobId = Str::uuid()->toString();
        $jobData = [
            'id' => $jobId,
            'user_id' => $userId,
            'content_type' => $contentType,
            'source' => $source,
            'options' => $options,
            'status' => 'queued',
            'stage' => 'initializing',
            'progress' => 0,
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
            'logs' => [],
            'result' => null,
            'error' => null
        ];

        $this->saveJob($jobId, $jobData);
        
        Log::info("Summarize job created", [
            'job_id' => $jobId,
            'user_id' => $userId,
            'content_type' => $contentType
        ]);

        return $jobData;
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

        $this->saveJob($jobId, $job);
        
        Log::info("Summarize job updated", [
            'job_id' => $jobId,
            'status' => $job['status'],
            'stage' => $job['stage'],
            'progress' => $job['progress']
        ]);

        return $job;
    }

    /**
     * Add log entry to job
     */
    public function addLog($jobId, $message, $level = 'info')
    {
        $job = $this->getJob($jobId);
        if (!$job) {
            return false;
        }

        $logEntry = [
            'timestamp' => now()->toISOString(),
            'level' => $level,
            'message' => $message
        ];

        $job['logs'][] = $logEntry;
        $job['updated_at'] = now()->toISOString();

        $this->saveJob($jobId, $job);
        return true;
    }

    /**
     * Complete job with result
     */
    public function completeJob($jobId, $result)
    {
        return $this->updateJob($jobId, [
            'status' => 'completed',
            'stage' => 'done',
            'progress' => 100,
            'result' => $result
        ]);
    }

    /**
     * Fail job with error
     */
    public function failJob($jobId, $error)
    {
        return $this->updateJob($jobId, [
            'status' => 'failed',
            'stage' => 'error',
            'progress' => 0,
            'error' => $error
        ]);
    }

    /**
     * Get job by ID
     */
    public function getJob($jobId)
    {
        return Cache::get($this->cachePrefix . $jobId);
    }

    /**
     * Save job to cache
     */
    private function saveJob($jobId, $jobData)
    {
        Cache::put($this->cachePrefix . $jobId, $jobData, $this->defaultTtl);
    }

    /**
     * Delete job
     */
    public function deleteJob($jobId)
    {
        Cache::forget($this->cachePrefix . $jobId);
    }

    /**
     * Get job status for API response
     */
    public function getJobStatus($jobId)
    {
        $job = $this->getJob($jobId);
        if (!$job) {
            return null;
        }

        return [
            'job_id' => $job['id'],
            'status' => $job['status'],
            'stage' => $job['stage'],
            'progress' => $job['progress'],
            'created_at' => $job['created_at'],
            'updated_at' => $job['updated_at'],
            'logs' => array_slice($job['logs'], -10), // Last 10 logs
            'result' => $job['result'],
            'error' => $job['error']
        ];
    }

    /**
     * Get job result (only if completed)
     */
    public function getJobResult($jobId)
    {
        $job = $this->getJob($jobId);
        if (!$job || $job['status'] !== 'completed') {
            return null;
        }

        return $job['result'];
    }

    /**
     * Check if job exists
     */
    public function jobExists($jobId)
    {
        return $this->getJob($jobId) !== null;
    }

    /**
     * Get user's jobs
     */
    public function getUserJobs($userId, $limit = 10)
    {
        // This is a simplified implementation
        // In production, you might want to use a database instead of cache
        $jobs = [];
        $pattern = $this->cachePrefix . '*';
        
        // Note: This is a simplified approach. In production, consider using Redis SCAN
        // or a proper database for job tracking
        return $jobs;
    }
}
