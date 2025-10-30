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
use App\Http\Controllers\Api\Client\FileExtractionController;

// Admin Controllers
use App\Http\Controllers\Api\Admin\AdminAuthController;
use App\Http\Controllers\Api\Admin\AdminPasswordResetController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Api\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Api\Admin\ToolUsageController;
use App\Http\Controllers\Api\Admin\VisitorController;

// 🔹 Public
Route::get('/plans', [PlanController::class, 'index']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 🔹 Public Presentation Routes (for testing)
Route::get('/presentations/templates', [PresentationController::class, 'getTemplates']);
Route::post('/presentations/generate-outline', [PresentationController::class, 'generateOutline']);
Route::post('/presentations/{aiResultId}/generate-content', [PresentationController::class, 'generateContent']);
Route::post('/presentations/{aiResultId}/export', [PresentationController::class, 'exportPresentation']);
Route::get('/presentations/{aiResultId}/data', [PresentationController::class, 'getPresentationData']);
Route::post('/presentations/{aiResultId}/save', [PresentationController::class, 'savePresentation']);
Route::get('/files/download/{filename}', [PresentationController::class, 'downloadPresentation']);

// 🔹 Public Presentation Management Routes
Route::get('/presentations', [PresentationController::class, 'getPresentations']);
Route::delete('/presentations/{aiResultId}', [PresentationController::class, 'deletePresentation']);

// CORS OPTIONS for public presentation routes
Route::options('/presentations/{aiResultId}/export', function () { 
    return response('', 200)
        ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
        ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
        ->header('Access-Control-Allow-Credentials', 'true');
});
Route::options('/presentations', function () { 
    return response('', 200)
        ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
        ->header('Access-Control-Allow-Methods', 'GET, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
        ->header('Access-Control-Allow-Credentials', 'true');
});
Route::options('/presentations/{aiResultId}', function () { 
    return response('', 200)
        ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
        ->header('Access-Control-Allow-Methods', 'DELETE, OPTIONS')
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
    return response()->json([
        'job_id' => $job['id'],
        'tool_type' => 'summarize',
        'input_type' => 'file',
        'status' => $job['status'] ?? 'unknown',
        'progress' => $job['progress'] ?? 0,
        'stage' => $job['stage'] ?? null,
        'error' => $job['error'] ?? null,
        'created_at' => $job['created_at'] ?? null,
        'updated_at' => $job['updated_at'] ?? null
    ]);
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

// Presentations Text Status
Route::get('/status/presentations/text', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'presentations', 'text');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    return response()->json([
        'job_id' => $job['id'],
        'tool_type' => 'presentations',
        'input_type' => 'text',
        'status' => $job['status'] ?? 'unknown',
        'progress' => $job['progress'] ?? 0,
        'stage' => $job['stage'] ?? null,
        'error' => $job['error'] ?? null,
        'created_at' => $job['created_at'] ?? null,
        'updated_at' => $job['updated_at'] ?? null
    ]);
});

// Presentations Text Result
Route::get('/result/presentations/text', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'presentations', 'text');
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
        'tool_type' => 'presentations',
        'input_type' => 'text',
        'data' => $job['result'] ?? null
    ]);
});

// Presentations File Status
Route::get('/status/presentations/file', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'presentations', 'file');
    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['code']);
    }
    
    $job = $result['job'];
    return response()->json([
        'job_id' => $job['id'],
        'tool_type' => 'presentations',
        'input_type' => 'file',
        'status' => $job['status'] ?? 'unknown',
        'progress' => $job['progress'] ?? 0,
        'stage' => $job['stage'] ?? null,
        'error' => $job['error'] ?? null,
        'created_at' => $job['created_at'] ?? null,
        'updated_at' => $job['updated_at'] ?? null
    ]);
});

// Presentations File Result
Route::get('/result/presentations/file', function (Request $request) use ($authenticateUser, $getJobForTool) {
    $authResult = $authenticateUser($request);
    if ($authResult) return $authResult;
    
    $jobId = $request->query('job_id');
    if (!$jobId) return response()->json(['error' => 'job_id parameter is required'], 400);
    
    $result = $getJobForTool($jobId, 'presentations', 'file');
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
        'tool_type' => 'presentations',
        'input_type' => 'file',
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

// 🔹 Specialized Async Summarize Endpoints
// YouTube Video Summarization
Route::post('/summarize/async/youtube', function (Request $request) {
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
    $youtubeRequest = new Request([
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

// Image Summarization (using file_id)
Route::post('/summarize/async/image', function (Request $request) {
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
    
    // Create request with image-specific format
    $imageRequest = new Request([
        'content_type' => 'image',
        'source' => [
            'type' => 'file',
            'data' => (string)$fileId // Ensure file ID is string
        ],
        'options' => array_merge([
            'language' => 'en',
            'format' => 'detailed',
            'focus' => 'summary'
        ], $request->options ?? [])
    ]);

    $controller = app(\App\Http\Controllers\Api\Client\SummarizeController::class);
    return $controller->summarizeAsync($imageRequest);
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
    
    Route::post('/diagram/generate', [DiagramController::class, 'generate']);
    
    // File Processing and Conversion
    Route::post('/file-processing/convert', [FileExtractionController::class, 'convertDocument']);
    Route::post('/file-processing/extract', [FileExtractionController::class, 'extractContent']);
    Route::get('/file-processing/conversion-capabilities', [FileExtractionController::class, 'getCapabilities']);
    Route::get('/file-processing/extraction-capabilities', [FileExtractionController::class, 'getExtractionCapabilities']);
    Route::get('/file-processing/health', [FileExtractionController::class, 'checkHealth']);
    
    // File Upload Summarization (using file_id)
    Route::post('/summarize/async/file', function (Request $request) {
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
            'content_type' => 'file',
            'source' => [
                'type' => 'file',
                'data' => (string)$fileId
            ],
            'options' => array_merge([
                'language' => 'en',
                'format' => 'detailed',
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
    Route::options('/presentations/{aiResultId}/generate-content', function () { 
        return response('', 200)
            ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
            ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
            ->header('Access-Control-Allow-Credentials', 'true');
    });
    Route::options('/presentations/{aiResultId}/generate-powerpoint', function () { 
        return response('', 200)
            ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
            ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
            ->header('Access-Control-Allow-Credentials', 'true');
    });
    Route::options('/presentations/{aiResultId}/save', function () { 
        return response('', 200)
            ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
            ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
            ->header('Access-Control-Allow-Credentials', 'true');
    });
    Route::options('/presentations/{aiResultId}/data', function () { 
        return response('', 200)
            ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
            ->header('Access-Control-Allow-Credentials', 'true');
    });
    Route::options('/presentations/{aiResultId}', function () { 
        return response('', 200)
            ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
            ->header('Access-Control-Allow-Methods', 'GET, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
            ->header('Access-Control-Allow-Credentials', 'true');
    });
    Route::options('/presentations', function () { 
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
    Route::options('/presentations/{aiResultId}/update-outline', function () { 
        return response('', 200)
            ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
            ->header('Access-Control-Allow-Methods', 'PUT, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept')
            ->header('Access-Control-Allow-Credentials', 'true');
    });

    // AI Presentation Generator
    Route::put('/presentations/{aiResultId}/update-outline', [PresentationController::class, 'updateOutline']);
    Route::post('/presentations/{aiResultId}/generate-content', [PresentationController::class, 'generateContent']);
    Route::post('/presentations/{aiResultId}/generate-powerpoint', [PresentationController::class, 'generatePowerPoint']);
    Route::get('/presentations/{aiResultId}', [PresentationController::class, 'getPresentation']);
    
    // Frontend Editing Endpoints (JSON-based)
    Route::get('/presentations/{aiResultId}/status', [PresentationController::class, 'getProgressStatus']);
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