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
        $users = User::all();
        $tools = Tool::all()->keyBy('slug');

        if ($users->isEmpty() || $tools->isEmpty()) {
            $this->command->warn('⚠️ Skipped HistorySeeder (no users or tools found).');
            return;
        }

        $histories = [];
        
        // Generate history for each user
        foreach ($users as $user) {
            // Generate 5-20 history entries per user
            $userHistoryCount = rand(5, 20);
            
            for ($i = 0; $i < $userHistoryCount; $i++) {
                $toolSlug = array_rand($tools->toArray());
                $tool = $tools[$toolSlug];
                
                $historyData = $this->generateHistoryData($toolSlug);
                
                $histories[] = [
                    'user_id' => $user->id,
                    'tool_id' => $tool->id,
                    'input' => $historyData['input'],
                    'output' => $historyData['output'],
                    'meta' => json_encode($historyData['meta']),
                    'created_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59)),
                    'updated_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59)),
                ];
            }
        }

        // Insert histories in chunks
        $chunks = array_chunk($histories, 100);
        foreach ($chunks as $chunk) {
            History::insert($chunk);
        }

        $this->command->info("✅ Created " . count($histories) . " history records for tool usage analytics.");
    }

    private function generateHistoryData(string $toolSlug): array
    {
        $data = [
            'youtube' => [
                'input' => 'https://youtube.com/watch?v=' . $this->generateRandomString(11),
                'output' => 'This video discusses ' . $this->generateRandomTopic() . '. Key points include: ' . $this->generateRandomSummary(),
                'meta' => ['language' => 'en', 'mode' => 'summary', 'duration' => rand(300, 3600)],
            ],
            'pdf' => [
                'input' => 'uploads/pdf/document_' . rand(1, 100) . '.pdf',
                'output' => 'This document covers ' . $this->generateRandomTopic() . '. Main findings: ' . $this->generateRandomSummary(),
                'meta' => ['pages' => rand(5, 50), 'file_size' => rand(1000000, 10000000)],
            ],
            'writer' => [
                'input' => 'Write a ' . $this->generateRandomContentType(),
                'output' => $this->generateRandomContent(),
                'meta' => ['content_type' => $this->generateRandomContentType(), 'word_count' => rand(100, 2000)],
            ],
            'math' => [
                'input' => $this->generateRandomMathProblem(),
                'output' => $this->generateMathSolution(),
                'meta' => ['problem_type' => $this->generateRandomMathType(), 'difficulty' => rand(1, 5)],
            ],
            'flashcards' => [
                'input' => 'Create flashcards for ' . $this->generateRandomTopic(),
                'output' => json_encode($this->generateFlashcards()),
                'meta' => ['card_count' => rand(5, 20), 'subject' => $this->generateRandomTopic()],
            ],
            'diagram' => [
                'input' => 'Create a ' . $this->generateRandomDiagramType(),
                'output' => $this->generateDiagramCode(),
                'meta' => ['diagram_type' => $this->generateRandomDiagramType(), 'nodes' => rand(3, 15)],
            ],
            'code' => [
                'input' => 'Generate ' . $this->generateRandomLanguage() . ' code for ' . $this->generateRandomTopic(),
                'output' => $this->generateCodeSnippet(),
                'meta' => ['language' => $this->generateRandomLanguage(), 'lines' => rand(10, 100)],
            ],
            'translator' => [
                'input' => $this->generateRandomText(),
                'output' => $this->generateTranslatedText(),
                'meta' => ['from_lang' => 'en', 'to_lang' => $this->generateRandomLanguage(), 'word_count' => rand(10, 100)],
            ],
            'voice' => [
                'input' => 'uploads/audio/recording_' . rand(1, 50) . '.mp3',
                'output' => $this->generateTranscribedText(),
                'meta' => ['duration' => rand(30, 600), 'language' => 'en', 'confidence' => rand(80, 99)],
            ],
        ];

        return $data[$toolSlug] ?? $data['writer'];
    }

    private function generateRandomString(int $length): string
    {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
    }

    private function generateRandomTopic(): string
    {
        $topics = [
            'artificial intelligence', 'machine learning', 'web development', 'data science',
            'blockchain technology', 'cybersecurity', 'cloud computing', 'mobile development',
            'user experience design', 'digital marketing', 'project management', 'business strategy',
        ];
        return $topics[array_rand($topics)];
    }

    private function generateRandomSummary(): string
    {
        $summaries = [
            'The main concept involves understanding the fundamental principles and applying them effectively.',
            'Key insights include practical applications and real-world examples that demonstrate the concepts.',
            'Important considerations are scalability, performance, and user experience in implementation.',
            'The approach focuses on best practices and industry standards for optimal results.',
        ];
        return $summaries[array_rand($summaries)];
    }

    private function generateRandomContentType(): string
    {
        $types = ['blog post', 'article', 'essay', 'story', 'report', 'summary', 'review'];
        return $types[array_rand($types)];
    }

    private function generateRandomContent(): string
    {
        $contents = [
            'This is a comprehensive analysis of the topic, covering all major aspects and providing detailed insights.',
            'The content explores various perspectives and offers practical recommendations for implementation.',
            'A detailed examination reveals important patterns and trends that are worth considering.',
            'The analysis provides valuable information and actionable insights for readers.',
        ];
        return $contents[array_rand($contents)];
    }

    private function generateRandomMathProblem(): string
    {
        $problems = [
            '2x + 5 = 13', '3y - 7 = 14', 'x² + 4x + 4 = 0', '2x + 3y = 12',
            '√(x + 5) = 3', 'log₂(x) = 4', 'sin(x) = 0.5', '∫(2x + 3)dx',
        ];
        return $problems[array_rand($problems)];
    }

    private function generateMathSolution(): string
    {
        $solutions = ['x = 4', 'y = 7', 'x = -2', 'x = 16', 'x = 4', 'x = 16', 'x = 30°', 'x² + 3x + C'];
        return $solutions[array_rand($solutions)];
    }

    private function generateRandomMathType(): string
    {
        $types = ['algebra', 'calculus', 'geometry', 'trigonometry', 'statistics'];
        return $types[array_rand($types)];
    }

    private function generateFlashcards(): array
    {
        $cards = [];
        $count = rand(3, 8);
        
        for ($i = 0; $i < $count; $i++) {
            $cards[] = [
                'question' => 'What is ' . $this->generateRandomTopic() . '?',
                'answer' => 'A comprehensive explanation of ' . $this->generateRandomTopic() . '.',
            ];
        }
        
        return $cards;
    }

    private function generateRandomDiagramType(): string
    {
        $types = ['flowchart', 'mind map', 'network diagram', 'process diagram', 'organizational chart'];
        return $types[array_rand($types)];
    }

    private function generateDiagramCode(): string
    {
        return "graph TD; A[Start] --> B[Process]; B --> C[Decision]; C -->|Yes| D[End]; C -->|No| B;";
    }

    private function generateRandomLanguage(): string
    {
        $languages = ['JavaScript', 'Python', 'Java', 'C++', 'PHP', 'Ruby', 'Go', 'Rust'];
        return $languages[array_rand($languages)];
    }

    private function generateCodeSnippet(): string
    {
        return "function example() {\n    console.log('Hello, World!');\n    return true;\n}";
    }

    private function generateRandomText(): string
    {
        $texts = [
            'Hello, how are you today?',
            'This is a sample text for translation.',
            'The weather is beautiful today.',
            'I love learning new technologies.',
        ];
        return $texts[array_rand($texts)];
    }

    private function generateTranslatedText(): string
    {
        $translations = [
            'Hola, ¿cómo estás hoy?',
            'Este es un texto de muestra para traducción.',
            'El clima está hermoso hoy.',
            'Me encanta aprender nuevas tecnologías.',
        ];
        return $translations[array_rand($translations)];
    }

    private function generateTranscribedText(): string
    {
        $transcriptions = [
            'This is a sample transcription of the audio recording.',
            'The speaker discussed various topics related to technology and innovation.',
            'Key points from the meeting include project updates and future plans.',
            'The presentation covered important aspects of the business strategy.',
        ];
        return $transcriptions[array_rand($transcriptions)];
    }
}