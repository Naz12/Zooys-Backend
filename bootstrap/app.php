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

        // ✅ Aliases
        $middleware->alias([
            'check.usage' => \App\Http\Middleware\CheckUsageLimit::class,
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
            'admin.session' => \App\Http\Middleware\AdminSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();