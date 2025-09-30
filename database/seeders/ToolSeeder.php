<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ToolSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tools')->insert([
            [
                'name' => 'YouTube Summarizer',
                'slug' => 'youtube',
                'enabled' => true,
                'created_at' => now()->subDays(30),
                'updated_at' => now()->subDays(30),
            ],
            [
                'name' => 'PDF Summarizer',
                'slug' => 'pdf',
                'enabled' => true,
                'created_at' => now()->subDays(25),
                'updated_at' => now()->subDays(25),
            ],
            [
                'name' => 'AI Writer',
                'slug' => 'writer',
                'enabled' => true,
                'created_at' => now()->subDays(20),
                'updated_at' => now()->subDays(20),
            ],
            [
                'name' => 'Math Solver',
                'slug' => 'math',
                'enabled' => true,
                'created_at' => now()->subDays(15),
                'updated_at' => now()->subDays(15),
            ],
            [
                'name' => 'Flashcards Generator',
                'slug' => 'flashcards',
                'enabled' => true,
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(10),
            ],
            [
                'name' => 'Diagram Generator',
                'slug' => 'diagram',
                'enabled' => true,
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],
            [
                'name' => 'Code Generator',
                'slug' => 'code',
                'enabled' => true,
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ],
            [
                'name' => 'Image Generator',
                'slug' => 'image',
                'enabled' => false, // Disabled tool
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(1),
            ],
            [
                'name' => 'Text Translator',
                'slug' => 'translator',
                'enabled' => true,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
            [
                'name' => 'Voice Transcriber',
                'slug' => 'voice',
                'enabled' => true,
                'created_at' => now()->subHours(12),
                'updated_at' => now()->subHours(12),
            ],
        ]);
    }
}