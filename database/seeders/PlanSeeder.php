<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
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
                'name' => 'Starter',
                'price' => 4.99,
                'currency' => 'USD',
                'limit' => 100,
                'is_active' => true,
                'created_at' => now()->subDays(25),
                'updated_at' => now()->subDays(25),
            ],
            [
                'name' => 'Pro',
                'price' => 9.99,
                'currency' => 'USD',
                'limit' => 500,
                'is_active' => true,
                'created_at' => now()->subDays(20),
                'updated_at' => now()->subDays(20),
            ],
            [
                'name' => 'Business',
                'price' => 19.99,
                'currency' => 'USD',
                'limit' => 2000,
                'is_active' => true,
                'created_at' => now()->subDays(15),
                'updated_at' => now()->subDays(15),
            ],
            [
                'name' => 'Premium',
                'price' => 29.99,
                'currency' => 'USD',
                'limit' => null, // unlimited
                'is_active' => true,
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(10),
            ],
            [
                'name' => 'Enterprise',
                'price' => 99.99,
                'currency' => 'USD',
                'limit' => null, // unlimited
                'is_active' => true,
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],
            [
                'name' => 'Legacy Plan',
                'price' => 5.99,
                'currency' => 'USD',
                'limit' => 50,
                'is_active' => false, // Inactive plan
                'created_at' => now()->subDays(60),
                'updated_at' => now()->subDays(30),
            ],
        ]);
    }
}