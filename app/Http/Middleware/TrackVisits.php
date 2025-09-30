<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Visit;

class TrackVisits
{
    protected array $botKeywords = [
        'bot', 'crawl', 'spider', 'slurp', 'curl',
        'facebookexternalhit', 'bingpreview', 'python-requests',
        'axios', 'wget', 'httpclient'
    ];

    public function handle($request, Closure $next)
    {
        if ($request->isMethod('get')) {
            $path = $request->path();

            // Skip admin, API, Filament
            if (
                str_starts_with($path, 'admin') ||
                str_starts_with($path, 'api') ||
                str_starts_with($path, 'filament')
            ) {
                return $next($request);
            }

            $userAgent = $request->header('User-Agent', 'unknown');

            // Detect bot
            $isBot = false;
            foreach ($this->botKeywords as $keyword) {
                if (stripos($userAgent, $keyword) !== false) {
                    $isBot = true;
                    break;
                }
            }

            // ✅ Log visit (store bot flag too)
            Visit::create([
                'ip'         => $request->ip(),
                'user_agent' => $userAgent,
                'visited_at' => now(),
                'is_bot'     => $isBot,
            ]);
        }

        return $next($request);
    }
}