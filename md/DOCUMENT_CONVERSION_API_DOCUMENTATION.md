# Document Conversion API Documentation

**Base URL:** `http://localhost:8000/api`

**Authentication:** All endpoints require Bearer token authentication.

---

## 📄 Document Conversion Endpoints

### 1. Submit Conversion Job

**Endpoint:** `POST /api/file-processing/convert`

**Description:** Convert a document to a different format. The conversion is processed asynchronously, and you'll receive a `job_id` to track the progress.

**Authentication:** Required (Bearer token)

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "file_id": "250",
  "target_format": "pdf",
  "options": {
    "quality": "high",
    "include_metadata": true
  }
}
```

**Request Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `file_id` | string | ✅ Yes | The ID of the uploaded file (from `/api/files/upload`) |
| `target_format` | string | ✅ Yes | Target format: `pdf`, `png`, `jpg`, `jpeg`, `docx`, `doc`, `txt`, `html`, `md`, `pptx`, `ppt`, `xlsx`, `xls` |
| `options` | object | ❌ No | Additional conversion options (see below) |

**Supported Target Formats:**
- `pdf` - Portable Document Format
- `png` - PNG Image
- `jpg` / `jpeg` - JPEG Image
- `docx` - Microsoft Word Document (modern format)
- `doc` - Microsoft Word Document (legacy format)
- `txt` - Plain Text
- `html` - HTML Document
- `md` - Markdown
- `pptx` - PowerPoint (modern format)
- `ppt` - PowerPoint (legacy format)
- `xlsx` - Excel (modern format)
- `xls` - Excel (legacy format)

**Options Object:**
```json
{
  "quality": "high",           // Image quality: "low", "medium", "high"
  "include_metadata": true,    // Include file metadata in conversion
  "dpi": 300,                  // DPI for image conversion (72-600)
  "page_range": "1-10"         // Convert specific pages (e.g., "1-5", "2-10")
}
```

**Response (202 Accepted):**
```json
{
  "success": true,
  "message": "Document conversion job started",
  "job_id": "27ecc3c8-4e19-42e5-9fdf-04089a936a99",
  "status": "pending",
  "poll_url": "http://localhost:8000/api/file-processing/convert/status?job_id=27ecc3c8-4e19-42e5-9fdf-04089a936a99",
  "result_url": "http://localhost:8000/api/file-processing/convert/result?job_id=27ecc3c8-4e19-42e5-9fdf-04089a936a99"
}
```

**Error Responses:**

**400 Bad Request - Validation Error:**
```json
{
  "success": false,
  "error": "Validation failed",
  "details": {
    "file_id": ["The file id field is required."],
    "target_format": ["The target format must be one of: pdf, png, jpg, jpeg, docx, doc, txt, html, md, pptx, ppt, xlsx, xls."]
  }
}
```

**404 Not Found - File Not Found:**
```json
{
  "success": false,
  "error": "File not found",
  "details": "File does not exist"
}
```

**401 Unauthorized:**
```json
{
  "message": "Unauthenticated.",
  "error": "Authentication required"
}
```

---

### 2. Check Conversion Status

**Endpoint:** `GET /api/file-processing/convert/status`

**Description:** Check the status of a document conversion job. Poll this endpoint every 2-5 seconds until the status is `completed` or `failed`.

**Authentication:** Required (Bearer token)

**Request Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `job_id` | string | ✅ Yes | The job ID returned from the convert endpoint |

**Example Request:**
```
GET /api/file-processing/convert/status?job_id=27ecc3c8-4e19-42e5-9fdf-04089a936a99
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "job_id": "27ecc3c8-4e19-42e5-9fdf-04089a936a99",
  "tool_type": "document_conversion",
  "input_type": "file",
  "status": "completed",
  "progress": 100,
  "stage": "completed",
  "error": null,
  "created_at": "2025-11-17T14:16:00.000000Z",
  "updated_at": "2025-11-17T14:16:11.000000Z"
}
```

**Status Values:**

| Status | Description |
|--------|-------------|
| `pending` | Job is queued and waiting to start |
| `running` | Job is currently being processed |
| `completed` | Job finished successfully |
| `failed` | Job encountered an error and failed |

**Progress Stages:**

| Stage | Description | Progress |
|-------|-------------|----------|
| `validating_file` | Validating the input file | 10% |
| `processing_file` | Processing the file | 30% |
| `converting_document` | Converting the document | 60% |
| `monitoring_conversion` | Monitoring conversion progress | 80% |
| `finalizing` | Finalizing the result | 90% |
| `completed` | Conversion completed | 100% |
| `failed` | Conversion failed | - |

**Error Response (404 Not Found):**
```json
{
  "error": "Job not found"
}
```

**Error Response (400 Bad Request):**
```json
{
  "error": "job_id parameter is required"
}
```

**Error Response (401 Unauthorized):**
```json
{
  "message": "Unauthenticated.",
  "error": "Authentication required"
}
```

---

### 3. Get Conversion Result

**Endpoint:** `GET /api/file-processing/convert/result`

**Description:** Get the result of a completed document conversion job. This endpoint only works when the job status is `completed`.

**Authentication:** Required (Bearer token)

**Request Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `job_id` | string | ✅ Yes | The job ID returned from the convert endpoint |

**Example Request:**
```
GET /api/file-processing/convert/result?job_id=27ecc3c8-4e19-42e5-9fdf-04089a936a99
Authorization: Bearer {token}
```

**Response (200 OK - Completed):**
```json
{
  "success": true,
  "job_id": "27ecc3c8-4e19-42e5-9fdf-04089a936a99",
  "tool_type": "document_conversion",
  "input_type": "file",
  "data": {
    "file_path": "converted-files/document.pdf",
    "file_url": "http://localhost:8000/storage/converted-files/document.pdf",
    "original_format": "pdf",
    "target_format": "pdf",
    "file_size": 238820,
    "pages": 1,
    "conversion_time": 8.5,
    "metadata": {
      "quality": "high",
      "include_metadata": true
    }
  }
}
```

**Result Data Structure:**

| Field | Type | Description |
|-------|------|-------------|
| `file_path` | string | Path to the converted file |
| `file_url` | string | Public URL to download the converted file |
| `original_format` | string | Original file format |
| `target_format` | string | Target format after conversion |
| `file_size` | integer | Size of converted file in bytes |
| `pages` | integer | Number of pages (for documents) |
| `conversion_time` | float | Time taken for conversion in seconds |
| `metadata` | object | Conversion metadata and options |

**Error Response (409 Conflict - Job Not Completed):**
```json
{
  "error": "Job not completed",
  "status": "running"
}
```

**Error Response (404 Not Found):**
```json
{
  "error": "Job not found"
}
```

**Error Response (400 Bad Request):**
```json
{
  "error": "job_id parameter is required"
}
```

**Error Response (401 Unauthorized):**
```json
{
  "message": "Unauthenticated.",
  "error": "Authentication required"
}
```

---

## 🔄 Complete Workflow Example

### Step 1: Upload a File
```http
POST /api/files/upload
Authorization: Bearer {token}
Content-Type: multipart/form-data

file: [your file]
```

**Response:**
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "data": {
    "file_id": "250",
    "file_url": "http://localhost:8000/storage/uploads/files/07ecd3f4-cd30-4ba4-af32-62b0b0f24fe3.pdf"
  }
}
```

### Step 2: Submit Conversion Job
```http
POST /api/file-processing/convert
Authorization: Bearer {token}
Content-Type: application/json

{
  "file_id": "250",
  "target_format": "docx",
  "options": {
    "quality": "high"
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Document conversion job started",
  "job_id": "27ecc3c8-4e19-42e5-9fdf-04089a936a99",
  "status": "pending"
}
```

### Step 3: Poll for Status (every 2-5 seconds)
```http
GET /api/file-processing/convert/status?job_id=27ecc3c8-4e19-42e5-9fdf-04089a936a99
Authorization: Bearer {token}
```

**Response (while processing):**
```json
{
  "job_id": "27ecc3c8-4e19-42e5-9fdf-04089a936a99",
  "status": "running",
  "progress": 60,
  "stage": "converting_document"
}
```

**Response (when completed):**
```json
{
  "job_id": "27ecc3c8-4e19-42e5-9fdf-04089a936a99",
  "status": "completed",
  "progress": 100,
  "stage": "completed"
}
```

### Step 4: Get Result
```http
GET /api/file-processing/convert/result?job_id=27ecc3c8-4e19-42e5-9fdf-04089a936a99
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "job_id": "27ecc3c8-4e19-42e5-9fdf-04089a936a99",
  "data": {
    "file_url": "http://localhost:8000/storage/converted-files/document.docx",
    "target_format": "docx",
    "file_size": 45678
  }
}
```

---

## ⚠️ Common Errors and Solutions

### Error: "No converter available for pdf -> pdf"
**Cause:** Trying to convert a file to the same format it already is.

**Solution:** Use a different `target_format`. For example, if the file is already PDF, convert to `docx`, `png`, or `txt`.

### Error: "Conversion failed: 'NoneType' object is not subscriptable"
**Cause:** The microservice returned an unexpected response format.

**Solution:** This is a backend issue. Check microservice logs and ensure the conversion service is running properly.

### Error: "Job not found"
**Cause:** The `job_id` doesn't exist or has expired (jobs are stored for 2 hours in cache, but persisted to database).

**Solution:** 
- Verify the `job_id` is correct
- Check if the job was created successfully
- Jobs are now persisted to database, so they should be available even after cache expiration

### Error: "File not found"
**Cause:** The `file_id` doesn't exist or belongs to another user.

**Solution:**
- Verify the file was uploaded successfully
- Ensure you're using the correct `file_id`
- Make sure the file belongs to the authenticated user

---

## 📋 Supported Format Conversions

The microservice supports **16 input file types** and **11 output file types**, with **100+ conversion combinations**.

### 📥 Supported Input File Types

| Format | Description | Can Convert To |
|--------|-------------|----------------|
| **BMP** | Bitmap images | JPG, PDF, PNG |
| **DOC** | Word documents (legacy) | HTML, JPG, MD, PDF, PNG, PPTX, XLSX |
| **DOCX** | Word documents (modern) | HTML, JPG, MD, PDF, PNG, PPTX, XLSX |
| **GIF** | GIF images | JPG, PDF, PNG |
| **HTM** | HTML files | DOCX, JPG, MD, PDF, PNG |
| **HTML** | HTML files | DOC, DOCX, JPG, MD, PDF, PNG, PPT, PPTX, XLS, XLSX |
| **JPEG** | JPEG images | JPG, PDF, PNG |
| **JPG** | JPEG images | JPG, PDF, PNG |
| **MD** | Markdown files | DOC, DOCX, HTML, JPG, PDF, PNG, PPT, PPTX, XLS, XLSX |
| **PDF** | PDF documents | DOC, DOCX, HTML, JPG, MD, PNG, PPT, PPTX, XLS, XLSX |
| **PNG** | PNG images | JPG, PDF, PNG |
| **PPT** | PowerPoint (legacy) | DOCX, HTML, JPG, MD, PDF, PNG, XLSX |
| **PPTX** | PowerPoint (modern) | DOCX, HTML, JPG, MD, PDF, PNG, XLSX |
| **TXT** | Plain text files | DOC, DOCX, HTML, JPG, MD, PDF, PNG, PPT, PPTX, XLS, XLSX |
| **XLS** | Excel (legacy) | DOCX, HTML, JPG, MD, PDF, PNG, PPTX |
| **XLSX** | Excel (modern) | DOCX, HTML, JPG, MD, PDF, PNG, PPTX |

### 📤 Supported Output File Types

1. **DOC** - Word documents (legacy format)
2. **DOCX** - Word documents (modern format)
3. **HTML** - HTML web pages
4. **JPG** - JPEG images
5. **MD** - Markdown files
6. **PDF** - PDF documents
7. **PNG** - PNG images
8. **PPT** - PowerPoint (legacy format)
9. **PPTX** - PowerPoint (modern format)
10. **XLS** - Excel (legacy format)
11. **XLSX** - Excel (modern format)

### 🔄 Conversion Categories

**Office Documents:**
- DOCX/DOC → PDF, HTML, MD, PNG, JPG, PPTX, XLSX
- PPTX/PPT → PDF, DOCX, HTML, MD, PNG, JPG, XLSX
- XLSX/XLS → PDF, DOCX, HTML, MD, PNG, JPG, PPTX

**PDF Conversions:**
- PDF → DOC, DOCX, HTML, MD, PNG, JPG, PPT, PPTX, XLS, XLSX

**Image Conversions:**
- JPG/JPEG/PNG/BMP/GIF → PDF, PNG, JPG

**Text/Markup Conversions:**
- TXT → DOC, DOCX, HTML, MD, PDF, PNG, JPG, PPT, PPTX, XLS, XLSX
- MD → DOC, DOCX, HTML, PDF, PNG, JPG, PPT, PPTX, XLS, XLSX
- HTML/HTM → DOC, DOCX, MD, PDF, PNG, JPG, PPT, PPTX, XLS, XLSX

**Universal Converters:**
- Any format → JPG (via any_to_jpg converter)
- Any format → PNG (via any_to_png converter)

### ⚠️ Important Notes

**Same-Format Conversions:**
- **NOT SUPPORTED**: Converting a file to the same format (e.g., PDF → PDF) will fail with error: "No converter available for pdf -> pdf"
- Use specific operation endpoints instead (e.g., `/v1/pdf/compress` for PDF operations)

**File Size Limits:**
- **Max file size**: 500 MB
- **Max pages**: 150,000 pages
- **Job TTL**: 24 hours (jobs expire after 24 hours)

---

## 🔐 Authentication

All endpoints require authentication using Laravel Sanctum Bearer tokens.

**Getting a Token:**

1. **Login:**
```http
POST /api/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "user": {
    "id": 21,
    "name": "User Name",
    "email": "user@example.com"
  },
  "token": "285|kujTpxtFaaDoOLXLa3nl5xMyLTRghUGp0NyK0Gqz40912b15"
}
```

2. **Use the token in all subsequent requests:**
```
Authorization: Bearer 285|kujTpxtFaaDoOLXLa3nl5xMyLTRghUGp0NyK0Gqz40912b15
```

---

## 📝 Notes

- **Async Processing:** Conversion jobs are processed asynchronously. Always poll the status endpoint until completion.
- **Polling Frequency:** Poll the status endpoint every 2-5 seconds. Don't poll too frequently to avoid rate limiting.
- **Job Persistence:** Jobs are now persisted to the database, so they remain available even after cache expiration.
- **File Ownership:** You can only convert files that belong to your authenticated user account.
- **Format Validation:** The `target_format` must be one of the supported formats listed above.
- **Same Format Conversion:** Converting a file to the same format (e.g., PDF → PDF) is not supported and will fail.

---

## 🧪 Testing with cURL

### Submit Conversion Job
```bash
curl -X POST http://localhost:8000/api/file-processing/convert \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "file_id": "250",
    "target_format": "docx",
    "options": {}
  }'
```

### Check Status
```bash
curl -X GET "http://localhost:8000/api/file-processing/convert/status?job_id=YOUR_JOB_ID" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Get Result
```bash
curl -X GET "http://localhost:8000/api/file-processing/convert/result?job_id=YOUR_JOB_ID" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 🔍 Checking Conversion Capabilities

To get the current list of supported conversions dynamically, use:

**Endpoint:** `GET /api/file-processing/conversion-capabilities`

**Authentication:** Required (Bearer token)

**Request:**
```http
GET /api/file-processing/conversion-capabilities
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "supported_inputs": ["pdf", "docx", "doc", "pptx", "ppt", "xlsx", "xls", "jpg", "png", "bmp", "gif", "html", "txt", "md"],
    "supported_outputs": ["pdf", "docx", "doc", "pptx", "ppt", "xlsx", "xls", "jpg", "png", "html", "md"],
    "conversion_matrix": {
      "pdf": ["doc", "docx", "html", "jpg", "md", "png", "ppt", "pptx", "xls", "xlsx"],
      "docx": ["html", "jpg", "md", "pdf", "png", "pptx", "xlsx"],
      // ... more combinations
    }
  }
}
```

This endpoint returns all available conversion combinations dynamically based on installed microservice plugins.

---

## 📚 Related Endpoints

- **File Upload:** `POST /api/files/upload` - Upload files before conversion
- **List Files:** `GET /api/files` - List all uploaded files
- **Get File:** `GET /api/files/{id}` - Get file details
- **Content Extraction:** `POST /api/file-processing/extract` - Extract content from documents
- **Conversion Capabilities:** `GET /api/file-processing/conversion-capabilities` - Get supported formats
- **Extraction Capabilities:** `GET /api/file-processing/extraction-capabilities` - Get extraction options
- **Health Check:** `GET /api/file-processing/health` - Check microservice health

---

## 📝 Example Conversions

### Convert PDF to DOCX
```json
{
  "file_id": "250",
  "target_format": "docx",
  "options": {
    "quality": "high"
  }
}
```

### Convert DOCX to PDF
```json
{
  "file_id": "250",
  "target_format": "pdf",
  "options": {
    "page_size": "A4"
  }
}
```

### Convert PDF to PNG (Image)
```json
{
  "file_id": "250",
  "target_format": "png",
  "options": {
    "dpi": 300
  }
}
```

### Convert HTML to PDF
```json
{
  "file_id": "250",
  "target_format": "pdf",
  "options": {
    "page_size": "A4",
    "margin": "1in"
  }
}
```

### Convert Excel to PDF
```json
{
  "file_id": "250",
  "target_format": "pdf",
  "options": {
    "orientation": "landscape"
  }
}
```

### Convert PowerPoint to DOCX
```json
{
  "file_id": "250",
  "target_format": "docx",
  "options": {
    "include_speaker_notes": true
  }
}
```

---

**Last Updated:** November 17, 2025

