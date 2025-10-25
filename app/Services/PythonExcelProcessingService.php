<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class PythonExcelProcessingService
{
    protected $pythonScriptPath;

    public function __construct()
    {
        $this->pythonScriptPath = base_path('python_document_extractors/excel_extractor.py');
    }

    public function extractTextFromExcel($filePath)
    {
        Log::info("Calling Python Excel extractor for file: {$filePath}");
        
        $command = ['python', $this->pythonScriptPath, $filePath];

        $process = new Process($command);
        $process->setTimeout(300); // 5 minutes timeout for Excel extraction

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
            Log::error("Python Excel extraction failed: " . $exception->getMessage(), [
                'file_path' => $filePath,
                'output' => $process->getOutput(),
                'error_output' => $process->getErrorOutput()
            ]);
            return [
                'success' => false,
                'text' => '',
                'sheets' => 0,
                'metadata' => [],
                'word_count' => 0,
                'character_count' => 0,
                'error' => 'Excel extraction failed: ' . $process->getErrorOutput()
            ];
        } catch (\Exception $e) {
            Log::error("Error during Python Excel processing: " . $e->getMessage(), ['file_path' => $filePath]);
            return [
                'success' => false,
                'text' => '',
                'sheets' => 0,
                'metadata' => [],
                'word_count' => 0,
                'character_count' => 0,
                'error' => 'Excel processing failed: ' . $e->getMessage()
            ];
        }
    }
}


