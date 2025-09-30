<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        // Find the first user (admin you created with make:filament-user)
        $user = User::first();
        $plan = Plan::where('name', 'Premium')->first();

        if (! $user || ! $plan) {
            $this->command->warn('⚠️ Skipped SubscriptionSeeder (no user or plan found).');
            return;
        }

        Subscription::updateOrCreate(
            [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ],
            [
                'starts_at' => now(),
                'ends_at'   => now()->addMonth(),
                'active'    => true,
            ]
        );

        $this->command->info("✅ User {$user->email} subscribed to {$plan->name} plan.");
    }
}