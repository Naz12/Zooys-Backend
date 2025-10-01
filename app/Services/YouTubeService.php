<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeService
{
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
}
