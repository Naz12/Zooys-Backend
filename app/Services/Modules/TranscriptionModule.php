<?php

namespace App\Services\Modules;

use App\Services\YouTubeTranscriberService;
use Illuminate\Support\Facades\Log;

class TranscriptionModule
{
    private $transcriberService;

    public function __construct(YouTubeTranscriberService $transcriberService)
    {
        $this->transcriberService = $transcriberService;
    }

    /**
     * Main transcription method
     */
    public function transcribeVideo($videoUrl, $options = [])
    {
        try {
            Log::info("TranscriptionModule: Starting video transcription", [
                'video_url' => $videoUrl,
                'options' => $options
            ]);

            $result = $this->transcriberService->transcribe($videoUrl, $options);
            
            if (!$result['success']) {
                throw new \Exception("Transcription failed: " . ($result['error'] ?? 'Unknown error'));
            }

            Log::info("TranscriptionModule: Transcription completed successfully", [
                'video_id' => $result['video_id'],
                'language' => $result['language'],
                'format' => $result['format'],
                'content_length' => strlen($result['subtitle_text'])
            ]);

            return [
                'success' => true,
                'video_id' => $result['video_id'],
                'language' => $result['language'],
                'format' => $result['format'],
                'transcript' => $result['subtitle_text'],
                'metadata' => $result['meta'] ?? null
            ];

        } catch (\Exception $e) {
            Log::error("TranscriptionModule: Transcription failed", [
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
}





