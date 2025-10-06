# 🚀 **Complete API Documentation - Updated**

## **Authentication**
All API endpoints require Bearer token authentication:
```
Authorization: Bearer {your_token}
```

---

## **📚 Flashcards API**

### **Generate Flashcards**
**POST** `/api/flashcards/generate`

Generate flashcards from text, URL, YouTube video, or uploaded file.

#### **Request Body:**
```json
{
    "input": "Machine learning is a subset of artificial intelligence...",
    "file": "file_upload", // Optional - for file uploads
    "input_type": "text", // text, url, youtube, file
    "count": 5, // 1-40
    "difficulty": "intermediate", // beginner, intermediate, advanced
    "style": "mixed" // definition, application, analysis, comparison, mixed
}
```

#### **Response:**
```json
{
    "flashcards": [
        {
            "question": "What is machine learning?",
            "answer": "Machine learning is a subset of artificial intelligence that focuses on algorithms that can learn from data."
        }
    ],
    "flashcard_set": {
        "id": 1,
        "title": "Machine Learning Flashcards",
        "description": "Flashcards generated from text input",
        "total_cards": 5,
        "created_at": "2025-10-06T14:45:00Z"
    },
    "ai_result": {
        "id": 1,
        "title": "Machine Learning Flashcards",
        "file_url": "/storage/uploads/files/uuid.txt", // If file was uploaded
        "created_at": "2025-10-06T14:45:00Z"
    },
    "metadata": {
        "total_generated": 5,
        "input_type": "text",
        "source_metadata": {
            "word_count": 150,
            "character_count": 800
        }
    }
}
```

### **Get User's Flashcard Sets**
**GET** `/api/flashcards`

#### **Query Parameters:**
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 15)
- `search` (optional): Search term for title/description

#### **Response:**
```json
{
    "flashcard_sets": [
        {
            "id": 1,
            "title": "Machine Learning Flashcards",
            "description": "Flashcards generated from text input",
            "total_cards": 5,
            "difficulty": "intermediate",
            "style": "mixed",
            "created_at": "2025-10-06T14:45:00Z",
            "flashcards": [
                {
                    "id": 1,
                    "question": "What is machine learning?",
                    "answer": "Machine learning is a subset of artificial intelligence...",
                    "order_index": 0
                }
            ]
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 15,
        "total": 1
    }
}
```

### **Get Public Flashcard Sets**
**GET** `/api/flashcards/public`

#### **Query Parameters:**
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 15)
- `search` (optional): Search term for title/description

#### **Response:**
```json
{
    "flashcard_sets": [
        {
            "id": 1,
            "title": "Machine Learning Flashcards",
            "description": "Flashcards generated from text input",
            "total_cards": 5,
            "difficulty": "intermediate",
            "style": "mixed",
            "user": {
                "id": 1,
                "name": "John Doe"
            },
            "created_at": "2025-10-06T14:45:00Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 15,
        "total": 1
    }
}
```

### **Get Specific Flashcard Set**
**GET** `/api/flashcards/{id}`

#### **Response:**
```json
{
    "flashcard_set": {
        "id": 1,
        "title": "Machine Learning Flashcards",
        "description": "Flashcards generated from text input",
        "total_cards": 5,
        "difficulty": "intermediate",
        "style": "mixed",
        "created_at": "2025-10-06T14:45:00Z",
        "flashcards": [
            {
                "id": 1,
                "question": "What is machine learning?",
                "answer": "Machine learning is a subset of artificial intelligence...",
                "order_index": 0
            }
        ]
    }
}
```

### **Update Flashcard Set**
**PUT** `/api/flashcards/{id}`

#### **Request Body:**
```json
{
    "title": "Updated Title",
    "description": "Updated description",
    "is_public": false
}
```

#### **Response:**
```json
{
    "message": "Flashcard set updated successfully",
    "flashcard_set": {
        "id": 1,
        "title": "Updated Title",
        "description": "Updated description",
        "is_public": false,
        "updated_at": "2025-10-06T15:30:00Z"
    }
}
```

### **Delete Flashcard Set**
**DELETE** `/api/flashcards/{id}`

#### **Response:**
```json
{
    "message": "Flashcard set deleted successfully"
}
```

---

## **📁 File Upload API**

### **Upload File**
**POST** `/api/files/upload`

Upload files for AI processing (PDF, DOC, TXT, audio).

#### **Request Body (multipart/form-data):**
```
file: [file_upload]
metadata: {
    "tool_type": "flashcards",
    "description": "Optional description"
}
```

#### **Response:**
```json
{
    "message": "File uploaded successfully",
    "file_upload": {
        "id": 1,
        "original_name": "machine_learning.pdf",
        "stored_name": "uuid.pdf",
        "file_type": "pdf",
        "file_size": 1024000,
        "human_file_size": "1.02 MB",
        "mime_type": "application/pdf",
        "is_processed": false,
        "created_at": "2025-10-06T14:45:00Z"
    },
    "file_url": "/storage/uploads/files/uuid.pdf"
}
```

### **Get User's Files**
**GET** `/api/files`

#### **Query Parameters:**
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 15)
- `search` (optional): Search term for filename/type

#### **Response:**
```json
{
    "files": [
        {
            "id": 1,
            "original_name": "machine_learning.pdf",
            "stored_name": "uuid.pdf",
            "file_type": "pdf",
            "file_size": 1024000,
            "human_file_size": "1.02 MB",
            "mime_type": "application/pdf",
            "is_processed": true,
            "file_url": "/storage/uploads/files/uuid.pdf",
            "created_at": "2025-10-06T14:45:00Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 15,
        "total": 1
    }
}
```

### **Get Specific File**
**GET** `/api/files/{id}`

#### **Response:**
```json
{
    "file": {
        "id": 1,
        "original_name": "machine_learning.pdf",
        "stored_name": "uuid.pdf",
        "file_type": "pdf",
        "file_size": 1024000,
        "human_file_size": "1.02 MB",
        "mime_type": "application/pdf",
        "is_processed": true,
        "file_url": "/storage/uploads/files/uuid.pdf",
        "metadata": {
            "tool_type": "flashcards",
            "uploaded_at": "2025-10-06T14:45:00Z"
        },
        "created_at": "2025-10-06T14:45:00Z"
    }
}
```

### **Get File Content**
**GET** `/api/files/{id}/content`

Extract and return the text content of a file.

#### **Response:**
```json
{
    "content": "Machine learning is a subset of artificial intelligence...",
    "metadata": {
        "word_count": 150,
        "character_count": 800,
        "pages": [
            {
                "page": 1,
                "text": "Machine learning is a subset..."
            }
        ],
        "total_pages": 1
    }
}
```

### **Delete File**
**DELETE** `/api/files/{id}`

#### **Response:**
```json
{
    "message": "File deleted successfully"
}
```

---

## **🤖 AI Results API**

### **Get User's AI Results**
**GET** `/api/ai-results`

#### **Query Parameters:**
- `tool_type` (optional): Filter by tool type (flashcards, presentation, etc.)
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 15)
- `search` (optional): Search term for title/description

#### **Response:**
```json
{
    "ai_results": [
        {
            "id": 1,
            "tool_type": "flashcards",
            "title": "Machine Learning Flashcards",
            "description": "Flashcards generated from uploaded file",
            "status": "completed",
            "file_url": "/storage/uploads/files/uuid.pdf",
            "input_data": {
                "input": "file_id",
                "input_type": "file",
                "count": 5,
                "difficulty": "intermediate",
                "style": "mixed"
            },
            "result_data": [
                {
                    "question": "What is machine learning?",
                    "answer": "Machine learning is a subset of artificial intelligence..."
                }
            ],
            "metadata": {
                "generation_method": "ai",
                "file_id": 1,
                "file_name": "machine_learning.pdf"
            },
            "created_at": "2025-10-06T14:45:00Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 15,
        "total": 1
    }
}
```

### **Get Specific AI Result**
**GET** `/api/ai-results/{id}`

#### **Response:**
```json
{
    "ai_result": {
        "id": 1,
        "tool_type": "flashcards",
        "title": "Machine Learning Flashcards",
        "description": "Flashcards generated from uploaded file",
        "status": "completed",
        "file_url": "/storage/uploads/files/uuid.pdf",
        "file_upload": {
            "id": 1,
            "original_name": "machine_learning.pdf",
            "file_type": "pdf",
            "file_url": "/storage/uploads/files/uuid.pdf"
        },
        "input_data": {...},
        "result_data": [...],
        "metadata": {...},
        "created_at": "2025-10-06T14:45:00Z"
    }
}
```

### **Update AI Result**
**PUT** `/api/ai-results/{id}`

#### **Request Body:**
```json
{
    "title": "Updated Title",
    "description": "Updated description",
    "metadata": {
        "custom_field": "value"
    }
}
```

#### **Response:**
```json
{
    "message": "Result updated successfully",
    "ai_result": {
        "id": 1,
        "title": "Updated Title",
        "description": "Updated description",
        "updated_at": "2025-10-06T15:30:00Z"
    }
}
```

### **Delete AI Result**
**DELETE** `/api/ai-results/{id}`

#### **Response:**
```json
{
    "message": "Result deleted successfully"
}
```

### **Get AI Results Statistics**
**GET** `/api/ai-results/stats`

#### **Response:**
```json
{
    "stats": {
        "total_results": 25,
        "results_by_tool": {
            "flashcards": 15,
            "presentation": 8,
            "summary": 2
        },
        "recent_results": [
            {
                "id": 1,
                "title": "Machine Learning Flashcards",
                "tool_type": "flashcards",
                "created_at": "2025-10-06T14:45:00Z"
            }
        ]
    }
}
```

---

## **🔧 Error Responses**

All endpoints return consistent error responses:

### **Validation Error (422)**
```json
{
    "error": "Validation failed",
    "messages": {
        "input": ["The input field is required."],
        "count": ["The count must be between 1 and 40."]
    }
}
```

### **Not Found (404)**
```json
{
    "error": "Flashcard set not found"
}
```

### **Server Error (500)**
```json
{
    "error": "AI service is currently unavailable. Please try again later."
}
```

### **Content Too Short (400)**
```json
{
    "error": "Content is too short. Please provide more detailed content (at least 5 words)."
}
```

---

## **📋 Request Examples**

### **Generate Flashcards from Text**
```javascript
const response = await fetch('/api/flashcards/generate', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        input: "Machine learning is a subset of artificial intelligence...",
        count: 5,
        difficulty: "intermediate",
        style: "mixed"
    })
});
```

### **Generate Flashcards from File**
```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('count', '5');
formData.append('difficulty', 'intermediate');
formData.append('style', 'mixed');

const response = await fetch('/api/flashcards/generate', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer ' + token
    },
    body: formData
});
```

### **Upload File**
```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('metadata', JSON.stringify({
    tool_type: 'flashcards',
    description: 'Machine learning document'
}));

const response = await fetch('/api/files/upload', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer ' + token
    },
    body: formData
});
```

### **Search Flashcard Sets**
```javascript
const response = await fetch('/api/flashcards?search=machine&page=1&per_page=10', {
    headers: {
        'Authorization': 'Bearer ' + token
    }
});
```

---

## **🎯 Key Features**

### **File Management**
- ✅ Upload files (PDF, DOC, TXT, audio)
- ✅ Extract content from files
- ✅ Get file URLs for frontend access
- ✅ Automatic file cleanup when results deleted

### **AI Results Management**
- ✅ Store all AI results in database
- ✅ Link results to source files
- ✅ Include file URLs in responses
- ✅ Full CRUD operations

### **Search & Filtering**
- ✅ Search flashcard sets by title/description
- ✅ Filter AI results by tool type
- ✅ Pagination for all list endpoints

### **File Lifecycle**
1. **Upload** → File saved with unique UUID
2. **Process** → Content extracted automatically
3. **Generate** → AI creates result from content
4. **Store** → Result saved with file association
5. **Return** → File URL included in response
6. **Delete** → File deleted when last result using it is deleted

---

## **🚀 New Endpoints Summary**

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/flashcards/generate` | Generate flashcards (supports file uploads) |
| GET | `/api/flashcards` | Get user's flashcard sets (with search) |
| GET | `/api/flashcards/public` | Get public flashcard sets |
| GET | `/api/flashcards/{id}` | Get specific flashcard set |
| PUT | `/api/flashcards/{id}` | Update flashcard set |
| DELETE | `/api/flashcards/{id}` | Delete flashcard set |
| POST | `/api/files/upload` | Upload files |
| GET | `/api/files` | Get user's files (with search) |
| GET | `/api/files/{id}` | Get specific file |
| GET | `/api/files/{id}/content` | Get file content |
| DELETE | `/api/files/{id}` | Delete file |
| GET | `/api/ai-results` | Get AI results (with filters) |
| GET | `/api/ai-results/{id}` | Get specific AI result |
| PUT | `/api/ai-results/{id}` | Update AI result |
| DELETE | `/api/ai-results/{id}` | Delete AI result |
| GET | `/api/ai-results/stats` | Get result statistics |

---

## **💡 Frontend Integration Tips**

1. **File Upload**: Use `FormData` for file uploads, not JSON
2. **File URLs**: Use the returned `file_url` to display/download files
3. **Search**: Implement debounced search for better UX
4. **Pagination**: Use the pagination object for navigation
5. **Error Handling**: Check for specific error messages and status codes
6. **Loading States**: Show loading indicators for file uploads and AI generation

---

**🎉 All endpoints are ready for frontend integration!**
