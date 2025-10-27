<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIManagerService
{
    private $apiUrl;
    private $apiKey;
    private $timeout;

    public function __construct()
    {
        $this->apiUrl = config('services.ai_manager.url');
        $this->apiKey = config('services.ai_manager.api_key');
        $this->timeout = config('services.ai_manager.timeout', 30);
    }

    /**
     * Validate if a task is supported
     */
    public function validateTask($task)
    {
        $supportedTasks = $this->getSupportedTasks();
        return in_array($task, $supportedTasks);
    }

    /**
     * Get supported tasks
     */
    public function getSupportedTasks()
    {
        return ['summarize', 'generate', 'qa', 'translate', 'sentiment', 'code-review'];
    }

    /**
     * Process text using AI Manager microservice
     */
public function processText($text, $task, $options = [])
    {
        // Check if service is available (circuit breaker)
        if (!$this->isServiceAvailable()) {
            throw new \Exception("AI Manager service is currently unavailable");
        }

        $maxRetries = 2; // Reduced retries to fail faster
        $retryDelay = 5; // Increased delay between retries
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                Log::info("AI Manager API Request", [
                    'task' => $task,
                    'text_length' => strlen($text),
                    'options' => $options,
                    'attempt' => $attempt
                ]);

                $requestData = [
                    'text' => $text,
                    'task' => $task,
                    'options' => $options
                ];

                $response = Http::timeout($this->timeout) // Use config timeout
                    ->connectTimeout(15) // Increased connection timeout
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'X-API-KEY' => $this->apiKey,
                    ])->post($this->apiUrl . '/api/process-text', $requestData);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    Log::info("AI Manager API Success", [
                        'task' => $task,
                        'response_length' => strlen($data['insights'] ?? ''),
                        'model_used' => $data['model_used'] ?? 'unknown',
                        'attempt' => $attempt
                    ]);

                    return [
                        'success' => true,
                        'insights' => $data['insights'] ?? '',
                        'data' => $data['data'] ?? [],
                        'confidence_score' => $data['confidence_score'] ?? 0.8,
                        'model_used' => $data['model_used'] ?? 'gpt-3.5-turbo',
                        'tokens_used' => $data['tokens_used'] ?? 0,
                        'processing_time' => $data['processing_time'] ?? 0
                    ];
                } else {
                    Log::warning("AI Manager API failed", [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'task' => $task,
                        'attempt' => $attempt
                    ]);

                    // If this is the last attempt, mark service as unavailable and return error
                    if ($attempt === $maxRetries) {
                        $this->markServiceUnavailable();
                        return [
                            'success' => false,
                            'error' => 'AI Manager API failed after ' . $maxRetries . ' attempts: ' . $response->status(),
                            'details' => $response->body()
                        ];
                    }
                    
                    // Wait before retry
                    sleep($retryDelay);
                    continue;
                }

            } catch (\Exception $e) {
                $isTimeout = strpos($e->getMessage(), 'timeout') !== false || 
                           strpos($e->getMessage(), 'timed out') !== false ||
                           strpos($e->getMessage(), 'Operation timed out') !== false;
                
                Log::warning("AI Manager API Error", [
                    'error' => $e->getMessage(),
                    'task' => $task,
                    'attempt' => $attempt,
                    'is_timeout' => $isTimeout
                ]);

                // If this is the last attempt, mark service as unavailable and return error
                if ($attempt === $maxRetries) {
                    $this->markServiceUnavailable();
                    $errorMessage = $isTimeout ? 
                        'AI Manager service is currently overloaded and taking longer than ' . $this->timeout . ' seconds to respond. Please try again later.' : 
                        'AI Manager service error: ' . $e->getMessage();
                    
                    return [
                        'success' => false,
                        'error' => $errorMessage
                    ];
                }
                
                // For timeout errors, wait longer before retry
                $waitTime = $isTimeout ? $retryDelay * 2 : $retryDelay;
                sleep($waitTime);
            }
        }
        
        // This should never be reached, but just in case
        return [
            'success' => false,
            'error' => 'AI Manager service failed after all retry attempts'
        ];
    }

    /**
     * Summarize text
     */
    public function summarize($text, $options = [])
    {
        return $this->processText($text, 'summarize', $options);
    }

    /**
     * Generate text
     */
    public function generate($prompt, $options = [])
    {
        return $this->processText($prompt, 'generate', $options);
    }

    /**
     * Answer question
     */
    public function answerQuestion($question, $context = null, $options = [])
    {
        $text = $question;
        if ($context) {
            $text = "Context: {$context}\nQuestion: {$question}";
        }
        return $this->processText($text, 'qa', $options);
    }

    /**
     * Translate text
     */
    public function translate($text, $targetLanguage, $options = [])
    {
        $options['target_language'] = $targetLanguage;
        return $this->processText($text, 'translate', $options);
    }

    /**
     * Analyze sentiment
     */
    public function analyzeSentiment($text, $options = [])
    {
        return $this->processText($text, 'sentiment', $options);
    }

    /**
     * Review code
     */
    public function reviewCode($code, $options = [])
    {
        return $this->processText($code, 'code-review', $options);
    }

    /**
     * Check service health
     */
    public function checkHealth()
    {
        try {
            // Test with a simple process-text request instead of health endpoint
            $testData = [
                'text' => 'test',
                'task' => 'summarize',
                'options' => []
            ];

            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-API-KEY' => $this->apiKey,
                ])->post($this->apiUrl . '/api/process-text', $testData);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'response' => $response->json()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if service is available (circuit breaker)
     */
    private function isServiceAvailable()
    {
        $cacheKey = 'ai_manager_unavailable';
        $unavailableUntil = \Illuminate\Support\Facades\Cache::get($cacheKey);
        
        if ($unavailableUntil && now()->timestamp < $unavailableUntil) {
            return false;
        }
        
        return true;
    }

    /**
     * Mark service as unavailable for 2 minutes (reduced from 5 minutes)
     */
    private function markServiceUnavailable()
    {
        $cacheKey = 'ai_manager_unavailable';
        $unavailableUntil = now()->addMinutes(2)->timestamp; // Reduced from 5 to 2 minutes
        
        \Illuminate\Support\Facades\Cache::put($cacheKey, $unavailableUntil, 120); // 2 minutes
        
        Log::warning("AI Manager service marked as unavailable until " . now()->addMinutes(2)->toISOString());
    }

    /**
     * Reset circuit breaker (for manual recovery)
     */
    public function resetCircuitBreaker()
    {
        $cacheKey = 'ai_manager_unavailable';
        \Illuminate\Support\Facades\Cache::forget($cacheKey);
        
        Log::info("AI Manager circuit breaker reset manually");
        
        return [
            'success' => true,
            'message' => 'Circuit breaker reset successfully'
        ];
    }

}

