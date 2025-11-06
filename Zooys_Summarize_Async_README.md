# Zooys Summarize Async - Postman Collection

Complete API collection for testing all async summarization endpoints.

## Files

1. **Zooys_Summarize_Async.postman_collection.json** - Main collection with all endpoints
2. **Zooys_Summarize_Async.postman_environment.json** - Local development environment
3. **Zooys_Summarize_Async.postman_environment.production.json** - Production environment

## Endpoints Included

### 1. YouTube Video Summarization
- **Endpoint:** `POST /api/summarize/async/youtube`
- **Flow:** YouTube URL → TranscriberModule → AI Manager → Summary
- **Body:**
  ```json
  {
    "url": "https://www.youtube.com/watch?v=...",
    "options": {
      "language": "en",
      "format": "detailed",
      "model": "deepseek-chat"
    }
  }
  ```

### 2. Text Summarization
- **Endpoint:** `POST /api/summarize/async/text`
- **Flow:** Text → AI Manager → Summary
- **Body:**
  ```json
  {
    "text": "Your text content here...",
    "options": {
      "language": "en",
      "format": "detailed",
      "model": "deepseek-chat",
      "focus": "summary"
    }
  }
  ```

### 3. File Summarization (PDF/Doc/Image)
- **Endpoint:** `POST /api/summarize/async/file`
- **Flow:** File ID → Check if ingested → Ingest (if needed) → Poll → Document Intelligence → Summary
- **Body:**
  ```json
  {
    "file_id": "1",
    "options": {
      "language": "en",
      "format": "detailed",
      "focus": "summary",
      "ocr": "auto",
      "lang": "eng",
      "llm_model": "llama3",
      "max_tokens": 512,
      "top_k": 3,
      "temperature": 0.7,
      "force_fallback": true
    }
  }
  ```
- **Note:** File summarization uses Document Intelligence service, which has its own LLM models (see Document Intelligence Options below). The service now supports `deepseek-chat` in addition to other models.
- **Important:** `force_fallback` now defaults to `true` for better reliability. The service supports `deepseek-chat` model and larger values for `max_tokens` and `top_k`.

### 4. Audio/Video File Summarization
- **Endpoint:** `POST /api/summarize/async/audiovideo`
- **Flow:** File ID → TranscriberModule → AI Manager → Summary
- **Body:**
  ```json
  {
    "file_id": "2",
    "options": {
      "language": "en",
      "format": "detailed",
      "focus": "summary",
      "model": "deepseek-chat"
    }
  }
  ```

### 5. Link/URL Summarization
- **Endpoint:** `POST /api/summarize/link`
- **Flow:** URL → WebScrapingService → AI Manager → Summary
- **Body:**
  ```json
  {
    "url": "https://example.com/article",
    "options": {
      "language": "en",
      "format": "detailed",
      "focus": "summary",
      "model": "deepseek-chat"
    }
  }
  ```

### 6. Status & Result Endpoints

Each summarize type has its own dedicated status and result endpoints:

#### YouTube Summarization
- **Status:** `GET /api/status/summarize/youtube?job_id={job_id}`
- **Result:** `GET /api/result/summarize/youtube?job_id={job_id}`

#### Text Summarization
- **Status:** `GET /api/status/summarize/text?job_id={job_id}`
- **Result:** `GET /api/result/summarize/text?job_id={job_id}`

#### File Summarization (PDF/Doc/Image)
- **Status:** `GET /api/status/summarize/file?job_id={job_id}`
- **Result:** `GET /api/result/summarize/file?job_id={job_id}`

#### Audio/Video File Summarization
- **Status:** `GET /api/status/summarize/audiovideo?job_id={job_id}`
- **Result:** `GET /api/result/summarize/audiovideo?job_id={job_id}`

#### Web/Link Summarization
- **Status:** `GET /api/status/summarize/web?job_id={job_id}`
- **Result:** `GET /api/result/summarize/web?job_id={job_id}`

#### Universal Endpoints (Alternative)
- **Status:** `GET /api/status?job_id={job_id}` (works for all job types)
- **Result:** `GET /api/result?job_id={job_id}` (works for all job types)

**Note:** Use specific endpoints when possible as they provide better validation and include `tool_type` and `input_type` in the response.

### 8. Get Available Models
- **Endpoint:** `GET /api/models`
- **Purpose:** List available AI models for summarization

## Setup Instructions

1. **Import Collection:**
   - Open Postman
   - Click Import → Select `Zooys_Summarize_Async.postman_collection.json`

2. **Import Environment:**
   - Click Import → Select environment file (local or production)
   - Select the imported environment from the dropdown

3. **Get Bearer Token:**
   - First, login to get a token: `POST /api/login`
   ```json
   {
     "email": "your@email.com",
     "password": "yourpassword"
   }
   ```
   - Response includes: `{ "user": {...}, "token": "1|abc123def456..." }`
   - Copy the entire token value (including the `|` separator)

4. **Set Bearer Token in Environment:**
   - Open the environment variables
   - Set `bearer_token` to the full token from login response
   - **Important:** Use the full token as returned (format: `{token_id}|{token_hash}`)
   - Example: `1|abc123def456ghi789jkl012mno345pqr678stu901vwx234yz`

5. **Set Base URL:**
   - Local: `http://localhost:8000`
   - Production: `https://api.zooys.com`

6. **Test Token (Optional):**
   - Use `GET /api/test-token-validation` to verify your token is valid
   - This endpoint shows detailed token validation information

## Usage Flow

1. **Start Summarization:**
   - Call any summarize endpoint (YouTube, Text, File, etc.)
   - Response includes `job_id`

2. **Poll Status:**
   - Use the specific status endpoint for your summarize type:
     - YouTube: `/api/status/summarize/youtube?job_id={job_id}`
     - Text: `/api/status/summarize/text?job_id={job_id}`
     - File: `/api/status/summarize/file?job_id={job_id}`
     - Web/Link: `/api/status/summarize/web?job_id={job_id}`
   - Or use universal endpoint: `/api/status?job_id={job_id}`
   - Poll every 2-5 seconds until status is `completed` or `failed`

3. **Get Result:**
   - When status is `completed`, use the specific result endpoint:
     - YouTube: `/api/result/summarize/youtube?job_id={job_id}`
     - Text: `/api/result/summarize/text?job_id={job_id}`
     - File: `/api/result/summarize/file?job_id={job_id}`
     - Web/Link: `/api/result/summarize/web?job_id={job_id}`
   - Or use universal endpoint: `/api/result?job_id={job_id}`
   - Response includes summary, key_points, metadata, etc.

## Response Examples

### Job Created Response
```json
{
  "success": true,
  "message": "Summarization job started",
  "job_id": "12345",
  "status": "pending",
  "poll_url": "http://localhost:8000/api/status?job_id=12345",
  "result_url": "http://localhost:8000/api/result?job_id=12345"
}
```

### Status Response

#### Processing Status
```json
{
  "job_id": "12345",
  "tool_type": "summarize",
  "input_type": "file",
  "status": "running",
  "progress": 70,
  "stage": "summarizing",
  "error": null,
  "created_at": "2025-11-05T20:26:56.861152Z",
  "updated_at": "2025-11-05T20:27:58.938456Z"
}
```

#### Failed Status (with Error Details)
```json
{
  "job_id": "6e4f3f05-f50e-4509-97fc-7bd4a26af978",
  "tool_type": "summarize",
  "input_type": "file",
  "status": "failed",
  "progress": 70,
  "stage": "failed",
  "error": "Document Intelligence LLM service is currently unavailable. Please try again later.",
  "created_at": "2025-11-05T20:26:56.861152Z",
  "updated_at": "2025-11-05T20:27:58.938456Z",
  "doc_id": "doc_c5fd576830",
  "conversation_id": "zooys.3bf61467d36fa9c63e56754d04f984b4",
  "error_details": {
    "error_type": "llm_service_unavailable",
    "error_source": "DocumentIntelligenceService",
    "error_message": "LLM service unavailable (fallback)",
    "hint": "The LLM service may be temporarily down. Your document has been ingested (doc_id: doc_c5fd576830) and you can try again later or use the chat endpoint.",
    "doc_id": "doc_c5fd576830",
    "conversation_id": "zooys.3bf61467d36fa9c63e56754d04f984b4"
  }
}
```

**Note:** Even when a file summarization job fails, you'll receive `doc_id` and `conversation_id` if the document was successfully ingested. You can use these for follow-up operations like document chat.

### Result Response (Success)

#### YouTube/Text/Web Summarization
```json
{
  "success": true,
  "job_id": "12345",
  "tool_type": "summarize",
  "input_type": "youtube",
  "data": {
    "summary": "Comprehensive summary text...",
    "key_points": ["Point 1", "Point 2", "Point 3"],
    "metadata": {
      "model_used": "deepseek-chat",
      "tokens_used": 1500
    }
  },
  "metadata": {
    "file_count": 1,
    "confidence_score": 0.9,
    "source_type": "youtube",
    "processing_stages": ["analyzing_content", "transcribing", "ai_processing", "finalizing"]
  }
}
```

#### File Summarization (PDF/Doc/Image)
```json
{
  "success": true,
  "job_id": "12345",
  "tool_type": "summarize",
  "input_type": "file",
  "data": {
    "summary": "Comprehensive document summary...",
    "sources": [
      {
        "doc_id": "doc_c5fd576830",
        "chunk_id": "c_000007",
        "text": "Relevant text excerpt...",
        "score": 0.015,
        "meta": {
          "page": 4,
          "section": null
        }
      }
    ],
    "metadata": {
      "doc_id": "doc_c5fd576830",
      "conversation_id": "zooys.3bf61467d36fa9c63e56754d04f984b4",
      "file_type": "pdf",
      "sources_count": 3
    }
  },
  "metadata": {
    "file_count": 1,
    "confidence_score": 0.9,
    "source_type": "pdf",
    "doc_id": "doc_c5fd576830",
    "conversation_id": "zooys.3bf61467d36fa9c63e56754d04f984b4",
    "processing_stages": ["analyzing_content", "processing_file", "checking_ingestion", "ingesting", "polling_ingestion", "summarizing", "finalizing"]
  }
}
```

**Key Fields for File Summarization:**
- `doc_id`: Document ID from Document Intelligence (for follow-up operations)
- `conversation_id`: Conversation ID for multi-turn document chat (use with `/api/document/chat`)

## Options Reference

### Common Options (AI Manager)
- `language`: Language code (default: "en")
- `format`: Output format - "detailed", "brief", "bullet" (default: "detailed")
- `model`: AI model to use (default: "deepseek-chat")
  - Available: `deepseek-chat`, `ollama:llama3`, `ollama:mistral`, `gpt-4o`, `gpt-3.5-turbo`

### Document Intelligence Options (Files only)

**Important:** Document Intelligence uses its own LLM service, separate from AI Manager. The service now supports `deepseek-chat` model in addition to other models like `llama3`, `mistral:latest`, and `gpt-4`.

- `ocr`: OCR mode - "off", "auto", "force" (default: "auto")
- `lang`: Language code for OCR (default: "eng")
- `llm_model`: LLM model for Document Intelligence (default: "llama3")
  - Available: `llama3`, `deepseek-chat`, `mistral:latest`, `gpt-4`
  - **Note:** `deepseek-chat` is now supported for Document Intelligence
  - **Current default:** `llama3`
- `max_tokens`: Maximum tokens in response (default: 512)
  - Can be set to any value (e.g., 600, 450, etc.)
  - No longer capped - service supports larger values
- `top_k`: Number of context chunks (default: 3)
  - Can be set to any value (e.g., 4, 5, etc.)
  - No longer capped - service supports larger values
- `temperature`: LLM temperature (default: 0.7)
  - Controls randomness in responses (0.0 = deterministic, 1.0 = more creative)
- `force_fallback`: Skip local LLM, use remote immediately (default: **true**)
  - **Important:** Now defaults to `true` for better reliability
  - Set to `false` to try local LLM first, then fallback to remote

## Error Handling

All endpoints include comprehensive error handling with:
- `error`: Error message
- `error_details`: Structured error information
  - `error_type`: Type of error (transcription_error, ingestion_error, llm_service_unavailable, etc.)
  - `error_source`: Source component where error occurred
  - `context`: Additional context about the error
  - `hint`: Helpful troubleshooting hints

### File Summarization Error Responses

When file summarization fails, the response includes `doc_id` and `conversation_id` (if the document was successfully ingested), allowing you to:

1. **Use Document Chat:** Even if summarization fails, you can use the `/api/document/chat` endpoint with the `conversation_id`:
   ```json
   POST /api/document/chat
   {
     "query": "What are the main topics in this document?",
     "doc_ids": ["doc_c5fd576830"],
     "conversation_id": "zooys.3bf61467d36fa9c63e56754d04f984b4"
   }
   ```

2. **Retry Later:** The document is already ingested, so you can retry summarization when the LLM service is available

3. **Use doc_id Directly:** Access the document via Document Intelligence APIs using the `doc_id`

## Troubleshooting

### "Token not found" Error

If you get `{"error": "Token not found"}`, check:

1. **Token Format:**
   - Token must be in format: `{token_id}|{token_hash}`
   - Get a fresh token from `/api/login` endpoint
   - Copy the entire token value (don't modify it)

2. **Token Expiration:**
   - Tokens may expire or be revoked
   - Logout and login again to get a new token

3. **Authorization Header:**
   - Make sure header is: `Authorization: Bearer {token}`
   - No quotes around the token
   - No extra spaces

4. **Test Your Token:**
   - Use `GET /api/test-token-validation` to verify token validity
   - This shows detailed information about your token

5. **Common Issues:**
   - Using token without `Bearer` prefix → Add `Bearer ` before token
   - Token has extra spaces → Remove spaces
   - Token expired → Login again
   - Token from wrong user → Use correct user's token

### Example: Getting and Using Token

```bash
# 1. Login
POST http://localhost:8000/api/login
{
  "email": "user@example.com",
  "password": "password123"
}

# Response:
{
  "user": {...},
  "token": "1|abc123def456ghi789jkl012mno345pqr678stu901vwx234yz"
}

# 2. Use token in Authorization header
Authorization: Bearer 1|abc123def456ghi789jkl012mno345pqr678stu901vwx234yz

# 3. Test token (optional)
GET http://localhost:8000/api/test-token-validation
Authorization: Bearer 1|abc123def456ghi789jkl012mno345pqr678stu901vwx234yz
```

## Document Chat Integration

After a successful file summarization (or even if it fails but the document was ingested), you can use the `conversation_id` for follow-up chat conversations:

```json
POST /api/document/chat
{
  "query": "Can you explain section 3 in more detail?",
  "doc_ids": ["doc_c5fd576830"],
  "conversation_id": "zooys.3bf61467d36fa9c63e56754d04f984b4",
  "llm_model": "llama3",
  "max_tokens": 512,
  "top_k": 3,
  "temperature": 0.7,
  "force_fallback": true
}
```

The `conversation_id` maintains conversation context across multiple chat turns, allowing for natural multi-turn document Q&A.

## Notes

- All async endpoints require Bearer token authentication
- File endpoints require file to be uploaded first via `/api/files/upload`
- YouTube processing can take 5-10 minutes
- Document Intelligence ingestion can take 2-5 minutes
- Polling interval recommended: 2-5 seconds
- Job results are stored and can be retrieved multiple times
- Tokens can be revoked by logging out or deleting tokens
- **File summarization** uses Document Intelligence service (models: llama3, deepseek-chat, mistral:latest, gpt-4)
  - **Default model:** `llama3` (deepseek-chat is now supported)
  - **Default parameters:** `max_tokens: 512`, `top_k: 3`, `temperature: 0.7`, `force_fallback: true`
  - `force_fallback` now defaults to `true` for better reliability
- **YouTube/Text/Web summarization** uses AI Manager service (models: deepseek-chat, ollama:llama3, gpt-4o, etc.)
- `conversation_id` is automatically created for file summarization jobs and can be used for document chat
- Even if file summarization fails, `doc_id` and `conversation_id` are provided if document ingestion succeeded
- **Force fallback:** `force_fallback` now defaults to `true` for all Document Intelligence operations, ensuring better reliability by using remote LLM services directly.

