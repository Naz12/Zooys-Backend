<?php

namespace App\Services;

use App\Models\User;
use App\Models\Subscription;
use App\Mail\UsageWarningMail;
use App\Mail\UsageLimitReachedMail;
use App\Mail\UpgradePromptMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class UsageAlertService
{
    /**
     * Check and send usage alerts for a user
     */
    public function checkAndSendAlerts(User $user): void
    {
        $subscription = $user->getActiveSubscription();
        
        if (!$subscription) {
            return;
        }

        $currentUsage = $subscription->current_usage;
        $limit = $subscription->plan->limit;

        if (!$limit) {
            return; // Unlimited plan
        }

        $usagePercentage = ($currentUsage / $limit) * 100;

        // Check 80% threshold
        if ($usagePercentage >= 80 && $usagePercentage < 100) {
            $this->send80PercentAlert($user);
        }

        // Check 100% threshold
        if ($usagePercentage >= 100) {
            $this->send100PercentAlert($user);
        }

        // Check for upgrade prompt (if user frequently hits limits)
        if ($this->shouldSuggestUpgrade($user)) {
            $this->sendUpgradePrompt($user);
        }
    }

    /**
     * Send 80% usage warning
     */
    public function send80PercentAlert(User $user): void
    {
        $subscription = $user->getActiveSubscription();
        
        if (!$subscription || !$this->shouldSendAlert($subscription, 80)) {
            return;
        }

        try {
            Mail::to($user->email)->send(new UsageWarningMail($user, $subscription));
            
            $subscription->update(['last_alert_sent_at' => now()]);
            
            Log::info("80% usage alert sent to user {$user->id}", [
                'current_usage' => $subscription->current_usage,
                'plan_limit' => $subscription->plan->limit,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send 80% usage alert to user {$user->id}: " . $e->getMessage());
        }
    }

    /**
     * Send 100% usage limit reached notification
     */
    public function send100PercentAlert(User $user): void
    {
        $subscription = $user->getActiveSubscription();
        
        if (!$subscription || !$this->shouldSendAlert($subscription, 100)) {
            return;
        }

        try {
            Mail::to($user->email)->send(new UsageLimitReachedMail($user, $subscription));
            
            $subscription->update(['last_alert_sent_at' => now()]);
            
            Log::info("100% usage alert sent to user {$user->id}", [
                'current_usage' => $subscription->current_usage,
                'plan_limit' => $subscription->plan->limit,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send 100% usage alert to user {$user->id}: " . $e->getMessage());
        }
    }

    /**
     * Send upgrade prompt to user
     */
    public function sendUpgradePrompt(User $user): void
    {
        $subscription = $user->getActiveSubscription();
        
        if (!$subscription) {
            return;
        }

        try {
            Mail::to($user->email)->send(new UpgradePromptMail($user, $subscription));
            
            Log::info("Upgrade prompt sent to user {$user->id}", [
                'current_plan' => $subscription->plan->name,
                'current_usage' => $subscription->current_usage,
                'plan_limit' => $subscription->plan->limit,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send upgrade prompt to user {$user->id}: " . $e->getMessage());
        }
    }

    /**
     * Check if alert should be sent (avoid spam)
     */
    public function shouldSendAlert(Subscription $subscription, int $threshold): bool
    {
        // Don't send if already sent recently (within 24 hours)
        if ($subscription->last_alert_sent_at && 
            $subscription->last_alert_sent_at->diffInHours(now()) < 24) {
            return false;
        }

        // Don't send if subscription is inactive
        if (!$subscription->active) {
            return false;
        }

        // Don't send if in grace period
        if ($subscription->isInGracePeriod()) {
            return false;
        }

        return true;
    }

    /**
     * Check if user should be suggested to upgrade
     */
    private function shouldSuggestUpgrade(User $user): bool
    {
        $subscription = $user->getActiveSubscription();
        
        if (!$subscription) {
            return false;
        }

        // Check if user has hit limit multiple times in recent billing cycles
        $recentLimitHits = $user->histories()
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $limit = $subscription->plan->limit;
        if (!$limit) {
            return false; // Unlimited plan
        }

        // Suggest upgrade if user has used more than 90% of limit multiple times
        $usagePercentage = ($recentLimitHits / $limit) * 100;
        
        return $usagePercentage >= 90 && $recentLimitHits >= 3;
    }

    /**
     * Send bulk alerts for multiple users (admin function)
     */
    public function sendBulkAlerts(array $userIds, string $alertType = 'usage_warning'): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($userIds as $userId) {
            try {
                $user = User::findOrFail($userId);
                
                switch ($alertType) {
                    case 'usage_warning':
                        $this->send80PercentAlert($user);
                        break;
                    case 'limit_reached':
                        $this->send100PercentAlert($user);
                        break;
                    case 'upgrade_prompt':
                        $this->sendUpgradePrompt($user);
                        break;
                }
                
                $results['sent']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "User {$userId}: " . $e->getMessage();
                Log::error("Bulk alert failed for user {$userId}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Get alert statistics for admin dashboard
     */
    public function getAlertStats(): array
    {
        $totalSubscriptions = Subscription::where('active', true)->count();
        $subscriptionsWithAlerts = Subscription::where('active', true)
            ->whereNotNull('last_alert_sent_at')
            ->count();

        $recentAlerts = Subscription::where('active', true)
            ->where('last_alert_sent_at', '>=', now()->subDays(7))
            ->count();

        return [
            'total_subscriptions' => $totalSubscriptions,
            'subscriptions_with_alerts' => $subscriptionsWithAlerts,
            'recent_alerts_7_days' => $recentAlerts,
            'alert_percentage' => $totalSubscriptions > 0 ? 
                round(($subscriptionsWithAlerts / $totalSubscriptions) * 100, 2) : 0,
        ];
    }
}
