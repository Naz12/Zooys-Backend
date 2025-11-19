<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AIDiagramService;
use App\Services\UniversalJobService;
use App\Services\AIResultService;
use App\Models\AIResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DiagramController extends Controller
{
    protected $aiDiagramService;
    protected $universalJobService;
    protected $aiResultService;

    public function __construct(
        AIDiagramService $aiDiagramService,
        UniversalJobService $universalJobService,
        AIResultService $aiResultService
    ) {
        $this->aiDiagramService = $aiDiagramService;
        $this->universalJobService = $universalJobService;
        $this->aiResultService = $aiResultService;
    }

    /**
     * Generate a diagram
     * 
     * POST /api/diagram/generate
     */
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'prompt' => 'required|string|max:2000',
            'diagram_type' => 'required|string|in:flowchart,sequence,class,state,er,user_journey,block,mindmap,pie,quadrant,timeline,sankey,xy',
            'output_format' => 'nullable|string|in:svg,pdf,png',
            'language' => 'nullable|string|max:10'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthenticated'
            ], 401);
        }

        try {
            $inputData = [
                'prompt' => $request->input('prompt'),
                'diagram_type' => $request->input('diagram_type'),
                'output_format' => $request->input('output_format', 'svg'),
                'language' => $request->input('language', 'en')
            ];

            // Create universal job
            $job = $this->universalJobService->createJob(
                'diagram',
                $inputData,
                [],
                $user->id
            );

            // Queue job for asynchronous processing (uses queue worker)
            $this->universalJobService->queueJob($job['id']);

            return response()->json([
                'success' => true,
                'job_id' => $job['id'],
                'status' => 'pending',
                'message' => 'Diagram generation job created',
                'poll_url' => url('/api/diagram/status?job_id=' . $job['id']),
                'result_url' => url('/api/diagram/result?job_id=' . $job['id'])
            ]);

        } catch (\Exception $e) {
            Log::error('Diagram generation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate diagram: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get job status
     * 
     * GET /api/diagram/status?job_id={jobId}
     */
    public function status(Request $request)
    {
        $jobId = $request->query('job_id');
        if (!$jobId) {
            return response()->json([
                'success' => false,
                'error' => 'job_id parameter is required'
            ], 400);
        }

        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthenticated'
            ], 401);
        }

        try {
            $job = $this->universalJobService->getJob($jobId);

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'error' => 'Job not found'
                ], 404);
            }

            // Check if user owns this job
            if ($job['user_id'] !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'job_id' => $job['id'],
                'tool_type' => 'diagram',
                'status' => $job['status'] ?? 'unknown',
                'progress' => $job['progress'] ?? 0,
                'stage' => $job['stage'] ?? null,
                'error' => $job['error'] ?? null,
                'created_at' => $job['created_at'] ?? null,
                'updated_at' => $job['updated_at'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get diagram job status', [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve job status'
            ], 500);
        }
    }

    /**
     * Get job result
     * 
     * GET /api/diagram/result?job_id={jobId}
     */
    public function result(Request $request)
    {
        $jobId = $request->query('job_id');
        if (!$jobId) {
            return response()->json([
                'success' => false,
                'error' => 'job_id parameter is required'
            ], 400);
        }

        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthenticated'
            ], 401);
        }

        try {
            $job = $this->universalJobService->getJob($jobId);

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'error' => 'Job not found'
                ], 404);
            }

            // Check if user owns this job
            if ($job['user_id'] !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized'
                ], 403);
            }

            if ($job['status'] !== 'completed') {
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
                'tool_type' => 'diagram',
                'data' => $job['result'] ?? null,
                'metadata' => $job['metadata'] ?? []
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get diagram job result', [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve job result'
            ], 500);
        }
    }

    /**
     * Get diagram by AI result ID
     * 
     * GET /api/diagram/{aiResultId}
     */
    public function show($aiResultId)
    {
        try {
            $aiResult = AIResult::where('id', $aiResultId)
                ->where('tool_type', 'diagram')
                ->first();

            if (!$aiResult) {
                return response()->json([
                    'success' => false,
                    'error' => 'Diagram not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $aiResult->id,
                    'title' => $aiResult->title,
                    'result_data' => $aiResult->result_data,
                    'metadata' => $aiResult->metadata,
                    'created_at' => $aiResult->created_at,
                    'updated_at' => $aiResult->updated_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get diagram', [
                'ai_result_id' => $aiResultId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve diagram'
            ], 500);
        }
    }

    /**
     * List user's diagrams
     * 
     * GET /api/diagram
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthenticated'
            ], 401);
        }

        try {
            $diagrams = AIResult::where('user_id', $user->id)
                ->where('tool_type', 'diagram')
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $diagrams->items(),
                'pagination' => [
                    'current_page' => $diagrams->currentPage(),
                    'last_page' => $diagrams->lastPage(),
                    'per_page' => $diagrams->perPage(),
                    'total' => $diagrams->total()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to list diagrams', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve diagrams'
            ], 500);
        }
    }

    /**
     * Delete diagram
     * 
     * DELETE /api/diagram/{aiResultId}
     */
    public function destroy($aiResultId)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthenticated'
            ], 401);
        }

        try {
            $aiResult = AIResult::where('id', $aiResultId)
                ->where('user_id', $user->id)
                ->where('tool_type', 'diagram')
                ->first();

            if (!$aiResult) {
                return response()->json([
                    'success' => false,
                    'error' => 'Diagram not found'
                ], 404);
            }

            // Delete associated image file if exists
            $resultData = $aiResult->result_data ?? [];
            if (isset($resultData['image_path'])) {
                try {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($resultData['image_path']);
                } catch (\Exception $e) {
                    Log::warning('Failed to delete diagram image file', [
                        'path' => $resultData['image_path'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $aiResult->delete();

            return response()->json([
                'success' => true,
                'message' => 'Diagram deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete diagram', [
                'ai_result_id' => $aiResultId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete diagram'
            ], 500);
        }
    }

    /**
     * Get supported diagram types
     * 
     * GET /api/diagram/types
     */
    public function getTypes()
    {
        try {
            $types = $this->aiDiagramService->getSupportedDiagramTypes();

            return response()->json([
                'success' => true,
                'data' => [
                    'graph_based' => [
                        'flowchart',
                        'sequence',
                        'class',
                        'state',
                        'er',
                        'user_journey',
                        'block',
                        'mindmap'
                    ],
                    'chart_based' => [
                        'pie',
                        'quadrant',
                        'timeline',
                        'sankey',
                        'xy'
                    ],
                    'all' => $types
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve diagram types'
            ], 500);
        }
    }

    /**
     * Check microservice health
     * 
     * GET /api/diagram/health
     */
    public function health()
    {
        try {
            $available = $this->aiDiagramService->isMicroserviceAvailable();

            return response()->json([
                'success' => true,
                'available' => $available,
                'message' => $available ? 'Microservice is available' : 'Microservice is unavailable'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'available' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
