<?php

namespace App\Services\Modules;

use Illuminate\Support\Facades\Log;

class ContentChunkingService
{
    private $maxChunkSize;
    private $overlapSize;
    private $minChunkSize;

    public function __construct()
    {
        $this->maxChunkSize = config('ai.chunking.max_size', 3000);
        $this->overlapSize = config('ai.chunking.overlap_size', 200);
        $this->minChunkSize = config('ai.chunking.min_size', 500);
    }

    /**
     * Chunk content based on type and requirements
     */
    public function chunkContent($content, $contentType = 'text', $options = [])
    {
        $chunkingStrategy = $this->getChunkingStrategy($contentType);
        
        return $this->$chunkingStrategy($content, $options);
    }

    /**
     * Smart text chunking with sentence boundary detection
     */
    public function chunkText($content, $options = [])
    {
        $maxSize = $options['max_size'] ?? $this->maxChunkSize;
        $overlap = $options['overlap'] ?? $this->overlapSize;
        
        $chunks = [];
        $sentences = $this->splitIntoSentences($content);
        $currentChunk = '';
        $currentSize = 0;
        
        foreach ($sentences as $sentence) {
            $sentenceSize = strlen($sentence);
            
            // If adding this sentence would exceed max size, start new chunk
            if ($currentSize + $sentenceSize > $maxSize && $currentSize > $this->minChunkSize) {
                $chunks[] = $this->createChunk($currentChunk, count($chunks));
                $currentChunk = $sentence;
                $currentSize = $sentenceSize;
            } else {
                $currentChunk .= ($currentChunk ? ' ' : '') . $sentence;
                $currentSize += $sentenceSize;
            }
        }
        
        // Add the last chunk if it has content
        if (!empty(trim($currentChunk))) {
            $chunks[] = $this->createChunk($currentChunk, count($chunks));
        }
        
        return $this->addOverlap($chunks, $overlap);
    }

    /**
     * YouTube transcript chunking with speaker awareness
     */
    public function chunkTranscript($content, $options = [])
    {
        $maxSize = $options['max_size'] ?? $this->maxChunkSize;
        $overlap = $options['overlap'] ?? $this->overlapSize;
        
        // Split by speaker changes or time intervals
        $segments = $this->splitTranscriptIntoSegments($content);
        $chunks = [];
        $currentChunk = '';
        $currentSize = 0;
        
        foreach ($segments as $segment) {
            $segmentSize = strlen($segment);
            
            if ($currentSize + $segmentSize > $maxSize && $currentSize > $this->minChunkSize) {
                $chunks[] = $this->createChunk($currentChunk, count($chunks), 'transcript');
                $currentChunk = $segment;
                $currentSize = $segmentSize;
            } else {
                $currentChunk .= ($currentChunk ? ' ' : '') . $segment;
                $currentSize += $segmentSize;
            }
        }
        
        if (!empty(trim($currentChunk))) {
            $chunks[] = $this->createChunk($currentChunk, count($chunks), 'transcript');
        }
        
        return $this->addOverlap($chunks, $overlap);
    }

    /**
     * PDF document chunking with paragraph awareness
     */
    public function chunkDocument($content, $options = [])
    {
        $maxSize = $options['max_size'] ?? $this->maxChunkSize;
        $overlap = $options['overlap'] ?? $this->overlapSize;
        
        // Split by paragraphs first
        $paragraphs = $this->splitIntoParagraphs($content);
        $chunks = [];
        $currentChunk = '';
        $currentSize = 0;
        
        foreach ($paragraphs as $paragraph) {
            $paragraphSize = strlen($paragraph);
            
            if ($currentSize + $paragraphSize > $maxSize && $currentSize > $this->minChunkSize) {
                $chunks[] = $this->createChunk($currentChunk, count($chunks), 'document');
                $currentChunk = $paragraph;
                $currentSize = $paragraphSize;
            } else {
                $currentChunk .= ($currentChunk ? "\n\n" : '') . $paragraph;
                $currentSize += $paragraphSize;
            }
        }
        
        if (!empty(trim($currentChunk))) {
            $chunks[] = $this->createChunk($currentChunk, count($chunks), 'document');
        }
        
        return $this->addOverlap($chunks, $overlap);
    }

    /**
     * Get chunking strategy based on content type
     */
    private function getChunkingStrategy($contentType)
    {
        $strategies = [
            'text' => 'chunkText',
            'transcript' => 'chunkTranscript',
            'document' => 'chunkDocument',
            'youtube' => 'chunkTranscript',
            'pdf' => 'chunkDocument',
        ];
        
        return $strategies[$contentType] ?? 'chunkText';
    }

    /**
     * Split text into sentences
     */
    private function splitIntoSentences($text)
    {
        // Simple sentence splitting - can be enhanced with NLP
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        return array_filter(array_map('trim', $sentences));
    }

    /**
     * Split transcript into segments
     */
    private function splitTranscriptIntoSegments($transcript)
    {
        // Split by speaker changes or time markers
        $segments = preg_split('/(?=\[.*?\]|\n\n)/', $transcript, -1, PREG_SPLIT_NO_EMPTY);
        return array_filter(array_map('trim', $segments));
    }

    /**
     * Split document into paragraphs
     */
    private function splitIntoParagraphs($content)
    {
        $paragraphs = preg_split('/\n\s*\n/', $content, -1, PREG_SPLIT_NO_EMPTY);
        return array_filter(array_map('trim', $paragraphs));
    }

    /**
     * Create a chunk with metadata
     */
    private function createChunk($content, $index, $type = 'text')
    {
        return [
            'content' => trim($content),
            'index' => $index,
            'type' => $type,
            'size' => strlen($content),
            'word_count' => str_word_count($content),
            'character_count' => strlen($content),
        ];
    }

    /**
     * Add overlap between chunks for context preservation
     */
    private function addOverlap($chunks, $overlapSize)
    {
        if (count($chunks) <= 1 || $overlapSize <= 0) {
            return $chunks;
        }
        
        for ($i = 1; $i < count($chunks); $i++) {
            $previousChunk = $chunks[$i - 1]['content'];
            $overlap = substr($previousChunk, -$overlapSize);
            
            if (!empty($overlap)) {
                $chunks[$i]['content'] = $overlap . ' ' . $chunks[$i]['content'];
                $chunks[$i]['overlap'] = $overlap;
            }
        }
        
        return $chunks;
    }

    /**
     * Get chunking statistics
     */
    public function getChunkingStats($chunks)
    {
        return [
            'total_chunks' => count($chunks),
            'total_characters' => array_sum(array_column($chunks, 'character_count')),
            'total_words' => array_sum(array_column($chunks, 'word_count')),
            'average_chunk_size' => count($chunks) > 0 ? array_sum(array_column($chunks, 'character_count')) / count($chunks) : 0,
            'largest_chunk' => max(array_column($chunks, 'character_count')),
            'smallest_chunk' => min(array_column($chunks, 'character_count')),
        ];
    }
}
