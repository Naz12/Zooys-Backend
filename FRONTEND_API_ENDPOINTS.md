# 🚀 Frontend API Endpoints Reference

## 🔐 **Authentication Endpoints**

### **Register User**
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

### **Login User**
```http
POST /api/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

### **Logout User**
```http
POST /api/logout
Authorization: Bearer {token}
```

### **Get Current User**
```http
GET /api/user
Authorization: Bearer {token}
```

---

## 📁 **File Management Endpoints**

### **Upload File**
```http
POST /api/files/upload
Authorization: Bearer {token}
Content-Type: multipart/form-data

FormData:
- file: [File object]
- metadata: {"tool_type": "summarize"}
```

**Response:**
```json
{
  "message": "File uploaded successfully",
  "file_upload": {
    "id": 123,
    "original_name": "document.pdf",
    "file_type": "pdf",
    "file_size": 1024000,
    "human_file_size": "1.0 MB",
    "file_url": "/storage/uploads/files/uuid.pdf",
    "created_at": "2025-01-06T10:30:00Z"
  },
  "file_url": "/storage/uploads/files/uuid.pdf"
}
```

### **Get User Files**
```http
GET /api/files?page=1&per_page=15&search=document
Authorization: Bearer {token}
```

### **Get Specific File**
```http
GET /api/files/{id}
Authorization: Bearer {token}
```

### **Delete File**
```http
DELETE /api/files/{id}
Authorization: Bearer {token}
```

### **Get File Content**
```http
GET /api/files/{id}/content
Authorization: Bearer {token}
```

---

## 🤖 **AI Results Management**

### **Get All AI Results**
```http
GET /api/ai-results?page=1&per_page=15&tool_type=summarize&search=document
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Results per page (default: 15)
- `tool_type` - Filter by tool type (summarize, youtube, flashcards, etc.)
- `search` - Search in title and description

**Response:**
```json
{
  "ai_results": [
    {
      "id": 123,
      "user_id": 1,
      "file_upload_id": 456,
      "tool_type": "summarize",
      "title": "Document Summary",
      "description": "AI-generated summary of the document",
      "input_data": {...},
      "result_data": {...},
      "metadata": {...},
      "status": "completed",
      "file_url": "/storage/uploads/files/uuid.pdf",
      "created_at": "2025-01-06T10:30:00Z",
      "updated_at": "2025-01-06T10:30:00Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75
  }
}
```

### **Get Specific AI Result**
```http
GET /api/ai-results/{id}
Authorization: Bearer {token}
```

### **Update AI Result**
```http
PUT /api/ai-results/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Updated Title",
  "description": "Updated description",
  "metadata": {...}
}
```

### **Delete AI Result**
```http
DELETE /api/ai-results/{id}
Authorization: Bearer {token}
```

### **Get AI Results Statistics**
```http
GET /api/ai-results/stats
Authorization: Bearer {token}
```

---

## 📝 **Content Summarization**

### **Summarize Content**
```http
POST /api/summarize
Authorization: Bearer {token}
Content-Type: application/json

{
  "content_type": "pdf",
  "source": {
    "type": "file",
    "data": "123"
  },
  "options": {
    "mode": "detailed",
    "language": "en",
    "focus": "summary"
  }
}
```

**Content Types:**
- `pdf` - PDF documents
- `image` - Images (OCR)
- `audio` - Audio files
- `video` - Video files
- `link` - Web links
- `text` - Plain text

**Response:**
```json
{
  "summary": "AI-generated summary content...",
  "metadata": {
    "content_type": "pdf",
    "processing_time": "4.2s",
    "tokens_used": 1500,
    "confidence": 0.95
  },
  "source_info": {
    "pages": 10,
    "word_count": 2500,
    "file_size": "2.5MB",
    "title": "Document Title"
  },
  "ai_result": {
    "id": 123,
    "title": "Document Summary",
    "file_url": "/storage/uploads/files/uuid.pdf",
    "created_at": "2025-01-06T10:30:00Z"
  }
}
```

### **Upload File for Summarization**
```http
POST /api/summarize/upload
Authorization: Bearer {token}
Content-Type: multipart/form-data

FormData:
- file: [File object]
- content_type: "pdf"
```

---

## 🎥 **YouTube Summarization**

### **Summarize YouTube Video**
```http
POST /api/youtube/summarize
Authorization: Bearer {token}
Content-Type: application/json

{
  "video_url": "https://youtube.com/watch?v=VIDEO_ID",
  "language": "en",
  "mode": "detailed"
}
```

**Response:**
```json
{
  "summary": "AI-generated video summary...",
  "video_info": {
    "title": "Video Title",
    "channel": "Channel Name",
    "duration": "10:30",
    "views": "1.2M"
  },
  "ai_result": {
    "id": 123,
    "title": "Video Summary",
    "file_url": null,
    "created_at": "2025-01-06T10:30:00Z"
  }
}
```

---

## 🃏 **Flashcards**

### **Generate Flashcards**
```http
POST /api/flashcards/generate
Authorization: Bearer {token}
Content-Type: application/json

{
  "input": "Machine learning concepts",
  "input_type": "text",
  "count": 10,
  "difficulty": "intermediate",
  "style": "mixed"
}
```

**Input Types:**
- `text` - Plain text input
- `url` - Web page URL
- `youtube` - YouTube video URL
- `file` - Uploaded file ID

**Response:**
```json
{
  "flashcards": [
    {
      "question": "What is machine learning?",
      "answer": "Machine learning is a subset of AI..."
    }
  ],
  "flashcard_set": {
    "id": 123,
    "title": "Machine Learning Concepts",
    "description": "Generated flashcards about machine learning",
    "total_cards": 10,
    "created_at": "2025-01-06T10:30:00Z"
  },
  "ai_result": {
    "id": 456,
    "title": "Machine Learning Flashcards",
    "file_url": "/storage/uploads/files/uuid.pdf",
    "created_at": "2025-01-06T10:30:00Z"
  }
}
```

### **Get Flashcard Sets**
```http
GET /api/flashcards?page=1&per_page=15&search=machine
Authorization: Bearer {token}
```

### **Get Public Flashcard Sets**
```http
GET /api/flashcards/public?page=1&per_page=15&search=machine
Authorization: Bearer {token}
```

### **Get Specific Flashcard Set**
```http
GET /api/flashcards/{id}
Authorization: Bearer {token}
```

### **Update Flashcard Set**
```http
PUT /api/flashcards/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Updated Title",
  "description": "Updated description",
  "is_public": true
}
```

### **Delete Flashcard Set**
```http
DELETE /api/flashcards/{id}
Authorization: Bearer {token}
```

---

## 💬 **AI Chat**

### **Send Chat Message**
```http
POST /api/chat
Authorization: Bearer {token}
Content-Type: application/json

{
  "message": "Hello, how can you help me?",
  "session_id": 123,
  "conversation_history": [...],
  "model": "gpt-3.5-turbo",
  "temperature": 0.7,
  "max_tokens": 1000
}
```

### **Create Chat Session**
```http
POST /api/chat/create-and-chat
Authorization: Bearer {token}
Content-Type: application/json

{
  "message": "Start a new conversation",
  "title": "New Chat Session"
}
```

### **Get Chat History**
```http
GET /api/chat/history?page=1&per_page=15
Authorization: Bearer {token}
```

### **Get Chat Sessions**
```http
GET /api/chat/sessions?page=1&per_page=15
Authorization: Bearer {token}
```

### **Create Chat Session**
```http
POST /api/chat/sessions
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "New Session",
  "description": "Session description"
}
```

### **Get Specific Chat Session**
```http
GET /api/chat/sessions/{sessionId}
Authorization: Bearer {token}
```

### **Update Chat Session**
```http
PUT /api/chat/sessions/{sessionId}
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Updated Title",
  "description": "Updated description"
}
```

### **Delete Chat Session**
```http
DELETE /api/chat/sessions/{sessionId}
Authorization: Bearer {token}
```

### **Archive Chat Session**
```http
POST /api/chat/sessions/{sessionId}/archive
Authorization: Bearer {token}
```

### **Restore Chat Session**
```http
POST /api/chat/sessions/{sessionId}/restore
Authorization: Bearer {token}
```

### **Send Message to Session**
```http
POST /api/chat/sessions/{sessionId}/messages
Authorization: Bearer {token}
Content-Type: application/json

{
  "content": "Message content",
  "role": "user"
}
```

### **Get Session Messages**
```http
GET /api/chat/sessions/{sessionId}/messages?page=1&per_page=15
Authorization: Bearer {token}
```

### **Get Session History**
```http
GET /api/chat/sessions/{sessionId}/history
Authorization: Bearer {token}
```

---

## 📊 **Subscription & Usage**

### **Get Current Subscription**
```http
GET /api/subscription
Authorization: Bearer {token}
```

### **Get Subscription History**
```http
GET /api/subscription/history
Authorization: Bearer {token}
```

### **Get Usage Statistics**
```http
GET /api/usage
Authorization: Bearer {token}
```

### **Get Available Plans**
```http
GET /api/plans
```

---

## 💳 **Payments**

### **Create Checkout Session**
```http
POST /api/checkout
Authorization: Bearer {token}
Content-Type: application/json

{
  "plan_id": 2,
  "success_url": "https://yourapp.com/success",
  "cancel_url": "https://yourapp.com/cancel"
}
```

---

## 🔧 **Other Tools**

### **Generate Diagram**
```http
POST /api/diagram/generate
Authorization: Bearer {token}
Content-Type: application/json

{
  "description": "Create a flowchart for user registration",
  "type": "flowchart"
}
```

### **Document Chat**
```http
POST /api/chat/document
Authorization: Bearer {token}
Content-Type: application/json

{
  "document_id": 123,
  "query": "What is this document about?",
  "conversation_history": [...]
}
```

### **Get Document Chat History**
```http
GET /api/chat/document/{documentId}/history
Authorization: Bearer {token}
```

---

## 📋 **Common Response Formats**

### **Success Response:**
```json
{
  "message": "Operation successful",
  "data": {...}
}
```

### **Error Response:**
```json
{
  "error": "Error message",
  "details": {...}
}
```

### **Pagination Response:**
```json
{
  "data": [...],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75
  }
}
```

---

## 🔑 **Authentication Headers**

All protected endpoints require:
```http
Authorization: Bearer {your_token_here}
```

---

## 📝 **Notes**

1. **Base URL:** `http://localhost:8000` (development)
2. **Content-Type:** `application/json` for JSON requests
3. **File Uploads:** Use `multipart/form-data`
4. **Pagination:** All list endpoints support pagination
5. **Search:** Most list endpoints support search functionality
6. **File URLs:** All file URLs are relative to the base URL
