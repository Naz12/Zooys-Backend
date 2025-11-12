# Flashcard Module - Complete API Documentation

**Version:** 1.1  
**Last Updated:** November 2025  
**Base URL:** `http://your-domain.com/api`  
**Status:** ✅ Production Ready  
**AI Manager Endpoint:** `POST /api/custom-prompt` (dynamically constructed requests)

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Endpoints](#endpoints)
4. [Input Types](#input-types)
5. [Request/Response Formats](#requestresponse-formats)
6. [Error Handling](#error-handling)
7. [Examples](#examples)
8. [Workflows](#workflows)
9. [Technical Details](#technical-details)

---

## 🎯 Overview

The Flashcard Module provides a complete API for generating flashcards from various content sources using AI. It supports text, files, URLs, and YouTube videos, with customizable difficulty levels and question styles.

### **Key Features**

- Generate flashcards from multiple input types (text, file, URL, YouTube)
- Async job processing with status tracking via **Universal Job Scheduler**
- Customizable flashcard count (1-40 cards)
- Multiple difficulty levels (beginner, intermediate, advanced)
- Various question styles (definition, application, analysis, comparison, mixed)
- Complete lifecycle management
- **Queue Worker Integration** for background processing

### **Architecture**

The Flashcard Module uses the **Universal Job Scheduler** and **Queue Worker** system:

1. **Job Creation:** Creates a universal job via `UniversalJobService`
2. **Queue Processing:** Jobs are queued and processed asynchronously by queue workers
3. **Stage Tracking:** Detailed progress tracking through multiple stages
4. **AI Integration:** Communicates with AI Manager Microservice for flashcard generation
5. **Content Extraction:** Supports text, files, URLs, and YouTube videos

### **Processing Mode**

- **All requests** → Asynchronous processing via **Queue Workers** (returns `job_id`, poll for results)
- Uses **Universal Job Scheduler** for job management and status tracking
- Jobs are processed in the background, allowing immediate HTTP response

---

## 🔐 Authentication

All endpoints require Bearer token authentication:

```http
Authorization: Bearer {your-token}
```

---

## 🌐 Endpoints

### **1. Generate Flashcards**

Generate flashcards from text, file, URL, or YouTube video.

**Endpoint:** `POST /api/flashcards/generate`

**Authentication:** Required

**Request Body (Text Input):**

```json
{
  "input": "Photosynthesis is the process by which plants convert light energy into chemical energy. It occurs in chloroplasts and requires sunlight, water, and carbon dioxide.",
  "input_type": "text",
  "count": 5,
  "difficulty": "intermediate",
  "style": "mixed"
}
```

**Request Body (File Input):**

```json
{
  "file_id": "uuid-string",
  "input_type": "file",
  "count": 10,
  "difficulty": "advanced",
  "style": "application"
}
```

**Request Body (URL Input):**

```json
{
  "input": "https://example.com/article",
  "input_type": "url",
  "count": 8,
  "difficulty": "intermediate",
  "style": "analysis"
}
```

**Request Body (YouTube Input):**

```json
{
  "input": "https://www.youtube.com/watch?v=video-id",
  "input_type": "youtube",
  "count": 6,
  "difficulty": "beginner",
  "style": "definition"
}
```

**Request Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `input` | string | Yes* | Content to generate flashcards from (required if `file_id` not provided) |
| `file_id` | string | Yes* | File ID for file-based generation (required if `input` not provided) |
| `input_type` | string | No | Input type: `text`, `url`, `youtube`, `file` (auto-detected if not specified) |
| `count` | integer | No | Number of flashcards to generate (1-40, default: 5) |
| `difficulty` | string | No | Difficulty level: `beginner`, `intermediate`, `advanced` (default: `intermediate`) |
| `style` | string | No | Question style: `definition`, `application`, `analysis`, `comparison`, `mixed` (default: `mixed`) |
| `model` | string | No | AI model to use (default: `deepseek-chat`) |

**Response:**

```json
{
  "success": true,
  "message": "Flashcard generation job started",
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "pending",
  "poll_url": "http://your-domain.com/api/status/flashcards/text?job_id=550e8400-e29b-41d4-a716-446655440000",
  "result_url": "http://your-domain.com/api/result/flashcards/text?job_id=550e8400-e29b-41d4-a716-446655440000",
  "data": {
    "job_id": "550e8400-e29b-41d4-a716-446655440000",
    "status": "pending",
    "flashcards": []
  }
}
```

**Example Request:**

```bash
curl -X POST http://your-domain.com/api/flashcards/generate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "input": "Photosynthesis converts light to energy.",
    "input_type": "text",
    "count": 5,
    "difficulty": "intermediate",
    "style": "mixed"
  }'
```

---

### **2. Get Job Status (Text)**

Get the current status of a flashcard generation job for text/URL/YouTube inputs.

**Endpoint:** `GET /api/status/flashcards/text?job_id={jobId}`

**Authentication:** Required

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `job_id` | string | Yes | Job ID returned from generate endpoint |

**Response:**

```json
{
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "tool_type": "flashcards",
  "input_type": "text",
  "status": "processing",
  "progress": 60.0,
  "stage": "generating_flashcards",
  "error": null,
  "created_at": "2024-01-01T12:00:00Z",
  "updated_at": "2024-01-01T12:01:00Z"
}
```

**Status Values:**

- `pending` - Job created, waiting to be processed
- `processing` - Job is currently being processed
- `completed` - Job completed successfully
- `failed` - Job failed with an error

**Stages:**

- `initializing` - Job created and queued for processing
- `analyzing_content` - Analyzing content for flashcard generation (10% progress)
- `validating_input` - Validating input (20-30% progress)
- `extracting_content` - Extracting content from source (40% progress)
- `validating_content` - Validating extracted content (50% progress)
- `generating_flashcards` - Generating flashcards using AI (60% progress)
- `saving_flashcards` - Saving flashcards to database (80% progress)
- `finalizing` - Finalizing job (90% progress)

**Example Request:**

```bash
curl -X GET "http://your-domain.com/api/status/flashcards/text?job_id=550e8400-e29b-41d4-a716-446655440000" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### **3. Get Job Result (Text)**

Get the result of a completed flashcard generation job for text/URL/YouTube inputs.

**Endpoint:** `GET /api/result/flashcards/text?job_id={jobId}`

**Authentication:** Required

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `job_id` | string | Yes | Job ID returned from generate endpoint |

**Response (Completed):**

```json
{
  "success": true,
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "tool_type": "flashcards",
  "input_type": "text",
  "data": {
    "flashcards": [
      {
        "front": "What is photosynthesis?",
        "back": "The process by which plants convert light energy into chemical energy.",
        "question": "What is photosynthesis?",
        "answer": "The process by which plants convert light energy into chemical energy."
      },
      {
        "front": "Where does photosynthesis occur?",
        "back": "In chloroplasts.",
        "question": "Where does photosynthesis occur?",
        "answer": "In chloroplasts."
      }
    ],
    "flashcard_set": {
      "id": 123,
      "title": "Photosynthesis converts light to energy.",
      "description": "Flashcards generated from text input",
      "total_cards": 5,
      "created_at": "2024-01-01T12:00:00Z"
    },
    "ai_result": {
      "id": 456,
      "title": "Photosynthesis converts light to energy.",
      "file_url": null,
      "created_at": "2024-01-01T12:00:00Z"
    }
  }
}
```

**Response (Not Completed - 202 Accepted):**

```json
{
  "error": "Job not completed",
  "status": "processing",
  "progress": 60.0
}
```

**Example Request:**

```bash
curl -X GET "http://your-domain.com/api/result/flashcards/text?job_id=550e8400-e29b-41d4-a716-446655440000" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### **4. Get Job Status (File)**

Get the current status of a flashcard generation job for file inputs.

**Endpoint:** `GET /api/status/flashcards/file?job_id={jobId}`

**Authentication:** Required

**Response:** Same format as text status endpoint

---

### **5. Get Job Result (File)**

Get the result of a completed flashcard generation job for file inputs.

**Endpoint:** `GET /api/result/flashcards/file?job_id={jobId}`

**Authentication:** Required

**Response:** Same format as text result endpoint

---

### **6. List Flashcard Sets**

Get list of user's flashcard sets.

**Endpoint:** `GET /api/flashcards`

**Authentication:** Required

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `page` | integer | No | Page number (default: 1) |
| `per_page` | integer | No | Items per page (default: 15) |
| `search` | string | No | Search term for title/description |

**Response:**

```json
{
  "flashcard_sets": [
    {
      "id": 123,
      "user_id": 21,
      "title": "Photosynthesis",
      "description": "Flashcards generated from text input",
      "input_type": "text",
      "input_content": "Photosynthesis converts light to energy.",
      "difficulty": "intermediate",
      "style": "mixed",
      "total_cards": 5,
      "source_metadata": {
        "source_type": "text",
        "word_count": 5,
        "character_count": 40
      },
      "is_public": false,
      "created_at": "2024-01-01T12:00:00Z",
      "updated_at": "2024-01-01T12:00:00Z",
      "flashcards": [
        {
          "id": 1,
          "flashcard_set_id": 123,
          "question": "What is photosynthesis?",
          "answer": "The process by which plants convert light energy into chemical energy.",
          "order_index": 0,
          "created_at": "2024-01-01T12:00:00Z",
          "updated_at": "2024-01-01T12:00:00Z"
        }
      ]
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "total": 45
  }
}
```

**Example Request:**

```bash
curl -X GET "http://your-domain.com/api/flashcards?page=1&per_page=15&search=photosynthesis" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### **7. Get Flashcard Set**

Get specific flashcard set with all its cards.

**Endpoint:** `GET /api/flashcards/{id}`

**Authentication:** Required

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Flashcard set ID |

**Response:**

```json
{
  "flashcard_set": {
    "id": 123,
    "user_id": 21,
    "title": "Photosynthesis",
    "description": "Flashcards generated from text input",
    "input_type": "text",
    "input_content": "Photosynthesis converts light to energy.",
    "difficulty": "intermediate",
    "style": "mixed",
    "total_cards": 5,
    "is_public": false,
    "created_at": "2024-01-01T12:00:00Z",
    "updated_at": "2024-01-01T12:00:00Z",
    "flashcards": [
      {
        "id": 1,
        "flashcard_set_id": 123,
        "question": "What is photosynthesis?",
        "answer": "The process by which plants convert light energy into chemical energy.",
        "order_index": 0,
        "created_at": "2024-01-01T12:00:00Z",
        "updated_at": "2024-01-01T12:00:00Z"
      }
    ]
  }
}
```

---

### **8. Update Flashcard Set**

Update a flashcard set's metadata.

**Endpoint:** `PUT /api/flashcards/{id}`

**Authentication:** Required

**Request Body:**

```json
{
  "title": "Updated Title",
  "description": "Updated description",
  "is_public": true
}
```

**Request Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `title` | string | No | Flashcard set title (max: 255 chars) |
| `description` | string | No | Flashcard set description (max: 1000 chars) |
| `is_public` | boolean | No | Make flashcard set public |

**Response:**

```json
{
  "message": "Flashcard set updated successfully",
  "flashcard_set": {
    "id": 123,
    "title": "Updated Title",
    "description": "Updated description",
    "is_public": true,
    "updated_at": "2024-01-01T12:05:00Z"
  }
}
```

---

### **9. Delete Flashcard Set**

Delete a flashcard set and all its cards.

**Endpoint:** `DELETE /api/flashcards/{id}`

**Authentication:** Required

**Response:**

```json
{
  "message": "Flashcard set deleted successfully"
}
```

---

### **10. Get Public Flashcard Sets**

Get list of public flashcard sets from all users.

**Endpoint:** `GET /api/flashcards/public`

**Authentication:** Required

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `page` | integer | No | Page number (default: 1) |
| `per_page` | integer | No | Items per page (default: 15) |
| `search` | string | No | Search term for title/description |

**Response:** Same format as list endpoint, but includes `user` information:

```json
{
  "flashcard_sets": [
    {
      "id": 123,
      "user_id": 21,
      "title": "Photosynthesis",
      "user": {
        "id": 21,
        "name": "John Doe"
      },
      "flashcards": [...]
    }
  ],
  "pagination": {...}
}
```

---

## 📝 Input Types

### **Text Input**

Plain text content for flashcard generation.

**Example:**
```json
{
  "input": "Artificial Intelligence is the simulation of human intelligence in machines.",
  "input_type": "text"
}
```

### **File Input**

Generate flashcards from uploaded files (PDF, DOCX, TXT, etc.).

**Prerequisites:**
- File must be uploaded first via `/api/files/upload`
- Use the returned `file_id` in the request

**Example:**
```json
{
  "file_id": "550e8400-e29b-41d4-a716-446655440000",
  "input_type": "file"
}
```

### **URL Input**

Extract content from a web page and generate flashcards.

**Example:**
```json
{
  "input": "https://example.com/article",
  "input_type": "url"
}
```

### **YouTube Input**

Transcribe YouTube video and generate flashcards from the transcript.

**Example:**
```json
{
  "input": "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
  "input_type": "youtube"
}
```

**Note:** If `input_type` is not specified, the system will auto-detect based on the input content.

---

## 📊 Request/Response Formats

### **Standard Success Response**

```json
{
  "success": true,
  "data": {...},
  "message": "Optional success message"
}
```

### **Standard Error Response**

```json
{
  "success": false,
  "error": "Error message",
  "details": {
    "field": ["Validation error message"]
  }
}
```

---

## ⚠️ Error Handling

### **Common Error Codes**

| Status Code | Description | Solution |
|-------------|-------------|----------|
| `400` | Bad Request | Check request format and parameters |
| `401` | Unauthorized | Verify authentication token |
| `404` | Not Found | Check endpoint URL and resource ID |
| `422` | Validation Error | Check request body validation errors |
| `500` | Internal Server Error | Service issue, retry later |
| `202` | Accepted | Job still processing (for result endpoint) |

### **Error Response Format**

```json
{
  "error": "Validation failed",
  "details": {
    "input": ["The input field is required when file_id is not present."],
    "count": ["The count must be between 1 and 40."]
  }
}
```

---

## 💻 Examples

### **Example 1: Generate Flashcards from Text**

```bash
# Step 1: Generate flashcards
curl -X POST http://your-domain.com/api/flashcards/generate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "input": "Photosynthesis is the process by which plants convert light energy into chemical energy.",
    "input_type": "text",
    "count": 5,
    "difficulty": "intermediate",
    "style": "mixed"
  }'

# Response:
# {
#   "success": true,
#   "job_id": "550e8400-e29b-41d4-a716-446655440000",
#   "status": "pending"
# }

# Step 2: Poll for status (every 2-3 seconds)
curl -X GET "http://your-domain.com/api/status/flashcards/text?job_id=550e8400-e29b-41d4-a716-446655440000" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Step 3: Get result when completed
curl -X GET "http://your-domain.com/api/result/flashcards/text?job_id=550e8400-e29b-41d4-a716-446655440000" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### **Example 2: Generate Flashcards from File**

```bash
# Step 1: Upload file first (if not already uploaded)
curl -X POST http://your-domain.com/api/files/upload \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@document.pdf"

# Step 2: Generate flashcards using file_id
curl -X POST http://your-domain.com/api/flashcards/generate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "file_id": "file-uuid-here",
    "input_type": "file",
    "count": 10,
    "difficulty": "advanced"
  }'
```

### **Example 3: Generate Flashcards from YouTube**

```bash
curl -X POST http://your-domain.com/api/flashcards/generate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "input": "https://www.youtube.com/watch?v=video-id",
    "input_type": "youtube",
    "count": 8,
    "difficulty": "beginner"
  }'
```

### **Example 4: List All Flashcard Sets**

```bash
curl -X GET "http://your-domain.com/api/flashcards?page=1&per_page=15" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 🔄 Workflows

### **Standard Workflow**

1. **Generate Flashcards** → `POST /api/flashcards/generate`
   - Creates job in **Universal Job Scheduler**
   - Queues job for **Queue Worker** processing
   - Returns `job_id` immediately (non-blocking)
   - Job is processed asynchronously in background

2. **Poll Status** → `GET /api/status/flashcards/{text|file}?job_id={id}`
   - Check every 2-3 seconds
   - Monitor `progress` (0-100) and `stage`
   - Wait until `status` is `completed`
   - Status tracked by Universal Job Scheduler

3. **Get Result** → `GET /api/result/flashcards/{text|file}?job_id={id}`
   - Returns `flashcards` array and `flashcard_set` information
   - Includes `ai_result` metadata
   - Frontend can display flashcards immediately

4. **Access Flashcards** → Use `flashcard_set.id` to retrieve via `GET /api/flashcards/{id}`
   - Full flashcard set with all cards
   - Can be updated or deleted via API

### **Processing Architecture**

```
Frontend Request
    ↓
POST /api/flashcards/generate
    ↓
UniversalJobService.createJob()
    ↓
UniversalJobService.queueJob()
    ↓
Queue Worker (Background)
    ↓
UniversalJobService.processFlashcardsJobWithStages()
    ↓
Stage 1: Analyze Content
    → Validates input
    ↓
Stage 2: Extract Content
    → From text/file/URL/YouTube
    ↓
Stage 3: Validate Content
    → Checks word count, quality
    ↓
Stage 4: Generate Flashcards
    → Calls AI Manager (POST /api/custom-prompt)
    → Dynamically constructs request from user input
    → Uses system_prompt + user_prompt with count, difficulty, style
    ↓
Stage 5: Parse Flashcards
    → Extracts from AI response
    → Converts front/back to question/answer
    ↓
Stage 6: Save to Database
    → Creates FlashcardSet
    → Creates individual Flashcard records
    ↓
Stage 7: Complete Job
    → Updates job status to 'completed'
    → Returns flashcard_set_id to frontend
```

### **Complete Example Flow**

```javascript
// 1. Generate flashcards
const generateResponse = await fetch('/api/flashcards/generate', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    input: 'Photosynthesis converts light to energy.',
    input_type: 'text',
    count: 5,
    difficulty: 'intermediate',
    style: 'mixed'
  })
});

const { job_id } = await generateResponse.json();

// 2. Poll for status
const pollStatus = async () => {
  const statusResponse = await fetch(`/api/status/flashcards/text?job_id=${job_id}`, {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  const status = await statusResponse.json();
  
  if (status.status === 'completed') {
    // 3. Get result
    const resultResponse = await fetch(`/api/result/flashcards/text?job_id=${job_id}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const result = await resultResponse.json();
    
    // 4. Display flashcards
    const flashcards = result.data.flashcards;
    const flashcardSetId = result.data.flashcard_set.id;
    
    flashcards.forEach(card => {
      // Cards have both front/back and question/answer formats
      console.log(`Q: ${card.front || card.question}`);
      console.log(`A: ${card.back || card.answer}`);
    });
  } else if (status.status === 'processing') {
    // Continue polling
    setTimeout(pollStatus, 2000);
  }
};

pollStatus();
```

---

## 📝 Notes

1. **Async Processing:** All flashcard generation is asynchronous via **Queue Workers**. Use polling to check job status.

2. **Queue Worker:** Jobs are processed by Laravel queue workers. Ensure queue workers are running:
   ```bash
   php artisan queue:work
   ```

3. **Universal Job Scheduler:** The module uses `UniversalJobService` for:
   - Job creation and tracking
   - Status management
   - Progress updates
   - Error handling

4. **Polling Interval:** Poll status endpoint every 2-3 seconds until completed.

5. **Maximum Wait Time:** Jobs typically complete within 30-120 seconds depending on content length and complexity.

6. **Content Requirements:**
   - Minimum: 5 words
   - Maximum: 50,000 words
   - Text input: Minimum 3 characters

7. **Flashcard Count:** 
   - Minimum: 1
   - Maximum: 40
   - Default: 5

8. **Difficulty Levels:**
   - `beginner` - Simple, straightforward questions
   - `intermediate` - Moderate complexity
   - `advanced` - Complex, detailed questions

9. **Question Styles:**
   - `definition` - "What is X?" format
   - `application` - "How do you use X?" format
   - `analysis` - "Why does X happen?" format
   - `comparison` - "What's the difference between X and Y?" format
   - `mixed` - Combination of all styles

10. **AI Manager Integration:**
    - Uses `POST /api/custom-prompt` endpoint (AI Manager Microservice)
    - Dynamically constructs request from user input parameters
    - Returns flashcards in `data.content` array with `front`/`back` format
    - Automatically converts to `question`/`answer` format for database storage

11. **Job Processing Flow:**
    - Request received → Job created in Universal Job Scheduler
    - Job queued → Processed by queue worker
    - Content extracted → From source (text/file/URL/YouTube)
    - AI Manager called → Flashcard generation started
    - Flashcards parsed → From AI response
    - Database saved → FlashcardSet and Flashcard records created
    - Job completed → Returns flashcard_set_id to frontend

---

## 🔧 Technical Details

### **Universal Job Scheduler Integration**

The Flashcard Module is fully integrated with the Universal Job Scheduler:

- **Job Creation:** `UniversalJobService::createJob('flashcards', ...)`
- **Job Queuing:** `UniversalJobService::queueJob($jobId)`
- **Job Processing:** `UniversalJobService::processFlashcardsJobWithStages()`
- **Status Tracking:** Real-time progress and stage updates
- **Error Handling:** Automatic job failure handling

### **Queue Worker Requirements**

For optimal performance, ensure queue workers are running:

```bash
# Start queue worker
php artisan queue:work

# Or use supervisor/systemd for production
```

**Queue Configuration:**
- Default: Uses Laravel queue system
- Fallback: Background process if queue is 'sync'
- Timeout: 180 seconds for flashcard generation

### **AI Manager Communication**

- **Endpoint:** `POST /api/custom-prompt` (AI Manager Microservice)
- **Base URL:** `https://aimanager.akmicroservice.com` (configurable via `.env`)
- **Authentication:** `X-API-KEY` header
- **API Key:** `AI_MANAGER_API_KEY` environment variable
- **Timeout:** 180 seconds (configurable)

#### **Request Structure to AI Manager**

The request is **dynamically constructed** from user input parameters:

**Endpoint:** `POST {AI_MANAGER_URL}/api/custom-prompt`

**Headers:**
```http
Content-Type: application/json
X-API-KEY: {AI_MANAGER_API_KEY}
Accept: application/json
```

**Request Body:**
```json
{
  "system_prompt": "You are an expert educational flashcard generator. Your task is to create high-quality flashcards that help students learn effectively.\n\nRules:\n1. Each flashcard MUST have exactly two fields: 'front' and 'back'\n2. 'front' contains the question, prompt, or term\n3. 'back' contains the answer, explanation, or definition\n4. Flashcards should be clear, concise, and educational\n5. Return ONLY valid JSON array format, no additional text or explanations\n\nDifficulty Levels:\n- beginner: Simple, basic concepts with straightforward questions\n- intermediate: Moderate complexity, requires understanding of relationships\n- advanced: Complex concepts, requires deep analysis and synthesis\n\nStyle Types:\n- definition: Focus on definitions and key terms\n- application: Focus on practical applications and examples\n- analysis: Focus on analysis and critical thinking\n- comparison: Focus on comparing and contrasting concepts\n- mixed: Combine different question types\n\nOutput format: A JSON array of flashcard objects.\nExample:\n[\n  {\"front\": \"What is X?\", \"back\": \"X is...\"},\n  {\"front\": \"How does Y work?\", \"back\": \"Y works by...\"}\n]",
  "prompt": "create json only flash card about {user_content} card count {count}, difficulty {difficulty}, style {style}, front and back",
  "response_format": "json",
  "model": "deepseek-chat",
  "max_tokens": 512
}
```

**Dynamic Prompt Construction:**
- `{user_content}` → Replaced with the actual content from user's `input` field
- `{count}` → Replaced with user's `count` parameter (1-40)
- `{difficulty}` → Replaced with user's `difficulty` parameter (beginner/intermediate/advanced)
- `{style}` → Replaced with user's `style` parameter (definition/application/analysis/comparison/mixed)

**Example with Actual Values:**
```json
{
  "system_prompt": "...",
  "prompt": "create json only flash card about Java is a high-level programming language that is object-oriented and class-based. It was developed by Sun Microsystems and is now owned by Oracle. Java applications are compiled to bytecode that runs on the Java Virtual Machine (JVM). card count 5, difficulty intermediate, style mixed, front and back",
  "response_format": "json",
  "model": "deepseek-chat",
  "max_tokens": 512
}
```

#### **Response Structure from AI Manager**

**Success Response:**
```json
{
  "status": "success",
  "model_used": "deepseek-chat",
  "model_display": "deepseek-chat",
  "format": "json",
  "data": {
    "format": "json",
    "raw": "[\n  {\"front\": \"What is Java?\", \"back\": \"Java is a high-level, object-oriented programming language...\"},\n  {\"front\": \"What is JVM?\", \"back\": \"JVM (Java Virtual Machine) is...\"}\n]",
    "content": [
      {
        "front": "What is Java?",
        "back": "Java is a high-level, object-oriented programming language developed by Sun Microsystems (now Oracle)."
      },
      {
        "front": "What is the JVM?",
        "back": "JVM (Java Virtual Machine) is a virtual machine that enables Java programs to run on any device or operating system."
      },
      {
        "front": "What is the difference between JDK and JRE?",
        "back": "JDK (Java Development Kit) includes development tools like compiler and debugger, while JRE (Java Runtime Environment) only includes the runtime needed to run Java applications."
      }
    ]
  },
  "tokens_used": 245,
  "processing_time": 1.23
}
```

**Key Response Fields:**
- `data.content` - **Primary location** for flashcards array (Priority 1 for extraction)
- `data.json` - Alternative location (Priority 2)
- `data.raw` - Raw JSON string (fallback parsing)
- Each flashcard has `front` and `back` fields

**Error Response:**
```json
{
  "status": "error",
  "message": "Error description here",
  "available_models": ["deepseek-chat", "gpt-4", ...]
}
```

### **Content Extraction Services**

- **Text:** Direct use of input text
- **File:** Uses Document Intelligence Module for content extraction
- **URL:** Uses Web Scraping Service
- **YouTube:** Uses Transcriber Module for video transcription

### **Job Storage**

- **Storage:** Laravel Cache (2 hour TTL)
- **Key Format:** `universal_job_{jobId}`
- **Status Updates:** Real-time via cache

### **Database Schema**

**FlashcardSet:**
- `id` - Primary key
- `user_id` - Owner user ID
- `title` - Set title
- `description` - Set description
- `input_type` - Source type (text/file/url/youtube)
- `input_content` - Original input
- `difficulty` - Difficulty level
- `style` - Question style
- `total_cards` - Number of cards
- `is_public` - Public visibility
- `source_metadata` - JSON metadata
- `created_at`, `updated_at`

**Flashcard:**
- `id` - Primary key
- `flashcard_set_id` - Foreign key to FlashcardSet
- `question` - Question text
- `answer` - Answer text
- `order_index` - Display order
- `created_at`, `updated_at`

---

**Last Updated:** November 2025  
**Documentation Version:** 1.1  
**Status:** ✅ Production Ready  
**Queue Worker:** ✅ Integrated  
**Universal Job Scheduler:** ✅ Integrated  
**AI Manager Integration:** ✅ Custom Prompt Endpoint (Dynamic Request Construction)

