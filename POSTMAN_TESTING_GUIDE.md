# 📋 Postman Testing Guide - PDF Operations

## 🚀 Quick Start - Copy & Paste Ready!

All endpoints are formatted for easy copy-paste into Postman:
1. Copy the URL from code blocks
2. Select the HTTP method (GET/POST)
3. Add headers (Authorization + Content-Type for POST)
4. Copy-paste JSON body for POST requests
5. Hit Send!

**Replace these placeholders:**
- `{{bearer_token}}` → Your actual Bearer token
- `{{file_id}}` → File ID from upload response (e.g., 204)
- `{{job_id}}` → Job ID from submit response (auto-saved if using test scripts)

### 📋 All Endpoints Quick Reference

| Operation | Submit (POST) | Status (GET) | Result (GET) |
|-----------|---------------|--------------|--------------|
| **File Upload** | `localhost:8000/api/files/upload` | - | - |
| **Merge** | `localhost:8000/api/pdf/edit/merge` | `localhost:8000/api/pdf/edit/merge/status?job_id={{job_id}}` | `localhost:8000/api/pdf/edit/merge/result?job_id={{job_id}}` |
| **Split** | `localhost:8000/api/pdf/edit/split` | `localhost:8000/api/pdf/edit/split/status?job_id={{job_id}}` | `localhost:8000/api/pdf/edit/split/result?job_id={{job_id}}` |
| **Compress** | `localhost:8000/api/pdf/edit/compress` | `localhost:8000/api/pdf/edit/compress/status?job_id={{job_id}}` | `localhost:8000/api/pdf/edit/compress/result?job_id={{job_id}}` |
| **Watermark** | `localhost:8000/api/pdf/edit/watermark` | `localhost:8000/api/pdf/edit/watermark/status?job_id={{job_id}}` | `localhost:8000/api/pdf/edit/watermark/result?job_id={{job_id}}` |
| **Page Numbers** | `localhost:8000/api/pdf/edit/page_numbers` | `localhost:8000/api/pdf/edit/page_numbers/status?job_id={{job_id}}` | `localhost:8000/api/pdf/edit/page_numbers/result?job_id={{job_id}}` |
| **Annotate** | `localhost:8000/api/pdf/edit/annotate` | `localhost:8000/api/pdf/edit/annotate/status?job_id={{job_id}}` | `localhost:8000/api/pdf/edit/annotate/result?job_id={{job_id}}` |
| **Protect** | `localhost:8000/api/pdf/edit/protect` | `localhost:8000/api/pdf/edit/protect/status?job_id={{job_id}}` | `localhost:8000/api/pdf/edit/protect/result?job_id={{job_id}}` |
| **Unlock** | `localhost:8000/api/pdf/edit/unlock` | `localhost:8000/api/pdf/edit/unlock/status?job_id={{job_id}}` | `localhost:8000/api/pdf/edit/unlock/result?job_id={{job_id}}` |
| **Preview** | `localhost:8000/api/pdf/edit/preview` | `localhost:8000/api/pdf/edit/preview/status?job_id={{job_id}}` | `localhost:8000/api/pdf/edit/preview/result?job_id={{job_id}}` |
| **Edit PDF** | `localhost:8000/api/pdf/edit/edit_pdf` | `localhost:8000/api/pdf/edit/edit_pdf/status?job_id={{job_id}}` | `localhost:8000/api/pdf/edit/edit_pdf/result?job_id={{job_id}}` |
| **Batch** | `localhost:8000/api/pdf/edit/batch` | `localhost:8000/api/pdf/edit/batch/status?job_id={{job_id}}` | `localhost:8000/api/pdf/edit/batch/result?job_id={{job_id}}` |
| **Convert** | `localhost:8000/api/file-processing/convert` | `localhost:8000/api/jobs/status?job_id={{job_id}}&operation=document_conversion` | `localhost:8000/api/jobs/result?job_id={{job_id}}&operation=document_conversion` |
| **Extract** | `localhost:8000/api/file-processing/extract` | `localhost:8000/api/jobs/status?job_id={{job_id}}&operation=content_extraction` | `localhost:8000/api/jobs/result?job_id={{job_id}}&operation=content_extraction` |

---

## 🔧 Setup

### Base Configuration

**Base URL:** `localhost:8000/api`

**Authentication:** All endpoints require Bearer token authentication

**Headers (for all requests):**
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
```

### Postman Environment Variables (Optional but Recommended)

**Option 1: Create Environment** (Recommended)
1. Click the gear icon ⚙️ in top right
2. Click "Add" to create new environment
3. Add these variables:

| Variable | Initial Value | Current Value |
|----------|---------------|---------------|
| `base_url` | `localhost:8000/api` | `localhost:8000/api` |
| `bearer_token` | `YOUR_TOKEN_HERE` | `YOUR_TOKEN_HERE` |
| `file_id` | `204` | `204` |
| `job_id` | (empty) | (auto-saved by test scripts) |

**Option 2: Manual Replace**
Simply replace these in each request:
- Replace `{{bearer_token}}` → Your actual token (e.g., `207|vhs65UeUfk2kpYoTpJX3zIClf4zQyixWZdkbfPYXf7ae0dfe`)
- Replace `{{file_id}}` → Your file ID from upload (e.g., `204`)
- Replace `{{job_id}}` → Your job ID from submit response

---

## 📂 File Upload (Prerequisite)

Before testing PDF operations, you need to upload files first.

### Upload Single File

**Method:** `POST`

**URL:** 
```
localhost:8000/api/files/upload
```
Or with variables:
```
{{base_url}}/files/upload
```

**Headers:**
```
Authorization: Bearer {{bearer_token}}
```

**Body:** Select `form-data` tab
```
Key: file    Type: File    Value: [Select PDF file]
```

**Response:**
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "file_upload": {
    "id": 204,
    "user_id": 17,
    "original_name": "test.pdf",
    "stored_name": "uuid.pdf",
    "file_path": "uploads/files/uuid.pdf",
    "mime_type": "application/pdf",
    "file_size": 680366,
    "file_type": "pdf",
    "is_processed": false,
    "created_at": "2025-10-31T12:43:52.000000Z",
    "updated_at": "2025-10-31T12:43:52.000000Z"
  },
  "file_url": "http://localhost:8000/storage/uploads/files/uuid.pdf"
}
```

**Save file_id:** `204` (use this for PDF operations)

### Upload Multiple Files

**Method:** `POST`

**URL:** 
```
localhost:8000/api/files/upload
```

**Headers:**
```
Authorization: Bearer {{bearer_token}}
```

**Body:** Select `form-data` tab
```
Key: files[]    Type: File    Value: [Select PDF file 1]
Key: files[]    Type: File    Value: [Select PDF file 2]
Key: files[]    Type: File    Value: [Select PDF file 3]
```

**Response:**
```json
{
  "success": true,
  "message": "3 file(s) uploaded successfully",
  "uploaded_count": 3,
  "error_count": 0,
  "file_uploads": [
    {
      "file_upload": {"id": 205, ...},
      "file_url": "..."
    },
    {
      "file_upload": {"id": 206, ...},
      "file_url": "..."
    },
    {
      "file_upload": {"id": 207, ...},
      "file_url": "..."
    }
  ],
  "errors": []
}
```

---

## 🔀 PDF Merge

Combine multiple PDF files into one.

### 1. Submit Merge Job

**Method:** `POST`

**URL:**
```
localhost:8000/api/pdf/edit/merge
```

**Headers:**
```
Authorization: Bearer {{bearer_token}}
Content-Type: application/json
```

**Body:** Select `raw` → `JSON`
```json
{
  "file_ids": [204, 205, 206],
  "options": {
    "page_order": "as_uploaded",
    "remove_blank_pages": false,
    "add_page_numbers": false
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "PDF merge job created successfully",
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "queued",
  "operation": "merge"
}
```

**📌 Save the `job_id` from response!**

### 2. Check Status

**Method:** `GET`

**URL:**
```
localhost:8000/api/pdf/edit/merge/status?job_id={{job_id}}
```

**Headers:**
```
Authorization: Bearer {{bearer_token}}
```

**Response (Processing):**
```json
{
  "success": true,
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "processing",
  "progress": 50,
  "stage": "processing",
  "operation": "merge",
  "created_at": "2025-10-31T13:00:00.000000Z"
}
```

**Response (Completed):**
```json
{
  "success": true,
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "completed",
  "progress": 100,
  "stage": "completed",
  "operation": "merge",
  "created_at": "2025-10-31T13:00:00.000000Z",
  "completed_at": "2025-10-31T13:00:15.000000Z"
}
```

### 3. Get Result

**Method:** `GET`

**URL:**
```
localhost:8000/api/pdf/edit/merge/result?job_id={{job_id}}
```

**Headers:**
```
Authorization: Bearer {{bearer_token}}
```

**Response:**
```json
{
  "success": true,
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "completed",
  "operation": "merge",
  "result": {
    "download_urls": [
      "http://localhost:8004/v1/files/550e8400.../merged_document.pdf"
    ],
    "files": ["merged_document.pdf"],
    "job_type": "merge"
  },
  "created_at": "2025-10-31T13:00:00.000000Z",
  "completed_at": "2025-10-31T13:00:15.000000Z"
}
```

---

## ✂️ PDF Split

Split a PDF into multiple files.

### 1. Submit Split Job

**Method:** `POST`

**URL:**
```
localhost:8000/api/pdf/edit/split
```

**Headers:**
```
Authorization: Bearer {{bearer_token}}
Content-Type: application/json
```

**Body:** Select `raw` → `JSON`
```json
{
  "file_id": 204,
  "options": {
    "split_points": "2,4,6",
    "title_prefix": "Section",
    "author": "Your Name"
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "PDF split job created successfully",
  "job_id": "550e8400-e29b-41d4-a716-446655440001",
  "status": "queued",
  "operation": "split"
}
```

### 2. Check Status

**Endpoint:** `GET {{base_url}}/pdf/edit/split/status?job_id={{job_id}}`

### 3. Get Result

**Endpoint:** `GET {{base_url}}/pdf/edit/split/result?job_id={{job_id}}`

**Response:**
```json
{
  "success": true,
  "job_id": "550e8400-e29b-41d4-a716-446655440001",
  "status": "completed",
  "operation": "split",
  "result": {
    "download_urls": [
      "http://localhost:8004/v1/files/.../split_1_pages_1-2.pdf",
      "http://localhost:8004/v1/files/.../split_2_pages_3-4.pdf",
      "http://localhost:8004/v1/files/.../split_3_pages_5-6.pdf"
    ],
    "files": [
      "split_1_pages_1-2.pdf",
      "split_2_pages_3-4.pdf",
      "split_3_pages_5-6.pdf"
    ],
    "job_type": "split"
  }
}
```

---

## 🗜️ PDF Compress

Reduce PDF file size.

### 1. Submit Compress Job

**Endpoint:** `POST {{base_url}}/pdf/edit/compress`

**Body (JSON):**
```json
{
  "file_id": 204,
  "options": {
    "compression_level": "medium",
    "quality": 75
  }
}
```

**Compression Levels:**
- `low` - Minimal compression
- `medium` - Balanced (recommended)
- `high` - Maximum compression

**Response:**
```json
{
  "success": true,
  "message": "PDF compress job created successfully",
  "job_id": "550e8400-e29b-41d4-a716-446655440002",
  "status": "queued",
  "operation": "compress"
}
```

### 2. Check Status

**Endpoint:** `GET {{base_url}}/pdf/edit/compress/status?job_id={{job_id}}`

### 3. Get Result

**Endpoint:** `GET {{base_url}}/pdf/edit/compress/result?job_id={{job_id}}`

**Response:**
```json
{
  "success": true,
  "job_id": "550e8400-e29b-41d4-a716-446655440002",
  "status": "completed",
  "operation": "compress",
  "result": {
    "download_urls": [
      "http://localhost:8004/v1/files/.../compressed_document.pdf"
    ],
    "files": ["compressed_document.pdf"],
    "original_size": 680366,
    "compressed_size": 340183,
    "compression_ratio": 50
  }
}
```

---

## 🏷️ PDF Watermark

Add text or image watermark to PDF.

### 1. Submit Watermark Job

**Endpoint:** `POST {{base_url}}/pdf/edit/watermark`

**Body (JSON):**
```json
{
  "file_id": 204,
  "options": {
    "watermark_type": "text",
    "watermark_content": "CONFIDENTIAL",
    "position_x": 50,
    "position_y": 50,
    "rotation": -45,
    "opacity": 0.3,
    "color": "#FF0000",
    "font_family": "Arial",
    "font_size": 48,
    "apply_to_all": true
  }
}
```

**Options:**
- `watermark_type`: `text` or `image`
- `position_x`: 0-100 (percentage from left)
- `position_y`: 0-100 (percentage from top)
- `rotation`: -180 to 180 (degrees)
- `opacity`: 0.0-1.0 (transparency)
- `apply_to_all`: true/false
- `selected_pages`: "1,3,5" (if apply_to_all is false)

**Response:**
```json
{
  "success": true,
  "message": "PDF watermark job created successfully",
  "job_id": "550e8400-e29b-41d4-a716-446655440003",
  "status": "queued",
  "operation": "watermark"
}
```

### 2. Check Status

**Endpoint:** `GET {{base_url}}/pdf/edit/watermark/status?job_id={{job_id}}`

### 3. Get Result

**Endpoint:** `GET {{base_url}}/pdf/edit/watermark/result?job_id={{job_id}}`

---

## 🔢 PDF Page Numbers

Add page numbers to PDF.

### 1. Submit Page Numbers Job

**Endpoint:** `POST {{base_url}}/pdf/edit/page_numbers`

**Body (JSON):**
```json
{
  "file_id": 204,
  "options": {
    "position": "bottom_right",
    "format_type": "arabic",
    "font_size": 12,
    "page_ranges": []
  }
}
```

**Options:**
- `position`: `bottom_right`, `bottom_left`, `top_right`, `top_left`, `bottom_center`
- `format_type`: `arabic` (1,2,3), `roman_lower` (i,ii,iii), `roman_upper` (I,II,III)
- `font_size`: 6-72
- `page_ranges`: Empty array for all pages, or `[{"start": 1, "end": 3}, {"start": 5, "end": 8}]`

**Response:**
```json
{
  "success": true,
  "message": "PDF page_numbers job created successfully",
  "job_id": "550e8400-e29b-41d4-a716-446655440004",
  "status": "queued",
  "operation": "page_numbers"
}
```

### 2. Check Status

**Endpoint:** `GET {{base_url}}/pdf/edit/page_numbers/status?job_id={{job_id}}`

### 3. Get Result

**Endpoint:** `GET {{base_url}}/pdf/edit/page_numbers/result?job_id={{job_id}}`

---

## 📝 PDF Annotate

Add annotations (notes, highlights, text) to PDF.

### 1. Submit Annotate Job

**Endpoint:** `POST {{base_url}}/pdf/edit/annotate`

**Body (JSON):**
```json
{
  "file_id": 204,
  "options": {
    "annotations": [
      {
        "type": "note",
        "page_number": 1,
        "x": 60,
        "y": 60,
        "text": "Important note here"
      },
      {
        "type": "highlight",
        "page_number": 1,
        "x": 60,
        "y": 100,
        "width": 350,
        "height": 35,
        "color": [1, 1, 0]
      },
      {
        "type": "text",
        "page_number": 2,
        "x": 100,
        "y": 100,
        "text": "Additional comment",
        "font_size": 12,
        "color": [0, 0, 1]
      }
    ]
  }
}
```

**Annotation Types:**
- `note`: Sticky note annotation
- `highlight`: Highlight text area
- `text`: Free text annotation

**Color Format:** `[r, g, b]` where each value is 0-1
- `[1, 1, 0]` = Yellow
- `[1, 0, 0]` = Red
- `[0, 0, 1]` = Blue

**Response:**
```json
{
  "success": true,
  "message": "PDF annotate job created successfully",
  "job_id": "550e8400-e29b-41d4-a716-446655440005",
  "status": "queued",
  "operation": "annotate"
}
```

### 2. Check Status

**Endpoint:** `GET {{base_url}}/pdf/edit/annotate/status?job_id={{job_id}}`

### 3. Get Result

**Endpoint:** `GET {{base_url}}/pdf/edit/annotate/result?job_id={{job_id}}`

---

## 🔒 PDF Protect

Add password protection to PDF.

### 1. Submit Protect Job

**Endpoint:** `POST {{base_url}}/pdf/edit/protect`

**Body (JSON):**
```json
{
  "file_id": 204,
  "options": {
    "password": "SecurePassword123",
    "permissions": ["print", "copy"]
  }
}
```

**Available Permissions:**
- `print` - Allow printing
- `copy` - Allow copying text/images
- `modify` - Allow document modification

**Response:**
```json
{
  "success": true,
  "message": "PDF protect job created successfully",
  "job_id": "550e8400-e29b-41d4-a716-446655440006",
  "status": "queued",
  "operation": "protect"
}
```

### 2. Check Status

**Endpoint:** `GET {{base_url}}/pdf/edit/protect/status?job_id={{job_id}}`

### 3. Get Result

**Endpoint:** `GET {{base_url}}/pdf/edit/protect/result?job_id={{job_id}}`

---

## 🔓 PDF Unlock

Remove password protection from PDF.

### 1. Submit Unlock Job

**Endpoint:** `POST {{base_url}}/pdf/edit/unlock`

**Body (JSON):**
```json
{
  "file_id": 204,
  "options": {
    "password": "CurrentPassword123"
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "PDF unlock job created successfully",
  "job_id": "550e8400-e29b-41d4-a716-446655440007",
  "status": "queued",
  "operation": "unlock"
}
```

### 2. Check Status

**Endpoint:** `GET {{base_url}}/pdf/edit/unlock/status?job_id={{job_id}}`

### 3. Get Result

**Endpoint:** `GET {{base_url}}/pdf/edit/unlock/result?job_id={{job_id}}`

---

## 👁️ PDF Preview

Generate thumbnail previews of PDF pages.

### 1. Submit Preview Job

**Endpoint:** `POST {{base_url}}/pdf/edit/preview`

**Body (JSON):**
```json
{
  "file_id": 204,
  "options": {
    "page_numbers": "1,2,3",
    "thumbnail_width": 200,
    "thumbnail_height": 200,
    "zoom": 2.0
  }
}
```

**Options:**
- `page_numbers`: "1,2,3" or empty for all pages
- `thumbnail_width`: 50-1000 (pixels)
- `thumbnail_height`: 50-1000 (pixels)
- `zoom`: 0.5-5.0

**Response:**
```json
{
  "success": true,
  "message": "PDF preview job created successfully",
  "job_id": "550e8400-e29b-41d4-a716-446655440008",
  "status": "queued",
  "operation": "preview"
}
```

### 2. Check Status

**Endpoint:** `GET {{base_url}}/pdf/edit/preview/status?job_id={{job_id}}`

### 3. Get Result

**Endpoint:** `GET {{base_url}}/pdf/edit/preview/result?job_id={{job_id}}`

**Response:**
```json
{
  "success": true,
  "job_id": "550e8400-e29b-41d4-a716-446655440008",
  "status": "completed",
  "operation": "preview",
  "result": {
    "download_urls": [
      "http://localhost:8004/v1/files/.../page_1_thumb.png",
      "http://localhost:8004/v1/files/.../page_2_thumb.png",
      "http://localhost:8004/v1/files/.../page_3_thumb.png"
    ],
    "files": [
      "page_1_thumb.png",
      "page_2_thumb.png",
      "page_3_thumb.png"
    ],
    "thumbnail_size": {
      "width": 200,
      "height": 200
    }
  }
}
```

---

## ✏️ PDF Edit

Reorder, remove, or edit PDF pages.

### 1. Submit Edit Job

**Endpoint:** `POST {{base_url}}/pdf/edit/edit_pdf`

**Body (JSON) - Reverse Pages:**
```json
{
  "file_id": 204,
  "options": {
    "page_order": "reverse",
    "remove_blank_pages": true,
    "remove_pages": ""
  }
}
```

**Body (JSON) - Custom Order:**
```json
{
  "file_id": 204,
  "options": {
    "page_order": "3,1,2,5",
    "remove_blank_pages": true,
    "remove_pages": "4"
  }
}
```

**Body (JSON) - Keep Order, Remove Specific Pages:**
```json
{
  "file_id": 204,
  "options": {
    "page_order": "as_is",
    "remove_blank_pages": false,
    "remove_pages": "2,5,7"
  }
}
```

**Options:**
- `page_order`: `"as_is"`, `"reverse"`, or `"1,3,2,5"` (custom order)
- `remove_blank_pages`: true/false
- `remove_pages`: `""` (none) or `"2,5,7"` (specific pages)

**Response:**
```json
{
  "success": true,
  "message": "PDF edit_pdf job created successfully",
  "job_id": "550e8400-e29b-41d4-a716-446655440009",
  "status": "queued",
  "operation": "edit_pdf"
}
```

### 2. Check Status

**Endpoint:** `GET {{base_url}}/pdf/edit/edit_pdf/status?job_id={{job_id}}`

### 3. Get Result

**Endpoint:** `GET {{base_url}}/pdf/edit/edit_pdf/result?job_id={{job_id}}`

---

## 📦 Batch Operations

Apply the same operation to multiple PDFs.

### 1. Submit Batch Job

**Endpoint:** `POST {{base_url}}/pdf/edit/batch`

**Body (JSON) - Batch Compress:**
```json
{
  "file_ids": [204, 205, 206],
  "options": {
    "operation": "compress",
    "compression_level": "medium",
    "quality": 75
  }
}
```

**Body (JSON) - Batch Watermark:**
```json
{
  "file_ids": [204, 205, 206],
  "options": {
    "operation": "watermark",
    "watermark_type": "text",
    "watermark_content": "DRAFT",
    "opacity": 0.3
  }
}
```

**Supported Operations:**
- `compress`
- `watermark`
- `page_numbers`

**Response:**
```json
{
  "success": true,
  "message": "PDF batch job created successfully",
  "job_id": "550e8400-e29b-41d4-a716-446655440010",
  "status": "queued",
  "operation": "batch"
}
```

### 2. Check Status

**Endpoint:** `GET {{base_url}}/pdf/edit/batch/status?job_id={{job_id}}`

### 3. Get Result

**Endpoint:** `GET {{base_url}}/pdf/edit/batch/result?job_id={{job_id}}`

**Response:**
```json
{
  "success": true,
  "job_id": "550e8400-e29b-41d4-a716-446655440010",
  "status": "completed",
  "operation": "batch",
  "result": {
    "download_urls": [
      "http://localhost:8004/v1/files/.../document1_processed.pdf",
      "http://localhost:8004/v1/files/.../document2_processed.pdf",
      "http://localhost:8004/v1/files/.../document3_processed.pdf"
    ],
    "files": [
      "document1_processed.pdf",
      "document2_processed.pdf",
      "document3_processed.pdf"
    ],
    "processed_count": 3,
    "failed_count": 0
  }
}
```

---

## 🔄 Document Conversion

Convert documents between formats (PDF, DOCX, HTML, TXT).

### 1. Submit Conversion Job

**Endpoint:** `POST {{base_url}}/file-processing/convert`

**Body (JSON):**
```json
{
  "file_id": 204,
  "target_format": "docx"
}
```

**Supported Formats:**
- `pdf`
- `docx`
- `html`
- `txt`

**Response:**
```json
{
  "success": true,
  "message": "Conversion job created successfully",
  "job_id": "550e8400-e29b-41d4-a716-446655440011",
  "status": "queued",
  "tool_type": "document_conversion"
}
```

### 2. Check Status

**Endpoint:** `GET {{base_url}}/jobs/status?job_id={{job_id}}&operation=document_conversion`

**Response:**
```json
{
  "success": true,
  "job_id": "550e8400-e29b-41d4-a716-446655440011",
  "status": "completed",
  "progress": 100,
  "stage": "completed",
  "tool_type": "document_conversion"
}
```

### 3. Get Result

**Endpoint:** `GET {{base_url}}/jobs/result?job_id={{job_id}}&operation=document_conversion`

**Response:**
```json
{
  "success": true,
  "job_id": "550e8400-e29b-41d4-a716-446655440011",
  "status": "completed",
  "result": {
    "file_path": "converted-files/document.docx",
    "file_url": "http://localhost:8000/storage/converted-files/document.docx",
    "original_format": "pdf",
    "target_format": "docx"
  }
}
```

---

## 📄 Content Extraction

Extract text and metadata from documents.

### 1. Submit Extraction Job

**Endpoint:** `POST {{base_url}}/file-processing/extract`

**Body (JSON):**
```json
{
  "file_id": 204,
  "extraction_type": "text",
  "language": "eng",
  "options": {
    "include_formatting": true,
    "max_pages": 10
  }
}
```

**Extraction Types:**
- `text` - Plain text extraction
- `structured` - Formatted text with structure
- `ocr` - OCR for scanned documents

**Response:**
```json
{
  "success": true,
  "message": "Extraction job created successfully",
  "job_id": "550e8400-e29b-41d4-a716-446655440012",
  "status": "queued",
  "tool_type": "content_extraction"
}
```

### 2. Check Status

**Endpoint:** `GET {{base_url}}/jobs/status?job_id={{job_id}}&operation=content_extraction`

### 3. Get Result

**Endpoint:** `GET {{base_url}}/jobs/result?job_id={{job_id}}&operation=content_extraction`

**Response:**
```json
{
  "success": true,
  "job_id": "550e8400-e29b-41d4-a716-446655440012",
  "status": "completed",
  "result": {
    "content": "Extracted text content...",
    "metadata": {
      "page_count": 10,
      "word_count": 1500,
      "language": "en"
    },
    "extraction_type": "text"
  }
}
```

---

## 🧪 Postman Test Scripts

### Auto-save job_id After Job Submission

Add this to the **Tests** tab of job submission requests:

```javascript
// Save job_id to environment variable
if (pm.response.code === 200 || pm.response.code === 201) {
    var jsonData = pm.response.json();
    if (jsonData.job_id) {
        pm.environment.set("job_id", jsonData.job_id);
        console.log("Job ID saved: " + jsonData.job_id);
    }
}

// Test successful submission
pm.test("Job submitted successfully", function () {
    pm.response.to.have.status(200);
    var jsonData = pm.response.json();
    pm.expect(jsonData.success).to.be.true;
    pm.expect(jsonData.job_id).to.exist;
});
```

### Auto-check Status Until Completed

Add this to the **Tests** tab of status check requests:

```javascript
var jsonData = pm.response.json();

// Test response structure
pm.test("Status check successful", function () {
    pm.response.to.have.status(200);
    pm.expect(jsonData.job_id).to.exist;
    pm.expect(jsonData.status).to.exist;
});

// If still processing, wait and retry
if (jsonData.status === "processing" || jsonData.status === "queued") {
    console.log("Status: " + jsonData.status + " (" + jsonData.progress + "%)");
    
    // Wait 2 seconds and retry
    setTimeout(function() {}, 2000);
    postman.setNextRequest(pm.info.requestName);
} else if (jsonData.status === "completed") {
    console.log("Job completed! Moving to result endpoint...");
    // Stop retrying, job is done
} else if (jsonData.status === "failed") {
    console.log("Job failed: " + jsonData.error);
}
```

### Validate Result Response

Add this to the **Tests** tab of result requests:

```javascript
// Test successful result
pm.test("Result retrieved successfully", function () {
    pm.response.to.have.status(200);
    var jsonData = pm.response.json();
    pm.expect(jsonData.success).to.be.true;
    pm.expect(jsonData.status).to.equal("completed");
    pm.expect(jsonData.result).to.exist;
});

// Test download URLs exist
pm.test("Download URLs present", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.result.download_urls).to.be.an('array');
    pm.expect(jsonData.result.download_urls.length).to.be.above(0);
});

// Log download URLs
var jsonData = pm.response.json();
if (jsonData.result && jsonData.result.download_urls) {
    console.log("Download URLs:");
    jsonData.result.download_urls.forEach(function(url, index) {
        console.log((index + 1) + ". " + url);
    });
}
```

---

## ⚠️ Common Status Codes

| Code | Meaning | Action |
|------|---------|--------|
| 200 | Success | Request completed successfully |
| 201 | Created | Job created successfully |
| 400 | Bad Request | Check request body/parameters |
| 401 | Unauthorized | Check Bearer token |
| 404 | Not Found | Check endpoint URL or job_id |
| 422 | Validation Error | Check required fields |
| 500 | Server Error | Check Laravel logs |

---

## 🔍 Troubleshooting

### Job Stuck in "Queued" Status

**Cause:** Queue worker not running

**Solution:**
```bash
php artisan queue:work redis --timeout=0 --tries=3
```

### Job Failed with Timeout Error

**Cause:** Operation taking too long

**Solution:** Check Laravel logs at `storage/logs/laravel.log` for details

### Invalid file_id Error

**Cause:** File doesn't exist or belongs to different user

**Solution:** Upload file first and use correct file_id

### Download URL Returns 404

**Cause:** File expired (24 hour expiration) or job_id incorrect

**Solution:** Re-run the job or check job_id

---

## 📝 Quick Reference

### Typical Workflow

1. **Upload file(s)** → Get `file_id`
2. **Submit operation** → Get `job_id`
3. **Poll status** → Wait for `completed`
4. **Get result** → Download files from `download_urls`

### Polling Intervals

- **First 30 seconds:** Check every 2-3 seconds
- **After 30 seconds:** Check every 5-10 seconds
- **Maximum wait:** 10 minutes for complex operations

### Environment Variables Setup

**Variable Name** → **Value**
- `base_url` → `localhost:8000/api`
- `bearer_token` → Your actual token (e.g., `207|vhs65UeUfk2kpYoTpJX3zIClf4zQyixWZdkbfPYXf7ae0dfe`)
- `file_id` → `204` (or your uploaded file ID)
- `job_id` → Leave empty (auto-saved by test scripts)

---

**Last Updated:** October 31, 2025  
**API Version:** 1.0  
**Backend:** `localhost:8000`  
**PDF Microservice:** `localhost:8004`

