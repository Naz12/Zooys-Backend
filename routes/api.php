<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Client\AuthController;
use App\Http\Controllers\Api\Client\StripeController;
use App\Http\Controllers\Api\Client\PlanController;
use App\Http\Controllers\Api\Client\SubscriptionController;
use App\Http\Controllers\Api\Client\YoutubeController;
use App\Http\Controllers\Api\Client\PdfController;
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

// 🔹 Authenticated
Route::middleware(['auth:sanctum', 'check.usage'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', fn (Request $request) => $request->user());

    // Payments
    Route::post('/checkout', [StripeController::class, 'createCheckoutSession']);

    // Subscriptions
    Route::get('/subscription', [SubscriptionController::class, 'current']);
    Route::get('/subscription/history', [SubscriptionController::class, 'history']);
    Route::get('/usage', [SubscriptionController::class, 'usage']);

    // Tools
    Route::post('/youtube/summarize', [YoutubeController::class, 'summarize']);
    Route::post('/pdf/summarize', [PdfController::class, 'summarize']);
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
    
    // Content Summarization
    Route::post('/summarize', [SummarizeController::class, 'summarize']);
    Route::post('/summarize/validate', [SummarizeController::class, 'validateFile']);
    
    // Document Chat
    Route::post('/chat/document', [DocumentChatController::class, 'chat']);
    Route::get('/chat/document/{documentId}/history', [DocumentChatController::class, 'history']);
    
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