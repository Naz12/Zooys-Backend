<?php

namespace App\Services;

use App\Models\ContentUpload;
use App\Models\DocumentChunk;
use App\Services\EmbeddingService;
use App\Services\SimilaritySearchService;
use App\Services\ContentProcessingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RAGService
{
    protected $embeddingService;
    protected $similaritySearchService;
    protected $contentProcessingService;

    public function __construct(
        EmbeddingService $embeddingService,
        SimilaritySearchService $similaritySearchService,
        ContentProcessingService $contentProcessingService
    ) {
        $this->embeddingService = $embeddingService;
        $this->similaritySearchService = $similaritySearchService;
        $this->contentProcessingService = $contentProcessingService;
    }

    /**
     * Process document for RAG (chunk and generate embeddings)
     */
    public function processDocument(int $uploadId): bool
    {
        try {
            $upload = ContentUpload::find($uploadId);
            if (!$upload) {
                throw new \Exception('Upload not found');
            }

            // Check if already processed
            if ($upload->rag_processed_at) {
                Log::info("Document {$uploadId} already processed for RAG");
                return true;
            }

            // Extract text from document
            $text = $this->extractTextFromUpload($upload);
            if (empty($text)) {
                throw new \Exception('No text extracted from document');
            }

            // Chunk the document
            $chunks = $this->chunkDocument($text, $upload);
            if (empty($chunks)) {
                throw new \Exception('No chunks created from document');
            }

            // Generate embeddings for chunks
            $this->generateChunkEmbeddings($chunks, $uploadId);

            // Update upload status
            $upload->update([
                'rag_processed_at' => now(),
                'chunk_count' => count($chunks),
                'rag_enabled' => true
            ]);

            Log::info("Document {$uploadId} processed for RAG with " . count($chunks) . " chunks");
            return true;

        } catch (\Exception $e) {
            Log::error("RAG processing error for upload {$uploadId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Extract text from uploaded document
     */
    private function extractTextFromUpload(ContentUpload $upload): string
    {
        $filePath = Storage::path($upload->file_path);
        
        if (!file_exists($filePath)) {
            throw new \Exception('File not found: ' . $filePath);
        }

        switch ($upload->file_type) {
            case 'pdf':
                $result = $this->contentProcessingService->extractTextFromPDF($filePath);
                if (!$result['success']) {
                    throw new \Exception($result['error']);
                }
                return $result['text'];
            
            case 'text':
                return file_get_contents($filePath);
            
            default:
                throw new \Exception('Unsupported file type for RAG: ' . $upload->file_type);
        }
    }

    /**
     * Chunk document into smaller pieces
     */
    private function chunkDocument(string $text, ContentUpload $upload): array
    {
        $chunks = [];
        $chunkSize = 2000; // characters per chunk
        $overlap = 200; // overlap between chunks
        
        $text = $this->cleanText($text);
        $textLength = strlen($text);
        
        $chunkIndex = 0;
        $start = 0;
        
        while ($start < $textLength) {
            $end = min($start + $chunkSize, $textLength);
            
            // Try to end at sentence boundary
            if ($end < $textLength) {
                $lastPeriod = strrpos(substr($text, $start, $chunkSize), '.');
                if ($lastPeriod !== false && $lastPeriod > $chunkSize * 0.7) {
                    $end = $start + $lastPeriod + 1;
                }
            }
            
            $chunkText = substr($text, $start, $end - $start);
            
            if (strlen(trim($chunkText)) > 100) { // Only include substantial chunks
                $chunks[] = [
                    'content' => trim($chunkText),
                    'chunk_index' => $chunkIndex,
                    'start_pos' => $start,
                    'end_pos' => $end
                ];
                $chunkIndex++;
            }
            
            $start = $end - $overlap;
        }
        
        return $chunks;
    }

    /**
     * Generate embeddings for document chunks
     */
    private function generateChunkEmbeddings(array $chunks, int $uploadId): void
    {
        foreach ($chunks as $chunkData) {
            try {
                // Truncate content for embedding
                $truncatedContent = $this->embeddingService->truncateTextForEmbedding($chunkData['content']);
                
                // Generate embedding
                $embedding = $this->embeddingService->generateEmbedding($truncatedContent);
                
                // Store chunk in database
                DocumentChunk::create([
                    'upload_id' => $uploadId,
                    'chunk_index' => $chunkData['chunk_index'],
                    'content' => $chunkData['content'],
                    'embedding' => $embedding,
                    'page_start' => null, // We don't have page info for now
                    'page_end' => null,
                    'metadata' => [
                        'start_pos' => $chunkData['start_pos'],
                        'end_pos' => $chunkData['end_pos'],
                        'content_length' => strlen($chunkData['content'])
                    ]
                ]);
                
            } catch (\Exception $e) {
                Log::error("Failed to generate embedding for chunk {$chunkData['chunk_index']}: " . $e->getMessage());
                // Continue with other chunks
            }
        }
    }

    /**
     * Clean text for processing
     */
    private function cleanText(string $text): string
    {
        // Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Remove special characters but keep punctuation
        $text = preg_replace('/[^\w\s\.\,\!\?\;\:\-\(\)]/', '', $text);
        
        // Remove multiple newlines
        $text = preg_replace('/\n\s*\n/', "\n\n", $text);
        
        return trim($text);
    }

    /**
     * Get RAG-based summary for a document
     */
    public function getRAGSummary(int $uploadId, string $query = null, array $options = []): array
    {
        try {
            $upload = ContentUpload::find($uploadId);
            if (!$upload || !$upload->rag_enabled) {
                throw new \Exception('Document not processed for RAG');
            }

            // Generate query embedding
            $queryText = $query ?: 'Provide a comprehensive summary of this document';
            $queryEmbedding = $this->embeddingService->generateEmbedding($queryText);

            // Find relevant chunks
            $relevantContent = $this->similaritySearchService->getRelevantContentWithMetadata(
                $queryEmbedding, 
                $uploadId, 
                $options['max_chunks'] ?? 5
            );

            if (empty($relevantContent['content'])) {
                throw new \Exception('No relevant content found');
            }

            return [
                'content' => $relevantContent['content'],
                'chunks' => $relevantContent['chunks'],
                'metadata' => $relevantContent['metadata'],
                'query' => $query,
                'total_chunks' => $upload->chunk_count
            ];

        } catch (\Exception $e) {
            Log::error("RAG summary error for upload {$uploadId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if document is RAG-enabled
     */
    public function isRAGEnabled(int $uploadId): bool
    {
        $upload = ContentUpload::find($uploadId);
        return $upload && $upload->rag_enabled;
    }

    /**
     * Get document RAG statistics
     */
    public function getDocumentStats(int $uploadId): array
    {
        $upload = ContentUpload::find($uploadId);
        if (!$upload) {
            return [];
        }

        $stats = $this->similaritySearchService->getDocumentStats($uploadId);
        
        return [
            'upload_id' => $uploadId,
            'rag_enabled' => $upload->rag_enabled,
            'processed_at' => $upload->rag_processed_at,
            'chunk_count' => $upload->chunk_count,
            'total_chunks' => $stats['total_chunks'],
            'total_content_length' => $stats['total_content_length'],
            'page_range' => $stats['page_range']
        ];
    }

    /**
     * Delete RAG data for a document
     */
    public function deleteRAGData(int $uploadId): bool
    {
        try {
            // Delete all chunks
            DocumentChunk::where('upload_id', $uploadId)->delete();
            
            // Update upload status
            $upload = ContentUpload::find($uploadId);
            if ($upload) {
                $upload->update([
                    'rag_processed_at' => null,
                    'chunk_count' => 0,
                    'rag_enabled' => false
                ]);
            }
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("Failed to delete RAG data for upload {$uploadId}: " . $e->getMessage());
            return false;
        }
    }
}
