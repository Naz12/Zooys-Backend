# Summarize & Document Chat API Documentation

Complete API documentation for all summarization endpoints and document chat functionality.

---

## 📋 Table of Contents

1. [Authentication](#authentication)
2. [Summarize Endpoints](#summarize-endpoints)
   - [YouTube Video Summarization](#1-youtube-video-summarization)
   - [Text Summarization](#2-text-summarization)
   - [File Summarization (PDF/Doc/Image)](#3-file-summarization-pdfdocimage)
   - [Audio/Video File Summarization](#4-audiovideo-file-summarization)
   - [Web Link Summarization](#5-web-link-summarization)
3. [Status & Result Endpoints](#status--result-endpoints)
4. [Document Chat](#document-chat)
5. [Response Formats](#response-formats)
6. [Error Handling](#error-handling)
7. [Examples](#examples)

---

## 🔐 Authentication

All endpoints require Bearer token authentication.

**Header:**
```
Authorization: Bearer {token_id}|{token_hash}
```

**Example:**
```
Authorization: Bearer 1|abc123def456...
```

---

## 📝 Summarize Endpoints

All summarization endpoints are **asynchronous**. They return a `job_id` that you must use to poll for status and retrieve results.

### 1. YouTube Video Summarization

**Endpoint:** `POST /api/summarize/async/youtube`

**Description:** Summarize a YouTube video by providing its URL. The system will transcribe the video, extract content, and generate a summary with chapters.

**Request Body:**
```json
{
  "url": "https://www.youtube.com/watch?v=VIDEO_ID",
  "options": {
    "language": "en",
    "format": "bundle",
    "llm_model": "deepseek-chat"
  }
}
```

**Request Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `url` | string | Yes | - | YouTube video URL (youtube.com or youtu.be) |
| `options` | object | No | {} | Optional configuration |
| `options.language` | string | No | "en" | Language code (en, es, fr, etc.) |
| `options.format` | string | No | "bundle" | Format: `plain`, `json`, `srt`, `article`, `bundle` |
| `options.llm_model` | string | No | "deepseek-chat" | LLM model for summarization |

**Response:**
```json
{
  "success": true,
  "job_id": "08110cd3-9a1b-44b0-92c5-30d4f1b6209f",
  "status": "pending",
  "message": "YouTube summarization job started"
}
```

**Status Endpoint:** `GET /api/status/summarize/youtube?job_id={job_id}`

**Result Endpoint:** `GET /api/result/summarize/youtube?job_id={job_id}`

---

### 2. Text Summarization

**Endpoint:** `POST /api/summarize/async/text`

**Description:** Summarize plain text content. Minimum 10 characters required.

**Request Body:**
```json
{
  "text": "Your long text content here. This can be an article, document excerpt, or any text you want summarized...",
  "options": {
    "language": "en",
    "format": "detailed",
    "llm_model": "deepseek-chat"
  }
}
```

**Request Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `text` | string | Yes | - | Text content to summarize (min 10 chars) |
| `options` | object | No | {} | Optional configuration |
| `options.language` | string | No | "en" | Language code |
| `options.format` | string | No | "detailed" | Format preference |
| `options.llm_model` | string | No | "deepseek-chat" | LLM model for summarization |

**Response:**
```json
{
  "success": true,
  "job_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
  "status": "pending",
  "message": "Text summarization job started"
}
```

**Status Endpoint:** `GET /api/status/summarize/text?job_id={job_id}`

**Result Endpoint:** `GET /api/result/summarize/text?job_id={job_id}`

---

### 3. File Summarization (PDF/Doc/Image)

**Endpoint:** `POST /api/summarize/async/file`

**Description:** Summarize uploaded files (PDF, DOC, DOCX, TXT, images). The file must be uploaded first using `/api/files/upload` to get a `file_id`.

**Request Body:**
```json
{
  "file_id": "uuid-string",
  "options": {
    "language": "en",
    "format": "bundle",
    "llm_model": "deepseek-chat",
    "ocr": "auto",
    "lang": "eng",
    "max_tokens": 2000,
    "top_k": 10,
    "temperature": 0.7,
    "force_fallback": true
  }
}
```

**Request Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `file_id` | string | Yes | - | File ID from upload endpoint |
| `options` | object | No | {} | Optional configuration |
| `options.language` | string | No | "en" | Language code |
| `options.format` | string | No | "bundle" | Format preference |
| `options.llm_model` | string | No | "deepseek-chat" | LLM model (deepseek-chat, llama3, etc.) |
| `options.ocr` | string | No | "auto" | OCR mode: `auto`, `force`, `skip` |
| `options.lang` | string | No | "eng" | Document language for OCR |
| `options.max_tokens` | integer | No | 2000 | Maximum response tokens |
| `options.top_k` | integer | No | 10 | Number of context chunks |
| `options.temperature` | float | No | 0.7 | LLM temperature (0.0-1.0) |
| `options.force_fallback` | boolean | No | true | Use remote LLM (always true) |

**Response:**
```json
{
  "success": true,
  "job_id": "b2c3d4e5-f6a7-8901-bcde-f12345678901",
  "status": "pending",
  "message": "File summarization job started"
}
```

**Status Endpoint:** `GET /api/status/summarize/file?job_id={job_id}`

**Result Endpoint:** `GET /api/result/summarize/file?job_id={job_id}`

**Note:** File summarization uses Document Intelligence service. If the service is unavailable, the system automatically falls back to AI Manager.

---

### 4. Audio/Video File Summarization

**Endpoint:** `POST /api/summarize/async/audiovideo`

**Description:** Summarize uploaded audio or video files. The file must be uploaded first using `/api/files/upload` to get a `file_id`.

**Request Body:**
```json
{
  "file_id": "uuid-string",
  "options": {
    "language": "en",
    "format": "bundle",
    "llm_model": "deepseek-chat"
  }
}
```

**Request Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `file_id` | string | Yes | - | File ID from upload endpoint (must be audio/video) |
| `options` | object | No | {} | Optional configuration |
| `options.language` | string | No | "en" | Language code |
| `options.format` | string | No | "bundle" | Format: `plain`, `json`, `srt`, `article`, `bundle` |
| `options.llm_model` | string | No | "deepseek-chat" | LLM model for summarization |

**Response:**
```json
{
  "success": true,
  "job_id": "c3d4e5f6-a7b8-9012-cdef-123456789012",
  "status": "pending",
  "message": "Audio/video summarization job started"
}
```

**Status Endpoint:** `GET /api/status/summarize/audiovideo?job_id={job_id}`

**Result Endpoint:** `GET /api/result/summarize/audiovideo?job_id={job_id}`

**Note:** Audio/video files are first transcribed, then summarized. If Document Intelligence is unavailable, the system automatically falls back to AI Manager.

---

### 5. Web Link Summarization

**Endpoint:** `POST /api/summarize/link`

**Description:** Summarize content from any web URL. The system scrapes the webpage and generates a summary.

**Request Body:**
```json
{
  "url": "https://example.com/article",
  "options": {
    "language": "en",
    "format": "bundle",
    "llm_model": "deepseek-chat"
  }
}
```

**Request Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `url` | string | Yes | - | Webpage URL to summarize |
| `options` | object | No | {} | Optional configuration |
| `options.language` | string | No | "en" | Language code |
| `options.format` | string | No | "bundle" | Format preference |
| `options.llm_model` | string | No | "deepseek-chat" | LLM model for summarization |

**Response:**
```json
{
  "success": true,
  "job_id": "d4e5f6a7-b8c9-0123-def0-234567890123",
  "status": "pending",
  "message": "Link summarization job started"
}
```

**Status Endpoint:** `GET /api/status/summarize/web?job_id={job_id}`

**Result Endpoint:** `GET /api/result/summarize/web?job_id={job_id}`

---

## 📊 Status & Result Endpoints

### Status Endpoints

Get the current status of a summarization job.

**Format:** `GET /api/status/summarize/{input_type}?job_id={job_id}`

**Input Types:**
- `youtube` - YouTube video summarization
- `text` - Text summarization
- `file` - File summarization
- `audiovideo` - Audio/video file summarization
- `web` - Web link summarization

**Example:**
```http
GET /api/status/summarize/youtube?job_id=08110cd3-9a1b-44b0-92c5-30d4f1b6209f
Authorization: Bearer 1|abc123def456...
```

**Response:**
```json
{
  "job_id": "08110cd3-9a1b-44b0-92c5-30d4f1b6209f",
  "tool_type": "summarize",
  "input_type": "youtube",
  "status": "processing",
  "progress": 50,
  "stage": "extracting_summary",
  "error": null,
  "created_at": "2025-11-18T08:52:33.882917Z",
  "updated_at": "2025-11-18T08:52:38.529022Z"
}
```

**Status Values:**
- `pending` - Job created, waiting to start
- `processing` - Job is being processed
- `completed` - Job completed successfully
- `failed` - Job failed with error

**Stages:**
- `transcribing` - Transcribing audio/video (YouTube, audio/video files)
- `ingesting_text` - Ingesting text into Document Intelligence
- `polling_ingestion` - Waiting for ingestion to complete
- `extracting_summary` - Generating summary
- `extracting_chapters` - Extracting chapters
- `finalizing` - Finalizing response
- `fallback_ai_manager` - Using AI Manager fallback
- `failed` - Job failed

---

### Result Endpoints

Get the completed summarization result.

**Format:** `GET /api/result/summarize/{input_type}?job_id={job_id}`

**Example:**
```http
GET /api/result/summarize/youtube?job_id=08110cd3-9a1b-44b0-92c5-30d4f1b6209f
Authorization: Bearer 1|abc123def456...
```

**Response (Success):**
```json
{
  "success": true,
  "job_id": "08110cd3-9a1b-44b0-92c5-30d4f1b6209f",
  "tool_type": "summarize",
  "input_type": "youtube",
  "data": {
    "summary": "This video discusses...",
    "key_points": [
      "Key point 1",
      "Key point 2",
      "Key point 3"
    ],
    "chapters": [
      {
        "title": "Introduction",
        "timestamp": "00:00:00",
        "description": "Introduction to the topic"
      },
      {
        "title": "Main Discussion",
        "timestamp": "00:05:30",
        "description": "Main content discussion"
      }
    ],
    "bundle": {
      "article_text": "Full transcript text...",
      "json_items": [...],
      "transcript_json": [...]
    },
    "doc_id": "doc_abc123",
    "conversation_id": "conv_xyz789",
    "confidence_score": 0.9,
    "model_used": "deepseek-chat",
    "sources": [],
    "metadata": {
      "source_type": "youtube",
      "video_id": "srbTzkSYfXE",
      "language": "en",
      "chapters_extracted": true,
      "chapters_count": 2,
      "chat_enabled": true,
      "sources_count": 0
    }
  }
}
```

**Response (Failed):**
```json
{
  "job_id": "08110cd3-9a1b-44b0-92c5-30d4f1b6209f",
  "tool_type": "summarize",
  "input_type": "youtube",
  "status": "failed",
  "progress": 50,
  "stage": "failed",
  "error": "Error message here",
  "created_at": "2025-11-18T08:52:33.882917Z",
  "updated_at": "2025-11-18T08:52:38.529022Z"
}
```

**Important Notes:**
- `doc_id` and `conversation_id` are only available when Document Intelligence is used
- If AI Manager fallback is used, `doc_id` and `conversation_id` will be `null`
- When `doc_id` is `null`, document chat is not available
- Check `metadata.fallback_used` to see if fallback was used

---

## 💬 Document Chat

**Endpoint:** `POST /api/document/chat`

**Description:** Chat with a summarized document using its `doc_id` and `conversation_id`. This enables multi-turn conversations with document context.

**Prerequisites:**
- The document must have been summarized successfully
- The summary result must include a `doc_id` (not null)
- Document Intelligence service must be available (not using AI Manager fallback)

**Request Body:**
```json
{
  "doc_id": "doc_abc123",
  "query": "What are the key points discussed in this video?",
  "conversation_id": "conv_xyz789",
  "llm_model": "deepseek-chat",
  "max_tokens": 512,
  "top_k": 3
}
```

**Request Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `doc_id` | string | Yes | - | Document ID from summarization result |
| `query` | string | Yes | - | Your question/message (max 1000 chars) |
| `conversation_id` | string | No | auto | Conversation ID for context (auto-created if not provided) |
| `llm_model` | string | No | "deepseek-chat" | LLM model for chat |
| `max_tokens` | integer | No | 512 | Maximum response tokens (50-2000) |
| `top_k` | integer | No | 3 | Number of context chunks (1-10) |

**Response (Success):**
```json
{
  "success": true,
  "conversation_id": "conv_xyz789",
  "answer": "The key points discussed in this video include: 1) Introduction to the topic, 2) Main discussion points, 3) Conclusion and takeaways.",
  "sources": [
    {
      "doc_id": "doc_abc123",
      "page": 1,
      "score": 0.91
    }
  ],
  "doc_id": "doc_abc123"
}
```

**Response (Error):**
```json
{
  "success": false,
  "message": "Chat failed: Document Intelligence service unavailable"
}
```

**Multi-Turn Conversation Example:**

**First Message:**
```json
POST /api/document/chat
{
  "doc_id": "doc_abc123",
  "query": "What is this document about?"
}
```

**Response:**
```json
{
  "success": true,
  "conversation_id": "conv_xyz789",  // Save this!
  "answer": "This document is about...",
  "sources": [...],
  "doc_id": "doc_abc123"
}
```

**Follow-up Message (using conversation_id):**
```json
POST /api/document/chat
{
  "doc_id": "doc_abc123",
  "query": "Can you explain the second point in more detail?",
  "conversation_id": "conv_xyz789"  // Reuse for context
}
```

**Response:**
```json
{
  "success": true,
  "conversation_id": "conv_xyz789",
  "answer": "The second point refers to...",
  "sources": [...],
  "doc_id": "doc_abc123"
}
```

**Important Notes:**
- `conversation_id` is automatically created if not provided
- Reuse the same `conversation_id` for related questions to maintain context
- Start a new `conversation_id` for unrelated topics
- Document chat only works when `doc_id` is available (Document Intelligence was used)
- If AI Manager fallback was used, `doc_id` will be `null` and chat is not available

---

## 📦 Response Formats

### Summary Response Structure

All successful summarization results follow this structure:

```json
{
  "success": true,
  "job_id": "...",
  "tool_type": "summarize",
  "input_type": "youtube|text|file|audiovideo|web",
  "data": {
    "summary": "Main summary text...",
    "key_points": ["Point 1", "Point 2", ...],
    "chapters": [
      {
        "title": "Chapter Title",
        "timestamp": "00:00:00" or null,
        "description": "Chapter description"
      }
    ],
    "bundle": {
      "article_text": "Full content...",
      "json_items": [...],
      "transcript_json": [...]
    },
    "doc_id": "doc_abc123" or null,
    "conversation_id": "conv_xyz789" or null,
    "confidence_score": 0.9,
    "model_used": "deepseek-chat",
    "sources": [],
    "metadata": {
      "source_type": "youtube|text|file|audiovideo|web",
      "chapters_extracted": true,
      "chapters_count": 2,
      "chat_enabled": true,
      "sources_count": 0,
      "fallback_used": false,
      "fallback_reason": null
    }
  }
}
```

### Key Fields Explained

- **`doc_id`**: Document ID for Document Intelligence. `null` if AI Manager fallback was used.
- **`conversation_id`**: Conversation ID for document chat. `null` if `doc_id` is `null`.
- **`chat_enabled`**: `true` if `doc_id` is available, `false` otherwise.
- **`fallback_used`**: `true` if AI Manager fallback was used, `false` if Document Intelligence was used.
- **`chapters`**: Array of chapter objects (may be empty if extraction failed).

---

## ⚠️ Error Handling

### Common Error Responses

**401 Unauthorized:**
```json
{
  "error": "Unauthenticated"
}
```

**400 Bad Request:**
```json
{
  "error": "job_id parameter is required"
}
```

**404 Not Found:**
```json
{
  "error": "Job not found"
}
```

**409 Conflict (Job Not Completed):**
```json
{
  "error": "Job not completed",
  "status": "processing"
}
```

**422 Validation Error:**
```json
{
  "error": "Invalid YouTube URL"
}
```

**500 Server Error:**
```json
{
  "error": "Internal server error",
  "message": "Detailed error message"
}
```

### Job Failure Response

When a job fails, the status endpoint will return:

```json
{
  "job_id": "...",
  "status": "failed",
  "progress": 50,
  "stage": "failed",
  "error": "Failed to ingest youtube transcript into Document Intelligence. The Document Intelligence service endpoint '/v1/ingest/text' was not found.",
  "created_at": "...",
  "updated_at": "..."
}
```

**Note:** If Document Intelligence fails, the system automatically falls back to AI Manager. Check the result to see if fallback was used.

---

## 📚 Examples

### Complete Workflow: YouTube Summarization + Chat

**Step 1: Start Summarization**
```http
POST /api/summarize/async/youtube
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "url": "https://www.youtube.com/watch?v=srbTzkSYfXE",
  "options": {
    "llm_model": "deepseek-chat"
  }
}
```

**Response:**
```json
{
  "success": true,
  "job_id": "08110cd3-9a1b-44b0-92c5-30d4f1b6209f",
  "status": "pending"
}
```

**Step 2: Poll Status**
```http
GET /api/status/summarize/youtube?job_id=08110cd3-9a1b-44b0-92c5-30d4f1b6209f
Authorization: Bearer 1|abc123def456...
```

**Step 3: Get Result**
```http
GET /api/result/summarize/youtube?job_id=08110cd3-9a1b-44b0-92c5-30d4f1b6209f
Authorization: Bearer 1|abc123def456...
```

**Response:**
```json
{
  "success": true,
  "data": {
    "summary": "...",
    "key_points": [...],
    "chapters": [...],
    "doc_id": "doc_abc123",
    "conversation_id": "conv_xyz789",
    ...
  }
}
```

**Step 4: Chat with Document**
```http
POST /api/document/chat
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "doc_id": "doc_abc123",
  "query": "What are the main topics covered?",
  "conversation_id": "conv_xyz789"
}
```

**Response:**
```json
{
  "success": true,
  "conversation_id": "conv_xyz789",
  "answer": "The main topics covered include...",
  "sources": [...],
  "doc_id": "doc_abc123"
}
```

---

### Text Summarization Example

```http
POST /api/summarize/async/text
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "text": "Artificial intelligence is transforming the way we work and live. From healthcare to education, AI applications are becoming increasingly prevalent...",
  "options": {
    "llm_model": "deepseek-chat"
  }
}
```

---

### File Summarization Example

```http
POST /api/summarize/async/file
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "file_id": "uuid-string-from-upload",
  "options": {
    "llm_model": "deepseek-chat",
    "max_tokens": 2000
  }
}
```

---

## 🔄 Fallback Mechanism

The system includes automatic fallback to AI Manager when Document Intelligence is unavailable:

1. **Primary**: Document Intelligence (provides `doc_id` and `conversation_id` for chat)
2. **Fallback**: AI Manager (no `doc_id`, no chat available)

**How to Check:**
- Look for `metadata.fallback_used: true` in the result
- Check if `doc_id` is `null` (indicates fallback was used)
- Check `metadata.fallback_reason` for details

**Impact:**
- When fallback is used, summaries and chapters are still generated
- Document chat is **not available** when `doc_id` is `null`
- All other features work normally

---

## 📝 Notes

1. **Default Model**: All endpoints default to `deepseek-chat` model
2. **Async Processing**: All summarization is asynchronous - always poll for status
3. **Document Chat**: Only available when `doc_id` is present (Document Intelligence was used)
4. **Conversation Context**: Reuse `conversation_id` for multi-turn conversations
5. **File Upload**: Files must be uploaded first using `/api/files/upload` to get a `file_id`
6. **Polling**: Poll status endpoint every 2-5 seconds until `status` is `completed` or `failed`

---

## 🔗 Related Endpoints

- **File Upload**: `POST /api/files/upload`
- **Universal Status**: `GET /api/status?job_id={job_id}`
- **Universal Result**: `GET /api/result?job_id={job_id}`
- **Document Chat History**: `GET /api/chat/document/{documentId}/history`

---

**Last Updated:** 2025-11-18

