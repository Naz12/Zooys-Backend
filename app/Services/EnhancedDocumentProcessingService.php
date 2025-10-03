<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EnhancedDocumentProcessingService
{
    private $contentProcessingService;
    private $vectorService;
    
    public function __construct(ContentProcessingService $contentProcessingService, VectorDatabaseService $vectorService)
    {
        $this->contentProcessingService = $contentProcessingService;
        $this->vectorService = $vectorService;
    }
    
    /**
     * Process PDF with page tracking
     */
    public function processPDFWithPages($filePath)
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            $pages = $pdf->getPages();
            
            $chunks = [];
            $chunkIndex = 0;
            
            foreach ($pages as $pageNumber => $page) {
                $pageText = $page->getText();
                
                if (empty(trim($pageText))) {
                    continue; // Skip empty pages
                }
                
                // Split page into chunks
                $pageChunks = $this->splitIntoChunks($pageText, 500);
                
                foreach ($pageChunks as $chunk) {
                    $chunks[] = [
                        'page' => $pageNumber + 1,
                        'chunk_index' => $chunkIndex++,
                        'text' => $chunk
                    ];
                }
            }
            
            Log::info("Processed PDF: {$chunkIndex} chunks from " . count($pages) . " pages");
            return $chunks;
            
        } catch (\Exception $e) {
            Log::error("PDF processing error: " . $e->getMessage());
            throw new \Exception("Failed to process PDF: " . $e->getMessage());
        }
    }
    
    /**
     * Process Word document with page tracking
     */
    public function processWordWithPages($filePath)
    {
        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
            $chunks = [];
            $chunkIndex = 0;
            $currentPage = 1;
            $pageText = '';
            
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text = $element->getText();
                        $pageText .= $text . "\n";
                        
                        // Split into pages (rough estimation: 2000 characters per page)
                        if (strlen($pageText) > 2000) {
                            $pageChunks = $this->splitIntoChunks($pageText, 500);
                            
                            foreach ($pageChunks as $chunk) {
                                $chunks[] = [
                                    'page' => $currentPage,
                                    'chunk_index' => $chunkIndex++,
                                    'text' => $chunk
                                ];
                            }
                            
                            $currentPage++;
                            $pageText = '';
                        }
                    }
                }
            }
            
            // Process remaining text
            if (!empty(trim($pageText))) {
                $pageChunks = $this->splitIntoChunks($pageText, 500);
                
                foreach ($pageChunks as $chunk) {
                    $chunks[] = [
                        'page' => $currentPage,
                        'chunk_index' => $chunkIndex++,
                        'text' => $chunk
                    ];
                }
            }
            
            Log::info("Processed Word document: {$chunkIndex} chunks from {$currentPage} pages");
            return $chunks;
            
        } catch (\Exception $e) {
            Log::error("Word processing error: " . $e->getMessage());
            throw new \Exception("Failed to process Word document: " . $e->getMessage());
        }
    }
    
    /**
     * Process text file with page tracking
     */
    public function processTextWithPages($filePath)
    {
        try {
            $content = file_get_contents($filePath);
            $chunks = [];
            $chunkIndex = 0;
            $currentPage = 1;
            
            // Split content into pages (rough estimation: 2000 characters per page)
            $pages = str_split($content, 2000);
            
            foreach ($pages as $pageContent) {
                if (empty(trim($pageContent))) {
                    continue;
                }
                
                $pageChunks = $this->splitIntoChunks($pageContent, 500);
                
                foreach ($pageChunks as $chunk) {
                    $chunks[] = [
                        'page' => $currentPage,
                        'chunk_index' => $chunkIndex++,
                        'text' => $chunk
                    ];
                }
                
                $currentPage++;
            }
            
            Log::info("Processed text file: {$chunkIndex} chunks from " . count($pages) . " pages");
            return $chunks;
            
        } catch (\Exception $e) {
            Log::error("Text processing error: " . $e->getMessage());
            throw new \Exception("Failed to process text file: " . $e->getMessage());
        }
    }
    
    /**
     * Split text into chunks
     */
    private function splitIntoChunks($text, $maxLength = 500)
    {
        $chunks = [];
        $sentences = preg_split('/(?<=[.!?])\s+/', $text);
        $currentChunk = '';
        
        foreach ($sentences as $sentence) {
            if (strlen($currentChunk . $sentence) > $maxLength) {
                if (!empty($currentChunk)) {
                    $chunks[] = trim($currentChunk);
                    $currentChunk = $sentence;
                }
            } else {
                $currentChunk .= ' ' . $sentence;
            }
        }
        
        if (!empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }
        
        return $chunks;
    }
    
    /**
     * Process document and store in vector database
     */
    public function processDocument($documentId, $filePath, $fileType)
    {
        try {
            // Update processing status
            \App\Models\DocumentMetadata::updateOrCreate(
                ['document_id' => $documentId],
                ['processing_status' => 'processing']
            );
            
            // Process document based on type
            switch ($fileType) {
                case 'pdf':
                    $chunks = $this->processPDFWithPages($filePath);
                    break;
                case 'word':
                    $chunks = $this->processWordWithPages($filePath);
                    break;
                case 'text':
                    $chunks = $this->processTextWithPages($filePath);
                    break;
                default:
                    throw new \Exception("Unsupported file type: {$fileType}");
            }
            
            if (empty($chunks)) {
                throw new \Exception("No content found in document");
            }
            
            // Store in vector database
            $success = $this->vectorService->storeDocumentChunks($documentId, $chunks);
            
            if (!$success) {
                throw new \Exception("Failed to store document chunks");
            }
            
            Log::info("Successfully processed document {$documentId} with " . count($chunks) . " chunks");
            return [
                'success' => true,
                'total_chunks' => count($chunks),
                'total_pages' => max(array_column($chunks, 'page'))
            ];
            
        } catch (\Exception $e) {
            // Update status to failed
            \App\Models\DocumentMetadata::updateOrCreate(
                ['document_id' => $documentId],
                ['processing_status' => 'failed']
            );
            
            Log::error("Document processing failed for {$documentId}: " . $e->getMessage());
            throw $e;
        }
    }
}
