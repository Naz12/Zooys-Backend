<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Services\UniversalJobService;
use App\Services\Modules\UniversalFileManagementModule;
use App\Services\DocumentConverterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FileExtractionController extends Controller
{
    protected $universalJobService;
    protected $universalFileModule;
    protected $documentConverterService;

    public function __construct(
        UniversalJobService $universalJobService,
        UniversalFileManagementModule $universalFileModule,
        DocumentConverterService $documentConverterService
    ) {
        $this->universalJobService = $universalJobService;
        $this->universalFileModule = $universalFileModule;
        $this->documentConverterService = $documentConverterService;
    }

    /**
     * Convert document to specified format
     */
    public function convertDocument(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'file_id' => 'required|string|exists:file_uploads,id',
                'target_format' => 'required|string|in:pdf,png,jpg,jpeg,docx,doc,txt,html,md,pptx,ppt,xlsx,xls',
                'options' => 'sometimes|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            $fileId = $request->input('file_id');
            $targetFormat = $request->input('target_format');
            $options = $request->input('options', []);

            // Get file using universal file management
            $fileResult = $this->universalFileModule->getFile($fileId);
            
            if (!$fileResult['success']) {
                return response()->json([
                    'success' => false,
                    'error' => 'File not found',
                    'details' => $fileResult['error'] ?? 'File does not exist'
                ], 404);
            }

            $fileRecord = $fileResult['file'];

            // Create universal job for document conversion
            Log::info('Creating document conversion job with options', [
                'options' => $options,
                'options_type' => gettype($options)
            ]);
            
            $job = $this->universalJobService->createJob('document_conversion', [
                'file_id' => $fileId,
                'target_format' => $targetFormat,
                'original_filename' => $fileRecord->original_filename,
                'file_size' => $fileRecord->file_size
            ], $options, $user->id);

            // Queue background processing
            \Illuminate\Support\Facades\Artisan::queue('universal:process-job', [
                'jobId' => $job['id']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document conversion job started',
                'job_id' => $job['id'],
                'status' => $job['status'],
                'poll_url' => url('/api/status?job_id=' . $job['id']),
                'result_url' => url('/api/result?job_id=' . $job['id'])
            ], 202);

        } catch (\Exception $e) {
            Log::error('Document conversion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to start document conversion job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extract content from document
     */
    public function extractContent(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'file_id' => 'required|string|exists:file_uploads,id',
                'extraction_type' => 'sometimes|string|in:text,metadata,both',
                'language' => 'sometimes|string|in:eng,spa,fra,deu,ita,por,rus,chi,jpn,kor,ara',
                'include_formatting' => 'sometimes|boolean',
                'max_pages' => 'sometimes|integer|min:1|max:1000',
                'options' => 'sometimes|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            $fileId = $request->input('file_id');
            $extractionType = $request->input('extraction_type', 'text');
            $language = $request->input('language', 'eng');
            $includeFormatting = $request->input('include_formatting', false);
            $maxPages = $request->input('max_pages', 10);
            $options = $request->input('options', []);

            // Get file using universal file management
            $fileResult = $this->universalFileModule->getFile($fileId);
            
            if (!$fileResult['success']) {
                return response()->json([
                    'success' => false,
                    'error' => 'File not found',
                    'details' => $fileResult['error'] ?? 'File does not exist'
                ], 404);
            }

            $fileRecord = $fileResult['file'];

            // Create universal job for content extraction
            $job = $this->universalJobService->createJob('content_extraction', [
                'file_id' => $fileId,
                'extraction_type' => $extractionType,
                'language' => $language,
                'include_formatting' => $includeFormatting,
                'max_pages' => $maxPages,
                'original_filename' => $fileRecord->original_filename,
                'file_size' => $fileRecord->file_size
            ], $options, $user->id);

            // Queue background processing
            \Illuminate\Support\Facades\Artisan::queue('universal:process-job', [
                'jobId' => $job['id']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Content extraction job started',
                'job_id' => $job['id'],
                'status' => $job['status'],
                'poll_url' => url('/api/status?job_id=' . $job['id']),
                'result_url' => url('/api/result?job_id=' . $job['id'])
            ], 202);

        } catch (\Exception $e) {
            Log::error('Content extraction error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to start content extraction job: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Get supported formats and capabilities
     */
    public function getCapabilities()
    {
        try {
            $capabilities = $this->documentConverterService->getCapabilities();
            
            return response()->json([
                'success' => true,
                'data' => $capabilities
            ]);

        } catch (\Exception $e) {
            Log::error('Get capabilities error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to get capabilities: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get extraction capabilities
     */
    public function getExtractionCapabilities()
    {
        try {
            $capabilities = $this->documentConverterService->getExtractionCapabilities();
            
            return response()->json([
                'success' => true,
                'data' => $capabilities
            ]);

        } catch (\Exception $e) {
            Log::error('Get extraction capabilities error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to get extraction capabilities: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check microservice health
     */
    public function checkHealth()
    {
        try {
            $health = $this->documentConverterService->checkHealth();
            
            return response()->json([
                'success' => true,
                'data' => $health
            ]);

        } catch (\Exception $e) {
            Log::error('Health check error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to check microservice health: ' . $e->getMessage()
            ], 500);
        }
    }
}
