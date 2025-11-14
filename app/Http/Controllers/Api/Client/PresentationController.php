<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Services\AIPresentationService;
use App\Services\Modules\UniversalFileManagementModule;
use App\Services\UniversalJobService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PresentationController extends Controller
{
    private $aiPresentationService;
    private $universalFileModule;
    private $universalJobService;

    public function __construct(
        AIPresentationService $aiPresentationService,
        UniversalFileManagementModule $universalFileModule,
        UniversalJobService $universalJobService
    ) {
        $this->aiPresentationService = $aiPresentationService;
        $this->universalFileModule = $universalFileModule;
        $this->universalJobService = $universalJobService;
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

            // Generate presentation outline (async - returns job_id)
            $result = $this->aiPresentationService->generateOutline($inputData, $userId);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'job_id' => $result['job_id'],
                'message' => $result['message'] ?? 'Outline generation job created successfully'
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
     * Accepts outline from frontend (edited or original)
     */
    public function generateContent(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'outline' => 'required|array',
                'outline.title' => 'required|string|max:255',
                'outline.slides' => 'required|array|min:1',
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
            $outline = $request->input('outline');
            $language = $request->input('language', 'English');
            $tone = $request->input('tone', 'Professional');
            $detailLevel = $request->input('detail_level', 'detailed');

            // Generate full content using microservice (async - returns job_id)
            $result = $this->aiPresentationService->generateFullContent(
                $outline,
                $userId,
                $language,
                $tone,
                $detailLevel
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'job_id' => $result['job_id'],
                'message' => $result['message'] ?? 'Content generation job created successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Content generation failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id() ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate content: ' . $e->getMessage() . '. Please try again with the same data.'
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

            // Check if templates are empty or null
            if (empty($templates) || !is_array($templates)) {
                Log::warning('No templates available from service', [
                    'templates_type' => gettype($templates),
                    'templates_count' => is_array($templates) ? count($templates) : 0
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'No templates available. Please try again later.'
                ], 503);
            }

            return response()->json([
                'success' => true,
                'templates' => $templates
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get presentation templates', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get templates: ' . $e->getMessage()
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
     * Export presentation to PowerPoint using Universal Job Scheduler
     * Accepts content + style from frontend
     */
    public function exportPresentation(Request $request): JsonResponse
    {
        try {
            // Accept both 'content' (from content generation) and 'presentation_data' (legacy format)
            $presentationData = $request->input('presentation_data') ?? $request->input('content');
            
            if (!$presentationData) {
                return response()->json([
                    'success' => false,
                    'error' => 'Missing presentation data. Provide either "presentation_data" or "content" field.'
                ], 422);
            }

            // Transform content format if needed (convert string content to array)
            $presentationData = $this->transformPresentationData($presentationData);

            $validator = Validator::make(['presentation_data' => $presentationData], [
                'presentation_data' => 'required|array',
                'presentation_data.title' => 'required|string|max:255',
                'presentation_data.slides' => 'required|array|min:1',
                'template' => 'string',
                'color_scheme' => 'string',
                'font_style' => 'string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid export data',
                    'details' => $validator->errors()
                ], 422);
            }

            $userId = auth()->id() ?? 5;
            
            $templateData = null;
            if ($request->has(['template', 'color_scheme', 'font_style'])) {
                $templateData = [
                    'template' => $request->input('template'),
                    'color_scheme' => $request->input('color_scheme'),
                    'font_style' => $request->input('font_style')
                ];
            }

            $result = $this->aiPresentationService->exportPresentation(
                $presentationData,
                $userId,
                $templateData
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'job_id' => $result['job_id'],
                    'message' => $result['message']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Export failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id() ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Export failed: ' . $e->getMessage() . '. Please try again with the same data.'
            ], 500);
        }
    }

    /**
     * Transform presentation data to ensure compatibility
     * Converts content generation output format to export format
     */
    private function transformPresentationData($data)
    {
        if (!is_array($data) || !isset($data['slides']) || !is_array($data['slides'])) {
            return $data;
        }

        // Transform each slide's content field from string to array if needed
        foreach ($data['slides'] as &$slide) {
            if (!isset($slide['content'])) {
                // If content is missing, create empty array
                $slide['content'] = [];
                continue;
            }

            // If content is already an array, keep it as-is
            if (is_array($slide['content'])) {
                continue;
            }

            // If content is a string, convert to array of bullet points
            if (is_string($slide['content'])) {
                $slide['content'] = $this->convertStringContentToArray($slide['content']);
            }
        }

        return $data;
    }

    /**
     * Convert string content to array of bullet points
     * Handles various formats: newlines, bullet markers, paragraphs
     */
    private function convertStringContentToArray($content)
    {
        if (empty($content)) {
            return [];
        }

        $lines = [];
        
        // Split by double newlines (paragraphs)
        $paragraphs = preg_split('/\n\s*\n/', $content);
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (empty($paragraph)) {
                continue;
            }

            // Check if paragraph contains bullet markers
            if (preg_match('/^[•\-\*]\s+/', $paragraph) || preg_match('/^\d+\.\s+/', $paragraph)) {
                // Already has bullet markers, split by newlines
                $bulletLines = preg_split('/\n/', $paragraph);
                foreach ($bulletLines as $line) {
                    $line = trim($line);
                    if (!empty($line)) {
                        // Remove bullet markers if present
                        $line = preg_replace('/^[•\-\*]\s+/', '', $line);
                        $line = preg_replace('/^\d+\.\s+/', '', $line);
                        $lines[] = $line;
                    }
                }
            } else {
                // No bullet markers, try to split by single newlines
                $singleLines = preg_split('/\n/', $paragraph);
                if (count($singleLines) > 1) {
                    // Multiple lines, treat each as a bullet point
                    foreach ($singleLines as $line) {
                        $line = trim($line);
                        if (!empty($line)) {
                            $lines[] = $line;
                        }
                    }
                } else {
                    // Single paragraph, try to split by sentences if too long
                    $paragraph = trim($paragraph);
                    if (strlen($paragraph) > 200) {
                        // Long paragraph, split by sentences
                        $sentences = preg_split('/(?<=[.!?])\s+/', $paragraph);
                        foreach ($sentences as $sentence) {
                            $sentence = trim($sentence);
                            if (!empty($sentence)) {
                                $lines[] = $sentence;
                            }
                        }
                    } else {
                        // Short paragraph, use as single bullet point
                        $lines[] = $paragraph;
                    }
                }
            }
        }

        // If no lines were created, use the original content as a single item
        if (empty($lines)) {
            $lines[] = $content;
        }

        return $lines;
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

    /**
     * Get all presentation files for the authenticated user
     */
    public function getPresentationFiles(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id() ?? 5;
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');

            $result = $this->aiPresentationService->getUserPresentationFiles($userId, $perPage, $search);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Get presentation files failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id() ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get presentation files'
            ], 500);
        }
    }

    /**
     * Delete a presentation file
     */
    public function deletePresentationFile($fileId): JsonResponse
    {
        try {
            $userId = auth()->id() ?? 5;

            $result = $this->aiPresentationService->deletePresentationFile($fileId, $userId);

            if ($result['success']) {
                return response()->json($result);
            } else {
                return response()->json($result, 404);
            }

        } catch (\Exception $e) {
            Log::error('Delete presentation file failed', [
                'error' => $e->getMessage(),
                'file_id' => $fileId,
                'user_id' => auth()->id() ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete file'
            ], 500);
        }
    }

    /**
     * Get editable content for a presentation file
     */
    public function getFileContent($fileId): JsonResponse
    {
        try {
            $userId = auth()->id() ?? 5;

            $result = $this->aiPresentationService->getPresentationFileContent($fileId, $userId);

            if ($result['success']) {
                return response()->json($result);
            } else {
                // Use 400 for missing content data (file exists but no content), 404 for file not found
                $statusCode = strpos($result['error'], 'not found') !== false ? 404 : 400;
                return response()->json($result, $statusCode);
            }

        } catch (\Exception $e) {
            Log::error('Get presentation file content failed', [
                'error' => $e->getMessage(),
                'file_id' => $fileId,
                'user_id' => auth()->id() ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get file content'
            ], 500);
        }
    }

    /**
     * Download a presentation file (protected - user authentication required)
     */
    public function downloadPresentationFile($fileId)
    {
        try {
            $userId = auth()->id() ?? 5;

            $result = $this->aiPresentationService->getPresentationFileForDownload($fileId, $userId);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 404);
            }

            $file = $result['file'];
            $filePath = $result['file_path'];

            return response()->download($filePath, $file->filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'Content-Disposition' => 'attachment; filename="' . $file->filename . '"',
            ]);

        } catch (\Exception $e) {
            Log::error('Download presentation file failed', [
                'error' => $e->getMessage(),
                'file_id' => $fileId,
                'user_id' => auth()->id() ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Download failed'
            ], 500);
        }
    }

    /**
     * Get job status for presentation operations (outline, content, or export)
     * GET /api/presentations/status?job_id={jobId}
     */
    public function getJobStatus(Request $request): JsonResponse
    {
        try {
            $jobId = $request->query('job_id');
            
            if (!$jobId) {
                return response()->json([
                    'success' => false,
                    'error' => 'job_id parameter is required'
                ], 400);
            }

            $job = $this->universalJobService->getJob($jobId);
            
            if (!$job) {
                return response()->json([
                    'success' => false,
                    'error' => 'Job not found'
                ], 404);
            }

            // Verify job is for presentations tool (outline, content, or export)
            $validToolTypes = ['presentation_outline', 'presentation_content', 'presentation_export'];
            $jobToolType = $job['tool_type'] ?? '';
            
            if (!in_array($jobToolType, $validToolTypes)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid job type. Must be one of: ' . implode(', ', $validToolTypes)
                ], 400);
            }

            // Check if user owns this job (optional - can be public for polling)
            $userId = auth()->id();
            if ($userId && isset($job['user_id']) && $job['user_id'] !== $userId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized'
                ], 403);
            }

            // Get stage information with user-friendly messages
            $stage = $job['stage'] ?? 'initializing';
            $stageInfo = $this->getStageInfo($jobToolType, $stage, $job['progress'] ?? 0);
            
            return response()->json([
                'success' => true,
                'job_id' => $job['id'],
                'tool_type' => $jobToolType,
                'status' => $job['status'] ?? 'unknown',
                'progress' => $job['progress'] ?? 0,
                'stage' => $stage,
                'stage_message' => $stageInfo['message'],
                'stage_description' => $stageInfo['description'],
                'error' => $job['error'] ?? null,
                'created_at' => $job['created_at'] ?? null,
                'updated_at' => $job['updated_at'] ?? null,
                'logs' => $job['logs'] ?? []
            ]);

        } catch (\Exception $e) {
            Log::error('Get presentation job status failed', [
                'error' => $e->getMessage(),
                'job_id' => $request->query('job_id'),
                'user_id' => auth()->id() ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get job status'
            ], 500);
        }
    }

    /**
     * Get job result for presentation operations (outline, content, or export)
     * GET /api/presentations/result?job_id={jobId}
     */
    public function getJobResult(Request $request): JsonResponse
    {
        try {
            $jobId = $request->query('job_id');
            
            if (!$jobId) {
                return response()->json([
                    'success' => false,
                    'error' => 'job_id parameter is required'
                ], 400);
            }

            $job = $this->universalJobService->getJob($jobId);
            
            if (!$job) {
                return response()->json([
                    'success' => false,
                    'error' => 'Job not found'
                ], 404);
            }

            // Verify job is for presentations tool (outline, content, or export)
            $validToolTypes = ['presentation_outline', 'presentation_content', 'presentation_export'];
            $jobToolType = $job['tool_type'] ?? '';
            
            if (!in_array($jobToolType, $validToolTypes)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid job type. Must be one of: ' . implode(', ', $validToolTypes)
                ], 400);
            }

            // Check if user owns this job
            $userId = auth()->id();
            if ($userId && isset($job['user_id']) && $job['user_id'] !== $userId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized'
                ], 403);
            }

            if (($job['status'] ?? '') !== 'completed') {
                return response()->json([
                    'success' => false,
                    'error' => 'Job not completed yet',
                    'status' => $job['status'] ?? 'unknown',
                    'progress' => $job['progress'] ?? 0
                ], 202);
            }

            return response()->json([
                'success' => true,
                'job_id' => $job['id'],
                'tool_type' => $jobToolType,
                'result' => $job['result'] ?? null,
                'metadata' => $job['metadata'] ?? []
            ]);

        } catch (\Exception $e) {
            Log::error('Get presentation job result failed', [
                'error' => $e->getMessage(),
                'job_id' => $request->query('job_id'),
                'user_id' => auth()->id() ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get job result'
            ], 500);
        }
    }

    /**
     * Get user-friendly stage information based on tool type and stage
     */
    private function getStageInfo($toolType, $stage, $progress)
    {
        $stages = [
            'presentation_outline' => [
                'initializing' => [
                    'message' => 'Preparing to generate outline...',
                    'description' => 'Setting up the outline generation process'
                ],
                'analyzing_content' => [
                    'message' => 'Analyzing your input...',
                    'description' => 'Processing the provided content to understand the topic'
                ],
                'processing_file' => [
                    'message' => 'Reading and processing file...',
                    'description' => 'Extracting content from the uploaded file'
                ],
                'processing_text' => [
                    'message' => 'Processing text content...',
                    'description' => 'Analyzing the text to identify key points'
                ],
                'generating_outline' => [
                    'message' => 'Generating presentation outline...',
                    'description' => 'Creating the structure and slides for your presentation'
                ],
                'ai_processing' => [
                    'message' => 'AI is creating your outline...',
                    'description' => 'Using AI to generate a comprehensive presentation outline'
                ],
                'finalizing' => [
                    'message' => 'Finalizing outline...',
                    'description' => 'Completing the outline and preparing it for you'
                ],
                'completed' => [
                    'message' => 'Outline generated successfully!',
                    'description' => 'Your presentation outline is ready'
                ],
                'failed' => [
                    'message' => 'Outline generation failed',
                    'description' => 'An error occurred while generating the outline'
                ]
            ],
            'presentation_content' => [
                'initializing' => [
                    'message' => 'Preparing to generate content...',
                    'description' => 'Setting up the content generation process'
                ],
                'validating' => [
                    'message' => 'Validating outline structure...',
                    'description' => 'Checking that your outline is properly formatted'
                ],
                'processing_outline' => [
                    'message' => 'Processing outline...',
                    'description' => 'Analyzing your presentation outline'
                ],
                'generating_content' => [
                    'message' => 'Generating slide content...',
                    'description' => 'Creating detailed content for each slide'
                ],
                'ai_processing' => [
                    'message' => 'AI is writing your content...',
                    'description' => 'Using AI to generate comprehensive content for all slides'
                ],
                'enhancing_content' => [
                    'message' => 'Enhancing content quality...',
                    'description' => 'Refining and improving the generated content'
                ],
                'finalizing' => [
                    'message' => 'Finalizing content...',
                    'description' => 'Completing the content generation process'
                ],
                'completed' => [
                    'message' => 'Content generated successfully!',
                    'description' => 'Your presentation content is ready'
                ],
                'failed' => [
                    'message' => 'Content generation failed',
                    'description' => 'An error occurred while generating the content'
                ]
            ],
            'presentation_export' => [
                'initializing' => [
                    'message' => 'Preparing to export presentation...',
                    'description' => 'Setting up the export process'
                ],
                'validating' => [
                    'message' => 'Validating presentation data...',
                    'description' => 'Checking that all presentation content is valid'
                ],
                'calling_microservice' => [
                    'message' => 'Generating PowerPoint file...',
                    'description' => 'Creating your PowerPoint presentation with the selected template'
                ],
                'generating_pptx' => [
                    'message' => 'Building PowerPoint slides...',
                    'description' => 'Assembling all slides into a PowerPoint file'
                ],
                'applying_template' => [
                    'message' => 'Applying template and styling...',
                    'description' => 'Adding your chosen template, colors, and fonts'
                ],
                'saving_file' => [
                    'message' => 'Saving your presentation...',
                    'description' => 'Storing the generated PowerPoint file'
                ],
                'finalizing' => [
                    'message' => 'Finalizing export...',
                    'description' => 'Completing the export process'
                ],
                'completed' => [
                    'message' => 'Export completed successfully!',
                    'description' => 'Your PowerPoint file is ready for download'
                ],
                'failed' => [
                    'message' => 'Export failed',
                    'description' => 'An error occurred while exporting the presentation'
                ]
            ]
        ];

        $defaultStage = [
            'message' => 'Processing...',
            'description' => 'Your request is being processed'
        ];

        return $stages[$toolType][$stage] ?? $defaultStage;
    }
}
