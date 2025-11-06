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
        $result = $this->process($content, 'flashcard', $options);
        
        return [
            'flashcards' => $result['data']['flashcards'] ?? [],
            'insights' => $result['insights'] ?? '',
            'model_used' => $result['model_used'] ?? 'unknown',
            'model_display' => $result['model_display'] ?? 'AI Model'
        ];
    }
}
