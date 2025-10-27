# File Processing API Documentation

## Overview
This API provides file upload, document conversion, and content extraction capabilities using universal file management and job scheduling.

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

## 1. File Upload

### Upload File
Upload a file to get a `file_id` for use in other endpoints.

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
  description: 'My document'
}));

fetch('http://localhost:8000/api/files/upload', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN_HERE',
    'Accept': 'application/json'
  },
  body: formData
});
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "file_upload": {
    "id": 177,
    "user_id": 17,
    "original_name": "document.pptx",
    "stored_name": "450f51b0-7b04-49ef-a9f8-7aa5b1449e4d.pptx",
    "file_path": "uploads/files/450f51b0-7b04-49ef-a9f8-7aa5b1449e4d.pptx",
    "mime_type": "application/vnd.openxmlformats-officedocument.presentationml.presentation",
    "file_size": 12345,
    "file_type": "pptx",
    "metadata": {
      "uploaded_at": "2025-10-25T14:07:21.562804Z",
      "client_ip": "::1",
      "user_agent": null
    },
    "is_processed": false,
    "created_at": "2025-10-25T14:07:21.000000Z",
    "updated_at": "2025-10-25T14:07:21.000000Z"
  },
  "file_url": "http://localhost:8000/storage/uploads/files/450f51b0-7b04-49ef-a9f8-7aa5b1449e4d.pptx"
}
```

**Error Response (422):**
```json
{
  "error": "Validation failed",
  "messages": {
    "file": ["The file field is required."]
  }
}
```

---

## 2. Document Conversion

### Convert Document
Convert a document to a different format using the file_id from upload.

**Endpoint:** `POST /file-processing/convert`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "file_id": "177",
  "target_format": "pdf",
  "options": {
    "quality": "high",
    "include_metadata": true,
    "page_range": "1-10"
  }
}
```

**Parameters:**
- `file_id` (string, required): File ID from upload response
- `target_format` (string, required): Target format - `pdf`, `png`, `jpg`, `jpeg`, `docx`, `txt`, `html`
- `options` (object, optional): Conversion options

**Example Request:**
```javascript
const response = await fetch('http://localhost:8000/api/file-processing/convert', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN_HERE',
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    file_id: '177',
    target_format: 'pdf',
    options: {
      quality: 'high',
      include_metadata: true
    }
  })
});
```

**Success Response (202):**
```json
{
  "success": true,
  "message": "Document conversion job started",
  "job_id": "c4f6d022-10ba-4bcc-8ddc-2c50553a32a2",
  "status": "pending",
  "poll_url": "http://localhost:8000/api/status?job_id=c4f6d022-10ba-4bcc-8ddc-2c50553a32a2",
  "result_url": "http://localhost:8000/api/result?job_id=c4f6d022-10ba-4bcc-8ddc-2c50553a32a2"
}
```

**Error Response (404):**
```json
{
  "success": false,
  "error": "File not found",
  "details": "File does not exist"
}
```

---

## 3. Content Extraction

### Extract Content
Extract text and metadata from a document using the file_id from upload.

**Endpoint:** `POST /file-processing/extract`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "file_id": "177",
  "extraction_type": "text",
  "language": "eng",
  "include_formatting": true,
  "max_pages": 10,
  "options": {
    "preserve_layout": true,
    "extract_images": false
  }
}
```

**Parameters:**
- `file_id` (string, required): File ID from upload response
- `extraction_type` (string, optional): `text`, `metadata`, `both` (default: `text`)
- `language` (string, optional): Language code - `eng`, `spa`, `fra`, `deu`, `ita`, `por`, `rus`, `chi`, `jpn`, `kor`, `ara` (default: `eng`)
- `include_formatting` (boolean, optional): Include text formatting (default: `false`)
- `max_pages` (integer, optional): Maximum pages to process (default: `10`, max: `1000`)
- `options` (object, optional): Extraction options

**Example Request:**
```javascript
const response = await fetch('http://localhost:8000/api/file-processing/extract', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN_HERE',
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    file_id: '177',
    extraction_type: 'text',
    language: 'eng',
    include_formatting: true,
    max_pages: 10
  })
});
```

**Success Response (202):**
```json
{
  "success": true,
  "message": "Content extraction job started",
  "job_id": "a5de2983-f099-4d28-b8a3-b3740723c931",
  "status": "pending",
  "poll_url": "http://localhost:8000/api/status?job_id=a5de2983-f099-4d28-b8a3-b3740723c931",
  "result_url": "http://localhost:8000/api/result?job_id=a5de2983-f099-4d28-b8a3-b3740723c931"
}
```

---

## 4. Job Status & Results

### Check Job Status
Check the status of a processing job.

**Endpoint:** `GET /status?job_id={job_id}`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Example Request:**
```javascript
const response = await fetch('http://localhost:8000/api/status?job_id=c4f6d022-10ba-4bcc-8ddc-2c50553a32a2', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN_HERE',
    'Accept': 'application/json'
  }
});
```

**Status Response:**
```json
{
  "job_id": "c4f6d022-10ba-4bcc-8ddc-2c50553a32a2",
  "status": "completed",
  "progress": 100,
  "stage": "completed",
  "error": null,
  "tool_type": "document_conversion",
  "created_at": "2025-10-25T14:07:21.000000Z",
  "updated_at": "2025-10-25T14:07:21.000000Z"
}
```

**Status Values:**
- `pending`: Job is queued
- `processing`: Job is running
- `completed`: Job finished successfully
- `failed`: Job failed with error

### Get Job Result
Get the result of a completed job.

**Endpoint:** `GET /result?job_id={job_id}`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Example Request:**
```javascript
const response = await fetch('http://localhost:8000/api/result?job_id=c4f6d022-10ba-4bcc-8ddc-2c50553a32a2', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN_HERE',
    'Accept': 'application/json'
  }
});
```

**Conversion Result:**
```json
{
  "job_id": "c4f6d022-10ba-4bcc-8ddc-2c50553a32a2",
  "status": "completed",
  "result": {
    "converted_file": {
      "id": 178,
      "original_name": "document.pdf",
      "file_path": "uploads/files/converted_450f51b0-7b04-49ef-a9f8-7aa5b1449e4d.pdf",
      "file_size": 15678,
      "file_url": "http://localhost:8000/storage/uploads/files/converted_450f51b0-7b04-49ef-a9f8-7aa5b1449e4d.pdf"
    },
    "conversion_info": {
      "source_format": "pptx",
      "target_format": "pdf",
      "pages_converted": 5,
      "processing_time": "2.5s"
    }
  }
}
```

**Extraction Result:**
```json
{
  "job_id": "a5de2983-f099-4d28-b8a3-b3740723c931",
  "status": "completed",
  "result": {
    "extracted_content": {
      "text": "This is the extracted text content from the document...",
      "metadata": {
        "title": "Document Title",
        "author": "Author Name",
        "pages": 5,
        "word_count": 1250,
        "language": "eng"
      },
      "formatting": {
        "bold": ["Important text"],
        "headings": ["Chapter 1", "Chapter 2"]
      }
    },
    "extraction_info": {
      "extraction_type": "text",
      "pages_processed": 5,
      "processing_time": "1.8s"
    }
  }
}
```

---

## 5. Capabilities & Health

### Get Conversion Capabilities
Get supported file formats and conversion options.

**Endpoint:** `GET /file-processing/conversion-capabilities`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Response:**
```json
{
  "success": true,
  "data": {
    "supported_formats": {
      "input": ["pdf", "doc", "docx", "ppt", "pptx", "xls", "xlsx", "txt", "html", "jpg", "jpeg", "png"],
      "output": ["pdf", "png", "jpg", "jpeg", "docx", "txt", "html"]
    },
    "conversion_options": {
      "quality": ["low", "medium", "high"],
      "page_range": "1-10",
      "include_metadata": true
    }
  }
}
```

### Get Extraction Capabilities
Get supported extraction types and options.

**Endpoint:** `GET /file-processing/extraction-capabilities`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Response:**
```json
{
  "success": true,
  "data": {
    "supported_formats": ["pdf", "doc", "docx", "ppt", "pptx", "xls", "xlsx", "txt", "jpg", "jpeg", "png", "bmp", "gif"],
    "extraction_types": ["text", "metadata", "both"],
    "supported_languages": ["eng", "spa", "fra", "deu", "ita", "por", "rus", "chi", "jpn", "kor", "ara"],
    "max_pages": 1000
  }
}
```

### Health Check
Check if the document processing service is available.

**Endpoint:** `GET /file-processing/health`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Response:**
```json
{
  "success": true,
  "data": {
    "status": "healthy",
    "service": "document_converter",
    "version": "1.0.0",
    "uptime": "2d 5h 30m"
  }
}
```

---

## 6. File Summarization

### Summarize File Content
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

## 7. Frontend Implementation Examples

### Complete Workflow Example
```javascript
class FileProcessor {
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

  async convertDocument(fileId, targetFormat, options = {}) {
    const response = await fetch(`${this.baseUrl}/file-processing/convert`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        file_id: fileId,
        target_format: targetFormat,
        options
      })
    });

    const result = await response.json();
    if (!result.success) throw new Error(result.error);
    return result.job_id;
  }

  async extractContent(fileId, options = {}) {
    const response = await fetch(`${this.baseUrl}/file-processing/extract`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        file_id: fileId,
        ...options
      })
    });

    const result = await response.json();
    if (!result.success) throw new Error(result.error);
    return result.job_id;
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

// Usage Example
const processor = new FileProcessor('YOUR_TOKEN_HERE');

// Complete workflow
async function processFile(file) {
  try {
    // 1. Upload file
    const fileId = await processor.uploadFile(file);
    console.log('File uploaded:', fileId);

    // 2. Convert to PDF
    const convertJobId = await processor.convertDocument(fileId, 'pdf');
    console.log('Conversion started:', convertJobId);

    // 3. Extract content
    const extractJobId = await processor.extractContent(fileId, {
      extraction_type: 'text',
      include_formatting: true
    });
    console.log('Extraction started:', extractJobId);

    // 4. Summarize file content
    const summarizeJobId = await processor.summarizeFile(fileId, {
      language: 'en',
      format: 'detailed',
      focus: 'summary'
    });
    console.log('Summarization started:', summarizeJobId);

    // 5. Wait for conversion to complete
    const convertResult = await processor.pollJobCompletion(convertJobId);
    console.log('Conversion completed:', convertResult);

    // 6. Wait for extraction to complete
    const extractResult = await processor.pollJobCompletion(extractJobId);
    console.log('Extraction completed:', extractResult);

    // 7. Wait for summarization to complete
    const summarizeResult = await processor.pollJobCompletion(summarizeJobId);
    console.log('Summarization completed:', summarizeResult);

    return {
      fileId,
      convertedFile: convertResult.result.converted_file,
      extractedContent: extractResult.result.extracted_content,
      summary: summarizeResult.result.summary
    };
  } catch (error) {
    console.error('Processing failed:', error);
    throw error;
  }
}
```

### React Hook Example
```javascript
import { useState, useCallback } from 'react';

export const useFileProcessor = (token) => {
  const [processing, setProcessing] = useState(false);
  const [progress, setProgress] = useState(0);

  const processFile = useCallback(async (file, options = {}) => {
    setProcessing(true);
    setProgress(0);

    try {
      const processor = new FileProcessor(token);
      
      // Upload file
      setProgress(10);
      const fileId = await processor.uploadFile(file);
      
      // Start conversion if requested
      let convertResult = null;
      if (options.convert) {
        setProgress(30);
        const convertJobId = await processor.convertDocument(fileId, options.targetFormat || 'pdf');
        convertResult = await processor.pollJobCompletion(convertJobId);
      }
      
      // Start extraction if requested
      let extractResult = null;
      if (options.extract) {
        setProgress(60);
        const extractJobId = await processor.extractContent(fileId, options.extractionOptions || {});
        extractResult = await processor.pollJobCompletion(extractJobId);
      }
      
      // Start summarization if requested
      let summarizeResult = null;
      if (options.summarize) {
        setProgress(80);
        const summarizeJobId = await processor.summarizeFile(fileId, options.summarizationOptions || {});
        summarizeResult = await processor.pollJobCompletion(summarizeJobId);
      }
      
      setProgress(100);
      return { fileId, convertResult, extractResult, summarizeResult };
    } finally {
      setProcessing(false);
    }
  }, [token]);

  return { processFile, processing, progress };
};
```

---

## Error Handling

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
  "error": "File not found"
}
```

**422 Validation Error:**
```json
{
  "success": false,
  "error": "Validation failed",
  "details": {
    "file_id": ["The file id field is required."]
  }
}
```

**500 Server Error:**
```json
{
  "success": false,
  "error": "Failed to start document conversion job: Internal server error"
}
```

---

## Rate Limits & Best Practices

1. **File Size Limit:** 50MB maximum per file
2. **Concurrent Jobs:** Process jobs sequentially to avoid overwhelming the system
3. **Polling Interval:** Use 2-5 second intervals for job status polling
4. **Error Handling:** Always implement proper error handling and user feedback
5. **Progress Indicators:** Show upload and processing progress to users
6. **Token Management:** Store and refresh authentication tokens securely

---

## Support

For technical support or questions about the API, please contact the development team.
