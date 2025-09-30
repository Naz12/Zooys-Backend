<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VisitSeeder extends Seeder
{
    public function run(): void
    {
        $visits = [];
        
        // Generate visits for the last 30 days
        for ($i = 30; $i >= 0; $i--) {
            // Generate random number of visits per day (5-50)
            $dailyVisits = rand(5, 50);
            
            for ($j = 0; $j < $dailyVisits; $j++) {
                $visits[] = [
                    'ip' => $this->generateRandomIP(),
                    'user_agent' => $this->generateRandomUserAgent(),
                    'is_bot' => rand(0, 10) < 2, // 20% chance of being a bot
                    'visited_at' => now()->subDays($i)->addHours(rand(0, 23))->addMinutes(rand(0, 59)),
                ];
            }
        }

        // Insert visits in chunks to avoid memory issues
        $chunks = array_chunk($visits, 100);
        foreach ($chunks as $chunk) {
            DB::table('visits')->insert($chunk);
        }

        $this->command->info("✅ Created " . count($visits) . " visitor records for analytics.");
    }

    private function generateRandomIP(): string
    {
        return rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255);
    }

    private function generateRandomUserAgent(): string
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Edge/91.0.864.59',
        ];
        
        return $userAgents[array_rand($userAgents)];
    }
}
