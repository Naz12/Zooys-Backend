<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Services\AIPresentationService;
use App\Services\Modules\UniversalFileManagementModule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PresentationController extends Controller
{
    private $aiPresentationService;
    private $universalFileModule;

    public function __construct(
        AIPresentationService $aiPresentationService,
        UniversalFileManagementModule $universalFileModule
    ) {
        $this->aiPresentationService = $aiPresentationService;
        $this->universalFileModule = $universalFileModule;
    }

    /**
     * Generate presentation outline from user input
     */
    public function generateOutline(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'input_type' => 'required|in:text,file,url,youtube',
                'topic' => 'required_if:input_type,text|string|max:5000',
                'language' => 'string|in:English,Spanish,French,German,Italian,Portuguese,Chinese,Japanese',
                'tone' => 'string|in:Professional,Casual,Academic,Creative,Formal',
                'length' => 'string|in:Short,Medium,Long',
                'model' => 'string|in:Basic Model,Advanced Model,Premium Model,gpt-3.5-turbo,gpt-4,gpt-4o,deepseek-chat,ollama:mistral,ollama:llama3,ollama:phi3:mini',
                'file_id' => 'required_if:input_type,file|string|exists:file_uploads,id',
                'url' => 'required_if:input_type,url|url',
                'youtube_url' => 'required_if:input_type,youtube|url'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $inputData = $request->all();
            $userId = auth()->id() ?? 5; // Use existing user ID for public access

            // Handle file-based presentations using Universal File Management
            if ($inputData['input_type'] === 'file' && $request->has('file_id')) {
                $fileId = $request->input('file_id');
                
                // Use Universal File Management Module to get file
                $universalFileModule = app(\App\Services\Modules\UniversalFileManagementModule::class);
                $fileResult = $universalFileModule->getFile($fileId);
                
                if (!$fileResult['success']) {
                    return response()->json([
                        'success' => false,
                        'error' => 'File not found: ' . $fileResult['error']
                    ], 404);
                }
                
                $inputData['file_path'] = $fileResult['file']['file_path'];
                $inputData['file_type'] = $fileResult['file']['file_type'];
            }

            // Generate presentation outline
            $result = $this->aiPresentationService->generateOutline($inputData, $userId);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data']
            ]);

        } catch (\Exception $e) {
            Log::error('Presentation outline generation failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate presentation outline'
            ], 500);
        }
    }

    /**
     * Generate full presentation content from outline
     */
    public function generateContent(Request $request, $aiResultId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'language' => 'string|in:English,Spanish,French,German,Italian,Portuguese,Chinese,Japanese',
                'tone' => 'string|in:Professional,Casual,Academic,Creative,Formal',
                'detail_level' => 'string|in:brief,detailed,comprehensive'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $userId = auth()->id() ?? 5; // Use default user ID for public access
            $language = $request->input('language', 'English');
            $tone = $request->input('tone', 'Professional');
            $detailLevel = $request->input('detail_level', 'detailed');

            // Generate full content using microservice
            $result = $this->aiPresentationService->generateFullContent(
                $aiResultId,
                $userId,
                $language,
                $tone,
                $detailLevel
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data'],
                    'message' => $result['message']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Content generation failed', [
                'error' => $e->getMessage(),
                'ai_result_id' => $aiResultId,
                'user_id' => auth()->id() ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate content: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update presentation outline with user modifications
     */
    public function updateOutline(Request $request, $aiResultId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'outline' => 'required|array',
                'outline.title' => 'required|string|max:255',
                'outline.slides' => 'required|array|min:1',
                'outline.slides.*.slide_number' => 'required|integer|min:1',
                'outline.slides.*.header' => 'required|string|max:255',
                'outline.slides.*.subheaders' => 'required|array',
                'outline.slides.*.subheaders.*' => 'string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $userId = auth()->id();
            $updatedOutline = $request->input('outline');

            $result = $this->aiPresentationService->updateOutline($aiResultId, $updatedOutline, $userId);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data']
            ]);

        } catch (\Exception $e) {
            Log::error('Presentation outline update failed', [
                'error' => $e->getMessage(),
                'ai_result_id' => $aiResultId,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update presentation outline'
            ], 500);
        }
    }

    /**
     * Get available presentation templates
     */
    public function getTemplates(): JsonResponse
    {
        try {
            $templates = $this->aiPresentationService->getAvailableTemplates();

            return response()->json([
                'success' => true,
                'data' => [
                    'templates' => $templates
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get presentation templates', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get templates'
            ], 500);
        }
    }


    /**
     * Generate PowerPoint presentation
     */
    public function generatePowerPoint(Request $request, $aiResultId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'template' => 'required|string',
                'color_scheme' => 'string|in:blue,white,colorful,gray,dark',
                'font_style' => 'string|in:modern,classic,minimalist,creative'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $userId = auth()->id();
            $templateData = $request->only(['template', 'color_scheme', 'font_style']);

            Log::info('PowerPoint generation request', [
                'request_data' => $request->all(),
                'template_data' => $templateData,
                'ai_result_id' => $aiResultId,
                'user_id' => $userId
            ]);

            // Use microservice for PowerPoint generation
            if (!$this->aiPresentationService->isMicroserviceAvailable()) {
                return response()->json([
                    'success' => false,
                    'error' => 'PowerPoint generation service is currently unavailable. Please try again later.'
                ], 503);
            }
            
            $result = $this->aiPresentationService->generatePowerPointWithMicroservice($aiResultId, $templateData, $userId);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data']
            ]);

        } catch (\Exception $e) {
            Log::error('PowerPoint generation failed', [
                'error' => $e->getMessage(),
                'ai_result_id' => $aiResultId,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate PowerPoint'
            ], 500);
        }
    }

    /**
     * Get user's presentations
     */
    public function getPresentations(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id() ?? 5; // Use public user ID for public access
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');

            $query = \App\Models\AIResult::where('user_id', $userId)
                ->where('tool_type', 'presentation')
                ->orderBy('created_at', 'desc');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $presentations = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'presentations' => $presentations->items(),
                    'pagination' => [
                        'current_page' => $presentations->currentPage(),
                        'last_page' => $presentations->lastPage(),
                        'per_page' => $presentations->perPage(),
                        'total' => $presentations->total()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get presentations', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id() ?? 5
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get presentations'
            ], 500);
        }
    }

    /**
     * Get specific presentation
     */
    public function getPresentation($aiResultId): JsonResponse
    {
        try {
            $userId = auth()->id() ?? 5; // Use public user ID for public access

            $presentation = \App\Models\AIResult::where('id', $aiResultId)
                ->where('user_id', $userId)
                ->where('tool_type', 'presentation')
                ->first();

            if (!$presentation) {
                return response()->json([
                    'success' => false,
                    'error' => 'Presentation not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'presentation' => $presentation
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get presentation', [
                'error' => $e->getMessage(),
                'ai_result_id' => $aiResultId,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get presentation'
            ], 500);
        }
    }

    /**
     * Delete presentation
     */
    public function deletePresentation($aiResultId): JsonResponse
    {
        try {
            $userId = auth()->id() ?? 5; // Use public user ID for public access

            $presentation = \App\Models\AIResult::where('id', $aiResultId)
                ->where('user_id', $userId)
                ->where('tool_type', 'presentation')
                ->first();

            if (!$presentation) {
                return response()->json([
                    'success' => false,
                    'error' => 'Presentation not found'
                ], 404);
            }

            // Delete associated PowerPoint file if exists
            if (isset($presentation->result_data['powerpoint_file'])) {
                $filePath = $presentation->result_data['powerpoint_file'];
                $fullFilePath = storage_path('app/' . $filePath);
                
                // Use direct file operations since Storage facade has path issues
                if (file_exists($fullFilePath)) {
                    if (unlink($fullFilePath)) {
                        Log::info('PowerPoint file deleted successfully', [
                            'file_path' => $fullFilePath,
                            'ai_result_id' => $aiResultId
                        ]);
                    } else {
                        Log::warning('Failed to delete PowerPoint file', [
                            'file_path' => $fullFilePath,
                            'ai_result_id' => $aiResultId
                        ]);
                    }
                } else {
                    Log::info('PowerPoint file not found (may have been deleted already)', [
                        'file_path' => $fullFilePath,
                        'ai_result_id' => $aiResultId
                    ]);
                }
            }

            $presentation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Presentation deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete presentation', [
                'error' => $e->getMessage(),
                'ai_result_id' => $aiResultId,
                'user_id' => auth()->id() ?? 5
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete presentation'
            ], 500);
        }
    }

    /**
     * Save presentation data (JSON) for frontend editing
     */
    public function savePresentation(Request $request, $aiResultId)
    {
        $userId = auth()->id();

        $validator = Validator::make($request->all(), [
            'presentation_data' => 'required|array',
            'presentation_data.title' => 'required|string|max:255',
            'presentation_data.slides' => 'required|array|min:1',
            'presentation_data.template' => 'string',
            'presentation_data.color_scheme' => 'string',
            'presentation_data.font_style' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid presentation data',
                'details' => $validator->errors()
            ], 400);
        }

        $result = $this->aiPresentationService->savePresentationData(
            $aiResultId,
            $request->presentation_data,
            $userId
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data'] ?? null,
            'message' => $result['message'] ?? 'Presentation saved successfully'
        ]);
    }

    /**
     * Get presentation data for frontend editing
     */
    public function getPresentationData($aiResultId)
    {
        $userId = auth()->id() ?? 5; // Use public user ID for public access

        $result = $this->aiPresentationService->getPresentationData($aiResultId, $userId);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'metadata' => $result['metadata']
        ]);
    }

    /**
     * Download PowerPoint presentation file
     */
    public function downloadPresentation($filename)
    {
        try {
            // Construct the file path
            $filePath = storage_path('app/presentations/' . $filename);
            
            // Check if file exists
            if (!file_exists($filePath)) {
                Log::error('Download file not found', [
                    'filename' => $filename,
                    'file_path' => $filePath
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'File not found'
                ], 404)->header('Access-Control-Allow-Origin', '*')
                  ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                  ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            }

            // Get file size
            $fileSize = filesize($filePath);
            
            Log::info('Downloading presentation file', [
                'filename' => $filename,
                'file_size' => $fileSize,
                'file_path' => $filePath
            ]);

            // Return file download response with proper headers
            return response()->download($filePath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length' => $fileSize,
                'Cache-Control' => 'no-cache, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With'
            ]);

        } catch (\Exception $e) {
            Log::error('Download failed', [
                'filename' => $filename,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Download failed: ' . $e->getMessage()
            ], 500)->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        }
    }

    /**
     * Export presentation to PowerPoint (on-demand)
     */
    public function exportPresentation(Request $request, $aiResultId)
    {
        $userId = auth()->id() ?? 1; // Use default user ID for public access

        // Get the AI result to check last updated timestamp
        $aiResult = \App\Models\AIResult::where('id', $aiResultId)
            ->where('tool_type', 'presentation')
            ->first();
        
        if (!$aiResult) {
            return response()->json([
                'success' => false,
                'error' => 'Presentation not found'
            ], 404)->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        }
        
        // Create cache keys that include the last updated timestamp
        $lastUpdated = $aiResult->updated_at->timestamp;
        $lockKey = "export_generation_{$aiResultId}_{$userId}_{$lastUpdated}";
        $cacheKey = "export_result_{$aiResultId}_{$userId}_{$lastUpdated}";
        
        // Check if already processing
        if (\Illuminate\Support\Facades\Cache::has($lockKey)) {
            Log::info('Export generation already in progress', [
                'ai_result_id' => $aiResultId,
                'user_id' => $userId,
                'lock_key' => $lockKey
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Export generation already in progress',
                'status' => 'processing'
            ], 409)->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        }
        
        // Check if result already exists and is recent (within 10 minutes)
        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            $cachedResult = \Illuminate\Support\Facades\Cache::get($cacheKey);
            Log::info('Returning cached export result', [
                'ai_result_id' => $aiResultId,
                'user_id' => $userId,
                'cached_at' => $cachedResult['cached_at'] ?? 'unknown',
                'last_updated' => $lastUpdated
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $cachedResult['data'],
                'message' => $cachedResult['message'] ?? 'Presentation exported successfully (cached)',
                'cached' => true,
                'cached_at' => $cachedResult['cached_at']
            ])->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        }

        $validator = Validator::make($request->all(), [
            'presentation_data' => 'required|array',
            'template' => 'string',
            'color_scheme' => 'string',
            'font_style' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid export data',
                'details' => $validator->errors()
            ], 400)->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        }

        $templateData = null;
        if ($request->has(['template', 'color_scheme', 'font_style'])) {
            $templateData = [
                'template' => $request->template,
                'color_scheme' => $request->color_scheme,
                'font_style' => $request->font_style
            ];
        }

        // Set processing lock (expires in 15 minutes)
        \Illuminate\Support\Facades\Cache::put($lockKey, true, 900);
        
        try {
            $result = $this->aiPresentationService->exportPresentationToPowerPoint(
                $aiResultId,
                $request->presentation_data,
                $userId,
                $templateData
            );
            
            // Cache successful result for 10 minutes
            if ($result['success']) {
                \Illuminate\Support\Facades\Cache::put($cacheKey, [
                    'data' => $result['data'],
                    'message' => $result['message'] ?? 'Presentation exported successfully',
                    'cached_at' => now()->toISOString()
                ], 600);
            }
            
            return $result;
        } finally {
            // Always remove the lock
            \Illuminate\Support\Facades\Cache::forget($lockKey);
        }
    }

    /**
     * Get progress status for a presentation operation
     */
    public function getProgressStatus(Request $request, $aiResultId): JsonResponse
    {
        try {
            $operationId = $request->query('operation_id');
            
            if (!$operationId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Operation ID is required'
                ], 400);
            }

            // Call microservice to get progress
            $microserviceUrl = config('services.presentation_microservice.url', 'http://localhost:8001');
            $response = $this->aiPresentationService->callMicroservicePublic(
                $microserviceUrl . '/progress/' . $operationId,
                []
            );

            if ($response['success']) {
                $progressData = $response['data'];
                $progressData['ai_result_id'] = $aiResultId;
                $progressData['operation_id'] = $operationId;

                return response()->json(new \App\Http\Resources\PresentationProgressResource($progressData));
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $response['error'] ?? 'Failed to get progress status'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Progress status check failed', [
                'error' => $e->getMessage(),
                'ai_result_id' => $aiResultId,
                'operation_id' => $request->query('operation_id')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get progress status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check microservice availability
     */
    public function checkMicroserviceStatus()
    {
        $isAvailable = $this->aiPresentationService->isMicroserviceAvailable();

        return response()->json([
            'success' => true,
            'data' => [
                'microservice_available' => $isAvailable,
                'status' => $isAvailable ? 'online' : 'offline'
            ]
        ]);
    }
}
