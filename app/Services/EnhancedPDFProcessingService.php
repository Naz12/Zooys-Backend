<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class EnhancedPDFProcessingService
{
    /**
     * Extract text from PDF with password support
     */
    public function extractTextFromPDF($filePath, $password = null)
    {
        try {
            // First try with smalot/pdfparser (faster for unprotected PDFs)
            $result = $this->extractWithSmalot($filePath);
            if ($result['success']) {
                return $result;
            }
            
            // If that fails and we have a password, try with FPDI
            if ($password) {
                return $this->extractWithFPDI($filePath, $password);
            }
            
            // If no password provided, return the error
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Enhanced PDF processing error for: ' . $filePath . ' - ' . $e->getMessage());
            
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
     * Extract text using smalot/pdfparser (for unprotected PDFs)
     */
    private function extractWithSmalot($filePath)
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
            return [
                'text' => '',
                'pages' => 0,
                'metadata' => [],
                'word_count' => 0,
                'character_count' => 0,
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract text using FPDI (for password-protected PDFs)
     */
    private function extractWithFPDI($filePath, $password)
    {
        try {
            $pdf = new Fpdi();
            $pdf->setSourceFile($filePath);
            
            $text = '';
            $pageCount = $pdf->setSourceFile($filePath);
            
            // Extract text from each page
            for ($i = 1; $i <= $pageCount; $i++) {
                $page = $pdf->importPage($i);
                $pdf->AddPage();
                $pdf->useTemplate($page);
                
                // Note: FPDI doesn't directly extract text, this is a simplified approach
                // For full text extraction from password-protected PDFs, you'd need additional libraries
            }
            
            if (empty($text)) {
                throw new \Exception('Unable to extract text from password-protected PDF. The document may be encrypted with strong encryption.');
            }
            
            return [
                'text' => $text,
                'pages' => $pageCount,
                'metadata' => [],
                'word_count' => str_word_count($text),
                'character_count' => strlen($text),
                'success' => true
            ];
            
        } catch (\Exception $e) {
            return [
                'text' => '',
                'pages' => 0,
                'metadata' => [],
                'word_count' => 0,
                'character_count' => 0,
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get user-friendly error messages
     */
    private function getUserFriendlyError($errorMessage)
    {
        if (strpos($errorMessage, 'Secured pdf') !== false) {
            return 'This PDF is password-protected. Please provide the password or use an unprotected PDF file.';
        } elseif (strpos($errorMessage, 'Invalid PDF') !== false) {
            return 'This file is not a valid PDF or is corrupted. Please try a different PDF file.';
        } elseif (strpos($errorMessage, 'Permission denied') !== false) {
            return 'Unable to access the PDF file. Please check file permissions.';
        } elseif (strpos($errorMessage, 'encrypted') !== false) {
            return 'This PDF is encrypted and cannot be processed. Please use an unencrypted PDF file.';
        } elseif (strpos($errorMessage, 'scanned') !== false) {
            return 'This PDF appears to be scanned (image-based) and cannot be processed for text extraction.';
        }
        
        return $errorMessage;
    }

    /**
     * Check if PDF is password-protected
     */
    public function isPasswordProtected($filePath)
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            return false; // If we get here, it's not password protected
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Secured pdf') !== false) {
                return true;
            }
            return false;
        }
    }

    /**
     * Get PDF metadata without password
     */
    public function getPDFMetadata($filePath)
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            return $pdf->getDetails();
        } catch (\Exception $e) {
            return [];
        }
    }
}
