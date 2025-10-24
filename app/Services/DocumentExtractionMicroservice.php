<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class DocumentExtractionMicroservice
{
    protected $baseUrl;
    protected $timeout;

    public function __construct()
    {
        $this->baseUrl = env('DOCUMENT_EXTRACTION_URL', 'http://localhost:8003');
        $this->timeout = env('DOCUMENT_EXTRACTION_TIMEOUT', 300); // 5 minutes
    }

    /**
     * Extract text from a file using the microservice
     */
    public function extractText($filePath, $fileType, $options = [])
    {
        try {
            Log::info("Calling document extraction microservice", [
                'file_path' => $filePath,
                'file_type' => $fileType,
                'options' => $options
            ]);

            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post($this->baseUrl . '/extract', [
                    'file_path' => $filePath,
                    'file_type' => $fileType,
                    'options' => json_encode($options)
                ]);

            if ($response->successful()) {
                $result = $response->json();
                
                Log::info("Document extraction successful", [
                    'file_type' => $fileType,
                    'success' => $result['success'] ?? false,
                    'word_count' => $result['word_count'] ?? 0,
                    'character_count' => $result['character_count'] ?? 0
                ]);

                return $result;
            } else {
                $error = $response->json()['error'] ?? 'Unknown error';
                Log::error("Document extraction failed", [
                    'status' => $response->status(),
                    'error' => $error
                ]);

                return [
                    'success' => false,
                    'text' => '',
                    'pages' => 0,
                    'metadata' => [],
                    'word_count' => 0,
                    'character_count' => 0,
                    'error' => "Microservice error: {$error}"
                ];
            }

        } catch (RequestException $e) {
            Log::error("Document extraction microservice request failed", [
                'error' => $e->getMessage(),
                'file_path' => $filePath,
                'file_type' => $fileType
            ]);

            return [
                'success' => false,
                'text' => '',
                'pages' => 0,
                'metadata' => [],
                'word_count' => 0,
                'character_count' => 0,
                'error' => "Microservice connection failed: " . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error("Document extraction error", [
                'error' => $e->getMessage(),
                'file_path' => $filePath,
                'file_type' => $fileType
            ]);

            return [
                'success' => false,
                'text' => '',
                'pages' => 0,
                'metadata' => [],
                'word_count' => 0,
                'character_count' => 0,
                'error' => "Extraction failed: " . $e->getMessage()
            ];
        }
    }

    /**
     * Extract text from uploaded file using the microservice
     */
    public function extractFromUpload($file, $fileType, $options = [])
    {
        try {
            Log::info("Calling document extraction microservice with upload", [
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $fileType,
                'file_size' => $file->getSize()
            ]);

            $response = Http::timeout($this->timeout)
                ->attach('file', $file->get(), $file->getClientOriginalName())
                ->asForm()
                ->post($this->baseUrl . '/extract/file', [
                    'file_type' => $fileType,
                    'options' => json_encode($options)
                ]);

            if ($response->successful()) {
                $result = $response->json();
                
                Log::info("Document extraction from upload successful", [
                    'file_type' => $fileType,
                    'success' => $result['success'] ?? false,
                    'word_count' => $result['word_count'] ?? 0,
                    'character_count' => $result['character_count'] ?? 0
                ]);

                return $result;
            } else {
                $error = $response->json()['error'] ?? 'Unknown error';
                Log::error("Document extraction from upload failed", [
                    'status' => $response->status(),
                    'error' => $error
                ]);

                return [
                    'success' => false,
                    'text' => '',
                    'pages' => 0,
                    'metadata' => [],
                    'word_count' => 0,
                    'character_count' => 0,
                    'error' => "Microservice error: {$error}"
                ];
            }

        } catch (RequestException $e) {
            Log::error("Document extraction microservice upload request failed", [
                'error' => $e->getMessage(),
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $fileType
            ]);

            return [
                'success' => false,
                'text' => '',
                'pages' => 0,
                'metadata' => [],
                'word_count' => 0,
                'character_count' => 0,
                'error' => "Microservice connection failed: " . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error("Document extraction upload error", [
                'error' => $e->getMessage(),
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $fileType
            ]);

            return [
                'success' => false,
                'text' => '',
                'pages' => 0,
                'metadata' => [],
                'word_count' => 0,
                'character_count' => 0,
                'error' => "Extraction failed: " . $e->getMessage()
            ];
        }
    }

    /**
     * Check if the microservice is healthy
     */
    public function healthCheck()
    {
        try {
            $response = Http::timeout(10)->get($this->baseUrl . '/health');
            
            if ($response->successful()) {
                $result = $response->json();
                return [
                    'healthy' => true,
                    'status' => $result['status'] ?? 'unknown',
                    'message' => $result['message'] ?? 'Service is running',
                    'version' => $result['version'] ?? 'unknown'
                ];
            } else {
                return [
                    'healthy' => false,
                    'error' => 'Health check failed',
                    'status_code' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => 'Health check failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get service information
     */
    public function getServiceInfo()
    {
        try {
            $response = Http::timeout(10)->get($this->baseUrl . '/');
            
            if ($response->successful()) {
                return $response->json();
            } else {
                return [
                    'error' => 'Failed to get service info',
                    'status_code' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            return [
                'error' => 'Failed to get service info: ' . $e->getMessage()
            ];
        }
    }
}
