<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class FileUploadController extends Controller
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Upload one or multiple files
     */
    public function upload(Request $request): JsonResponse
    {
        // Log incoming request for debugging
        \Log::info('File upload request received', [
            'has_file' => $request->hasFile('file'),
            'has_files' => $request->hasFile('files'),
            'files_is_array' => $request->hasFile('files') && is_array($request->file('files')),
            'all_files' => $request->allFiles()
        ]);

        // Check if multiple files or single file
        $hasMultipleFiles = $request->hasFile('files') && is_array($request->file('files'));
        
        if ($hasMultipleFiles) {
            // Validate multiple files
            $validator = Validator::make($request->all(), [
                'files' => 'required|array',
                'files.*' => 'required|file|max:51200', // 50MB max per file
                'metadata' => 'sometimes|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            $files = $request->file('files');
            $metadata = $request->input('metadata', []);
            
            $uploadedFiles = [];
            $errors = [];

            foreach ($files as $index => $file) {
                $result = $this->fileUploadService->uploadFile($file, $user->id, $metadata);
                
                if ($result['success']) {
                    $uploadedFiles[] = [
                        'file_upload' => $result['file_upload'],
                        'file_url' => $result['file_url']
                    ];
                } else {
                    $errors[] = [
                        'index' => $index,
                        'filename' => $file->getClientOriginalName(),
                        'error' => $result['error']
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => count($uploadedFiles) . ' file(s) uploaded successfully',
                'uploaded_count' => count($uploadedFiles),
                'error_count' => count($errors),
                'file_uploads' => $uploadedFiles,
                'errors' => $errors
            ], 201);
        } else {
            // Single file upload (backward compatibility)
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:51200', // 50MB max
            'metadata' => 'sometimes|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $file = $request->file('file');
        $metadata = $request->input('metadata', []);

        $result = $this->fileUploadService->uploadFile($file, $user->id, $metadata);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => [
                    'file_upload' => $result['file_upload'],
                    'file_url' => $result['file_url'],
                    'file_id' => $result['file_upload']->id,
                ],
                // Backward compatibility - keep top-level properties
                'file_upload' => $result['file_upload'],
                'file_url' => $result['file_url']
            ], 201);
        }

        return response()->json([
            'error' => $result['error']
        ], 400);
        }
    }

    /**
     * Get user's uploaded files
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');

        $files = $this->fileUploadService->getUserFiles($user->id, $perPage, $search);

        // Add download_url to each file
        $filesData = collect($files->items())->map(function ($file) {
            $fileArray = $file->toArray();
            $fileArray['download_url'] = url('/api/files/' . $file->id . '/download');
            return $fileArray;
        });

        return response()->json([
            'files' => $filesData,
            'pagination' => [
                'current_page' => $files->currentPage(),
                'last_page' => $files->lastPage(),
                'per_page' => $files->perPage(),
                'total' => $files->total()
            ]
        ]);
    }

    /**
     * Get specific file
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $file = \App\Models\FileUpload::where('user_id', $user->id)->find($id);

        if (!$file) {
            return response()->json([
                'error' => 'File not found'
            ], 404);
        }

        $fileData = $file->toArray();
        $fileData['download_url'] = url('/api/files/' . $file->id . '/download');

        return response()->json([
            'file' => $fileData
        ]);
    }

    /**
     * Delete a file
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $file = \App\Models\FileUpload::where('user_id', $user->id)->find($id);

        if (!$file) {
            return response()->json([
                'error' => 'File not found'
            ], 404);
        }

        $success = $this->fileUploadService->deleteFile($file);

        if ($success) {
            return response()->json([
                'message' => 'File deleted successfully'
            ]);
        }

        return response()->json([
            'error' => 'Failed to delete file'
        ], 500);
    }

    /**
     * Get file content for processing
     */
    public function content(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $file = \App\Models\FileUpload::where('user_id', $user->id)->find($id);

        if (!$file) {
            return response()->json([
                'error' => 'File not found'
            ], 404);
        }

        $result = $this->fileUploadService->getFileContent($file);

        if ($result['success']) {
            return response()->json([
                'content' => $result['content'],
                'metadata' => $result['metadata']
            ]);
        }

        return response()->json([
            'error' => $result['error']
        ], 400);
    }

    /**
     * Download a file
     */
    public function download(Request $request, $id)
    {
        $user = $request->user();
        $file = \App\Models\FileUpload::where('user_id', $user->id)->find($id);

        if (!$file) {
            return response()->json([
                'error' => 'File not found'
            ], 404);
        }

        $filePath = storage_path('app/' . $file->file_path);

        if (!file_exists($filePath)) {
            return response()->json([
                'error' => 'File not found on server'
            ], 404);
        }

        return response()->download($filePath, $file->original_name, [
            'Content-Type' => $file->mime_type,
        ]);
    }

    /**
     * Test endpoint to check what files are received
     */
    public function testUpload(Request $request): JsonResponse
    {
        $allFiles = [];
        foreach ($request->allFiles() as $key => $files) {
            if (is_array($files)) {
                foreach ($files as $file) {
                    $allFiles[] = [
                        'key' => $key,
                        'name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime' => $file->getMimeType()
                    ];
                }
            } else {
                $allFiles[] = [
                    'key' => $key,
                    'name' => $files->getClientOriginalName(),
                    'size' => $files->getSize(),
                    'mime' => $files->getMimeType()
                ];
            }
        }
        
        return response()->json([
            'message' => 'File upload test endpoint',
            'has_file' => $request->hasFile('file'),
            'has_files' => $request->hasFile('files'),
            'file_count' => $request->hasFile('files') && is_array($request->file('files')) 
                ? count($request->file('files')) 
                : ($request->hasFile('file') ? 1 : 0),
            'files_is_array' => $request->hasFile('files') ? is_array($request->file('files')) : false,
            'all_files' => $allFiles,
            'request_keys' => array_keys($request->all()),
            'file_keys' => array_keys($request->allFiles())
        ]);
    }
}
