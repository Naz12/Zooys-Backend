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
        $users = User::all();
        $plans = Plan::all();

        if ($users->isEmpty() || $plans->isEmpty()) {
            $this->command->warn('⚠️ Skipped SubscriptionSeeder (no users or plans found).');
            return;
        }

        $subscriptions = [
            // Active subscriptions
            [
                'user_id' => $users[0]->id,
                'plan_id' => $plans->where('name', 'Pro')->first()->id,
                'starts_at' => now()->subDays(20),
                'ends_at' => now()->addDays(10),
                'active' => true,
                'created_at' => now()->subDays(20),
                'updated_at' => now()->subDays(20),
            ],
            [
                'user_id' => $users[1]->id,
                'plan_id' => $plans->where('name', 'Premium')->first()->id,
                'starts_at' => now()->subDays(15),
                'ends_at' => now()->addDays(15),
                'active' => true,
                'created_at' => now()->subDays(15),
                'updated_at' => now()->subDays(15),
            ],
            [
                'user_id' => $users[2]->id,
                'plan_id' => $plans->where('name', 'Business')->first()->id,
                'starts_at' => now()->subDays(10),
                'ends_at' => now()->addDays(20),
                'active' => true,
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(10),
            ],
            [
                'user_id' => $users[3]->id,
                'plan_id' => $plans->where('name', 'Starter')->first()->id,
                'starts_at' => now()->subDays(5),
                'ends_at' => now()->addDays(25),
                'active' => true,
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],
            [
                'user_id' => $users[4]->id,
                'plan_id' => $plans->where('name', 'Enterprise')->first()->id,
                'starts_at' => now()->subDays(3),
                'ends_at' => now()->addDays(27),
                'active' => true,
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ],
            // Expired subscriptions
            [
                'user_id' => $users[5]->id,
                'plan_id' => $plans->where('name', 'Pro')->first()->id,
                'starts_at' => now()->subDays(40),
                'ends_at' => now()->subDays(10),
                'active' => false,
                'created_at' => now()->subDays(40),
                'updated_at' => now()->subDays(10),
            ],
            [
                'user_id' => $users[6]->id,
                'plan_id' => $plans->where('name', 'Business')->first()->id,
                'starts_at' => now()->subDays(35),
                'ends_at' => now()->subDays(5),
                'active' => false,
                'created_at' => now()->subDays(35),
                'updated_at' => now()->subDays(5),
            ],
            // Cancelled subscriptions
            [
                'user_id' => $users[7]->id,
                'plan_id' => $plans->where('name', 'Premium')->first()->id,
                'starts_at' => now()->subDays(25),
                'ends_at' => now()->subDays(5),
                'active' => false,
                'created_at' => now()->subDays(25),
                'updated_at' => now()->subDays(5),
            ],
            // Free plan users (no subscription)
            // users[8] - Free plan user
            // users[9] - Free plan user
            // Recent subscriptions
            [
                'user_id' => $users[10]->id,
                'plan_id' => $plans->where('name', 'Starter')->first()->id,
                'starts_at' => now()->subDays(1),
                'ends_at' => now()->addDays(29),
                'active' => true,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
            [
                'user_id' => $users[11]->id,
                'plan_id' => $plans->where('name', 'Pro')->first()->id,
                'starts_at' => now()->subHours(12),
                'ends_at' => now()->addDays(29)->addHours(12),
                'active' => true,
                'created_at' => now()->subHours(12),
                'updated_at' => now()->subHours(12),
            ],
        ];

        foreach ($subscriptions as $subscriptionData) {
            Subscription::create($subscriptionData);
        }

        $this->command->info("✅ Created " . count($subscriptions) . " subscriptions with various states.");
    }
}