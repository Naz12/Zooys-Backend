<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Clear existing plans
        DB::table('plans')->truncate();
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // Insert new 3-tier plan structure (Testing plan removed)
        DB::table('plans')->insert([
            [
                'name' => 'Free',
                'price' => 0,
                'currency' => 'USD',
                'limit' => 20,
                'is_active' => true,
                'created_at' => now()->subDays(30),
                'updated_at' => now()->subDays(30),
            ],
            [
                'name' => 'Pro',
                'price' => 9.99,
                'currency' => 'USD',
                'limit' => 500,
                'is_active' => true,
                'created_at' => now()->subDays(25),
                'updated_at' => now()->subDays(25),
            ],
            [
                'name' => 'Unlimited',
                'price' => 29.99,
                'currency' => 'USD',
                'limit' => 10000, // Soft limit for abuse prevention
                'is_active' => true,
                'created_at' => now()->subDays(20),
                'updated_at' => now()->subDays(20),
            ],
        ]);
    }
}