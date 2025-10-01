<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Services\YouTubeService;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use App\Models\Tool;
use App\Models\History;

class YoutubeController extends Controller
{
    private $youtubeService;
    private $openAIService;

    public function __construct(YouTubeService $youtubeService, OpenAIService $openAIService)
    {
        $this->youtubeService = $youtubeService;
        $this->openAIService = $openAIService;
    }

    public function summarize(Request $request)
    {
        $request->validate([
            'video_url' => 'required|url',
            'language'  => 'nullable|string',
            'mode'      => 'nullable|string',
        ]);

        $user = $request->user();
        $tool = Tool::where('slug', 'youtube')->first();

        try {
            // Extract video ID from URL
            $videoId = $this->youtubeService->extractVideoId($request->video_url);
            
            if (!$videoId) {
                return response()->json(['error' => 'Invalid YouTube URL'], 400);
            }

            // Get video details from YouTube API
            $videoData = $this->youtubeService->getVideoDetails($videoId);
            
            if (!$videoData) {
                return response()->json(['error' => 'Video not found or unavailable'], 404);
            }

            // Create summary using OpenAI
            $summary = $this->createSummary($videoData, $request->language, $request->mode);

            // Log usage
            if ($tool) {
                History::create([
                    'user_id' => $user->id,
                    'tool_id' => $tool->id,
                    'input'   => $request->video_url,
                    'output'  => $summary,
                    'meta'    => json_encode([
                        'video_id' => $videoId,
                        'video_title' => $videoData['title'],
                        'language' => $request->language,
                        'mode' => $request->mode,
                    ]),
                ]);
            }

            return response()->json([
                'summary' => $summary,
                'video_info' => [
                    'title' => $videoData['title'],
                    'channel' => $videoData['channel_title'],
                    'duration' => $videoData['duration'],
                    'views' => $videoData['view_count'],
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('YouTube Summary Error: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to process video at this time'], 500);
        }
    }

    private function createSummary($videoData, $language = 'en', $mode = 'detailed')
    {
        $prompt = $this->buildPrompt($videoData, $language, $mode);
        return $this->openAIService->generateResponse($prompt);
    }

    private function buildPrompt($videoData, $language, $mode)
    {
        $basePrompt = "Analyze this YouTube video and provide a comprehensive summary:

Title: {$videoData['title']}
Description: {$videoData['description']}
Channel: {$videoData['channel_title']}
Duration: {$videoData['duration']}
Views: {$videoData['view_count']}
Published: {$videoData['published_at']}
Tags: " . implode(', ', $videoData['tags']) . "

Please provide:";

        if ($mode === 'brief') {
            $basePrompt .= "\n1. Main topic (1 sentence)\n2. Key takeaway (1 sentence)";
        } else {
            $basePrompt .= "\n1. Main topic and themes\n2. Key points (5-7 bullet points)\n3. Target audience\n4. Educational value\n5. Overall rating (1-10)";
        }

        if ($language !== 'en') {
            $basePrompt .= "\n\nPlease respond in {$language} language.";
        }

        return $basePrompt;
    }
}