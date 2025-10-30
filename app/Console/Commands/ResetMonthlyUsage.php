<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use App\Services\SubscriptionUsageService;
use App\Services\UsageAlertService;

class ResetMonthlyUsage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:reset-usage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset monthly usage for subscriptions that have reached their billing cycle';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting monthly usage reset process...');

        $subscriptions = Subscription::where('active', true)
            ->where('usage_reset_date', '<=', now())
            ->with(['user', 'plan'])
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->info('No subscriptions need usage reset.');
            return;
        }

        $usageService = app(SubscriptionUsageService::class);
        $alertService = app(UsageAlertService::class);
        $resetCount = 0;

        foreach ($subscriptions as $subscription) {
            try {
                // Reset usage
                $usageService->resetMonthlyUsage($subscription);
                
                // Send usage reset notification if needed
                $this->sendUsageResetNotification($subscription);
                
                $resetCount++;
                
                $this->line("Reset usage for user {$subscription->user->email} ({$subscription->plan->name})");
                
            } catch (\Exception $e) {
                $this->error("Failed to reset usage for subscription {$subscription->id}: " . $e->getMessage());
            }
        }

        $this->info("Monthly usage reset completed. {$resetCount} subscriptions processed.");
        
        // Log the reset activity
        \Illuminate\Support\Facades\Log::info("Monthly usage reset completed", [
            'reset_count' => $resetCount,
            'total_subscriptions' => $subscriptions->count(),
        ]);
    }

    /**
     * Send usage reset notification to user
     */
    private function sendUsageResetNotification(Subscription $subscription): void
    {
        try {
            // You can create a UsageResetMail class if needed
            // For now, we'll just log it
            \Illuminate\Support\Facades\Log::info("Usage reset notification sent", [
                'user_id' => $subscription->user_id,
                'plan' => $subscription->plan->name,
                'reset_date' => $subscription->usage_reset_date,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send usage reset notification: " . $e->getMessage());
        }
    }
}