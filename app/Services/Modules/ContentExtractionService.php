<?php

namespace App\Services\Modules;

use App\Services\YouTubeService;
use App\Services\DocumentExtractionMicroservice;
use App\Services\EnhancedDocumentProcessingService;
use App\Services\WordProcessingService;
use App\Services\WebScrapingService;
use Illuminate\Support\Facades\Log;

class ContentExtractionService
{
    private $youtubeService;
    private $documentExtractionService;
    private $documentService;
    private $webScrapingService;

    public function __construct(
        YouTubeService $youtubeService,
        DocumentExtractionMicroservice $documentExtractionService,
        EnhancedDocumentProcessingService $documentService,
        WebScrapingService $webScrapingService
    ) {
        $this->youtubeService = $youtubeService;
        $this->documentExtractionService = $documentExtractionService;
        $this->documentService = $documentService;
        $this->webScrapingService = $webScrapingService;
    }

    /**
     * Extract content from various sources
     */
    public function extractContent($input, $inputType, $options = [])
    {
        try {
            Log::info("Extracting content from {$inputType}: " . (is_string($input) ? substr($input, 0, 100) : 'file'));
            
            switch ($inputType) {
                case 'text':
                    return $this->extractFromText($input, $options);
                
                case 'youtube':
                case 'video':
                    return $this->extractFromYouTube($input, $options);
                
                case 'url':
                case 'web':
                    return $this->extractFromUrl($input, $options);
                
                case 'pdf':
                    return $this->extractFromPDF($input, $options);
                
                case 'document':
                case 'doc':
                case 'docx':
                    return $this->extractFromDocument($input, $options);
                
                case 'file':
                    return $this->extractFromFile($input, $options);
                
                default:
                    throw new \Exception("Unsupported input type: {$inputType}");
            }
        } catch (\Exception $e) {
            Log::error('Content extraction error: ' . $e->getMessage());
            return [
                'success' => false,
                'content' => '',
                'metadata' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract content from plain text
     */
    private function extractFromText($text, $options)
    {
        if (empty(trim($text))) {
            throw new \Exception('Text input is empty');
        }

        return [
            'success' => true,
            'content' => trim($text),
            'metadata' => [
                'source_type' => 'text',
                'word_count' => str_word_count($text),
                'character_count' => strlen($text),
                'language' => $this->detectLanguage($text),
            ]
        ];
    }

    /**
     * Extract content from YouTube video
     */
    private function extractFromYouTube($videoUrl, $options)
    {
        $videoId = $this->youtubeService->extractVideoId($videoUrl);
        
        if (!$videoId) {
            throw new \Exception('Invalid YouTube URL');
        }

        // Try to get full transcript using YouTube Transcriber microservice
        $transcript = $this->youtubeService->getVideoContentWithCaptions($videoId);
        
        // Create minimal video data for metadata
        $videoData = [
            'id' => $videoId,
            'title' => 'YouTube Video',
            'description' => 'Video content extracted via transcription',
            'channel_title' => 'Unknown',
            'published_at' => null,
            'duration' => null,
            'view_count' => 0,
            'like_count' => 0,
            'comment_count' => 0,
            'tags' => [],
            'category_id' => null,
            'thumbnail' => null,
        ];
        
        if ($transcript) {
            // Use full transcript
            $content = $this->buildYouTubeContentWithTranscript($videoData, $transcript);
            $hasTranscript = true;
        } else {
            // Fallback to metadata only
            $content = $this->buildYouTubeContentFromMetadata($videoData);
            $hasTranscript = false;
        }

        return [
            'success' => true,
            'content' => $content,
            'metadata' => [
                'source_type' => 'youtube',
                'video_id' => $videoId,
                'title' => $videoData['title'],
                'channel' => $videoData['channel_title'],
                'duration' => $videoData['duration'],
                'views' => $videoData['view_count'],
                'has_transcript' => $hasTranscript,
                'word_count' => str_word_count($content),
                'character_count' => strlen($content),
            ]
        ];
    }

    /**
     * Extract content from URL/web page
     */
    private function extractFromUrl($url, $options)
    {
        $result = $this->webScrapingService->scrapeContent($url);
        
        if (!$result['success']) {
            throw new \Exception($result['error']);
        }

        return [
            'success' => true,
            'content' => $result['content'],
            'metadata' => array_merge($result['metadata'], [
                'source_type' => 'url',
                'word_count' => str_word_count($result['content']),
                'character_count' => strlen($result['content']),
            ])
        ];
    }

    /**
     * Extract content from PDF file
     */
    private function extractFromPDF($filePath, $options)
    {
        $result = $this->pdfService->extractTextFromPDF($filePath);
        
        if (!$result['success']) {
            throw new \Exception($result['error']);
        }

        return [
            'success' => true,
            'content' => $result['content'],
            'metadata' => array_merge($result['metadata'], [
                'source_type' => 'pdf',
                'word_count' => str_word_count($result['content']),
                'character_count' => strlen($result['content']),
            ])
        ];
    }

    /**
     * Extract content from Word document
     */
    private function extractFromWord($filePath, $options)
    {
        $result = $this->wordService->extractTextFromWord($filePath);
        
        if (!$result['success']) {
            throw new \Exception($result['error']);
        }

        return [
            'success' => true,
            'content' => $result['text'],
            'metadata' => array_merge($result['metadata'], [
                'source_type' => 'word',
                'word_count' => $result['word_count'],
                'character_count' => $result['character_count'],
                'paragraphs' => $result['paragraphs'],
            ])
        ];
    }

    /**
     * Extract content from text file
     */
    private function extractFromTxt($filePath, $options)
    {
        $result = $this->txtService->extractTextFromTxt($filePath);
        
        if (!$result['success']) {
            throw new \Exception($result['error']);
        }

        return [
            'success' => true,
            'content' => $result['text'],
            'metadata' => array_merge($result['metadata'], [
                'source_type' => 'txt',
                'word_count' => $result['word_count'],
                'character_count' => $result['character_count'],
                'lines' => $result['lines'],
            ])
        ];
    }

    /**
     * Extract content from PowerPoint file
     */
    private function extractFromPpt($filePath, $options)
    {
        $result = $this->pptService->extractTextFromPpt($filePath);
        
        if (!$result['success']) {
            throw new \Exception($result['error']);
        }

        return [
            'success' => true,
            'content' => $result['text'],
            'metadata' => array_merge($result['metadata'], [
                'source_type' => 'ppt',
                'word_count' => $result['word_count'],
                'character_count' => $result['character_count'],
                'slides' => $result['slides'],
            ])
        ];
    }

    /**
     * Extract content from Excel file
     */
    private function extractFromExcel($filePath, $options)
    {
        $result = $this->excelService->extractTextFromExcel($filePath);
        
        if (!$result['success']) {
            throw new \Exception($result['error']);
        }

        return [
            'success' => true,
            'content' => $result['text'],
            'metadata' => array_merge($result['metadata'], [
                'source_type' => 'excel',
                'word_count' => $result['word_count'],
                'character_count' => $result['character_count'],
                'sheets' => $result['sheets'],
            ])
        ];
    }

    /**
     * Extract content from document file (legacy)
     */
    private function extractFromDocument($filePath, $options)
    {
        $result = $this->documentService->extractTextFromDocument($filePath);
        
        if (!$result['success']) {
            throw new \Exception($result['error']);
        }

        return [
            'success' => true,
            'content' => $result['content'],
            'metadata' => array_merge($result['metadata'], [
                'source_type' => 'document',
                'word_count' => str_word_count($result['content']),
                'character_count' => strlen($result['content']),
            ])
        ];
    }

    /**
     * Extract content from uploaded file
     */
    public function extractFromFile($fileId, $options)
    {
        $fileUpload = \App\Models\FileUpload::find($fileId);
        
        if (!$fileUpload) {
            throw new \Exception('File not found');
        }

        // Check if file exists in public storage first, then app storage
        $publicPath = storage_path('app/public/' . $fileUpload->file_path);
        $appPath = storage_path('app/' . $fileUpload->file_path);
        
        if (file_exists($publicPath)) {
            $filePath = $publicPath;
        } elseif (file_exists($appPath)) {
            $filePath = $appPath;
        } else {
            throw new \Exception('File not found at expected location: ' . $fileUpload->file_path);
        }
        
        $fileType = strtolower($fileUpload->file_type);
        
        // Use the document extraction microservice
        $result = $this->documentExtractionService->extractText($filePath, $fileType, $options);
        
        if (!$result['success']) {
            throw new \Exception($result['error'] ?? 'Document extraction failed');
        }

        return [
            'success' => true,
            'content' => $result['text'],
            'metadata' => array_merge($result['metadata'], [
                'source_type' => $fileType,
                'word_count' => $result['word_count'],
                'character_count' => $result['character_count'],
                'extraction_method' => $result['extraction_method'] ?? 'microservice'
            ])
        ];
    }

    /**
     * Build YouTube content with full transcript
     */
    private function buildYouTubeContentWithTranscript($videoData, $transcript)
    {
        $content = "Title: {$videoData['title']}\n\n";
        $content .= "Channel: {$videoData['channel_title']}\n";
        $content .= "Duration: {$videoData['duration']}\n";
        $content .= "Views: {$videoData['view_count']}\n\n";
        
        $content .= "=== FULL VIDEO TRANSCRIPT ===\n";
        $content .= $transcript . "\n\n";
        
        $content .= "=== VIDEO DESCRIPTION ===\n";
        $content .= $videoData['description'] . "\n";
        
        if (!empty($videoData['tags'])) {
            $content .= "\nTags: " . implode(', ', $videoData['tags']) . "\n";
        }
        
        return $content;
    }

    /**
     * Build YouTube content from metadata only
     */
    private function buildYouTubeContentFromMetadata($videoData)
    {
        $content = "Title: {$videoData['title']}\n\n";
        $content .= "Description: {$videoData['description']}\n\n";
        $content .= "Channel: {$videoData['channel_title']}\n";
        $content .= "Duration: {$videoData['duration']}\n";
        $content .= "Views: {$videoData['view_count']}\n";
        
        if (!empty($videoData['tags'])) {
            $content .= "Tags: " . implode(', ', $videoData['tags']) . "\n";
        }
        
        return $content;
    }

    /**
     * Detect content language
     */
    private function detectLanguage($text)
    {
        // Simple language detection - can be enhanced with proper NLP
        $sample = substr($text, 0, 1000);
        
        // Basic heuristics
        if (preg_match('/[а-яё]/i', $sample)) return 'ru';
        if (preg_match('/[一-龯]/', $sample)) return 'zh';
        if (preg_match('/[あ-ん]/', $sample)) return 'ja';
        if (preg_match('/[ا-ي]/', $sample)) return 'ar';
        
        return 'en'; // Default to English
    }

    /**
     * Get extraction statistics
     */
    public function getExtractionStats($result)
    {
        if (!$result['success']) {
            return ['error' => $result['error']];
        }
        
        return [
            'source_type' => $result['metadata']['source_type'],
            'word_count' => $result['metadata']['word_count'],
            'character_count' => $result['metadata']['character_count'],
            'language' => $result['metadata']['language'] ?? 'unknown',
            'has_transcript' => $result['metadata']['has_transcript'] ?? false,
        ];
    }

    /**
     * Detect input type based on input content
     */
    public function detectInputType($input)
    {
        if (is_numeric($input)) {
            return 'file';
        }
        
        if (filter_var($input, FILTER_VALIDATE_URL)) {
            if (strpos($input, 'youtube.com') !== false || strpos($input, 'youtu.be') !== false) {
                return 'youtube';
            }
            return 'url';
        }
        
        return 'text';
    }

    /**
     * Validate input based on type
     */
    public function validateInput($input, $inputType)
    {
        switch ($inputType) {
            case 'text':
                if (empty(trim($input))) {
                    throw new \Exception('Text input cannot be empty');
                }
                break;
                
            case 'url':
            case 'youtube':
                if (!filter_var($input, FILTER_VALIDATE_URL)) {
                    throw new \Exception('Invalid URL format');
                }
                break;
                
            case 'file':
                if (!is_numeric($input)) {
                    throw new \Exception('File ID must be numeric');
                }
                break;
                
            default:
                throw new \Exception("Unsupported input type: {$inputType}");
        }
        
        return true;
    }
}
