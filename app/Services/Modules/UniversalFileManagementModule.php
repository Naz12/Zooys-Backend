<?php

namespace App\Services\Modules;

use App\Services\FileUploadService;
use App\Services\WebScrapingService;
use App\Services\YouTubeService;
use App\Services\EnhancedPDFProcessingService;
use App\Services\AIMathService;
use App\Services\FlashcardGenerationService;
use App\Services\AIPresentationService;
use App\Services\Modules\AIProcessingModule;
use App\Services\Modules\ContentExtractionService;
use App\Services\Modules\TranscriptionModule;
use Illuminate\Support\Facades\Log;

class UniversalFileManagementModule
{
    public $aiMathService;
    public $flashcardService;
    public $presentationService;
    
    private $fileUploadService;
    private $webScrapingService;
    private $youtubeService;
    private $pdfProcessingService;
    private $aiProcessingModule;
    private $contentExtractionService;
    private $transcriptionModule;

    public function __construct(
        FileUploadService $fileUploadService,
        WebScrapingService $webScrapingService,
        YouTubeService $youtubeService,
        EnhancedPDFProcessingService $pdfProcessingService,
        AIMathService $aiMathService,
        FlashcardGenerationService $flashcardService,
        AIPresentationService $presentationService,
        AIProcessingModule $aiProcessingModule,
        ContentExtractionService $contentExtractionService,
        TranscriptionModule $transcriptionModule
    ) {
        $this->fileUploadService = $fileUploadService;
        $this->webScrapingService = $webScrapingService;
        $this->youtubeService = $youtubeService;
        $this->pdfProcessingService = $pdfProcessingService;
        $this->aiMathService = $aiMathService;
        $this->flashcardService = $flashcardService;
        $this->presentationService = $presentationService;
        $this->aiProcessingModule = $aiProcessingModule;
        $this->contentExtractionService = $contentExtractionService;
        $this->transcriptionModule = $transcriptionModule;
    }

    /**
     * Get AI Processing Module
     */
    public function getAIProcessingModule()
    {
        return $this->aiProcessingModule;
    }

    /**
     * Extract content from various sources
     */
    public function extractContent($source, $options = [])
    {
        try {
            $contentType = $source['type'] ?? 'unknown';
            
            switch ($contentType) {
                case 'file':
                    return $this->extractFromFile($source['data'], $options);
                case 'url':
                    return $this->extractFromUrl($source['data'], $options);
                case 'text':
                    return $this->extractFromText($source['data'], $options);
                default:
                    throw new \Exception("Unsupported content type: {$contentType}");
            }
        } catch (\Exception $e) {
            Log::error("Content extraction failed", [
                'source' => $source,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Content extraction failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Extract content from file
     */
    private function extractFromFile($fileId, $options = [])
    {
        $file = \App\Models\FileUpload::find($fileId);
        if (!$file) {
            throw new \Exception('File not found');
        }

        return $this->contentExtractionService->extractFromFile($file, $options);
    }

    /**
     * Extract content from URL
     */
    private function extractFromUrl($url, $options = [])
    {
        return $this->contentExtractionService->extractFromUrl($url, $options);
    }

    /**
     * Extract content from text
     */
    private function extractFromText($text, $options = [])
    {
            return [
            'success' => true,
            'content' => [
                'text' => $text,
                'metadata' => [
                    'source_type' => 'text',
                    'word_count' => str_word_count($text),
                    'character_count' => strlen($text)
                ]
            ]
        ];
    }

    /**
     * Process content for summarization
     */
    public function processForSummarize($content, $options = [])
    {
        $text = $content['text'];
        $maxTokens = 12000;
        $truncatedText = $this->truncateTextForAI($text, $maxTokens);
        
        $result = $this->aiProcessingModule->summarize($truncatedText, $options);
        
        return [
            'success' => true,
            'data' => [
                'summary' => $result['insights'] ?? '',
                'key_points' => $result['key_points'] ?? [],
                'confidence_score' => $result['confidence_score'] ?? 0.8,
            'metadata' => [
                    'original_length' => strlen($text),
                    'processed_length' => strlen($truncatedText),
                    'tokens_used' => $result['tokens_used'] ?? 0,
                    'model_used' => $result['model_used'] ?? 'unknown'
                ]
            ]
        ];
    }

    /**
     * Truncate text for AI processing
     */
    private function truncateTextForAI($text, $maxTokens)
    {
        $maxChars = $maxTokens * 4; // Rough estimation
        return strlen($text) > $maxChars ? substr($text, 0, $maxChars) . '...' : $text;
    }

    /**
     * Upload file
     */
    public function uploadFile($file, $userId, $options = [])
    {
        return $this->fileUploadService->uploadFile($file, $userId, $options);
    }

    /**
     * Get file by ID
     */
    public function getFile($fileId)
    {
        return \App\Models\FileUpload::find($fileId);
    }

    /**
     * Process YouTube video
     */
    public function processYouTubeVideo($videoUrl, $options = [])
    {
        try {
            $videoId = $this->youtubeService->extractVideoId($videoUrl);
            if (!$videoId) {
                throw new \Exception('Invalid YouTube URL');
            }

            // Get video content with captions
            $transcript = $this->youtubeService->getVideoContentWithCaptions($videoId);
            
            if (!$transcript) {
                throw new \Exception('No transcript available for this video');
            }

            return [
                'success' => true,
                'content' => [
                    'text' => $transcript,
                    'metadata' => [
                        'source_type' => 'youtube',
                        'video_id' => $videoId,
                        'word_count' => str_word_count($transcript),
                        'character_count' => strlen($transcript)
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Log::error("YouTube processing failed", [
                'video_url' => $videoUrl,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'YouTube processing failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process web URL
     */
    public function processWebUrl($url, $options = [])
    {
        try {
            $result = $this->webScrapingService->scrapeUrl($url, $options);
            
            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Web scraping failed');
            }

            return [
                'success' => true,
                'content' => [
                    'text' => $result['content'] ?? '',
                    'metadata' => [
                        'source_type' => 'web',
                        'url' => $url,
                        'title' => $result['title'] ?? '',
                        'word_count' => str_word_count($result['content'] ?? ''),
                        'character_count' => strlen($result['content'] ?? '')
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Log::error("Web URL processing failed", [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Web URL processing failed: ' . $e->getMessage()
            ];
        }
    }
}