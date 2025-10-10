<?php

namespace App\Services;

use App\Models\AIResult;
use App\Services\OpenAIService;
use App\Services\AIResultService;
use App\Services\Modules\ContentExtractionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class AIPresentationService
{
    private $openAIService;
    private $aiResultService;
    private $contentExtractionService;
    private $microserviceUrl;

    public function __construct(
        OpenAIService $openAIService,
        AIResultService $aiResultService,
        ContentExtractionService $contentExtractionService
    ) {
        $this->openAIService = $openAIService;
        $this->aiResultService = $aiResultService;
        $this->contentExtractionService = $contentExtractionService;
        $this->microserviceUrl = config('services.powerpoint_microservice.url', 'http://localhost:8001');
    }

    /**
     * Generate presentation outline from user input
     */
    public function generateOutline($inputData, $userId)
    {
        try {
            Log::info('Starting presentation outline generation', [
                'user_id' => $userId,
                'input_type' => $inputData['input_type'] ?? 'text'
            ]);

            // Extract content based on input type
            $content = $this->extractContent($inputData);
            
            if (!$content['success']) {
                return [
                    'success' => false,
                    'error' => $content['error']
                ];
            }

            // Generate presentation outline using AI
            $outline = $this->generateAIPresentationOutline($content['content'], $inputData);

            if (!$outline['success']) {
                return [
                    'success' => false,
                    'error' => $outline['error']
                ];
            }

            // Save initial result
            $aiResult = $this->aiResultService->saveResult(
                $userId,
                'presentation',
                $outline['data']['title'],
                'AI-generated presentation outline',
                $inputData,
                $outline['data'],
                [
                    'step' => 'outline_generated',
                    'input_type' => $inputData['input_type'] ?? 'text',
                    'language' => $inputData['language'] ?? 'English',
                    'tone' => $inputData['tone'] ?? 'Professional',
                    'length' => $inputData['length'] ?? 'Medium',
                    'model' => $inputData['model'] ?? 'Basic Model'
                ]
            );

            if (!$aiResult['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to save presentation outline: ' . $aiResult['error']
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'outline' => $outline['data'],
                    'ai_result_id' => $aiResult['ai_result']->id
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Presentation outline generation failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'input_data' => $inputData
            ]);

            return [
                'success' => false,
                'error' => 'Failed to generate presentation outline: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update presentation outline with user modifications
     */
    public function updateOutline($aiResultId, $updatedOutline, $userId)
    {
        try {
            Log::info('Updating presentation outline', [
                'ai_result_id' => $aiResultId,
                'user_id' => $userId
            ]);

            // Use flexible lookup for public access
            $aiResult = AIResult::where('id', $aiResultId)
                ->where('tool_type', 'presentation')
                ->first();
            
            // If not found and we have a specific user_id, try with user_id filter
            if (!$aiResult && $userId) {
                $aiResult = AIResult::where('id', $aiResultId)
                    ->where('user_id', $userId)
                    ->where('tool_type', 'presentation')
                    ->first();
            }

            if (!$aiResult) {
                return [
                    'success' => false,
                    'error' => 'Presentation not found'
                ];
            }

            // Update the result data with modified outline
            $resultData = $aiResult->result_data;
            $resultData['outline'] = $updatedOutline;
            $resultData['step'] = 'outline_modified';

            $aiResult->update([
                'result_data' => $resultData,
                'metadata' => array_merge($aiResult->metadata ?? [], [
                    'last_modified' => now()->toISOString()
                ])
            ]);

            return [
                'success' => true,
                'data' => [
                    'outline' => $updatedOutline,
                    'ai_result_id' => $aiResultId
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to update presentation outline', [
                'error' => $e->getMessage(),
                'ai_result_id' => $aiResultId,
                'user_id' => $userId
            ]);

            return [
                'success' => false,
                'error' => 'Failed to update outline: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate full content for presentation slides
     */
    public function generateContent($aiResultId, $userId)
    {
        try {
            // For public access, try to find by ID and tool_type first, then by user_id if needed
            $aiResult = AIResult::where('id', $aiResultId)
                ->where('tool_type', 'presentation')
                ->first();
            
            // If not found and we have a specific user_id, try with user_id filter
            if (!$aiResult && $userId) {
                $aiResult = AIResult::where('id', $aiResultId)
                    ->where('user_id', $userId)
                    ->where('tool_type', 'presentation')
                    ->first();
            }

            if (!$aiResult) {
                return [
                    'success' => false,
                    'error' => 'Presentation not found'
                ];
            }

            $outline = $aiResult->result_data;
            $slides = $outline['slides'] ?? [];

            // Generate content for all slides in a single API call
            $contentSlides = [];
            $slidesToProcess = [];
            
            // Separate title slides from content slides
            foreach ($slides as $slide) {
                if ($slide['slide_type'] === 'title') {
                    $contentSlides[] = $slide;
                } else {
                    $slidesToProcess[] = $slide;
                }
            }
            
            // Generate content for all content slides in ONE API call
            if (!empty($slidesToProcess)) {
                $allContent = $this->generateAllSlideContent($slidesToProcess, $outline['title']);
                
                foreach ($slidesToProcess as $index => $slide) {
                    $content = $allContent[$index] ?? $this->getFallbackContent($slide);
                    $contentSlides[] = array_merge($slide, ['content' => $content]);
                }
            }

            // Update the result with full content
            $resultData = $aiResult->result_data;
            $resultData['slides'] = $contentSlides;
            $resultData['step'] = 'content_generated';

            $aiResult->update([
                'result_data' => $resultData,
                'metadata' => array_merge($aiResult->metadata ?? [], [
                    'content_generated_at' => now()->toISOString()
                ])
            ]);

            return [
                'success' => true,
                'data' => [
                    'slides' => $contentSlides,
                    'ai_result_id' => $aiResultId
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Content generation failed', [
                'error' => $e->getMessage(),
                'ai_result_id' => $aiResultId,
                'user_id' => $userId
            ]);

            return [
                'success' => false,
                'error' => 'Failed to generate content: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate content for all slides in a single API call
     */
    private function generateAllSlideContent($slides, $presentationTitle)
    {
        $slideData = [];
        foreach ($slides as $index => $slide) {
            $slideData[] = [
                'index' => $index,
                'header' => $slide['header'],
                'subheaders' => $slide['subheaders'] ?? [],
                'slide_type' => $slide['slide_type'] ?? 'content'
            ];
        }

        $prompt = "Generate detailed content for ALL slides in this presentation titled '{$presentationTitle}'.

Complete Slide Structure:
" . json_encode($slideData, JSON_PRETTY_PRINT) . "

For each slide, provide:
1. 3-5 detailed bullet points that expand on each key point
2. Specific examples, data, or insights where relevant
3. Professional, engaging content suitable for a business presentation
4. Keep each bullet point concise but informative (1-2 sentences)
5. Ensure content flows well between slides and maintains consistency

Format the response as a JSON object with a 'slides' field containing an array of objects, each with 'index' and 'content' fields. The content should be an array of bullet points.

Example format:
{
  \"slides\": [
    {
      \"index\": 0,
      \"content\": [
        \"• First bullet point with detailed information\",
        \"• Second bullet point with examples\",
        \"• Third bullet point with insights\"
      ]
    }
  ]
}";

        $response = $this->openAIService->generateResponse($prompt, 'gpt-3.5-turbo');

        if (empty($response) || strpos($response, 'Sorry, I was unable') === 0) {
            // Return fallback content for all slides
            $fallbackContent = [];
            foreach ($slides as $index => $slide) {
                $fallbackContent[$index] = $this->getFallbackContent($slide);
            }
            return $fallbackContent;
        }

        $parsed = json_decode($response, true);
        if ($parsed && isset($parsed['slides']) && is_array($parsed['slides'])) {
            $result = [];
            foreach ($parsed['slides'] as $slideContent) {
                if (isset($slideContent['index']) && isset($slideContent['content'])) {
                    $result[$slideContent['index']] = $slideContent['content'];
                }
            }
            return $result;
        }

        // Fallback if parsing fails
        $fallbackContent = [];
        foreach ($slides as $index => $slide) {
            $fallbackContent[$index] = $this->getFallbackContent($slide);
        }
        return $fallbackContent;
    }

    /**
     * Get fallback content for a slide
     */
    private function getFallbackContent($slide)
    {
        $header = $slide['header'];
        $subheaders = $slide['subheaders'] ?? [];
        
        // Create more specific fallback content based on the slide header
        $specificContent = [];
        
        // Add the subheaders as bullet points
        foreach ($subheaders as $subheader) {
            $specificContent[] = "• " . $subheader;
        }
        
        // Add topic-specific content based on common slide types
        if (stripos($header, 'history') !== false) {
            $specificContent[] = "• Historical significance and development timeline";
            $specificContent[] = "• Key events and milestones that shaped the area";
        } elseif (stripos($header, 'economic') !== false) {
            $specificContent[] = "• Economic impact and contribution to regional development";
            $specificContent[] = "• Key industries and business opportunities";
        } elseif (stripos($header, 'infrastructure') !== false) {
            $specificContent[] = "• Transportation networks and connectivity";
            $specificContent[] = "• Public services and utilities available";
        } elseif (stripos($header, 'tourist') !== false) {
            $specificContent[] = "• Popular destinations and attractions";
            $specificContent[] = "• Cultural sites and recreational activities";
        } else {
            $specificContent[] = "• Important aspects and key features";
            $specificContent[] = "• Current status and future potential";
        }
        
        return $specificContent;
    }

    /**
     * Generate content for a single slide using OpenAI
     */
    private function generateSlideContent($slide, $presentationTitle)
    {
        $header = $slide['header'];
        $subheaders = $slide['subheaders'] ?? [];
        $slideType = $slide['slide_type'] ?? 'content';

        $prompt = "Generate detailed, specific content for a presentation slide about '{$header}' in a presentation titled '{$presentationTitle}'.

Slide Type: {$slideType}
Key Points to Cover: " . implode(', ', $subheaders) . "

IMPORTANT: Generate REAL, SPECIFIC content about the topic. Do NOT use generic placeholder text like 'Additional details and insights' or 'Key takeaways and important information'.

Please provide:
1. 4-6 detailed bullet points with SPECIFIC information about the topic
2. Real facts, examples, statistics, or concrete details where relevant
3. Professional, engaging content suitable for a business presentation
4. Each bullet point should be informative and specific (2-3 sentences)
5. Focus on the actual topic - be specific and factual

Format the response as a JSON object with a 'content' field containing an array of bullet points.";

        $response = $this->openAIService->generateResponse($prompt, 'gpt-3.5-turbo');

        if (empty($response) || strpos($response, 'Sorry, I was unable') === 0) {
            // Fallback content if OpenAI fails
            return [
                "• " . implode("\n• ", $subheaders),
                "• Additional details and insights about {$header}",
                "• Key takeaways and important information",
                "• Professional presentation content"
            ];
        }

        // Try to parse JSON response
        $parsed = json_decode($response, true);
        if ($parsed && isset($parsed['content']) && is_array($parsed['content'])) {
            return $parsed['content'];
        }

        // If not JSON, split by lines and format as bullet points
        $lines = array_filter(array_map('trim', explode("\n", $response)));
        $bulletPoints = [];
        
        foreach ($lines as $line) {
            if (!empty($line)) {
                // Add bullet point if not already present
                $bulletPoints[] = (strpos($line, '•') === 0 || strpos($line, '-') === 0) ? $line : "• " . $line;
            }
        }

        return !empty($bulletPoints) ? $bulletPoints : [
            "• " . implode("\n• ", $subheaders),
            "• Additional details and insights about {$header}",
            "• Key takeaways and important information"
        ];
    }

    /**
     * Generate PowerPoint presentation using Python
     */
    public function generatePowerPoint($aiResultId, $templateData, $userId)
    {
        try {
            Log::info('Starting PowerPoint generation', [
                'ai_result_id' => $aiResultId,
                'user_id' => $userId,
                'template' => $templateData['template'] ?? 'default'
            ]);

            // Use flexible lookup for public access
            $aiResult = AIResult::where('id', $aiResultId)
                ->where('tool_type', 'presentation')
                ->first();
            
            // If not found and we have a specific user_id, try with user_id filter
            if (!$aiResult && $userId) {
                $aiResult = AIResult::where('id', $aiResultId)
                    ->where('user_id', $userId)
                    ->where('tool_type', 'presentation')
                    ->first();
            }

            if (!$aiResult) {
                return [
                    'success' => false,
                    'error' => 'Presentation not found'
                ];
            }

            // Validate result data
            if (!$aiResult->result_data || !is_array($aiResult->result_data)) {
                return [
                    'success' => false,
                    'error' => 'Invalid presentation data. Please regenerate the outline.'
                ];
            }

            // Always use the most up-to-date content from the database
            // This ensures we use the generated content instead of just the outline
            $resultData = $aiResult->result_data;
            
            // Prepare data for Python script
            $pythonData = [
                'outline' => $resultData,
                'template' => $templateData['template'] ?? 'corporate_blue',
                'color_scheme' => $templateData['color_scheme'] ?? 'blue',
                'font_style' => $templateData['font_style'] ?? 'modern',
                'user_id' => $userId,
                'ai_result_id' => $aiResultId
            ];

            Log::info('Python data prepared', [
                'template_data' => $templateData,
                'python_data' => $pythonData,
                'result_data_keys' => array_keys($aiResult->result_data ?? []),
                'result_data' => $aiResult->result_data,
                'slides_count' => count($resultData['slides'] ?? []),
                'first_slide_content' => $resultData['slides'][0]['content'] ?? 'No content',
                'first_slide_subheaders' => $resultData['slides'][0]['subheaders'] ?? 'No subheaders'
            ]);

            // Generate PowerPoint using Python script
            $powerPointResult = $this->generatePowerPointWithPython($pythonData);

            if (!$powerPointResult['success']) {
                return [
                    'success' => false,
                    'error' => $powerPointResult['error']
                ];
            }

            // Update AI result with PowerPoint file
            $resultData = $aiResult->result_data;
            $resultData['powerpoint_file'] = $powerPointResult['file_path'];
            $resultData['step'] = 'powerpoint_generated';
            $resultData['template_used'] = $templateData['template'] ?? 'corporate_blue';

            $aiResult->update([
                'result_data' => $resultData,
                'metadata' => array_merge($aiResult->metadata ?? [], [
                    'template' => $templateData['template'] ?? 'corporate_blue',
                    'color_scheme' => $templateData['color_scheme'] ?? 'blue',
                    'font_style' => $templateData['font_style'] ?? 'modern',
                    'generated_at' => now()->toISOString()
                ])
            ]);

            return [
                'success' => true,
                'data' => [
                    'powerpoint_file' => $powerPointResult['file_path'],
                    'download_url' => $powerPointResult['download_url'],
                    'ai_result_id' => $aiResultId
                ]
            ];

        } catch (\Exception $e) {
            Log::error('PowerPoint generation failed', [
                'error' => $e->getMessage(),
                'ai_result_id' => $aiResultId,
                'user_id' => $userId
            ]);

            return [
                'success' => false,
                'error' => 'Failed to generate PowerPoint: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Extract content based on input type
     */
    private function extractContent($inputData)
    {
        $inputType = $inputData['input_type'] ?? 'text';

        try {
            switch ($inputType) {
                case 'text':
                    return [
                        'success' => true,
                        'content' => $inputData['topic'] ?? $inputData['content']
                    ];

                case 'file':
                    $extractionResult = $this->contentExtractionService->extractContent(
                        $inputData['file_path'],
                        'document',
                        ['file_type' => $inputData['file_type'] ?? 'pdf']
                    );
                    return $extractionResult;

                case 'url':
                    $extractionResult = $this->contentExtractionService->extractContent(
                        $inputData['url'],
                        'url'
                    );
                    return $extractionResult;

                case 'youtube':
                    $extractionResult = $this->contentExtractionService->extractContent(
                        $inputData['youtube_url'],
                        'youtube'
                    );
                    return $extractionResult;

                default:
                    return [
                        'success' => false,
                        'error' => 'Unsupported input type: ' . $inputType
                    ];
            }
        } catch (\Exception $e) {
            Log::error('Content extraction failed', [
                'error' => $e->getMessage(),
                'input_type' => $inputType
            ]);

            return [
                'success' => false,
                'error' => 'Failed to extract content: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate AI presentation outline
     */
    private function generateAIPresentationOutline($content, $inputData)
    {
        try {
            $language = $inputData['language'] ?? 'English';
            $tone = $inputData['tone'] ?? 'Professional';
            $length = $inputData['length'] ?? 'Medium';
            $model = $inputData['model'] ?? 'Basic Model';

            // Determine slide count based on length
            $slideCount = $this->getSlideCount($length);

            $prompt = "Create a {$tone} presentation outline for: {$content}

Requirements: {$slideCount} slides, {$language} language

Return JSON format:
{
  \"title\": \"Presentation Title\",
  \"slides\": [
    {
      \"slide_number\": 1,
      \"header\": \"Slide Title\",
      \"subheaders\": [\"Point 1\", \"Point 2\"],
      \"slide_type\": \"title\"
    }
  ],
  \"estimated_duration\": \"X minutes\",
  \"slide_count\": {$slideCount}
}";

            $aiResponse = $this->openAIService->generateResponse($prompt, 'gpt-3.5-turbo');

            // Parse AI response
            $outlineData = $this->parseAIOutlineResponse($aiResponse);

            if (!$outlineData) {
                return [
                    'success' => false,
                    'error' => 'Failed to parse AI response'
                ];
            }

            return [
                'success' => true,
                'data' => $outlineData
            ];

        } catch (\Exception $e) {
            Log::error('AI outline generation failed', [
                'error' => $e->getMessage(),
                'content_length' => strlen($content)
            ]);

            return [
                'success' => false,
                'error' => 'Failed to generate outline: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate PowerPoint using Python script
     */
    private function generatePowerPointWithPython($data)
    {
        try {
            $pythonScript = base_path('python/generate_presentation.py');
            $tempFile = tempnam(sys_get_temp_dir(), 'presentation_data_');
            
            // Write data to temporary file
            file_put_contents($tempFile, json_encode($data));

            // Execute Python script with timeout (only capture stdout for JSON)
            $command = "py \"{$pythonScript}\" \"{$tempFile}\"";
            Log::info('Executing Python command', [
                'command' => $command,
                'temp_file' => $tempFile,
                'data' => $data
            ]);
            
            // Use proc_open for better control and timeout handling
            $descriptorspec = array(
                0 => array("pipe", "r"),
                1 => array("pipe", "w"),
                2 => array("pipe", "w")
            );
            
            $process = proc_open($command, $descriptorspec, $pipes);
            
            if (is_resource($process)) {
                // Close input pipe
                fclose($pipes[0]);
                
                // Set timeout (30 seconds)
                $timeout = 30;
                $startTime = time();
                
                $output = '';
                $errorOutput = '';
                
                // Read output with timeout
                while (time() - $startTime < $timeout) {
                    $read = array($pipes[1], $pipes[2]);
                    $write = null;
                    $except = null;
                    
                    if (stream_select($read, $write, $except, 1) > 0) {
                        if (in_array($pipes[1], $read)) {
                            $chunk = fread($pipes[1], 8192);
                            if ($chunk !== false) {
                                $output .= $chunk;
                            }
                        }
                        if (in_array($pipes[2], $read)) {
                            $chunk = fread($pipes[2], 8192);
                            if ($chunk !== false) {
                                $errorOutput .= $chunk;
                            }
                        }
                    }
                    
                    // Check if process is still running
                    $status = proc_get_status($process);
                    if (!$status['running']) {
                        // Process finished, read any remaining output
                        while (!feof($pipes[1])) {
                            $chunk = fread($pipes[1], 8192);
                            if ($chunk !== false) {
                                $output .= $chunk;
                            }
                        }
                        while (!feof($pipes[2])) {
                            $chunk = fread($pipes[2], 8192);
                            if ($chunk !== false) {
                                $errorOutput .= $chunk;
                            }
                        }
                        break;
                    }
                }
                
                // Close pipes
                fclose($pipes[1]);
                fclose($pipes[2]);
                
                // Get exit code
                $exitCode = proc_close($process);
                
                Log::info('Python script execution completed', [
                    'exit_code' => $exitCode,
                    'output' => $output,
                    'error_output' => $errorOutput,
                    'execution_time' => time() - $startTime
                ]);
                
                if ($exitCode !== 0) {
                    return [
                        'success' => false,
                        'error' => 'Python script failed with exit code: ' . $exitCode . '. Error: ' . $errorOutput
                    ];
                }
                
                if (time() - $startTime >= $timeout) {
                    return [
                        'success' => false,
                        'error' => 'Python script execution timed out after ' . $timeout . ' seconds'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to start Python process'
                ];
            }

            // Clean up temp file
            unlink($tempFile);

            if (!$output) {
                return [
                    'success' => false,
                    'error' => 'Python script execution failed - no output'
                ];
            }

            // Parse Python script output (should be clean JSON now)
            Log::info('Raw Python output for parsing', [
                'output_length' => strlen($output),
                'output_preview' => substr($output, 0, 200),
                'output_end' => substr($output, -200)
            ]);
            
            $result = json_decode(trim($output), true);

            Log::info('Parsed Python result', [
                'result' => $result,
                'json_valid' => json_last_error() === JSON_ERROR_NONE
            ]);

            if (!$result || !$result['success']) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'PowerPoint generation failed - invalid JSON response'
                ];
            }

            return [
                'success' => true,
                'file_path' => $result['file_path'],
                'download_url' => $result['download_url']
            ];

        } catch (\Exception $e) {
            Log::error('Python PowerPoint generation failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'success' => false,
                'error' => 'Failed to execute Python script: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Parse AI outline response
     */
    private function parseAIOutlineResponse($response)
    {
        try {
            // Try to extract JSON from response
            $jsonStart = strpos($response, '{');
            $jsonEnd = strrpos($response, '}') + 1;

            if ($jsonStart === false || $jsonEnd === false) {
                return null;
            }

            $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart);
            $data = json_decode($jsonString, true);

            if (!$data || !isset($data['title']) || !isset($data['slides'])) {
                return null;
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('Failed to parse AI outline response', [
                'error' => $e->getMessage(),
                'response' => substr($response, 0, 500)
            ]);

            return null;
        }
    }

    /**
     * Get slide count based on length
     */
    private function getSlideCount($length)
    {
        switch (strtolower($length)) {
            case 'short':
                return 8;
            case 'medium':
                return 12;
            case 'long':
                return 18;
            default:
                return 12;
        }
    }

    /**
     * Get available templates
     */
    public function getAvailableTemplates()
    {
        return [
            'corporate_blue' => [
                'name' => 'Corporate Blue',
                'description' => 'Professional blue theme for business presentations',
                'color_scheme' => 'blue',
                'category' => 'business'
            ],
            'modern_white' => [
                'name' => 'Modern White',
                'description' => 'Clean white theme with modern typography',
                'color_scheme' => 'white',
                'category' => 'modern'
            ],
            'creative_colorful' => [
                'name' => 'Creative Colorful',
                'description' => 'Vibrant colors for creative presentations',
                'color_scheme' => 'colorful',
                'category' => 'creative'
            ],
            'minimalist_gray' => [
                'name' => 'Minimalist Gray',
                'description' => 'Simple gray theme for focused content',
                'color_scheme' => 'gray',
                'category' => 'minimalist'
            ],
            'academic_formal' => [
                'name' => 'Academic Formal',
                'description' => 'Formal theme for educational presentations',
                'color_scheme' => 'dark',
                'category' => 'academic'
            ],
            'tech_modern' => [
                'name' => 'Tech Modern',
                'description' => 'Modern tech theme with teal and green accents',
                'color_scheme' => 'teal',
                'category' => 'tech'
            ],
            'elegant_purple' => [
                'name' => 'Elegant Purple',
                'description' => 'Sophisticated purple theme for elegant presentations',
                'color_scheme' => 'purple',
                'category' => 'elegant'
            ],
            'professional_green' => [
                'name' => 'Professional Green',
                'description' => 'Professional green theme for corporate presentations',
                'color_scheme' => 'green',
                'category' => 'professional'
            ]
        ];
    }

    /**
     * Save presentation data (JSON) using microservice
     */
    public function savePresentationData($aiResultId, $presentationData, $userId)
    {
        try {
            Log::info('Saving presentation data with microservice', [
                'ai_result_id' => $aiResultId,
                'user_id' => $userId
            ]);

            // Prepare request data
            $requestData = [
                'presentation_data' => $presentationData,
                'user_id' => $userId,
                'ai_result_id' => $aiResultId
            ];

            // Call FastAPI microservice
            $response = Http::timeout(30)->post($this->microserviceUrl . '/save', $requestData);

            if ($response->successful()) {
                $result = $response->json();
                
                if ($result['success']) {
                    // Update AI result with presentation data - use flexible lookup
                    $aiResult = AIResult::where('id', $aiResultId)
                        ->where('tool_type', 'presentation')
                        ->first();
                    
                    // If not found and we have a specific user_id, try with user_id filter
                    if (!$aiResult && $userId) {
                        $aiResult = AIResult::where('id', $aiResultId)
                            ->where('user_id', $userId)
                            ->where('tool_type', 'presentation')
                            ->first();
                    }

                    if ($aiResult) {
                        $aiResult->update([
                            'result_data' => $presentationData,
                            'metadata' => array_merge($aiResult->metadata ?? [], [
                                'saved_at' => now()->toISOString(),
                                'saved_by' => 'microservice',
                                'version' => ($aiResult->metadata['version'] ?? 0) + 1
                            ])
                        ]);
                    }

                    return [
                        'success' => true,
                        'data' => $result['data'],
                        'message' => $result['message']
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => $result['error'] ?? 'Microservice save failed'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => 'Microservice communication failed: ' . $response->body()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Microservice save failed', [
                'error' => $e->getMessage(),
                'ai_result_id' => $aiResultId,
                'user_id' => $userId
            ]);

            return [
                'success' => false,
                'error' => 'Save failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Call FastAPI microservice
     */
    private function callMicroservice($url, $data)
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                Log::error('Microservice cURL error', ['error' => $error, 'url' => $url]);
                return [
                    'success' => false,
                    'error' => 'cURL error: ' . $error
                ];
            }

            if ($httpCode !== 200) {
                Log::error('Microservice HTTP error', [
                    'http_code' => $httpCode,
                    'response' => $response,
                    'url' => $url
                ]);
                return [
                    'success' => false,
                    'error' => 'HTTP error ' . $httpCode . ': ' . $response
                ];
            }

            $decodedResponse = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Microservice JSON decode error', [
                    'json_error' => json_last_error_msg(),
                    'response' => $response
                ]);
                return [
                    'success' => false,
                    'error' => 'Invalid JSON response from microservice'
                ];
            }

            return $decodedResponse;

        } catch (\Exception $e) {
            Log::error('Microservice call exception', [
                'error' => $e->getMessage(),
                'url' => $url
            ]);
            return [
                'success' => false,
                'error' => 'Microservice call failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Export presentation to PowerPoint (on-demand)
     */
    public function exportPresentationToPowerPoint($aiResultId, $presentationData, $userId, $templateData = null)
    {
        try {
            Log::info('Exporting presentation to PowerPoint using FastAPI microservice', [
                'ai_result_id' => $aiResultId,
                'user_id' => $userId
            ]);

            // Get the AI result to access the outline data - use flexible lookup
            $aiResult = AIResult::where('id', $aiResultId)
                ->where('tool_type', 'presentation')
                ->first();
            
            // If not found and we have a specific user_id, try with user_id filter
            if (!$aiResult && $userId) {
                $aiResult = AIResult::where('id', $aiResultId)
                    ->where('user_id', $userId)
                    ->where('tool_type', 'presentation')
                    ->first();
            }

            if (!$aiResult) {
                return [
                    'success' => false,
                    'error' => 'Presentation not found'
                ];
            }

            // Always use the most up-to-date content from the database
            // This ensures we use the generated content instead of just the outline
            $slides = $aiResult->result_data['slides'] ?? [];
            
            // If frontend sent updated slides, merge them with the database content
            if (isset($presentationData['slides']) && is_array($presentationData['slides'])) {
                // Merge frontend updates with database content, prioritizing database content for full content
                foreach ($presentationData['slides'] as $frontendSlide) {
                    $slideIndex = $frontendSlide['slide_number'] ?? null;
                    if ($slideIndex !== null) {
                        // Find matching slide in database
                        foreach ($slides as &$dbSlide) {
                            if (($dbSlide['slide_number'] ?? 0) == $slideIndex) {
                                // Update with frontend changes but preserve generated content
                                $dbSlide = array_merge($dbSlide, $frontendSlide);
                                // If frontend slide has content, use it; otherwise keep database content
                                if (!isset($frontendSlide['content']) && isset($dbSlide['content'])) {
                                    // Keep the generated content from database
                                }
                                break;
                            }
                        }
                    }
                }
            }
            
            // Prepare data for FastAPI microservice
            $requestData = [
                'presentation_data' => [
                    'title' => $aiResult->result_data['title'] ?? 'Presentation',
                    'slides' => $slides
                ],
                'user_id' => $userId,
                'ai_result_id' => $aiResultId,
                'template' => $templateData['template'] ?? 'corporate_blue',
                'color_scheme' => $templateData['color_scheme'] ?? 'blue',
                'font_style' => $templateData['font_style'] ?? 'modern'
            ];
            
            // Log the data being sent for debugging
            Log::info('Data being sent to microservice', [
                'ai_result_id' => $aiResultId,
                'user_id' => $userId,
                'request_data' => $requestData,
                'slides_count' => count($slides),
                'first_slide_content' => $slides[0]['content'] ?? 'No content',
                'first_slide_subheaders' => $slides[0]['subheaders'] ?? 'No subheaders',
                'has_generated_content' => isset($slides[0]['content']) && !empty($slides[0]['content'])
            ]);

            // Call FastAPI microservice
            $microserviceUrl = config('services.presentation_microservice.url', 'http://localhost:8001');
            $response = $this->callMicroservice($microserviceUrl . '/export', $requestData);

            if (!$response['success']) {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Microservice call failed'
                ];
            }

            // Update AI result with export info
            $resultData = $aiResult->result_data;
            $resultData['powerpoint_file'] = $response['data']['file_path'];
            $resultData['exported_at'] = now()->toISOString();

            $aiResult->update([
                'result_data' => $resultData,
                'metadata' => array_merge($aiResult->metadata ?? [], [
                    'exported_at' => now()->toISOString(),
                    'exported_by' => 'fastapi_microservice',
                    'export_version' => ($aiResult->metadata['export_version'] ?? 0) + 1
                ])
            ]);

            return [
                'success' => true,
                'data' => $response['data'],
                'message' => 'Presentation exported successfully using FastAPI microservice'
            ];

        } catch (\Exception $e) {
            Log::error('Export failed', [
                'error' => $e->getMessage(),
                'ai_result_id' => $aiResultId,
                'user_id' => $userId
            ]);

            return [
                'success' => false,
                'error' => 'Export failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get presentation data for frontend editing
     */
    public function getPresentationData($aiResultId, $userId)
    {
        try {
            // Use flexible lookup for public access
            $aiResult = AIResult::where('id', $aiResultId)
                ->where('tool_type', 'presentation')
                ->first();
            
            // If not found and we have a specific user_id, try with user_id filter
            if (!$aiResult && $userId) {
                $aiResult = AIResult::where('id', $aiResultId)
                    ->where('user_id', $userId)
                    ->where('tool_type', 'presentation')
                    ->first();
            }

            if (!$aiResult) {
                return [
                    'success' => false,
                    'error' => 'Presentation not found'
                ];
            }

            // Return the presentation data for frontend editing
            return [
                'success' => true,
                'data' => $aiResult->result_data,
                'metadata' => $aiResult->metadata
            ];

        } catch (\Exception $e) {
            Log::error('Get presentation data failed', [
                'error' => $e->getMessage(),
                'ai_result_id' => $aiResultId,
                'user_id' => $userId
            ]);

            return [
                'success' => false,
                'error' => 'Failed to get presentation data: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if microservice is available
     */
    public function isMicroserviceAvailable()
    {
        try {
            $response = Http::timeout(5)->get($this->microserviceUrl . '/health');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
