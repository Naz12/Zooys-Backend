# Content Writer API Documentation

## Overview

The Content Writer API provides endpoints for AI-powered content generation and rewriting. The API uses an asynchronous job-based system where clients create jobs, poll for status, and retrieve results when complete.

**Base URL:** `/api/content`

**Authentication:** All endpoints require Bearer token authentication (client users only, not admin).

---

## Endpoints

### 1. Write Content

Generate new content based on a prompt.

**Endpoint:** `POST /api/content/write`

**Authentication:** Required (Bearer token)

**Request Body:**
```json
{
  "prompt": "Write a blog post about the benefits of freelancing",
  "mode": "professional"
}
```

**Parameters:**
- `prompt` (required, string, max 5000 chars): The writing prompt or topic
- `mode` (optional, string): Writing mode - `creative`, `professional`, or `academic`. Default: `professional`

**Note:** Content is generated up to 500 words maximum (AI Manager limitation).

**Response (201 Created):**
```json
{
  "success": true,
  "job_id": "95aef618-59d5-4102-b7c0-de84f80f67f6",
  "status": "pending",
  "message": "Content writing job created successfully",
  "poll_url": "http://localhost:8000/api/content/status?job_id=95aef618-59d5-4102-b7c0-de84f80f67f6",
  "result_url": "http://localhost:8000/api/content/result?job_id=95aef618-59d5-4102-b7c0-de84f80f67f6"
}
```

**Error Responses:**
- `422 Validation Error`: Invalid request parameters
- `500 Server Error`: Job creation failed

---

### 2. Rewrite Content

Rewrite existing content based on new instructions.

**Endpoint:** `POST /api/content/rewrite`

**Authentication:** Required (Bearer token)

**Request Body:**
```json
{
  "previous_content": "Full previous blog content here...",
  "prompt": "Make it more engaging and add specific examples",
  "mode": "creative"
}
```

**Parameters:**
- `previous_content` (required, string, max 50000 chars): The original content to rewrite
- `prompt` (required, string, max 5000 chars): Instructions for rewriting
- `mode` (optional, string): Writing mode - `creative`, `professional`, or `academic`. Default: `professional`

**Response (201 Created):**
```json
{
  "success": true,
  "job_id": "664287b4-d7ec-448b-8635-739649548a61",
  "status": "pending",
  "message": "Content rewriting job created successfully",
  "poll_url": "http://localhost:8000/api/content/status?job_id=664287b4-d7ec-448b-8635-739649548a61",
  "result_url": "http://localhost:8000/api/content/result?job_id=664287b4-d7ec-448b-8635-739649548a61"
}
```

**Error Responses:**
- `422 Validation Error`: Invalid request parameters
- `500 Server Error`: Job creation failed

---

### 3. Get Job Status

Poll the status of a content writing or rewriting job.

**Endpoint:** `GET /api/content/status`

**Authentication:** Required (Bearer token)

**Query Parameters:**
- `job_id` (required, string): The job ID returned from write/rewrite endpoints

**Response (200 OK):**
```json
{
  "success": true,
  "job_id": "95aef618-59d5-4102-b7c0-de84f80f67f6",
  "tool_type": "content_write",
  "status": "running",
  "progress": 65,
  "stage": "generating_content",
  "stage_message": "Generating your content...",
  "stage_description": "AI is creating your content based on your prompt",
  "error": null,
  "created_at": "2025-11-19T10:06:49.603460Z",
  "updated_at": "2025-11-19T10:07:15.938456Z",
  "logs": []
}
```

**Status Values:**
- `pending`: Job created, waiting to start
- `running`: Job is being processed
- `completed`: Job finished successfully
- `failed`: Job failed with an error

**Stage Values:**
- `initializing`: Setting up the job
- `validating_input`: Validating the input
- `generating_content`: AI is generating content
- `refining_content`: Polishing the content
- `finalizing`: Completing the job
- `completed`: Job finished
- `failed`: Job failed

**Error Responses:**
- `400 Bad Request`: Missing job_id parameter
- `404 Not Found`: Job not found
- `403 Forbidden`: Job belongs to another user

---

### 4. Get Job Result

Retrieve the completed content from a finished job.

**Endpoint:** `GET /api/content/result`

**Authentication:** Required (Bearer token)

**Query Parameters:**
- `job_id` (required, string): The job ID returned from write/rewrite endpoints

**Response (200 OK):**
```json
{
  "success": true,
  "job_id": "95aef618-59d5-4102-b7c0-de84f80f67f6",
  "tool_type": "content_write",
  "data": {
    "content": "Generated blog content here...",
    "word_count": 850,
    "character_count": 4500,
    "mode": "professional",
    "metadata": {
      "model_used": "deepseek-chat",
      "model_display": "AI Model",
      "tokens_used": 1250,
      "processing_time": 12.5,
      "is_rewrite": false
    }
  },
  "metadata": {
    "processing_started_at": "2025-11-19T10:06:50.000000Z",
    "processing_completed_at": "2025-11-19T10:07:02.500000Z",
    "total_processing_time": 12,
    "tool_type": "content_write",
    "mode": "professional",
    "model_used": "deepseek-chat"
  }
}
```

**Response Fields:**
- `content`: The generated or rewritten content
- `word_count`: Number of words in the content
- `character_count`: Number of characters in the content
- `mode`: The writing mode used
- `metadata.is_rewrite`: `true` for rewrite jobs, `false` for write jobs
- `metadata.model_used`: AI model used (always `deepseek-chat`)
- `metadata.tokens_used`: Number of tokens consumed
- `metadata.processing_time`: Time taken in seconds

**Error Responses:**
- `400 Bad Request`: Missing job_id parameter
- `404 Not Found`: Job not found
- `403 Forbidden`: Job belongs to another user
- `202 Accepted`: Job not completed yet (status will be in response)

---

## Usage Flow

### Write Flow

1. **Create Write Job:**
   ```bash
   POST /api/content/write
   {
     "prompt": "Write a blog about freelancing",
     "mode": "professional",
   }
   ```
   Response includes `job_id`

2. **Poll Status:**
   ```bash
   GET /api/content/status?job_id={job_id}
   ```
   Poll every 2-5 seconds until `status` is `completed` or `failed`

3. **Get Result:**
   ```bash
   GET /api/content/result?job_id={job_id}
   ```
   Retrieve the generated content when status is `completed`

### Rewrite Flow

1. **Create Rewrite Job:**
   ```bash
   POST /api/content/rewrite
   {
     "previous_content": "Original content...",
     "prompt": "Make it more engaging",
     "mode": "creative"
   }
   ```
   Response includes `job_id`

2. **Poll Status:** (Same as write flow)

3. **Get Result:** (Same as write flow)

---

## Writing Modes

### Creative Mode
- Engaging, vivid descriptions
- Storytelling elements
- Varied sentence structures
- Captivating language

### Professional Mode (Default)
- Clear, concise business language
- Professional tone
- Logical structure
- Suitable for business contexts

### Academic Mode
- Formal, scholarly language
- Structured with introduction, body, conclusion
- Evidence-based reasoning
- Objective tone

---

## Content Length

**Maximum:** 500 words (AI Manager limitation)

All content is generated up to 500 words maximum, regardless of the writing mode selected.

---

## Error Handling

### Common Errors

**Validation Error (422):**
```json
{
  "success": false,
  "error": "Validation failed",
  "details": {
    "prompt": ["The prompt field is required."],
    "mode": ["The selected mode is invalid."]
  }
}
```

**Job Not Found (404):**
```json
{
  "success": false,
  "error": "Job not found"
}
```

**Job Failed:**
```json
{
  "success": true,
  "job_id": "...",
  "status": "failed",
  "error": "Content generation failed: AI model unavailable",
  "stage": "failed"
}
```

**Unauthorized (403):**
```json
{
  "success": false,
  "error": "Unauthorized"
}
```

---

## Rate Limiting

Content generation jobs are processed asynchronously. There is no explicit rate limiting, but:
- Jobs are processed in the order they are created
- Processing time depends on AI model availability
- Typical processing time: 10-30 seconds for content generation

---

## Notes

- **Model Selection:** The API always uses the default AI model (`deepseek-chat`). Clients cannot specify a different model.
- **Authentication:** All endpoints require a valid Bearer token from client user login (`/api/login`).
- **Job Persistence:** Jobs are stored in the database and cached for 2 hours.
- **Polling:** Recommended polling interval is 2-5 seconds. Maximum timeout is typically 5 minutes.
- **Content Limits:** 
  - Prompt: Maximum 5,000 characters
  - Previous content (for rewrite): Maximum 50,000 characters
  - Generated content: Maximum 500 words (AI Manager limitation)

---

## Example: Complete Write Flow

```bash
# 1. Login to get token
POST /api/login
{
  "email": "user@example.com",
  "password": "password"
}
# Response: { "token": "1|abc123...", "user": {...} }

# 2. Create write job
POST /api/content/write
Authorization: Bearer 1|abc123...
{
  "prompt": "Write a professional blog post about remote work benefits",
  "mode": "professional",
  "length": "medium"
}
# Response: { "job_id": "uuid-here", "status": "pending", ... }

# 3. Poll status (repeat every 2-5 seconds)
GET /api/content/status?job_id=uuid-here
Authorization: Bearer 1|abc123...
# Response: { "status": "running", "progress": 65, ... }

# 4. Get result when status is "completed"
GET /api/content/result?job_id=uuid-here
Authorization: Bearer 1|abc123...
# Response: { "data": { "content": "...", "word_count": 850, ... } }
```

---

## Example: Complete Rewrite Flow

```bash
# 1. Create rewrite job
POST /api/content/rewrite
Authorization: Bearer 1|abc123...
{
  "previous_content": "Original blog content here...",
  "prompt": "Make it more engaging with specific examples",
  "mode": "creative"
}
# Response: { "job_id": "uuid-here", ... }

# 2. Poll status (same as write flow)

# 3. Get result (same as write flow)
# Note: metadata.is_rewrite will be true
```

---

## Integration Notes

- The API follows the same async job pattern as other tools (summarize, presentations, flashcards)
- Jobs are processed via UniversalJobService
- Status polling is required - there is no webhook support
- All content is generated using the AI Manager microservice
- The default model (deepseek-chat) is used automatically

---

## Support

For issues or questions:
- Check job status for error messages
- Review logs in the status response
- Ensure AI Manager service is available
- Verify authentication token is valid

