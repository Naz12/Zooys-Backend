<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\SubscriptionUsageService;
use App\Services\UsageAlertService;

class CheckUsageLimit
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $subscription = $user->getActiveSubscription();

        if (!$subscription) {
            return response()->json([
                'error' => 'No active subscription',
                'message' => 'Please subscribe to a plan to use this service'
            ], 403);
        }

        $usageService = app(SubscriptionUsageService::class);
        $alertService = app(UsageAlertService::class);

        // Check if usage should be reset
        if ($subscription->shouldResetUsage()) {
            $usageService->resetMonthlyUsage($subscription);
        }

        // Check if user can make requests
        if (!$usageService->checkLimit($user)) {
            // Check if in grace period
            if ($subscription->isInGracePeriod()) {
                // Allow request but log warning
                \Illuminate\Support\Facades\Log::warning("User {$user->id} making request during grace period");
            } else {
                // Block request and send alert
                $alertService->send100PercentAlert($user);
                
                return response()->json([
                    'error' => 'Usage limit reached',
                    'message' => 'You have reached your monthly usage limit',
                    'current_usage' => $subscription->current_usage,
                    'plan_limit' => $subscription->plan->limit,
                    'reset_date' => $subscription->usage_reset_date,
                    'upgrade_url' => config('app.frontend_url') . '/subscription/upgrade'
                ], 403);
            }
        }

        // Track usage for this request
        $usageService->trackUsage($user);

        // Check and send alerts if needed
        $alertService->checkAndSendAlerts($user);

        return $next($request);
    }
}