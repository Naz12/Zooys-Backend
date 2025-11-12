<?php

namespace App\Services\Modules;

use App\Services\AIDiagramService;
use Illuminate\Support\Facades\Log;

class DiagramModule
{
    private $diagramService;

    public function __construct(AIDiagramService $diagramService)
    {
        $this->diagramService = $diagramService;
    }

    /**
     * Generate a diagram
     * 
     * @param array $inputData Input data including:
     *   - prompt: Description/instruction for the diagram
     *   - diagram_type: Type of diagram to generate
     *   - language: Language code (optional, default: "en")
     * @param int $userId User ID
     * @return array Response with job_id
     */
    public function generateDiagram(array $inputData, int $userId)
    {
        try {
            Log::info('DiagramModule: Generating diagram', [
                'diagram_type' => $inputData['diagram_type'] ?? 'unknown',
                'user_id' => $userId
            ]);

            $result = $this->diagramService->generateDiagram($inputData, $userId);

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Diagram generation failed');
            }

            Log::info('DiagramModule: Diagram generation job created', [
                'ai_result_id' => $result['ai_result_id'] ?? null,
                'microservice_job_id' => $result['microservice_job_id'] ?? null
            ]);

            return [
                'success' => true,
                'ai_result_id' => $result['ai_result_id'] ?? null,
                'microservice_job_id' => $result['microservice_job_id'] ?? null,
                'status' => $result['status'] ?? 'queued'
            ];

        } catch (\Exception $e) {
            Log::error('DiagramModule: Error generating diagram', [
                'error' => $e->getMessage(),
                'diagram_type' => $inputData['diagram_type'] ?? 'unknown'
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check job status
     * 
     * @param string $microserviceJobId Microservice job ID
     * @return array Status information
     */
    public function checkJobStatus(string $microserviceJobId)
    {
        return $this->diagramService->checkJobStatus($microserviceJobId);
    }

    /**
     * Get job result and download image
     * 
     * @param string $microserviceJobId Microservice job ID
     * @param int $aiResultId AI Result ID
     * @return array Result with image URL
     */
    public function getJobResult(string $microserviceJobId, int $aiResultId)
    {
        return $this->diagramService->getJobResult($microserviceJobId, $aiResultId);
    }

    /**
     * Get supported diagram types
     * 
     * @return array List of supported diagram types
     */
    public function getSupportedDiagramTypes()
    {
        return $this->diagramService->getSupportedDiagramTypes();
    }

    /**
     * Check if the diagram microservice is available
     * 
     * @return bool True if service is available
     */
    public function isAvailable()
    {
        try {
            return $this->diagramService->isMicroserviceAvailable();
        } catch (\Exception $e) {
            return false;
        }
    }
}

