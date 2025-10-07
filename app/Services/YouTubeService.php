<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\PythonYouTubeService;

class YouTubeService
{
    private $pythonService;

    public function __construct()
    {
        $this->pythonService = new PythonYouTubeService();
    }
    public function getVideoDetails($videoId)
    {
        $apiKey = config('services.youtube.api_key');
        $url = "https://www.googleapis.com/youtube/v3/videos";
        
        try {
            $response = Http::get($url, [
                'part' => 'snippet,contentDetails,statistics',
                'id' => $videoId,
                'key' => $apiKey
            ]);

            if ($response->failed()) {
                Log::error('YouTube API Error: ' . $response->body());
                return null;
            }

            $data = $response->json();
            
            if (empty($data['items'])) {
                return null;
            }

            $video = $data['items'][0];
            $snippet = $video['snippet'];
            $statistics = $video['statistics'];
            $contentDetails = $video['contentDetails'];

            return [
                'id' => $videoId,
                'title' => $snippet['title'],
                'description' => $snippet['description'],
                'channel_title' => $snippet['channelTitle'],
                'published_at' => $snippet['publishedAt'],
                'duration' => $contentDetails['duration'],
                'view_count' => $statistics['viewCount'] ?? 0,
                'like_count' => $statistics['likeCount'] ?? 0,
                'comment_count' => $statistics['commentCount'] ?? 0,
                'tags' => $snippet['tags'] ?? [],
                'category_id' => $snippet['categoryId'],
                'thumbnail' => $snippet['thumbnails']['high']['url'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('YouTube API Error: ' . $e->getMessage());
            return null;
        }
    }

    public function extractVideoId($url)
    {
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/';
        preg_match($pattern, $url, $matches);
        return $matches[1] ?? null;
    }

    /**
     * Get video captions using YouTube API
     */
    public function getVideoCaptions($videoId)
    {
        $apiKey = config('services.youtube.api_key');
        $url = "https://www.googleapis.com/youtube/v3/captions";
        
        try {
            // First, get caption tracks
            $response = Http::get($url, [
                'part' => 'snippet',
                'videoId' => $videoId,
                'key' => $apiKey
            ]);

            if ($response->failed()) {
                Log::error('YouTube Captions API Error: ' . $response->body());
                return null;
            }

            $data = $response->json();
            
            if (empty($data['items'])) {
                Log::info('No captions found for video: ' . $videoId);
                return null;
            }

            // Find the best caption track (prefer auto-generated English)
            $captionTrack = $this->findBestCaptionTrack($data['items']);
            
            if (!$captionTrack) {
                Log::info('No suitable caption track found for video: ' . $videoId);
                return null;
            }

            // Download the caption content
            return $this->downloadCaptionContent($captionTrack['id'], $apiKey);

        } catch (\Exception $e) {
            Log::error('YouTube Captions Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Find the best caption track
     */
    private function findBestCaptionTrack($captionTracks)
    {
        // Prefer auto-generated English captions
        foreach ($captionTracks as $track) {
            if ($track['snippet']['language'] === 'en' && 
                $track['snippet']['trackKind'] === 'asr') {
                return $track;
            }
        }

        // Fallback to any English captions
        foreach ($captionTracks as $track) {
            if ($track['snippet']['language'] === 'en') {
                return $track;
            }
        }

        // Fallback to any available captions
        return $captionTracks[0] ?? null;
    }

    /**
     * Download caption content
     */
    private function downloadCaptionContent($captionId, $apiKey)
    {
        try {
            $url = "https://www.googleapis.com/youtube/v3/captions/{$captionId}";
            
            $response = Http::get($url, [
                'key' => $apiKey,
                'tfmt' => 'srt' // Request SRT format
            ]);

            if ($response->failed()) {
                Log::error('Caption download failed: ' . $response->body());
                return null;
            }

            return $this->parseSRTContent($response->body());

        } catch (\Exception $e) {
            Log::error('Caption download error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse SRT content to extract text
     */
    private function parseSRTContent($srtContent)
    {
        $lines = explode("\n", $srtContent);
        $text = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines, numbers, and timestamps
            if (empty($line) || 
                is_numeric($line) || 
                preg_match('/^\d{2}:\d{2}:\d{2},\d{3} --> \d{2}:\d{2}:\d{2},\d{3}$/', $line)) {
                continue;
            }
            
            $text .= $line . ' ';
        }
        
        return trim($text);
    }

    /**
     * Web scraping fallback for YouTube captions
     */
    public function getVideoCaptionsByScraping($videoId)
    {
        try {
            $url = "https://www.youtube.com/watch?v={$videoId}";
            
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ])->get($url);

            if ($response->failed()) {
                Log::error('YouTube scraping failed: ' . $response->status());
                return null;
            }

            $html = $response->body();
            
            // Extract captions from HTML (this is a simplified approach)
            // In practice, you might need more sophisticated parsing
            $captions = $this->extractCaptionsFromHTML($html);
            
            return $captions;

        } catch (\Exception $e) {
            Log::error('YouTube scraping error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract captions from HTML content
     */
    private function extractCaptionsFromHTML($html)
    {
        // Look for caption tracks in the HTML
        if (preg_match('/"captions":\s*({.*?})/', $html, $matches)) {
            $captionsData = json_decode($matches[1], true);
            
            if (isset($captionsData['playerCaptionsTracklistRenderer']['captionTracks'])) {
                $tracks = $captionsData['playerCaptionsTracklistRenderer']['captionTracks'];
                
                // Find the best caption track
                foreach ($tracks as $track) {
                    if (isset($track['baseUrl']) && isset($track['languageCode']) && $track['languageCode'] === 'en') {
                        Log::info('Found English caption track: ' . $track['baseUrl']);
                        return $this->downloadCaptionFromUrl($track['baseUrl']);
                    }
                }
                
                // Fallback to any available track
                foreach ($tracks as $track) {
                    if (isset($track['baseUrl'])) {
                        Log::info('Found caption track: ' . $track['baseUrl']);
                        return $this->downloadCaptionFromUrl($track['baseUrl']);
                    }
                }
            }
        }
        
        // Alternative method: Look for transcript data
        if (preg_match('/"transcript":\s*({.*?})/', $html, $matches)) {
            $transcriptData = json_decode($matches[1], true);
            if (isset($transcriptData['transcriptRenderer']['body']['transcriptBodyRenderer']['cueGroups'])) {
                return $this->extractTranscriptFromData($transcriptData);
            }
        }
        
        return null;
    }

    /**
     * Extract transcript from transcript data
     */
    private function extractTranscriptFromData($transcriptData)
    {
        $text = '';
        $cueGroups = $transcriptData['transcriptRenderer']['body']['transcriptBodyRenderer']['cueGroups'];
        
        foreach ($cueGroups as $cueGroup) {
            if (isset($cueGroup['transcriptCueGroupRenderer']['cues'])) {
                foreach ($cueGroup['transcriptCueGroupRenderer']['cues'] as $cue) {
                    if (isset($cue['transcriptCueRenderer']['cue']['simpleText'])) {
                        $text .= $cue['transcriptCueRenderer']['cue']['simpleText'] . ' ';
                    }
                }
            }
        }
        
        return trim($text);
    }

    /**
     * Download caption content from URL
     */
    private function downloadCaptionFromUrl($url)
    {
        try {
            $response = Http::get($url);
            
            if ($response->failed()) {
                return null;
            }
            
            return $this->parseSRTContent($response->body());
            
        } catch (\Exception $e) {
            Log::error('Caption URL download error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get video content with captions (main method)
     */
    public function getVideoContentWithCaptions($videoId)
    {
        // Try Python integration multiple times with different strategies
        $pythonAttempts = [
            ['url' => "https://www.youtube.com/watch?v={$videoId}", 'language' => 'en'],
            ['url' => "https://youtu.be/{$videoId}", 'language' => 'en'],
            ['url' => "https://www.youtube.com/watch?v={$videoId}", 'language' => null],
            ['url' => "https://youtu.be/{$videoId}", 'language' => null],
        ];
        
        foreach ($pythonAttempts as $index => $attempt) {
            $attemptNumber = $index + 1;
            Log::info("Python attempt {$attemptNumber}/4 for video: {$videoId} with URL: {$attempt['url']}");
            
            $pythonResult = $this->pythonService->getVideoTranscript($attempt['url'], $attempt['language']);
            
            if ($pythonResult['success'] && !empty($pythonResult['transcript'])) {
                $wordCount = str_word_count($pythonResult['transcript']);
                Log::info("Python attempt {$attemptNumber} successful! Captions obtained: {$wordCount} words");
                return $pythonResult['transcript'];
            }
            
            Log::warning("Python attempt {$attemptNumber} failed: " . ($pythonResult['error'] ?? 'Unknown error'));
            
            // Add small delay between attempts
            if ($index < count($pythonAttempts) - 1) {
                sleep(1);
            }
        }
        
        Log::info('All Python attempts failed, falling back to web scraping for video: ' . $videoId);
        $captions = $this->getVideoCaptionsByScraping($videoId);
        
        if ($captions) {
            Log::info('Captions obtained via web scraping for video: ' . $videoId);
            return $captions;
        }
        
        Log::info('Web scraping failed, falling back to YouTube API for video: ' . $videoId);
        $captions = $this->getVideoCaptions($videoId);
        
        if ($captions) {
            Log::info('Captions obtained via YouTube API for video: ' . $videoId);
            return $captions;
        }
        
        Log::warning('No captions found for video: ' . $videoId);
        return null;
    }

    /**
     * Test Python integration
     */
    public function testPythonIntegration()
    {
        return $this->pythonService->testIntegration();
    }
}
