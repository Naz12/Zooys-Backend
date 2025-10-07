<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ContentExtractionService
{
    protected $webScrapingService;
    protected $youtubeService;
    protected $contentProcessingService;
    protected $enhancedDocumentProcessingService;
    protected $wordProcessingService;

    public function __construct(
        WebScrapingService $webScrapingService,
        YouTubeService $youtubeService,
        ContentProcessingService $contentProcessingService,
        EnhancedDocumentProcessingService $enhancedDocumentProcessingService,
        WordProcessingService $wordProcessingService
    ) {
        $this->webScrapingService = $webScrapingService;
        $this->youtubeService = $youtubeService;
        $this->contentProcessingService = $contentProcessingService;
        $this->enhancedDocumentProcessingService = $enhancedDocumentProcessingService;
        $this->wordProcessingService = $wordProcessingService;
    }

    /**
     * Extract content from various input types
     */
    public function extractContent($input, $inputType = 'text')
    {
        try {
            switch ($inputType) {
                case 'text':
                    return $this->extractFromText($input);
                
                case 'url':
                case 'link':
                    return $this->extractFromUrl($input);
                
                case 'youtube':
                    return $this->extractFromYouTube($input);
                
                case 'file':
                    return $this->extractFromFile($input);
                
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
    private function extractFromText($text)
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
                'character_count' => strlen($text)
            ]
        ];
    }

    /**
     * Extract content from URL/web link
     */
    private function extractFromUrl($url)
    {
        $result = $this->webScrapingService->extractWebContent($url);
        
        if (!$result['success']) {
            throw new \Exception($result['error']);
        }

        return [
            'success' => true,
            'content' => $result['content'],
            'metadata' => array_merge($result['metadata'], [
                'source_type' => 'url',
                'word_count' => str_word_count($result['content'])
            ])
        ];
    }

    /**
     * Extract content from uploaded file
     */
    private function extractFromFile($fileId)
    {
        $fileUpload = \App\Models\FileUpload::find($fileId);
        
        if (!$fileUpload) {
            throw new \Exception('File not found');
        }

        $fileUploadService = app(\App\Services\FileUploadService::class);
        $result = $fileUploadService->getFileContent($fileUpload);
        
        if (!$result['success']) {
            throw new \Exception($result['error']);
        }

        return [
            'success' => true,
            'content' => $result['content'],
            'metadata' => array_merge($result['metadata'], [
                'source_type' => 'file',
                'file_id' => $fileUpload->id,
                'file_name' => $fileUpload->original_name,
                'file_type' => $fileUpload->file_type,
                'file_size' => $fileUpload->file_size,
                'file_url' => $fileUpload->file_url
            ])
        ];
    }

    /**
     * Extract content from YouTube video
     */
private function extractFromYouTube($videoUrl)
    {
        // Extract video ID
        $videoId = $this->youtubeService->extractVideoId($videoUrl);
        
        if (!$videoId) {
            throw new \Exception('Invalid YouTube URL');
        }

        // Get video details
        $videoData = $this->youtubeService->getVideoDetails($videoId);
        
        if (!$videoData) {
            throw new \Exception('Video not found or unavailable');
        }

        // Try to get captions first
        $captions = $this->youtubeService->getVideoContentWithCaptions($videoId);
        
        if ($captions) {
            // Use captions as primary content
            $content = $this->buildYouTubeContentWithCaptions($videoData, $captions);
            $hasCaptions = true;
        } else {
            // Fallback to basic metadata
            $content = $this->buildYouTubeContent($videoData);
            $hasCaptions = false;
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
                'has_captions' => $hasCaptions,
                'word_count' => str_word_count($content)
            ]
        ];
    }

    /**
     * Extract content from file path
     */
    private function extractFromFilePath($filePath)
    {
        $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        switch ($fileExtension) {
            case 'pdf':
                return $this->extractFromPDF($filePath);
            
            case 'doc':
            case 'docx':
                return $this->extractFromWord($filePath);
            
            case 'txt':
                return $this->extractFromTextFile($filePath);
            
            default:
                throw new \Exception("Unsupported file type: {$fileExtension}");
        }
    }

    /**
     * Extract content from PDF file
     */
    private function extractFromPDF($filePath)
    {
        $result = $this->contentProcessingService->extractTextFromPDF($filePath);
        
        if (!$result['success']) {
            throw new \Exception($result['error'] ?? 'Failed to extract text from PDF');
        }

        return [
            'success' => true,
            'content' => $result['text'],
            'metadata' => [
                'source_type' => 'pdf',
                'pages' => $result['pages'],
                'word_count' => $result['word_count'],
                'character_count' => $result['character_count']
            ]
        ];
    }

    /**
     * Extract content from Word document
     */
    private function extractFromWord($filePath)
    {
        $result = $this->wordProcessingService->extractTextFromWord($filePath);
        
        if (!$result['success']) {
            throw new \Exception($result['error'] ?? 'Failed to extract text from Word document');
        }

        return [
            'success' => true,
            'content' => $result['text'],
            'metadata' => [
                'source_type' => 'word',
                'word_count' => $result['word_count'],
                'character_count' => $result['character_count']
            ]
        ];
    }

    /**
     * Extract content from text file
     */
    private function extractFromTextFile($filePath)
    {
        if (!file_exists($filePath)) {
            throw new \Exception('File not found');
        }

        $content = file_get_contents($filePath);
        
        if ($content === false) {
            throw new \Exception('Failed to read text file');
        }

        return [
            'success' => true,
            'content' => $content,
            'metadata' => [
                'source_type' => 'text_file',
                'word_count' => str_word_count($content),
                'character_count' => strlen($content)
            ]
        ];
    }

    /**
     * Build content from YouTube video data
     */
    private function buildYouTubeContent($videoData)
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
     * Build YouTube content with captions
     */
    private function buildYouTubeContentWithCaptions($videoData, $captions)
    {
        $content = "Title: {$videoData['title']}\n\n";
        $content .= "Channel: {$videoData['channel_title']}\n";
        $content .= "Duration: {$videoData['duration']}\n";
        $content .= "Views: {$videoData['view_count']}\n\n";
        
        $content .= "=== VIDEO TRANSCRIPT ===\n";
        $content .= $captions . "\n\n";
        
        $content .= "=== VIDEO DESCRIPTION ===\n";
        $content .= $videoData['description'] . "\n";
        
        if (!empty($videoData['tags'])) {
            $content .= "\nTags: " . implode(', ', $videoData['tags']) . "\n";
        }
        
        return $content;
    }

    /**
     * Detect input type automatically
     */
    public function detectInputType($input)
    {
        // Check if it's a URL
        if (filter_var($input, FILTER_VALIDATE_URL)) {
            // Check if it's YouTube
            if (strpos($input, 'youtube.com') !== false || strpos($input, 'youtu.be') !== false) {
                return 'youtube';
            }
            return 'url';
        }

        // Check if it's a file path
        if (file_exists($input)) {
            return 'file';
        }

        // Default to text
        return 'text';
    }

    /**
     * Validate input based on type
     */
    public function validateInput($input, $inputType)
    {
        switch ($inputType) {
            case 'text':
                if (empty(trim($input)) || strlen($input) < 10) {
                    throw new \Exception('Text input is too short. Please provide more detailed content.');
                }
                break;
            
            case 'url':
            case 'youtube':
                if (!filter_var($input, FILTER_VALIDATE_URL)) {
                    throw new \Exception('Invalid URL format');
                }
                break;
            
            case 'file':
                if (!file_exists($input)) {
                    throw new \Exception('File not found');
                }
                break;
        }

        return true;
    }
}
