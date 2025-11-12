<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIManagerService
{
    private $apiUrl;
    private $apiKey;
    private $timeout;
    private $defaultModel;

    public function __construct()
    {
        $this->apiUrl = rtrim(config('services.ai_manager.url'), '/');
        $this->apiKey = config('services.ai_manager.api_key');
        $this->timeout = config('services.ai_manager.timeout', 180);
        $this->defaultModel = config('services.ai_manager.default_model', 'ollama:llama3');
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
        return ['summarize', 'generate', 'qa', 'translate', 'sentiment', 'code-review', 'ppt-generate', 'flashcard'];
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
                $requestData = [
                    'text' => $text,
                    'task' => $task
                ];

                // Add model if specified in options, otherwise use default
                if (isset($options['model'])) {
                    $requestData['model'] = $options['model'];
                    unset($options['model']); // Remove from options to avoid duplication
                }

                // Note: Based on working API examples, the AI Manager API only accepts:
                // - text (required)
                // - task (required) 
                // - model (optional)
                // Other options like 'language', 'format' may not be supported and could cause issues
                // Only send the core parameters that the API expects
                
                // DO NOT add other options - the API may reject them or they may cause errors
                // The API handles task-specific behavior internally based on the 'task' parameter

                Log::info("AI Manager API Request", [
                    'url' => $this->apiUrl . '/api/process-text',
                    'task' => $task,
                    'text_length' => strlen($text),
                    'request_data' => $requestData,
                    'api_key' => substr($this->apiKey, 0, 10) . '...', // Log partial key for debugging
                    'attempt' => $attempt
                ]);

                $response = Http::timeout($this->timeout) // Use config timeout
                    ->connectTimeout(15) // Increased connection timeout
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'X-API-KEY' => $this->apiKey,
                    ])->post($this->apiUrl . '/api/process-text', $requestData);

                if ($response->successful()) {
                    $responseData = $response->json();
                    
                    // Handle new response format (status: success/error)
                    $isSuccess = ($responseData['status'] ?? 'success') === 'success';
                    
                    if (!$isSuccess) {
                        // Error response with available models
                        Log::warning("AI Manager returned error status", [
                            'task' => $task,
                            'message' => $responseData['message'] ?? 'Unknown error',
                            'available_models' => $responseData['available_models'] ?? []
                        ]);

                        return [
                            'success' => false,
                            'error' => $responseData['message'] ?? 'Request failed',
                            'available_models' => $responseData['available_models'] ?? []
                        ];
                    }

                    $data = $responseData['data'] ?? $responseData;
                    
                    // Log structure for flashcard task debugging
                    if ($task === 'flashcard') {
                        Log::info("AI Manager API Success - Flashcard Task", [
                            'task' => $task,
                            'response_data_keys' => array_keys($responseData),
                            'has_data_key' => isset($responseData['data']),
                            'data_keys' => isset($responseData['data']) ? array_keys($responseData['data']) : null,
                            'has_raw_output' => isset($responseData['data']['raw_output']),
                            'raw_output_keys' => isset($responseData['data']['raw_output']) ? array_keys($responseData['data']['raw_output']) : null,
                            'has_cards' => isset($responseData['data']['raw_output']['cards']),
                            'cards_count' => isset($responseData['data']['raw_output']['cards']) ? count($responseData['data']['raw_output']['cards']) : 0,
                            'model_used' => $responseData['model_used'] ?? 'unknown'
                        ]);
                    }
                    
                    Log::info("AI Manager API Success", [
                        'task' => $task,
                        'response_length' => strlen($data['insights'] ?? ''),
                        'model_used' => $responseData['model_used'] ?? 'unknown',
                        'model_display' => $responseData['model_display'] ?? 'unknown',
                        'attempt' => $attempt
                    ]);

                    return [
                        'success' => true,
                        'insights' => $data['insights'] ?? '',
                        'data' => $data,
                        'confidence_score' => $data['confidence_score'] ?? 0.8,
                        'model_used' => $responseData['model_used'] ?? $this->defaultModel,
                        'model_display' => $responseData['model_display'] ?? 'AI Model',
                        'tokens_used' => $data['tokens_used'] ?? 0,
                        'processing_time' => $data['processing_time'] ?? 0
                    ];
                } else {
                    // Parse error response if available
                    $errorBody = $response->body();
                    $errorData = null;
                    try {
                        $errorData = $response->json();
                    } catch (\Exception $e) {
                        // If JSON parsing fails, use raw body
                    }
                    
                    // Detect cache directory errors in response
                    $errorBodyStr = is_string($errorBody) ? $errorBody : json_encode($errorBody);
                    $isCacheError = strpos($errorBodyStr, 'Failed to open stream') !== false || 
                                   strpos($errorBodyStr, 'storage/framework/cache') !== false ||
                                   strpos($errorBodyStr, 'No such file or directory') !== false;
                    
                    Log::error("AI Manager API failed", [
                        'status' => $response->status(),
                        'status_text' => $response->status() === 500 ? 'Internal Server Error' : 'Unknown',
                        'response_body' => $errorBody,
                        'error_data' => $errorData,
                        'task' => $task,
                        'attempt' => $attempt,
                        'text_length' => strlen($text),
                        'request_url' => $this->apiUrl . '/api/process-text',
                        'request_data' => [
                            'task' => $task,
                            'text_length' => strlen($text),
                            'model' => $requestData['model'] ?? 'default',
                            'has_options' => !empty($options)
                        ],
                        'is_cache_error' => $isCacheError,
                        'error_type' => $isCacheError ? 'cache_directory_missing' : 'unknown'
                    ]);

                    // If this is the last attempt, mark service as unavailable and return error
                    if ($attempt === $maxRetries) {
                        $this->markServiceUnavailable();
                        
                        // Provide more specific error message for 500 errors
                        $errorMessage = 'AI Manager API failed after ' . $maxRetries . ' attempts: ' . $response->status();
                        if ($response->status() === 500) {
                            $errorMessage = 'AI Manager service encountered an internal server error. ';
                            
                            // Check for specific error patterns
                            $errorBodyStr = is_string($errorBody) ? $errorBody : json_encode($errorBody);
                            $errorMessageStr = $errorData['message'] ?? $errorData['error'] ?? $errorBodyStr ?? '';
                            
                            // Detect cache directory issues
                            if (strpos($errorMessageStr, 'Failed to open stream') !== false || 
                                strpos($errorMessageStr, 'No such file or directory') !== false ||
                                strpos($errorMessageStr, 'storage/framework/cache') !== false) {
                                $errorMessage = 'AI Manager service has a configuration issue (cache directory missing). ';
                                $errorMessage .= 'This is a server-side problem with the AI Manager microservice. ';
                                $errorMessage .= 'Please contact the system administrator or try again later.';
                            } elseif ($errorData && isset($errorData['message'])) {
                                $errorMessage .= $errorData['message'];
                            } elseif ($errorData && isset($errorData['error'])) {
                                $errorMessage .= $errorData['error'];
                            } else {
                                $errorMessage .= 'The service may be temporarily unavailable. Please try again later.';
                            }
                            
                            // Check if text might be too long
                            $textLength = strlen($text);
                            if ($textLength > 100000) {
                                $errorMessage .= ' The content may be too long for processing.';
                            }
                        }
                        
                        return [
                            'success' => false,
                            'error' => $errorMessage,
                            'error_details' => [
                                'error_type' => 'ai_manager_api_error',
                                'status_code' => $response->status(),
                                'api_response' => $errorData ?? $errorBody,
                                'text_length' => strlen($text),
                                'task' => $task
                            ]
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
     * Custom prompt using /api/custom-prompt endpoint
     * 
     * @param string|array $prompt User prompt (string) or messages array
     * @param array $options Optional: system_prompt, response_format, model, max_tokens, etc.
     * @return array Response with status, model_used, format, and data
     */
    public function customPrompt($prompt, array $options = [])
    {
        // Check if service is available (circuit breaker)
        if (!$this->isServiceAvailable()) {
            throw new \Exception("AI Manager service is currently unavailable");
        }

        try {
            $requestData = [];

            // Handle prompt format: string or messages array
            if (is_array($prompt)) {
                $requestData['messages'] = $prompt;
            } else {
                $requestData['prompt'] = $prompt;
            }

            // Add system prompt if provided
            if (isset($options['system_prompt'])) {
                $requestData['system_prompt'] = $options['system_prompt'];
                unset($options['system_prompt']);
            }

            // Add response format (default to 'json' for structured responses)
            $requestData['response_format'] = $options['response_format'] ?? 'json';
            unset($options['response_format']);

            // Add model if specified
            if (isset($options['model'])) {
                $requestData['model'] = $options['model'];
                unset($options['model']);
            }

            // Add other options (max_tokens, temperature, etc.)
            foreach ($options as $key => $value) {
                if (!in_array($key, ['model', 'response_format', 'system_prompt'])) {
                    $requestData[$key] = $value;
                }
            }

            Log::info("AI Manager Custom Prompt Request", [
                'url' => $this->apiUrl . '/api/custom-prompt',
                'has_prompt' => isset($requestData['prompt']),
                'has_messages' => isset($requestData['messages']),
                'response_format' => $requestData['response_format'],
                'model' => $requestData['model'] ?? 'default'
            ]);

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-API-KEY' => $this->apiKey,
                ])->post($this->apiUrl . '/api/custom-prompt', $requestData);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Handle response format
                $isSuccess = ($responseData['status'] ?? 'success') === 'success';
                
                if (!$isSuccess) {
                    Log::warning("AI Manager Custom Prompt returned error status", [
                        'message' => $responseData['message'] ?? 'Unknown error',
                        'available_models' => $responseData['available_models'] ?? []
                    ]);

                    return [
                        'success' => false,
                        'error' => $responseData['message'] ?? 'Request failed',
                        'available_models' => $responseData['available_models'] ?? []
                    ];
                }

                return [
                    'success' => true,
                    'status' => $responseData['status'] ?? 'success',
                    'model_used' => $responseData['model_used'] ?? $this->defaultModel,
                    'model_display' => $responseData['model_display'] ?? 'AI Model',
                    'format' => $responseData['format'] ?? 'json',
                    'data' => $responseData['data'] ?? [],
                    'tokens_used' => $responseData['tokens_used'] ?? 0,
                    'processing_time' => $responseData['processing_time'] ?? 0
                ];
            } else {
                $errorBody = $response->body();
                $errorData = null;
                try {
                    $errorData = $response->json();
                } catch (\Exception $e) {
                    // If JSON parsing fails, use raw body
                }

                Log::error("AI Manager Custom Prompt API failed", [
                    'status' => $response->status(),
                    'response_body' => $errorBody,
                    'error_data' => $errorData,
                    'has_available_models' => isset($errorData['available_models'])
                ]);

                return [
                    'success' => false,
                    'error' => $errorData['message'] ?? $errorData['error'] ?? 'Custom prompt request failed',
                    'status_code' => $response->status(),
                    'available_models' => $errorData['available_models'] ?? []
                ];
            }
        } catch (\Exception $e) {
            Log::error("AI Manager Custom Prompt Exception", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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

    /**
     * Get available models from AI Manager
     * 
     * @return array List of available models with keys, vendors, and display names
     */
    public function getAvailableModels()
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'X-API-KEY' => $this->apiKey,
                ])->get($this->apiUrl . '/api/models');

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info("AI Manager models retrieved", [
                    'count' => $data['data']['count'] ?? 0
                ]);

                // Handle both formats: array of strings OR array of objects with 'key' field
                $availableModels = $data['data']['available_models'] ?? [];
                $modelKeys = [];
                foreach ($availableModels as $model) {
                    if (is_string($model)) {
                        // Simple string array format
                        $modelKeys[] = $model;
                    } elseif (is_array($model) && isset($model['key'])) {
                        // Object format with 'key' field
                        $modelKeys[] = $model['key'];
                    }
                }
                
                return [
                    'success' => true,
                    'count' => $data['data']['count'] ?? 0,
                    'models' => $modelKeys // Return as simple array of strings for consistency
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to retrieve models: ' . $response->status()
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get available models", [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Topic-based chat with document context
     * 
     * @param string $topic The main topic or summary to ground the conversation
     * @param string $message Current user message/question
     * @param array $messages Optional: Previous conversation messages [{role, content}]
     * @param array $options Optional: model, max_tokens, etc.
     * @return array Chat response with reply, supporting_points, follow_up_questions, suggested_resources
     */
    public function topicChat(string $topic, string $message, array $messages = [], array $options = [])
    {
        try {
            Log::info("AI Manager Topic Chat Request", [
                'topic_length' => strlen($topic),
                'message_length' => strlen($message),
                'previous_messages' => count($messages),
                'options' => $options
            ]);

            $requestData = [
                'topic' => $topic,
                'message' => $message
            ];

            // Add previous messages if provided
            if (!empty($messages)) {
                $requestData['messages'] = $messages;
            }

            // Add model if specified
            if (isset($options['model'])) {
                $requestData['model'] = $options['model'];
            }

            // Add any other options
            foreach ($options as $key => $value) {
                if (!in_array($key, ['model'])) {
                    $requestData[$key] = $value;
                }
            }

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-API-KEY' => $this->apiKey,
                ])->post($this->apiUrl . '/api/topic-chat', $requestData);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Handle error status
                if (($responseData['status'] ?? 'success') !== 'success') {
                    return [
                        'success' => false,
                        'error' => $responseData['message'] ?? 'Topic chat failed',
                        'available_models' => $responseData['available_models'] ?? []
                    ];
                }

                $data = $responseData['data'] ?? [];
                
                Log::info("AI Manager Topic Chat Success", [
                    'reply_length' => strlen($data['reply'] ?? ''),
                    'supporting_points' => count($data['supporting_points'] ?? []),
                    'model_used' => $responseData['model_used'] ?? 'unknown'
                ]);

                return [
                    'success' => true,
                    'reply' => $data['reply'] ?? '',
                    'supporting_points' => $data['supporting_points'] ?? [],
                    'follow_up_questions' => $data['follow_up_questions'] ?? [],
                    'suggested_resources' => $data['suggested_resources'] ?? [],
                    'model_used' => $responseData['model_used'] ?? $this->defaultModel,
                    'model_display' => $responseData['model_display'] ?? 'AI Model'
                ];
            }

            return [
                'success' => false,
                'error' => 'Topic chat request failed: ' . $response->status(),
                'details' => $response->body()
            ];
        } catch (\Exception $e) {
            Log::error("AI Manager Topic Chat Error", [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Topic chat error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate PowerPoint/Presentation content
     * 
     * @param string $topic Presentation topic
     * @param array $options Optional: slides_count, tone, target_audience, etc.
     * @return array Presentation outline and content
     */
    public function generatePresentation(string $topic, array $options = [])
    {
        $options['topic'] = $topic;
        return $this->processText($topic, 'ppt-generate', $options);
    }

    /**
     * Generate flashcards from content
     * 
     * @param string $content Content to create flashcards from
     * @param array $options Optional: card_count, difficulty, etc.
     * @return array Generated flashcards
     */
    public function generateFlashcards(string $content, array $options = [])
    {
        return $this->processText($content, 'flashcard', $options);
    }

}

