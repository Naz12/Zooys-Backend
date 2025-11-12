<?php

namespace App\Services\Modules;

use App\Services\AIManagerService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIProcessingModule
{
    private $aiManagerService;

    public function __construct(AIManagerService $aiManagerService)
    {
        $this->aiManagerService = $aiManagerService;
    }

    /**
     * Generic AI processing method
     */
    public function process($text, $task, $options = [])
    {
        if (!$this->aiManagerService->validateTask($task)) {
            throw new \Exception("Unsupported task: {$task}. Supported tasks: " . implode(', ', $this->aiManagerService->getSupportedTasks()));
        }

        $result = $this->aiManagerService->processText($text, $task, $options);
        
        if (!$result['success']) {
            throw new \Exception("AI processing failed: " . ($result['error'] ?? 'Unknown error'));
        }

        return $result;
    }

    /**
     * Summarize content
     */
    public function summarize($content, $options = [])
    {
        // Use default model if not specified
        if (!isset($options['model'])) {
            $options['model'] = config('services.ai_manager.default_model', 'deepseek-chat');
        }
        
        $result = $this->process($content, 'summarize', $options);
        
        // Handle different response structures from AI Manager
        $summary = '';
        $keyPoints = [];
        
        // Log the actual response structure for debugging
        Log::info('AI Manager Response Structure:', [
            'result_keys' => array_keys($result),
            'data_keys' => isset($result['data']) ? array_keys($result['data']) : 'no_data',
            'full_result' => $result
        ]);
        
        // Check for the actual structure from your example
        if (isset($result['summary'])) {
            // Direct summary at root level (from your example)
            $summary = $result['summary'];
            $keyPoints = $result['key_points'] ?? [];
        } elseif (isset($result['data']['summary'])) {
            // Direct summary in data
            $summary = $result['data']['summary'];
            $keyPoints = $result['data']['key_points'] ?? [];
        } elseif (isset($result['data']['raw_output']['data']['raw_output']['summary'])) {
            // Deepest nested structure from AI Manager
            $summary = $result['data']['raw_output']['data']['raw_output']['summary'];
            $keyPoints = $result['data']['raw_output']['data']['raw_output']['key_points'] ?? [];
        } elseif (isset($result['data']['raw_output']['data']['summary'])) {
            // New nested structure from AI Manager
            $summary = $result['data']['raw_output']['data']['summary'];
            $keyPoints = $result['data']['raw_output']['data']['key_points'] ?? [];
        } elseif (isset($result['data']['raw_output']['summary'])) {
            // Alternative nested structure
            $summary = $result['data']['raw_output']['summary'];
            $keyPoints = $result['data']['raw_output']['key_points'] ?? [];
        } elseif (isset($result['insights'])) {
            // Fallback to insights
            $summary = $result['insights'];
        } else {
            // If no summary found, log the structure and use a fallback
            Log::warning('No summary found in AI Manager response', [
                'result_structure' => $result
            ]);
            $summary = 'Summary not available - AI response structure not recognized';
        }
        
        return [
            'summary' => $summary,
            'key_points' => $keyPoints,
            'confidence_score' => $result['confidence_score'] ?? 0.8,
            'model_used' => $result['model_used'] ?? 'unknown'
        ];
    }

    /**
     * Generate text
     */
    public function generateText($prompt, $options = [])
    {
        $result = $this->process($prompt, 'generate', $options);
        
        $generatedContent = $result['data']['generated_content'] ?? $result['insights'] ?? '';
        
        // If generated_content is an array (like flashcards), convert to string
        if (is_array($generatedContent)) {
            $generatedContent = json_encode($generatedContent);
        }
        
        return [
            'generated_content' => $generatedContent,
            'ideas' => $result['data']['ideas'] ?? [],
            'model_used' => $result['model_used']
        ];
    }

    /**
     * Answer question with optional context
     */
    public function answerQuestion($question, $context = null, $options = [])
    {
        $text = $question;
        if ($context) {
            $text = "Context: {$context}\nQuestion: {$question}";
        }

        $result = $this->process($text, 'qa', $options);
        
        return [
            'answer' => $result['insights'] ?? $result['data']['answer'] ?? '',
            'sources' => $result['data']['sources'] ?? [],
            'confidence' => $result['data']['confidence'] ?? $result['confidence_score'],
            'model_used' => $result['model_used']
        ];
    }

    /**
     * Translate text
     */
    public function translate($text, $targetLanguage, $options = [])
    {
        $result = $this->process($text, 'translate', $options);
        
        return [
            'translated_text' => $result['insights'] ?? $result['data']['translated_text'] ?? '',
            'source_lang' => $result['data']['source_lang'] ?? null,
            'target_lang' => $result['data']['target_lang'] ?? $targetLanguage,
            'model_used' => $result['model_used']
        ];
    }

    /**
     * Analyze sentiment
     */
    public function analyzeSentiment($text, $options = [])
    {
        $result = $this->process($text, 'sentiment', $options);
        
        return [
            'sentiment' => $result['data']['sentiment'] ?? null,
            'score' => $result['data']['score'] ?? $result['confidence_score'],
            'explanation' => $result['insights'] ?? $result['data']['explanation'] ?? '',
            'model_used' => $result['model_used']
        ];
    }

    /**
     * Review code
     */
    public function reviewCode($code, $options = [])
    {
        $result = $this->process($code, 'code-review', $options);
        
        return [
            'insights' => $result['insights'] ?? '',
            'suggestions' => $result['data']['suggestions'] ?? [],
            'issues' => $result['data']['issues'] ?? [],
            'confidence_score' => $result['confidence_score'],
            'model_used' => $result['model_used']
        ];
    }

    /**
     * Analyze image - NOT IMPLEMENTED
     * 
     * Image analysis should be added to AI Manager microservice.
     * Until then, this feature is not available.
     * 
     * @deprecated Use AI Manager microservice when vision support is added
     * @throws \Exception Always throws exception
     */
    public function analyzeImage($imagePath, $prompt, $options = [])
    {
        $model = $options['model'] ?? config('services.openai.vision_model', 'gpt-4o');
        $maxTokens = $options['max_tokens'] ?? 2000;
        $temperature = $options['temperature'] ?? 0.3;

        $maxRetries = 3;
        $baseDelay = 2; // seconds
        $timeout = 60; // seconds

        throw new \Exception('Image analysis is not currently available. Waiting for AI Manager microservice to add vision support. Use Document Intelligence microservice for document-based image analysis instead.');
    }

    /**
     * Generate embedding using OpenAI (temporary until AI Manager supports embeddings)
     * TODO: Will use AI Manager when embedding support is added
     */
    public function generateEmbedding($text, $options = [])
    {
        $model = $options['model'] ?? 'text-embedding-ada-002';
        $url = 'https://api.openai.com/v1/embeddings';

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'model' => $model,
                'input' => $text
            ]);

            if ($response->failed()) {
                Log::error('OpenAI Embeddings API Error: ' . $response->body());
                throw new \Exception('Failed to generate embedding');
            }

            $data = $response->json();
            
            if (isset($data['data'][0]['embedding'])) {
                return $data['data'][0]['embedding'];
            }

            throw new \Exception('No embedding returned from OpenAI');
        } catch (\Exception $e) {
            Log::error('OpenAI Embeddings API Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate batch embeddings
     */
    public function generateBatchEmbeddings($texts, $options = [])
    {
        $embeddings = [];
        
        foreach ($texts as $text) {
            $embeddings[] = $this->generateEmbedding($text, $options);
        }
        
        return $embeddings;
    }

    /**
     * Get available AI models
     * 
     * @return array List of available models with keys, vendors, and display names
     */
    public function getAvailableModels()
    {
        return $this->aiManagerService->getAvailableModels();
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
        $result = $this->aiManagerService->topicChat($topic, $message, $messages, $options);
        
        if (!$result['success']) {
            throw new \Exception("Topic chat failed: " . ($result['error'] ?? 'Unknown error'));
        }
        
        return $result;
    }

    /**
     * Generate PowerPoint/Presentation content
     * 
     * @param string $topic Presentation topic
     * @param array $options Optional: slides_count, tone, target_audience, model, etc.
     * @return array Presentation outline and content
     */
    public function generatePresentation(string $topic, array $options = [])
    {
        $result = $this->process($topic, 'ppt-generate', $options);
        
        return [
            'outline' => $result['data']['outline'] ?? [],
            'slides' => $result['data']['slides'] ?? [],
            'insights' => $result['insights'] ?? '',
            'model_used' => $result['model_used'] ?? 'unknown',
            'model_display' => $result['model_display'] ?? 'AI Model'
        ];
    }

    /**
     * Generate flashcards from content
     * 
     * @param string $content Content to create flashcards from
     * @param array $options Optional: card_count, difficulty, model, etc.
     * @return array Generated flashcards
     */
    public function generateFlashcards(string $content, array $options = [])
    {
        \Illuminate\Support\Facades\Log::info('AIProcessingModule::generateFlashcards START', [
            'content_length' => strlen($content),
            'options' => $options,
            'using_custom_prompt' => true
        ]);
        
        try {
            // Use custom prompt endpoint for better control over flashcard format
               $count = $options['count'] ?? 5;
               $difficulty = $options['difficulty'] ?? 'intermediate';
               $style = $options['style'] ?? 'mixed';
               $model = $options['model'] ?? config('services.ai_manager.default_model', 'deepseek-chat');
               // Calculate max_tokens based on count (at least 150 tokens per flashcard for comprehensive answers)
               // Minimum: 1024 tokens, Maximum: 4096 tokens (AI Manager limit)
               // For larger counts, we'll use the maximum allowed (4096) and let the AI generate what it can
               $maxTokens = $options['max_tokens'] ?? min(4096, max(1024, $count * 150));
            
            \Illuminate\Support\Facades\Log::info('AIProcessingModule::generateFlashcards calling customPrompt', [
                'count' => $count,
                'difficulty' => $difficulty,
                'model' => $model,
                'max_tokens' => $maxTokens,
                'tokens_per_card' => round($maxTokens / $count, 2)
            ]);
            
            // System prompt: Set up the AI to be a flashcard generation expert
            // This is static and defines the AI's role, output format, and requirements
            $systemPrompt = "You are an expert educational flashcard generator. Generate comprehensive flashcards ABOUT the user's topic (not from the text).\n\n" .
                "Rules:\n" .
                "1. Each flashcard has exactly two fields: 'front' (question/prompt) and 'back' (detailed answer, 1-2 sentences)\n" .
                "2. Cover key concepts, definitions, applications, examples, and relationships\n" .
                "3. Return ONLY a valid JSON array, no additional text\n\n" .
                "Difficulty: {$difficulty} (" . 
                    ($difficulty === 'beginner' ? 'simple, basic concepts' : 
                     ($difficulty === 'intermediate' ? 'moderate complexity, practical applications' : 
                      'complex, deep analysis')) . ")\n" .
                "Style: {$style} (" .
                    ($style === 'definition' ? 'definitions and key terms' :
                     ($style === 'application' ? 'practical applications and examples' :
                      ($style === 'analysis' ? 'analysis and critical thinking' :
                       ($style === 'comparison' ? 'comparing and contrasting' :
                        'mixed question types')))) . ")\n" .
                "Count: Exactly {$count} flashcards\n\n" .
                "Format: [{\"front\": \"Question\", \"back\": \"Detailed answer\"}, ...]";

            // User prompt: ONLY the user's input content (topic/title)
            // All instructions are in the system prompt
            $userPrompt = $content;

            // Call custom prompt endpoint with JSON format
            // Try multiple models if default model is unavailable
            // First, get available models from AI Manager
            $availableModelsResult = $this->aiManagerService->getAvailableModels();
            $availableModelKeys = [];
            
            if ($availableModelsResult['success'] && !empty($availableModelsResult['models'])) {
                $availableModelKeys = array_column($availableModelsResult['models'], 'key');
                \Illuminate\Support\Facades\Log::info('AIProcessingModule::generateFlashcards got available models', [
                    'count' => count($availableModelKeys),
                    'models' => $availableModelKeys
                ]);
            } else {
                \Illuminate\Support\Facades\Log::warning('AIProcessingModule::generateFlashcards failed to get available models', [
                    'error' => $availableModelsResult['error'] ?? 'Unknown error'
                ]);
            }
            
            // Build models to try: preferred first, then available models
            $modelsToTry = $this->getModelsToTry($model, $availableModelKeys);
            $lastError = null;
            $result = null;
            $triedModels = [];
            
            foreach ($modelsToTry as $modelToTry) {
                $triedModels[] = $modelToTry;
                \Illuminate\Support\Facades\Log::info('AIProcessingModule::generateFlashcards trying model', [
                    'model' => $modelToTry,
                    'attempt' => count($triedModels),
                    'total_models' => count($modelsToTry),
                    'tried_so_far' => $triedModels
                ]);
                
                try {
                    $result = $this->aiManagerService->customPrompt($userPrompt, [
                        'system_prompt' => $systemPrompt,
                        'response_format' => 'json',
                        'model' => $modelToTry,
                        'max_tokens' => $maxTokens
                    ]);
                    
                    if ($result['success']) {
                        \Illuminate\Support\Facades\Log::info('AIProcessingModule::generateFlashcards model succeeded', [
                            'model' => $modelToTry,
                            'model_used' => $result['model_used'] ?? $modelToTry,
                            'tried_models' => $triedModels
                        ]);
                        break; // Success, exit loop
                    }
                    
                    // Check if error is model unavailability or not configured
                    $errorMessage = $result['error'] ?? '';
                    $isModelUnavailable = stripos($errorMessage, 'unavailable') !== false || 
                                         stripos($errorMessage, 'not available') !== false ||
                                         stripos($errorMessage, 'currently unavailable') !== false ||
                                         stripos($errorMessage, 'Requested model') !== false ||
                                         stripos($errorMessage, 'not configured') !== false ||
                                         stripos($errorMessage, 'not found') !== false;
                    
                    if ($isModelUnavailable) {
                        $lastError = $result['error'];
                        \Illuminate\Support\Facades\Log::warning('AIProcessingModule::generateFlashcards model unavailable', [
                            'model' => $modelToTry,
                            'error' => $lastError,
                            'available_models_in_response' => $result['available_models'] ?? []
                        ]);
                        
                        // If we have available_models in response, update our list dynamically
                        if (isset($result['available_models']) && is_array($result['available_models']) && !empty($result['available_models'])) {
                            // Handle both formats: array of strings OR array of objects with 'key' field
                            $responseAvailableModelKeys = [];
                            foreach ($result['available_models'] as $model) {
                                if (is_string($model)) {
                                    // Simple string array format
                                    $responseAvailableModelKeys[] = $model;
                                } elseif (is_array($model) && isset($model['key'])) {
                                    // Object format with 'key' field
                                    $responseAvailableModelKeys[] = $model['key'];
                                }
                            }
                            
                            \Illuminate\Support\Facades\Log::info('AIProcessingModule::generateFlashcards updating models from error response', [
                                'available_from_response' => $responseAvailableModelKeys,
                                'current_models_to_try' => $modelsToTry,
                                'original_format' => is_string($result['available_models'][0] ?? null) ? 'string_array' : 'object_array'
                            ]);
                            
                            // Remove unavailable models and add new available ones
                            $modelsToTry = array_filter($modelsToTry, function($m) use ($responseAvailableModelKeys) {
                                return in_array($m, $responseAvailableModelKeys);
                            });
                            
                            // Add any new available models we haven't tried yet
                            foreach ($responseAvailableModelKeys as $newModel) {
                                if (!in_array($newModel, $modelsToTry) && !in_array($newModel, $triedModels)) {
                                    $modelsToTry[] = $newModel;
                                }
                            }
                            
                            $modelsToTry = array_values($modelsToTry); // Re-index
                            
                            \Illuminate\Support\Facades\Log::info('AIProcessingModule::generateFlashcards updated models list', [
                                'new_models_to_try' => $modelsToTry,
                                'count' => count($modelsToTry)
                            ]);
                        }
                        
                        continue; // Try next model
                    } else {
                        // Other error (not model unavailability), throw immediately
                        throw new \Exception("Flashcard generation failed: " . $errorMessage);
                    }
                    
                } catch (\Exception $e) {
                    $lastError = $e->getMessage();
                    \Illuminate\Support\Facades\Log::warning('AIProcessingModule::generateFlashcards model attempt failed', [
                        'model' => $modelToTry,
                        'error' => $lastError,
                        'exception_type' => get_class($e)
                    ]);
                    
                    // If it's not a model unavailability/configuration error, throw immediately
                    if (stripos($lastError, 'unavailable') === false && 
                        stripos($lastError, 'not available') === false &&
                        stripos($lastError, 'Requested model') === false &&
                        stripos($lastError, 'not configured') === false &&
                        stripos($lastError, 'not found') === false) {
                        throw $e;
                    }
                    
                    continue; // Try next model
                }
            }
            
            // If we exhausted all models, throw error
            if (!$result || !$result['success']) {
                throw new \Exception("Flashcard generation failed: All models unavailable. Tried: " . implode(', ', $triedModels) . ". Last error: " . ($lastError ?? 'Unknown error'));
            }
            
            // Log the custom prompt response structure (with more detail for debugging)
            \Illuminate\Support\Facades\Log::info('AIProcessingModule::generateFlashcards custom prompt response', [
                'result_keys' => array_keys($result),
                'format' => $result['format'] ?? null,
                'has_data' => isset($result['data']),
                'data_type' => isset($result['data']) ? gettype($result['data']) : null,
                'data_keys' => isset($result['data']) && is_array($result['data']) ? array_keys($result['data']) : null,
                'has_content' => isset($result['data']['content']),
                'has_json' => isset($result['data']['json']),
                'has_raw_output' => isset($result['data']['raw_output']),
                'content_type' => isset($result['data']['content']) ? gettype($result['data']['content']) : null,
                'content_is_array' => isset($result['data']['content']) && is_array($result['data']['content']),
                'content_count' => isset($result['data']['content']) && is_array($result['data']['content']) ? count($result['data']['content']) : 0,
                'raw_output_type' => isset($result['data']['raw_output']) ? gettype($result['data']['raw_output']) : null,
                'raw_output_is_array' => isset($result['data']['raw_output']) && is_array($result['data']['raw_output']),
                'raw_output_count' => isset($result['data']['raw_output']) && is_array($result['data']['raw_output']) ? count($result['data']['raw_output']) : 0,
                'requested_count' => $count,
                'data_structure_preview' => isset($result['data']) ? substr(json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 2000) : 'no_data'
            ]);
            
            // Extract flashcards from custom prompt response
            // Custom prompt with response_format='json' returns data.content (array) or data.json
            $flashcards = [];
            
            // Priority 1: Check data.content (most common format from custom prompt)
            if (isset($result['data']['content']) && is_array($result['data']['content']) && !empty($result['data']['content'])) {
                $flashcards = $result['data']['content'];
                \Illuminate\Support\Facades\Log::info('AIProcessingModule: Extracted from data.content', [
                    'count' => count($flashcards),
                    'first_card' => $flashcards[0] ?? null
                ]);
            }
            // Priority 2: Check data.json
            elseif (isset($result['data']['json']) && is_array($result['data']['json'])) {
                $flashcards = $result['data']['json'];
                \Illuminate\Support\Facades\Log::info('AIProcessingModule: Extracted from data.json', [
                    'count' => count($flashcards),
                    'first_card' => $flashcards[0] ?? null
                ]);
            }
            // Priority 3: Check data.raw_output
            elseif (isset($result['data']['raw_output']) && is_array($result['data']['raw_output'])) {
                $flashcards = $result['data']['raw_output'];
                \Illuminate\Support\Facades\Log::info('AIProcessingModule: Extracted from data.raw_output', [
                    'count' => count($flashcards),
                    'first_card' => $flashcards[0] ?? null
                ]);
            }
            // Priority 4: Search through data for flashcards array
            elseif (is_array($result['data'])) {
                foreach ($result['data'] as $key => $value) {
                    if (is_array($value) && !empty($value) && isset($value[0]) && is_array($value[0])) {
                        $firstItem = $value[0];
                        if (isset($firstItem['front']) || isset($firstItem['back']) || isset($firstItem['question']) || isset($firstItem['answer'])) {
                            $flashcards = $value;
                            \Illuminate\Support\Facades\Log::info('AIProcessingModule: Extracted from data.'.$key, [
                                'count' => count($flashcards),
                                'first_card' => $flashcards[0] ?? null
                            ]);
                            break;
                        }
                    }
                }
            }
            
            \Illuminate\Support\Facades\Log::info('AIProcessingModule::generateFlashcards extracted flashcards', [
                'cards_count' => count($flashcards),
                'first_card' => $flashcards[0] ?? null,
                'extraction_success' => !empty($flashcards)
            ]);
            
            // If no flashcards were extracted, throw error with details
            if (empty($flashcards)) {
                \Illuminate\Support\Facades\Log::error('AIProcessingModule::generateFlashcards - No flashcards extracted', [
                    'result_structure' => $result,
                    'result_keys' => is_array($result) ? array_keys($result) : 'not_array',
                    'has_data' => isset($result['data']),
                    'data_type' => isset($result['data']) ? gettype($result['data']) : null,
                    'data_keys' => isset($result['data']) && is_array($result['data']) ? array_keys($result['data']) : null,
                    'has_content' => isset($result['data']['content']),
                    'has_json' => isset($result['data']['json']),
                    'has_raw_output' => isset($result['data']['raw_output']),
                    'content_type' => isset($result['data']['content']) ? gettype($result['data']['content']) : null,
                    'content_count' => isset($result['data']['content']) && is_array($result['data']['content']) ? count($result['data']['content']) : 0,
                    'format' => $result['format'] ?? null,
                    'full_result' => json_encode($result, JSON_PRETTY_PRINT)
                ]);
                throw new \Exception('Failed to extract flashcards from AI response. Response format: ' . ($result['format'] ?? 'unknown') . ', Data keys: ' . (isset($result['data']) && is_array($result['data']) ? implode(', ', array_keys($result['data'])) : 'none') . '. Check logs for full response structure.');
            }
            
            // Validate flashcards before transforming
            if (empty($flashcards) || !is_array($flashcards)) {
                \Illuminate\Support\Facades\Log::error('AIProcessingModule::generateFlashcards - Empty or invalid flashcards array', [
                    'flashcards_type' => gettype($flashcards),
                    'flashcards_count' => is_array($flashcards) ? count($flashcards) : 0,
                    'flashcards_value' => $flashcards
                ]);
                throw new \Exception('AI Manager returned empty or invalid flashcards array. Check logs for details.');
            }
            
            // Transform custom prompt response to match expected format
            $result = [
                'success' => true,
                'data' => [
                    'raw_output' => [
                        'cards' => $flashcards
                    ],
                    'insights' => 'Flashcards generated successfully',
                    'confidence_score' => 0.9
                ],
                'model_used' => $result['model_used'] ?? $model,
            'model_display' => $result['model_display'] ?? 'AI Model'
        ];
            
            \Illuminate\Support\Facades\Log::info('AIProcessingModule::generateFlashcards - Transformed result', [
                'has_cards' => isset($result['data']['raw_output']['cards']),
                'cards_count' => count($result['data']['raw_output']['cards']),
                'first_card' => $result['data']['raw_output']['cards'][0] ?? null
            ]);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AIProcessingModule::generateFlashcards exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
        
        // Return the transformed result (already in expected format)
        return $result;
    }
    
    /**
     * Get list of models to try in order of preference
     * Falls back to alternative models if default is unavailable
     * 
     * @param string $preferredModel The preferred model to try first
     * @param array $availableModelKeys Optional: List of available model keys from AI Manager
     * @return array List of models to try in order
     */
    private function getModelsToTry($preferredModel, $availableModelKeys = [])
    {
        // Default fallback models (in order of preference) - ONLY use models that are commonly available
        $fallbackModels = [
            'deepseek-chat',
            'gpt-4o',
            'gpt-3.5-turbo',
            'ollama:llama3',
            'ollama:mistral',
            'ollama:phi3:mini'
        ];
        
        // If we have available models, use ONLY those models
        if (!empty($availableModelKeys)) {
            // Handle both formats: array of strings OR array of objects with 'key' field
            $availableModelKeysArray = [];
            foreach ($availableModelKeys as $model) {
                if (is_string($model)) {
                    // Simple string array format
                    $availableModelKeysArray[] = $model;
                } elseif (is_array($model) && isset($model['key'])) {
                    // Object format with 'key' field
                    $availableModelKeysArray[] = $model['key'];
                }
            }
            
            // Start with preferred model if it's available
            $modelsToTry = [];
            if (in_array($preferredModel, $availableModelKeysArray)) {
                $modelsToTry[] = $preferredModel;
            }
            
            // Add other available fallback models
            foreach ($fallbackModels as $fallback) {
                if ($fallback !== $preferredModel && in_array($fallback, $availableModelKeysArray) && !in_array($fallback, $modelsToTry)) {
                    $modelsToTry[] = $fallback;
                }
            }
            
            // If preferred model wasn't available, use ALL available models (don't filter by fallback list)
            if (empty($modelsToTry)) {
                $modelsToTry = $availableModelKeysArray;
            }
            
            \Illuminate\Support\Facades\Log::info('AIProcessingModule: Models to try (from available models)', [
                'preferred' => $preferredModel,
                'preferred_available' => in_array($preferredModel, $availableModelKeysArray),
                'models_to_try' => $modelsToTry,
                'available_count' => count($availableModelKeysArray),
                'available_models' => $availableModelKeysArray
            ]);
            
            // IMPORTANT: Only return models that are actually available
            return $modelsToTry;
        }
        
        // Fallback: Use preferred model first, then fallback list (only if we couldn't get available models)
        $modelsToTry = [$preferredModel];
        foreach ($fallbackModels as $fallback) {
            if ($fallback !== $preferredModel && !in_array($fallback, $modelsToTry)) {
                $modelsToTry[] = $fallback;
            }
        }
        
        \Illuminate\Support\Facades\Log::warning('AIProcessingModule: Models to try (using fallback list - no available models from API)', [
            'preferred' => $preferredModel,
            'models_to_try' => $modelsToTry,
            'note' => 'This should rarely happen - available models should be fetched from API'
        ]);
        
        return $modelsToTry;
    }
}
