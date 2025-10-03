<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EmbeddingService
{
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->apiUrl = config('services.openai.embedding_url', 'https://api.openai.com/v1/embeddings');
    }

    /**
     * Generate embedding for a single text
     */
    public function generateEmbedding(string $text): array
    {
        try {
            // Check cache first
            $cacheKey = 'embedding_' . md5($text);
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($this->apiUrl, [
                'input' => $text,
                'model' => 'text-embedding-ada-002'
            ]);

            if ($response->failed()) {
                Log::error('OpenAI Embedding API Error: ' . $response->body());
                throw new \Exception('Failed to generate embedding: ' . $response->body());
            }

            $data = $response->json();
            $embedding = $data['data'][0]['embedding'];

            // Cache the result for 24 hours
            Cache::put($cacheKey, $embedding, 86400);

            return $embedding;

        } catch (\Exception $e) {
            Log::error('Embedding generation error: ' . $e->getMessage());
            throw new \Exception('Failed to generate embedding: ' . $e->getMessage());
        }
    }

    /**
     * Generate embeddings for multiple texts (batch processing)
     */
    public function generateEmbeddings(array $texts): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($this->apiUrl, [
                'input' => $texts,
                'model' => 'text-embedding-ada-002'
            ]);

            if ($response->failed()) {
                Log::error('OpenAI Embedding API Error: ' . $response->body());
                throw new \Exception('Failed to generate embeddings: ' . $response->body());
            }

            $data = $response->json();
            $embeddings = [];

            foreach ($data['data'] as $item) {
                $embeddings[] = $item['embedding'];
            }

            return $embeddings;

        } catch (\Exception $e) {
            Log::error('Batch embedding generation error: ' . $e->getMessage());
            throw new \Exception('Failed to generate embeddings: ' . $e->getMessage());
        }
    }

    /**
     * Truncate text to fit within token limits
     */
    public function truncateTextForEmbedding(string $text, int $maxTokens = 8000): string
    {
        // Rough estimation: 1 token ≈ 4 characters
        $maxCharacters = $maxTokens * 4;
        
        if (strlen($text) <= $maxCharacters) {
            return $text;
        }
        
        // Truncate to max characters
        $truncated = substr($text, 0, $maxCharacters);
        
        // Try to end at a sentence boundary
        $lastSentence = strrpos($truncated, '.');
        if ($lastSentence !== false && $lastSentence > $maxCharacters * 0.8) {
            $truncated = substr($truncated, 0, $lastSentence + 1);
        }
        
        return $truncated;
    }

    /**
     * Get embedding dimensions
     */
    public function getEmbeddingDimensions(): int
    {
        return 1536; // text-embedding-ada-002 dimensions
    }
}
