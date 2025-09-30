<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run in correct order (dependencies matter)
        $this->call([
            ToolSeeder::class,        // Tools first (no dependencies)
            PlanSeeder::class,        // Plans second (no dependencies)
            UserSeeder::class,        // Users third (no dependencies)
            SubscriptionSeeder::class, // Subscriptions fourth (needs users and plans)
            VisitSeeder::class,       // Visits fifth (no dependencies)
            HistorySeeder::class,     // History sixth (needs users and tools)
            AdminSeeder::class,       // Admin last (no dependencies)
        ]);
    }
}