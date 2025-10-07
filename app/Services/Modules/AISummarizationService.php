<?php

namespace App\Services\Modules;

use App\Services\OpenAIService;
use Illuminate\Support\Facades\Log;

class AISummarizationService
{
    private $openAIService;
    private $maxTokens;
    private $temperature;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
        $this->maxTokens = config('ai.summarization.max_tokens', 1000);
        $this->temperature = config('ai.summarization.temperature', 0.7);
    }

    /**
     * Summarize content using chunked processing
     */
    public function summarizeContent($content, $contentType = 'text', $options = [])
    {
        $chunkingService = app(ContentChunkingService::class);
        $chunks = $chunkingService->chunkContent($content, $contentType, $options);
        
        if (empty($chunks)) {
            return $this->createErrorResponse('No content to summarize');
        }
        
        Log::info("Processing {$contentType} content with " . count($chunks) . " chunks");
        
        // Process chunks
        $chunkSummaries = $this->processChunks($chunks, $contentType, $options);
        
        if (empty($chunkSummaries)) {
            return $this->createErrorResponse('Failed to process content chunks');
        }
        
        // Combine chunk summaries
        $finalSummary = $this->combineSummaries($chunkSummaries, $contentType, $options);
        
        return $this->createSuccessResponse($finalSummary, $chunks, $chunkSummaries);
    }

    /**
     * Process individual chunks
     */
    private function processChunks($chunks, $contentType, $options)
    {
        $chunkSummaries = [];
        $totalChunks = count($chunks);
        
        foreach ($chunks as $index => $chunk) {
            Log::info("Processing chunk " . ($index + 1) . "/{$totalChunks}");
            
            $chunkSummary = $this->summarizeChunk($chunk, $contentType, $options, $index, $totalChunks);
            
            if ($chunkSummary['success']) {
                $chunkSummaries[] = [
                    'chunk_index' => $index,
                    'summary' => $chunkSummary['summary'],
                    'key_points' => $chunkSummary['key_points'] ?? [],
                    'metadata' => $chunkSummary['metadata'] ?? [],
                ];
            } else {
                Log::warning("Failed to summarize chunk {$index}: " . $chunkSummary['error']);
            }
        }
        
        return $chunkSummaries;
    }

    /**
     * Summarize a single chunk
     */
    private function summarizeChunk($chunk, $contentType, $options, $chunkIndex, $totalChunks)
    {
        $prompt = $this->buildChunkPrompt($chunk, $contentType, $options, $chunkIndex, $totalChunks);
        
        try {
            $response = $this->openAIService->generateResponse($prompt);
            
            return [
                'success' => true,
                'summary' => $response,
                'key_points' => $this->extractKeyPoints($response),
                'metadata' => [
                    'chunk_size' => $chunk['character_count'],
                    'word_count' => $chunk['word_count'],
                    'processing_time' => microtime(true),
                ]
            ];
        } catch (\Exception $e) {
            Log::error("Chunk summarization failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Build prompt for chunk summarization
     */
    private function buildChunkPrompt($chunk, $contentType, $options, $chunkIndex, $totalChunks)
    {
        $mode = $options['mode'] ?? 'detailed';
        $language = $options['language'] ?? 'en';
        
        $basePrompt = "Analyze this {$contentType} content chunk ({$chunkIndex}/{$totalChunks}) and provide a summary:\n\n";
        $basePrompt .= "Content:\n{$chunk['content']}\n\n";
        
        if ($mode === 'brief') {
            $basePrompt .= "Provide:\n1. Main topic (1 sentence)\n2. Key takeaway (1 sentence)";
        } else {
            $basePrompt .= "Provide:\n1. Main topic and themes\n2. Key points (3-5 bullet points)\n3. Important details\n4. Context for overall content";
        }
        
        if ($language !== 'en') {
            $basePrompt .= "\n\nPlease respond in {$language} language.";
        }
        
        return $basePrompt;
    }

    /**
     * Combine chunk summaries into final summary
     */
    private function combineSummaries($chunkSummaries, $contentType, $options)
    {
        $mode = $options['mode'] ?? 'detailed';
        $language = $options['language'] ?? 'en';
        
        // Create combined content from chunk summaries
        $combinedContent = $this->buildCombinedContent($chunkSummaries);
        
        $prompt = "Create a comprehensive summary from these {$contentType} content summaries:\n\n";
        $prompt .= $combinedContent . "\n\n";
        
        if ($mode === 'brief') {
            $prompt .= "Provide:\n1. Overall main topic (1 sentence)\n2. Key takeaway (1 sentence)";
        } else {
            $prompt .= "Provide:\n1. Main topic and themes\n2. Key points (5-7 bullet points)\n3. Target audience\n4. Educational value\n5. Overall rating (1-10)";
        }
        
        if ($language !== 'en') {
            $prompt .= "\n\nPlease respond in {$language} language.";
        }
        
        try {
            $finalSummary = $this->openAIService->generateResponse($prompt);
            return $finalSummary;
        } catch (\Exception $e) {
            Log::error("Final summary combination failed: " . $e->getMessage());
            return "Summary generation failed: " . $e->getMessage();
        }
    }

    /**
     * Build combined content from chunk summaries
     */
    private function buildCombinedContent($chunkSummaries)
    {
        $combined = "";
        
        foreach ($chunkSummaries as $index => $summary) {
            $combined .= "Section " . ($index + 1) . ":\n";
            $combined .= $summary['summary'] . "\n\n";
        }
        
        return $combined;
    }

    /**
     * Extract key points from summary
     */
    private function extractKeyPoints($summary)
    {
        // Simple key point extraction - can be enhanced
        $lines = explode("\n", $summary);
        $keyPoints = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^[-•]\s*(.+)/', $line, $matches)) {
                $keyPoints[] = $matches[1];
            }
        }
        
        return $keyPoints;
    }

    /**
     * Create success response
     */
    private function createSuccessResponse($summary, $chunks, $chunkSummaries)
    {
        return [
            'success' => true,
            'summary' => $summary,
            'metadata' => [
                'total_chunks' => count($chunks),
                'processed_chunks' => count($chunkSummaries),
                'total_characters' => array_sum(array_column($chunks, 'character_count')),
                'total_words' => array_sum(array_column($chunks, 'word_count')),
                'processing_method' => 'chunked',
                'chunk_summaries' => $chunkSummaries,
            ]
        ];
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
            'total_chunks' => $result['metadata']['total_chunks'],
            'processed_chunks' => $result['metadata']['processed_chunks'],
            'total_characters' => $result['metadata']['total_characters'],
            'total_words' => $result['metadata']['total_words'],
            'processing_method' => $result['metadata']['processing_method'],
            'summary_length' => strlen($result['summary']),
            'summary_words' => str_word_count($result['summary']),
        ];
    }
}
