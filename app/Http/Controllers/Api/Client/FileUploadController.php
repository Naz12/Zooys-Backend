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
     * Upload a file
     */
    public function upload(Request $request): JsonResponse
    {
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
                'file_upload' => $result['file_upload'],
                'file_url' => $result['file_url']
            ], 201);
        }

        return response()->json([
            'error' => $result['error']
        ], 400);
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

        return response()->json([
            'files' => $files->items(),
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

        return response()->json([
            'file' => $file
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
}
