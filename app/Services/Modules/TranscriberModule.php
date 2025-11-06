<?php

namespace App\Services\Modules;

use App\Services\YouTubeTranscriberService;
use App\Services\WebScrapingService;
use Illuminate\Support\Facades\Log;

class TranscriberModule
{
    private $transcriberService;
    private $webScrapingService;

    public function __construct(
        YouTubeTranscriberService $transcriberService,
        WebScrapingService $webScrapingService
    ) {
        $this->transcriberService = $transcriberService;
        $this->webScrapingService = $webScrapingService;
    }

    /**
     * Main transcription method
     */
    public function transcribeVideo($videoUrl, $options = [])
    {
        try {
            Log::info("TranscriberModule: Starting video transcription", [
                'video_url' => $videoUrl,
                'options' => $options
            ]);

            $result = $this->transcriberService->transcribe($videoUrl, $options);
            
            if (!$result['success']) {
                $errorMessage = "Transcription failed: " . ($result['error'] ?? 'Unknown error');
                
                // Include error details if available (for 422 validation errors, etc.)
                if (isset($result['error_details'])) {
                    $errorDetails = $result['error_details'];
                    $errorMessage .= " | Details: " . json_encode($errorDetails);
                    
                    Log::error("TranscriberModule: Transcription failed with details", [
                        'video_url' => $videoUrl,
                        'error' => $result['error'] ?? 'Unknown error',
                        'error_details' => $errorDetails
                    ]);
                }
                
                throw new \Exception($errorMessage);
            }

            // Check if transcript content is actually available
            // Prioritize article_text for bundle format (as shown in manual API test)
            $subtitleText = $result['article_text'] ?? $result['subtitle_text'] ?? '';
            
            Log::info("TranscriberModule: Extracted transcript content", [
                'video_url' => $videoUrl,
                'video_id' => $result['video_id'] ?? null,
                'has_article_text' => isset($result['article_text']),
                'has_subtitle_text' => isset($result['subtitle_text']),
                'article_text_length' => isset($result['article_text']) ? strlen($result['article_text']) : 0,
                'subtitle_text_length' => isset($result['subtitle_text']) ? strlen($result['subtitle_text'] ?? '') : 0,
                'final_transcript_length' => strlen($subtitleText),
                'result_keys' => array_keys($result)
            ]);
            
            if (empty(trim($subtitleText))) {
                Log::warning("TranscriberModule: Transcription succeeded but no content available", [
                    'video_url' => $videoUrl,
                    'video_id' => $result['video_id'] ?? null,
                    'result_keys' => array_keys($result),
                    'article_text_empty' => empty($result['article_text'] ?? ''),
                    'subtitle_text_empty' => empty($result['subtitle_text'] ?? '')
                ]);
                throw new \Exception("No transcript content available from video. The video may not have captions or the transcriber service returned empty content.");
            }

            Log::info("TranscriberModule: Transcription completed successfully", [
                'video_id' => $result['video_id'],
                'language' => $result['language'],
                'format' => $result['format'],
                'content_length' => strlen($subtitleText)
            ]);

            $returnData = [
                'success' => true,
                'video_id' => $result['video_id'],
                'language' => $result['language'],
                'format' => $result['format'],
                'transcript' => $subtitleText,
                'metadata' => $result['meta'] ?? null
            ];
            
            // Include bundle data if available (for bundle format)
            if (isset($result['article_text'])) {
                $returnData['article_text'] = $result['article_text'];
            }
            if (isset($result['json_items'])) {
                $returnData['json_items'] = $result['json_items'];
            }
            if (isset($result['transcript_json'])) {
                $returnData['transcript_json'] = $result['transcript_json'];
            }
            
            return $returnData;

        } catch (\Exception $e) {
            Log::error("TranscriberModule: Transcription failed", [
                'error' => $e->getMessage(),
                'video_url' => $videoUrl
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'video_id' => null,
                'transcript' => null
            ];
        }
    }

    /**
     * Get plain text transcript
     */
    public function getPlainTranscript($videoUrl)
    {
        return $this->transcribeVideo($videoUrl, [
            'format' => 'plain'
        ]);
    }

    /**
     * Get article format transcript with headings
     */
    public function getArticleTranscript($videoUrl, $withHeadings = true)
    {
        return $this->transcribeVideo($videoUrl, [
            'format' => 'article',
            'headings' => $withHeadings,
            'article_mode' => 'clean'
        ]);
    }

    /**
     * Get structured transcript with metadata
     */
    public function getStructuredTranscript($videoUrl)
    {
        return $this->transcribeVideo($videoUrl, [
            'format' => 'json',
            'meta' => true
        ]);
    }

    /**
     * Get SRT format transcript
     */
    public function getSRTTranscript($videoUrl)
    {
        return $this->transcribeVideo($videoUrl, [
            'format' => 'srt'
        ]);
    }

    /**
     * Extract video ID from URL
     */
    public function extractVideoId($url)
    {
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/';
        preg_match($pattern, $url, $matches);
        return $matches[1] ?? null;
    }

    /**
     * Get supported formats
     */
    public function getSupportedFormats()
    {
        return $this->transcriberService->getSupportedFormats();
    }

    /**
     * Validate format
     */
    public function validateFormat($format)
    {
        return $this->transcriberService->validateFormat($format);
    }

    /**
     * Transcribe audio/video file by uploading directly to transcriber service
     * 
     * @param string $filePath Full path to the audio/video file
     * @param array $options Transcription options
     *   - format: 'plain', 'json', 'srt', 'article', 'bundle' (default: 'bundle')
     *   - lang: Language code or 'auto' (default: 'auto')
     *   - include_meta: Include metadata (default: true for bundle format)
     * @return array Transcription result
     */
    public function transcribeFile($filePath, $options = [])
    {
        try {
            Log::info("TranscriberModule: Starting file transcription", [
                'file_path' => $filePath,
                'options' => $options
            ]);

            $result = $this->transcriberService->transcribeFileUpload($filePath, $options);
            
            if (!$result['success']) {
                $errorMessage = "Transcription failed: " . ($result['error'] ?? 'Unknown error');
                
                // Include error details if available
                if (isset($result['error_details'])) {
                    $errorDetails = $result['error_details'];
                    $errorMessage .= " | Details: " . json_encode($errorDetails);
                    
                    Log::error("TranscriberModule: File transcription failed with details", [
                        'file_path' => $filePath,
                        'error' => $result['error'] ?? 'Unknown error',
                        'error_details' => $errorDetails
                    ]);
                }
                
                throw new \Exception($errorMessage);
            }

            // Check if transcript content is actually available
            // Prioritize article_text for bundle format
            $subtitleText = $result['article_text'] ?? $result['subtitle_text'] ?? '';
            
            Log::info("TranscriberModule: Extracted transcript content", [
                'file_path' => $filePath,
                'has_article_text' => isset($result['article_text']),
                'has_subtitle_text' => isset($result['subtitle_text']),
                'article_text_length' => isset($result['article_text']) ? strlen($result['article_text']) : 0,
                'subtitle_text_length' => isset($result['subtitle_text']) ? strlen($result['subtitle_text'] ?? '') : 0,
                'final_transcript_length' => strlen($subtitleText),
                'result_keys' => array_keys($result)
            ]);
            
            if (empty(trim($subtitleText))) {
                // Extract job_key from error_details if available (for timeout cases)
                $jobKey = $result['error_details']['job_key'] ?? null;
                $errorMessage = "No transcript content available from file. The transcriber service returned empty content.";
                
                if ($jobKey) {
                    $errorMessage .= " The transcription job may still be processing. Job key: {$jobKey}";
                }
                
                Log::warning("TranscriberModule: Transcription succeeded but no content available", [
                    'file_path' => $filePath,
                    'result_keys' => array_keys($result),
                    'article_text_empty' => empty($result['article_text'] ?? ''),
                    'subtitle_text_empty' => empty($result['subtitle_text'] ?? ''),
                    'has_json_items' => isset($result['json_items']),
                    'has_transcript_json' => isset($result['transcript_json']),
                    'job_key' => $jobKey,
                    'video_id' => $result['video_id'] ?? null,
                    'format' => $result['format'] ?? null
                ]);
                
                throw new \Exception($errorMessage);
            }

            Log::info("TranscriberModule: File transcription completed successfully", [
                'file_path' => $filePath,
                'format' => $result['format'] ?? 'unknown',
                'language' => $result['language'] ?? 'unknown',
                'content_length' => strlen($subtitleText)
            ]);

            $returnData = [
                'success' => true,
                'video_id' => $result['video_id'] ?? null,
                'language' => $result['language'] ?? null,
                'format' => $result['format'] ?? 'bundle',
                'transcript' => $subtitleText,
                'metadata' => $result['meta'] ?? null
            ];
            
            // Include bundle data if available (for bundle format)
            if (isset($result['article_text'])) {
                $returnData['article_text'] = $result['article_text'];
            }
            if (isset($result['json_items'])) {
                $returnData['json_items'] = $result['json_items'];
            }
            if (isset($result['transcript_json'])) {
                $returnData['transcript_json'] = $result['transcript_json'];
            }
            
            return $returnData;

        } catch (\Exception $e) {
            Log::error("TranscriberModule: File transcription failed", [
                'error' => $e->getMessage(),
                'file_path' => $filePath
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'video_id' => null,
                'transcript' => null
            ];
        }
    }

    /**
     * Extract content from web URL
     * 
     * @param string $url Web URL to scrape
     * @return array Extracted content
     */
    public function scrapeWebContent(string $url)
    {
        try {
            Log::info('TranscriberModule: Scraping web content', [
                'url' => $url
            ]);

            $result = $this->webScrapingService->extractContent($url);

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Web scraping failed');
            }

            Log::info('TranscriberModule: Web content scraped successfully', [
                'url' => $url,
                'content_length' => strlen($result['content'] ?? '')
            ]);

            return [
                'success' => true,
                'content' => $result['content'] ?? '',
                'title' => $result['title'] ?? null,
                'metadata' => $result['metadata'] ?? []
            ];

        } catch (\Exception $e) {
            Log::error('TranscriberModule: Web scraping failed', [
                'error' => $e->getMessage(),
                'url' => $url
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'content' => null
            ];
        }
    }

    /**
     * Get supported transcription formats
     * 
     * @return array List of supported formats
     */
    public function getSupportedTranscriptionFormats()
    {
        return $this->transcriberService->getSupportedFormats();
    }

    /**
     * Check if the transcriber microservice is available
     * 
     * @return bool True if service is available
     */
    public function isAvailable()
    {
        try {
            $apiUrl = config('services.youtube_transcriber.url');
            return !empty($apiUrl);
        } catch (\Exception $e) {
            return false;
        }
    }
}





