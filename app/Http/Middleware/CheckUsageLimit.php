<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\UsageWarningMail;

class CheckUsageLimit
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $subscription = $user->subscription;

        if (!$subscription || !$subscription->plan) {
            return response()->json(['error' => 'No active subscription'], 403);
        }

        $used = $subscription->histories()->count();
        $limit = $subscription->plan->limit;

        if ($limit && $used >= $limit) {
            return response()->json(['error' => 'Usage limit reached'], 403);
        }

        // ⚠️ Warn at 80%
        if ($limit && $used >= ($limit * 0.8) && !$subscription->warned) {
            Mail::to($user->email)->send(new UsageWarningMail($user, $subscription));
            $subscription->update(['warned' => true]); // add warned column in migration
        }

        return $next($request);
    }
}