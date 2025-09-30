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
        // Run in correct order
        $this->call([
            ToolSeeder::class,
            PlanSeeder::class,
            SubscriptionSeeder::class,
            HistorySeeder::class,
        ]);
            $this->call(AdminSeeder::class);

    }
}