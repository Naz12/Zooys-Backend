# File Summarization API Documentation

## Overview
The File Summarization API allows you to extract and summarize content from uploaded files using the document converter microservice and AI processing.

## Base URL
```
http://localhost:8000/api
```

## Authentication
All endpoints require Bearer token authentication:
```
Authorization: Bearer YOUR_TOKEN_HERE
```

---

## 1. Upload File

### Upload File
First, upload a file to get a `file_id` for summarization.

**Endpoint:** `POST /files/upload`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Body:** `multipart/form-data`
- `file` (File): The file to upload
- `metadata` (Object, optional): Additional metadata

**Supported File Types:**
- Documents: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT, HTML, HTM
- Images: JPG, JPEG, PNG, BMP, GIF
- Maximum file size: 50MB

**Example Request:**
```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('metadata', JSON.stringify({
  description: 'Document for summarization'
}));

const response = await fetch('http://localhost:8000/api/files/upload', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN_HERE',
    'Accept': 'application/json'
  },
  body: formData
});

const result = await response.json();
console.log('File uploaded:', result.file_upload.id);
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "file_upload": {
    "id": 180,
    "user_id": 17,
    "original_name": "document.pdf",
    "stored_name": "450f51b0-7b04-49ef-a9f8-7aa5b1449e4d.pdf",
    "file_path": "uploads/files/450f51b0-7b04-49ef-a9f8-7aa5b1449e4d.pdf",
    "mime_type": "application/pdf",
    "file_size": 12345,
    "file_type": "pdf",
    "metadata": {
      "uploaded_at": "2025-10-25T14:07:21.562804Z",
      "client_ip": "::1",
      "user_agent": null
    },
    "is_processed": false,
    "created_at": "2025-10-25T14:07:21.000000Z",
    "updated_at": "2025-10-25T14:07:21.000000Z"
  },
  "file_url": "http://localhost:8000/storage/uploads/files/450f51b0-7b04-49ef-a9f8-7aa5b1449e4d.pdf"
}
```

---

## 2. Summarize File Content

### Summarize File
Extract and summarize content from uploaded files using the document converter microservice.

**Endpoint:** `POST /summarize/async/file`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "file_id": "180",
  "options": {
    "language": "en",
    "format": "detailed",
    "focus": "summary",
    "include_formatting": true,
    "max_pages": 10
  }
}
```

**Parameters:**
- `file_id` (string, required): File ID from upload response
- `options` (object, optional): Summarization options
  - `language` (string): Language code (default: `en`)
  - `format` (string): Output format - `detailed`, `brief`, `bullet` (default: `detailed`)
  - `focus` (string): Focus area - `summary`, `key_points`, `insights` (default: `summary`)
  - `include_formatting` (boolean): Include text formatting (default: `true`)
  - `max_pages` (integer): Maximum pages to process (default: `10`)

**Example Request:**
```javascript
const response = await fetch('http://localhost:8000/api/summarize/async/file', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN_HERE',
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    file_id: '180',
    options: {
      language: 'en',
      format: 'detailed',
      focus: 'summary',
      include_formatting: true,
      max_pages: 10
    }
  })
});

const result = await response.json();
console.log('Summarization started:', result.job_id);
```

**Success Response (202):**
```json
{
  "success": true,
  "message": "Summarization job started",
  "job_id": "bbd1e4d3-dbc6-4eb9-b169-618201333d85",
  "status": "pending",
  "poll_url": "http://localhost:8000/api/status?job_id=bbd1e4d3-dbc6-4eb9-b169-618201333d85",
  "result_url": "http://localhost:8000/api/result?job_id=bbd1e4d3-dbc6-4eb9-b169-618201333d85"
}
```

**Error Response (400):**
```json
{
  "error": "Content extraction failed",
  "details": "Failed to extract content from file"
}
```

**Error Response (404):**
```json
{
  "error": "File not found",
  "details": "File does not exist"
}
```

---

## 3. Check Job Status

### Check Summarization Status
Monitor the progress of your summarization job.

**Endpoint:** `GET /status?job_id={job_id}`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Example Request:**
```javascript
const response = await fetch('http://localhost:8000/api/status?job_id=bbd1e4d3-dbc6-4eb9-b169-618201333d85', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN_HERE',
    'Accept': 'application/json'
  }
});

const status = await response.json();
console.log('Job status:', status.status);
```

**Status Response:**
```json
{
  "job_id": "bbd1e4d3-dbc6-4eb9-b169-618201333d85",
  "status": "completed",
  "progress": 100,
  "stage": "completed",
  "error": null,
  "tool_type": "summarization",
  "created_at": "2025-10-25T14:07:21.000000Z",
  "updated_at": "2025-10-25T14:07:21.000000Z"
}
```

**Status Values:**
- `pending`: Job is queued
- `processing`: Job is running
- `completed`: Job finished successfully
- `failed`: Job failed with error

---

## 4. Get Summarization Result

### Get Summarization Result
Retrieve the completed summarization result.

**Endpoint:** `GET /result?job_id={job_id}`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Example Request:**
```javascript
const response = await fetch('http://localhost:8000/api/result?job_id=bbd1e4d3-dbc6-4eb9-b169-618201333d85', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN_HERE',
    'Accept': 'application/json'
  }
});

const result = await response.json();
console.log('Summary:', result.result.summary);
```

**Success Result:**
```json
{
  "job_id": "bbd1e4d3-dbc6-4eb9-b169-618201333d85",
  "status": "completed",
  "result": {
    "summary": "This document discusses the key principles of artificial intelligence and machine learning. The main topics covered include neural networks, deep learning algorithms, and practical applications in various industries. The document emphasizes the importance of data quality and the need for continuous learning in AI systems.",
    "key_points": [
      "Neural networks form the foundation of modern AI",
      "Deep learning algorithms require large datasets",
      "Data quality is crucial for AI success",
      "Continuous learning is essential for AI systems"
    ],
    "insights": [
      "AI adoption is accelerating across industries",
      "Machine learning models need regular updates",
      "Human-AI collaboration is becoming more important"
    ],
    "metadata": {
      "original_length": 2500,
      "summary_length": 150,
      "compression_ratio": 0.06,
      "processing_time": "2.3s",
      "language_detected": "en",
      "confidence_score": 0.92
    }
  }
}
```

---

## 5. Frontend Implementation

### Complete JavaScript Class
```javascript
class FileSummarizer {
  constructor(token) {
    this.token = token;
    this.baseUrl = 'http://localhost:8000/api';
  }

  async uploadFile(file) {
    const formData = new FormData();
    formData.append('file', file);

    const response = await fetch(`${this.baseUrl}/files/upload`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json'
      },
      body: formData
    });

    const result = await response.json();
    if (!result.success) throw new Error(result.error);
    return result.file_upload.id;
  }

  async summarizeFile(fileId, options = {}) {
    const response = await fetch(`${this.baseUrl}/summarize/async/file`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        file_id: fileId,
        options: {
          language: 'en',
          format: 'detailed',
          focus: 'summary',
          include_formatting: true,
          max_pages: 10,
          ...options
        }
      })
    });

    const result = await response.json();
    if (!result.success) throw new Error(result.error);
    return result.job_id;
  }

  async checkJobStatus(jobId) {
    const response = await fetch(`${this.baseUrl}/status?job_id=${jobId}`, {
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json'
      }
    });

    return await response.json();
  }

  async getJobResult(jobId) {
    const response = await fetch(`${this.baseUrl}/result?job_id=${jobId}`, {
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json'
      }
    });

    return await response.json();
  }

  async pollJobCompletion(jobId, maxAttempts = 60, interval = 2000) {
    for (let i = 0; i < maxAttempts; i++) {
      const status = await this.checkJobStatus(jobId);
      
      if (status.status === 'completed') {
        return await this.getJobResult(jobId);
      } else if (status.status === 'failed') {
        throw new Error(status.error || 'Job failed');
      }
      
      await new Promise(resolve => setTimeout(resolve, interval));
    }
    
    throw new Error('Job timeout');
  }
}
```

### Usage Example
```javascript
// Initialize summarizer
const summarizer = new FileSummarizer('YOUR_TOKEN_HERE');

// Complete workflow
async function summarizeDocument(file) {
  try {
    // 1. Upload file
    const fileId = await summarizer.uploadFile(file);
    console.log('File uploaded:', fileId);

    // 2. Start summarization
    const jobId = await summarizer.summarizeFile(fileId, {
      language: 'en',
      format: 'detailed',
      focus: 'summary'
    });
    console.log('Summarization started:', jobId);

    // 3. Wait for completion
    const result = await summarizer.pollJobCompletion(jobId);
    console.log('Summarization completed:', result);

    return {
      fileId,
      summary: result.result.summary,
      keyPoints: result.result.key_points,
      insights: result.result.insights,
      metadata: result.result.metadata
    };
  } catch (error) {
    console.error('Summarization failed:', error);
    throw error;
  }
}

// Use with file input
document.getElementById('fileInput').addEventListener('change', async (event) => {
  const file = event.target.files[0];
  if (file) {
    try {
      const result = await summarizeDocument(file);
      console.log('Summary:', result.summary);
      console.log('Key Points:', result.keyPoints);
    } catch (error) {
      console.error('Error:', error.message);
    }
  }
});
```

### React Hook Example
```javascript
import { useState, useCallback } from 'react';

export const useFileSummarizer = (token) => {
  const [processing, setProcessing] = useState(false);
  const [progress, setProgress] = useState(0);
  const [result, setResult] = useState(null);

  const summarizeFile = useCallback(async (file, options = {}) => {
    setProcessing(true);
    setProgress(0);
    setResult(null);

    try {
      const summarizer = new FileSummarizer(token);
      
      // Upload file
      setProgress(20);
      const fileId = await summarizer.uploadFile(file);
      
      // Start summarization
      setProgress(40);
      const jobId = await summarizer.summarizeFile(fileId, options);
      
      // Wait for completion
      setProgress(60);
      const result = await summarizer.pollJobCompletion(jobId);
      
      setProgress(100);
      setResult(result.result);
      
      return result.result;
    } catch (error) {
      console.error('Summarization failed:', error);
      throw error;
    } finally {
      setProcessing(false);
    }
  }, [token]);

  return { summarizeFile, processing, progress, result };
};
```

---

## 6. Error Handling

### Common Error Responses

**401 Unauthorized:**
```json
{
  "message": "Unauthenticated.",
  "error": "Authentication required"
}
```

**404 Not Found:**
```json
{
  "error": "File not found",
  "details": "File does not exist"
}
```

**422 Validation Error:**
```json
{
  "message": "The file id field is required.",
  "errors": {
    "file_id": ["The file id field is required."]
  }
}
```

**400 Content Extraction Error:**
```json
{
  "error": "Content extraction failed",
  "details": "Failed to extract content from file"
}
```

**500 Server Error:**
```json
{
  "success": false,
  "error": "Failed to start summarization job: Internal server error"
}
```

---

## 7. Best Practices

1. **File Size Limit:** 50MB maximum per file
2. **Polling Interval:** Use 2-5 second intervals for job status polling
3. **Error Handling:** Always implement proper error handling and user feedback
4. **Progress Indicators:** Show upload and processing progress to users
5. **Token Management:** Store and refresh authentication tokens securely
6. **File Types:** Ensure files are in supported formats before upload
7. **Timeout Handling:** Set reasonable timeouts for long-running operations

---

## 8. Supported File Types

### Documents
- **PDF**: Portable Document Format
- **DOC**: Microsoft Word 97-2003
- **DOCX**: Microsoft Word 2007+
- **PPT**: Microsoft PowerPoint 97-2003
- **PPTX**: Microsoft PowerPoint 2007+
- **XLS**: Microsoft Excel 97-2003
- **XLSX**: Microsoft Excel 2007+
- **TXT**: Plain text
- **HTML**: HyperText Markup Language
- **HTM**: HTML variant

### Images
- **JPG/JPEG**: Joint Photographic Experts Group
- **PNG**: Portable Network Graphics
- **BMP**: Bitmap
- **GIF**: Graphics Interchange Format

---

## Support

For technical support or questions about the File Summarization API, please contact the development team.
