# 🚀 Zooys Backend - Complete Client API Documentation

## 📋 Table of Contents

1. [Authentication](#authentication)
2. [File Management](#file-management)
3. [Tool-Specific Endpoints](#tool-specific-endpoints)
4. [Status & Result Endpoints](#status--result-endpoints)
5. [AI Tools](#ai-tools)
6. [Chat System](#chat-system)
7. [Subscription & Payments](#subscription--payments)
8. [Error Handling](#error-handling)

---

## 🔐 Authentication

### Register User
```http
POST /api/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "created_at": "2024-01-01T00:00:00.000000Z"
  },
  "token": "1|abc123def456..."
}
```

### Login User
```http
POST /api/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "token": "1|abc123def456..."
}
```

### Get Current User
```http
GET /api/user
Authorization: Bearer 1|abc123def456...
```

**Response:**
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "created_at": "2024-01-01T00:00:00.000000Z"
}
```

### Logout
```http
POST /api/logout
Authorization: Bearer 1|abc123def456...
```

**Response:**
```json
{
  "message": "Logged out successfully"
}
```

---

## 📁 File Management

### Upload File
```http
POST /api/files/upload
Authorization: Bearer 1|abc123def456...
Content-Type: multipart/form-data

file: [File]
metadata: {"description": "My document"}
```

**Response:**
```json
{
  "success": true,
  "file_id": "uuid-string",
  "file_url": "https://example.com/files/uuid-string",
  "file_name": "document.pdf",
  "file_size": 1024000,
  "file_type": "application/pdf",
  "uploaded_at": "2024-01-01T00:00:00.000000Z"
}
```

### Get All Files
```http
GET /api/files
Authorization: Bearer 1|abc123def456...
```

**Response:**
```json
{
  "data": [
    {
      "id": "uuid-string",
      "file_name": "document.pdf",
      "file_size": 1024000,
      "file_type": "application/pdf",
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

### Get File Details
```http
GET /api/files/{file_id}
Authorization: Bearer 1|abc123def456...
```

**Response:**
```json
{
  "id": "uuid-string",
  "file_name": "document.pdf",
  "file_size": 1024000,
  "file_type": "application/pdf",
  "file_path": "/storage/files/uuid-string.pdf",
  "created_at": "2024-01-01T00:00:00.000000Z"
}
```

### Get File Content
```http
GET /api/files/{file_id}/content
Authorization: Bearer 1|abc123def456...
```

**Response:** File content as binary data

### Delete File
```http
DELETE /api/files/{file_id}
Authorization: Bearer 1|abc123def456...
```

**Response:**
```json
{
  "message": "File deleted successfully"
}
```

---

## 🛠️ Tool-Specific Endpoints

### 📝 Summarization

#### Text Summarization
```http
POST /api/summarize/async/text
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "text": "Your long text content here...",
  "options": {
    "language": "en",
    "format": "detailed",
    "focus": "summary"
  }
}
```

#### YouTube Video Summarization
```http
POST /api/summarize/async/youtube
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "url": "https://www.youtube.com/watch?v=VIDEO_ID",
  "options": {
    "language": "en",
    "format": "bundle",
    "focus": "summary"
  }
}
```

#### File Summarization
```http
POST /api/summarize/async/file
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "file_id": "uuid-string",
  "options": {
    "language": "en",
    "format": "detailed",
    "focus": "summary"
  }
}
```

#### Image Summarization
```http
POST /api/summarize/async/image
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "file_id": "uuid-string",
  "options": {
    "language": "en",
    "format": "detailed",
    "focus": "summary"
  }
}
```

#### Audio/Video Summarization
```http
POST /api/summarize/async/audiovideo
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "file_id": "uuid-string",
  "options": {
    "language": "en",
    "format": "bundle",
    "focus": "summary"
  }
}
```

#### Link Summarization
```http
POST /api/summarize/link
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "url": "https://example.com/article",
  "options": {
    "language": "en",
    "format": "bundle",
    "focus": "summary"
  }
}
```

**All Summarization Responses:**
```json
{
  "success": true,
  "job_id": "uuid-string",
  "status": "pending",
  "message": "Summarization job started"
}
```

### 🧮 Math Problem Solving

#### Solve Math Problem
```http
POST /api/math/solve
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "problem_text": "Solve for x: 2x + 5 = 15",
  "subject_area": "algebra",
  "difficulty_level": "intermediate"
}
```

#### Solve Math Problem with Image
```http
POST /api/math/solve
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "file_id": "uuid-string",
  "subject_area": "geometry",
  "difficulty_level": "advanced"
}
```

**Response:**
```json
{
  "success": true,
  "job_id": "uuid-string",
  "status": "pending",
  "message": "Math problem solving started"
}
```

#### Get Math Problems
```http
GET /api/math/problems
Authorization: Bearer 1|abc123def456...
```

#### Get Math Problem Details
```http
GET /api/math/problems/{id}
Authorization: Bearer 1|abc123def456...
```

#### Get Math History
```http
GET /api/math/history
Authorization: Bearer 1|abc123def456...
```

#### Get Math Statistics
```http
GET /api/math/stats
Authorization: Bearer 1|abc123def456...
```

### 🎴 Flashcard Generation

#### Generate Flashcards
```http
POST /api/flashcards/generate
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "input": "Your content here...",
  "input_type": "text",
  "count": 10,
  "difficulty": "intermediate",
  "style": "mixed"
}
```

#### Generate Flashcards from File
```http
POST /api/flashcards/generate
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "file_id": "uuid-string",
  "input_type": "file",
  "count": 10,
  "difficulty": "intermediate",
  "style": "mixed"
}
```

**Response:**
```json
{
  "success": true,
  "job_id": "uuid-string",
  "status": "pending",
  "message": "Flashcard generation started"
}
```

#### Get Flashcards
```http
GET /api/flashcards
Authorization: Bearer 1|abc123def456...
```

#### Get Public Flashcards
```http
GET /api/flashcards/public
Authorization: Bearer 1|abc123def456...
```

#### Get Flashcard Details
```http
GET /api/flashcards/{id}
Authorization: Bearer 1|abc123def456...
```

#### Update Flashcard
```http
PUT /api/flashcards/{id}
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "title": "Updated Title",
  "content": "Updated content..."
}
```

#### Delete Flashcard
```http
DELETE /api/flashcards/{id}
Authorization: Bearer 1|abc123def456...
```

### 📊 Presentation Generation

#### Generate Presentation
```http
POST /api/presentations/generate-outline
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "input_type": "text",
  "topic": "Artificial Intelligence",
  "language": "English",
  "tone": "Professional",
  "length": "Medium",
  "model": "Advanced Model"
}
```

#### Generate Presentation from File
```http
POST /api/presentations/generate-outline
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "input_type": "file",
  "file_id": "uuid-string",
  "language": "English",
  "tone": "Professional",
  "length": "Medium",
  "model": "Advanced Model"
}
```

**Response:**
```json
{
  "success": true,
  "job_id": "uuid-string",
  "status": "pending",
  "message": "Presentation generation started"
}
```

#### Get Presentation Templates
```http
GET /api/presentations/templates
Authorization: Bearer 1|abc123def456...
```

#### Get Presentation Details
```http
GET /api/presentations/{aiResultId}
Authorization: Bearer 1|abc123def456...
```

#### Update Presentation Outline
```http
PUT /api/presentations/{aiResultId}/update-outline
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "outline": "Updated outline structure..."
}
```

#### Generate Presentation Content
```http
POST /api/presentations/{aiResultId}/generate-content
Authorization: Bearer 1|abc123def456...
```

#### Generate PowerPoint File
```http
POST /api/presentations/{aiResultId}/generate-powerpoint
Authorization: Bearer 1|abc123def456...
```

#### Get Presentation Status
```http
GET /api/presentations/{aiResultId}/status
Authorization: Bearer 1|abc123def456...
```

#### Export Presentation
```http
POST /api/presentations/{aiResultId}/export
Authorization: Bearer 1|abc123def456...
```

#### Save Presentation
```http
POST /api/presentations/{aiResultId}/save
Authorization: Bearer 1|abc123def456...
```

### 📄 Document Chat

#### Chat with Document
```http
POST /api/chat/document
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "file_id": "uuid-string",
  "message": "What is this document about?",
  "session_id": "optional-session-id"
}
```

**Response:**
```json
{
  "success": true,
  "response": "This document is about...",
  "session_id": "session-uuid",
  "message_id": "message-uuid"
}
```

#### Get Document Chat History
```http
GET /api/chat/document/{documentId}/history
Authorization: Bearer 1|abc123def456...
```

### 🔄 File Processing & Conversion

#### Convert Document
```http
POST /api/file-processing/convert
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "file_id": "uuid-string",
  "target_format": "pdf",
  "options": {
    "quality": "high",
    "compression": "medium"
  }
}
```

#### Extract Content
```http
POST /api/file-processing/extract
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "file_id": "uuid-string",
  "extraction_type": "text",
  "options": {
    "include_images": false,
    "preserve_formatting": true
  }
}
```

#### Get Conversion Capabilities
```http
GET /api/file-processing/conversion-capabilities
Authorization: Bearer 1|abc123def456...
```

#### Get Extraction Capabilities
```http
GET /api/file-processing/extraction-capabilities
Authorization: Bearer 1|abc123def456...
```

#### Check Processing Health
```http
GET /api/file-processing/health
Authorization: Bearer 1|abc123def456...
```

### 🎨 Diagram Generation

#### Generate Diagram
```http
POST /api/diagram/generate
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "description": "Create a flowchart for user registration process",
  "diagram_type": "flowchart",
  "style": "modern"
}
```

**Response:**
```json
{
  "success": true,
  "job_id": "uuid-string",
  "status": "pending",
  "message": "Diagram generation started"
}
```

### ✍️ Content Writing

#### Generate Content
```http
POST /api/writer/run
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "prompt": "Write a blog post about AI",
  "content_type": "blog_post",
  "tone": "professional",
  "length": "medium"
}
```

**Response:**
```json
{
  "success": true,
  "job_id": "uuid-string",
  "status": "pending",
  "message": "Content generation started"
}
```

---

## 📊 Status & Result Endpoints

### Universal Status Check
```http
GET /api/status?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

**Response:**
```json
{
  "job_id": "uuid-string",
  "status": "completed",
  "progress": 100,
  "stage": "finalizing",
  "error": null,
  "tool_type": "summarize",
  "created_at": "2024-01-01T00:00:00.000000Z",
  "updated_at": "2024-01-01T00:05:00.000000Z"
}
```

### Universal Result Retrieval
```http
GET /api/result?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

**Response:**
```json
{
  "success": true,
  "data": {
    "summary": "Generated summary content...",
    "key_points": ["Point 1", "Point 2", "Point 3"],
    "word_count": 150,
    "confidence_score": 0.95
  }
}
```

### Tool-Specific Status & Result Endpoints

#### 📝 Summarize Tool

**Text Summarization Status:**
```http
GET /api/status/summarize/text?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

**Text Summarization Result:**
```http
GET /api/result/summarize/text?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

**YouTube Summarization Status:**
```http
GET /api/status/summarize/youtube?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

**YouTube Summarization Result:**
```http
GET /api/result/summarize/youtube?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

**File Summarization Status:**
```http
GET /api/status/summarize/file?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

**File Summarization Result:**
```http
GET /api/result/summarize/file?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

**Web Summarization Status:**
```http
GET /api/status/summarize/web?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

**Web Summarization Result:**
```http
GET /api/result/summarize/web?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

#### 🧮 Math Tool

**Text Math Status:**
```http
GET /api/status/math/text?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

**Text Math Result:**
```http
GET /api/result/math/text?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

**Image Math Status:**
```http
GET /api/status/math/image?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

**Image Math Result:**
```http
GET /api/result/math/image?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

#### 🎴 Flashcards Tool

**Text Flashcards Status:**
```http
GET /api/status/flashcards/text?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

**Text Flashcards Result:**
```http
GET /api/result/flashcards/text?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

**File Flashcards Status:**
```http
GET /api/status/flashcards/file?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

**File Flashcards Result:**
```http
GET /api/result/flashcards/file?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

#### 📊 Presentations Tool

**Text Presentations Status:**
```http
GET /api/status/presentations/text?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

**Text Presentations Result:**
```http
GET /api/result/presentations/text?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

**File Presentations Status:**
```http
GET /api/status/presentations/file?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

**File Presentations Result:**
```http
GET /api/result/presentations/file?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

#### 💬 Document Chat Tool

**Document Chat Status:**
```http
GET /api/status/document_chat/file?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

**Document Chat Result:**
```http
GET /api/result/document_chat/file?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

#### 📄 Content Extraction Tool

**Content Extraction Status:**
```http
GET /api/status/content_extraction/file?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

**Content Extraction Result:**
```http
GET /api/result/content_extraction/file?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

#### 🔄 Document Conversion Tool

**Document Conversion Status:**
```http
GET /api/status/document_conversion/file?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

**Document Conversion Result:**
```http
GET /api/result/document_conversion/file?job_id={jobId}
Authorization: Bearer 1|abc123def456...
```

### Tool-Specific Response Format

**Status Response:**
```json
{
  "job_id": "uuid-string",
  "tool_type": "summarize",
  "input_type": "text",
  "status": "completed",
  "progress": 100,
  "stage": "finalizing",
  "error": null,
  "created_at": "2024-01-01T00:00:00.000000Z",
  "updated_at": "2024-01-01T00:05:00.000000Z"
}
```

**Result Response:**
```json
{
  "success": true,
  "job_id": "uuid-string",
  "tool_type": "summarize",
  "input_type": "text",
  "data": {
    "summary": "Generated summary content...",
    "key_points": ["Point 1", "Point 2", "Point 3"],
    "word_count": 150,
    "confidence_score": 0.95
  }
}
```

---

## 💬 Chat System

### General Chat
```http
POST /api/chat
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "message": "Hello, how can you help me?",
  "session_id": "optional-session-id"
}
```

### Create and Chat
```http
POST /api/chat/create-and-chat
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "message": "Hello, how can you help me?",
  "context": "I'm working on a project about AI"
}
```

### Get Chat History
```http
GET /api/chat/history
Authorization: Bearer 1|abc123def456...
```

### Chat Sessions

#### Get All Sessions
```http
GET /api/chat/sessions
Authorization: Bearer 1|abc123def456...
```

#### Create Session
```http
POST /api/chat/sessions
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "title": "My Chat Session",
  "context": "Discussion about AI"
}
```

#### Get Session Details
```http
GET /api/chat/sessions/{sessionId}
Authorization: Bearer 1|abc123def456...
```

#### Update Session
```http
PUT /api/chat/sessions/{sessionId}
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "title": "Updated Title",
  "context": "Updated context"
}
```

#### Delete Session
```http
DELETE /api/chat/sessions/{sessionId}
Authorization: Bearer 1|abc123def456...
```

#### Archive Session
```http
POST /api/chat/sessions/{sessionId}/archive
Authorization: Bearer 1|abc123def456...
```

#### Restore Session
```http
POST /api/chat/sessions/{sessionId}/restore
Authorization: Bearer 1|abc123def456...
```

### Chat Messages

#### Get Session Messages
```http
GET /api/chat/sessions/{sessionId}/messages
Authorization: Bearer 1|abc123def456...
```

#### Send Message to Session
```http
POST /api/chat/sessions/{sessionId}/messages
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "message": "Hello, how are you?",
  "role": "user"
}
```

#### Get Session History
```http
GET /api/chat/sessions/{sessionId}/history
Authorization: Bearer 1|abc123def456...
```

---

## 💳 Subscription & Payments

### Get Available Plans
```http
GET /api/plans
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Free",
      "price": 0,
      "features": ["Basic features"],
      "limits": {
        "requests_per_month": 100
      }
    }
  ]
}
```

### Create Checkout Session
```http
POST /api/checkout
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "plan_id": 2,
  "success_url": "https://yourapp.com/success",
  "cancel_url": "https://yourapp.com/cancel"
}
```

**Response:**
```json
{
  "checkout_url": "https://checkout.stripe.com/pay/...",
  "session_id": "cs_test_..."
}
```

### Verify Checkout Session
```http
GET /api/checkout/verify/{sessionId}
Authorization: Bearer 1|abc123def456...
```

### Get Current Subscription
```http
GET /api/subscription
Authorization: Bearer 1|abc123def456...
```

### Get Subscription History
```http
GET /api/subscription/history
Authorization: Bearer 1|abc123def456...
```

### Get Usage Statistics
```http
GET /api/usage
Authorization: Bearer 1|abc123def456...
```

---

## 📈 AI Results Management

### Get All AI Results
```http
GET /api/ai-results
Authorization: Bearer 1|abc123def456...
```

### Get AI Result Details
```http
GET /api/ai-results/{id}
Authorization: Bearer 1|abc123def456...
```

### Update AI Result
```http
PUT /api/ai-results/{id}
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "title": "Updated Title",
  "content": "Updated content..."
}
```

### Delete AI Result
```http
DELETE /api/ai-results/{id}
Authorization: Bearer 1|abc123def456...
```

### Get AI Results Statistics
```http
GET /api/ai-results/stats
Authorization: Bearer 1|abc123def456...
```

---

## ❌ Error Handling

### Common Error Responses

#### Authentication Error (401)
```json
{
  "error": "Unauthenticated",
  "message": "Invalid or missing authentication token"
}
```

#### Validation Error (422)
```json
{
  "error": "Validation failed",
  "details": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

#### Not Found Error (404)
```json
{
  "error": "Resource not found",
  "message": "The requested resource could not be found"
}
```

#### Job Not Found (404)
```json
{
  "error": "Job not found",
  "message": "The specified job ID does not exist"
}
```

#### Job Not Completed (409)
```json
{
  "error": "Job not completed",
  "status": "processing",
  "message": "The job is still being processed"
}
```

#### File Not Found (404)
```json
{
  "error": "File not found",
  "details": "File does not exist or has been deleted"
}
```

#### Server Error (500)
```json
{
  "error": "Internal server error",
  "message": "An unexpected error occurred"
}
```

### Job Status Values

- `pending` - Job is queued and waiting to be processed
- `processing` - Job is currently being processed
- `completed` - Job has finished successfully
- `failed` - Job encountered an error and failed
- `cancelled` - Job was cancelled by user or system

### Job Stages

- `initializing` - Setting up the job
- `analyzing_content` - Analyzing input content
- `ai_processing` - Running AI models
- `finalizing` - Preparing final results
- `completed` - Job finished

---

## 🔧 Client API Endpoints (Legacy)

### Math Client Endpoints
```http
POST /api/client/math/generate
POST /api/client/math/help
GET /api/client/math/history
GET /api/client/math/stats
```

These endpoints are aliases for the main math endpoints and provide the same functionality.

---

## 📝 Notes

1. **Authentication**: All endpoints (except public ones) require a Bearer token in the Authorization header
2. **File Uploads**: Use the `/api/files/upload` endpoint first to get a `file_id`, then use that ID in tool endpoints
3. **Async Processing**: Most AI tools return a `job_id` for async processing. Use status/result endpoints to check progress
4. **Rate Limits**: Be aware of subscription-based rate limits
5. **File Types**: Supported file types include PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT, HTML, HTM, JPG, JPEG, PNG, BMP, GIF
6. **File Size**: Maximum file size is 50MB
7. **CORS**: CORS is configured for `http://localhost:3000` for development

---

## 🚀 Quick Start Example

1. **Register/Login** to get authentication token
2. **Upload file** to get file_id
3. **Start AI job** using tool endpoint
4. **Poll status** until job completes
5. **Get results** from result endpoint

```javascript
// Example workflow
const token = await login(email, password);
const fileId = await uploadFile(file, token);
const job = await startSummarization(fileId, token);
const result = await pollForResult(job.job_id, token);
```
