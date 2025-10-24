<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PythonPDFProcessingService
{
    private $pythonScriptPath;
    private $pythonExecutable;

    public function __construct()
    {
        $this->pythonScriptPath = base_path('python_document_extractors/pdf_extractor.py');
        $this->pythonExecutable = $this->getPythonExecutable();
    }

    /**
     * Extract text from PDF using Python script
     */
    public function extractTextFromPDF($filePath, $password = null)
    {
        try {
            Log::info("Extracting PDF text using Python: {$filePath}");
            
            if (!file_exists($filePath)) {
                throw new \Exception('PDF file not found: ' . $filePath);
            }

            if (!file_exists($this->pythonScriptPath)) {
                throw new \Exception('Python PDF extractor script not found: ' . $this->pythonScriptPath);
            }

            // Build command
            $command = $this->buildPythonCommand($filePath, $password);
            
            Log::info("Executing Python command: {$command}");
            
            // Execute Python script
            $output = shell_exec($command . ' 2>&1');
            
            if ($output === null) {
                throw new \Exception('Failed to execute Python script');
            }

            // Parse JSON output
            $result = json_decode($output, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response from Python script: ' . $output);
            }

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'PDF extraction failed');
            }

            Log::info("PDF extraction successful", [
                'pages' => $result['pages'],
                'word_count' => $result['word_count'],
                'character_count' => $result['character_count']
            ]);

            return [
                'text' => $result['text'],
                'pages' => $result['pages'],
                'metadata' => $result['metadata'] ?? [],
                'word_count' => $result['word_count'],
                'character_count' => $result['character_count'],
                'success' => true
            ];

        } catch (\Exception $e) {
            Log::error('Python PDF processing error: ' . $e->getMessage(), [
                'file_path' => $filePath,
                'python_script' => $this->pythonScriptPath
            ]);

            return [
                'text' => '',
                'pages' => 0,
                'metadata' => [],
                'word_count' => 0,
                'character_count' => 0,
                'success' => false,
                'error' => $this->getUserFriendlyError($e->getMessage())
            ];
        }
    }

    /**
     * Build Python command with arguments
     */
    private function buildPythonCommand($filePath, $password = null)
    {
        $command = escapeshellarg($this->pythonExecutable) . ' ' . escapeshellarg($this->pythonScriptPath);
        $command .= ' ' . escapeshellarg($filePath);
        
        if ($password) {
            $command .= ' --password ' . escapeshellarg($password);
        }

        return $command;
    }

    /**
     * Get Python executable path
     */
    private function getPythonExecutable()
    {
        // Try different Python executables with full paths
        $pythonCommands = [
            'python',
            'py',
            'C:\\Users\\nazrawi\\AppData\\Local\\Programs\\Python\\Python311\\python.exe'
        ];
        
        foreach ($pythonCommands as $cmd) {
            $output = shell_exec("{$cmd} --version 2>&1");
            if ($output && strpos($output, 'Python') !== false) {
                return $cmd;
            }
        }
        
        throw new \Exception('Python executable not found. Please install Python 3.6+ and ensure it\'s in your PATH.');
    }

    /**
     * Get user-friendly error messages
     */
    private function getUserFriendlyError($error)
    {
        $errorMessages = [
            'No readable text found' => 'No readable text found in PDF. The document may be scanned or image-based.',
            'PDF is password-protected' => 'This PDF is password-protected. Please provide the password or use an unprotected PDF.',
            'File not found' => 'PDF file not found. Please check the file path.',
            'Python executable not found' => 'Python is not installed or not in PATH. Please install Python 3.6+ to use PDF extraction.',
            'Missing required Python packages' => 'Required Python packages are missing. Please install: pip install PyPDF2 pdfplumber PyMuPDF pytesseract Pillow'
        ];

        foreach ($errorMessages as $key => $message) {
            if (strpos($error, $key) !== false) {
                return $message;
            }
        }

        return 'PDF extraction failed: ' . $error;
    }

    /**
     * Check if Python dependencies are installed
     */
    public function checkDependencies()
    {
        try {
            $command = escapeshellarg($this->pythonExecutable) . ' -c "import PyPDF2, pdfplumber, fitz, pytesseract, PIL; print(\"All dependencies available\")" 2>&1';
            $output = shell_exec($command);
            
            return [
                'success' => strpos($output, 'All dependencies available') !== false,
                'output' => $output
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
