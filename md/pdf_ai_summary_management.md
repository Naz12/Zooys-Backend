# 📄 PDF AI Summary Management Guide

## 🎯 **Overview**

This guide covers how to fetch and delete PDF AI summaries using the Laravel backend API. The system stores PDF summaries in the `a_i_results` table with comprehensive metadata and file associations.

## 📊 **Database Structure**

### **AI Results Table (`a_i_results`)**
```sql
- id (Primary Key)
- user_id (Foreign Key to users)
- file_upload_id (Foreign Key to file_uploads)
- tool_type (e.g., 'summarize', 'document_chat')
- title (Generated title)
- description (Result description)
- input_data (JSON - Original input)
- result_data (JSON - AI generated result)
- metadata (JSON - Additional metadata)
- status (completed, processing, failed)
- created_at, updated_at
```

## 🔍 **Fetching PDF AI Summaries**

### **1. Get All AI Results (with filtering)**
```http
GET /api/ai-results
Authorization: Bearer {token}
```

**Query Parameters:**
- `tool_type` - Filter by tool type (e.g., 'summarize')
- `per_page` - Results per page (default: 15)
- `page` - Page number (default: 1)
- `search` - Search in title and description

**Example Request:**
```http
GET /api/ai-results?tool_type=summarize&per_page=10&search=PDF
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

**Response:**
```json
{
  "ai_results": [
    {
      "id": 123,
      "user_id": 1,
      "file_upload_id": 456,
      "tool_type": "summarize",
      "title": "Document Summary - Research Paper",
      "description": "AI-generated summary of the research paper",
      "input_data": {
        "content_type": "pdf",
        "source": {
          "type": "file",
          "data": "456"
        },
        "options": {
          "mode": "detailed",
          "language": "en"
        }
      },
      "result_data": {
        "summary": "This research paper discusses...",
        "metadata": {
          "content_type": "pdf",
          "processing_time": "4.2s",
          "tokens_used": 1250,
          "confidence": 0.95
        }
      },
      "metadata": {
        "source_info": {
          "pages": 15,
          "word_count": 5000,
          "character_count": 25000,
          "file_size": "2.5 MB",
          "title": "Research Paper Title",
          "author": "Dr. John Doe",
          "created_date": "2024-01-15",
          "password_protected": false
        }
      },
      "status": "completed",
      "file_url": "/storage/uploads/files/uuid-filename.pdf",
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

### **2. Get Specific AI Result**
```http
GET /api/ai-results/{id}
Authorization: Bearer {token}
```

**Example Request:**
```http
GET /api/ai-results/123
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

**Response:**
```json
{
  "ai_result": {
    "id": 123,
    "user_id": 1,
    "file_upload_id": 456,
    "tool_type": "summarize",
    "title": "Document Summary - Research Paper",
    "description": "AI-generated summary of the research paper",
    "input_data": {...},
    "result_data": {
      "summary": "This research paper discusses the latest findings in artificial intelligence and machine learning applications in healthcare..."
    },
    "metadata": {...},
    "status": "completed",
    "file_url": "/storage/uploads/files/uuid-filename.pdf",
    "created_at": "2025-01-06T10:30:00Z",
    "updated_at": "2025-01-06T10:30:00Z"
  }
}
```

### **3. Get AI Results Statistics**
```http
GET /api/ai-results/stats
Authorization: Bearer {token}
```

**Response:**
```json
{
  "stats": {
    "total_results": 150,
    "results_by_tool": {
      "summarize": 45,
      "youtube": 30,
      "flashcards": 25,
      "document_chat": 50
    },
    "recent_results": [
      {
        "id": 123,
        "title": "Document Summary - Research Paper",
        "tool_type": "summarize",
        "created_at": "2025-01-06T10:30:00Z"
      }
    ]
  }
}
```

## 🗑️ **Deleting PDF AI Summaries**

### **1. Delete Specific AI Result**
```http
DELETE /api/ai-results/{id}
Authorization: Bearer {token}
```

**Example Request:**
```http
DELETE /api/ai-results/123
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

**Response:**
```json
{
  "message": "Result deleted successfully"
}
```

**Error Response:**
```json
{
  "error": "Result not found"
}
```

### **2. Automatic File Cleanup**
When an AI result is deleted:
- The system checks if the associated file is used by other results
- If no other results use the file, the file is automatically deleted
- This prevents orphaned files in storage

## 🔧 **Advanced Operations**

### **1. Update AI Result**
```http
PUT /api/ai-results/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Updated Title",
  "description": "Updated description",
  "metadata": {
    "custom_field": "value"
  }
}
```

### **2. Filter by PDF Summaries Only**
```http
GET /api/ai-results?tool_type=summarize&search=PDF
Authorization: Bearer {token}
```

### **3. Get Recent PDF Summaries**
```http
GET /api/ai-results?tool_type=summarize&per_page=5&page=1
Authorization: Bearer {token}
```

## 📝 **Frontend Integration Examples**

### **JavaScript/Fetch API**
```javascript
// Fetch all PDF summaries
async function fetchPDFSummaries() {
  const response = await fetch('/api/ai-results?tool_type=summarize', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  const data = await response.json();
  return data.ai_results;
}

// Delete a PDF summary
async function deletePDFSummary(resultId) {
  const response = await fetch(`/api/ai-results/${resultId}`, {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  const data = await response.json();
  return data;
}

// Get specific PDF summary
async function getPDFSummary(resultId) {
  const response = await fetch(`/api/ai-results/${resultId}`, {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  const data = await response.json();
  return data.ai_result;
}
```

### **Axios Example**
```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Authorization': `Bearer ${token}`
  }
});

// Fetch PDF summaries
const fetchPDFSummaries = async () => {
  const response = await api.get('/ai-results', {
    params: { tool_type: 'summarize' }
  });
  return response.data.ai_results;
};

// Delete PDF summary
const deletePDFSummary = async (resultId) => {
  const response = await api.delete(`/ai-results/${resultId}`);
  return response.data;
};
```

## 🛡️ **Security & Permissions**

### **User Isolation**
- Users can only access their own AI results
- All queries are automatically filtered by `user_id`
- File access is restricted to the result owner

### **Authentication Required**
- All endpoints require Bearer token authentication
- Invalid or expired tokens return 401 Unauthorized

### **Rate Limiting**
- API calls are subject to usage limits based on subscription plan
- Rate limiting is enforced by the `check.usage` middleware

## 📊 **Response Data Structure**

### **AI Result Object**
```typescript
interface AIResult {
  id: number;
  user_id: number;
  file_upload_id: number | null;
  tool_type: string;
  title: string;
  description: string | null;
  input_data: object;
  result_data: object;
  metadata: object | null;
  status: 'completed' | 'processing' | 'failed';
  file_url: string | null;
  created_at: string;
  updated_at: string;
}
```

### **Pagination Object**
```typescript
interface Pagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}
```

## 🚨 **Error Handling**

### **Common Error Responses**
```json
// 401 Unauthorized
{
  "message": "Unauthenticated."
}

// 404 Not Found
{
  "error": "Result not found"
}

// 422 Validation Error
{
  "error": "Validation failed",
  "details": {
    "per_page": ["The per page must be a number."]
  }
}

// 500 Server Error
{
  "error": "Unable to process your request at this time"
}
```

## 🔄 **Best Practices**

1. **Pagination**: Always use pagination for large result sets
2. **Filtering**: Use `tool_type=summarize` to get only PDF summaries
3. **Search**: Implement search functionality for better UX
4. **Error Handling**: Always handle API errors gracefully
5. **Caching**: Consider caching frequently accessed results
6. **File Cleanup**: The system automatically handles file cleanup

## 📈 **Performance Considerations**

- Database queries are optimized with proper indexing
- File URLs are generated on-demand
- Pagination prevents memory issues with large datasets
- Automatic file cleanup prevents storage bloat

---

**Note**: All API endpoints require authentication and are subject to usage limits based on the user's subscription plan.

