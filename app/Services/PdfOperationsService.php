<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PdfOperationsService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.document_converter.url', 'http://localhost:8004'), '/');
        $this->apiKey = (string)config('services.document_converter.api_key', 'test-api-key-123');
    }

    public function startOperation(string $operation, array $filePaths, array $params = [])
    {
        $path = $this->resolveOperationPath($operation);

        $request = Http::timeout(120)->withHeaders(['X-API-Key' => $this->apiKey])->asMultipart();

        // Attach file(s)
        if (in_array($operation, ['merge', 'batch'])) {
            foreach ($filePaths as $fp) {
                if (!file_exists($fp)) {
                    throw new \RuntimeException("File not found: $fp");
                }
                $fileContent = file_get_contents($fp);
                Log::info("Attaching file for merge/batch", [
                    'operation' => $operation,
                    'file_path' => $fp,
                    'file_name' => basename($fp),
                    'file_size' => strlen($fileContent),
                    'file_exists' => file_exists($fp)
                ]);
                $request = $request->attach('files', $fileContent, basename($fp));
            }
        } else {
            if (empty($filePaths)) {
                throw new \InvalidArgumentException('At least one file is required');
            }
            $filePath = $filePaths[0];
            if (!file_exists($filePath)) {
                throw new \RuntimeException("File not found: $filePath");
            }
            $fileContent = file_get_contents($filePath);
            Log::info("Attaching file for single operation", [
                'operation' => $operation,
                'file_path' => $filePath,
                'file_name' => basename($filePath),
                'file_size' => strlen($fileContent),
                'file_exists' => file_exists($filePath),
                'is_readable' => is_readable($filePath)
            ]);
            $request = $request->attach('file', $fileContent, basename($filePath));
        }

        // Add params (form-data)
        foreach ($params as $key => $value) {
            $request = $request->attach($key, is_string($value) ? $value : json_encode($value));
        }

        $fullUrl = $this->baseUrl . '/' . ltrim($path, '/');
        
        Log::info("Starting PDF operation", [
            'operation' => $operation,
            'url' => $fullUrl,
            'params' => $params,
            'file_count' => count($filePaths)
        ]);

        $response = $request->post($fullUrl);
        
        if (!$response->successful()) {
            Log::error("PDF operation failed to start", [
                'operation' => $operation,
                'url' => $fullUrl,
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'params' => $params
            ]);
            throw new \RuntimeException('PDF operation failed to start: ' . $response->body());
        }
        return $response->json();
    }

    public function getStatus(string $operation, string $jobId)
    {
        $statusPath = $this->resolveStatusPath($operation);
        return Http::timeout(30)
            ->withHeaders(['X-API-Key' => $this->apiKey])
            ->get($this->baseUrl . '/' . ltrim($statusPath, '/'), ['job_id' => $jobId])
            ->throw()
            ->json();
    }

    public function getResult(string $operation, string $jobId)
    {
        $resultPath = $this->resolveResultPath($operation);
        $response = Http::timeout(60)
            ->withHeaders(['X-API-Key' => $this->apiKey])
            ->get($this->baseUrl . '/' . ltrim($resultPath, '/'), ['job_id' => $jobId])
            ->throw();
        
        // Check if response is JSON or binary file
        $contentType = $response->header('Content-Type');
        
        if (str_contains($contentType, 'application/json')) {
            // Scenario B: JSON response with download URLs (multiple files)
            $jsonResult = $response->json();
            
            // If there are download_urls, download them and store locally
            if (isset($jsonResult['download_urls']) && is_array($jsonResult['download_urls'])) {
                return $this->downloadAndStoreMultipleFiles($jsonResult, $operation, $jobId);
            }
            
            // No download URLs, return as-is
            return $jsonResult;
        }
        
        // Scenario A: Binary file response (single file) - save it and return file info
        return $this->saveBinaryFile($response, $operation, $jobId);
    }

    /**
     * Download multiple files from microservice and store locally
     */
    private function downloadAndStoreMultipleFiles(array $microserviceResult, string $operation, string $jobId): array
    {
        $storagePath = 'pdf_results/' . date('Y-m-d');
        $fullPath = storage_path('app/public/' . $storagePath);
        
        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        $localFiles = [];
        $localUrls = [];
        $localPaths = [];

        foreach ($microserviceResult['download_urls'] as $index => $downloadUrl) {
            try {
                // Download file from microservice
                $fileResponse = Http::timeout(120)
                    ->withHeaders(['X-API-Key' => $this->apiKey])
                    ->get($downloadUrl);

                if ($fileResponse->successful()) {
                    // Extract original filename or generate one
                    $originalFilename = $microserviceResult['files'][$index] ?? null;
                    if (!$originalFilename) {
                        // Extract from URL or generate
                        $originalFilename = basename(parse_url($downloadUrl, PHP_URL_PATH));
                    }

                    // Ensure unique filename
                    $filename = substr($jobId, 0, 8) . '_' . $originalFilename;
                    $filePath = $fullPath . '/' . $filename;

                    // Save file locally
                    file_put_contents($filePath, $fileResponse->body());

                    $localFiles[] = $filename;
                    $localUrls[] = asset('storage/' . $storagePath . '/' . $filename);
                    $localPaths[] = $filePath;

                    Log::info("Downloaded and stored file from microservice", [
                        'operation' => $operation,
                        'job_id' => $jobId,
                        'filename' => $filename,
                        'size' => strlen($fileResponse->body())
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Failed to download file from microservice", [
                    'operation' => $operation,
                    'job_id' => $jobId,
                    'download_url' => $downloadUrl,
                    'error' => $e->getMessage()
                ]);
                // Continue with other files even if one fails
            }
        }

        return [
            'files' => $localFiles,
            'download_urls' => $localUrls,
            'file_paths' => $localPaths,
            'job_id' => $jobId,
            'job_type' => $operation,
            'file_count' => count($localFiles)
        ];
    }

    /**
     * Save binary file response and return file info
     */
    private function saveBinaryFile($response, string $operation, string $jobId): array
    {
        $contentType = $response->header('Content-Type');
        
        $extension = match($contentType) {
            'application/pdf' => 'pdf',
            'application/zip', 'application/x-zip-compressed' => 'zip',
            default => 'pdf'
        };
        
        $filename = $operation . '_' . substr($jobId, 0, 8) . '.' . $extension;
        $storagePath = 'pdf_results/' . date('Y-m-d');
        $fullPath = storage_path('app/public/' . $storagePath);
        
        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
        
        $filePath = $fullPath . '/' . $filename;
        file_put_contents($filePath, $response->body());

        Log::info("Saved binary file from microservice", [
            'operation' => $operation,
            'job_id' => $jobId,
            'filename' => $filename,
            'size' => strlen($response->body())
        ]);
        
        return [
            'files' => [$filename],
            'download_urls' => [asset('storage/' . $storagePath . '/' . $filename)],
            'file_paths' => [$filePath],
            'job_id' => $jobId,
            'job_type' => $operation
        ];
    }

    private function resolveOperationPath(string $operation): string
    {
        // POST endpoints
        return match ($operation) {
            'merge' => 'v1/merge',
            'split' => 'v1/pdf/split',
            'compress' => 'v1/pdf/compress',
            'watermark' => 'v1/pdf/watermark',
            'page_numbers' => 'v1/pdf/page-numbers',
            'annotate' => 'v1/pdf/annotate',
            'protect' => 'v1/pdf/protect',
            'unlock' => 'v1/pdf/unlock',
            'preview' => 'v1/pdf/preview',
            'batch' => 'v1/pdf/batch',
            'edit_pdf' => 'v1/pdf/edit_pdf',
            default => throw new \InvalidArgumentException("Unsupported PDF operation: {$operation}"),
        };
    }

    private function resolveStatusPath(string $operation): string
    {
        // GET status
        return match ($operation) {
            'merge' => 'v1/pdf/merge/status',
            'split' => 'v1/pdf/split/status',
            'compress' => 'v1/pdf/compress/status',
            'watermark' => 'v1/pdf/watermark/status',
            // Microservice expects underscore variant on status/result for page numbers
            'page_numbers' => 'v1/pdf/page_numbers/status',
            'annotate' => 'v1/pdf/annotate/status',
            'protect' => 'v1/pdf/protect/status',
            'unlock' => 'v1/pdf/unlock/status',
            'preview' => 'v1/pdf/preview/status',
            'batch' => 'v1/pdf/batch/status',
            'edit_pdf' => 'v1/pdf/edit_pdf/status',
            default => throw new \InvalidArgumentException("Unsupported PDF operation: {$operation}"),
        };
    }

    private function resolveResultPath(string $operation): string
    {
        // GET result
        return match ($operation) {
            'merge' => 'v1/pdf/merge/result',
            'split' => 'v1/pdf/split/result',
            'compress' => 'v1/pdf/compress/result',
            'watermark' => 'v1/pdf/watermark/result',
            'page_numbers' => 'v1/pdf/page_numbers/result',
            'annotate' => 'v1/pdf/annotate/result',
            'protect' => 'v1/pdf/protect/result',
            'unlock' => 'v1/pdf/unlock/result',
            'preview' => 'v1/pdf/preview/result',
            'batch' => 'v1/pdf/batch/result',
            'edit_pdf' => 'v1/pdf/edit_pdf/result',
            default => throw new \InvalidArgumentException("Unsupported PDF operation: {$operation}"),
        };
    }
}


