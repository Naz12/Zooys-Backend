<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now()->subDays(30),
                'created_at' => now()->subDays(30),
                'updated_at' => now()->subDays(30),
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane.smith@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now()->subDays(25),
                'created_at' => now()->subDays(25),
                'updated_at' => now()->subDays(25),
            ],
            [
                'name' => 'Mike Johnson',
                'email' => 'mike.johnson@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now()->subDays(20),
                'created_at' => now()->subDays(20),
                'updated_at' => now()->subDays(20),
            ],
            [
                'name' => 'Sarah Wilson',
                'email' => 'sarah.wilson@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now()->subDays(15),
                'created_at' => now()->subDays(15),
                'updated_at' => now()->subDays(15),
            ],
            [
                'name' => 'David Brown',
                'email' => 'david.brown@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now()->subDays(12),
                'created_at' => now()->subDays(12),
                'updated_at' => now()->subDays(12),
            ],
            [
                'name' => 'Emily Davis',
                'email' => 'emily.davis@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now()->subDays(10),
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(10),
            ],
            [
                'name' => 'Chris Miller',
                'email' => 'chris.miller@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now()->subDays(8),
                'created_at' => now()->subDays(8),
                'updated_at' => now()->subDays(8),
            ],
            [
                'name' => 'Lisa Garcia',
                'email' => 'lisa.garcia@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now()->subDays(6),
                'created_at' => now()->subDays(6),
                'updated_at' => now()->subDays(6),
            ],
            [
                'name' => 'Tom Anderson',
                'email' => 'tom.anderson@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now()->subDays(4),
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDays(4),
            ],
            [
                'name' => 'Anna Taylor',
                'email' => 'anna.taylor@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now()->subDays(2),
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
            [
                'name' => 'Robert Martinez',
                'email' => 'robert.martinez@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now()->subDays(1),
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
            [
                'name' => 'Jennifer Lee',
                'email' => 'jennifer.lee@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => null, // Unverified user
                'created_at' => now()->subHours(12),
                'updated_at' => now()->subHours(12),
            ],
            [
                'name' => 'Kevin White',
                'email' => 'kevin.white@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now()->subHours(6),
                'created_at' => now()->subHours(6),
                'updated_at' => now()->subHours(6),
            ],
            [
                'name' => 'Amanda Clark',
                'email' => 'amanda.clark@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now()->subHours(3),
                'created_at' => now()->subHours(3),
                'updated_at' => now()->subHours(3),
            ],
            [
                'name' => 'Daniel Rodriguez',
                'email' => 'daniel.rodriguez@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now()->subHours(1),
                'created_at' => now()->subHours(1),
                'updated_at' => now()->subHours(1),
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }
    }
}
