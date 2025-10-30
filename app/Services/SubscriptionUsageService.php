<?php

namespace App\Services;

use App\Models\User;
use App\Models\Subscription;
use App\Models\History;
use Illuminate\Support\Facades\Log;

class SubscriptionUsageService
{
    /**
     * Track usage for a user
     */
    public function trackUsage(User $user, string $toolType = null): void
    {
        $subscription = $user->getActiveSubscription();
        
        if (!$subscription) {
            Log::warning("Attempted to track usage for user {$user->id} without active subscription");
            return;
        }

        // Check if usage should be reset
        if ($subscription->shouldResetUsage()) {
            $this->resetMonthlyUsage($subscription);
        }

        // Increment usage counter
        $subscription->increment('current_usage');

        Log::info("Usage tracked for user {$user->id}", [
            'tool_type' => $toolType,
            'current_usage' => $subscription->fresh()->current_usage,
            'plan_limit' => $subscription->plan->limit
        ]);
    }

    /**
     * Check if user can make requests
     */
    public function checkLimit(User $user): bool
    {
        $subscription = $user->getActiveSubscription();
        
        if (!$subscription) {
            return false; // No subscription
        }

        // Check if in grace period
        if ($subscription->isInGracePeriod()) {
            return true;
        }

        // Check usage limit
        $limit = $subscription->plan->limit;
        if (!$limit) {
            return true; // Unlimited
        }

        return $subscription->current_usage < $limit;
    }

    /**
     * Get remaining usage for user
     */
    public function getRemainingUsage(User $user): int
    {
        $subscription = $user->getActiveSubscription();
        
        if (!$subscription) {
            return 0;
        }

        return $subscription->getRemainingUsage();
    }

    /**
     * Get usage breakdown by tool type
     */
    public function getUsageByTool(User $user): array
    {
        $histories = $user->histories()
            ->join('tools', 'histories.tool_id', '=', 'tools.id')
            ->selectRaw('tools.name, COUNT(*) as count')
            ->groupBy('tools.id', 'tools.name')
            ->get()
            ->pluck('count', 'name')
            ->toArray();

        return $histories;
    }

    /**
     * Reset monthly usage for a subscription
     */
    public function resetMonthlyUsage(Subscription $subscription): void
    {
        $subscription->update([
            'current_usage' => 0,
            'usage_reset_date' => now()->addMonth(),
            'last_alert_sent_at' => null, // Reset alert flag
        ]);

        Log::info("Usage reset for subscription {$subscription->id}", [
            'user_id' => $subscription->user_id,
            'plan' => $subscription->plan->name,
            'next_reset' => $subscription->usage_reset_date
        ]);
    }

    /**
     * Check if usage should be reset
     */
    public function shouldResetUsage(Subscription $subscription): bool
    {
        return $subscription->shouldResetUsage();
    }

    /**
     * Get complete usage statistics for user
     */
    public function getUsageStats(User $user): array
    {
        $subscription = $user->getActiveSubscription();
        
        if (!$subscription) {
            return [
                'status' => 'no_subscription',
                'message' => 'No active subscription found',
                'current_usage' => 0,
                'plan_limit' => null,
                'usage_percentage' => 0,
                'remaining_usage' => 0,
                'reset_date' => null,
                'by_tool' => [],
            ];
        }

        $currentUsage = $subscription->current_usage;
        $planLimit = $subscription->plan->limit;
        $usagePercentage = $planLimit ? ($currentUsage / $planLimit) * 100 : 0;
        $remainingUsage = $this->getRemainingUsage($user);
        $byTool = $this->getUsageByTool($user);

        return [
            'status' => 'active',
            'plan' => $subscription->plan->name,
            'price' => $subscription->plan->price,
            'currency' => $subscription->plan->currency,
            'plan_limit' => $planLimit,
            'current_usage' => $currentUsage,
            'remaining_usage' => $remainingUsage,
            'usage_percentage' => round($usagePercentage, 2),
            'starts_at' => $subscription->starts_at,
            'ends_at' => $subscription->ends_at,
            'usage_reset_date' => $subscription->usage_reset_date,
            'billing_cycle_start' => $subscription->billing_cycle_start,
            'in_grace_period' => $subscription->isInGracePeriod(),
            'grace_period_ends_at' => $subscription->grace_period_ends_at,
            'by_tool' => $byTool,
        ];
    }

    /**
     * Get usage statistics for multiple users (admin)
     */
    public function getBulkUsageStats(array $userIds): array
    {
        $subscriptions = Subscription::with(['user', 'plan'])
            ->whereIn('user_id', $userIds)
            ->where('active', true)
            ->get();

        $stats = [];
        foreach ($subscriptions as $subscription) {
            $stats[$subscription->user_id] = [
                'user_name' => $subscription->user->name,
                'user_email' => $subscription->user->email,
                'plan_name' => $subscription->plan->name,
                'current_usage' => $subscription->current_usage,
                'plan_limit' => $subscription->plan->limit,
                'usage_percentage' => $subscription->plan->limit ? 
                    round(($subscription->current_usage / $subscription->plan->limit) * 100, 2) : 0,
                'remaining_usage' => $subscription->getRemainingUsage(),
                'reset_date' => $subscription->usage_reset_date,
            ];
        }

        return $stats;
    }
}
