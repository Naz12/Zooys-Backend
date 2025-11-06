<?php

namespace App\Services\Modules;

use App\Services\Modules\AIProcessingModule;
use Illuminate\Support\Facades\Log;

class AISummarizationService
{
    private $aiProcessingModule;
    private $maxTokens;
    private $temperature;

    public function __construct(AIProcessingModule $aiProcessingModule)
    {
        $this->aiProcessingModule = $aiProcessingModule;
        $this->maxTokens = config('ai.summarization.max_tokens', 1000);
        $this->temperature = config('ai.summarization.temperature', 0.7);
    }

    /**
     * Summarize content directly using AI Manager (chunking handled by Document Intelligence or AI Manager)
     */
    public function summarizeContent($content, $contentType = 'text', $options = [])
    {
        if (empty($content)) {
            return $this->createErrorResponse('No content to summarize');
        }
        
        Log::info("Processing {$contentType} content for summarization", [
            'content_length' => strlen($content),
            'word_count' => str_word_count($content)
        ]);
        
        try {
            // Use AI Manager directly - it handles large content internally
            $result = $this->aiProcessingModule->summarize($content, $options);
            
            return [
                'success' => true,
                'summary' => $result['insights'] ?? $result['summary'] ?? '',
                'key_points' => $result['key_points'] ?? [],
                'metadata' => [
                    'total_characters' => strlen($content),
                    'total_words' => str_word_count($content),
                    'processing_method' => 'direct_ai_manager',
                    'model_used' => $result['model_used'] ?? 'unknown',
                    'tokens_used' => $result['tokens_used'] ?? 0,
                    'confidence_score' => $result['confidence_score'] ?? 0.8,
                ]
            ];
        } catch (\Exception $e) {
            Log::error("Summarization failed: " . $e->getMessage());
            return $this->createErrorResponse('Summarization failed: ' . $e->getMessage());
        }
    }


    /**
     * Create error response
     */
    private function createErrorResponse($error)
    {
        return [
            'success' => false,
            'error' => $error,
            'summary' => null,
            'metadata' => []
        ];
    }

    /**
     * Get summarization statistics
     */
    public function getSummarizationStats($result)
    {
        if (!$result['success']) {
            return ['error' => $result['error']];
        }
        
        return [
            'total_characters' => $result['metadata']['total_characters'] ?? 0,
            'total_words' => $result['metadata']['total_words'] ?? 0,
            'processing_method' => $result['metadata']['processing_method'] ?? 'direct_ai_manager',
            'summary_length' => strlen($result['summary'] ?? ''),
            'summary_words' => str_word_count($result['summary'] ?? ''),
            'model_used' => $result['metadata']['model_used'] ?? 'unknown',
            'tokens_used' => $result['metadata']['tokens_used'] ?? 0,
        ];
    }
}
