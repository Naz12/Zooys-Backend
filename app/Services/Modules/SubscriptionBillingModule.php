<?php

namespace App\Services\Modules;

use App\Services\SubscriptionUsageService;
use App\Services\SubscriptionManagementService;
use App\Services\UsageAlertService;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\PaymentHistory;
use Illuminate\Support\Facades\Log;

class SubscriptionBillingModule
{
    private $usageService;
    private $managementService;
    private $alertService;

    public function __construct(
        SubscriptionUsageService $usageService,
        SubscriptionManagementService $managementService,
        UsageAlertService $alertService
    ) {
        $this->usageService = $usageService;
        $this->managementService = $managementService;
        $this->alertService = $alertService;
    }

    /**
     * Get module information
     */
    public function getModuleInfo(): array
    {
        return [
            'name' => 'Subscription Billing Module',
            'version' => '1.0.0',
            'description' => 'Comprehensive subscription and billing management with Stripe integration',
            'features' => [
                'Usage tracking and limits',
                'Monthly billing cycles',
                'Grace periods for failed payments',
                'Automated alerts and notifications',
                'Plan upgrades and downgrades',
                'Payment history tracking',
                'Revenue analytics',
            ],
        ];
    }

    /**
     * Get subscription statistics
     */
    public function getSubscriptionStats(): array
    {
        $totalSubscriptions = Subscription::count();
        $activeSubscriptions = Subscription::where('active', true)->count();
        $inactiveSubscriptions = $totalSubscriptions - $activeSubscriptions;
        
        $subscriptionsInGracePeriod = Subscription::where('active', true)
            ->where('grace_period_ends_at', '>', now())
            ->count();

        $totalRevenue = PaymentHistory::where('status', 'completed')
            ->sum('amount');

        $monthlyRevenue = PaymentHistory::where('status', 'completed')
            ->where('created_at', '>=', now()->subMonth())
            ->sum('amount');

        return [
            'total_subscriptions' => $totalSubscriptions,
            'active_subscriptions' => $activeSubscriptions,
            'inactive_subscriptions' => $inactiveSubscriptions,
            'subscriptions_in_grace_period' => $subscriptionsInGracePeriod,
            'total_revenue' => $totalRevenue,
            'monthly_revenue' => $monthlyRevenue,
            'conversion_rate' => $this->calculateConversionRate(),
            'churn_rate' => $this->calculateChurnRate(),
        ];
    }

    /**
     * Get plan performance metrics
     */
    public function getPlanPerformance(): array
    {
        $plans = Plan::withCount('subscriptions')->get();
        
        $performance = [];
        foreach ($plans as $plan) {
            $activeSubscriptions = $plan->subscriptions()->where('active', true)->count();
            $revenue = $plan->subscriptions()
                ->where('active', true)
                ->join('payment_history', 'subscriptions.id', '=', 'payment_history.subscription_id')
                ->where('payment_history.status', 'completed')
                ->sum('payment_history.amount');

            $performance[] = [
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'price' => $plan->price,
                'total_subscriptions' => $plan->subscriptions_count,
                'active_subscriptions' => $activeSubscriptions,
                'revenue' => $revenue,
                'is_active' => $plan->is_active,
            ];
        }

        return $performance;
    }

    /**
     * Get usage analytics
     */
    public function getUsageAnalytics(): array
    {
        $subscriptions = Subscription::where('active', true)
            ->with('plan')
            ->get();

        $totalUsage = $subscriptions->sum('current_usage');
        $averageUsage = $subscriptions->avg('current_usage');
        
        $nearLimitSubscriptions = $subscriptions->filter(function ($subscription) {
            if (!$subscription->plan->limit) return false;
            $usagePercentage = ($subscription->current_usage / $subscription->plan->limit) * 100;
            return $usagePercentage >= 80;
        })->count();

        $overLimitSubscriptions = $subscriptions->filter(function ($subscription) {
            if (!$subscription->plan->limit) return false;
            return $subscription->current_usage >= $subscription->plan->limit;
        })->count();

        return [
            'total_usage' => $totalUsage,
            'average_usage' => round($averageUsage, 2),
            'near_limit_subscriptions' => $nearLimitSubscriptions,
            'over_limit_subscriptions' => $overLimitSubscriptions,
            'usage_distribution' => $this->getUsageDistribution($subscriptions),
        ];
    }

    /**
     * Process bulk operations
     */
    public function processBulkOperation(string $operation, array $subscriptionIds, array $params = []): array
    {
        $results = [
            'processed' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($subscriptionIds as $subscriptionId) {
            try {
                $subscription = Subscription::findOrFail($subscriptionId);
                
                switch ($operation) {
                    case 'activate':
                        $subscription->update(['active' => true]);
                        break;
                    case 'deactivate':
                        $subscription->update(['active' => false]);
                        break;
                    case 'apply_grace_period':
                        $this->managementService->applyGracePeriod($subscription);
                        break;
                    case 'remove_grace_period':
                        $this->managementService->removeGracePeriod($subscription);
                        break;
                    case 'reset_usage':
                        $this->usageService->resetMonthlyUsage($subscription);
                        break;
                    case 'send_alert':
                        $alertType = $params['alert_type'] ?? 'usage_warning';
                        $this->alertService->sendBulkAlerts([$subscription->user_id], $alertType);
                        break;
                }
                
                $results['processed']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Subscription {$subscriptionId}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Calculate conversion rate
     */
    private function calculateConversionRate(): float
    {
        $totalUsers = \App\Models\User::count();
        $usersWithSubscriptions = \App\Models\User::whereHas('subscription')->count();
        
        return $totalUsers > 0 ? round(($usersWithSubscriptions / $totalUsers) * 100, 2) : 0;
    }

    /**
     * Calculate churn rate
     */
    private function calculateChurnRate(): float
    {
        $totalSubscriptions = Subscription::count();
        $cancelledSubscriptions = Subscription::where('active', false)->count();
        
        return $totalSubscriptions > 0 ? round(($cancelledSubscriptions / $totalSubscriptions) * 100, 2) : 0;
    }

    /**
     * Get usage distribution
     */
    private function getUsageDistribution($subscriptions): array
    {
        $distribution = [
            '0-20%' => 0,
            '21-40%' => 0,
            '41-60%' => 0,
            '61-80%' => 0,
            '81-100%' => 0,
            'over_limit' => 0,
        ];

        foreach ($subscriptions as $subscription) {
            if (!$subscription->plan->limit) {
                continue; // Skip unlimited plans
            }

            $usagePercentage = ($subscription->current_usage / $subscription->plan->limit) * 100;

            if ($usagePercentage <= 20) {
                $distribution['0-20%']++;
            } elseif ($usagePercentage <= 40) {
                $distribution['21-40%']++;
            } elseif ($usagePercentage <= 60) {
                $distribution['41-60%']++;
            } elseif ($usagePercentage <= 80) {
                $distribution['61-80%']++;
            } elseif ($usagePercentage <= 100) {
                $distribution['81-100%']++;
            } else {
                $distribution['over_limit']++;
            }
        }

        return $distribution;
    }

    /**
     * Health check for the module
     */
    public function healthCheck(): array
    {
        $issues = [];
        
        // Check if Stripe is configured
        if (!config('services.stripe.secret')) {
            $issues[] = 'Stripe secret key not configured';
        }

        // Check if webhook secret is configured
        if (!config('services.stripe.webhook_secret')) {
            $issues[] = 'Stripe webhook secret not configured';
        }

        // Check for subscriptions with invalid billing cycles
        $invalidSubscriptions = Subscription::where('active', true)
            ->whereNull('billing_cycle_start')
            ->count();
            
        if ($invalidSubscriptions > 0) {
            $issues[] = "{$invalidSubscriptions} active subscriptions missing billing cycle start date";
        }

        return [
            'status' => empty($issues) ? 'healthy' : 'issues_detected',
            'issues' => $issues,
            'timestamp' => now()->toISOString(),
        ];
    }
}
