<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ContentProcessingService
{
    /**
     * Extract text from PDF
     */
    public function extractTextFromPDF($filePath)
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            
            $text = $pdf->getText();
            $pages = $pdf->getPages();
            $metadata = $pdf->getDetails();
            
            if (empty($text)) {
                throw new \Exception('No readable text found in PDF. The document may be scanned or image-based.');
            }
            
            return [
                'text' => $text,
                'pages' => count($pages),
                'metadata' => $metadata,
                'word_count' => str_word_count($text),
                'character_count' => strlen($text),
                'success' => true
            ];
            
        } catch (\Exception $e) {
            Log::error('PDF processing error for: ' . $filePath . ' - ' . $e->getMessage());
            
            // Provide user-friendly error messages
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'Secured pdf') !== false) {
                $errorMessage = 'This PDF is password-protected and cannot be processed. Please use an unprotected PDF file.';
            } elseif (strpos($errorMessage, 'Invalid PDF') !== false) {
                $errorMessage = 'This file is not a valid PDF or is corrupted. Please try a different PDF file.';
            } elseif (strpos($errorMessage, 'Permission denied') !== false) {
                $errorMessage = 'Unable to access the PDF file. Please check file permissions.';
            }
            
            return [
                'text' => '',
                'pages' => 0,
                'metadata' => [],
                'word_count' => 0,
                'character_count' => 0,
                'success' => false,
                'error' => $errorMessage
            ];
        }
    }

    /**
     * Extract text from image using OCR
     */
    public function extractTextFromImage($filePath)
    {
        // Mock OCR text extraction
        Log::info("Mock OCR text extraction for: " . $filePath);
        return "This is mock OCR text extracted from image: " . $filePath;
    }

    /**
     * Transcribe audio file
     */
    public function transcribeAudio($filePath)
    {
        // Mock audio transcription
        Log::info("Mock audio transcription for: " . $filePath);
        return "This is mock transcription of audio file: " . $filePath;
    }

    /**
     * Process video file (extract audio and transcribe)
     */
    public function processVideo($filePath)
    {
        // Mock video processing
        Log::info("Mock video processing for: " . $filePath);
        return "This is mock transcription from video audio track: " . $filePath;
    }

    /**
     * Extract content from web URL
     */
    public function extractWebContent($url)
    {
        // Mock web scraping
        Log::info("Mock web content extraction for: " . $url);
        return "This is mock content extracted from URL: " . $url;
    }

    /**
     * Get file metadata
     */
    public function getFileMetadata($filePath)
    {
        if (!Storage::exists($filePath)) {
            return null;
        }

        return [
            'file_size' => Storage::size($filePath),
            'last_modified' => Storage::lastModified($filePath),
            'mime_type' => Storage::mimeType($filePath)
        ];
    }

    /**
     * Clean up temporary files
     */
    public function cleanupTempFiles($filePath)
    {
        try {
            if (Storage::exists($filePath)) {
                Storage::delete($filePath);
                Log::info("Cleaned up temporary file: " . $filePath);
            }
        } catch (\Exception $e) {
            Log::error("Failed to cleanup file: " . $e->getMessage());
        }
    }
}
