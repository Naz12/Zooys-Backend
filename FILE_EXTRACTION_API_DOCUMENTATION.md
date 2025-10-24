# File Processing and Conversion API Documentation

## Overview

The File Processing and Conversion API provides endpoints for document conversion and content extraction using the document converter microservice. It supports 13+ file formats and runs with job scheduling for async processing.

## Base URL

```
http://localhost:8000/api
```

## Authentication

All endpoints require authentication using Bearer token:

```
Authorization: Bearer {your_token}
```

## Universal Endpoints

The API now uses universal status and result endpoints that work for all job types:

- **Status**: `GET /api/status/{jobId}` - Check status of any job
- **Result**: `GET /api/result/{jobId}` - Get result of any completed job

These endpoints work for:
- Document conversion jobs
- Content extraction jobs  
- Summarization jobs
- Math problem solving jobs
- Flashcard generation jobs
- Presentation generation jobs
- Document chat jobs

## Endpoints

### 1. Health Check

**GET** `/file-processing/health`

Check the microservice health and status.

#### Response
```json
{
  "success": true,
  "data": {
    "status": "healthy",
    "redis": "ok",
    "engines": {
      "libreoffice": "25.2.6.2 (installed)",
      "ghostscript": "10.06.0",
      "wkhtmltopdf": "wkhtmltopdf 0.12.6 (with patched qt)",
      "tesseract": "tesseract v5.5.0.20241111",
      "poppler": "pdftoppm version 23.08.0"
    },
    "disk_space": {
      "total_gb": 236.48,
      "free_gb": 24.32,
      "used_percent": 89.68
    },
    "version": "1.0.0"
  }
}
```

### 2. Get Conversion Capabilities

**GET** `/file-processing/conversion-capabilities`

Get all supported conversion capabilities.

#### Response
```json
{
  "success": true,
  "data": {
    "conversions": [
      {
        "from_format": "docx",
        "to_format": "pdf",
        "name": "docx_to_pdf",
        "description": "Convert Word documents to PDF using LibreOffice"
      }
    ],
    "limits": {
      "max_file_mb": 500,
      "max_pages": 150000
    }
  }
}
```

### 3. Get Extraction Capabilities

**GET** `/file-processing/extraction-capabilities`

Get supported file types and extraction options.

#### Response
```json
{
  "success": true,
  "data": {
    "supported_formats": [
      {
        "format": "pdf",
        "description": "PDF documents",
        "methods": ["tesseract_ocr", "pypdf2"]
      }
    ],
    "extraction_types": [
      {
        "type": "text",
        "description": "Extract text content only"
      }
    ],
    "languages": [
      {"code": "eng", "name": "English"},
      {"code": "spa", "name": "Spanish"}
    ],
    "limits": {
      "max_file_mb": 500,
      "max_pages": 150000,
      "supported_languages": 12
    }
  }
}
```

### 4. Convert Document

**POST** `/file-processing/convert`

Convert a document to the specified format.

#### Request

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Body (form-data):**
| Field | Type | Required | Description | Example |
|-------|------|----------|-------------|---------|
| `file` | File | ✅ Yes | Document to convert | Select any supported file |
| `target_format` | Text | ✅ Yes | Output format | `pdf`, `png`, `jpg` |
| `options` | Text | ❌ No | JSON string with options | `{"quality": "high"}` |

#### Supported Conversions

| From Format | To Format | Plugin Used | Options |
|-------------|-----------|-------------|---------|
| DOCX, DOC | PDF | LibreOffice | `{"quality": "high"}` |
| PPTX, PPT | PDF | LibreOffice | `{"quality": "high"}` |
| XLSX, XLS | PDF | LibreOffice | `{"quality": "high"}` |
| HTML | PDF | wkhtmltopdf | `{"page_size": "A4"}` |
| PNG, JPG | PDF | img2pdf | `{"compression": "high"}` |
| PDF | PNG | PyMuPDF | `{"dpi": 300}` |
| PDF | JPG | PyMuPDF | `{"dpi": 300}` |

#### Response
```json
{
  "success": true,
  "message": "Document conversion job started",
  "job_id": "uuid-string",
  "status": "pending",
  "poll_url": "http://localhost:8000/api/status/{job_id}",
  "result_url": "http://localhost:8000/api/result/{job_id}"
}
```

### 5. Extract Content

**POST** `/file-processing/extract`

Extract text content from documents.

#### Request

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Body (form-data):**
| Field | Type | Required | Description | Example |
|-------|------|----------|-------------|---------|
| `file` | File | ✅ Yes | Document to extract from | Select any supported file |
| `extraction_type` | Text | ❌ No | Type of extraction | `text`, `metadata`, `both` |
| `language` | Text | ❌ No | Language for OCR | `eng`, `spa`, `fra` |
| `include_formatting` | Boolean | ❌ No | Include formatting | `true`, `false` |
| `max_pages` | Integer | ❌ No | Maximum pages to process | `10` |

#### Supported Input Formats

| Format | Description | Extraction Method |
|--------|-------------|-------------------|
| PDF | PDF documents | Tesseract OCR, PyPDF2 |
| DOCX | Word documents | python-docx |
| DOC | Word documents | LibreOffice |
| TXT | Plain text files | Direct read |
| JPG, PNG, BMP, GIF | Images | Tesseract OCR |
| XLSX, XLS | Excel spreadsheets | pandas |
| PPTX, PPT | PowerPoint presentations | python-pptx |

#### Response
```json
{
  "success": true,
  "message": "Content extraction job started",
  "job_id": "uuid-string",
  "status": "pending",
  "poll_url": "http://localhost:8000/api/status/{job_id}",
  "result_url": "http://localhost:8000/api/result/{job_id}"
}
```

### 6. Check Job Status

**GET** `/status/{jobId}`

Check the status of any job (conversion, extraction, summarization, etc.).

#### Request
```
Authorization: Bearer {token}
```

#### Response
```json
{
  "job_id": "uuid-string",
  "status": "running",
  "progress": 60,
  "stage": "converting_document",
  "error": null,
  "tool_type": "document_conversion",
  "created_at": "2025-01-30T10:00:00.000000Z",
  "updated_at": "2025-01-30T10:05:00.000000Z"
}
```

#### Status Values

| Status | Description |
|--------|-------------|
| `pending` | Job is waiting to be processed |
| `running` | Job is currently being processed |
| `completed` | Job completed successfully |
| `failed` | Job failed with error |

#### Stage Values

**Document Conversion:**
- `validating_file` → `processing_file` → `converting_document` → `monitoring_conversion` → `finalizing`

**Content Extraction:**
- `validating_file` → `processing_file` → `extracting_content` → `monitoring_extraction` → `finalizing`

### 7. Get Job Result

**GET** `/result/{jobId}`

Get the result of a completed job.

#### Request
```
Authorization: Bearer {token}
```

#### Response for Conversion Jobs
```json
{
  "success": true,
  "data": {
    "conversion_result": {
      "job_id": "uuid-string",
      "job_type": "conversion",
      "status": "completed",
      "files": ["output.pdf"],
      "file_paths": ["/path/to/output.pdf"]
    },
    "converted_file": {
      "path": "converted-files/output.pdf",
      "url": "http://localhost:8000/storage/converted-files/output.pdf",
      "filename": "output.pdf"
    },
    "original_file": {
      "id": 123,
      "filename": "document.docx",
      "size": 1024
    }
  }
}
```

#### Response for Extraction Jobs
```json
{
  "success": true,
  "data": {
    "extraction_result": {
      "job_id": "uuid-string",
      "job_type": "extraction",
      "status": "completed",
      "content": "Extracted text content...",
      "metadata": {
        "file_name": "document.pdf",
        "file_size": 1024,
        "page_count": 1,
        "language_detected": "eng"
      },
      "word_count": 150,
      "page_count": 1,
      "language_detected": "eng",
      "extraction_method": "pypdf2"
    },
    "original_file": {
      "id": 123,
      "filename": "document.pdf",
      "size": 1024
    },
    "extracted_content": "Extracted text content...",
    "metadata": {
      "file_name": "document.pdf",
      "file_size": 1024,
      "page_count": 1,
      "language_detected": "eng"
    },
    "word_count": 150,
    "page_count": 1,
    "language_detected": "eng",
    "extraction_method": "pypdf2"
  }
}
```

## Error Handling

### Common Error Codes

| Code | Description | Solution |
|------|-------------|----------|
| 400 | Bad Request | Check request format and parameters |
| 401 | Unauthorized | Verify authentication token |
| 404 | Not Found | Check job ID or endpoint |
| 409 | Conflict | Job not completed yet |
| 413 | Payload Too Large | Reduce file size |
| 422 | Validation Error | Check request parameters |
| 500 | Internal Server Error | Contact support |

### Error Response Format
```json
{
  "success": false,
  "error": "Error description",
  "details": "Additional error details"
}
```

## Rate Limits

- **File Size**: Maximum 50MB per file
- **Pages**: Maximum 150,000 pages per document
- **Concurrent Jobs**: Limited by Redis queue capacity
- **Timeout**: 5 minutes for conversion/extraction jobs

## Examples

### Complete Workflow: Convert DOCX to PDF

1. **Upload and Convert:**
```bash
curl -X POST http://localhost:8000/api/file-processing/convert \
  -H "Authorization: Bearer {token}" \
  -F "file=@document.docx" \
  -F "target_format=pdf" \
  -F "options={\"quality\": \"high\"}"
```

2. **Check Status:**
```bash
curl -X GET http://localhost:8000/api/status/{job_id} \
  -H "Authorization: Bearer {token}"
```

3. **Get Result:**
```bash
curl -X GET http://localhost:8000/api/result/{job_id} \
  -H "Authorization: Bearer {token}"
```

### Complete Workflow: Extract Text from PDF

1. **Upload and Extract:**
```bash
curl -X POST http://localhost:8000/api/file-processing/extract \
  -H "Authorization: Bearer {token}" \
  -F "file=@document.pdf" \
  -F "extraction_type=text" \
  -F "language=eng" \
  -F "max_pages=10"
```

2. **Check Status:**
```bash
curl -X GET http://localhost:8000/api/status/{job_id} \
  -H "Authorization: Bearer {token}"
```

3. **Get Result:**
```bash
curl -X GET http://localhost:8000/api/result/{job_id} \
  -H "Authorization: Bearer {token}"
```

## Integration with Existing System

The file extraction endpoints integrate seamlessly with the existing Laravel application:

- **Authentication**: Uses the same Sanctum token system
- **File Management**: Integrates with UniversalFileManagementModule
- **Job Scheduling**: Uses UniversalJobService for async processing
- **Storage**: Files are stored using Laravel's storage system
- **Logging**: All operations are logged using Laravel's logging system

## Configuration

Add these environment variables to your `.env` file:

```env
DOCUMENT_CONVERTER_URL=http://localhost:8004
DOCUMENT_CONVERTER_API_KEY=test-api-key-123
DOCUMENT_CONVERTER_TIMEOUT=300
```

## Support

For issues or questions:
- Check the health endpoint for microservice status
- Verify all required tools are installed in the microservice
- Ensure proper authentication headers
- Check file size and format limitations
- Review job status and error messages for debugging
