<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Services\Modules\UnifiedProcessingService;
use App\Services\Modules\ModuleRegistry;
use Illuminate\Http\Request;
use App\Models\Tool;
use App\Models\History;
use Illuminate\Support\Facades\Log;

class YoutubeController extends Controller
{
    private $unifiedProcessingService;
    private $moduleRegistry;

    public function __construct(
        UnifiedProcessingService $unifiedProcessingService,
        ModuleRegistry $moduleRegistry
    ) {
        $this->unifiedProcessingService = $unifiedProcessingService;
        $this->moduleRegistry = $moduleRegistry;
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
            // Use unified processing service
            $result = $this->unifiedProcessingService->processYouTubeVideo(
                $request->video_url,
                [
                    'language' => $request->language ?? 'en',
                    'mode' => $request->mode ?? 'detailed',
                ]
            );

            if (!$result['success']) {
                return response()->json(['error' => $result['error']], 500);
            }

            // Log usage
            if ($tool) {
                History::create([
                    'user_id' => $user->id,
                    'tool_id' => $tool->id,
                    'input'   => $request->video_url,
                    'output'  => $result['summary'],
                    'meta'    => json_encode([
                        'video_id' => $result['metadata']['video_id'] ?? null,
                        'video_title' => $result['metadata']['title'] ?? null,
                        'language' => $request->language,
                        'mode' => $request->mode,
                        'processing_method' => $result['metadata']['processing_method'],
                        'chunks_processed' => $result['metadata']['chunks_processed'],
                    ]),
                ]);
            }

            return response()->json([
                'summary' => $result['summary'],
                'video_info' => [
                    'title' => $result['metadata']['title'] ?? 'Unknown',
                    'channel' => $result['metadata']['channel'] ?? 'Unknown',
                    'duration' => $result['metadata']['duration'] ?? 'Unknown',
                    'views' => $result['metadata']['views'] ?? 0,
                ],
                'captions_info' => [
                    'has_captions' => $result['metadata']['has_transcript'] ?? false,
                    'caption_length' => $result['metadata']['total_characters'] ?? 0,
                    'caption_words' => $result['metadata']['total_words'] ?? 0
                ],
                'processing_info' => [
                    'method' => $result['metadata']['processing_method'],
                    'chunks_processed' => $result['metadata']['chunks_processed'],
                    'total_characters' => $result['metadata']['total_characters'],
                    'total_words' => $result['metadata']['total_words'],
                ],
                'ai_result' => [
                    'id' => $result['ai_result']->id,
                    'title' => $result['ai_result']->title,
                    'file_url' => $result['ai_result']->file_url,
                    'created_at' => $result['ai_result']->created_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('YouTube Summary Error: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to process video at this time'], 500);
        }
    }

}