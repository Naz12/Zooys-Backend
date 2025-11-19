# YouTube Chapters Extraction - Implementation Plan

## 📋 Overview

This document explains how to add a `chapters` field to the `/api/summarize/async/youtube` endpoint response by leveraging the **Document Intelligence** module to extract chapters from transcribed YouTube video content.

---

## 🔄 Current Flow (YouTube Summarization)

### Current Process:
```
YouTube URL 
  ↓
TranscriberModule.transcribeVideo()
  ↓
Returns: {
  transcript: "...",
  bundle: {
    article_text: "...",
    json_items: [...],
    transcript_json: [...]
  },
  video_id: "..."
}
  ↓
AI Manager.summarize(transcript)
  ↓
Returns: {
  summary: "...",
  key_points: [...],
  confidence_score: 0.8,
  model_used: "deepseek-chat"
}
```

### Current Response Structure:
```json
{
  "success": true,
  "job_id": "...",
  "tool_type": "summarize",
  "input_type": "youtube",
  "data": {
    "summary": "...",
    "key_points": [...],
    "confidence_score": 0.8,
    "model_used": "deepseek-chat",
    "transcript": "...",
    "bundle": {
      "article_text": "...",
      "json_items": [...],
      "transcript_json": [...]
    },
    "metadata": {...}
  }
}
```

---

## 🎯 Proposed Solution: Add Chapters Field

### How Document Intelligence Works

#### 1. **Text Ingestion** (`DocumentIntelligenceModule::ingestText()`)
- **Location**: `app/Services/Modules/DocumentIntelligenceModule.php` (line 355-392)
- **Method**: `ingestText(string $text, array $options = [])`
- **What it does**:
  - Takes raw text content
  - Ingests it into Document Intelligence microservice
  - Creates vector embeddings for semantic search
  - Returns `doc_id` and `job_id`
- **Options**:
  - `filename`: Custom filename (default: 'summary.txt')
  - `lang`: Language code (default: 'eng')
  - `metadata`: Custom metadata array
  - `force_fallback`: Always true (skip local LLM)

**Example Usage:**
```php
$docModule = app(\App\Services\Modules\DocumentIntelligenceModule::class);
$result = $docModule->ingestText($transcriptText, [
    'filename' => 'youtube_transcript.txt',
    'metadata' => [
        'source' => 'youtube',
        'video_id' => $videoId
    ]
]);
// Returns: { success: true, doc_id: "doc_abc123", job_id: "job_xyz" }
```

#### 2. **RAG-Powered Q&A** (`DocumentIntelligenceModule::answer()`)
- **Location**: `app/Services/Modules/DocumentIntelligenceModule.php` (line 107-208)
- **Method**: `answer(string $query, array $options = [])`
- **What it does**:
  - Takes a natural language question
  - Searches the ingested document semantically
  - Uses RAG (Retrieval-Augmented Generation) to generate an answer
  - Returns answer with source citations
- **Options**:
  - `doc_ids`: Array of document IDs (required)
  - `llm_model`: LLM to use (default: 'llama3', also supports 'deepseek-chat')
  - `max_tokens`: Maximum response tokens (default: 512, can be increased)
  - `top_k`: Number of context chunks (default: 3, can be increased)
  - `temperature`: LLM temperature (default: 0.7)
  - `force_fallback`: Always true (skip local LLM)

**Example Usage:**
```php
$result = $docModule->answer(
    "Please identify and extract all chapters or sections from this transcript. " .
    "For each chapter, provide: title, start timestamp (if available), and a brief description. " .
    "Format the response as a JSON array.",
    [
        'doc_ids' => [$docId],
        'llm_model' => 'deepseek-chat',
        'max_tokens' => 2000,  // More tokens for detailed chapters
        'top_k' => 10,         // More context chunks
        'temperature' => 0.7,
        'force_fallback' => true
    ]
);
// Returns: { success: true, answer: "[{title: '...', timestamp: '...', description: '...'}]" }
```

---

## 🔧 Implementation Strategy

### Step 1: After Transcription, Before Summarization

**Location**: `app/Services/UniversalJobService.php`
**Method**: `processYouTubeVideoSummarizationWithStages()` (around line 1070-1200)

**Current Flow:**
```php
// 1. Transcribe video
$transcriptionResult = $transcriberModule->transcribeVideo($videoUrl, [...]);
$transcript = $transcriptionResult['transcript'];

// 2. Summarize with AI Manager
$summaryResult = $aiProcessingModule->summarize($transcript, [...]);
```

**Proposed Addition:**
```php
// 1. Transcribe video
$transcriptionResult = $transcriberModule->transcribeVideo($videoUrl, [...]);
$transcript = $transcriptionResult['transcript'];
$articleText = $transcriptionResult['bundle']['article_text'] ?? $transcript;

// 2. Extract chapters using Document Intelligence
$this->updateJob($jobId, [
    'stage' => 'extracting_chapters',
    'progress' => 50
]);

$docIntelligenceModule = app(\App\Services\Modules\DocumentIntelligenceModule::class);

// Ingest transcript text
$ingestResult = $docIntelligenceModule->ingestText($articleText, [
    'filename' => "youtube_{$videoId}_transcript.txt",
    'metadata' => [
        'source' => 'youtube',
        'video_id' => $videoId,
        'user_id' => $userId
    ]
]);

$chapters = [];
if ($ingestResult['success'] && !empty($ingestResult['doc_id'])) {
    $docId = $ingestResult['doc_id'];
    
    // Wait for ingestion to complete (poll job)
    $ingestJobId = $ingestResult['job_id'] ?? null;
    if ($ingestJobId) {
        $pollResult = $docIntelligenceModule->pollJobCompletion($ingestJobId, 30, 2);
        // Wait until ingestion is complete
    }
    
    // Ask Document Intelligence to extract chapters
    $chaptersQuery = "Please analyze this transcript and identify all chapters or major sections. " .
                     "For each chapter, provide:\n" .
                     "1. Chapter title (clear and descriptive)\n" .
                     "2. Start timestamp (if available in the transcript)\n" .
                     "3. Brief description (1-2 sentences)\n\n" .
                     "Format your response as a valid JSON array of objects, where each object has: " .
                     "{\"title\": \"...\", \"timestamp\": \"...\", \"description\": \"...\"}. " .
                     "If timestamps are not available, use null for the timestamp field.";
    
    $chaptersResult = $docIntelligenceModule->answer($chaptersQuery, [
        'doc_ids' => [$docId],
        'llm_model' => 'deepseek-chat',
        'max_tokens' => 2000,
        'top_k' => 10,
        'temperature' => 0.7,
        'force_fallback' => true
    ]);
    
    if ($chaptersResult['success'] && !empty($chaptersResult['answer'])) {
        // Parse JSON response
        $chaptersJson = $chaptersResult['answer'];
        // Try to extract JSON from markdown if wrapped
        if (preg_match('/```json\s*(\[.*?\])\s*```/s', $chaptersJson, $matches)) {
            $chaptersJson = $matches[1];
        } elseif (preg_match('/\[.*?\]/s', $chaptersJson, $matches)) {
            $chaptersJson = $matches[0];
        }
        
        $decodedChapters = json_decode($chaptersJson, true);
        if (is_array($decodedChapters)) {
            $chapters = $decodedChapters;
        }
    }
}

// 3. Summarize with AI Manager (existing flow)
$summaryResult = $aiProcessingModule->summarize($transcript, [...]);
```

### Step 2: Add Chapters to Response

**Location**: `app/Services/UniversalJobService.php`
**Method**: `processYouTubeVideoSummarizationWithStages()` (around line 1200-1250)

**Current Response:**
```php
return [
    'success' => true,
    'summary' => $summary,
    'key_points' => $keyPoints,
    'confidence_score' => $confidenceScore,
    'model_used' => $modelUsed,
    'transcript' => $transcript,
    'bundle' => $bundle,
    'metadata' => $metadata
];
```

**Proposed Response:**
```php
return [
    'success' => true,
    'summary' => $summary,
    'key_points' => $keyPoints,
    'chapters' => $chapters,  // NEW FIELD
    'confidence_score' => $confidenceScore,
    'model_used' => $modelUsed,
    'transcript' => $transcript,
    'bundle' => $bundle,
    'metadata' => array_merge($metadata, [
        'chapters_extracted' => count($chapters) > 0,
        'chapters_count' => count($chapters)
    ])
];
```

---

## 📊 Expected Chapter Structure

### Chapter Object Format:
```json
{
  "title": "Introduction to the Topic",
  "timestamp": "00:00:00",
  "description": "The video begins with an introduction to the main topic..."
}
```

### Full Response Example:
```json
{
  "success": true,
  "job_id": "3b7b618c-3449-4aa7-bd02-dc5d9e0d2eba",
  "tool_type": "summarize",
  "input_type": "youtube",
  "data": {
    "summary": "A new poll shows Zoron leading...",
    "key_points": [...],
    "chapters": [
      {
        "title": "Poll Results Discussion",
        "timestamp": "00:00:00",
        "description": "Discussion of new poll showing Zoron leading Cuomo by 25 points"
      },
      {
        "title": "Voter Demographics Analysis",
        "timestamp": "00:02:30",
        "description": "Analysis of black voter support consolidation behind Zoron"
      },
      {
        "title": "Campaign Ads Review",
        "timestamp": "00:05:00",
        "description": "Review of closing campaign ads from both candidates"
      },
      {
        "title": "Political Challenges Discussion",
        "timestamp": "00:08:00",
        "description": "Discussion of potential challenges Zoron may face in governing"
      }
    ],
    "confidence_score": 0.8,
    "model_used": "deepseek-chat",
    "transcript": "...",
    "bundle": {...},
    "metadata": {
      "video_id": "OCaQUWTrNn8",
      "chapters_extracted": true,
      "chapters_count": 4
    }
  }
}
```

---

## 🔍 Key Implementation Details

### 1. **Text Source for Chapters**
- Use `bundle.article_text` if available (cleaner format)
- Fallback to `transcript` if `article_text` is not available
- The article text is usually better formatted for chapter extraction

### 2. **Ingestion Polling**
- Document Intelligence ingestion is async
- Need to poll `pollJobCompletion()` until status is 'completed'
- Maximum wait: 30 attempts × 2 seconds = 60 seconds
- If ingestion fails or times out, continue without chapters (non-blocking)

### 3. **Chapter Extraction Query**
- Use a clear, specific prompt asking for JSON format
- Request: title, timestamp, description
- Handle cases where timestamps may not be available
- Parse JSON from response (may be wrapped in markdown code blocks)

### 4. **Error Handling**
- If Document Intelligence is unavailable → continue without chapters
- If ingestion fails → continue without chapters
- If chapter extraction fails → continue without chapters
- Always return successful response even if chapters are empty

### 5. **Performance Considerations**
- Chapter extraction adds ~30-60 seconds to processing time
- Can be made optional via `options['extract_chapters']` flag
- Consider caching chapters if same video is processed multiple times

---

## 🎯 Alternative: Using AI Manager for Chapters

If Document Intelligence is not preferred, chapters can also be extracted using **AI Manager**:

```php
$chaptersResult = $aiProcessingModule->processText(
    "Please extract chapters from this transcript: " . $articleText,
    'extract_chapters',  // Custom task
    [
        'model' => 'deepseek-chat',
        'format' => 'json'
    ]
);
```

However, **Document Intelligence is preferred** because:
1. ✅ Better semantic understanding of document structure
2. ✅ RAG-powered (uses context from transcript)
3. ✅ Can reference specific parts of the transcript
4. ✅ More reliable for structured data extraction

---

## 📝 Summary

**To add chapters to YouTube summarization:**

1. **After transcription**: Ingest transcript text into Document Intelligence
2. **Poll ingestion**: Wait for ingestion job to complete
3. **Extract chapters**: Use Document Intelligence `answer()` method with a specific query
4. **Parse response**: Extract JSON array from the answer
5. **Add to response**: Include `chapters` array in the final response

**Files to modify:**
- `app/Services/UniversalJobService.php` - Method: `processYouTubeVideoSummarizationWithStages()`

**Dependencies:**
- `DocumentIntelligenceModule` (already available)
- `ingestText()` method (already implemented)
- `answer()` method (already implemented)
- `pollJobCompletion()` method (already implemented)

**No new code needed** - just use existing Document Intelligence capabilities! 🎉

---

**Last Updated**: November 17, 2025




