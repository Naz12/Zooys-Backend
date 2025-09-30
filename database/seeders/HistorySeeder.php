<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Tool;
use App\Models\History;

class HistorySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        $tools = Tool::all()->keyBy('slug');

        if (! $user || $tools->isEmpty()) {
            $this->command->warn('⚠️ Skipped HistorySeeder (no user or tools found).');
            return;
        }

        $dummyHistories = [
            [
                'tool'   => 'youtube',
                'input'  => 'https://youtube.com/watch?v=dQw4w9WgXcQ',
                'output' => 'This is a dummy summary of the YouTube video.',
                'meta'   => ['language' => 'en', 'mode' => 'summary'],
            ],
            [
                'tool'   => 'pdf',
                'input'  => 'uploads/pdf/example.pdf',
                'output' => 'This is a dummy summary of the PDF file.',
                'meta'   => ['pages' => 12],
            ],
            [
                'tool'   => 'writer',
                'input'  => 'Once upon a time...',
                'output' => 'Dummy AI-generated story: Once upon a time...',
                'meta'   => ['writer_tool' => 'story'],
            ],
            [
                'tool'   => 'math',
                'input'  => '2x + 3 = 7',
                'output' => 'x = 2',
                'meta'   => [],
            ],
            [
                'tool'   => 'flashcards',
                'input'  => 'Artificial Intelligence basics',
                'output' => json_encode([
                    ['question' => 'What is AI?', 'answer' => 'Artificial Intelligence'],
                ]),
                'meta'   => [],
            ],
            [
                'tool'   => 'diagram',
                'input'  => 'Simple workflow',
                'output' => "graph TD; A[Start] --> B[Process]; B --> C[End];",
                'meta'   => [],
            ],
        ];

        foreach ($dummyHistories as $item) {
            if (isset($tools[$item['tool']])) {
                History::create([
                    'user_id' => $user->id,
                    'tool_id' => $tools[$item['tool']]->id,
                    'input'   => $item['input'],
                    'output'  => $item['output'],
                    'meta'    => json_encode($item['meta']),
                ]);
            }
        }

        $this->command->info('✅ Dummy histories seeded successfully.');
    }
}