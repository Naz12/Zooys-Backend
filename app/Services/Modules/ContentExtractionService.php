<?php

namespace App\Services\Modules;

use App\Services\YouTubeService;
use App\Services\DocumentConverterService;
use App\Services\WebScrapingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ContentExtractionService
{
    private $youtubeService;
    private $documentConverterService;
    private $webScrapingService;

    public function __construct(
        YouTubeService $youtubeService,
        DocumentConverterService $documentConverterService,
        WebScrapingService $webScrapingService
    ) {
        $this->youtubeService = $youtubeService;
        $this->documentConverterService = $documentConverterService;
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

        // Use YouTube Transcriber microservice
        $transcript = $this->youtubeService->getVideoContentWithCaptions($videoId);
        
        if (!$transcript) {
            throw new \Exception('Failed to get transcript from YouTube video');
        }

        return [
            'success' => true,
            'content' => $transcript,
            'metadata' => [
                'source_type' => 'youtube',
                'video_id' => $videoId,
                'word_count' => str_word_count($transcript),
                'character_count' => strlen($transcript),
            ]
        ];
    }

    /**
     * Extract content from URL/web page
     */
    private function extractFromUrl($url, $options)
    {
        $result = $this->webScrapingService->extractContent($url);
        
        if (!$result['success']) {
            throw new \Exception($result['error'] ?? 'Failed to extract content from URL');
        }

        return [
            'success' => true,
            'content' => $result['content'],
            'metadata' => array_merge($result['metadata'] ?? [], [
                'source_type' => 'url',
                'word_count' => str_word_count($result['content']),
                'character_count' => strlen($result['content']),
            ])
        ];
    }

    /**
     * Extract content from PDF file
     * Uses PDF microservice
     */
    private function extractFromPDF($filePath, $options)
    {
        // Ensure we have absolute path
        if (!file_exists($filePath)) {
            $filePath = Storage::path($filePath);
        }

        // Use PDF microservice for extraction
        $result = $this->documentConverterService->extractContent($filePath, [
            'content' => true,
            'metadata' => true,
            'images' => $options['include_images'] ?? false
        ]);
        
        if (!$result['success']) {
            throw new \Exception($result['error'] ?? 'PDF extraction failed');
        }

        $extractedData = $result['result'] ?? [];
        $content = $extractedData['content'] ?? '';

        return [
            'success' => true,
            'content' => $content,
            'metadata' => array_merge($extractedData['metadata'] ?? [], [
                'source_type' => 'pdf',
                'word_count' => str_word_count($content),
                'character_count' => strlen($content),
            ])
        ];
    }

    /**
     * Extract content from Word document
     * Uses PDF/Document microservice
     */
    private function extractFromDocument($filePath, $options)
    {
        // Ensure we have absolute path
        if (!file_exists($filePath)) {
            $filePath = Storage::path($filePath);
        }

        // Use PDF/Document microservice for extraction
        $result = $this->documentConverterService->extractContent($filePath, [
            'content' => true,
            'metadata' => true,
            'images' => $options['include_images'] ?? false
        ]);
        
        if (!$result['success']) {
            throw new \Exception($result['error'] ?? 'Document extraction failed');
        }

        $extractedData = $result['result'] ?? [];
        $content = $extractedData['content'] ?? '';

        return [
            'success' => true,
            'content' => $content,
            'metadata' => array_merge($extractedData['metadata'] ?? [], [
                'source_type' => 'document',
                'word_count' => str_word_count($content),
                'character_count' => strlen($content),
            ])
        ];
    }

    /**
     * Extract content from generic file
     * Routes to appropriate extraction method
     */
    private function extractFromFile($filePath, $options)
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'pdf':
                return $this->extractFromPDF($filePath, $options);
            
            case 'doc':
            case 'docx':
                return $this->extractFromDocument($filePath, $options);
            
            case 'txt':
                if (!file_exists($filePath)) {
                    $filePath = Storage::path($filePath);
                }
                $content = file_get_contents($filePath);
                return [
                    'success' => true,
                    'content' => $content,
                    'metadata' => [
                        'source_type' => 'text_file',
                        'word_count' => str_word_count($content),
                        'character_count' => strlen($content),
                    ]
                ];
            
            default:
                throw new \Exception("Unsupported file type: {$extension}");
        }
    }

    /**
     * Detect input type automatically from input string
     */
    public function detectInputType($input)
    {
        if (empty(trim($input))) {
            return 'text';
        }

        $input = trim($input);

        // Check if it's a YouTube URL
        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $input)) {
            return 'youtube';
        }

        // Check if it's a URL (http:// or https://)
        if (preg_match('/^https?:\/\/.+/', $input)) {
            return 'url';
        }

        // Default to text
        return 'text';
    }

    /**
     * Validate input based on input type
     */
    public function validateInput($input, $inputType)
    {
        if (empty(trim($input))) {
            throw new \Exception('Input cannot be empty');
        }

        switch ($inputType) {
            case 'youtube':
            case 'video':
                $videoId = $this->youtubeService->extractVideoId($input);
                if (!$videoId) {
                    throw new \Exception('Invalid YouTube URL');
                }
                break;

            case 'url':
            case 'web':
                if (!filter_var($input, FILTER_VALIDATE_URL)) {
                    throw new \Exception('Invalid URL format');
                }
                break;

            case 'text':
                if (strlen(trim($input)) < 3) {
                    throw new \Exception('Text input is too short (minimum 3 characters)');
                }
                break;

            case 'file':
                // File validation is handled by the file upload system
                break;

            default:
                if (!in_array($inputType, $this->getSupportedTypes())) {
                    throw new \Exception("Unsupported input type: {$inputType}");
                }
                break;
        }

        return true;
    }

    /**
     * Get supported input types
     */
    public function getSupportedTypes()
    {
        return ['text', 'youtube', 'video', 'url', 'web', 'pdf', 'document', 'doc', 'docx', 'file'];
    }
}
