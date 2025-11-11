<?php

namespace App\Services;

use App\Models\MathProblem;
use App\Models\MathSolution;
use App\Services\Modules\AIProcessingModule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class AIMathService
{
    private $aiProcessingModule;
    private $microserviceUrl;
    private $microserviceTimeout;
    private $microserviceApiKey;

    public function __construct(AIProcessingModule $aiProcessingModule)
    {
        $this->aiProcessingModule = $aiProcessingModule;
        $this->microserviceUrl = env('MATH_MICROSERVICE_URL', 'http://localhost:8002');
        $this->microserviceTimeout = env('MATH_MICROSERVICE_TIMEOUT', 60);
        $this->microserviceApiKey = env('MATH_MICROSERVICE_API_KEY');
    }

    /**
     * Solve a mathematical problem using the Python microservice
     */
    public function solveMathProblem($problemData, $userId)
    {
        try {
            // Create math problem record
            $mathProblem = MathProblem::create([
                'user_id' => $userId,
                'problem_text' => $problemData['problem_text'] ?? null,
                'problem_image' => $problemData['problem_image'] ?? null,
                'problem_type' => $problemData['problem_type'],
                'subject_area' => $problemData['subject_area'] ?? 'general',
                'difficulty_level' => $problemData['difficulty_level'] ?? 'intermediate',
                'metadata' => $problemData['metadata'] ?? []
            ]);

            // Solve using Python microservice only
            $solution = $this->solveWithMicroservice($mathProblem, $problemData);

            if (!$solution['success']) {
                return [
                    'success' => false,
                    'error' => $solution['error']
                ];
            }

            // Comprehensive data validation and sanitization
            $validatedData = $this->validateAndSanitizeSolutionData($solution);

            // Save solution to database with validated data
            $mathSolution = MathSolution::create([
                'math_problem_id' => $mathProblem->id,
                'solution_method' => $validatedData['method'],
                'step_by_step_solution' => $validatedData['step_by_step'],
                'final_answer' => $validatedData['final_answer'],
                'explanation' => $validatedData['explanation'],
                'verification' => $validatedData['verification'],
                'metadata' => $validatedData['metadata']
            ]);

            return [
                'success' => true,
                'math_problem' => $mathProblem,
                'math_solution' => $mathSolution,
                'solution_data' => $solution
            ];

        } catch (\Exception $e) {
            Log::error('AI Math Service Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to solve mathematical problem: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Solve problem using Python microservice
     * Supports both sync (text) and async (image) processing
     */
    private function solveWithMicroservice($mathProblem, $problemData)
    {
        try {
            // Prepare request data
            $requestData = [
                'problem_text' => $mathProblem->problem_text,
                'subject_area' => $mathProblem->subject_area,
                'difficulty_level' => $mathProblem->difficulty_level,
                'timeout_ms' => 30000, // 30 seconds
                'include_explanation' => true,
                'explanation_style' => 'educational'
            ];

            $hasImage = false;
            // Handle image if present
            if ($mathProblem->problem_image) {
                $imagePath = storage_path('app/public/' . $mathProblem->problem_image);
                if (file_exists($imagePath)) {
                    $imageData = base64_encode(file_get_contents($imagePath));
                    $requestData['problem_image'] = $imageData;
                    $requestData['image_type'] = 'auto';
                    unset($requestData['problem_text']); // Remove text when image is provided
                    $hasImage = true;
                }
            }

            // Prepare headers with API key
            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ];
            
            if ($this->microserviceApiKey) {
                $headers['X-API-Key'] = $this->microserviceApiKey;
            }

            // Make HTTP request to microservice
            $response = Http::timeout($this->microserviceTimeout)
                ->withHeaders($headers)
                ->post($this->microserviceUrl . '/explain', $requestData);

            if (!$response->successful()) {
                $errorBody = $response->body();
                $errorMessage = 'Microservice request failed: ' . $response->status();
                
                // Try to extract error message from response body
                if ($errorBody) {
                    $errorData = json_decode($errorBody, true);
                    if (isset($errorData['detail'])) {
                        $errorMessage = $errorData['detail'];
                    } elseif (isset($errorData['error']['message'])) {
                        $errorMessage = $errorData['error']['message'];
                    }
                }
                
                return [
                    'success' => false,
                    'error' => $errorMessage
                ];
            }

            $responseData = $response->json();

            // Check if this is an async job response (image processing)
            if (isset($responseData['job_id']) && $responseData['processing_mode'] === 'async') {
                // Poll for job completion
                return $this->pollJobResult($responseData['job_id']);
            }

            // Handle sync response (text processing)
            if (!$responseData['success']) {
                return [
                    'success' => false,
                    'error' => $responseData['error']['message'] ?? $responseData['detail'] ?? 'Microservice processing failed'
                ];
            }

            // Extract solution data from microservice response
            $solutionData = $responseData['solution'] ?? [];
            $explanationData = $responseData['explanation'] ?? null;

            return [
                'success' => true,
                'solution' => $solutionData['answer'] ?? $solutionData['result'] ?? 'No solution provided',
                'steps' => $this->formatMicroserviceSteps($solutionData['steps'] ?? []),
                'method' => $solutionData['method'] ?? 'unknown',
                'explanation' => $explanationData['content'] ?? null,
                'verification' => 'Solution verified by microservice',
                'metadata' => [
                    'solver_used' => $responseData['metadata']['solver_used'] ?? 'microservice',
                    'processing_time' => $responseData['metadata']['processing_time'] ?? 0,
                    'classification' => $responseData['classification'] ?? null,
                    'tokens_used' => $explanationData['tokens_used'] ?? 0,
                    'processing_mode' => $responseData['processing_mode'] ?? 'sync'
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Microservice request failed', [
                'error' => $e->getMessage(),
                'problem_id' => $mathProblem->id
            ]);

            return [
                'success' => false,
                'error' => 'Microservice request failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Poll job result for async processing
     */
    private function pollJobResult($jobId, $maxAttempts = 60, $intervalSeconds = 2)
    {
        $headers = [
            'Accept' => 'application/json'
        ];
        
        if ($this->microserviceApiKey) {
            $headers['X-API-Key'] = $this->microserviceApiKey;
        }

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            // Check job status
            $statusResponse = Http::timeout(10)
                ->withHeaders($headers)
                ->get($this->microserviceUrl . '/jobs/' . $jobId . '/status');

            if (!$statusResponse->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to check job status: ' . $statusResponse->status()
                ];
            }

            $statusData = $statusResponse->json();
            $status = $statusData['status'] ?? 'unknown';

            if ($status === 'completed') {
                // Get result
                $resultResponse = Http::timeout(10)
                    ->withHeaders($headers)
                    ->get($this->microserviceUrl . '/jobs/' . $jobId . '/result');

                if (!$resultResponse->successful()) {
                    return [
                        'success' => false,
                        'error' => 'Failed to get job result: ' . $resultResponse->status()
                    ];
                }

                $resultData = $resultResponse->json();
                $result = $resultData['result'] ?? [];

                // Extract solution data
                $solutionData = $result['solution'] ?? [];
                $explanationData = $result['explanation'] ?? null;

                return [
                    'success' => true,
                    'solution' => $solutionData['answer'] ?? $result['answer'] ?? 'No solution provided',
                    'steps' => $this->formatMicroserviceSteps($solutionData['steps'] ?? []),
                    'method' => $solutionData['method'] ?? $result['method'] ?? 'unknown',
                    'explanation' => $explanationData['content'] ?? null,
                    'verification' => 'Solution verified by microservice',
                    'metadata' => [
                        'solver_used' => $result['solver_used'] ?? 'microservice',
                        'processing_time' => $resultData['processing_time'] ?? 0,
                        'classification' => $result['classification'] ?? null,
                        'processing_mode' => 'async',
                        'job_id' => $jobId
                    ]
                ];
            } elseif ($status === 'failed') {
                return [
                    'success' => false,
                    'error' => $statusData['error'] ?? 'Job processing failed'
                ];
            }

            // Wait before next attempt
            sleep($intervalSeconds);
        }

        return [
            'success' => false,
            'error' => 'Job processing timeout after ' . ($maxAttempts * $intervalSeconds) . ' seconds'
        ];
    }

    /**
     * Format microservice steps for Laravel compatibility
     */
    private function formatMicroserviceSteps($steps)
    {
        $formattedSteps = [];
        
        foreach ($steps as $step) {
            $formattedSteps[] = [
                'step_number' => $step['step_number'] ?? $step['step'] ?? 1,
                'description' => $step['description'] ?? 'Step',
                'expression' => $step['expression'] ?? $step['result'] ?? '',
                'latex' => $step['latex'] ?? null,
                'confidence' => $step['confidence'] ?? 1.0
            ];
        }

        return $formattedSteps;
    }

    /**
     * Check if microservice is available
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
     * Comprehensive data validation and sanitization
     */
    private function validateAndSanitizeSolutionData($solution)
    {
        return [
            'method' => $this->sanitizeString($solution['method'] ?? 'mathematical_analysis'),
            'step_by_step' => $this->sanitizeString($solution['steps'] ?? 'No steps provided'),
            'final_answer' => $this->sanitizeString($solution['solution'] ?? 'No solution provided'),
            'explanation' => $this->sanitizeString($solution['explanation'] ?? 'No explanation provided'),
            'verification' => $this->sanitizeVerification($solution['verification'] ?? 'No verification provided'),
            'metadata' => $this->sanitizeMetadata($solution['metadata'] ?? [])
        ];
    }

    /**
     * Sanitize string data
     */
    private function sanitizeString($data)
    {
        if (is_array($data)) {
            return json_encode($data);
        }
        
        return is_string($data) ? trim($data) : (string)$data;
    }

    /**
     * Sanitize verification data
     */
    private function sanitizeVerification($verification)
    {
        if (is_array($verification)) {
            return implode(' ', array_map('trim', $verification));
        }
        
        return is_string($verification) ? trim($verification) : 'Verification not provided';
    }

    /**
     * Sanitize metadata
     */
    private function sanitizeMetadata($metadata)
    {
        if (!is_array($metadata)) {
            return [];
        }

        $sanitized = [];
        foreach ($metadata as $key => $value) {
            $sanitizedKey = is_string($key) ? trim($key) : (string)$key;
            $sanitizedValue = is_string($value) ? trim($value) : $value;
            $sanitized[$sanitizedKey] = $sanitizedValue;
        }

        return $sanitized;
    }

    /**
     * Get user's math problems with pagination and filters
     */
    public function getUserProblems($userId, $filters = [])
    {
        $query = MathProblem::where('user_id', $userId)
            ->with(['solutions' => function($q) {
                $q->orderBy('created_at', 'desc');
            }])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (!empty($filters['subject'])) {
            $query->where('subject_area', $filters['subject']);
        }

        if (!empty($filters['difficulty'])) {
            $query->where('difficulty_level', $filters['difficulty']);
        }

        // Paginate results
        $perPage = $filters['per_page'] ?? 15;
        return $query->paginate($perPage);
    }

    /**
     * Get specific math problem by ID for a user
     */
    public function getMathProblem($id, $userId)
    {
        return MathProblem::where('id', $id)
            ->where('user_id', $userId)
            ->with(['solutions' => function($q) {
                $q->orderBy('created_at', 'desc');
            }])
            ->firstOrFail();
    }

    /**
     * Get user's math statistics
     */
    public function getUserStats($userId)
    {
        $totalProblems = MathProblem::where('user_id', $userId)->count();
        $totalSolutions = MathSolution::whereHas('mathProblem', function($q) use ($userId) {
            $q->where('user_id', $userId);
        })->count();

        $subjectStats = MathProblem::where('user_id', $userId)
            ->selectRaw('subject_area, COUNT(*) as count')
            ->groupBy('subject_area')
            ->get()
            ->pluck('count', 'subject_area');

        $difficultyStats = MathProblem::where('user_id', $userId)
            ->selectRaw('difficulty_level, COUNT(*) as count')
            ->groupBy('difficulty_level')
            ->get()
            ->pluck('count', 'difficulty_level');

        $recentProblems = MathProblem::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'total_problems' => $totalProblems,
            'total_solutions' => $totalSolutions,
            'subject_stats' => $subjectStats,
            'difficulty_stats' => $difficultyStats,
            'recent_problems' => $recentProblems,
            'success_rate' => $totalProblems > 0 ? round(($totalSolutions / $totalProblems) * 100, 2) : 0
        ];
    }
}