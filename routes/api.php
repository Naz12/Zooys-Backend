<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Client\AuthController;
use App\Http\Controllers\Api\Client\StripeController;
use App\Http\Controllers\Api\Client\PlanController;
use App\Http\Controllers\Api\Client\SubscriptionController;
use App\Http\Controllers\Api\Client\WriterController;
use App\Http\Controllers\Api\Client\MathController;
use App\Http\Controllers\Api\Client\FlashcardController;
use App\Http\Controllers\Api\Client\FileUploadController;
use App\Http\Controllers\Api\Client\AIResultController;
use App\Http\Controllers\Api\Client\DiagramController;
use App\Http\Controllers\Api\Client\ChatController;
use App\Http\Controllers\Api\Client\ChatSessionController;
use App\Http\Controllers\Api\Client\ChatMessageController;
use App\Http\Controllers\Api\Client\SummarizeController;
use App\Http\Controllers\Api\Client\DocumentChatController;
use App\Http\Controllers\Api\Client\PresentationController;
use App\Http\Controllers\Api\Client\ContentController;
use App\Http\Controllers\Api\Client\FileExtractionController;
use App\Http\Controllers\Api\Client\PdfEditController;
use App\Http\Controllers\Api\Client\DocumentIntelligenceController;
use App\Http\Controllers\Api\Client\VisitorTrackingController;

// Admin Controllers
use App\Http\Controllers\Api\Admin\AdminAuthController;
use App\Http\Controllers\Api\Admin\AdminPasswordResetController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Api\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Api\Admin\ToolUsageController;
use App\Http\Controllers\Api\Admin\VisitorController;
use App\Http\Controllers\Api\Admin\VisitorTrackingController as AdminVisitorTrackingController;

// 🔹 Public
Route::get('/plans', [PlanController::class, 'index']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 🔹 Google OAuth (Public)
Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('google.callback');

// 🔹 Visitor Tracking (Public - optional auth)
Route::post('/visitor-tracking', [VisitorTrackingController::class, 'trackVisit']);

// 🔹 Public Presentation Routes (for testing)
Route::get('/presentations/templates', [PresentationController::class, 'getTemplates']);
Route::post('/presentations/generate-outline', [PresentationController::class, 'generateOutline']);
Route::post('/presentations/generate-content', [PresentationController::class, 'generateContent']);
Route::post('/presentations/export', [PresentationController::class, 'exportPresentation']);
Route::get('/presentations/files', [PresentationController::class, 'getPresentationFiles']);
Route::get('/presentations/files/{fileId}/content', [PresentationController::class, 'getFileContent']);
Route::delete('/presentations/files/{fileId}', [PresentationController::class, 'deletePresentationFile']);
Route::get('/presentations/files/{fileId}/download', [PresentationController::class, 'downloadPresentationFile']);
Route::get('/presentations/status', [PresentationController::class, 'getJobStatus']);
Route::get('/presentations/result', [PresentationController::class, 'getJobResult']);

// 🔹 PDF Edit public status/result (manual bearer validation not strictly required; keep open for polling)
Route::get('/pdf/edit/{operation}/status', [PdfEditController::class, 'status']);
Route::get('/pdf/edit/{operation}/result', [PdfEditController::class, 'result']);

// CORS OPTIONS for public presentation routes
Route::options('/presentations/status', function () { 
    return response('', 200)
        ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
        ->header('Access-Control-Allow-Credentials', 'true');
});
Route::options('/presentations/result', function () { 
    return response('', 200)
        ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
        ->header('Access-Control-Allow-Credentials', 'true');
});
Route::options('/presentations/files/{fileId}/content', function () { 
    return response('', 200)
        ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
        ->header('Access-Control-Allow-Credentials', 'true');
});

// 🔹 Stripe webhook
Route::post('/stripe/webhook', [StripeController::class, 'webhook']);

// 🔹 Test route without middleware
Route::get('/test-simple', function () {
    return response()->json(['message' => 'Test route works']);
});

// 🔹 Test route with auth middleware only
Route::middleware(['auth:sanctum'])->get('/test-auth-only', function (Request $request) {
    return response()->json([
        'message' => 'Auth route works',
        'user' => $request->user() ? $request->user()->id : null
    ]);
});

// 🔹 Test route with auth middleware outside group
Route::middleware(['auth:sanctum'])->get('/test-auth-outside', function (Request $request) {
    return response()->json([
        'message' => 'Auth route outside group works',
        'user' => $request->user() ? $request->user()->id : null
    ]);
});

// 🔹 Test route without any middleware
Route::get('/test-no-middleware', function (Request $request) {
    return response()->json([
        'message' => 'No middleware route works',
        'token' => $request->bearerToken(),
        'user' => $request->user() ? $request->user()->id : null
    ]);
});

// 🔹 Test route to manually check token
Route::get('/test-token-manual', function (Request $request) {
    $token = $request->bearerToken();
    $parts = explode('|', $token);

    $tokenRecord = null;
    if (count($parts) === 2) {
        $tokenRecord = Laravel\Sanctum\PersonalAccessToken::where('token', hash('sha256', $parts[1]))->first();
    }

    return response()->json([
        'message' => 'Manual token check',
        'token' => $token,
        'parts' => $parts,
        'token_exists' => $tokenRecord ? true : false,
        'token_id' => $tokenRecord ? $tokenRecord->id : null,
        'user_id' => $tokenRecord ? $tokenRecord->tokenable_id : null
    ]);
});

// 🔹 Test route with auth:sanctum middleware and debugging
Route::middleware(['auth:sanctum'])->get('/test-auth-debug', function (Request $request) {
    return response()->json([
        'message' => 'Auth debug route works',
        'user' => $request->user() ? $request->user()->id : null,
        'token' => $request->bearerToken(),
        'auth_check' => auth()->check(),
        'auth_user' => auth()->user() ? auth()->user()->id : null
    ]);
});

// 🔹 Test route with auth middleware (Laravel's built-in auth middleware)
Route::middleware(['auth'])->get('/test-auth-builtin', function (Request $request) {
    return response()->json([
        'message' => 'Auth builtin route works',
        'user' => $request->user() ? $request->user()->id : null,
        'token' => $request->bearerToken(),
        'auth_check' => auth()->check(),
        'auth_user' => auth()->user() ? auth()->user()->id : null
    ]);
});

// 🔹 Test route with no middleware to verify basic functionality
Route::get('/test-no-auth', function (Request $request) {
    return response()->json([
        'message' => 'No auth route works',
        'user' => $request->user() ? $request->user()->id : null,
        'token' => $request->bearerToken(),
        'auth_check' => auth()->check(),
        'auth_user' => auth()->user() ? auth()->user()->id : null
    ]);
});

// 🔹 Test route with full middleware class name
Route::middleware([\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class])->get('/test-auth-full', function (Request $request) {
    return response()->json([
        'message' => 'Auth full route works',
        'user' => $request->user() ? $request->user()->id : null,
        'token' => $request->bearerToken(),
        'auth_check' => auth()->check(),
        'auth_user' => auth()->user() ? auth()->user()->id : null
    ]);
});

// 🔹 Test route with Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful middleware
Route::middleware([\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class])->get('/test-sanctum-stateful', function (Request $request) {
    return response()->json([
        'message' => 'Sanctum stateful route works',
        'user' => $request->user() ? $request->user()->id : null,
        'token' => $request->bearerToken(),
        'auth_check' => auth()->check(),
        'auth_user' => auth()->user() ? auth()->user()->id : null
    ]);
});

// 🔹 Test route to manually authenticate and test summarize
Route::post('/test-summarize-manual', function (Request $request) {
    $token = $request->bearerToken();
    $parts = explode('|', $token);

    if (count($parts) !== 2) {
        return response()->json(['error' => 'Invalid token format'], 401);
    }

    $tokenRecord = Laravel\Sanctum\PersonalAccessToken::where('token', hash('sha256', $parts[1]))->first();

    if (!$tokenRecord) {
        return response()->json(['error' => 'Token not found'], 401);
    }

    $user = $tokenRecord->tokenable;

    if (!$user) {
        return response()->json(['error' => 'User not found'], 401);
    }

    // Manually authenticate the user
    // Manually authenticate the user
    auth()->login($user);

    // Test the summarize endpoint logic
    $contentType = $request->input('content_type');
    $source = $request->input('source');
    $options = $request->input('options', []);

    try {
        $universalJobService = app(\App\Services\UniversalJobService::class);
        $job = $universalJobService->createJob('summarize', [
            'content_type' => $contentType,
            'source' => $source
        ], $options, $user->id);

        return response()->json([
            'success' => true,
            'message' => 'Job created successfully',
            'job_id' => $job['id'],
            'status' => $job['status']
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

// 🔹 Test route to manually fetch job status (bypasses auth middleware)
Route::get('/test-status-manual/{jobId}', function (Request $request, $jobId) {
    $token = $request->bearerToken();
    $parts = explode('|', $token);

    if (count($parts) !== 2) {
        return response()->json(['error' => 'Invalid token format'], 401);
    }

    $tokenRecord = Laravel\Sanctum\PersonalAccessToken::where('token', hash('sha256', $parts[1]))->first();
    if (!$tokenRecord || !$tokenRecord->tokenable) {
        return response()->json(['error' => 'Unauthenticated'], 401);
    }

    auth()->login($tokenRecord->tokenable);

    try {
        $universalJobService = app(\App\Services\UniversalJobService::class);
        $job = $universalJobService->getJob($jobId);
        if (!$job) {
            return response()->json(['error' => 'Job not found'], 404);
        }
        return response()->json($job);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

// 🔹 Test route to manually fetch job result (bypasses auth middleware)
Route::get('/test-result-manual/{jobId}', function (Request $request, $jobId) {
    $token = $request->bearerToken();
    $parts = explode('|', $token);

    if (count($parts) !== 2) {
        return response()->json(['error' => 'Invalid token format'], 401);
    }

    $tokenRecord = Laravel\Sanctum\PersonalAccessToken::where('token', hash('sha256', $parts[1]))->first();
    if (!$tokenRecord || !$tokenRecord->tokenable) {
        return response()->json(['error' => 'Unauthenticated'], 401);
    }

    auth()->login($tokenRecord->tokenable);

    try {
        $universalJobService = app(\App\Services\UniversalJobService::class);
        $job = $universalJobService->getJob($jobId);
        if (!$job) {
            return response()->json(['error' => 'Job not found'], 404);
        }
        if (($job['status'] ?? '') !== 'completed') {
            return response()->json(['error' => 'Job not completed', 'status' => $job['status'] ?? 'unknown'], 409);
        }
        return response()->json(['success' => true, 'data' => $job['result'] ?? null]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

// 🔹 Universal Status and Result endpoints with manual bearer validation for frontend compatibility
Route::get('/status', function (Request $request) {
    $token = $request->bearerToken();
    $parts = explode('|', $token ?? '');
    if (count($parts) !== 2) return response()->json(['error' => 'Unauthenticated'], 401);
    $record = Laravel\Sanctum\PersonalAccessToken::where('token', hash('sha256', $parts[1]))->first();
    if (!$record || !$record->tokenable) return response()->json(['error' => 'Unauthenticated'], 401);
    auth()->login($record->tokenable);
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $service = app(\App\Services\UniversalJobService::class);
    $job = $service->getJob($jobId);
    if (!$job) return response()->json(['error' => 'Job not found'], 404);
    return response()->json([
        'job_id' => $job['id'],
        'status' => $job['status'] ?? 'unknown',
        'progress' => $job['progress'] ?? 0,
        'stage' => $job['stage'] ?? null,
        'error' => $job['error'] ?? null,
        'tool_type' => $job['tool_type'] ?? null,
        'created_at' => $job['created_at'] ?? null,
        'updated_at' => $job['updated_at'] ?? null
    ]);
});

Route::get('/result', function (Request $request) {
    $token = $request->bearerToken();
    $parts = explode('|', $token ?? '');
    if (count($parts) !== 2) return response()->json(['error' => 'Unauthenticated'], 401);
    $record = Laravel\Sanctum\PersonalAccessToken::where('token', hash('sha256', $parts[1]))->first();
    if (!$record || !$record->tokenable) return response()->json(['error' => 'Unauthenticated'], 401);
    auth()->login($record->tokenable);
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $service = app(\App\Services\UniversalJobService::class);
    $job = $service->getJob($jobId);
    if (!$job) return response()->json(['error' => 'Job not found'], 404);
    if (($job['status'] ?? '') !== 'completed') {
        return response()->json(['error' => 'Job not completed', 'status' => $job['status'] ?? 'unknown'], 409);
    }
    return response()->json(['success' => true, 'data' => $job['result'] ?? null]);
});

// 🔹 Tool-Specific Status and Result Endpoints

// Helper functions for authentication and job retrieval
$authenticateUser = function (Request $request) {
    $token = $request->bearerToken();
    $parts = explode('|', $token ?? '');
    if (count($parts) !== 2) return response()->json(['error' => 'Unauthenticated'], 401);
    $record = Laravel\Sanctum\PersonalAccessToken::where('token', hash('sha256', $parts[1]))->first();
    if (!$record || !$record->tokenable) return response()->json(['error' => 'Unauthenticated'], 401);
    auth()->login($record->tokenable);
    return null;
};

$getJobForTool = function ($jobId, $toolType, $inputType) {
    $service = app(\App\Services\UniversalJobService::class);
    $job = $service->getJob($jobId);
    if (!$job) return ['error' => 'Job not found', 'code' => 404];
    if ($job['tool_type'] !== $toolType) return ['error' => 'Job tool type mismatch', 'code' => 400];
    
    // For audiovideo, check if content_type is audio or video
    if ($inputType === 'audiovideo') {
        $contentType = $job['input']['content_type'] ?? null;
        if (!in_array($contentType, ['audio', 'video'])) {
            return ['error' => 'Job is not an audio/video summarization', 'code' => 400];
        }
    }
    
    return ['job' => $job];
};

// 📝 SUMMARIZE TOOL ENDPOINTS

// Summarize Text Status
Route::get('/status/summarize/text', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'summarize', 'text');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    return response()->json([
        'job_id' => $job['id'],
        'tool_type' => 'summarize',
        'input_type' => 'text',
        'status' => $job['status'] ?? 'unknown',
        'progress' => $job['progress'] ?? 0,
        'stage' => $job['stage'] ?? null,
        'error' => $job['error'] ?? null,
        'created_at' => $job['created_at'] ?? null,
        'updated_at' => $job['updated_at'] ?? null
    ]);
});

// Summarize Text Result
Route::get('/result/summarize/text', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'summarize', 'text');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    if (($job['status'] ?? '') !== 'completed') {
        return response()->json(['error' => 'Job not completed', 'status' => $job['status'] ?? 'unknown'], 409);
    }
    
    return response()->json([
        'success' => true,
        'job_id' => $job['id'],
        'tool_type' => 'summarize',
        'input_type' => 'text',
        'data' => $job['result'] ?? null
    ]);
});

// Summarize YouTube Status
Route::get('/status/summarize/youtube', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'summarize', 'youtube');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    return response()->json([
        'job_id' => $job['id'],
        'tool_type' => 'summarize',
        'input_type' => 'youtube',
        'status' => $job['status'] ?? 'unknown',
        'progress' => $job['progress'] ?? 0,
        'stage' => $job['stage'] ?? null,
        'error' => $job['error'] ?? null,
        'created_at' => $job['created_at'] ?? null,
        'updated_at' => $job['updated_at'] ?? null
    ]);
});

// Summarize YouTube Result
Route::get('/result/summarize/youtube', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'summarize', 'youtube');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    if (($job['status'] ?? '') !== 'completed') {
        return response()->json(['error' => 'Job not completed', 'status' => $job['status'] ?? 'unknown'], 409);
    }
    
    return response()->json([
        'success' => true,
        'job_id' => $job['id'],
        'tool_type' => 'summarize',
        'input_type' => 'youtube',
        'data' => $job['result'] ?? null
    ]);
});

// Summarize File Status
Route::get('/status/summarize/file', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'summarize', 'file');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    
    // Extract error details from result if available
    $errorDetails = null;
    $docId = null;
    $conversationId = null;
    
    if (isset($job['result']) && is_array($job['result'])) {
        $errorDetails = $job['result']['error_details'] ?? null;
        $docId = $job['result']['doc_id'] ?? $job['result']['error_details']['doc_id'] ?? null;
        $conversationId = $job['result']['conversation_id'] ?? $job['result']['error_details']['conversation_id'] ?? null;
    }
    
    $response = [
        'job_id' => $job['id'],
        'tool_type' => 'summarize',
        'input_type' => 'file',
        'status' => $job['status'] ?? 'unknown',
        'progress' => $job['progress'] ?? 0,
        'stage' => $job['stage'] ?? null,
        'error' => $job['error'] ?? null,
        'created_at' => $job['created_at'] ?? null,
        'updated_at' => $job['updated_at'] ?? null
    ];
    
    // Include doc_id and conversation_id if available (for failed jobs with Document Intelligence)
    if ($docId) {
        $response['doc_id'] = $docId;
    }
    if ($conversationId) {
        $response['conversation_id'] = $conversationId;
    }
    if ($errorDetails) {
        $response['error_details'] = $errorDetails;
    }
    
    return response()->json($response);
});

// Summarize File Result
Route::get('/result/summarize/file', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'summarize', 'file');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    if (($job['status'] ?? '') !== 'completed') {
        return response()->json(['error' => 'Job not completed', 'status' => $job['status'] ?? 'unknown'], 409);
    }
    
    return response()->json([
        'success' => true,
        'job_id' => $job['id'],
        'tool_type' => 'summarize',
        'input_type' => 'file',
        'data' => $job['result'] ?? null
    ]);
});

// Summarize Web Status
Route::get('/status/summarize/web', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'summarize', 'web');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    return response()->json([
        'job_id' => $job['id'],
        'tool_type' => 'summarize',
        'input_type' => 'web',
        'status' => $job['status'] ?? 'unknown',
        'progress' => $job['progress'] ?? 0,
        'stage' => $job['stage'] ?? null,
        'error' => $job['error'] ?? null,
        'created_at' => $job['created_at'] ?? null,
        'updated_at' => $job['updated_at'] ?? null
    ]);
});

// Summarize Web Result
Route::get('/result/summarize/web', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'summarize', 'web');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    if (($job['status'] ?? '') !== 'completed') {
        return response()->json(['error' => 'Job not completed', 'status' => $job['status'] ?? 'unknown'], 409);
    }
    
    return response()->json([
        'success' => true,
        'job_id' => $job['id'],
        'tool_type' => 'summarize',
        'input_type' => 'web',
        'data' => $job['result'] ?? null
    ]);
});

// Summarize Audio/Video Status
Route::get('/status/summarize/audiovideo', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'summarize', 'audiovideo');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    
    // Determine input_type from job's content_type
    $inputType = 'audiovideo';
    if (isset($job['input']['content_type'])) {
        $contentType = $job['input']['content_type'];
        if (in_array($contentType, ['audio', 'video'])) {
            $inputType = $contentType;
        }
    }
    
    return response()->json([
        'job_id' => $job['id'],
        'tool_type' => 'summarize',
        'input_type' => $inputType,
        'status' => $job['status'] ?? 'unknown',
        'progress' => $job['progress'] ?? 0,
        'stage' => $job['stage'] ?? null,
        'error' => $job['error'] ?? null,
        'created_at' => $job['created_at'] ?? null,
        'updated_at' => $job['updated_at'] ?? null
    ]);
});

// Summarize Audio/Video Result
Route::get('/result/summarize/audiovideo', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'summarize', 'audiovideo');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    if (($job['status'] ?? '') !== 'completed') {
        return response()->json(['error' => 'Job not completed', 'status' => $job['status'] ?? 'unknown'], 409);
    }
    
    // Determine input_type from job's content_type
    $inputType = 'audiovideo';
    if (isset($job['input']['content_type'])) {
        $contentType = $job['input']['content_type'];
        if (in_array($contentType, ['audio', 'video'])) {
            $inputType = $contentType;
        }
    }
    
    return response()->json([
        'success' => true,
        'job_id' => $job['id'],
        'tool_type' => 'summarize',
        'input_type' => $inputType,
        'data' => $job['result'] ?? null
    ]);
});

// 🧮 MATH TOOL ENDPOINTS

// Math Text Status
Route::get('/status/math/text', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'math', 'text');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    return response()->json([
        'job_id' => $job['id'],
        'tool_type' => 'math',
        'input_type' => 'text',
        'status' => $job['status'] ?? 'unknown',
        'progress' => $job['progress'] ?? 0,
        'stage' => $job['stage'] ?? null,
        'error' => $job['error'] ?? null,
        'created_at' => $job['created_at'] ?? null,
        'updated_at' => $job['updated_at'] ?? null
    ]);
});

// Math Text Result
Route::get('/result/math/text', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'math', 'text');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    if (($job['status'] ?? '') !== 'completed') {
        return response()->json(['error' => 'Job not completed', 'status' => $job['status'] ?? 'unknown'], 409);
    }
    
    return response()->json([
        'success' => true,
        'job_id' => $job['id'],
        'tool_type' => 'math',
        'input_type' => 'text',
        'data' => $job['result'] ?? null
    ]);
});

// Math Image Status
Route::get('/status/math/image', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'math', 'image');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    return response()->json([
        'job_id' => $job['id'],
        'tool_type' => 'math',
        'input_type' => 'image',
        'status' => $job['status'] ?? 'unknown',
        'progress' => $job['progress'] ?? 0,
        'stage' => $job['stage'] ?? null,
        'error' => $job['error'] ?? null,
        'created_at' => $job['created_at'] ?? null,
        'updated_at' => $job['updated_at'] ?? null
    ]);
});

// Math Image Result
Route::get('/result/math/image', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'math', 'image');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    if (($job['status'] ?? '') !== 'completed') {
        return response()->json(['error' => 'Job not completed', 'status' => $job['status'] ?? 'unknown'], 409);
    }
    
    return response()->json([
        'success' => true,
        'job_id' => $job['id'],
        'tool_type' => 'math',
        'input_type' => 'image',
        'data' => $job['result'] ?? null
    ]);
});

// 🎴 FLASHCARDS TOOL ENDPOINTS

// Flashcards Text Status
Route::get('/status/flashcards/text', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'flashcards', 'text');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    return response()->json([
        'job_id' => $job['id'],
        'tool_type' => 'flashcards',
        'input_type' => 'text',
        'status' => $job['status'] ?? 'unknown',
        'progress' => $job['progress'] ?? 0,
        'stage' => $job['stage'] ?? null,
        'error' => $job['error'] ?? null,
        'created_at' => $job['created_at'] ?? null,
        'updated_at' => $job['updated_at'] ?? null
    ]);
});

// Flashcards Text Result
Route::get('/result/flashcards/text', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'flashcards', 'text');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    
    // Handle failed jobs - return error information
    if (($job['status'] ?? '') === 'failed') {
        return response()->json([
            'success' => false,
            'job_id' => $job['id'],
            'tool_type' => 'flashcards',
            'input_type' => 'text',
            'status' => 'failed',
            'error' => $job['error'] ?? 'Job failed',
            'stage' => $job['stage'] ?? null,
            'progress' => $job['progress'] ?? 0
        ], 200);
    }
    
    if (($job['status'] ?? '') !== 'completed') {
        return response()->json(['error' => 'Job not completed', 'status' => $job['status'] ?? 'unknown'], 409);
    }
    
    return response()->json([
        'success' => true,
        'job_id' => $job['id'],
        'tool_type' => 'flashcards',
        'input_type' => 'text',
        'data' => $job['result'] ?? null
    ]);
});

// Flashcards File Status
Route::get('/status/flashcards/file', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'flashcards', 'file');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    return response()->json([
        'job_id' => $job['id'],
        'tool_type' => 'flashcards',
        'input_type' => 'file',
        'status' => $job['status'] ?? 'unknown',
        'progress' => $job['progress'] ?? 0,
        'stage' => $job['stage'] ?? null,
        'error' => $job['error'] ?? null,
        'created_at' => $job['created_at'] ?? null,
        'updated_at' => $job['updated_at'] ?? null
    ]);
});

// Flashcards File Result
Route::get('/result/flashcards/file', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'flashcards', 'file');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    
    // Handle failed jobs - return error information
    if (($job['status'] ?? '') === 'failed') {
        return response()->json([
            'success' => false,
            'job_id' => $job['id'],
            'tool_type' => 'flashcards',
            'input_type' => 'file',
            'status' => 'failed',
            'error' => $job['error'] ?? 'Job failed',
            'stage' => $job['stage'] ?? null,
            'progress' => $job['progress'] ?? 0
        ], 200);
    }
    
    if (($job['status'] ?? '') !== 'completed') {
        return response()->json(['error' => 'Job not completed', 'status' => $job['status'] ?? 'unknown'], 409);
    }
    
    return response()->json([
        'success' => true,
        'job_id' => $job['id'],
        'tool_type' => 'flashcards',
        'input_type' => 'file',
        'data' => $job['result'] ?? null
    ]);
});

// 📊 PRESENTATIONS TOOL ENDPOINTS


// 📊 DIAGRAM TOOL ENDPOINTS

// Diagram Status
Route::get('/status/diagram', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'diagram', 'default');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    return response()->json([
        'job_id' => $job['id'],
        'tool_type' => 'diagram',
        'status' => $job['status'] ?? 'unknown',
        'progress' => $job['progress'] ?? 0,
        'stage' => $job['stage'] ?? null,
        'error' => $job['error'] ?? null,
        'created_at' => $job['created_at'] ?? null,
        'updated_at' => $job['updated_at'] ?? null
    ]);
});

// Diagram Result
Route::get('/result/diagram', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'diagram', 'default');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    if (($job['status'] ?? '') !== 'completed') {
        return response()->json(['error' => 'Job not completed', 'status' => $job['status'] ?? 'unknown'], 409);
    }
    
    return response()->json([
        'success' => true,
        'job_id' => $job['id'],
        'tool_type' => 'diagram',
        'data' => $job['result'] ?? null
    ]);
});

// 💬 DOCUMENT CHAT TOOL ENDPOINTS

// Document Chat File Status
Route::get('/status/document_chat/file', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'document_chat', 'file');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    return response()->json([
        'job_id' => $job['id'],
        'tool_type' => 'document_chat',
        'input_type' => 'file',
        'status' => $job['status'] ?? 'unknown',
        'progress' => $job['progress'] ?? 0,
        'stage' => $job['stage'] ?? null,
        'error' => $job['error'] ?? null,
        'created_at' => $job['created_at'] ?? null,
        'updated_at' => $job['updated_at'] ?? null
    ]);
});

// Document Chat File Result
Route::get('/result/document_chat/file', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'document_chat', 'file');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    if (($job['status'] ?? '') !== 'completed') {
        return response()->json(['error' => 'Job not completed', 'status' => $job['status'] ?? 'unknown'], 409);
    }
    
    return response()->json([
        'success' => true,
        'job_id' => $job['id'],
        'tool_type' => 'document_chat',
        'input_type' => 'file',
        'data' => $job['result'] ?? null
    ]);
});

// 📄 CONTENT EXTRACTION TOOL ENDPOINTS

// Content Extraction File Status
Route::get('/status/content_extraction/file', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'content_extraction', 'file');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    return response()->json([
        'job_id' => $job['id'],
        'tool_type' => 'content_extraction',
        'input_type' => 'file',
        'status' => $job['status'] ?? 'unknown',
        'progress' => $job['progress'] ?? 0,
        'stage' => $job['stage'] ?? null,
        'error' => $job['error'] ?? null,
        'created_at' => $job['created_at'] ?? null,
        'updated_at' => $job['updated_at'] ?? null
    ]);
});

// Content Extraction File Result
Route::get('/result/content_extraction/file', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'content_extraction', 'file');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    if (($job['status'] ?? '') !== 'completed') {
        return response()->json(['error' => 'Job not completed', 'status' => $job['status'] ?? 'unknown'], 409);
    }
    
    return response()->json([
        'success' => true,
        'job_id' => $job['id'],
        'tool_type' => 'content_extraction',
        'input_type' => 'file',
        'data' => $job['result'] ?? null
    ]);
});

// 🔄 DOCUMENT CONVERSION TOOL ENDPOINTS

// Document Conversion File Status
Route::get('/status/document_conversion/file', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'document_conversion', 'file');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    return response()->json([
        'job_id' => $job['id'],
        'tool_type' => 'document_conversion',
        'input_type' => 'file',
        'status' => $job['status'] ?? 'unknown',
        'progress' => $job['progress'] ?? 0,
        'stage' => $job['stage'] ?? null,
        'error' => $job['error'] ?? null,
        'created_at' => $job['created_at'] ?? null,
        'updated_at' => $job['updated_at'] ?? null
    ]);
});

// Document Conversion File Result
Route::get('/result/document_conversion/file', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'document_conversion', 'file');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    if (($job['status'] ?? '') !== 'completed') {
        return response()->json(['error' => 'Job not completed', 'status' => $job['status'] ?? 'unknown'], 409);
    }
    
    return response()->json([
        'success' => true,
        'job_id' => $job['id'],
        'tool_type' => 'document_conversion',
        'input_type' => 'file',
        'data' => $job['result'] ?? null
    ]);
});


// 🔹 Test route to manually authenticate and upload file (bypasses auth middleware)
Route::post('/test-upload-manual', function (Request $request) {
    $token = $request->bearerToken();
    $parts = explode('|', $token);

    if (count($parts) !== 2) {
        return response()->json(['error' => 'Invalid token format'], 401);
    }

    $tokenRecord = Laravel\Sanctum\PersonalAccessToken::where('token', hash('sha256', $parts[1]))->first();
    if (!$tokenRecord || !$tokenRecord->tokenable) {
        return response()->json(['error' => 'Unauthenticated'], 401);
    }

    auth()->login($tokenRecord->tokenable);

    // Delegate to controller
    return app(\App\Http\Controllers\Api\Client\FileUploadController::class)->upload($request);
});

// 🔹 Working summarize endpoint with manual authentication
// Generic /summarize/async endpoint removed - use specialized endpoints instead

// 🔹 Test token validation endpoint
Route::get('/test-token-validation', function (Request $request) {
    $token = $request->bearerToken();
    
    if (empty($token)) {
        return response()->json([
            'error' => 'Token not provided',
            'hint' => 'Include Authorization header: Bearer {token}'
        ], 401);
    }

    $parts = explode('|', $token);
    
    $result = [
        'token_provided' => !empty($token),
        'token_length' => strlen($token),
        'parts_count' => count($parts),
        'token_format_valid' => count($parts) === 2,
    ];

    if (count($parts) === 2) {
        $tokenRecord = Laravel\Sanctum\PersonalAccessToken::where('token', hash('sha256', $parts[1]))->first();
        $tokenById = Laravel\Sanctum\PersonalAccessToken::find($parts[0]);
        
        $result['token_found'] = $tokenRecord ? true : false;
        $result['token_id_exists'] = $tokenById ? true : false;
        
        if ($tokenRecord) {
            $result['user_id'] = $tokenRecord->tokenable_id;
            $result['user_type'] = get_class($tokenRecord->tokenable);
            $result['token_valid'] = true;
        } else {
            $result['token_valid'] = false;
            $result['hint'] = 'Token hash does not match. Token may be expired or invalid.';
        }
    } else {
        $result['token_valid'] = false;
        $result['hint'] = 'Token format should be: {token_id}|{token_hash}';
    }

    return response()->json($result);
});

// 🔹 Specialized Async Summarize Endpoints
// YouTube Video Summarization
Route::post('/summarize/async/youtube', function (Request $request) {
    $token = $request->bearerToken();
    
    if (empty($token)) {
        return response()->json([
            'error' => 'Token not provided',
            'hint' => 'Include Authorization header: Bearer {token}'
        ], 401);
    }

    $parts = explode('|', $token);

    if (count($parts) !== 2) {
        return response()->json([
            'error' => 'Invalid token format',
            'hint' => 'Token should be in format: {token_id}|{token_hash}',
            'received_format' => count($parts) . ' parts'
        ], 401);
    }

    $tokenRecord = Laravel\Sanctum\PersonalAccessToken::where('token', hash('sha256', $parts[1]))->first();

    if (!$tokenRecord) {
        // Try to find by token ID to provide better error
        $tokenById = Laravel\Sanctum\PersonalAccessToken::find($parts[0]);
        return response()->json([
            'error' => 'Token not found',
            'hint' => 'Token may be expired, revoked, or invalid. Please login again to get a new token.',
            'token_id_exists' => $tokenById ? true : false
        ], 401);
    }

    $user = $tokenRecord->tokenable;

    if (!$user) {
        return response()->json(['error' => 'User not found'], 401);
    }

    // Manually authenticate the user
    auth()->login($user);

    // Validate YouTube URL
    $request->validate([
        'url' => 'required|url',
        'options' => 'sometimes|array'
    ]);
    
    // Additional YouTube URL validation
    $url = $request->url;
    if (!preg_match('/^https?:\/\/(www\.)?(youtube\.com|youtu\.be)\/.+/', $url)) {
        return response()->json(['error' => 'Invalid YouTube URL'], 422);
    }

    // Create request with YouTube-specific format
    // Map 'detailed' to 'bundle' (API only accepts: plain, json, srt, article, bundle)
    $format = $request->options['format'] ?? 'bundle';
    $validFormats = ['plain', 'json', 'srt', 'article', 'bundle'];
    if (!in_array($format, $validFormats)) {
        // Map common invalid formats to valid ones
        if ($format === 'detailed') {
            $format = 'bundle'; // 'detailed' maps to 'bundle' which includes article_text
        } else {
            $format = 'bundle'; // Default fallback
        }
    }
    
    $youtubeRequest = new Request([
        'content_type' => 'link',
        'source' => [
            'type' => 'url',
            'data' => $request->url
        ],
        'options' => array_merge([
            'language' => 'en',
            'format' => $format,
            'focus' => 'summary'
        ], array_merge($request->options ?? [], ['format' => $format]))
    ]);

    $controller = app(\App\Http\Controllers\Api\Client\SummarizeController::class);
    return $controller->summarizeAsync($youtubeRequest);
});

// Text Summarization
Route::post('/summarize/async/text', function (Request $request) {
    $token = $request->bearerToken();
    $parts = explode('|', $token);

    if (count($parts) !== 2) {
        return response()->json(['error' => 'Invalid token format'], 401);
    }

    $tokenRecord = Laravel\Sanctum\PersonalAccessToken::where('token', hash('sha256', $parts[1]))->first();

    if (!$tokenRecord) {
        return response()->json(['error' => 'Token not found'], 401);
    }

    $user = $tokenRecord->tokenable;

    if (!$user) {
        return response()->json(['error' => 'User not found'], 401);
    }

    // Manually authenticate the user
    auth()->login($user);

    // Validate text content
    $request->validate([
        'text' => 'required|string|min:10',
        'options' => 'sometimes|array'
    ]);

    // Create request with text-specific format
    $textRequest = new Request([
        'content_type' => 'text',
        'source' => [
            'type' => 'text',
            'data' => $request->text
        ],
        'options' => array_merge([
            'language' => 'en',
            'format' => 'detailed',
            'focus' => 'summary'
        ], $request->options ?? [])
    ]);

    $controller = app(\App\Http\Controllers\Api\Client\SummarizeController::class);
    return $controller->summarizeAsync($textRequest);
});

// Audio/Video File Summarization (using file_id)
Route::post('/summarize/async/audiovideo', function (Request $request) {
    $token = $request->bearerToken();
    $parts = explode('|', $token);

    if (count($parts) !== 2) {
        return response()->json(['error' => 'Invalid token format'], 401);
    }

    $tokenRecord = Laravel\Sanctum\PersonalAccessToken::where('token', hash('sha256', $parts[1]))->first();

    if (!$tokenRecord) {
        return response()->json(['error' => 'Token not found'], 401);
    }

    $user = $tokenRecord->tokenable;

    if (!$user) {
        return response()->json(['error' => 'User not found'], 401);
    }

    // Manually authenticate the user
    auth()->login($user);

    // Validate file_id
    $request->validate([
        'file_id' => 'required|string|exists:file_uploads,id',
        'options' => 'sometimes|array'
    ]);

    // Use Universal File Management Module to get file
    $universalFileModule = app(\App\Services\Modules\UniversalFileManagementModule::class);
    
    // Get file using universal file management
    $fileResult = $universalFileModule->getFile($request->file_id);
    
    if (!$fileResult['success']) {
        return response()->json([
            'error' => 'File not found',
            'details' => $fileResult['error'] ?? 'File does not exist'
        ], 404);
    }
    
    $fileId = $request->file_id;
    
    // Create request with file-specific format
    $fileRequest = new Request([
        'content_type' => 'audio', // Use 'audio' as content type for validation
        'source' => [
            'type' => 'file',
            'data' => (string)$fileId // Ensure file ID is string
        ],
        'options' => array_merge([
            'language' => 'en',
            'format' => 'bundle',
            'focus' => 'summary'
        ], $request->options ?? [])
    ]);

    $controller = app(\App\Http\Controllers\Api\Client\SummarizeController::class);
    return $controller->summarizeAsync($fileRequest);
});


// Link Summarization (for any URL)
Route::post('/summarize/link', function (Request $request) {
    $token = $request->bearerToken();
    $parts = explode('|', $token);

    if (count($parts) !== 2) {
        return response()->json(['error' => 'Invalid token format'], 401);
    }

    $tokenRecord = Laravel\Sanctum\PersonalAccessToken::where('token', hash('sha256', $parts[1]))->first();

    if (!$tokenRecord) {
        return response()->json(['error' => 'Token not found'], 401);
    }

    $user = $tokenRecord->tokenable;

    if (!$user) {
        return response()->json(['error' => 'User not found'], 401);
    }

    // Manually authenticate the user
    auth()->login($user);

    // Validate URL
    $request->validate([
        'url' => 'required|url',
        'options' => 'sometimes|array'
    ]);

    // Create request with link-specific format
    $linkRequest = new Request([
        'content_type' => 'link',
        'source' => [
            'type' => 'url',
            'data' => $request->url
        ],
        'options' => array_merge([
            'language' => 'en',
            'format' => 'bundle',
            'focus' => 'summary'
        ], $request->options ?? [])
    ]);

    $controller = app(\App\Http\Controllers\Api\Client\SummarizeController::class);
    return $controller->summarizeAsync($linkRequest);
});

// 🔹 Authenticated
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', fn (Request $request) => $request->user());

    // Payments
    Route::post('/checkout', [StripeController::class, 'createCheckoutSession']);
    Route::get('/checkout/verify/{sessionId}', [StripeController::class, 'verifyCheckoutSession']);

    // Subscriptions
    Route::get('/subscription', [SubscriptionController::class, 'current']);
    Route::get('/subscription/history', [SubscriptionController::class, 'history']);
    Route::get('/usage', [SubscriptionController::class, 'usage']);

    // Tools (PDF summarization moved to /api/summarize/async)
    Route::post('/writer/run', [WriterController::class, 'run']);
    Route::post('/math/solve', [MathController::class, 'solve']);
    Route::get('/math/problems', [MathController::class, 'index']);
    Route::get('/math/problems/{id}', [MathController::class, 'show']);
    Route::delete('/math/problems/{id}', [MathController::class, 'destroy']);
    Route::get('/math/history', [MathController::class, 'history']);
    Route::get('/math/stats', [MathController::class, 'stats']);
    
    // Client API routes (for frontend compatibility)
    Route::prefix('client')->group(function () {
        // Handle CORS preflight requests
        Route::options('/math/generate', function () { 
            return response('', 200)
                ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
                ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
                ->header('Access-Control-Allow-Credentials', 'true');
        });
        Route::post('/math/generate', [MathController::class, 'solve']); // Alias for solve
        
        Route::get('/math/history', [MathController::class, 'history']);
        
        Route::options('/math/help', function () { 
            return response('', 200)
                ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
                ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
                ->header('Access-Control-Allow-Credentials', 'true');
        });
        Route::post('/math/help', [MathController::class, 'solve']); // Alias for solve
        
        Route::get('/math/stats', [MathController::class, 'stats']);
    });
    
    // Flashcards
    Route::post('/flashcards/generate', [FlashcardController::class, 'generate']);
    Route::get('/flashcards', [FlashcardController::class, 'index']);
    Route::get('/flashcards/public', [FlashcardController::class, 'public']);
    Route::get('/flashcards/{id}', [FlashcardController::class, 'show']);
    Route::put('/flashcards/{id}', [FlashcardController::class, 'update']);
    Route::delete('/flashcards/{id}', [FlashcardController::class, 'destroy']);
    
    // File Uploads
    Route::post('/files/upload', [FileUploadController::class, 'upload']);
    Route::post('/files/test-upload', [FileUploadController::class, 'testUpload']); // Test endpoint
    Route::get('/files', [FileUploadController::class, 'index']);
    Route::get('/files/{id}', [FileUploadController::class, 'show']);
    Route::delete('/files/{id}', [FileUploadController::class, 'destroy']);
    Route::get('/files/{id}/content', [FileUploadController::class, 'content']);
    
    // AI Results
    Route::get('/ai-results', [AIResultController::class, 'index']);
    Route::get('/ai-results/{id}', [AIResultController::class, 'show']);
    Route::put('/ai-results/{id}', [AIResultController::class, 'update']);
    Route::delete('/ai-results/{id}', [AIResultController::class, 'destroy']);
    Route::get('/ai-results/stats', [AIResultController::class, 'stats']);
    
    // Diagram Generation
    Route::post('/diagram/generate', [DiagramController::class, 'generate']);
    Route::get('/diagram/status', [DiagramController::class, 'status']);
    Route::get('/diagram/result', [DiagramController::class, 'result']);
    Route::get('/diagram', [DiagramController::class, 'index']);
    Route::get('/diagram/{aiResultId}', [DiagramController::class, 'show']);
    Route::delete('/diagram/{aiResultId}', [DiagramController::class, 'destroy']);
    Route::get('/diagram/types', [DiagramController::class, 'getTypes']);
    Route::get('/diagram/health', [DiagramController::class, 'health']);
    
    // File Processing and Conversion
    Route::post('/file-processing/convert', [FileExtractionController::class, 'convertDocument']);
    Route::get('/file-processing/convert/status', function (Request $request) {
        $jobId = $request->query('job_id');
        if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
        
        $service = app(\App\Services\UniversalJobService::class);
        $job = $service->getJob($jobId);
        if (!$job) return response()->json(['error' => 'Job not found'], 404);
        if ($job['tool_type'] !== 'document_conversion') {
            return response()->json(['error' => 'Job tool type mismatch'], 400);
        }
        
        return response()->json([
            'job_id' => $job['id'],
            'tool_type' => 'document_conversion',
            'input_type' => 'file',
            'status' => $job['status'] ?? 'unknown',
            'progress' => $job['progress'] ?? 0,
            'stage' => $job['stage'] ?? null,
            'error' => $job['error'] ?? null,
            'created_at' => $job['created_at'] ?? null,
            'updated_at' => $job['updated_at'] ?? null
        ]);
    });
    Route::get('/file-processing/convert/result', function (Request $request) {
        $jobId = $request->query('job_id');
        if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
        
        $service = app(\App\Services\UniversalJobService::class);
        $job = $service->getJob($jobId);
        if (!$job) return response()->json(['error' => 'Job not found'], 404);
        if ($job['tool_type'] !== 'document_conversion') {
            return response()->json(['error' => 'Job tool type mismatch'], 400);
        }
        
        if (($job['status'] ?? '') !== 'completed') {
            return response()->json(['error' => 'Job not completed', 'status' => $job['status'] ?? 'unknown'], 409);
        }
        
        return response()->json([
            'success' => true,
            'job_id' => $job['id'],
            'tool_type' => 'document_conversion',
            'input_type' => 'file',
            'data' => $job['result'] ?? null
        ]);
    });
    Route::post('/file-processing/extract', [FileExtractionController::class, 'extractContent']);
    // PDF Edit operations (uses universal file IDs and job scheduler)
    Route::post('/pdf/edit/{operation}', [PdfEditController::class, 'start']);
    Route::get('/file-processing/conversion-capabilities', [FileExtractionController::class, 'getCapabilities']);
    Route::get('/file-processing/extraction-capabilities', [FileExtractionController::class, 'getExtractionCapabilities']);
    Route::get('/file-processing/health', [FileExtractionController::class, 'checkHealth']);
    
    // File Upload Summarization (using file_id)
    Route::post('/summarize/async/file', function (Request $request) {
        $request->validate([
            'file_id' => 'required|string|exists:file_uploads,id',
            'options' => 'sometimes|array'
        ]);

        // Get file record to determine content type
        $file = \App\Models\FileUpload::find($request->file_id);
        if (!$file) {
            return response()->json([
                'error' => 'File not found'
            ], 404);
        }

        // Determine content_type from file type
        // Map file_type to valid content_type (pdf, image, audio, video)
        $fileType = strtolower($file->file_type ?? '');
        $contentType = 'pdf'; // Default
        
        if (in_array($fileType, ['pdf', 'doc', 'docx', 'txt'])) {
            $contentType = 'pdf'; // Documents use pdf content_type
        } elseif (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'])) {
            $contentType = 'image';
        } elseif (in_array($fileType, ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'])) {
            $contentType = 'audio';
        } elseif (in_array($fileType, ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv', 'webm'])) {
            $contentType = 'video';
        }
        
        // Use Universal File Management Module to get file
        $universalFileModule = app(\App\Services\Modules\UniversalFileManagementModule::class);
        
        // Get file using universal file management
        $fileResult = $universalFileModule->getFile($request->file_id);
        
        if (!$fileResult['success']) {
            return response()->json([
                'error' => 'File not found',
                'details' => $fileResult['error'] ?? 'File does not exist'
            ], 404);
        }
        
        $fileId = $request->file_id;
        
        // Create request with file-specific format
        // Map 'detailed' format to 'bundle' for consistency (API only accepts: plain, json, srt, article, bundle)
        $format = $request->options['format'] ?? 'bundle';
        $validFormats = ['plain', 'json', 'srt', 'article', 'bundle'];
        if (!in_array($format, $validFormats)) {
            if ($format === 'detailed') {
                $format = 'bundle';
            } else {
                $format = 'bundle';
            }
        }
        
        $fileRequest = new Request([
            'content_type' => $contentType, // Use actual file type (pdf, image, audio, video)
            'source' => [
                'type' => 'file',
                'data' => (string)$fileId
            ],
            'options' => array_merge([
                'language' => 'en',
                'format' => $format,
                'focus' => 'summary'
            ], $request->options ?? [])
        ]);

        $controller = app(\App\Http\Controllers\Api\Client\SummarizeController::class);
        return $controller->summarizeAsync($fileRequest);
    });
    
    // AI Chat
    Route::post('/chat', [ChatController::class, 'chat']);
    Route::post('/chat/create-and-chat', [ChatController::class, 'createAndChat']);
    Route::get('/chat/history', [ChatController::class, 'history']);
    
    // Chat Sessions
    Route::get('/chat/sessions', [ChatSessionController::class, 'index']);
    Route::post('/chat/sessions', [ChatSessionController::class, 'store']);
    Route::get('/chat/sessions/{sessionId}', [ChatSessionController::class, 'show']);
    Route::put('/chat/sessions/{sessionId}', [ChatSessionController::class, 'update']);
    Route::delete('/chat/sessions/{sessionId}', [ChatSessionController::class, 'destroy']);
    Route::post('/chat/sessions/{sessionId}/archive', [ChatSessionController::class, 'archive']);
    Route::post('/chat/sessions/{sessionId}/restore', [ChatSessionController::class, 'restore']);
    
    // Chat Messages
    Route::post('/chat/sessions/{sessionId}/messages', [ChatMessageController::class, 'store']);
    Route::get('/chat/sessions/{sessionId}/messages', [ChatMessageController::class, 'index']);
    Route::get('/chat/sessions/{sessionId}/history', [ChatMessageController::class, 'history']);
    
    // Document Chat
    Route::post('/chat/document', [DocumentChatController::class, 'chat']);
    Route::get('/chat/document/{documentId}/history', [DocumentChatController::class, 'history']);
    
    // AI Content Writer
    Route::post('/content/write', [ContentController::class, 'write']);
    Route::post('/content/rewrite', [ContentController::class, 'rewrite']);
    Route::get('/content/status', [ContentController::class, 'getStatus']);
    Route::get('/content/result', [ContentController::class, 'getResult']);
    
    // Document Intelligence
    Route::post('/documents/ingest', [DocumentIntelligenceController::class, 'ingest']);
    Route::post('/documents/ingest/text', [DocumentIntelligenceController::class, 'ingestText']);
    Route::post('/documents/search', [DocumentIntelligenceController::class, 'search']);
    Route::post('/documents/answer', [DocumentIntelligenceController::class, 'answer']);
    Route::post('/documents/chat', [DocumentIntelligenceController::class, 'chat']);
    Route::get('/documents/jobs/{jobId}/status', [DocumentIntelligenceController::class, 'getStatus']);
    Route::get('/documents/jobs/{jobId}/result', [DocumentIntelligenceController::class, 'getResult']);
    Route::get('/documents/health', [DocumentIntelligenceController::class, 'health']);
    
    // AI Manager - Models
    Route::get('/models', function (Request $request) {
        $aiManagerService = app(\App\Services\AIManagerService::class);
        $result = $aiManagerService->getAvailableModels();
        
        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'available_models' => $result['models'],
                    'count' => $result['count']
                ]
            ]);
        }
        
        return response()->json([
            'status' => 'error',
            'message' => $result['error'] ?? 'Failed to retrieve models'
        ], 500);
    });
    
    // Document Chat (simplified endpoint with conversation_id management)
    Route::post('/document/chat', function (Request $request) {
        $request->validate([
            'doc_id' => 'required|string',
            'query' => 'required|string|max:1000',
            'conversation_id' => 'nullable|string|max:200',
            'llm_model' => 'nullable|string|max:50',
            'max_tokens' => 'nullable|integer|min:50|max:2000',
            'top_k' => 'nullable|integer|min:1|max:10'
        ]);

        try {
            $userId = auth()->id();
            $docId = $request->input('doc_id');
            $query = $request->input('query');
            $conversationId = $request->input('conversation_id');

            // Find or create conversation record
            $conversation = \App\Models\DocumentConversation::findOrCreateForDoc(
                $docId,
                $userId,
                $conversationId
            );

            // Use the conversation_id from the record
            $conversationId = $conversation->conversation_id;

            // Call Document Intelligence chat
            $docIntelligenceService = app(\App\Services\DocumentIntelligenceService::class);
            $chatResult = $docIntelligenceService->chat($query, [
                'doc_ids' => [$docId],
                'conversation_id' => $conversationId,
                'llm_model' => $request->input('llm_model', 'llama3'),
                'max_tokens' => $request->input('max_tokens', 512),
                'top_k' => $request->input('top_k', 3),
                'force_fallback' => true // Always true for Document Intelligence microservice
            ]);

            return response()->json([
                'success' => true,
                'conversation_id' => $chatResult['conversation_id'] ?? $conversationId,
                'answer' => $chatResult['answer'] ?? '',
                'sources' => $chatResult['sources'] ?? [],
                'doc_id' => $docId
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Document chat failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Chat failed: ' . $e->getMessage()
            ], 500);
        }
    });
});

// 🔹 Admin Processing Dashboard Routes
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    Route::get('/processing/overview', [\App\Http\Controllers\Api\Admin\ProcessingDashboardController::class, 'getOverview']);
    Route::get('/processing/statistics', [\App\Http\Controllers\Api\Admin\ProcessingDashboardController::class, 'getStatistics']);
    Route::get('/processing/performance', [\App\Http\Controllers\Api\Admin\ProcessingDashboardController::class, 'getPerformanceMetrics']);
    Route::get('/processing/health', [\App\Http\Controllers\Api\Admin\ProcessingDashboardController::class, 'getSystemHealth']);
    Route::get('/processing/cache', [\App\Http\Controllers\Api\Admin\ProcessingDashboardController::class, 'getCacheStatistics']);
    Route::get('/processing/batch', [\App\Http\Controllers\Api\Admin\ProcessingDashboardController::class, 'getBatchStatistics']);
    Route::get('/processing/jobs', [\App\Http\Controllers\Api\Admin\ProcessingDashboardController::class, 'getJobStatistics']);
    Route::get('/processing/activity', [\App\Http\Controllers\Api\Admin\ProcessingDashboardController::class, 'getRecentActivity']);
    Route::get('/processing/trends', [\App\Http\Controllers\Api\Admin\ProcessingDashboardController::class, 'getProcessingTrends']);
    Route::get('/processing/file-types', [\App\Http\Controllers\Api\Admin\ProcessingDashboardController::class, 'getFileTypeDistribution']);
    Route::get('/processing/tools', [\App\Http\Controllers\Api\Admin\ProcessingDashboardController::class, 'getToolUsageDistribution']);
    Route::post('/processing/cache/clear', [\App\Http\Controllers\Api\Admin\ProcessingDashboardController::class, 'clearCache']);
    Route::post('/processing/cache/warm', [\App\Http\Controllers\Api\Admin\ProcessingDashboardController::class, 'warmUpCache']);
    
    // Content Summarization
    Route::post('/summarize', [SummarizeController::class, 'summarize']);
    Route::post('/summarize/async', [SummarizeController::class, 'summarizeAsync']);
    // status/result endpoints are defined outside the auth group with manual bearer validation
    Route::post('/summarize/validate', [SummarizeController::class, 'validateFile']);
    
    // AI Presentation Generator - CORS OPTIONS routes
    Route::options('/presentations/generate-outline', function () { 
        return response('', 200)
            ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
            ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
            ->header('Access-Control-Allow-Credentials', 'true');
    });
    Route::options('/presentations/templates', function () { 
        return response('', 200)
            ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
            ->header('Access-Control-Allow-Credentials', 'true');
    });
    Route::options('/presentations/generate-content', function () { 
        return response('', 200)
            ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
            ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
            ->header('Access-Control-Allow-Credentials', 'true');
    });
    Route::options('/presentations/export', function () { 
        return response('', 200)
            ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
            ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
            ->header('Access-Control-Allow-Credentials', 'true');
    });
    Route::options('/presentations/files', function () { 
        return response('', 200)
            ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
            ->header('Access-Control-Allow-Credentials', 'true');
    });
    Route::options('/presentations/files/{fileId}', function () { 
        return response('', 200)
            ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
            ->header('Access-Control-Allow-Methods', 'GET, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
            ->header('Access-Control-Allow-Credentials', 'true');
    });
    Route::options('/presentations/files/{fileId}/download', function () { 
        return response('', 200)
            ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
            ->header('Access-Control-Allow-Credentials', 'true');
    });
    Route::options('/presentations/microservice-status', function () { 
        return response('', 200)
            ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
            ->header('Access-Control-Allow-Credentials', 'true');
    });
    Route::options('/presentations/status', function () { 
        return response('', 200)
            ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
            ->header('Access-Control-Allow-Credentials', 'true');
    });
    Route::options('/presentations/result', function () { 
        return response('', 200)
            ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
            ->header('Access-Control-Allow-Credentials', 'true');
    });

    // AI Presentation Generator - New Data Flow
    Route::post('/presentations/generate-content', [PresentationController::class, 'generateContent']);
    Route::post('/presentations/export', [PresentationController::class, 'exportPresentation']);
    
    // Presentation File Management
    Route::get('/presentations/files', [PresentationController::class, 'getPresentationFiles']);
    Route::delete('/presentations/files/{fileId}', [PresentationController::class, 'deletePresentationFile']);
    Route::get('/presentations/files/{fileId}/download', [PresentationController::class, 'downloadPresentationFile']);
    
    // Presentation Job Status & Result (for polling)
    Route::get('/presentations/status', [PresentationController::class, 'getJobStatus']);
    Route::get('/presentations/result', [PresentationController::class, 'getJobResult']);
    
    // Status & Health
    Route::get('/presentations/microservice-status', [PresentationController::class, 'checkMicroserviceStatus']);
});

// 🔹 Admin Authentication (Public)
Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login']);
    Route::post('/password/reset', [AdminPasswordResetController::class, 'sendResetLink']);
    Route::post('/password/reset/verify', [AdminPasswordResetController::class, 'verifyToken']);
    Route::post('/password/reset/confirm', [AdminPasswordResetController::class, 'reset']);
});

// 🔹 Admin Routes (Protected)
Route::prefix('admin')->middleware(['auth:sanctum', 'admin.auth'])->group(function () {
    
    // ========================================
    // 🔐 ADMIN AUTHENTICATION ROUTES
    // ========================================
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.auth.logout');
        Route::get('/me', [AdminAuthController::class, 'me'])->name('admin.auth.me');
        Route::post('/password/change', [AdminAuthController::class, 'changePassword'])->name('admin.auth.password.change');
    });

    // ========================================
    // 📊 DASHBOARD & ANALYTICS ROUTES
    // ========================================
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard.index');
        Route::get('/stats', [DashboardController::class, 'stats'])->name('admin.dashboard.stats');
        Route::get('/analytics', [DashboardController::class, 'analytics'])->name('admin.dashboard.analytics');
        Route::get('/revenue', [DashboardController::class, 'revenue'])->name('admin.dashboard.revenue');
        Route::get('/users', [DashboardController::class, 'users'])->name('admin.dashboard.users');
        Route::get('/subscriptions', [DashboardController::class, 'subscriptions'])->name('admin.dashboard.subscriptions');
        
        // Enhanced analytics routes
        Route::get('/mrr', [DashboardController::class, 'mrr'])->name('admin.dashboard.mrr');
        Route::get('/arr', [DashboardController::class, 'arr'])->name('admin.dashboard.arr');
        Route::get('/subscription-growth', [DashboardController::class, 'subscriptionGrowth'])->name('admin.dashboard.subscription-growth');
        Route::get('/revenue-by-plan', [DashboardController::class, 'revenueByPlan'])->name('admin.dashboard.revenue-by-plan');
        Route::get('/subscription-analytics', [DashboardController::class, 'subscriptionAnalytics'])->name('admin.dashboard.subscription-analytics');
    });

    // ========================================
    // 👥 USER MANAGEMENT ROUTES
    // ========================================
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('admin.users.index');
        Route::post('/', [UserController::class, 'store'])->name('admin.users.store');
        
        // Bulk operations
        Route::post('/bulk/activate', [UserController::class, 'bulkActivate'])->name('admin.users.bulk.activate');
        Route::post('/bulk/deactivate', [UserController::class, 'bulkDeactivate'])->name('admin.users.bulk.deactivate');
        Route::post('/bulk/delete', [UserController::class, 'bulkDelete'])->name('admin.users.bulk.delete');
    });
    
    // User-specific actions (separate group to avoid route conflicts)
    Route::prefix('users')->group(function () {
        Route::post('/{user}/activate', [UserController::class, 'activate'])->name('admin.users.activate');
        Route::post('/{user}/deactivate', [UserController::class, 'deactivate'])->name('admin.users.deactivate');
        Route::post('/{user}/suspend', [UserController::class, 'suspend'])->name('admin.users.suspend');
        Route::get('/{user}/subscriptions', [UserController::class, 'subscriptions'])->name('admin.users.subscriptions');
        Route::get('/{user}/usage', [UserController::class, 'usage'])->name('admin.users.usage');
        Route::get('/{user}/activity', [UserController::class, 'activity'])->name('admin.users.activity');
        
        // Basic CRUD routes
        Route::get('/{user}', [UserController::class, 'show'])->name('admin.users.show');
        Route::put('/{user}', [UserController::class, 'update'])->name('admin.users.update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
    });

    // ========================================
    // 💳 PLAN MANAGEMENT ROUTES
    // ========================================
    Route::prefix('plans')->group(function () {
        Route::get('/', [AdminPlanController::class, 'index'])->name('admin.plans.index');
        Route::post('/', [AdminPlanController::class, 'store'])->name('admin.plans.store');
        
        // Plan-specific actions (must come before {plan} routes)
        Route::post('/{plan}/activate', [AdminPlanController::class, 'activate'])->name('admin.plans.activate');
        Route::post('/{plan}/deactivate', [AdminPlanController::class, 'deactivate'])->name('admin.plans.deactivate');
        Route::get('/{plan}/subscriptions', [AdminPlanController::class, 'subscriptions'])->name('admin.plans.subscriptions');
        Route::get('/{plan}/analytics', [AdminPlanController::class, 'analytics'])->name('admin.plans.analytics');
        
        // Bulk operations
        Route::post('/bulk-update', [AdminPlanController::class, 'bulkUpdate'])->name('admin.plans.bulk-update');
        Route::post('/{plan}/duplicate', [AdminPlanController::class, 'duplicate'])->name('admin.plans.duplicate');
        
        // Basic CRUD routes (must come last)
        Route::get('/{plan}', [AdminPlanController::class, 'show'])->name('admin.plans.show');
        Route::put('/{plan}', [AdminPlanController::class, 'update'])->name('admin.plans.update');
        Route::delete('/{plan}', [AdminPlanController::class, 'destroy'])->name('admin.plans.destroy');
    });

    // ========================================
    // 📋 SUBSCRIPTION MANAGEMENT ROUTES
    // ========================================
    Route::prefix('subscriptions')->group(function () {
        Route::get('/', [AdminSubscriptionController::class, 'index'])->name('admin.subscriptions.index');
        Route::post('/', [AdminSubscriptionController::class, 'store'])->name('admin.subscriptions.store');
        Route::get('/{subscription}', [AdminSubscriptionController::class, 'show'])->name('admin.subscriptions.show');
        Route::put('/{subscription}', [AdminSubscriptionController::class, 'update'])->name('admin.subscriptions.update');
        Route::delete('/{subscription}', [AdminSubscriptionController::class, 'destroy'])->name('admin.subscriptions.destroy');
        
        // Subscription-specific actions
        Route::post('/{subscription}/activate', [AdminSubscriptionController::class, 'activate'])->name('admin.subscriptions.activate');
        Route::post('/{subscription}/cancel', [AdminSubscriptionController::class, 'cancel'])->name('admin.subscriptions.cancel');
        Route::post('/{subscription}/pause', [AdminSubscriptionController::class, 'pause'])->name('admin.subscriptions.pause');
        Route::post('/{subscription}/resume', [AdminSubscriptionController::class, 'resume'])->name('admin.subscriptions.resume');
        Route::post('/{subscription}/upgrade', [AdminSubscriptionController::class, 'upgrade'])->name('admin.subscriptions.upgrade');
        Route::post('/{subscription}/downgrade', [AdminSubscriptionController::class, 'downgrade'])->name('admin.subscriptions.downgrade');
        
        // Bulk operations
        Route::post('/bulk-activate', [AdminSubscriptionController::class, 'bulkActivate'])->name('admin.subscriptions.bulk-activate');
        Route::post('/bulk-cancel', [AdminSubscriptionController::class, 'bulkCancel'])->name('admin.subscriptions.bulk-cancel');
        
        // Additional operations
        Route::post('/{subscription}/apply-grace-period', [AdminSubscriptionController::class, 'applyGracePeriod'])->name('admin.subscriptions.apply-grace-period');
        Route::get('/{subscription}/payment-history', [AdminSubscriptionController::class, 'paymentHistory'])->name('admin.subscriptions.payment-history');
        
        // Analytics and reporting
        Route::get('/analytics/overview', [AdminSubscriptionController::class, 'analytics'])->name('admin.subscriptions.analytics');
        Route::get('/analytics/revenue', [AdminSubscriptionController::class, 'revenue'])->name('admin.subscriptions.revenue');
        Route::get('/analytics/churn', [AdminSubscriptionController::class, 'churn'])->name('admin.subscriptions.churn');
        Route::get('/analytics/conversion', [AdminSubscriptionController::class, 'conversion'])->name('admin.subscriptions.conversion');
    });

    // ========================================
    // 🛠️ TOOL MANAGEMENT ROUTES
    // ========================================
    Route::prefix('tools')->group(function () {
        Route::get('/', [ToolUsageController::class, 'index'])->name('admin.tools.index');
        Route::post('/', [ToolUsageController::class, 'store'])->name('admin.tools.store');
        Route::get('/{tool}', [ToolUsageController::class, 'show'])->name('admin.tools.show');
        Route::put('/{tool}', [ToolUsageController::class, 'update'])->name('admin.tools.update');
        Route::delete('/{tool}', [ToolUsageController::class, 'destroy'])->name('admin.tools.destroy');
        
        // Tool-specific actions
        Route::post('/{tool}/activate', [ToolUsageController::class, 'activate'])->name('admin.tools.activate');
        Route::post('/{tool}/deactivate', [ToolUsageController::class, 'deactivate'])->name('admin.tools.deactivate');
        Route::get('/{tool}/usage', [ToolUsageController::class, 'usage'])->name('admin.tools.usage');
        Route::get('/{tool}/analytics', [ToolUsageController::class, 'analytics'])->name('admin.tools.analytics');
        Route::get('/{tool}/users', [ToolUsageController::class, 'users'])->name('admin.tools.users');
    });

    // ========================================
    // 📈 VISITOR & ANALYTICS ROUTES
    // ========================================
    Route::prefix('visitors')->group(function () {
        Route::get('/', [VisitorController::class, 'index'])->name('admin.visitors.index');
        Route::get('/analytics', [VisitorController::class, 'analytics'])->name('admin.visitors.analytics');
        Route::get('/geographic', [VisitorController::class, 'geographic'])->name('admin.visitors.geographic');
        Route::get('/demographic', [VisitorController::class, 'demographic'])->name('admin.visitors.demographic');
        Route::get('/behavior', [VisitorController::class, 'behavior'])->name('admin.visitors.behavior');
        Route::get('/sources', [VisitorController::class, 'sources'])->name('admin.visitors.sources');
        Route::get('/devices', [VisitorController::class, 'devices'])->name('admin.visitors.devices');
        Route::get('/export', [VisitorController::class, 'export'])->name('admin.visitors.export');
    });

    // ========================================
    // 📊 VISITOR TRACKING ROUTES (Admin Only)
    // ========================================
    Route::prefix('visitor-tracking')->group(function () {
        Route::get('/', [AdminVisitorTrackingController::class, 'index'])->name('admin.visitor-tracking.index');
        Route::get('/statistics', [AdminVisitorTrackingController::class, 'statistics'])->name('admin.visitor-tracking.statistics');
        Route::get('/{id}', [AdminVisitorTrackingController::class, 'show'])->name('admin.visitor-tracking.show');
        Route::put('/{id}', [AdminVisitorTrackingController::class, 'update'])->name('admin.visitor-tracking.update');
        Route::patch('/{id}', [AdminVisitorTrackingController::class, 'update'])->name('admin.visitor-tracking.update');
        Route::delete('/{id}', [AdminVisitorTrackingController::class, 'destroy'])->name('admin.visitor-tracking.destroy');
    });

    // ========================================
    // 📊 REPORTS & EXPORTS ROUTES
    // ========================================
    Route::prefix('reports')->group(function () {
        Route::get('/users', [UserController::class, 'export'])->name('admin.reports.users');
        Route::get('/subscriptions', [AdminSubscriptionController::class, 'export'])->name('admin.reports.subscriptions');
        Route::get('/revenue', [DashboardController::class, 'exportRevenue'])->name('admin.reports.revenue');
        Route::get('/analytics', [DashboardController::class, 'exportAnalytics'])->name('admin.reports.analytics');
        Route::get('/usage', [ToolUsageController::class, 'export'])->name('admin.reports.usage');
    });

    // ========================================
    // ⚙️ SYSTEM ADMINISTRATION ROUTES
    // ========================================
    Route::prefix('system')->group(function () {
        Route::get('/health', [DashboardController::class, 'systemHealth'])->name('admin.system.health');
        Route::get('/logs', [DashboardController::class, 'systemLogs'])->name('admin.system.logs');
        Route::get('/performance', [DashboardController::class, 'systemPerformance'])->name('admin.system.performance');
        Route::get('/cache', [DashboardController::class, 'cacheStatus'])->name('admin.system.cache');
        Route::post('/cache/clear', [DashboardController::class, 'clearCache'])->name('admin.system.cache.clear');
        Route::get('/database', [DashboardController::class, 'databaseStatus'])->name('admin.system.database');
        Route::post('/maintenance', [DashboardController::class, 'maintenanceMode'])->name('admin.system.maintenance');
    });
});