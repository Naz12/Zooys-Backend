<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Services\UniversalJobService;
use App\Services\Modules\UniversalFileManagementModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class PdfEditController extends Controller
{
    private UniversalJobService $jobService;
    private UniversalFileManagementModule $fileModule;

    public function __construct(UniversalJobService $jobService, UniversalFileManagementModule $fileModule)
    {
        $this->jobService = $jobService;
        $this->fileModule = $fileModule;
    }

    public function start(Request $request, string $operation)
    {
        $allowed = [
            'merge','split','compress','watermark','page_numbers','annotate','protect','unlock','preview','batch','edit_pdf'
        ];
        if (!in_array($operation, $allowed)) {
            return response()->json(['success' => false, 'error' => 'Unsupported operation'], 422);
        }

        $user = $request->user();

        $request->validate([
            'file_ids' => 'sometimes|array',
            'file_ids.*' => 'string|exists:file_uploads,id',
            'file_id' => 'sometimes|string|exists:file_uploads,id',
            'params' => 'sometimes|array'
        ]);

        $fileIds = [];
        if (in_array($operation, ['merge','batch'])) {
            $fileIds = array_values(array_unique($request->input('file_ids', [])));
            if (count($fileIds) < 2 && $operation === 'merge') {
                return response()->json(['success' => false, 'error' => 'At least two files are required for merge'], 422);
            }
        } else {
            $single = $request->input('file_id') ?? ($request->input('file_ids.0') ?? null);
            if (!$single) {
                return response()->json(['success' => false, 'error' => 'file_id is required'], 422);
            }
            $fileIds = [$single];
        }

        $params = $request->input('params', []);

        $job = $this->jobService->createJob('pdf_edit', [
            'operation' => $operation,
            'file_ids' => $fileIds,
            'params' => $params,
        ], [], $user?->id);

        Artisan::queue('universal:process-job', ['jobId' => $job['id']]);

        return response()->json([
            'success' => true,
            'job_id' => $job['id'],
            'status' => $job['status'],
            'message' => 'PDF job queued successfully'
        ], 202);
    }

    public function status(Request $request, string $operation)
    {
        $jobId = $request->query('job_id');
        if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);

        $job = $this->jobService->getJob($jobId);
        if (!$job) return response()->json(['error' => 'Job not found'], 404);
        if (($job['tool_type'] ?? '') !== 'pdf_edit') return response()->json(['error' => 'Job tool type mismatch'], 400);
        if (($job['input']['operation'] ?? null) !== $operation) return response()->json(['error' => 'Operation mismatch'], 400);

        return response()->json([
            'job_id' => $job['id'],
            'status' => $job['status'] ?? 'unknown',
            'progress' => $job['progress'] ?? 0,
            'stage' => $job['stage'] ?? null,
            'error' => $job['error'] ?? null,
            'created_at' => $job['created_at'] ?? null,
            'updated_at' => $job['updated_at'] ?? null,
        ]);
    }

    public function result(Request $request, string $operation)
    {
        $jobId = $request->query('job_id');
        if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);

        $job = $this->jobService->getJob($jobId);
        if (!$job) return response()->json(['error' => 'Job not found'], 404);
        if (($job['tool_type'] ?? '') !== 'pdf_edit') return response()->json(['error' => 'Job tool type mismatch'], 400);
        if (($job['input']['operation'] ?? null) !== $operation) return response()->json(['error' => 'Operation mismatch'], 400);
        if (($job['status'] ?? '') !== 'completed') return response()->json(['error' => 'Job not completed', 'status' => $job['status'] ?? 'unknown'], 409);

        return response()->json([
            'success' => true,
            'job_id' => $job['id'],
            'operation' => $operation,
            'data' => $job['result'] ?? null,
            'metadata' => $job['metadata'] ?? []
        ]);
    }
}



