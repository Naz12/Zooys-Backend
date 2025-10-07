<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class PythonYouTubeService
{
    private $pythonScriptPath;
    private $pythonExecutable;

    public function __construct()
    {
        $this->pythonScriptPath = base_path('python/youtube_caption_extractor.py');
        $this->pythonExecutable = $this->findPythonExecutable();
    }

    /**
     * Find Python executable
     */
    private function findPythonExecutable()
    {
        $possiblePaths = [
            'python',
            'python3',
            'py',
            'C:\\Python39\\python.exe',
            'C:\\Python310\\python.exe',
            'C:\\Python311\\python.exe',
            'C:\\Python312\\python.exe',
            'C:\\Python313\\python.exe',
            // Check user's AppData directory
            getenv('USERPROFILE') . '\\AppData\\Local\\Programs\\Python\\Python313\\python.exe',
            getenv('USERPROFILE') . '\\AppData\\Local\\Programs\\Python\\Python312\\python.exe',
            getenv('USERPROFILE') . '\\AppData\\Local\\Programs\\Python\\Python311\\python.exe',
        ];

        foreach ($possiblePaths as $path) {
            try {
                if (file_exists($path)) {
                    $result = Process::run("\"$path\" --version");
                    if ($result->successful()) {
                        Log::info("Found Python executable: $path");
                        return $path;
                    }
                }
            } catch (\Exception $e) {
                // Continue to next path
            }
        }

        return null;
    }

    /**
     * Check if Python is available
     */
    public function isPythonAvailable()
    {
        return $this->pythonExecutable !== null;
    }

    /**
     * Extract captions from YouTube video using Python
     */
    public function extractCaptions($videoUrl, $language = 'en')
    {
        if (!$this->isPythonAvailable()) {
            Log::warning('Python not available for caption extraction');
            return [
                'success' => false,
                'error' => 'Python not available',
                'fallback' => true
            ];
        }

        try {
            // Prepare command with proper environment setup
            $command = sprintf(
                '%s %s "%s" --language %s',
                $this->pythonExecutable,
                $this->pythonScriptPath,
                $videoUrl,
                $language
            );

            Log::info("Executing Python command: $command");

            // Set environment variables for better network access
            $env = [
                'PYTHONPATH' => base_path('python'),
                'PYTHONIOENCODING' => 'utf-8',
                'HTTP_PROXY' => '',
                'HTTPS_PROXY' => '',
                'NO_PROXY' => 'localhost,127.0.0.1',
            ];

            // Execute Python script with environment variables
            $result = Process::run($command, null, null, null, 60, $env);

            if (!$result->successful()) {
                Log::error('Python script execution failed: ' . $result->errorOutput());
                return [
                    'success' => false,
                    'error' => 'Python script execution failed: ' . $result->errorOutput(),
                    'fallback' => true
                ];
            }

            // Parse JSON output
            $output = $result->output();
            $data = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to parse Python output as JSON: ' . json_last_error_msg());
                return [
                    'success' => false,
                    'error' => 'Failed to parse Python output',
                    'fallback' => true
                ];
            }

            if ($data['success']) {
                Log::info("Python caption extraction successful: {$data['word_count']} words");
            } else {
                Log::warning("Python caption extraction failed: {$data['error']}");
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('Python caption extraction exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage(),
                'fallback' => true
            ];
        }
    }

    /**
     * Get video transcript with fallback
     */
    public function getVideoTranscript($videoUrl, $language = 'en')
    {
        // Handle null language parameter
        if ($language === null) {
            $language = 'en';
        }
        
        // Try Python extraction first
        $result = $this->extractCaptions($videoUrl, $language);

        if ($result['success']) {
            return [
                'success' => true,
                'transcript' => $result['transcript'],
                'method' => 'python',
                'language' => $result['language'],
                'word_count' => $result['word_count'],
                'character_count' => $result['character_count'],
                'segment_count' => $result['segment_count']
            ];
        }

        // Fallback to PHP implementation
        Log::info('Falling back to PHP caption extraction');
        return [
            'success' => false,
            'error' => 'Python extraction failed, fallback not implemented',
            'method' => 'fallback',
            'fallback' => true
        ];
    }

    /**
     * Test Python integration
     */
    public function testIntegration()
    {
        if (!$this->isPythonAvailable()) {
            return [
                'success' => false,
                'error' => 'Python not available',
                'python_path' => null
            ];
        }

        try {
            $testCommand = "{$this->pythonExecutable} --version";
            $result = Process::run($testCommand);

            return [
                'success' => $result->successful(),
                'python_path' => $this->pythonExecutable,
                'version' => $result->output(),
                'error' => $result->successful() ? null : $result->errorOutput()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'python_path' => $this->pythonExecutable,
                'error' => $e->getMessage()
            ];
        }
    }
}
