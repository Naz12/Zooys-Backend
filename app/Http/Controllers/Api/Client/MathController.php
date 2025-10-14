<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tool;
use App\Models\History;
use App\Services\AIMathService;
use App\Services\FileUploadService;
use App\Services\AIResultService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MathController extends Controller
{
    protected $aiMathService;
    protected $fileUploadService;
    protected $aiResultService;

    public function __construct(
        AIMathService $aiMathService,
        FileUploadService $fileUploadService,
        AIResultService $aiResultService
    ) {
        $this->aiMathService = $aiMathService;
        $this->fileUploadService = $fileUploadService;
        $this->aiResultService = $aiResultService;
    }

    /**
     * Solve a mathematical problem
     */
    public function solve(Request $request)
    {
        $request->validate([
            'problem_text' => 'required_without:problem_image|string',
            'problem_image' => 'required_without:problem_text|image|max:10240',
            'subject_area' => 'nullable|string|in:algebra,geometry,calculus,statistics,trigonometry,arithmetic,maths',
            'difficulty_level' => 'nullable|string|in:beginner,intermediate,advanced'
        ]);

        $user = $request->user();
        $tool = Tool::where('slug', 'math')->first();

        try {
            $problemData = [
                'problem_type' => $request->hasFile('problem_image') ? 'image' : 'text',
                'subject_area' => $request->input('subject_area', 'general'),
                'difficulty_level' => $request->input('difficulty_level', 'intermediate'),
                'metadata' => []
            ];

            // Handle image upload using universal file upload system
            if ($request->hasFile('problem_image')) {
                $file = $request->file('problem_image');
                
                // Use universal file upload service
                $uploadResult = $this->fileUploadService->uploadFile($file, $user->id, [
                    'tool_type' => 'math',
                    'problem_type' => 'image',
                    'subject_area' => $request->input('subject_area', 'maths'),
                    'difficulty_level' => $request->input('difficulty_level', 'intermediate')
                ]);

                if (!$uploadResult['success']) {
                    return response()->json([
                        'error' => 'Failed to upload image: ' . $uploadResult['error']
                    ], 400);
                }

                $problemData['file_upload_id'] = $uploadResult['file_upload']->id;
                $problemData['problem_image'] = $uploadResult['file_upload']->file_path;
                $uploadResult = $uploadResult; // Store for response
            } else {
                $problemData['problem_text'] = $request->input('problem_text');
            }

            // Solve the mathematical problem
            $result = $this->aiMathService->solveMathProblem($problemData, $user->id);

            if (!$result['success']) {
                return response()->json([
                    'error' => $result['error']
                ], 500);
            }

            // Save AI result
            $aiResult = $this->aiResultService->saveResult(
                $user->id,
                'math',
                'Mathematical Problem Solution',
                'AI-generated mathematical solution with step-by-step explanation',
                $problemData,
                [
                    'solution' => $result['solution_data'],
                    'math_problem_id' => $result['math_problem']->id,
                    'math_solution_id' => $result['math_solution']->id
                ],
                [
                    'subject_area' => $problemData['subject_area'],
                    'difficulty_level' => $problemData['difficulty_level'],
                    'problem_type' => $problemData['problem_type']
                ],
                $problemData['file_upload_id'] ?? null
            );

            // Log usage
            if ($tool) {
                History::create([
                    'user_id' => $user->id,
                    'tool_id' => $tool->id,
                    'input' => $problemData['problem_text'] ?? 'Image uploaded',
                    'output' => $result['solution_data']['final_answer'],
                    'meta' => json_encode([
                        'subject_area' => $problemData['subject_area'],
                        'difficulty_level' => $problemData['difficulty_level'],
                        'problem_type' => $problemData['problem_type'],
                        'math_problem_id' => $result['math_problem']->id,
                        'math_solution_id' => $result['math_solution']->id,
                        'ai_result_id' => $aiResult['ai_result']->id
                    ])
                ]);
            }

            return response()->json([
                'math_problem' => [
                    'id' => $result['math_problem']->id,
                    'problem_text' => $result['math_problem']->problem_text,
                    'problem_image' => $result['math_problem']->image_url,
                    'file_url' => isset($problemData['file_upload_id']) ? $uploadResult['file_url'] : null,
                    'subject_area' => $result['math_problem']->subject_area,
                    'difficulty_level' => $result['math_problem']->difficulty_level,
                    'created_at' => $result['math_problem']->created_at
                ],
                'math_solution' => [
                    'id' => $result['math_solution']->id,
                    'solution_method' => $result['math_solution']->solution_method,
                    'step_by_step_solution' => $result['math_solution']->step_by_step_solution,
                    'final_answer' => $result['math_solution']->final_answer,
                    'explanation' => $result['math_solution']->explanation,
                    'verification' => $result['math_solution']->verification,
                    'created_at' => $result['math_solution']->created_at
                ],
                'ai_result' => [
                    'id' => $aiResult['ai_result']->id,
                    'title' => $aiResult['ai_result']->title,
                    'file_url' => $aiResult['ai_result']->file_url,
                    'created_at' => $aiResult['ai_result']->created_at
                ]
            ])->header('Access-Control-Allow-Origin', 'http://localhost:3000')
              ->header('Access-Control-Allow-Credentials', 'true');

        } catch (\Exception $e) {
            Log::error('Math Problem Solving Error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'problem_type' => $request->hasFile('problem_image') ? 'image' : 'text',
                'error_trace' => $e->getTraceAsString()
            ]);
            
            // Provide more specific error messages based on error type
            $errorMessage = 'Unable to solve mathematical problem at this time';
            $statusCode = 500;
            
            if (strpos($e->getMessage(), 'Array to string conversion') !== false) {
                $errorMessage = 'Data processing error. Please try again.';
                $statusCode = 422;
            } elseif (strpos($e->getMessage(), 'timeout') !== false || strpos($e->getMessage(), 'Connection') !== false) {
                $errorMessage = 'Request timeout. Please try again with a smaller image or simpler problem.';
                $statusCode = 408;
            } elseif (strpos($e->getMessage(), 'OpenAI') !== false) {
                $errorMessage = 'AI service temporarily unavailable. Please try again in a few moments.';
                $statusCode = 503;
            }
            
            return response()->json([
                'error' => $errorMessage,
                'error_type' => 'processing_error',
                'suggestion' => 'Please try again or contact support if the problem persists.'
            ], $statusCode)->header('Access-Control-Allow-Origin', 'http://localhost:3000')
              ->header('Access-Control-Allow-Credentials', 'true');
        }
    }

    /**
     * Get user's math problems
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 15);
        $filters = [
            'subject' => $request->input('subject'),
            'difficulty' => $request->input('difficulty'),
            'per_page' => $perPage
        ];

        $mathProblems = $this->aiMathService->getUserProblems($user->id, $filters);

        return response()->json([
            'math_problems' => $mathProblems->items(),
            'pagination' => [
                'current_page' => $mathProblems->currentPage(),
                'last_page' => $mathProblems->lastPage(),
                'per_page' => $mathProblems->perPage(),
                'total' => $mathProblems->total()
            ]
        ])->header('Access-Control-Allow-Origin', 'http://localhost:3000')
          ->header('Access-Control-Allow-Credentials', 'true');
    }

    /**
     * Get specific math problem with solutions
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $mathProblem = $this->aiMathService->getMathProblem($id, $user->id);

        return response()->json([
            'math_problem' => $mathProblem
        ]);
    }

    /**
     * Delete a math problem
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $mathProblem = $this->aiMathService->getMathProblem($id, $user->id);

        // Delete associated file through universal file upload system if exists
        if ($mathProblem->file_upload_id) {
            $this->fileUploadService->deleteFile($mathProblem->file_upload_id);
        }

        $mathProblem->delete();

        return response()->json([
            'message' => 'Math problem deleted successfully'
        ]);
    }

    /**
     * Get user's math history
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 15);
        $filters = [
            'subject' => $request->input('subject'),
            'difficulty' => $request->input('difficulty'),
            'per_page' => $perPage
        ];

        $mathProblems = $this->aiMathService->getUserProblems($user->id, $filters);

        // Return just the array of problems for frontend compatibility
        return response()->json($mathProblems->items())
            ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
            ->header('Access-Control-Allow-Credentials', 'true');
    }

    /**
     * Get user's math statistics
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        
        try {
            $stats = $this->aiMathService->getUserStats($user->id);
            
            return response()->json([
                'total_problems' => $stats['total_problems'],
                'total_solutions' => $stats['total_solutions'],
                'subject_stats' => $stats['subject_stats'],
                'difficulty_stats' => $stats['difficulty_stats'],
                'recent_problems' => $stats['recent_problems'],
                'success_rate' => $stats['success_rate']
            ])->header('Access-Control-Allow-Origin', 'http://localhost:3000')
              ->header('Access-Control-Allow-Credentials', 'true');
        } catch (\Exception $e) {
            Log::error('Math Stats Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to retrieve math statistics at this time'
            ], 500)->header('Access-Control-Allow-Origin', 'http://localhost:3000')
              ->header('Access-Control-Allow-Credentials', 'true');
        }
    }
}