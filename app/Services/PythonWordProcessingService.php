<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class PythonWordProcessingService
{
    protected $pythonScriptPath;

    public function __construct()
    {
        $this->pythonScriptPath = base_path('python_document_extractors/word_extractor.py');
    }

    public function extractTextFromWord($filePath, $password = null)
    {
        Log::info("Calling Python Word extractor for file: {$filePath}");
        
        $command = ['python', $this->pythonScriptPath, $filePath];
        
        // Add password if provided (Python script needs to handle it)
        if ($password) {
            $command[] = '--password';
            $command[] = $password;
        }

        $process = new Process($command);
        $process->setTimeout(300); // 5 minutes timeout for Word extraction

        try {
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $output = $process->getOutput();
            $result = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Invalid JSON response from Python script: " . $output);
            }

            return $result;

        } catch (ProcessFailedException $exception) {
            Log::error("Python Word extraction failed: " . $exception->getMessage(), [
                'file_path' => $filePath,
                'output' => $process->getOutput(),
                'error_output' => $process->getErrorOutput()
            ]);
            return [
                'success' => false,
                'text' => '',
                'paragraphs' => 0,
                'metadata' => [],
                'word_count' => 0,
                'character_count' => 0,
                'error' => 'Word extraction failed: ' . $process->getErrorOutput()
            ];
        } catch (\Exception $e) {
            Log::error("Error during Python Word processing: " . $e->getMessage(), ['file_path' => $filePath]);
            return [
                'success' => false,
                'text' => '',
                'paragraphs' => 0,
                'metadata' => [],
                'word_count' => 0,
                'character_count' => 0,
                'error' => 'Word processing failed: ' . $e->getMessage()
            ];
        }
    }
}









