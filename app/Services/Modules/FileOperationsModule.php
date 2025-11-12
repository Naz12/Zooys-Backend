<?php

namespace App\Services\Modules;

use App\Services\DocumentConverterService;
use App\Services\PdfOperationsService;
use Illuminate\Support\Facades\Log;

class FileOperationsModule
{
    private $documentConverterService;
    private $pdfOperationsService;

    public function __construct(
        DocumentConverterService $documentConverterService,
        PdfOperationsService $pdfOperationsService
    ) {
        $this->documentConverterService = $documentConverterService;
        $this->pdfOperationsService = $pdfOperationsService;
    }

    /**
     * Convert document to target format
     * 
     * @param string $filePath Path to the file
     * @param string $targetFormat Target format (pdf, png, jpg, docx, txt, html)
     * @param array $options Conversion options
     * @return array Conversion result with job_id
     */
    public function convertDocument(string $filePath, string $targetFormat, array $options = [])
    {
        try {
            Log::info('FileOperationsModule: Converting document', [
                'target_format' => $targetFormat,
                'file' => basename($filePath)
            ]);

            $result = $this->documentConverterService->convertDocument($filePath, $targetFormat, $options);

            return [
                'success' => true,
                'job_id' => $result['job_id'] ?? null,
                'data' => $result
            ];

        } catch (\Exception $e) {
            Log::error('FileOperationsModule: Document conversion failed', [
                'error' => $e->getMessage(),
                'target_format' => $targetFormat
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract content from document
     * 
     * @param string $filePath Path to the file
     * @param array $options Extraction options
     * @return array Extraction result with job_id
     */
    public function extractContent(string $filePath, array $options = [])
    {
        try {
            Log::info('FileOperationsModule: Extracting content', [
                'file' => basename($filePath)
            ]);

            $result = $this->documentConverterService->extractContent($filePath, $options);

            return [
                'success' => true,
                'job_id' => $result['job_id'] ?? null,
                'data' => $result
            ];

        } catch (\Exception $e) {
            Log::error('FileOperationsModule: Content extraction failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Start PDF operation (merge, split, compress, etc.)
     * 
     * @param string $operation Operation type (merge, split, compress, watermark, etc.)
     * @param array $filePaths Array of file paths
     * @param array $params Operation-specific parameters
     * @return array Operation result with job_id
     */
    public function startPdfOperation(string $operation, array $filePaths, array $params = [])
    {
        try {
            Log::info('FileOperationsModule: Starting PDF operation', [
                'operation' => $operation,
                'file_count' => count($filePaths)
            ]);

            $result = $this->pdfOperationsService->startOperation($operation, $filePaths, $params);

            return [
                'success' => true,
                'job_id' => $result['job_id'] ?? null,
                'data' => $result
            ];

        } catch (\Exception $e) {
            Log::error('FileOperationsModule: PDF operation failed', [
                'error' => $e->getMessage(),
                'operation' => $operation
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get PDF operation status
     * 
     * @param string $operation Operation type
     * @param string $jobId Job ID
     * @return array Job status
     */
    public function getPdfOperationStatus(string $operation, string $jobId)
    {
        return $this->pdfOperationsService->getStatus($operation, $jobId);
    }

    /**
     * Get PDF operation result
     * 
     * @param string $operation Operation type
     * @param string $jobId Job ID
     * @return array Operation result with download URLs
     */
    public function getPdfOperationResult(string $operation, string $jobId)
    {
        return $this->pdfOperationsService->getResult($operation, $jobId);
    }

    /**
     * Get conversion capabilities
     * 
     * @return array Supported formats and capabilities
     */
    public function getConversionCapabilities()
    {
        try {
            return $this->documentConverterService->getCapabilities();
        } catch (\Exception $e) {
            Log::error('FileOperationsModule: Failed to get capabilities', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get extraction capabilities
     * 
     * @return array Supported extraction options
     */
    public function getExtractionCapabilities()
    {
        try {
            return $this->documentConverterService->getExtractionCapabilities();
        } catch (\Exception $e) {
            Log::error('FileOperationsModule: Failed to get extraction capabilities', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get supported PDF operations
     * 
     * @return array List of supported operations
     */
    public function getSupportedPdfOperations()
    {
        return [
            'merge', 'split', 'compress', 'watermark', 'page_numbers',
            'annotate', 'protect', 'unlock', 'preview', 'batch', 'edit_pdf'
        ];
    }

    /**
     * Get supported input formats
     * 
     * @return array List of supported input formats
     */
    public function getSupportedInputFormats()
    {
        return ['pdf', 'docx', 'doc', 'jpg', 'jpeg', 'png', 'gif', 'html', 'txt'];
    }

    /**
     * Get supported output formats
     * 
     * @return array List of supported output formats
     */
    public function getSupportedOutputFormats()
    {
        return ['pdf', 'png', 'jpg', 'jpeg', 'docx', 'txt', 'html'];
    }

    /**
     * Check if the file operations microservice is available
     * 
     * @return bool True if service is available
     */
    public function isAvailable()
    {
        try {
            $health = $this->documentConverterService->checkHealth();
            return isset($health['status']) && $health['status'] === 'healthy';
        } catch (\Exception $e) {
            return false;
        }
    }
}










