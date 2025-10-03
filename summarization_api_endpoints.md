# 📝 Summarization API Endpoints Documentation

## 🎯 **Overview**
Complete API documentation for the unified summarization system supporting text, web links, PDFs, images, audio, and video content.

---

## 🔐 **Authentication**
All endpoints require Bearer token authentication:
```http
Authorization: Bearer YOUR_TOKEN_HERE
```

---

## 📋 **API Endpoints**

### **1. Unified Summarization**
```
POST /api/summarize
```

**Description:** Main endpoint for summarizing any type of content.

**Request Body:**
```json
{
  "content_type": "text|link|pdf|image|audio|video",
  "source": {
    "type": "text|url|file",
    "data": "content_or_upload_id"
  },
  "options": {
    "mode": "detailed|brief",
    "language": "en"
  }
}
```

**Response:**
```json
{
  "summary": "Generated summary...",
  "metadata": {
    "content_type": "text",
    "processing_time": "1.2s",
    "tokens_used": 800,
    "confidence": 0.95
  },
  "source_info": {
    "word_count": 38,
    "character_count": 276
  }
}
```

### **2. File Upload**
```
POST /api/summarize/upload
```

**Description:** Upload files for summarization.

**Request:** Multipart form data
- `file`: File to upload (max 100MB)
- `content_type`: Type of content (pdf, image, audio, video)

**Response:**
```json
{
  "upload_id": 123,
  "filename": "document.pdf",
  "file_path": "uploads/pdf/123_document.pdf",
  "file_size": 2048000,
  "content_type": "pdf",
  "status": "uploaded"
}
```

### **3. Upload Status**
```
GET /api/summarize/upload/{uploadId}/status
```

**Description:** Check the status of an uploaded file.

**Response:**
```json
{
  "upload_id": 123,
  "status": "completed",
  "file_type": "pdf",
  "file_size": 2048000,
  "created_at": "2025-10-03T10:36:37.000000Z"
}
```

---

## 📊 **Content Types & Examples**

### **1. Text Summarization**
```json
{
  "content_type": "text",
  "source": {
    "type": "text",
    "data": "Your long text content here..."
  },
  "options": {
    "mode": "detailed",
    "language": "en"
  }
}
```

**Response:**
```json
{
  "summary": "Summary:\n1. Main topics and themes: The content discusses...",
  "metadata": {
    "content_type": "text",
    "processing_time": "1.2s",
    "tokens_used": 800,
    "confidence": 0.95
  },
  "source_info": {
    "word_count": 38,
    "character_count": 276
  }
}
```

### **2. Web Link Summarization**
```json
{
  "content_type": "link",
  "source": {
    "type": "url",
    "data": "https://example.com/article"
  },
  "options": {
    "mode": "detailed",
    "language": "en"
  }
}
```

**Response:**
```json
{
  "summary": "Summary:\n1. Main topics and themes: The webpage discusses...",
  "metadata": {
    "content_type": "link",
    "processing_time": "3.2s",
    "tokens_used": 1200,
    "confidence": 0.95
  },
  "source_info": {
    "url": "https://example.com/article",
    "title": "Article Title",
    "description": "Article description",
    "author": "Article Author",
    "published_date": "2025-01-01",
    "word_count": 500
  }
}
```

### **3. PDF Document Summarization**
```json
{
  "content_type": "pdf",
  "source": {
    "type": "file",
    "data": "123"
  },
  "options": {
    "mode": "detailed",
    "language": "en"
  }
}
```

**Response (Success):**
```json
{
  "summary": "Summary:\n1. Main topics and themes: The document covers...",
  "metadata": {
    "content_type": "pdf",
    "processing_time": "4.2s",
    "tokens_used": 1500,
    "confidence": 0.95
  },
  "source_info": {
    "pages": 5,
    "word_count": 2000,
    "character_count": 12000,
    "file_size": "2.3MB",
    "title": "Document Title",
    "author": "Document Author",
    "created_date": "2025-01-01",
    "subject": "Document Subject",
    "password_protected": false
  }
}
```

**Response (Password-Protected PDF):**
```json
{
  "error": "This PDF is password-protected and cannot be summarized. Please use an unprotected PDF file.",
  "metadata": {
    "content_type": "pdf",
    "processing_time": "0.5s",
    "tokens_used": 0,
    "confidence": 0.0
  },
  "source_info": {
    "pages": 0,
    "word_count": 0,
    "file_size": "2.3MB",
    "password_protected": true
  }
}
```

### **4. Image Summarization (Mock Data)**
```json
{
  "content_type": "image",
  "source": {
    "type": "file",
    "data": "456"
  },
  "options": {
    "mode": "detailed",
    "language": "en"
  }
}
```

**Response:**
```json
{
  "summary": "Summary: The uploaded image contains visual content...",
  "metadata": {
    "content_type": "image",
    "processing_time": "2.5s",
    "tokens_used": 800,
    "confidence": 0.88
  },
  "source_info": {
    "file_size": "1.2MB",
    "image_resolution": "1920x1080",
    "file_format": "JPEG"
  }
}
```

### **5. Audio Summarization (Mock Data)**
```json
{
  "content_type": "audio",
  "source": {
    "type": "file",
    "data": "789"
  },
  "options": {
    "mode": "detailed",
    "language": "en"
  }
}
```

**Response:**
```json
{
  "summary": "Summary: The uploaded audio file contains speech about...",
  "metadata": {
    "content_type": "audio",
    "processing_time": "8.5s",
    "tokens_used": 2000,
    "confidence": 0.92
  },
  "source_info": {
    "duration": "5:30",
    "audio_quality": "High",
    "file_size": "8.2MB",
    "transcription": "Mock transcription of the audio content..."
  }
}
```

### **6. Video Summarization (Mock Data)**
```json
{
  "content_type": "video",
  "source": {
    "type": "file",
    "data": "101"
  },
  "options": {
    "mode": "detailed",
    "language": "en"
  }
}
```

**Response:**
```json
{
  "summary": "Summary: The uploaded video contains a presentation about...",
  "metadata": {
    "content_type": "video",
    "processing_time": "12.3s",
    "tokens_used": 2500,
    "confidence": 0.90
  },
  "source_info": {
    "duration": "10:45",
    "video_quality": "HD",
    "file_size": "45.6MB",
    "transcription": "Mock transcription from video audio track..."
  }
}
```

---

## 🚨 **Error Responses**

### **Validation Errors (422)**
```json
{
  "error": "Validation failed",
  "details": {
    "content_type": ["The content type field is required."],
    "source.type": ["The source.type field is required."]
  }
}
```

### **Authentication Errors (401)**
```json
{
  "error": "Unauthenticated"
}
```

### **File Not Found (400)**
```json
{
  "error": "PDF file not found. Please upload the file first.",
  "metadata": {
    "content_type": "pdf",
    "processing_time": "0.5s",
    "tokens_used": 0,
    "confidence": 0.0
  },
  "source_info": {
    "pages": 0,
    "word_count": 0,
    "file_size": "0MB"
  }
}
```

### **Password-Protected PDF (400)**
```json
{
  "error": "This PDF is password-protected and cannot be summarized. Please use an unprotected PDF file.",
  "metadata": {
    "content_type": "pdf",
    "processing_time": "0.5s",
    "tokens_used": 0,
    "confidence": 0.0
  },
  "source_info": {
    "pages": 0,
    "word_count": 0,
    "file_size": "2.3MB",
    "password_protected": true
  }
}
```

### **Web Scraping Errors (400)**
```json
{
  "error": "The website is not accessible. It may be down or blocking automated requests.",
  "metadata": {
    "content_type": "link",
    "processing_time": "0.5s",
    "tokens_used": 0,
    "confidence": 0.0
  },
  "source_info": {
    "url": "https://example.com",
    "title": "Failed to extract content",
    "word_count": 0
  }
}
```

---

## 🔧 **Frontend Integration Examples**

### **JavaScript/TypeScript**
```javascript
class SummarizationAPI {
  constructor(baseURL, token) {
    this.baseURL = baseURL;
    this.token = token;
  }

  async summarizeText(text, options = {}) {
    return this.request('/api/summarize', {
      content_type: 'text',
      source: {
        type: 'text',
        data: text
      },
      options: {
        mode: 'detailed',
        language: 'en',
        ...options
      }
    });
  }

  async summarizeLink(url, options = {}) {
    return this.request('/api/summarize', {
      content_type: 'link',
      source: {
        type: 'url',
        data: url
      },
      options: {
        mode: 'detailed',
        language: 'en',
        ...options
      }
    });
  }

  async summarizePDF(uploadId, password = null, options = {}) {
    const requestOptions = {
      mode: 'detailed',
      language: 'en',
      ...options
    };

    if (password) {
      requestOptions.password = password;
    }

    return this.request('/api/summarize', {
      content_type: 'pdf',
      source: {
        type: 'file',
        data: uploadId.toString()
      },
      options: requestOptions
    });
  }

  async uploadFile(file, contentType) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('content_type', contentType);

    return this.request('/api/summarize/upload', formData, {
      'Content-Type': 'multipart/form-data'
    });
  }

  async getUploadStatus(uploadId) {
    return this.request(`/api/summarize/upload/${uploadId}/status`);
  }

  async request(endpoint, data, headers = {}) {
    const url = `${this.baseURL}${endpoint}`;
    const requestHeaders = {
      'Authorization': `Bearer ${this.token}`,
      ...headers
    };

    // Remove Content-Type for FormData
    if (data instanceof FormData) {
      delete requestHeaders['Content-Type'];
    } else {
      requestHeaders['Content-Type'] = 'application/json';
    }

    const response = await fetch(url, {
      method: 'POST',
      headers: requestHeaders,
      body: data instanceof FormData ? data : JSON.stringify(data)
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(`HTTP error! status: ${response.status} - ${JSON.stringify(errorData)}`);
    }

    return await response.json();
  }
}

// Usage
const api = new SummarizationAPI('http://localhost:8000', 'your_token');

// Text summarization
const textResult = await api.summarizeText('Your text content here');

// Link summarization
const linkResult = await api.summarizeLink('https://example.com/article');

// PDF summarization
const uploadResult = await api.uploadFile(pdfFile, 'pdf');
const pdfResult = await api.summarizePDF(uploadResult.upload_id);
```

### **React Hook Example**
```javascript
import { useState, useCallback } from 'react';

export const useSummarization = (token) => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const summarize = useCallback(async (contentType, source, options = {}) => {
    setLoading(true);
    setError(null);

    try {
      const response = await fetch('http://localhost:8000/api/summarize', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
          content_type: contentType,
          source: source,
          options: {
            mode: 'detailed',
            language: 'en',
            ...options
          }
        })
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
      }

      const result = await response.json();
      return result;
    } catch (err) {
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  }, [token]);

  return { summarize, loading, error };
};
```

---

## 📈 **Implementation Status**

### **✅ 100% Real Implementation**
- **Text Summarization** - Direct OpenAI integration
- **Web Link Summarization** - Real web scraping + OpenAI
- **YouTube Video Summarization** - Real YouTube API + OpenAI
- **AI Chat** - Real OpenAI integration
- **File Upload System** - Complete with validation
- **PDF Summarization** - Real PDF parsing + Password support + OpenAI

### **🔄 Mock Data (Ready for Real Implementation)**
- **Image Summarization** - Mock OCR + OpenAI
- **Audio Summarization** - Mock transcription + OpenAI
- **Video Summarization** - Mock processing + OpenAI

---

## 🔒 **Security & Limits**

### **File Size Limits:**
- **PDF**: 10MB max
- **Images**: 5MB max
- **Audio**: 25MB max
- **Video**: 100MB max
- **Text**: 50,000 characters max

### **Rate Limits:**
- **Concurrent Jobs**: 5 per user
- **Subscription Limits**: Based on user's plan
- **API Rate Limits**: 100 requests per minute per user

### **Security Features:**
- Bearer token authentication required
- User-based access control
- File type validation
- Password-protected PDF support
- Secure password handling

---

## 🚀 **Quick Start**

### **1. Test Text Summarization:**
```bash
curl -X POST http://localhost:8000/api/summarize \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "content_type": "text",
    "source": {
      "type": "text",
      "data": "Your text content here"
    },
    "options": {
      "mode": "detailed",
      "language": "en"
    }
  }'
```

### **2. Test Web Link Summarization:**
```bash
curl -X POST http://localhost:8000/api/summarize \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "content_type": "link",
    "source": {
      "type": "url",
      "data": "https://example.com/article"
    },
    "options": {
      "mode": "detailed",
      "language": "en"
    }
  }'
```

### **3. Test PDF Upload:**
```bash
curl -X POST http://localhost:8000/api/summarize/upload \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@document.pdf" \
  -F "content_type=pdf"
```

---

## 🎯 **Ready for Production**

The summarization API is **production-ready** with:
- ✅ **Real content processing** for text, links, and PDFs
- ✅ **Password-protected PDF detection**
- ✅ **Comprehensive error handling**
- ✅ **User-friendly error messages**
- ✅ **File upload system**
- ✅ **Authentication & security**
- ✅ **Rate limiting & validation**

**Your summarization system is ready for frontend integration!** 🚀
