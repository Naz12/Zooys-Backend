<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: __DIR__ . '/../routes/health.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ✅ Global middleware
        $middleware->append(\App\Http\Middleware\TrackVisits::class);
        
        // ✅ CORS middleware for all routes
        $middleware->web(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
        
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        // ✅ Aliases
        $middleware->alias([
            'check.usage' => \App\Http\Middleware\CheckUsageLimit::class,
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
            'admin.session' => \App\Http\Middleware\AdminSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle authentication exceptions for API routes
        $exceptions->render(function (Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                    'error' => 'Authentication required'
                ], 401);
            }
        });

        // Handle route not found exceptions for API routes
        $exceptions->render(function (Symfony\Component\Routing\Exception\RouteNotFoundException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Route not found.',
                    'error' => 'The requested API endpoint does not exist'
                ], 404);
            }
        });
    })
    ->create();