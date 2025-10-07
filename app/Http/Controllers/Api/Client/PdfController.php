<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Services\Modules\UnifiedProcessingService;
use App\Services\Modules\ModuleRegistry;
use Illuminate\Http\Request;
use App\Models\Tool;
use App\Models\History;
use Illuminate\Support\Facades\Log;

class PdfController extends Controller
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
            'file_path' => 'required|string',
            'language'  => 'nullable|string',
            'mode'      => 'nullable|string',
        ]);

        $user = $request->user();
        $tool = Tool::where('slug', 'pdf')->first();

        try {
            // Use unified processing service
            $result = $this->unifiedProcessingService->processPDFDocument(
                $request->file_path,
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
                    'input'   => $request->file_path,
                    'output'  => $result['summary'],
                    'meta'    => json_encode([
                        'file_path' => $request->file_path,
                        'language' => $request->language,
                        'mode' => $request->mode,
                        'processing_method' => $result['metadata']['processing_method'],
                        'chunks_processed' => $result['metadata']['chunks_processed'],
                        'total_characters' => $result['metadata']['total_characters'],
                        'total_words' => $result['metadata']['total_words'],
                    ]),
                ]);
            }

            return response()->json([
                'summary' => $result['summary'],
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
            Log::error('PDF Summary Error: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to process PDF at this time'], 500);
        }
    }
}