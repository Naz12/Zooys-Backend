<?php

namespace App\Services;

use App\Models\FileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class FileUploadService
{
    /**
     * Upload and save a file
     */
    public function uploadFile(UploadedFile $file, $userId, $metadata = [])
    {
        try {
            // Generate unique filename
            $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            
            // Determine file type
            $fileType = $this->determineFileType($file);
            
            // Store file
            $filePath = $file->storeAs('uploads/files', $storedName, 'public');
            
            // Create database record
            $fileUpload = FileUpload::create([
                'user_id' => $userId,
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => $storedName,
                'file_path' => $filePath,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'file_type' => $fileType,
                'metadata' => array_merge($metadata, [
                    'uploaded_at' => now(),
                    'client_ip' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]),
                'is_processed' => false
            ]);

            Log::info('File uploaded successfully', [
                'file_id' => $fileUpload->id,
                'user_id' => $userId,
                'file_type' => $fileType,
                'file_size' => $fileUpload->file_size
            ]);

            return [
                'success' => true,
                'file_upload' => $fileUpload,
                'file_url' => $fileUpload->file_url
            ];

        } catch (\Exception $e) {
            Log::error('File upload failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'file_name' => $file->getClientOriginalName()
            ]);

            return [
                'success' => false,
                'error' => 'File upload failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Determine file type based on MIME type and extension
     */
    private function determineFileType(UploadedFile $file): string
    {
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        // PDF files
        if ($mimeType === 'application/pdf' || $extension === 'pdf') {
            return 'pdf';
        }

        // Word documents
        if (in_array($mimeType, [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ]) || in_array($extension, ['doc', 'docx'])) {
            return 'doc';
        }

        // Text files
        if (in_array($mimeType, ['text/plain', 'text/csv']) || $extension === 'txt') {
            return 'txt';
        }

        // Audio files
        if (str_starts_with($mimeType, 'audio/') || in_array($extension, ['mp3', 'wav', 'm4a', 'aac'])) {
            return 'audio';
        }

        // Image files
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        // Default
        return 'unknown';
    }

    /**
     * Get file content for processing
     */
    public function getFileContent(FileUpload $fileUpload): array
    {
        try {
            $filePath = $fileUpload->file_path;
            
            if (!Storage::exists($filePath)) {
                return [
                    'success' => false,
                    'error' => 'File not found'
                ];
            }

            $content = '';
            $metadata = [];

            switch ($fileUpload->file_type) {
                case 'pdf':
                    // Use existing PDF processing service
                    $pdfService = app(\App\Services\PythonPDFProcessingService::class);
                    $result = $pdfService->extractTextFromPDF($filePath);
                    if ($result['success']) {
                        $content = $result['text'];
                        $metadata = $result['metadata'] ?? [];
                    }
                    break;

                case 'doc':
                    // Use existing Word processing service
                    $wordService = app(\App\Services\WordProcessingService::class);
                    $result = $wordService->extractTextFromWord($filePath);
                    if ($result['success']) {
                        $content = $result['text'];
                        $metadata = [
                            'pages' => $result['pages'] ?? [],
                            'total_pages' => $result['total_pages'] ?? 0,
                            'word_count' => $result['word_count'] ?? 0,
                            'character_count' => $result['character_count'] ?? 0
                        ];
                    }
                    break;

                case 'txt':
                    $content = Storage::get($filePath);
                    $metadata = [
                        'word_count' => str_word_count($content),
                        'character_count' => strlen($content)
                    ];
                    break;

                default:
                    return [
                        'success' => false,
                        'error' => 'Unsupported file type: ' . $fileUpload->file_type
                    ];
            }

            // Mark file as processed
            $fileUpload->update(['is_processed' => true]);

            return [
                'success' => true,
                'content' => $content,
                'metadata' => array_merge($fileUpload->metadata ?? [], $metadata)
            ];

        } catch (\Exception $e) {
            Log::error('File content extraction failed', [
                'file_id' => $fileUpload->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to extract content: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete a file upload and its associated file
     */
    public function deleteFile(FileUpload $fileUpload): bool
    {
        try {
            // Delete the file from storage
            if (Storage::exists($fileUpload->file_path)) {
                Storage::delete($fileUpload->file_path);
            }

            // Delete the database record
            $fileUpload->delete();

            Log::info('File deleted successfully', [
                'file_id' => $fileUpload->id,
                'file_path' => $fileUpload->file_path
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('File deletion failed', [
                'file_id' => $fileUpload->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get user's uploaded files
     */
    public function getUserFiles($userId, $perPage = 15, $search = null)
    {
        $query = FileUpload::where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('original_name', 'like', "%{$search}%")
                  ->orWhere('file_type', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }
}
