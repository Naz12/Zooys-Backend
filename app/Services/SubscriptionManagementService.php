<?php

namespace App\Services;

use App\Models\User;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\PaymentHistory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SubscriptionManagementService
{
    /**
     * Upgrade user to a new plan
     */
    public function upgradePlan(User $user, int $newPlanId, bool $viaStripe = true): array
    {
        $subscription = $user->getActiveSubscription();
        $newPlan = Plan::findOrFail($newPlanId);

        if (!$subscription) {
            throw new \Exception('User has no active subscription to upgrade');
        }

        if ($subscription->plan_id === $newPlanId) {
            throw new \Exception('User is already on this plan');
        }

        DB::beginTransaction();
        try {
            $oldPlan = $subscription->plan;
            $daysRemaining = $this->calculateDaysRemaining($subscription);
            
            // Calculate proration if via Stripe
            $prorationAmount = 0;
            if ($viaStripe && $daysRemaining > 0) {
                $prorationAmount = $this->calculateProration($oldPlan, $newPlan, $daysRemaining);
            }

            // Update subscription
            $subscription->update([
                'plan_id' => $newPlanId,
                'billing_cycle_start' => now(),
                'usage_reset_date' => now()->addMonth(),
                'current_usage' => 0, // Reset usage on upgrade
            ]);

            // Record payment history
            if ($prorationAmount > 0) {
                PaymentHistory::create([
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'amount' => $prorationAmount,
                    'currency' => 'USD',
                    'status' => 'completed',
                    'payment_type' => 'upgrade',
                    'metadata' => [
                        'old_plan' => $oldPlan->name,
                        'new_plan' => $newPlan->name,
                        'days_remaining' => $daysRemaining,
                        'proration_amount' => $prorationAmount,
                    ],
                ]);
            }

            DB::commit();

            Log::info("Plan upgraded for user {$user->id}", [
                'old_plan' => $oldPlan->name,
                'new_plan' => $newPlan->name,
                'proration_amount' => $prorationAmount,
            ]);

            return [
                'success' => true,
                'message' => 'Plan upgraded successfully',
                'subscription' => $subscription->load('plan'),
                'proration_amount' => $prorationAmount,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Plan upgrade failed for user {$user->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Downgrade user to a new plan
     */
    public function downgradePlan(User $user, int $newPlanId, bool $viaStripe = true): array
    {
        $subscription = $user->getActiveSubscription();
        $newPlan = Plan::findOrFail($newPlanId);

        if (!$subscription) {
            throw new \Exception('User has no active subscription to downgrade');
        }

        if ($subscription->plan_id === $newPlanId) {
            throw new \Exception('User is already on this plan');
        }

        DB::beginTransaction();
        try {
            $oldPlan = $subscription->plan;
            $daysRemaining = $this->calculateDaysRemaining($subscription);
            
            // Calculate proration refund
            $prorationRefund = 0;
            if ($viaStripe && $daysRemaining > 0) {
                $prorationRefund = $this->calculateProration($oldPlan, $newPlan, $daysRemaining);
            }

            // Update subscription
            $subscription->update([
                'plan_id' => $newPlanId,
                'billing_cycle_start' => now(),
                'usage_reset_date' => now()->addMonth(),
                'current_usage' => 0, // Reset usage on downgrade
            ]);

            // Record payment history for refund
            if ($prorationRefund > 0) {
                PaymentHistory::create([
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'amount' => $prorationRefund,
                    'currency' => 'USD',
                    'status' => 'refunded',
                    'payment_type' => 'downgrade',
                    'metadata' => [
                        'old_plan' => $oldPlan->name,
                        'new_plan' => $newPlan->name,
                        'days_remaining' => $daysRemaining,
                        'proration_refund' => $prorationRefund,
                    ],
                ]);
            }

            DB::commit();

            Log::info("Plan downgraded for user {$user->id}", [
                'old_plan' => $oldPlan->name,
                'new_plan' => $newPlan->name,
                'proration_refund' => $prorationRefund,
            ]);

            return [
                'success' => true,
                'message' => 'Plan downgraded successfully',
                'subscription' => $subscription->load('plan'),
                'proration_refund' => $prorationRefund,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Plan downgrade failed for user {$user->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cancel user subscription
     */
    public function cancelSubscription(User $user, bool $immediately = false): array
    {
        $subscription = $user->getActiveSubscription();

        if (!$subscription) {
            throw new \Exception('User has no active subscription to cancel');
        }

        DB::beginTransaction();
        try {
            if ($immediately) {
                // Cancel immediately
                $subscription->update([
                    'active' => false,
                    'ends_at' => now(),
                ]);
            } else {
                // Cancel at end of billing period
                $subscription->update([
                    'active' => false,
                    'ends_at' => $subscription->usage_reset_date,
                ]);
            }

            // Record cancellation in payment history
            PaymentHistory::create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'amount' => 0,
                'currency' => 'USD',
                'status' => 'completed',
                'payment_type' => 'cancellation',
                'metadata' => [
                    'cancelled_immediately' => $immediately,
                    'plan_name' => $subscription->plan->name,
                    'cancelled_at' => now()->toISOString(),
                ],
            ]);

            DB::commit();

            Log::info("Subscription cancelled for user {$user->id}", [
                'immediately' => $immediately,
                'ends_at' => $subscription->ends_at,
            ]);

            return [
                'success' => true,
                'message' => $immediately ? 'Subscription cancelled immediately' : 'Subscription will be cancelled at end of billing period',
                'subscription' => $subscription->load('plan'),
                'ends_at' => $subscription->ends_at,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Subscription cancellation failed for user {$user->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate proration amount
     */
    public function calculateProration(Plan $currentPlan, Plan $newPlan, int $daysRemaining): float
    {
        $monthlyDays = 30; // Assume 30-day billing cycle
        $dailyRate = $daysRemaining / $monthlyDays;
        
        $currentPlanDailyValue = ($currentPlan->price * $dailyRate);
        $newPlanDailyValue = ($newPlan->price * $dailyRate);
        
        return $newPlanDailyValue - $currentPlanDailyValue;
    }

    /**
     * Apply grace period after payment failure
     */
    public function applyGracePeriod(Subscription $subscription): void
    {
        $gracePeriodDays = config('services.subscription.grace_period_days', 3);
        
        $subscription->update([
            'grace_period_ends_at' => now()->addDays($gracePeriodDays),
        ]);

        Log::info("Grace period applied to subscription {$subscription->id}", [
            'user_id' => $subscription->user_id,
            'grace_period_ends_at' => $subscription->grace_period_ends_at,
        ]);
    }

    /**
     * Remove grace period after successful payment
     */
    public function removeGracePeriod(Subscription $subscription): void
    {
        $subscription->update([
            'grace_period_ends_at' => null,
        ]);

        Log::info("Grace period removed from subscription {$subscription->id}", [
            'user_id' => $subscription->user_id,
        ]);
    }

    /**
     * Calculate days remaining in current billing cycle
     */
    private function calculateDaysRemaining(Subscription $subscription): int
    {
        if (!$subscription->usage_reset_date) {
            return 0;
        }

        $now = now();
        $resetDate = $subscription->usage_reset_date;
        
        if ($resetDate->isPast()) {
            return 0;
        }

        return $now->diffInDays($resetDate);
    }

    /**
     * Create new subscription for user
     */
    public function createSubscription(User $user, int $planId, array $stripeData = []): Subscription
    {
        $plan = Plan::findOrFail($planId);

        DB::beginTransaction();
        try {
            // Deactivate any existing subscription
            $user->subscription()->update(['active' => false]);

            // Create new subscription
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $planId,
                'stripe_id' => $stripeData['stripe_id'] ?? null,
                'stripe_customer_id' => $stripeData['stripe_customer_id'] ?? null,
                'active' => true,
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
                'billing_cycle_start' => now(),
                'usage_reset_date' => now()->addMonth(),
                'current_usage' => 0,
            ]);

            // Record initial payment
            if ($plan->price > 0) {
                PaymentHistory::create([
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'amount' => $plan->price,
                    'currency' => $plan->currency,
                    'status' => 'completed',
                    'payment_type' => 'initial',
                    'stripe_payment_id' => $stripeData['stripe_payment_id'] ?? null,
                    'metadata' => [
                        'plan_name' => $plan->name,
                        'created_at' => now()->toISOString(),
                    ],
                ]);
            }

            DB::commit();

            Log::info("New subscription created for user {$user->id}", [
                'plan' => $plan->name,
                'subscription_id' => $subscription->id,
            ]);

            return $subscription;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Subscription creation failed for user {$user->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Upgrade a user's subscription (alias for upgradePlan).
     */
    public function upgradeSubscription(Subscription $subscription, int $newPlanId): bool
    {
        $newPlan = Plan::find($newPlanId);
        if (!$newPlan || !$newPlan->is_active) {
            Log::warning("Attempted to upgrade subscription {$subscription->id} to invalid plan {$newPlanId}");
            return false;
        }

        if ($subscription->plan_id === $newPlanId) {
            Log::info("Subscription {$subscription->id} already on plan {$newPlanId}. No upgrade needed.");
            return true;
        }

        // Implement proration logic here if needed
        // For now, simply update the plan and reset usage
        $subscription->update([
            'plan_id' => $newPlanId,
            'current_usage' => 0,
            'usage_reset_date' => now()->addMonth(),
            'billing_cycle_start' => now(),
        ]);

        PaymentHistory::create([
            'user_id' => $subscription->user_id,
            'subscription_id' => $subscription->id,
            'amount' => $newPlan->price - $subscription->plan->price, // Difference
            'currency' => $newPlan->currency,
            'status' => 'completed',
            'payment_type' => 'upgrade',
            'metadata' => [
                'old_plan' => $subscription->plan->name,
                'new_plan' => $newPlan->name,
            ],
        ]);

        Log::info("Subscription {$subscription->id} upgraded from {$subscription->plan->name} to {$newPlan->name}");
        return true;
    }

    /**
     * Downgrade a user's subscription (alias for downgradePlan).
     */
    public function downgradeSubscription(Subscription $subscription, int $newPlanId): bool
    {
        $newPlan = Plan::find($newPlanId);
        if (!$newPlan || !$newPlan->is_active) {
            Log::warning("Attempted to downgrade subscription {$subscription->id} to invalid plan {$newPlanId}");
            return false;
        }

        if ($subscription->plan_id === $newPlanId) {
            Log::info("Subscription {$subscription->id} already on plan {$newPlanId}. No downgrade needed.");
            return true;
        }

        // Implement proration/refund logic here if needed
        // For now, simply update the plan and reset usage
        $subscription->update([
            'plan_id' => $newPlanId,
            'current_usage' => 0,
            'usage_reset_date' => now()->addMonth(),
            'billing_cycle_start' => now(),
        ]);

        PaymentHistory::create([
            'user_id' => $subscription->user_id,
            'subscription_id' => $subscription->id,
            'amount' => $subscription->plan->price - $newPlan->price, // Difference
            'currency' => $newPlan->currency,
            'status' => 'completed',
            'payment_type' => 'downgrade',
            'metadata' => [
                'old_plan' => $subscription->plan->name,
                'new_plan' => $newPlan->name,
            ],
        ]);

        Log::info("Subscription {$subscription->id} downgraded from {$subscription->plan->name} to {$newPlan->name}");
        return true;
    }
}
