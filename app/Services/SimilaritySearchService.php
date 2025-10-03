<?php

namespace App\Services;

use App\Models\DocumentChunk;
use Illuminate\Support\Facades\Log;

class SimilaritySearchService
{
    /**
     * Find similar chunks for a given query embedding
     */
    public function findSimilarChunks(array $queryEmbedding, int $uploadId, int $limit = 5, float $threshold = 0.7): array
    {
        try {
            // Get all chunks for the document
            $chunks = DocumentChunk::where('upload_id', $uploadId)->get();
            
            if ($chunks->isEmpty()) {
                return [];
            }

            $similarities = [];

            // Calculate similarity for each chunk
            foreach ($chunks as $chunk) {
                $similarity = $chunk->calculateSimilarity($queryEmbedding);
                
                if ($similarity >= $threshold) {
                    $similarities[] = [
                        'chunk' => $chunk,
                        'similarity' => $similarity
                    ];
                }
            }

            // Sort by similarity (highest first)
            usort($similarities, function ($a, $b) {
                return $b['similarity'] <=> $a['similarity'];
            });

            // Return top K results
            return array_slice($similarities, 0, $limit);

        } catch (\Exception $e) {
            Log::error('Similarity search error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Find similar chunks across multiple documents
     */
    public function findSimilarChunksAcrossDocuments(array $queryEmbedding, array $uploadIds, int $limit = 10, float $threshold = 0.7): array
    {
        try {
            // Get chunks from multiple documents
            $chunks = DocumentChunk::whereIn('upload_id', $uploadIds)->get();
            
            if ($chunks->isEmpty()) {
                return [];
            }

            $similarities = [];

            // Calculate similarity for each chunk
            foreach ($chunks as $chunk) {
                $similarity = $chunk->calculateSimilarity($queryEmbedding);
                
                if ($similarity >= $threshold) {
                    $similarities[] = [
                        'chunk' => $chunk,
                        'similarity' => $similarity
                    ];
                }
            }

            // Sort by similarity (highest first)
            usort($similarities, function ($a, $b) {
                return $b['similarity'] <=> $a['similarity'];
            });

            // Return top K results
            return array_slice($similarities, 0, $limit);

        } catch (\Exception $e) {
            Log::error('Cross-document similarity search error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get relevant content for summarization
     */
    public function getRelevantContent(array $queryEmbedding, int $uploadId, int $maxChunks = 5): string
    {
        $similarChunks = $this->findSimilarChunks($queryEmbedding, $uploadId, $maxChunks);
        
        if (empty($similarChunks)) {
            return '';
        }

        $content = '';
        foreach ($similarChunks as $item) {
            $chunk = $item['chunk'];
            $content .= $chunk->content . "\n\n";
        }

        return trim($content);
    }

    /**
     * Get relevant content with metadata
     */
    public function getRelevantContentWithMetadata(array $queryEmbedding, int $uploadId, int $maxChunks = 5): array
    {
        $similarChunks = $this->findSimilarChunks($queryEmbedding, $uploadId, $maxChunks);
        
        if (empty($similarChunks)) {
            return [
                'content' => '',
                'chunks' => [],
                'metadata' => []
            ];
        }

        $content = '';
        $chunks = [];
        $metadata = [];

        foreach ($similarChunks as $item) {
            $chunk = $item['chunk'];
            $content .= $chunk->content . "\n\n";
            
            $chunks[] = [
                'id' => $chunk->id,
                'content' => $chunk->content,
                'similarity' => $item['similarity'],
                'page_start' => $chunk->page_start,
                'page_end' => $chunk->page_end,
                'chunk_index' => $chunk->chunk_index
            ];

            $metadata[] = [
                'similarity' => $item['similarity'],
                'pages' => $chunk->page_start . '-' . $chunk->page_end,
                'chunk_index' => $chunk->chunk_index
            ];
        }

        return [
            'content' => trim($content),
            'chunks' => $chunks,
            'metadata' => $metadata
        ];
    }

    /**
     * Calculate cosine similarity between two vectors
     */
    public function calculateCosineSimilarity(array $vector1, array $vector2): float
    {
        if (count($vector1) !== count($vector2)) {
            return 0.0;
        }

        $dotProduct = 0;
        $norm1 = 0;
        $norm2 = 0;

        for ($i = 0; $i < count($vector1); $i++) {
            $dotProduct += $vector1[$i] * $vector2[$i];
            $norm1 += $vector1[$i] * $vector1[$i];
            $norm2 += $vector2[$i] * $vector2[$i];
        }

        $norm1 = sqrt($norm1);
        $norm2 = sqrt($norm2);

        if ($norm1 == 0 || $norm2 == 0) {
            return 0.0;
        }

        return $dotProduct / ($norm1 * $norm2);
    }

    /**
     * Get document statistics
     */
    public function getDocumentStats(int $uploadId): array
    {
        $chunks = DocumentChunk::where('upload_id', $uploadId)->get();
        
        return [
            'total_chunks' => $chunks->count(),
            'total_content_length' => $chunks->sum(function ($chunk) {
                return strlen($chunk->content);
            }),
            'page_range' => [
                'min' => $chunks->min('page_start'),
                'max' => $chunks->max('page_end')
            ],
            'chunk_sizes' => $chunks->map(function ($chunk) {
                return strlen($chunk->content);
            })->toArray()
        ];
    }
}
