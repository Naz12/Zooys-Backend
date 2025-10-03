<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class WordProcessingService
{
    /**
     * Extract text from Word document
     */
    public function extractTextFromWord($filePath)
    {
        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
            $text = '';
            $pages = [];
            $currentPage = 1;
            
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . "\n";
                        
                        // Split into pages (rough estimation: 2000 characters per page)
                        if (strlen($text) > 2000) {
                            $pages[] = [
                                'page' => $currentPage++,
                                'text' => $text
                            ];
                            $text = '';
                        }
                    }
                }
            }
            
            if (!empty($text)) {
                $pages[] = [
                    'page' => $currentPage,
                    'text' => $text
                ];
            }
            
            $fullText = implode(' ', array_column($pages, 'text'));
            
            return [
                'success' => true,
                'text' => $fullText,
                'pages' => $pages,
                'total_pages' => count($pages),
                'word_count' => str_word_count($fullText),
                'character_count' => strlen($fullText)
            ];
            
        } catch (\Exception $e) {
            Log::error("Word processing error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Unable to process Word document: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Extract metadata from Word document
     */
    public function extractMetadata($filePath)
    {
        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
            $properties = $phpWord->getDocInfo();
            
            return [
                'title' => $properties->getTitle() ?? 'Untitled',
                'author' => $properties->getCreator() ?? 'Unknown',
                'subject' => $properties->getSubject() ?? '',
                'description' => $properties->getDescription() ?? '',
                'keywords' => $properties->getKeywords() ?? '',
                'created_date' => $properties->getCreated() ?? null,
                'modified_date' => $properties->getModified() ?? null,
                'company' => $properties->getCompany() ?? '',
                'category' => $properties->getCategory() ?? ''
            ];
            
        } catch (\Exception $e) {
            Log::error("Word metadata extraction error: " . $e->getMessage());
            return [
                'title' => 'Untitled',
                'author' => 'Unknown',
                'subject' => '',
                'description' => '',
                'keywords' => '',
                'created_date' => null,
                'modified_date' => null,
                'company' => '',
                'category' => ''
            ];
        }
    }
    
    /**
     * Check if Word document is password protected
     */
    public function isPasswordProtected($filePath)
    {
        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
            return false; // PhpWord doesn't support password-protected files
        } catch (\Exception $e) {
            // If we can't load the file, it might be password protected
            return strpos($e->getMessage(), 'password') !== false || 
                   strpos($e->getMessage(), 'encrypted') !== false;
        }
    }
}
