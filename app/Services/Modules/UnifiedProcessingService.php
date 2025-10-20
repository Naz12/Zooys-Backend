<?php

namespace App\Services\Modules;

use App\Services\AIResultService;
use App\Services\YouTubeTranscriberService;
use App\Services\AIManagerService;
use Illuminate\Support\Facades\Log;

class UnifiedProcessingService
{
    private $contentExtractionService;
    private $contentChunkingService;
    private $aiSummarizationService;
    private $aiResultService;
    private $moduleRegistry;
    private $youtubeTranscriberService;
    private $aiManagerService;

    public function __construct(
        ContentExtractionService $contentExtractionService,
        ContentChunkingService $contentChunkingService,
        AISummarizationService $aiSummarizationService,
        AIResultService $aiResultService,
        ModuleRegistry $moduleRegistry,
        YouTubeTranscriberService $youtubeTranscriberService,
        AIManagerService $aiManagerService
    ) {
        $this->contentExtractionService = $contentExtractionService;
        $this->contentChunkingService = $contentChunkingService;
        $this->aiSummarizationService = $aiSummarizationService;
        $this->aiResultService = $aiResultService;
        $this->moduleRegistry = $moduleRegistry;
        $this->youtubeTranscriberService = $youtubeTranscriberService;
        $this->aiManagerService = $aiManagerService;
    }

    /**
     * Process content through the complete pipeline
     */
    public function processContent($input, $inputType, $options = [], $userId = null)
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
            $aiResult = $this->saveResult($extractionResult, $summaryResult, $options, $userId);
            
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
     * Process YouTube video with new workflow: check duration -> get bundle -> summarize with AI Manager -> merge
     */
    public function processYouTubeVideo($videoUrl, $options = [], $userId = null)
    {
        try {
            Log::info("Starting YouTube video processing with new workflow", [
                'video_url' => $videoUrl,
                'options' => $options
            ]);

            $videoId = $options['video_id'] ?? null;
            $durationInfo = $options['duration_info'] ?? null;

            // Step 1: Get bundle from transcriber
            Log::info("Getting bundle from YouTube transcriber", [
                'video_url' => $videoUrl,
                'video_id' => $videoId
            ]);

            $transcriptionResult = $this->youtubeTranscriberService->transcribe($videoUrl, [
                'format' => 'bundle',
                'language' => $options['language'] ?? 'auto',
                'meta' => true
            ]);

            if (!$transcriptionResult['success']) {
                throw new \Exception('Transcription failed: ' . $transcriptionResult['error']);
            }

            Log::info("Bundle received from transcriber", [
                'video_id' => $transcriptionResult['video_id'],
                'article_length' => strlen($transcriptionResult['article'] ?? ''),
                'json_segments' => count($transcriptionResult['json']['segments'] ?? [])
            ]);

            // Step 2: Send article to AI Manager for summarization
            $articleText = $transcriptionResult['article'] ?? '';
            if (empty($articleText)) {
                throw new \Exception('No article text received from transcriber');
            }

            Log::info("Sending article to AI Manager for summarization", [
                'article_length' => strlen($articleText),
                'mode' => $options['mode'] ?? 'detailed'
            ]);

            $summaryOptions = [
                'mode' => $options['mode'] ?? 'detailed',
                'language' => $options['language'] ?? 'en',
                'max_tokens' => 1000,
                'temperature' => 0.7
            ];

            $summaryResult = $this->aiManagerService->summarize($articleText, $summaryOptions);

            if (!$summaryResult['success']) {
                throw new \Exception('AI Manager summarization failed: ' . $summaryResult['error']);
            }

            Log::info("AI Manager summarization completed", [
                'summary_length' => strlen($summaryResult['insights']),
                'model_used' => $summaryResult['model_used'],
                'tokens_used' => $summaryResult['tokens_used']
            ]);

            // Step 3: Merge bundle with summary
            $mergedResult = $this->mergeBundleWithSummary($transcriptionResult, $summaryResult, $options);

            // Step 4: Save result
            $aiResult = $this->saveYouTubeResult($transcriptionResult, $summaryResult, $mergedResult, $options, $userId);

            return [
                'success' => true,
                'summary' => $summaryResult['insights'],
                'ai_result' => $aiResult,
                'bundle' => $transcriptionResult,
                'merged_result' => $mergedResult,
                'metadata' => [
                    'video_id' => $transcriptionResult['video_id'],
                    'title' => $transcriptionResult['meta']['title'] ?? 'Unknown',
                    'channel' => $transcriptionResult['meta']['channel'] ?? 'Unknown',
                    'duration' => $durationInfo['duration_formatted'] ?? 'Unknown',
                    'views' => $transcriptionResult['meta']['views'] ?? 0,
                    'has_transcript' => true,
                    'total_characters' => strlen($articleText),
                    'total_words' => str_word_count($articleText),
                    'processing_method' => 'youtube_transcriber_ai_manager',
                    'chunks_processed' => 1,
                    'ai_model_used' => $summaryResult['model_used'],
                    'ai_tokens_used' => $summaryResult['tokens_used']
                ]
            ];

        } catch (\Exception $e) {
            Log::error('YouTube video processing failed: ' . $e->getMessage());
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
    private function saveResult($extractionResult, $summaryResult, $options, $userId = null)
    {
        $userId = $userId ?? auth()->id();
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
     * Merge bundle with AI Manager summary
     */
    private function mergeBundleWithSummary($transcriptionResult, $summaryResult, $options)
    {
        try {
            $bundle = $transcriptionResult;
            $summary = $summaryResult['insights'];
            
            // Create merged result structure
            $mergedResult = [
                'video_id' => $bundle['video_id'],
                'language' => $bundle['language'],
                'format' => 'bundle_with_summary',
                'article' => $bundle['article'],
                'summary' => $summary,
                'json' => $bundle['json'],
                'meta' => array_merge($bundle['meta'] ?? [], [
                    'ai_summary' => $summary,
                    'ai_model_used' => $summaryResult['model_used'],
                    'ai_tokens_used' => $summaryResult['tokens_used'],
                    'ai_confidence_score' => $summaryResult['confidence_score'],
                    'processing_time' => $summaryResult['processing_time'],
                    'merged_at' => now()->toISOString()
                ])
            ];

            Log::info("Bundle merged with AI summary", [
                'video_id' => $bundle['video_id'],
                'article_length' => strlen($bundle['article']),
                'summary_length' => strlen($summary),
                'json_segments' => count($bundle['json']['segments'] ?? [])
            ]);

            return $mergedResult;

        } catch (\Exception $e) {
            Log::error('Failed to merge bundle with summary: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Save YouTube processing result
     */
    private function saveYouTubeResult($transcriptionResult, $summaryResult, $mergedResult, $options, $userId = null)
    {
        try {
            $userId = $userId ?? auth()->id();
            $title = $this->generateYouTubeTitle($transcriptionResult, $options);
            $description = $this->generateDescription($summaryResult['insights']);
            $toolType = 'summarize';
            $inputData = $transcriptionResult['article'];
            $resultData = [
                'summary' => $summaryResult['insights'],
                'bundle' => $mergedResult,
                'ai_metadata' => [
                    'model_used' => $summaryResult['model_used'],
                    'tokens_used' => $summaryResult['tokens_used'],
                    'confidence_score' => $summaryResult['confidence_score'],
                    'processing_time' => $summaryResult['processing_time']
                ]
            ];
            
            $metadata = [
                'video_id' => $transcriptionResult['video_id'],
                'language' => $transcriptionResult['language'],
                'format' => 'bundle_with_summary',
                'source_type' => 'youtube',
                'processing_method' => 'youtube_transcriber_ai_manager',
                'total_characters' => strlen($transcriptionResult['article']),
                'total_words' => str_word_count($transcriptionResult['article']),
                'summary_length' => strlen($summaryResult['insights']),
                'json_segments' => count($transcriptionResult['json']['segments'] ?? [])
            ];
            
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
            
            Log::info("YouTube result saved successfully", [
                'ai_result_id' => $saveResult['ai_result']->id,
                'video_id' => $transcriptionResult['video_id'],
                'user_id' => $userId
            ]);
            
            return $saveResult['ai_result'];

        } catch (\Exception $e) {
            Log::error('Failed to save YouTube result: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate title for YouTube result
     */
    private function generateYouTubeTitle($transcriptionResult, $options)
    {
        $videoId = $transcriptionResult['video_id'] ?? 'unknown';
        $title = $transcriptionResult['meta']['title'] ?? null;
        
        if ($title) {
            return "YouTube Summary: {$title}";
        }
        
        return "YouTube Video Summary ({$videoId})";
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
