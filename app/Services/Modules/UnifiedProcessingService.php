<?php

namespace App\Services\Modules;

use App\Services\AIResultService;
use Illuminate\Support\Facades\Log;

class UnifiedProcessingService
{
    private $contentExtractionService;
    private $contentChunkingService;
    private $aiSummarizationService;
    private $aiResultService;
    private $moduleRegistry;

    public function __construct(
        ContentExtractionService $contentExtractionService,
        ContentChunkingService $contentChunkingService,
        AISummarizationService $aiSummarizationService,
        AIResultService $aiResultService,
        ModuleRegistry $moduleRegistry
    ) {
        $this->contentExtractionService = $contentExtractionService;
        $this->contentChunkingService = $contentChunkingService;
        $this->aiSummarizationService = $aiSummarizationService;
        $this->aiResultService = $aiResultService;
        $this->moduleRegistry = $moduleRegistry;
    }

    /**
     * Process content through the complete pipeline
     */
    public function processContent($input, $inputType, $options = [])
    {
        try {
            Log::info("Starting unified processing for {$inputType}");
            
            // Step 1: Extract content
            $extractionResult = $this->contentExtractionService->extractContent($input, $inputType, $options);
            
            if (!$extractionResult['success']) {
                throw new \Exception('Content extraction failed: ' . $extractionResult['error']);
            }
            
            Log::info("Content extracted: " . $extractionResult['metadata']['word_count'] . " words");
            
            // Step 2: Chunk content if needed
            $chunkingOptions = $this->getChunkingOptions($extractionResult, $options);
            $chunks = $this->contentChunkingService->chunkContent(
                $extractionResult['content'], 
                $inputType, 
                $chunkingOptions
            );
            
            Log::info("Content chunked into " . count($chunks) . " chunks");
            
            // Step 3: Summarize content
            $summarizationOptions = $this->getSummarizationOptions($options);
            $summaryResult = $this->aiSummarizationService->summarizeContent(
                $extractionResult['content'], 
                $inputType, 
                $summarizationOptions
            );
            
            if (!$summaryResult['success']) {
                throw new \Exception('Content summarization failed: ' . $summaryResult['error']);
            }
            
            Log::info("Content summarized successfully");
            
            // Step 4: Save result
            $aiResult = $this->saveResult($extractionResult, $summaryResult, $options);
            
            return [
                'success' => true,
                'summary' => $summaryResult['summary'],
                'ai_result' => $aiResult,
                'metadata' => array_merge(
                    $extractionResult['metadata'],
                    $summaryResult['metadata'],
                    [
                        'processing_method' => 'unified',
                        'chunks_processed' => count($chunks),
                        'total_characters' => $extractionResult['metadata']['character_count'],
                        'total_words' => $extractionResult['metadata']['word_count'],
                    ]
                )
            ];
            
        } catch (\Exception $e) {
            Log::error('Unified processing failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'summary' => null,
                'ai_result' => null,
                'metadata' => []
            ];
        }
    }

    /**
     * Process YouTube video
     */
    public function processYouTubeVideo($videoUrl, $options = [])
    {
        return $this->processContent($videoUrl, 'youtube', $options);
    }

    /**
     * Process PDF document
     */
    public function processPDFDocument($filePath, $options = [])
    {
        return $this->processContent($filePath, 'pdf', $options);
    }

    /**
     * Process web URL
     */
    public function processWebUrl($url, $options = [])
    {
        return $this->processContent($url, 'url', $options);
    }

    /**
     * Process text content
     */
    public function processTextContent($text, $options = [])
    {
        return $this->processContent($text, 'text', $options);
    }

    /**
     * Get chunking options based on content
     */
    private function getChunkingOptions($extractionResult, $options)
    {
        $wordCount = $extractionResult['metadata']['word_count'];
        $characterCount = $extractionResult['metadata']['character_count'];
        
        // Determine if chunking is needed
        $needsChunking = $characterCount > 8000; // OpenAI context limit
        
        if (!$needsChunking) {
            return ['enabled' => false];
        }
        
        // Calculate optimal chunk size
        $maxChunkSize = $options['max_chunk_size'] ?? 3000;
        $overlapSize = $options['overlap_size'] ?? 200;
        
        return [
            'enabled' => true,
            'max_size' => $maxChunkSize,
            'overlap' => $overlapSize,
            'strategy' => $this->getChunkingStrategy($extractionResult['metadata']['source_type']),
        ];
    }

    /**
     * Get summarization options
     */
    private function getSummarizationOptions($options)
    {
        return [
            'mode' => $options['mode'] ?? 'detailed',
            'language' => $options['language'] ?? 'en',
            'max_tokens' => $options['max_tokens'] ?? 1000,
            'temperature' => $options['temperature'] ?? 0.7,
        ];
    }

    /**
     * Get chunking strategy based on content type
     */
    private function getChunkingStrategy($sourceType)
    {
        $strategies = [
            'youtube' => 'transcript',
            'pdf' => 'document',
            'text' => 'text',
            'url' => 'text',
        ];
        
        return $strategies[$sourceType] ?? 'text';
    }

    /**
     * Save processing result
     */
    private function saveResult($extractionResult, $summaryResult, $options)
    {
        $userId = auth()->id();
        $title = $this->generateTitle($extractionResult, $options);
        $description = $this->generateDescription($summaryResult['summary']);
        $toolType = $this->getToolType($extractionResult['metadata']['source_type']);
        $inputData = $extractionResult['content'];
        $resultData = ['summary' => $summaryResult['summary']];
        $metadata = array_merge(
            $extractionResult['metadata'],
            $summaryResult['metadata']
        );
        
        $saveResult = $this->aiResultService->saveResult(
            $userId,
            $toolType,
            $title,
            $description,
            $inputData,
            $resultData,
            $metadata
        );
        
        if (!$saveResult['success']) {
            throw new \Exception($saveResult['error']);
        }
        
        return $saveResult['ai_result'];
    }

    /**
     * Generate title for the result
     */
    private function generateTitle($extractionResult, $options)
    {
        $sourceType = $extractionResult['metadata']['source_type'];
        
        switch ($sourceType) {
            case 'youtube':
                return $extractionResult['metadata']['title'] ?? 'YouTube Video Summary';
            case 'pdf':
                return 'PDF Document Summary';
            case 'url':
                return 'Web Content Summary';
            case 'text':
                return 'Text Content Summary';
            default:
                return 'Content Summary';
        }
    }

    /**
     * Get tool type for the result
     */
    private function getToolType($sourceType)
    {
        $toolTypes = [
            'youtube' => 'summarize',
            'pdf' => 'summarize',
            'url' => 'summarize',
            'text' => 'summarize',
        ];
        
        return $toolTypes[$sourceType] ?? 'summarize';
    }

    /**
     * Generate description from summary
     */
    private function generateDescription($summary)
    {
        $words = explode(' ', $summary);
        $description = implode(' ', array_slice($words, 0, 20));
        return strlen($description) > 150 ? substr($description, 0, 147) . '...' : $description;
    }

    /**
     * Get processing statistics
     */
    public function getProcessingStats($result)
    {
        if (!$result['success']) {
            return ['error' => $result['error']];
        }
        
        return [
            'processing_method' => $result['metadata']['processing_method'],
            'chunks_processed' => $result['metadata']['chunks_processed'],
            'total_characters' => $result['metadata']['total_characters'],
            'total_words' => $result['metadata']['total_words'],
            'summary_length' => strlen($result['summary']),
            'summary_words' => str_word_count($result['summary']),
        ];
    }
}
