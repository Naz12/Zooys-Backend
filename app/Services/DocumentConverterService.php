<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentConverterService
{
    private $baseUrl;
    private $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.document_converter.url', 'http://localhost:8004');
        $this->apiKey = config('services.document_converter.api_key', 'test-api-key-123');
    }

    /**
     * Check microservice health
     */
    public function checkHealth()
    {
        try {
            $response = Http::timeout(10)->get($this->baseUrl . '/health');
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return [
                'status' => 'unhealthy',
                'error' => 'Microservice not responding'
            ];

        } catch (\Exception $e) {
            Log::error('Document converter health check failed: ' . $e->getMessage());
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get conversion capabilities
     */
    public function getCapabilities()
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->get($this->baseUrl . '/v1/capabilities');
            
            if ($response->successful()) {
                return $response->json();
            }
            
            throw new \Exception('Failed to get capabilities: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('Get capabilities failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get extraction capabilities
     */
    public function getExtractionCapabilities()
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->get($this->baseUrl . '/v1/extract/capabilities');
            
            if ($response->successful()) {
                return $response->json();
            }
            
            throw new \Exception('Failed to get extraction capabilities: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('Get extraction capabilities failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Convert document
     */
    public function convertDocument($filePath, $targetFormat, $options = [])
    {
        try {
            Log::info('Sending conversion request to microservice', [
                'target_format' => $targetFormat,
                'options' => $options,
                'options_type' => gettype($options)
            ]);
            
            $response = Http::timeout(120)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->attach('file', file_get_contents($filePath), basename($filePath))
                ->post($this->baseUrl . '/v1/convert', [
                    'target_format' => $targetFormat,
                    'options' => $options
                ]);
            
            if ($response->successful()) {
                $result = $response->json();
                Log::info('Microservice conversion response: ' . json_encode($result));
                Log::info('Conversion response type: ' . gettype($result));
                if (is_array($result)) {
                    Log::info('Conversion response is array, keys: ' . json_encode(array_keys($result)));
                }
                return $result;
            }
            
            throw new \Exception('Conversion failed: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('Document conversion failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Extract content from document
     */
    public function extractContent($filePath, $extractionType = 'text', $language = 'eng', $options = [])
    {
        try {
            $response = Http::timeout(120)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->attach('file', file_get_contents($filePath), basename($filePath))
                ->post($this->baseUrl . '/v1/extract', [
                    'extraction_type' => $extractionType,
                    'language' => $language,
                    'include_formatting' => $options['include_formatting'] ?? false,
                    'max_pages' => $options['max_pages'] ?? 10
                ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            throw new \Exception('Content extraction failed: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('Content extraction failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check job status
     */
    public function checkJobStatus($jobId)
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->get($this->baseUrl . '/v1/status', [
                    'job_id' => $jobId
                ]);
            
            if ($response->successful()) {
                $result = $response->json();
                Log::info('Microservice status response: ' . json_encode($result));
                Log::info('Status response type: ' . gettype($result));
                if (is_array($result)) {
                    Log::info('Status response is array, keys: ' . json_encode(array_keys($result)));
                }
                return $result;
            }
            
            throw new \Exception('Failed to check job status: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('Job status check failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get job result
     */
    public function getJobResult($jobId)
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->get($this->baseUrl . '/v1/result', [
                    'job_id' => $jobId
                ]);
            
            if ($response->successful()) {
                $result = $response->json();
                Log::info('Microservice result response: ' . json_encode($result));
                return $result;
            }
            
            throw new \Exception('Failed to get job result: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('Get job result failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Download converted file
     */
    public function downloadFile($filePath)
    {
        try {
            if (!file_exists($filePath)) {
                throw new \Exception('File not found: ' . $filePath);
            }

            return response()->download($filePath);

        } catch (\Exception $e) {
            Log::error('File download failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Store file in Laravel storage
     */
    public function storeFile($filePath, $filename = null)
    {
        try {
            if (!file_exists($filePath)) {
                throw new \Exception('File not found: ' . $filePath);
            }

            $filename = $filename ?: basename($filePath);
            $storagePath = 'converted-files/' . $filename;
            
            Storage::disk('public')->put($storagePath, file_get_contents($filePath));
            
            return [
                'path' => $storagePath,
                'url' => Storage::disk('public')->url($storagePath),
                'filename' => $filename
            ];

        } catch (\Exception $e) {
            Log::error('File storage failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
