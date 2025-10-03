<?php

namespace App\Services;

use App\Models\DocumentChunk;
use App\Models\DocumentMetadata;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VectorDatabaseService
{
    private $openAIService;
    
    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }
    
    /**
     * Store document chunks with embeddings
     */
    public function storeDocumentChunks($documentId, $chunks, $embeddings = null)
    {
        try {
            // Generate embeddings if not provided
            if (!$embeddings) {
                $embeddings = $this->generateEmbeddings(array_column($chunks, 'text'));
            }
            
            // Store chunks in database
            foreach ($chunks as $index => $chunk) {
                DocumentChunk::create([
                    'document_id' => $documentId,
                    'page_number' => $chunk['page'],
                    'chunk_index' => $chunk['chunk_index'] ?? $index,
                    'text' => $chunk['text'],
                    'embedding' => $embeddings[$index]
                ]);
            }
            
            // Update document metadata
            $this->updateDocumentMetadata($documentId, count($chunks), max(array_column($chunks, 'page')));
            
            Log::info("Stored {$chunks} chunks for document {$documentId}");
            return true;
            
        } catch (\Exception $e) {
            Log::error("Failed to store document chunks: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Search similar chunks
     */
    public function searchSimilarChunks($query, $documentId, $topK = 5)
    {
        try {
            // Generate query embedding
            $queryEmbedding = $this->generateEmbedding($query);
            
            // Get all chunks for the document
            $chunks = DocumentChunk::where('document_id', $documentId)->get();
            
            if ($chunks->isEmpty()) {
                return [];
            }
            
            // Calculate similarities
            $similarities = [];
            foreach ($chunks as $chunk) {
                $similarity = $this->calculateCosineSimilarity($queryEmbedding, $chunk->embedding);
                $similarities[] = [
                    'chunk' => $chunk,
                    'similarity' => $similarity
                ];
            }
            
            // Sort by similarity and return top K
            usort($similarities, function($a, $b) {
                return $b['similarity'] <=> $a['similarity'];
            });
            
            $results = array_slice($similarities, 0, $topK);
            
            return array_map(function($item) {
                return [
                    'id' => $item['chunk']->id,
                    'page' => $item['chunk']->page_number,
                    'chunk_index' => $item['chunk']->chunk_index,
                    'text' => $item['chunk']->text,
                    'similarity' => $item['similarity']
                ];
            }, $results);
            
        } catch (\Exception $e) {
            Log::error("Failed to search similar chunks: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generate embeddings for multiple texts
     */
    private function generateEmbeddings($texts)
    {
        $embeddings = [];
        
        foreach ($texts as $text) {
            $embeddings[] = $this->generateEmbedding($text);
        }
        
        return $embeddings;
    }
    
    /**
     * Generate embedding for single text
     */
    private function generateEmbedding($text)
    {
        try {
            // Use OpenAI embeddings API
            $response = $this->openAIService->generateEmbedding($text);
            return $response;
        } catch (\Exception $e) {
            Log::error("Failed to generate embedding: " . $e->getMessage());
            // Return zero vector as fallback
            return array_fill(0, 1536, 0.0);
        }
    }
    
    /**
     * Calculate cosine similarity between two vectors
     */
    private function calculateCosineSimilarity($vectorA, $vectorB)
    {
        if (count($vectorA) !== count($vectorB)) {
            return 0;
        }
        
        $dotProduct = 0;
        $normA = 0;
        $normB = 0;
        
        for ($i = 0; $i < count($vectorA); $i++) {
            $dotProduct += $vectorA[$i] * $vectorB[$i];
            $normA += $vectorA[$i] * $vectorA[$i];
            $normB += $vectorB[$i] * $vectorB[$i];
        }
        
        if ($normA == 0 || $normB == 0) {
            return 0;
        }
        
        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
    
    /**
     * Update document metadata
     */
    private function updateDocumentMetadata($documentId, $totalChunks, $totalPages)
    {
        DocumentMetadata::updateOrCreate(
            ['document_id' => $documentId],
            [
                'total_chunks' => $totalChunks,
                'total_pages' => $totalPages,
                'processing_status' => 'completed'
            ]
        );
    }
    
    /**
     * Get document processing status
     */
    public function getDocumentStatus($documentId)
    {
        $metadata = DocumentMetadata::where('document_id', $documentId)->first();
        
        if (!$metadata) {
            return [
                'status' => 'not_processed',
                'total_chunks' => 0,
                'total_pages' => 0
            ];
        }
        
        return [
            'status' => $metadata->processing_status,
            'total_chunks' => $metadata->total_chunks,
            'total_pages' => $metadata->total_pages
        ];
    }
}
