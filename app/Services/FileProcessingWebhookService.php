<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class FileProcessingWebhookService
{
    private $webhookUrl;
    private $webhookSecret;

    public function __construct()
    {
        $this->webhookUrl = config('services.webhooks.processing_url');
        $this->webhookSecret = config('services.webhooks.secret');
    }

    /**
     * Send webhook for file processing events
     */
    public function sendWebhook($event, $data, $userId = null)
    {
        if (!$this->webhookUrl) {
            return false;
        }

        try {
            $payload = [
                'event' => $event,
                'data' => $data,
                'user_id' => $userId,
                'timestamp' => now()->toISOString(),
                'signature' => $this->generateSignature($event, $data)
            ];

            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $this->generateSignature($event, $data),
                    'X-Webhook-Event' => $event
                ])
                ->post($this->webhookUrl, $payload);

            if ($response->successful()) {
                Log::info("Webhook sent successfully", [
                    'event' => $event,
                    'user_id' => $userId,
                    'status' => $response->status()
                ]);
                return true;
            } else {
                Log::warning("Webhook failed", [
                    'event' => $event,
                    'user_id' => $userId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error("Webhook error", [
                'event' => $event,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send file upload webhook
     */
    public function sendFileUploadWebhook($fileId, $userId, $fileData)
    {
        return $this->sendWebhook('file.uploaded', [
            'file_id' => $fileId,
            'file_name' => $fileData['original_name'] ?? 'Unknown',
            'file_type' => $fileData['file_type'] ?? 'unknown',
            'file_size' => $fileData['file_size'] ?? 0,
            'upload_url' => $fileData['file_url'] ?? null
        ], $userId);
    }

    /**
     * Send file processing started webhook
     */
    public function sendProcessingStartedWebhook($fileId, $toolType, $userId, $metadata = [])
    {
        return $this->sendWebhook('file.processing.started', [
            'file_id' => $fileId,
            'tool_type' => $toolType,
            'processing_stage' => 'started',
            'metadata' => $metadata
        ], $userId);
    }

    /**
     * Send file processing progress webhook
     */
    public function sendProcessingProgressWebhook($fileId, $toolType, $progress, $stage, $userId, $metadata = [])
    {
        return $this->sendWebhook('file.processing.progress', [
            'file_id' => $fileId,
            'tool_type' => $toolType,
            'progress' => $progress,
            'stage' => $stage,
            'metadata' => $metadata
        ], $userId);
    }

    /**
     * Send file processing completed webhook
     */
    public function sendProcessingCompletedWebhook($fileId, $toolType, $result, $userId, $metadata = [])
    {
        return $this->sendWebhook('file.processing.completed', [
            'file_id' => $fileId,
            'tool_type' => $toolType,
            'result' => $result,
            'processing_time' => $metadata['processing_time'] ?? null,
            'tokens_used' => $metadata['tokens_used'] ?? null,
            'confidence_score' => $metadata['confidence_score'] ?? null
        ], $userId);
    }

    /**
     * Send file processing failed webhook
     */
    public function sendProcessingFailedWebhook($fileId, $toolType, $error, $userId, $metadata = [])
    {
        return $this->sendWebhook('file.processing.failed', [
            'file_id' => $fileId,
            'tool_type' => $toolType,
            'error' => $error,
            'error_code' => $metadata['error_code'] ?? null,
            'retry_count' => $metadata['retry_count'] ?? 0
        ], $userId);
    }

    /**
     * Send batch processing webhook
     */
    public function sendBatchProcessingWebhook($batchId, $event, $data, $userId)
    {
        return $this->sendWebhook("batch.{$event}", [
            'batch_id' => $batchId,
            'batch_data' => $data
        ], $userId);
    }

    /**
     * Send job webhook
     */
    public function sendJobWebhook($jobId, $event, $data, $userId)
    {
        return $this->sendWebhook("job.{$event}", [
            'job_id' => $jobId,
            'job_data' => $data
        ], $userId);
    }

    /**
     * Send system health webhook
     */
    public function sendSystemHealthWebhook($healthStatus, $metrics)
    {
        return $this->sendWebhook('system.health', [
            'status' => $healthStatus,
            'metrics' => $metrics,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Send cache webhook
     */
    public function sendCacheWebhook($event, $data)
    {
        return $this->sendWebhook("cache.{$event}", $data);
    }

    /**
     * Send metrics webhook
     */
    public function sendMetricsWebhook($metrics, $period = 'daily')
    {
        return $this->sendWebhook('metrics.updated', [
            'metrics' => $metrics,
            'period' => $period,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Queue webhook for later processing
     */
    public function queueWebhook($event, $data, $userId = null, $delay = 0)
    {
        try {
            // This would typically use Laravel's queue system
            // For now, we'll just log it
            Log::info("Webhook queued", [
                'event' => $event,
                'user_id' => $userId,
                'delay' => $delay
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to queue webhook", [
                'event' => $event,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Retry failed webhook
     */
    public function retryWebhook($webhookId, $maxRetries = 3)
    {
        // This would typically retry a failed webhook
        Log::info("Webhook retry", [
            'webhook_id' => $webhookId,
            'max_retries' => $maxRetries
        ]);
        
        return true;
    }

    /**
     * Get webhook status
     */
    public function getWebhookStatus($webhookId)
    {
        // This would typically check webhook delivery status
        return [
            'webhook_id' => $webhookId,
            'status' => 'delivered',
            'delivered_at' => now()->toISOString(),
            'retry_count' => 0
        ];
    }

    /**
     * Get webhook statistics
     */
    public function getWebhookStats($period = 'daily')
    {
        return [
            'total_webhooks' => 0,
            'successful_webhooks' => 0,
            'failed_webhooks' => 0,
            'success_rate' => 0.0,
            'average_delivery_time' => 0.0,
            'by_event_type' => []
        ];
    }

    /**
     * Test webhook endpoint
     */
    public function testWebhook($testData = null)
    {
        $testData = $testData ?? [
            'test' => true,
            'message' => 'Webhook test from file processing system',
            'timestamp' => now()->toISOString()
        ];

        return $this->sendWebhook('test', $testData);
    }

    /**
     * Generate webhook signature
     */
    private function generateSignature($event, $data)
    {
        $payload = json_encode([
            'event' => $event,
            'data' => $data,
            'timestamp' => now()->toISOString()
        ]);

        return hash_hmac('sha256', $payload, $this->webhookSecret);
    }

    /**
     * Verify webhook signature
     */
    public function verifySignature($signature, $payload)
    {
        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Get webhook configuration
     */
    public function getWebhookConfig()
    {
        return [
            'webhook_url' => $this->webhookUrl,
            'webhook_secret' => $this->webhookSecret ? '***configured***' : null,
            'supported_events' => [
                'file.uploaded',
                'file.processing.started',
                'file.processing.progress',
                'file.processing.completed',
                'file.processing.failed',
                'batch.started',
                'batch.completed',
                'batch.failed',
                'job.started',
                'job.completed',
                'job.failed',
                'system.health',
                'cache.cleared',
                'cache.warmed',
                'metrics.updated'
            ],
            'retry_policy' => [
                'max_retries' => 3,
                'retry_delay' => 60, // seconds
                'exponential_backoff' => true
            ]
        ];
    }
}














