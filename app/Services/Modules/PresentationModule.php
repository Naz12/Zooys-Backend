<?php

namespace App\Services\Modules;

use App\Services\AIPresentationService;
use Illuminate\Support\Facades\Log;

class PresentationModule
{
    private $presentationService;

    public function __construct(AIPresentationService $presentationService)
    {
        $this->presentationService = $presentationService;
    }

    /**
     * Generate presentation outline from user input
     * 
     * @param array $inputData Input data including:
     *   - input_type: Type of input (text, file, url, youtube)
     *   - content: Text content (if input_type is text)
     *   - file_id: File ID (if input_type is file)
     *   - url: URL (if input_type is url or youtube)
     *   - language: Presentation language (default: English)
     *   - tone: Presentation tone (default: Professional)
     *   - length: Presentation length (default: Medium)
     *   - model: AI model to use (optional)
     * @param int $userId User ID
     * @return array Presentation outline
     */
    public function generateOutline(array $inputData, int $userId)
    {
        try {
            Log::info('PresentationModule: Generating presentation outline', [
                'input_type' => $inputData['input_type'] ?? 'text',
                'user_id' => $userId
            ]);

            $result = $this->presentationService->generateOutline($inputData, $userId);

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Presentation outline generation failed');
            }

            Log::info('PresentationModule: Outline generated successfully', [
                'result_id' => $result['ai_result']['id'] ?? null
            ]);

            return [
                'success' => true,
                'result_id' => $result['ai_result']['id'] ?? null,
                'title' => $result['data']['title'] ?? null,
                'outline' => $result['data']['outline'] ?? [],
                'slides' => $result['data']['slides'] ?? [],
                'metadata' => $result['data']['metadata'] ?? []
            ];

        } catch (\Exception $e) {
            Log::error('PresentationModule: Error generating outline', [
                'error' => $e->getMessage(),
                'input_type' => $inputData['input_type'] ?? 'unknown'
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate full presentation with slides
     * 
     * @param array $inputData Input data (same as generateOutline)
     * @param int $userId User ID
     * @return array Complete presentation with slides
     */
    public function generatePresentation(array $inputData, int $userId)
    {
        try {
            Log::info('PresentationModule: Generating full presentation', [
                'input_type' => $inputData['input_type'] ?? 'text',
                'user_id' => $userId
            ]);

            $result = $this->presentationService->generatePresentation($inputData, $userId);

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Presentation generation failed');
            }

            return [
                'success' => true,
                'result_id' => $result['ai_result']['id'] ?? null,
                'title' => $result['data']['title'] ?? null,
                'slides' => $result['data']['slides'] ?? [],
                'file_path' => $result['data']['file_path'] ?? null,
                'download_url' => $result['data']['download_url'] ?? null,
                'metadata' => $result['data']['metadata'] ?? []
            ];

        } catch (\Exception $e) {
            Log::error('PresentationModule: Error generating presentation', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get supported input types
     * 
     * @return array List of supported input types
     */
    public function getSupportedInputTypes()
    {
        return ['text', 'file', 'url', 'youtube'];
    }

    /**
     * Get supported templates
     * 
     * @return array List of supported templates
     */
    public function getSupportedTemplates()
    {
        return ['corporate_blue', 'modern_white', 'creative_colorful', 'minimalist_gray', 'academic_formal'];
    }

    /**
     * Get supported languages
     * 
     * @return array List of supported languages
     */
    public function getSupportedLanguages()
    {
        return ['English', 'Spanish', 'French', 'German', 'Italian', 'Portuguese', 'Chinese', 'Japanese'];
    }

    /**
     * Get supported tones
     * 
     * @return array List of supported tones
     */
    public function getSupportedTones()
    {
        return ['Professional', 'Casual', 'Academic', 'Creative', 'Formal'];
    }

    /**
     * Check if the presentation microservice is available
     * 
     * @return bool True if service is available
     */
    public function isAvailable()
    {
        try {
            $microserviceUrl = env('PRESENTATION_MICROSERVICE_URL', 'http://localhost:8001');
            return !empty($microserviceUrl);
        } catch (\Exception $e) {
            return false;
        }
    }
}


















