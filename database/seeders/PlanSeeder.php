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
                'name'       => 'Free',
                'price'      => 0,
                'currency'   => 'USD',
                'limit'      => 20, // e.g. 20 summaries per month
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Pro',
                'price'      => 9.99,
                'currency'   => 'USD',
                'limit'      => 200,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Premium',
                'price'      => 29.99,
                'currency'   => 'USD',
                'limit'      => null, // unlimited
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}