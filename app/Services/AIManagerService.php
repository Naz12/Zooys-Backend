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
        $this->timeout = config('services.ai_manager.timeout', 60);
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
        $maxRetries = 3;
        $retryDelay = 2; // seconds
        
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

                $response = Http::timeout($this->timeout)
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

                    // If this is the last attempt, return error
                    if ($attempt === $maxRetries) {
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
                Log::warning("AI Manager API Error", [
                    'error' => $e->getMessage(),
                    'task' => $task,
                    'attempt' => $attempt
                ]);

                // If this is the last attempt, return error
                if ($attempt === $maxRetries) {
                    return [
                        'success' => false,
                        'error' => 'AI Manager service error: ' . $e->getMessage()
                    ];
                }
                
                // Wait before retry
                sleep($retryDelay);
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
            $response = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'X-API-KEY' => $this->apiKey,
                ])->get($this->apiUrl . '/health');

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
}

