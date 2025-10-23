<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\AIManagerService;

class YouTubeFallbackService
{
    private $aiManager;

    public function __construct()
    {
        $this->aiManager = new AIManagerService();
    }

    /**
     * Process YouTube video without transcriber dependency
     */
    public function processYouTubeVideo($videoUrl, $options = [], $userId = null)
    {
        try {
            Log::info("YouTube Fallback Processing", [
                'video_url' => $videoUrl,
                'user_id' => $userId
            ]);

            // Extract video ID from URL
            $videoId = $this->extractVideoId($videoUrl);
            if (!$videoId) {
                return [
                    'success' => false,
                    'error' => 'Invalid YouTube URL'
                ];
            }

            // Get video information (title, description, etc.)
            $videoInfo = $this->getVideoInfo($videoId);
            if (!$videoInfo['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to get video information: ' . $videoInfo['error']
                ];
            }

            // Create content for AI processing
            $content = $this->createContentForAI($videoInfo['data']);
            
            // Process with AI Manager
            $aiResult = $this->aiManager->processText($content, 'summarize', [
                'max_length' => 300,
                'include_key_points' => true
            ]);

            if (!$aiResult['success']) {
                return [
                    'success' => false,
                    'error' => 'AI processing failed: ' . $aiResult['error']
                ];
            }

            // Create result structure
            $result = [
                'success' => true,
                'video_id' => $videoId,
                'language' => 'en',
                'format' => 'bundle',
                'article' => $content,
                'summary' => $aiResult['insights'] ?? 'No summary available',
                'meta' => [
                    'title' => $videoInfo['data']['title'] ?? 'Unknown',
                    'channel' => $videoInfo['data']['channel'] ?? 'Unknown',
                    'duration' => $videoInfo['data']['duration'] ?? 'Unknown',
                    'views' => $videoInfo['data']['views'] ?? 0,
                    'description' => $videoInfo['data']['description'] ?? '',
                    'ai_summary' => $aiResult['insights'] ?? '',
                    'ai_model_used' => $aiResult['model_used'] ?? 'ollama:phi3:mini',
                    'ai_tokens_used' => $aiResult['tokens_used'] ?? 0,
                    'processing_method' => 'youtube_fallback_ai_manager'
                ],
                'json' => [
                    'segments' => [
                        [
                            'start' => 0,
                            'end' => 0,
                            'text' => $content
                        ]
                    ]
                ]
            ];

            Log::info("YouTube Fallback Processing completed", [
                'video_id' => $videoId,
                'content_length' => strlen($content),
                'ai_success' => true
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error("YouTube Fallback Processing failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'YouTube processing failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Extract video ID from YouTube URL
     */
    private function extractVideoId($url)
    {
        $pattern = '/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Get video information (mock implementation)
     */
    private function getVideoInfo($videoId)
    {
        try {
            // This would normally call YouTube API or web scraping
            // For now, return mock data
            return [
                'success' => true,
                'data' => [
                    'title' => 'Sample YouTube Video',
                    'channel' => 'Sample Channel',
                    'duration' => '5:30',
                    'views' => 1000,
                    'description' => 'This is a sample video description that contains information about the video content. It discusses various topics and provides context for the video content.'
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create content for AI processing
     */
    private function createContentForAI($videoInfo)
    {
        $content = $videoInfo['title'] . "\n\n";
        $content .= "Channel: " . $videoInfo['channel'] . "\n";
        $content .= "Duration: " . $videoInfo['duration'] . "\n";
        $content .= "Views: " . $videoInfo['views'] . "\n\n";
        $content .= "Description: " . $videoInfo['description'];
        
        return $content;
    }
}

