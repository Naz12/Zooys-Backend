# PDF Operations & Document Processing API Documentation

Base URL: `http://localhost:8000/api`

Authentication: Bearer token (Laravel Sanctum) required for all POST endpoints.

Status/Result endpoints are public for polling.

---

## 📄 Document Conversion

### Submit Conversion Job

**Endpoint:** `POST /api/file-processing/convert`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "file_id": "204",
  "target_format": "pdf",
  "options": {}
}
```

**Response (202 Accepted):**
```json
{
  "success": true,
  "message": "Document conversion job started",
  "job_id": "ba439eef-0787-49f4-b02a-874cbd39897f",
  "status": "pending",
  "poll_url": "http://localhost:8000/api/status?job_id=ba439eef-0787-49f4-b02a-874cbd39897f",
  "result_url": "http://localhost:8000/api/result?job_id=ba439eef-0787-49f4-b02a-874cbd39897f"
}
```

### Check Conversion Status

**Endpoint:** `GET /api/status/document_conversion/file?job_id={job_id}`

**Or Generic:** `GET /api/status?job_id={job_id}`

**Headers:** Optional Bearer token

**Response (200 OK):**
```json
{
  "job_id": "ba439eef-0787-49f4-b02a-874cbd39897f",
  "status": "completed",
  "progress": 100,
  "stage": "completed",
  "error": null,
  "created_at": "2025-10-30T19:11:19.767298Z",
  "updated_at": "2025-10-30T19:11:30.558834Z"
}
```

**Possible Status Values:**
- `pending` - Job queued, not started
- `running` - Job processing
- `completed` - Job finished successfully
- `failed` - Job failed with error

### Get Conversion Result

**Endpoint:** `GET /api/result/document_conversion/file?job_id={job_id}`

**Or Generic:** `GET /api/result?job_id={job_id}`

**Headers:** Optional Bearer token

**Response (200 OK - Completed):**
```json
{
  "success": true,
  "data": {
    "conversion_result": {
      "job_id": "d4a60592-f9d1-4a29-8193-0a39fda410a2",
      "status": "completed",
      "progress": 100,
      "stage": "completed",
      "output_files": [
        "./pdf\\job_d4a60592-f9d1-4a29-8193-0a39fda410a2\\output.pdf"
      ],
      "download_urls": [
        "http://localhost:8004/v1/files/d4a60592-f9d1-4a29-8193-0a39fda410a2/output.pdf"
      ],
      "job_type": "conversion"
    },
    "converted_file": {
      "path": "converted-files/filename.pdf",
      "url": "http://localhost/storage/converted-files/filename.pdf",
      "filename": "filename.pdf"
    },
    "original_file": {
      "id": "204",
      "filename": "image.jpg",
      "size": 229304
    }
  }
}
```

**Response (409 Conflict - Not Completed):**
```json
{
  "error": "Job not completed",
  "status": "running"
}
```

**Supported Target Formats:** `pdf`, `docx`, `html`, `txt`

---

## 📝 Content Extraction

### Submit Extraction Job

**Endpoint:** `POST /api/file-processing/extract`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "file_id": "204",
  "extraction_type": "text",
  "language": "eng",
  "include_formatting": false,
  "max_pages": 10,
  "options": {
    "request": {
      "content": true,
      "metadata": true,
      "images": false
    }
  }
}
```

**Response (202 Accepted):**
```json
{
  "success": true,
  "message": "Content extraction job started",
  "job_id": "da6b8dad-8105-426a-b7f8-b284950f76e0",
  "status": "pending",
  "poll_url": "http://localhost:8000/api/status?job_id=da6b8dad-8105-426a-b7f8-b284950f76e0",
  "result_url": "http://localhost:8000/api/result?job_id=da6b8dad-8105-426a-b7f8-b284950f76e0"
}
```

### Check Extraction Status

**Endpoint:** `GET /api/status/content_extraction/file?job_id={job_id}`

**Response (200 OK):**
```json
{
  "job_id": "da6b8dad-8105-426a-b7f8-b284950f76e0",
  "tool_type": "content_extraction",
  "input_type": "file",
  "status": "completed",
  "progress": 100,
  "stage": "completed",
  "error": null
}
```

### Get Extraction Result

**Endpoint:** `GET /api/result/content_extraction/file?job_id={job_id}`

**Response (200 OK - Completed):**
```json
{
  "success": true,
  "job_id": "da6b8dad-8105-426a-b7f8-b284950f76e0",
  "tool_type": "content_extraction",
  "input_type": "file",
  "data": {
    "extraction_result": {
      "job_id": "43966675-3d73-4cd4-91c7-9cdec29f2fca",
      "status": "completed",
      "progress": 100,
      "stage": "completed",
      "job_type": "extraction"
    },
    "original_file": {
      "id": 204,
      "filename": null,
      "size": 229304
    },
    "extracted_content": "Extracted text content...",
    "metadata": [],
    "word_count": 150,
    "page_count": 10,
    "language_detected": "eng",
    "extraction_method": "pymupdf"
  }
}
```

**Extraction Types:** `text`, `metadata`, `both`

**Supported Languages:** `eng`, `spa`, `fra`, `deu`, `ita`, `por`, `rus`, `chi`, `jpn`, `kor`, `ara`

---

## 📊 PDF Operations

All PDF operations follow the same pattern:
- **Submit:** `POST /api/pdf/edit/{operation}`
- **Status:** `GET /api/pdf/edit/{operation}/status?job_id={job_id}`
- **Result:** `GET /api/pdf/edit/{operation}/result?job_id={job_id}`

### 1. Merge PDFs

**Submit:** `POST /api/pdf/edit/merge`

**Request Body:**
```json
{
  "file_ids": ["203", "202"],
  "params": {
    "page_order": "as_uploaded",
    "remove_blank_pages": false,
    "add_page_numbers": false
  }
}
```

**Response (202 Accepted):**
```json
{
  "success": true,
  "job_id": "0a5d0c5b-795a-43a2-a6c1-c262df272702",
  "status": "pending",
  "message": "PDF job queued successfully"
}
```

**Status Response:**
```json
{
  "job_id": "0a5d0c5b-795a-43a2-a6c1-c262df272702",
  "status": "completed",
  "progress": 100,
  "stage": "completed",
  "error": null,
  "created_at": "2025-10-30T19:15:02.810688Z",
  "updated_at": "2025-10-30T19:15:17.289761Z"
}
```

**Result Response:**
```json
{
  "success": true,
  "job_id": "0a5d0c5b-795a-43a2-a6c1-c262df272702",
  "operation": "merge",
  "data": {
    "remote_job_id": "1af81593-742c-418e-b560-26a1fac755c0",
    "operation": "merge",
    "result": {
      "job_id": "1af81593-742c-418e-b560-26a1fac755c0",
      "status": "completed",
      "files": ["merged_document.pdf"],
      "download_urls": [
        "http://localhost:8004/v1/files/1af81593-742c-418e-b560-26a1fac755c0/merged_document.pdf"
      ]
    }
  },
  "metadata": {
    "processing_stages": ["validating", "preparing_files", "starting_microservice_job", "monitoring", "fetching_result"],
    "file_count": 2,
    "total_processing_time": 11.839385
  }
}
```

**Parameters:**
- `page_order`: `"as_uploaded"` | `"reverse"`
- `remove_blank_pages`: `true` | `false`
- `add_page_numbers`: `true` | `false`

---

### 2. Split PDF

**Submit:** `POST /api/pdf/edit/split`

**Request Body:**
```json
{
  "file_id": "203",
  "params": {
    "split_points": "1,3,5",
    "title_prefix": "Chapter",
    "author": "Author Name"
  }
}
```

**Result Response:**
```json
{
  "success": true,
  "job_id": "a2b04837-a965-4275-8090-3a7f3602399f",
  "operation": "split",
  "data": {
    "remote_job_id": "faa75ec9-1bad-4748-9430-4763882e2ead",
    "operation": "split",
    "result": {
      "job_id": "faa75ec9-1bad-4748-9430-4763882e2ead",
      "status": "completed",
      "files": [
        "split_1_1-4.pdf",
        "split_documents.zip"
      ],
      "download_urls": [
        "http://localhost:8004/v1/files/faa75ec9-1bad-4748-9430-4763882e2ead/split_1_1-4.pdf",
        "http://localhost:8004/v1/files/faa75ec9-1bad-4748-9430-4763882e2ead/split_documents.zip"
      ]
    }
  }
}
```

**Parameters:**
- `split_points`: Comma-separated page numbers (e.g., `"1,3,5"`)
- `title_prefix`: Optional prefix for split files
- `author`: Optional author name

---

### 3. Compress PDF

**Submit:** `POST /api/pdf/edit/compress`

**Request Body:**
```json
{
  "file_id": "203",
  "params": {
    "compression_level": "medium",
    "quality": 85
  }
}
```

**Parameters:**
- `compression_level`: `"low"` | `"medium"` | `"high"`
- `quality`: Integer 50-100

---

### 4. Watermark

**Submit:** `POST /api/pdf/edit/watermark`

**Request Body:**
```json
{
  "file_id": "203",
  "params": {
    "watermark_type": "text",
    "watermark_content": "CONFIDENTIAL",
    "position_x": 50,
    "position_y": 50,
    "rotation": -45,
    "opacity": 0.3,
    "color": "#000000",
    "font_family": "Arial",
    "font_size": 48,
    "apply_to_all": true,
    "selected_pages": "1,2,3"
  }
}
```

**Parameters:**
- `watermark_type`: `"text"` | `"image"`
- `watermark_content`: Text string or image file path
- `position_x`, `position_y`: Integers 0-100
- `rotation`: Integer (degrees)
- `opacity`: Float 0.0-1.0
- `color`: Hex color code (e.g., `"#000000"`)
- `font_family`: Font name
- `font_size`: Integer
- `apply_to_all`: `true` | `false`
- `selected_pages`: Optional comma-separated page numbers

---

### 5. Page Numbers

**Submit:** `POST /api/pdf/edit/page_numbers`

**Request Body:**
```json
{
  "file_id": "203",
  "params": {
    "position": "bottom_right",
    "format_type": "arabic",
    "font_size": 12,
    "page_ranges": []
  }
}
```

**Parameters:**
- `position`: `"bottom_right"` | `"bottom_left"` | `"top_right"` | `"top_left"` | `"bottom_center"`
- `format_type`: `"arabic"` | `"roman_lower"` | `"roman_upper"`
- `font_size`: Integer 6-72
- `page_ranges`: Array of `{"start": 1, "end": 3}` or empty array for all pages

---

### 6. Annotate

**Submit:** `POST /api/pdf/edit/annotate`

**Request Body:**
```json
{
  "file_id": "203",
  "params": {
    "annotations": [
      {
        "type": "note",
        "page_number": 1,
        "x": 60,
        "y": 60,
        "text": "Visible note"
      },
      {
        "type": "highlight",
        "page_number": 1,
        "x": 60,
        "y": 100,
        "width": 350,
        "height": 35,
        "color": [1, 1, 0]
      }
    ]
  }
}
```

**Annotation Types:**
- `"note"` - Requires: `type`, `page_number`, `x`, `y`, `text`
- `"highlight"` - Requires: `type`, `page_number`, `x`, `y`, `width`, `height`, `color`
- `"text"` - Requires: `type`, `page_number`, `x`, `y`, `width`, `height`, `text`

**Color Format:** RGB array `[r, g, b]` with values 0.0-1.0

---

### 7. Protect (Password)

**Submit:** `POST /api/pdf/edit/protect`

**Request Body:**
```json
{
  "file_id": "203",
  "params": {
    "password": "your_password",
    "permissions": ["print", "copy"]
  }
}
```

**Parameters:**
- `password`: String (required)
- `permissions`: Array of strings (optional): `"print"`, `"copy"`, `"modify"`

---

### 8. Unlock (Remove Password)

**Submit:** `POST /api/pdf/edit/unlock`

**Request Body:**
```json
{
  "file_id": "203",
  "params": {
    "password": "current_password"
  }
}
```

---

### 9. Preview/Thumbnails

**Submit:** `POST /api/pdf/edit/preview`

**Request Body:**
```json
{
  "file_id": "203",
  "params": {
    "page_numbers": "1,2",
    "thumbnail_width": 200,
    "thumbnail_height": 200,
    "zoom": 2.0
  }
}
```

**Result Response:**
```json
{
  "success": true,
  "data": {
    "result": {
      "job_id": "...",
      "files": [
        "page_1_thumb.png",
        "page_2_thumb.png"
      ],
      "download_urls": [
        "http://localhost:8004/v1/files/.../page_1_thumb.png",
        "http://localhost:8004/v1/files/.../page_2_thumb.png"
      ]
    }
  }
}
```

**Parameters:**
- `page_numbers`: Comma-separated page numbers (optional, all pages if empty)
- `thumbnail_width`: Integer 50-1000 (default: 200)
- `thumbnail_height`: Integer 50-1000 (default: 200)
- `zoom`: Float 0.5-5.0 (default: 2.0)

---

### 10. Batch Processing

**Submit:** `POST /api/pdf/edit/batch`

**Request Body:**
```json
{
  "file_ids": ["203", "202"],
  "params": {
    "operation": "compress",
    "options": {
      "compression_level": "medium"
    }
  }
}
```

**Note:** Batch endpoint may return 404 if not enabled on microservice.

---

### 11. Edit PDF (Reorder/Remove Pages)

**Submit:** `POST /api/pdf/edit/edit_pdf`

**Request Body:**
```json
{
  "file_id": "203",
  "params": {
    "page_order": "as_is",
    "remove_blank_pages": true
  }
}
```

**Parameters:**
- `page_order`: 
  - `"reverse"` - Reverse all pages
  - `"as_is"` - Keep original order
  - `"1,3,2,5"` - Custom comma-separated page order
- `remove_blank_pages`: `true` | `false` (default: true)
- `remove_pages`: Optional comma-separated page numbers to remove (e.g., `"1,5,10"`)

**Example - Custom Order:**
```json
{
  "file_id": "203",
  "params": {
    "page_order": "3,1,2,5",
    "remove_pages": "4",
    "remove_blank_pages": true
  }
}
```

---

## 🔄 Common Response Patterns

### Status Endpoints (All Operations)

**While Processing:**
```json
{
  "job_id": "...",
  "status": "running",
  "progress": 50,
  "stage": "monitoring",
  "error": null
}
```

**On Completion:**
```json
{
  "job_id": "...",
  "status": "completed",
  "progress": 100,
  "stage": "completed",
  "error": null,
  "created_at": "2025-10-30T19:15:02.810688Z",
  "updated_at": "2025-10-30T19:15:17.289761Z"
}
```

**On Failure:**
```json
{
  "job_id": "...",
  "status": "failed",
  "progress": 80,
  "stage": "failed",
  "error": "Conversion failed",
  "created_at": "...",
  "updated_at": "..."
}
```

### Result Endpoints (All Operations)

**Success Response:**
```json
{
  "success": true,
  "job_id": "...",
  "operation": "split",
  "data": {
    "remote_job_id": "...",
    "operation": "split",
    "result": {
      "download_urls": [
        "http://localhost:8004/v1/files/{remote_job_id}/output.pdf"
      ],
      "files": ["output.pdf"]
    }
  },
  "metadata": {
    "processing_stages": [...],
    "file_count": 1,
    "total_processing_time": 11.84
  }
}
```

**Not Completed (409):**
```json
{
  "error": "Job not completed",
  "status": "running"
}
```

**Job Not Found (404):**
```json
{
  "error": "Job not found"
}
```

---

## 📥 Downloading Generated Files

All generated files are available via `download_urls` in the result response. These URLs point directly to the microservice:

```
http://localhost:8004/v1/files/{remote_job_id}/{filename}
```

These URLs:
- ✅ Work directly in browsers (`<img>`, `<a>`, `fetch`)
- ✅ No authentication required (public endpoints)
- ✅ Files expire 24 hours after job completion
- ✅ Use UUID-based job IDs for security

**Example Usage:**
```javascript
// Direct download
fetch('http://localhost:8004/v1/files/abc123/output.pdf')
  .then(response => response.blob())
  .then(blob => {
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'output.pdf';
    a.click();
  });
```

---

## ⚠️ Error Handling

### Common Error Responses

**400 Bad Request:**
```json
{
  "success": false,
  "error": "file_id is required"
}
```

**401 Unauthorized:**
```json
{
  "error": "Unauthenticated"
}
```

**404 Not Found:**
```json
{
  "error": "Job not found"
}
```

**409 Conflict:**
```json
{
  "error": "Job not completed",
  "status": "running"
}
```

**410 Gone (Microservice):**
```json
{
  "detail": "Conversion job has expired. Jobs expire 24 hours after completion. Please create a new job."
}
```

**500 Internal Server Error:**
```json
{
  "error": "Conversion failed",
  "details": "..."
}
```

---

## 📋 File Upload

Before using any endpoint, upload files first:

**Endpoint:** `POST /api/files/upload`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request:**
```
file: [Select file]
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "204",
    "original_filename": "document.pdf",
    "file_size": 229304,
    "file_type": "pdf",
    "file_path": "files/204/document.pdf",
    "created_at": "2025-10-30T19:00:00.000000Z"
  }
}
```

Use the returned `id` as `file_id` or in `file_ids` array for all operations.

---

## 🔑 Quick Reference

### All Endpoints Summary

| Operation | Submit | Status | Result |
|-----------|--------|--------|--------|
| Convert | `POST /api/file-processing/convert` | `GET /api/status/document_conversion/file?job_id={id}` | `GET /api/result/document_conversion/file?job_id={id}` |
| Extract | `POST /api/file-processing/extract` | `GET /api/status/content_extraction/file?job_id={id}` | `GET /api/result/content_extraction/file?job_id={id}` |
| Merge | `POST /api/pdf/edit/merge` | `GET /api/pdf/edit/merge/status?job_id={id}` | `GET /api/pdf/edit/merge/result?job_id={id}` |
| Split | `POST /api/pdf/edit/split` | `GET /api/pdf/edit/split/status?job_id={id}` | `GET /api/pdf/edit/split/result?job_id={id}` |
| Compress | `POST /api/pdf/edit/compress` | `GET /api/pdf/edit/compress/status?job_id={id}` | `GET /api/pdf/edit/compress/result?job_id={id}` |
| Watermark | `POST /api/pdf/edit/watermark` | `GET /api/pdf/edit/watermark/status?job_id={id}` | `GET /api/pdf/edit/watermark/result?job_id={id}` |
| Page Numbers | `POST /api/pdf/edit/page_numbers` | `GET /api/pdf/edit/page_numbers/status?job_id={id}` | `GET /api/pdf/edit/page_numbers/result?job_id={id}` |
| Annotate | `POST /api/pdf/edit/annotate` | `GET /api/pdf/edit/annotate/status?job_id={id}` | `GET /api/pdf/edit/annotate/result?job_id={id}` |
| Protect | `POST /api/pdf/edit/protect` | `GET /api/pdf/edit/protect/status?job_id={id}` | `GET /api/pdf/edit/protect/result?job_id={id}` |
| Unlock | `POST /api/pdf/edit/unlock` | `GET /api/pdf/edit/unlock/status?job_id={id}` | `GET /api/pdf/edit/unlock/result?job_id={id}` |
| Preview | `POST /api/pdf/edit/preview` | `GET /api/pdf/edit/preview/status?job_id={id}` | `GET /api/pdf/edit/preview/result?job_id={id}` |
| Batch | `POST /api/pdf/edit/batch` | `GET /api/pdf/edit/batch/status?job_id={id}` | `GET /api/pdf/edit/batch/result?job_id={id}` |
| Edit PDF | `POST /api/pdf/edit/edit_pdf` | `GET /api/pdf/edit/edit_pdf/status?job_id={id}` | `GET /api/pdf/edit/edit_pdf/result?job_id={id}` |

---

## 🚀 Testing Workflow

1. **Upload File:** `POST /api/files/upload` → Get `file_id`
2. **Submit Job:** `POST /api/pdf/edit/{operation}` or convert/extract → Get `job_id`
3. **Poll Status:** `GET /api/pdf/edit/{operation}/status?job_id={job_id}` (every 2-5 seconds)
4. **Get Result:** `GET /api/pdf/edit/{operation}/result?job_id={job_id}` (when status = "completed")
5. **Download File:** Use `download_urls` from result response

---

*Last Updated: 2025-10-30*
*API Version: 1.0*

