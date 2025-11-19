<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Services\Modules\ModuleRegistry;
use App\Services\UniversalJobService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ContentController extends Controller
{
    private $universalJobService;

    public function __construct(
        UniversalJobService $universalJobService
    ) {
        $this->universalJobService = $universalJobService;
    }

    /**
     * Create a write job
     * POST /api/content/write
     */
    public function write(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'prompt' => 'required|string|max:5000',
                'mode' => 'nullable|string|in:creative,professional,academic',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $userId = auth()->id();
            $prompt = $request->input('prompt');
            $mode = $request->input('mode', 'professional');

            // Create job via UniversalJobService
            $job = $this->universalJobService->createJob(
                'content_write',
                [
                    'prompt' => $prompt,
                    'mode' => $mode
                ],
                [
                    'mode' => $mode
                ],
                $userId
            );

            Log::info('Content write job created', [
                'job_id' => $job['id'],
                'user_id' => $userId,
                'mode' => $mode
            ]);

            // Queue the job for processing
            $this->universalJobService->queueJob($job['id']);

            return response()->json([
                'success' => true,
                'job_id' => $job['id'],
                'status' => 'pending',
                'message' => 'Content writing job created successfully',
                'poll_url' => url('/api/content/status?job_id=' . $job['id']),
                'result_url' => url('/api/content/result?job_id=' . $job['id'])
            ]);

        } catch (\Exception $e) {
            Log::error('Content write job creation failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create write job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a rewrite job
     * POST /api/content/rewrite
     */
    public function rewrite(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'previous_content' => 'required|string|max:50000',
                'prompt' => 'required|string|max:5000',
                'mode' => 'nullable|string|in:creative,professional,academic',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $userId = auth()->id();
            $previousContent = $request->input('previous_content');
            $prompt = $request->input('prompt');
            $mode = $request->input('mode', 'professional');

            // Create job via UniversalJobService
            $job = $this->universalJobService->createJob(
                'content_rewrite',
                [
                    'previous_content' => $previousContent,
                    'prompt' => $prompt,
                    'mode' => $mode
                ],
                [
                    'mode' => $mode
                ],
                $userId
            );

            Log::info('Content rewrite job created', [
                'job_id' => $job['id'],
                'user_id' => $userId,
                'mode' => $mode
            ]);

            // Queue the job for processing
            $this->universalJobService->queueJob($job['id']);

            return response()->json([
                'success' => true,
                'job_id' => $job['id'],
                'status' => 'pending',
                'message' => 'Content rewriting job created successfully',
                'poll_url' => url('/api/content/status?job_id=' . $job['id']),
                'result_url' => url('/api/content/result?job_id=' . $job['id'])
            ]);

        } catch (\Exception $e) {
            Log::error('Content rewrite job creation failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create rewrite job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get job status for content operations
     * GET /api/content/status?job_id={jobId}
     */
    public function getStatus(Request $request): JsonResponse
    {
        try {
            $jobId = $request->query('job_id');
            
            if (!$jobId) {
                return response()->json([
                    'success' => false,
                    'error' => 'job_id parameter is required'
                ], 400);
            }

            $job = $this->universalJobService->getJob($jobId);
            
            if (!$job) {
                return response()->json([
                    'success' => false,
                    'error' => 'Job not found'
                ], 404);
            }

            // Verify job is for content writer tool
            $validToolTypes = ['content_write', 'content_rewrite'];
            $jobToolType = $job['tool_type'] ?? '';
            
            if (!in_array($jobToolType, $validToolTypes)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid job type. Must be one of: ' . implode(', ', $validToolTypes)
                ], 400);
            }

            // Check if user owns this job (optional - can be public for polling)
            $userId = auth()->id();
            if ($userId && isset($job['user_id']) && $job['user_id'] !== $userId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized'
                ], 403);
            }

            // Get stage information with user-friendly messages
            $stage = $job['stage'] ?? 'initializing';
            $stageInfo = $this->getStageInfo($jobToolType, $stage, $job['progress'] ?? 0);
            
            return response()->json([
                'success' => true,
                'job_id' => $job['id'],
                'tool_type' => $jobToolType,
                'status' => $job['status'] ?? 'unknown',
                'progress' => $job['progress'] ?? 0,
                'stage' => $stage,
                'stage_message' => $stageInfo['message'],
                'stage_description' => $stageInfo['description'],
                'error' => $job['error'] ?? null,
                'created_at' => $job['created_at'] ?? null,
                'updated_at' => $job['updated_at'] ?? null,
                'logs' => $job['logs'] ?? []
            ]);

        } catch (\Exception $e) {
            Log::error('Get content job status failed', [
                'error' => $e->getMessage(),
                'job_id' => $request->query('job_id'),
                'user_id' => auth()->id() ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get job status'
            ], 500);
        }
    }

    /**
     * Get job result for content operations
     * GET /api/content/result?job_id={jobId}
     */
    public function getResult(Request $request): JsonResponse
    {
        try {
            $jobId = $request->query('job_id');
            
            if (!$jobId) {
                return response()->json([
                    'success' => false,
                    'error' => 'job_id parameter is required'
                ], 400);
            }

            $job = $this->universalJobService->getJob($jobId);
            
            if (!$job) {
                return response()->json([
                    'success' => false,
                    'error' => 'Job not found'
                ], 404);
            }

            // Verify job is for content writer tool
            $validToolTypes = ['content_write', 'content_rewrite'];
            $jobToolType = $job['tool_type'] ?? '';
            
            if (!in_array($jobToolType, $validToolTypes)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid job type. Must be one of: ' . implode(', ', $validToolTypes)
                ], 400);
            }

            // Check if user owns this job
            $userId = auth()->id();
            if ($userId && isset($job['user_id']) && $job['user_id'] !== $userId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized'
                ], 403);
            }

            if (($job['status'] ?? '') !== 'completed') {
                return response()->json([
                    'success' => false,
                    'error' => 'Job not completed yet',
                    'status' => $job['status'] ?? 'unknown',
                    'progress' => $job['progress'] ?? 0
                ], 202);
            }

            return response()->json([
                'success' => true,
                'job_id' => $job['id'],
                'tool_type' => $jobToolType,
                'data' => $job['result'] ?? null,
                'metadata' => $job['metadata'] ?? []
            ]);

        } catch (\Exception $e) {
            Log::error('Get content job result failed', [
                'error' => $e->getMessage(),
                'job_id' => $request->query('job_id'),
                'user_id' => auth()->id() ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get job result'
            ], 500);
        }
    }

    /**
     * Get user-friendly stage information based on tool type and stage
     */
    private function getStageInfo($toolType, $stage, $progress)
    {
        $stages = [
            'content_write' => [
                'initializing' => [
                    'message' => 'Preparing to generate content...',
                    'description' => 'Setting up the content generation process'
                ],
                'validating_input' => [
                    'message' => 'Validating your prompt...',
                    'description' => 'Checking the input for quality and completeness'
                ],
                'generating_content' => [
                    'message' => 'Generating your content...',
                    'description' => 'AI is creating your content based on your prompt'
                ],
                'refining_content' => [
                    'message' => 'Refining the content...',
                    'description' => 'Polishing and improving the generated content'
                ],
                'finalizing' => [
                    'message' => 'Finalizing content...',
                    'description' => 'Completing the content and preparing it for you'
                ],
                'completed' => [
                    'message' => 'Content generated successfully!',
                    'description' => 'Your content is ready'
                ],
                'failed' => [
                    'message' => 'Content generation failed',
                    'description' => 'An error occurred while generating the content'
                ]
            ],
            'content_rewrite' => [
                'initializing' => [
                    'message' => 'Preparing to rewrite content...',
                    'description' => 'Setting up the content rewriting process'
                ],
                'validating_input' => [
                    'message' => 'Validating your input...',
                    'description' => 'Checking the content and rewrite instructions'
                ],
                'generating_content' => [
                    'message' => 'Rewriting your content...',
                    'description' => 'AI is rewriting the content based on your instructions'
                ],
                'refining_content' => [
                    'message' => 'Refining the rewritten content...',
                    'description' => 'Polishing and improving the rewritten content'
                ],
                'finalizing' => [
                    'message' => 'Finalizing rewritten content...',
                    'description' => 'Completing the rewrite and preparing it for you'
                ],
                'completed' => [
                    'message' => 'Content rewritten successfully!',
                    'description' => 'Your rewritten content is ready'
                ],
                'failed' => [
                    'message' => 'Content rewriting failed',
                    'description' => 'An error occurred while rewriting the content'
                ]
            ]
        ];

        $toolStages = $stages[$toolType] ?? [];
        $stageInfo = $toolStages[$stage] ?? [
            'message' => 'Processing...',
            'description' => 'Your request is being processed'
        ];

        return $stageInfo;
    }
}

