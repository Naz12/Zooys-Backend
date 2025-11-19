<?php

namespace App\Services\Modules;

use App\Services\AIManagerService;
use Illuminate\Support\Facades\Log;

class WriterModule
{
    private $aiProcessingModule;
    private $aiManagerService;

    public function __construct(
        AIProcessingModule $aiProcessingModule,
        AIManagerService $aiManagerService
    ) {
        $this->aiProcessingModule = $aiProcessingModule;
        $this->aiManagerService = $aiManagerService;
    }

    /**
     * Generate new content based on prompt and mode
     * 
     * @param string $prompt User's writing prompt
     * @param string $mode Writing mode: creative, professional, academic
     * @param array $options Optional: model, language, etc.
     * @return array Generated content with metadata
     */
    public function generateContent($prompt, $mode = 'professional', $options = [])
    {
        try {
            Log::info("WriterModule: Generating content", [
                'prompt_length' => strlen($prompt),
                'mode' => $mode,
                'options' => $options
            ]);

            // Validate input
            $this->validateInput($prompt, 'prompt');

            // Validate mode
            if (!in_array($mode, $this->getSupportedModes())) {
                throw new \Exception("Unsupported mode: {$mode}. Supported modes: " . implode(', ', $this->getSupportedModes()));
            }

            // Build system prompt based on mode
            $systemPrompt = $this->buildSystemPrompt($mode);

            // Always use default model (deepseek-chat)
            $model = config('services.ai_manager.default_model', 'deepseek-chat');
            
            // Ensure model is set (fallback to deepseek-chat if config is empty)
            if (empty($model)) {
                $model = 'deepseek-chat';
            }

            // Set max_tokens to 4096
            $maxTokens = 4096;

            // Log the exact request being sent
            Log::info("WriterModule: Sending request to AI Manager", [
                'model' => $model,
                'max_tokens' => $maxTokens,
                'response_format' => 'text',
                'system_prompt_length' => strlen($systemPrompt),
                'prompt_length' => strlen($prompt)
            ]);

            // Use customPrompt for better control (similar to FlashcardModule)
            $result = $this->aiManagerService->customPrompt($prompt, [
                'system_prompt' => $systemPrompt,
                'response_format' => 'text', // We want plain text, not JSON
                'model' => $model,
                'max_tokens' => $maxTokens
            ]);

            // Log the response
            Log::info("WriterModule: AI Manager response", [
                'success' => $result['success'] ?? false,
                'error' => $result['error'] ?? null,
                'model_used' => $result['model_used'] ?? null,
                'status_code' => $result['status_code'] ?? null
            ]);

            // If model unavailable, try fallback models
            if (!$result['success'] && isset($result['error']) && stripos($result['error'], 'unavailable') !== false) {
                $availableModels = $result['available_models'] ?? [];
                $fallbackModels = ['ollama:llama3', 'ollama:mistral', 'gpt-4o'];
                
                foreach ($fallbackModels as $fallbackModel) {
                    if (in_array($fallbackModel, $availableModels)) {
                        Log::info("WriterModule: Retrying with fallback model", ['fallback_model' => $fallbackModel]);
                        
                        $result = $this->aiManagerService->customPrompt($prompt, [
                            'system_prompt' => $systemPrompt,
                            'response_format' => 'text',
                            'model' => $fallbackModel,
                            'max_tokens' => $maxTokens
                        ]);
                        
                        if ($result['success']) {
                            Log::info("WriterModule: Fallback model succeeded", ['model_used' => $fallbackModel]);
                            break;
                        }
                    }
                }
            }

            if (!$result['success']) {
                throw new \Exception("Content generation failed: " . ($result['error'] ?? 'Unknown error'));
            }

            // Extract content from response (handle various response structures)
            $content = $this->extractContentFromResponse($result);

            if (empty($content)) {
                throw new \Exception('AI Manager returned empty content');
            }

            // Calculate word and character counts
            $wordCount = str_word_count($content);
            $characterCount = strlen($content);

            Log::info("WriterModule: Content generated successfully", [
                'word_count' => $wordCount,
                'character_count' => $characterCount,
                'model_used' => $result['model_used'] ?? $model
            ]);

            return [
                'success' => true,
                'content' => $content,
                'word_count' => $wordCount,
                'character_count' => $characterCount,
                'mode' => $mode,
                'metadata' => [
                    'model_used' => $result['model_used'] ?? $model,
                    'model_display' => $result['model_display'] ?? 'AI Model',
                    'tokens_used' => $result['tokens_used'] ?? 0,
                    'processing_time' => $result['processing_time'] ?? 0,
                    'is_rewrite' => false
                ]
            ];

        } catch (\Exception $e) {
            Log::error('WriterModule: Content generation error', [
                'error' => $e->getMessage(),
                'mode' => $mode
            ]);

            return [
                'success' => false,
                'content' => '',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Rewrite existing content based on new prompt and mode
     * 
     * @param string $previousContent Original content to rewrite
     * @param string $newPrompt Instructions for rewriting
     * @param string $mode Writing mode: creative, professional, academic
     * @param array $options Optional: model, etc.
     * @return array Rewritten content with metadata
     */
    public function rewriteContent($previousContent, $newPrompt, $mode = 'professional', $options = [])
    {
        try {
            Log::info("WriterModule: Rewriting content", [
                'previous_content_length' => strlen($previousContent),
                'prompt_length' => strlen($newPrompt),
                'mode' => $mode
            ]);

            // Validate inputs
            $this->validateInput($previousContent, 'content');
            $this->validateInput($newPrompt, 'prompt');

            // Validate mode
            if (!in_array($mode, $this->getSupportedModes())) {
                throw new \Exception("Unsupported mode: {$mode}. Supported modes: " . implode(', ', $this->getSupportedModes()));
            }

            // Build rewrite prompt
            $userPrompt = $this->buildRewritePrompt($previousContent, $newPrompt, $mode);

            // Build system prompt based on mode
            $systemPrompt = $this->buildSystemPrompt($mode);

            // Always use default model (deepseek-chat)
            $model = config('services.ai_manager.default_model', 'deepseek-chat');
            
            // Ensure model is set (fallback to deepseek-chat if config is empty)
            if (empty($model)) {
                $model = 'deepseek-chat';
            }

            // Calculate max_tokens (use longer for rewrites to ensure full content)
            $maxTokens = $options['max_tokens'] ?? 4096;

            // Use customPrompt for rewriting
            $result = $this->aiManagerService->customPrompt($userPrompt, [
                'system_prompt' => $systemPrompt,
                'response_format' => 'text',
                'model' => $model,
                'max_tokens' => $maxTokens
            ]);

            if (!$result['success']) {
                throw new \Exception("Content rewriting failed: " . ($result['error'] ?? 'Unknown error'));
            }

            // Extract content from response
            $content = $this->extractContentFromResponse($result);

            if (empty($content)) {
                throw new \Exception('AI Manager returned empty rewritten content');
            }

            // Calculate word and character counts
            $wordCount = str_word_count($content);
            $characterCount = strlen($content);

            Log::info("WriterModule: Content rewritten successfully", [
                'word_count' => $wordCount,
                'character_count' => $characterCount,
                'model_used' => $result['model_used'] ?? $model
            ]);

            return [
                'success' => true,
                'content' => $content,
                'word_count' => $wordCount,
                'character_count' => $characterCount,
                'mode' => $mode,
                'metadata' => [
                    'model_used' => $result['model_used'] ?? $model,
                    'model_display' => $result['model_display'] ?? 'AI Model',
                    'tokens_used' => $result['tokens_used'] ?? 0,
                    'processing_time' => $result['processing_time'] ?? 0,
                    'is_rewrite' => true
                ]
            ];

        } catch (\Exception $e) {
            Log::error('WriterModule: Content rewriting error', [
                'error' => $e->getMessage(),
                'mode' => $mode
            ]);

            return [
                'success' => false,
                'content' => '',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Build system prompt based on writing mode
     * 
     * @param string $mode Writing mode
     * @param array $options Additional options
     * @return string System prompt
     */
    private function buildSystemPrompt($mode)
    {
        switch ($mode) {
            case 'creative':
                return "Write creative, engaging content (up to 500 words). Use vivid descriptions, storytelling, and varied sentence structures.";

            case 'professional':
                return "Write professional, clear content (up to 500 words). Use business-appropriate language, logical structure, and maintain a professional tone.";

            case 'academic':
                return "Write formal, scholarly content (up to 500 words). Use academic language, evidence-based reasoning, and maintain objectivity.";

            default:
                return "Write high-quality content (up to 500 words).";
        }
    }

    /**
     * Build rewrite prompt combining previous content and new instructions
     * 
     * @param string $previousContent Original content
     * @param string $newPrompt Rewrite instructions
     * @param string $mode Writing mode
     * @return string Combined prompt
     */
    private function buildRewritePrompt($previousContent, $newPrompt, $mode)
    {
        return "Rewrite the following content based on these instructions: {$newPrompt}\n\n" .
               "Maintain the {$mode} writing style.\n\n" .
               "Original content:\n{$previousContent}\n\n" .
               "Please provide the rewritten content:";
    }

    /**
     * Calculate max_tokens based on length option
     * 
     * @param string $length Length option
     * @return int Max tokens
     */
    private function calculateMaxTokens($length)
    {
        switch ($length) {
            case 'short':
                return 1024; // ~300-500 words
            case 'medium':
                return 2048; // ~800-1200 words
            case 'long':
                return 4096; // ~2000+ words
            default:
                return 2048; // Default to medium
        }
    }

    /**
     * Extract content from AI Manager response
     * Handles various response structures
     * 
     * @param array $result AI Manager response
     * @return string Extracted content
     */
    private function extractContentFromResponse($result)
    {
        // Try various possible locations in the response
        if (isset($result['data']['content']) && is_string($result['data']['content'])) {
            return $result['data']['content'];
        }

        if (isset($result['data']['raw_output']) && is_string($result['data']['raw_output'])) {
            return $result['data']['raw_output'];
        }

        if (isset($result['data']['text']) && is_string($result['data']['text'])) {
            return $result['data']['text'];
        }

        if (isset($result['insights']) && is_string($result['insights'])) {
            return $result['insights'];
        }

        if (isset($result['data']['generated_content']) && is_string($result['data']['generated_content'])) {
            return $result['data']['generated_content'];
        }

        // If content is an array, try to convert to string
        if (isset($result['data']['content']) && is_array($result['data']['content'])) {
            return json_encode($result['data']['content'], JSON_PRETTY_PRINT);
        }

        // Log warning if no content found
        Log::warning('WriterModule: Could not extract content from response', [
            'result_keys' => array_keys($result),
            'data_keys' => isset($result['data']) && is_array($result['data']) ? array_keys($result['data']) : null
        ]);

        return '';
    }

    /**
     * Validate input
     * 
     * @param string $input Input to validate
     * @param string $inputType Type of input (prompt, content)
     * @return bool True if valid
     * @throws \Exception If invalid
     */
    public function validateInput($input, $inputType = 'prompt')
    {
        if (empty(trim($input))) {
            throw new \Exception("{$inputType} cannot be empty");
        }

        $minLength = $inputType === 'prompt' ? 3 : 10;
        if (strlen(trim($input)) < $minLength) {
            throw new \Exception("{$inputType} is too short (minimum {$minLength} characters)");
        }

        $maxLength = $inputType === 'prompt' ? 5000 : 50000;
        if (strlen(trim($input)) > $maxLength) {
            throw new \Exception("{$inputType} is too long (maximum {$maxLength} characters)");
        }

        return true;
    }

    /**
     * Get supported writing modes
     * 
     * @return array List of supported modes
     */
    public function getSupportedModes()
    {
        return ['creative', 'professional', 'academic'];
    }
}

