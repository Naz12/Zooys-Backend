<?php

namespace App\Services;

use App\Models\AIResult;
use App\Services\AIResultService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class AIDiagramService
{
    private $aiResultService;
    private $microserviceUrl;
    private $microserviceApiKey;
    private $microserviceTimeout;

    public function __construct(AIResultService $aiResultService)
    {
        $this->aiResultService = $aiResultService;
        $this->microserviceUrl = env('DIAGRAM_MICROSERVICE_URL', 'http://localhost:8005');
        $this->microserviceApiKey = env('DIAGRAM_MICROSERVICE_API_KEY', 'diagram-service-api-key-12345');
        $this->microserviceTimeout = env('DIAGRAM_MICROSERVICE_TIMEOUT', 120);
    }

    /**
     * Generate a diagram using the microservice
     * 
     * @param array $inputData Input data including:
     *   - prompt: Description/instruction for the diagram
     *   - diagram_type: Type of diagram to generate
     *   - output_format: Output format (svg, pdf, png) - default: "svg"
     *   - language: Language code (optional, default: "en")
     * @param int $userId User ID
     * @return array Response with job_id from microservice
     */
    public function generateDiagram(array $inputData, int $userId)
    {
        try {
            Log::info('AIDiagramService: Generating diagram', [
                'diagram_type' => $inputData['diagram_type'] ?? 'unknown',
                'output_format' => $inputData['output_format'] ?? 'svg',
                'user_id' => $userId
            ]);

            // Prepare request data for microservice
            $requestData = [
                'diagram_type' => $inputData['diagram_type'],
                'prompt' => $inputData['prompt'],
                'output_format' => $inputData['output_format'] ?? 'svg'
            ];

            // Prepare headers with API key
            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-API-Key' => $this->microserviceApiKey
            ];

            // Call microservice to generate diagram
            $response = Http::timeout($this->microserviceTimeout)
                ->withHeaders($headers)
                ->post($this->microserviceUrl . '/generate-diagram', $requestData);

            if (!$response->successful()) {
                $errorBody = $response->body();
                $errorMessage = 'Microservice request failed: ' . $response->status();
                
                if ($errorBody) {
                    $errorData = json_decode($errorBody, true);
                    if (isset($errorData['detail'])) {
                        $errorMessage = $errorData['detail'];
                    }
                }

                Log::error('AIDiagramService: Microservice request failed', [
                    'error' => $errorMessage,
                    'status' => $response->status()
                ]);

                return [
                    'success' => false,
                    'error' => $errorMessage
                ];
            }

            $responseData = $response->json();
            $microserviceJobId = $responseData['job_id'] ?? null;

            if (!$microserviceJobId) {
                return [
                    'success' => false,
                    'error' => 'No job_id returned from microservice'
                ];
            }

            // Save initial AI result with job tracking
            $aiResult = $this->aiResultService->saveResult(
                $userId,
                'diagram',
                'Diagram Generation',
                'AI-generated diagram',
                $inputData,
                [
                    'microservice_job_id' => $microserviceJobId,
                    'status' => 'queued',
                    'diagram_type' => $inputData['diagram_type'],
                    'prompt' => $inputData['prompt'],
                    'output_format' => $inputData['output_format'] ?? 'svg'
                ],
                [
                    'diagram_type' => $inputData['diagram_type'],
                    'output_format' => $inputData['output_format'] ?? 'svg',
                    'language' => $inputData['language'] ?? 'en',
                    'microservice_url' => $this->microserviceUrl
                ]
            );

            if (!$aiResult['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to save diagram result: ' . $aiResult['error']
                ];
            }

            Log::info('AIDiagramService: Diagram generation job created', [
                'ai_result_id' => $aiResult['ai_result']->id,
                'microservice_job_id' => $microserviceJobId
            ]);

            return [
                'success' => true,
                'ai_result_id' => $aiResult['ai_result']->id,
                'microservice_job_id' => $microserviceJobId,
                'status' => $responseData['status'] ?? 'queued'
            ];

        } catch (\Exception $e) {
            Log::error('AIDiagramService: Error generating diagram', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);

            return [
                'success' => false,
                'error' => 'Failed to generate diagram: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check job status on microservice
     * 
     * @param string $microserviceJobId Job ID from microservice
     * @return array Status information
     */
    public function checkJobStatus(string $microserviceJobId)
    {
        try {
            $headers = [
                'Accept' => 'application/json',
                'X-API-Key' => $this->microserviceApiKey
            ];

            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->get($this->microserviceUrl . '/status/' . $microserviceJobId);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to check job status: ' . $response->status()
                ];
            }

            $responseData = $response->json();

            return [
                'success' => true,
                'job_id' => $microserviceJobId,
                'status' => $responseData['status'] ?? 'unknown',
                'error' => $responseData['error'] ?? null
            ];

        } catch (\Exception $e) {
            Log::error('AIDiagramService: Error checking job status', [
                'error' => $e->getMessage(),
                'job_id' => $microserviceJobId
            ]);

            return [
                'success' => false,
                'error' => 'Failed to check job status: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get job result from microservice and download image
     * 
     * @param string $microserviceJobId Job ID from microservice
     * @param int $aiResultId AI Result ID to update
     * @return array Result with downloaded image URL
     */
    public function getJobResult(string $microserviceJobId, int $aiResultId)
    {
        try {
            $headers = [
                'Accept' => 'application/json',
                'X-API-Key' => $this->microserviceApiKey
            ];

            // Get result from microservice
            $response = Http::timeout(30)
                ->withHeaders($headers)
                ->get($this->microserviceUrl . '/result/' . $microserviceJobId);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to get job result: ' . $response->status()
                ];
            }

            $responseData = $response->json();
            $status = $responseData['status'] ?? 'unknown';

            if ($status === 'processing') {
                return [
                    'success' => false,
                    'status' => 'processing',
                    'message' => 'Job still processing'
                ];
            }

            if ($status === 'failed') {
                return [
                    'success' => false,
                    'status' => 'failed',
                    'error' => $responseData['error'] ?? 'Job failed'
                ];
            }

            if ($status !== 'completed') {
                return [
                    'success' => false,
                    'status' => $status,
                    'message' => 'Job not completed yet'
                ];
            }

            $downloadUrl = $responseData['download_url'] ?? null;

            if (!$downloadUrl) {
                return [
                    'success' => false,
                    'error' => 'No download URL in result'
                ];
            }

            // Get output format from AI result metadata
            $aiResult = AIResult::find($aiResultId);
            $outputFormat = 'svg'; // Default
            if ($aiResult && isset($aiResult->metadata['output_format'])) {
                $outputFormat = $aiResult->metadata['output_format'];
            } elseif ($aiResult && isset($aiResult->result_data['output_format'])) {
                $outputFormat = $aiResult->result_data['output_format'];
            }

            // Download image from microservice
            $imageData = $this->downloadAndStoreImage($downloadUrl, $microserviceJobId, $aiResultId, $outputFormat);

            if (!$imageData['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to download image: ' . $imageData['error']
                ];
            }

            // Update AI result with image information
            $aiResult = AIResult::find($aiResultId);
            if ($aiResult) {
                $resultData = $aiResult->result_data ?? [];
                $resultData['image_path'] = $imageData['path'];
                $resultData['image_url'] = $imageData['url'];
                $resultData['image_filename'] = $imageData['filename'];
                $resultData['status'] = 'completed';
                $resultData['download_url'] = $downloadUrl;
                $resultData['output_format'] = $outputFormat;

                $aiResult->update([
                    'result_data' => $resultData,
                    'metadata' => array_merge($aiResult->metadata ?? [], [
                        'image_downloaded_at' => now()->toISOString(),
                        'image_size' => $imageData['size'] ?? 0,
                        'output_format' => $outputFormat
                    ])
                ]);
            }

            Log::info('AIDiagramService: Diagram image downloaded and stored', [
                'ai_result_id' => $aiResultId,
                'microservice_job_id' => $microserviceJobId,
                'image_path' => $imageData['path']
            ]);

            return [
                'success' => true,
                'status' => 'completed',
                'image_path' => $imageData['path'],
                'image_url' => $imageData['url'],
                'image_filename' => $imageData['filename'],
                'download_url' => $downloadUrl
            ];

        } catch (\Exception $e) {
            Log::error('AIDiagramService: Error getting job result', [
                'error' => $e->getMessage(),
                'job_id' => $microserviceJobId
            ]);

            return [
                'success' => false,
                'error' => 'Failed to get job result: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Download image from microservice and store in Laravel storage
     * 
     * @param string $downloadUrl Download URL from microservice
     * @param string $jobId Job ID for filename
     * @param int $aiResultId AI Result ID
     * @param string $outputFormat Output format (svg, pdf, png) - default: "svg"
     * @return array Storage information
     */
    private function downloadAndStoreImage(string $downloadUrl, string $jobId, int $aiResultId, string $outputFormat = 'svg')
    {
        try {
            // Download image from microservice (public endpoint, no auth needed)
            $response = Http::timeout(60)->get($downloadUrl);

            if (!$response->successful()) {
                throw new \Exception('Failed to download image: ' . $response->status());
            }

            // Determine file extension based on output format
            $extension = match($outputFormat) {
                'pdf' => 'pdf',
                'png' => 'png',
                'svg' => 'svg',
                default => 'svg'
            };

            // Generate unique filename
            $filename = 'diagram_' . substr($jobId, 0, 8) . '_' . $aiResultId . '.' . $extension;
            $storagePath = 'diagrams/' . date('Y-m-d') . '/' . $filename;

            // Store image in Laravel storage
            Storage::disk('public')->put($storagePath, $response->body());

            // Get file size
            $fileSize = strlen($response->body());

            // Generate public URL
            $publicUrl = Storage::disk('public')->url($storagePath);

            Log::info('AIDiagramService: Image downloaded and stored', [
                'storage_path' => $storagePath,
                'file_size' => $fileSize,
                'public_url' => $publicUrl
            ]);

            return [
                'success' => true,
                'path' => $storagePath,
                'url' => $publicUrl,
                'filename' => $filename,
                'size' => $fileSize
            ];

        } catch (\Exception $e) {
            Log::error('AIDiagramService: Error downloading image', [
                'error' => $e->getMessage(),
                'download_url' => $downloadUrl
            ]);

            return [
                'success' => false,
                'error' => 'Failed to download image: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if microservice is available
     * 
     * @return bool True if service is available
     */
    public function isMicroserviceAvailable()
    {
        try {
            $response = Http::timeout(5)->get($this->microserviceUrl . '/health');
            $responseData = $response->json();
            return $response->successful() && ($responseData['status'] ?? '') === 'healthy';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get supported diagram types
     * 
     * @return array List of supported diagram types
     */
    public function getSupportedDiagramTypes()
    {
        return [
            // Graph-based diagrams
            'flowchart',
            'sequence',
            'class',
            'state',
            'er',
            'user_journey',
            'block',
            'mindmap',
            // Chart-based diagrams
            'pie',
            'quadrant',
            'timeline',
            'sankey',
            'xy'
        ];
    }
}

