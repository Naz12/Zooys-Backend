### PDF & Document Processing - Postman Testing Guide

This guide contains ready-to-use Postman examples for all PDF edit endpoints plus convert and extract. All endpoints use universal file IDs (from /api/files/upload) and the universal job scheduler.

## Setup

- Base URL (Laravel API): `http://localhost:8000/api`
- PDF Microservice (internal): `http://localhost:8004`
- Auth: Bearer token via Laravel Sanctum (required for POST submit endpoints)

Postman variables (recommended):
```json
{
  "api_base": "http://localhost:8000/api",
  "bearer": "<YOUR_TOKEN>",
  "file_id": "<A_UPLOADED_FILE_ID>",
  "file_id_2": "<ANOTHER_FILE_ID>",
  "job_id": ""
}
```

Common headers:
```
Authorization: Bearer {{bearer}}
Content-Type: application/json
```

Note: Upload files first using existing endpoint: `POST {{api_base}}/files/upload` → save the returned `id` as `{{file_id}}`

---

## Conversion (Document → pdf/docx/html/txt)

Submit conversion job
```
POST {{api_base}}/file-processing/convert
```
Body (JSON):
```json
{
  "file_id": "{{file_id}}",
  "target_format": "pdf",
  "options": { "optimize": true }
}
```
Response (202):
```json
{
  "success": true,
  "message": "Document conversion job started",
  "job_id": "{{job_id}}",
  "status": "pending",
  "poll_url": "{{api_base}}/status?job_id={{job_id}}",
  "result_url": "{{api_base}}/result?job_id={{job_id}}"
}
```

Check conversion status
```
GET {{api_base}}/status/document_conversion/file?job_id={{job_id}}
```
or generic:
```
GET {{api_base}}/status?job_id={{job_id}}
```

Get conversion result
```
GET {{api_base}}/result/document_conversion/file?job_id={{job_id}}
```
Result (example):
```json
{
  "success": true,
  "data": {
    "conversion_result": { "status": "completed", "download_urls": ["http://localhost:8004/v1/files/<remote_job_id>/output.pdf"] },
    "converted_file": { "url": "http://localhost/storage/converted-files/filename.pdf" },
    "original_file": { "id": "{{file_id}}", "filename": "document.pdf", "size": 12345 }
  }
}
```

---

## Content Extraction (text/metadata/images)

Submit extraction job
```
POST {{api_base}}/file-processing/extract
```
Body (JSON):
```json
{
  "file_id": "{{file_id}}",
  "extraction_type": "text",
  "language": "eng",
  "include_formatting": false,
  "max_pages": 10,
  "options": {}
}
```
Response (202): same shape as conversion (with job_id)

Check extraction status
```
GET {{api_base}}/status/content_extraction/file?job_id={{job_id}}
```

Get extraction result
```
GET {{api_base}}/result/content_extraction/file?job_id={{job_id}}
```
Result (example):
```json
{
  "success": true,
  "data": {
    "extraction_result": { "status": "completed" },
    "original_file": { "id": "{{file_id}}", "filename": "document.pdf" },
    "extracted_content": "Extracted text...",
    "metadata": { "page_count": 10 },
    "word_count": 150,
    "page_count": 10,
    "language_detected": "en"
  }
}
```

---

## PDF Edit Operations (fixed endpoints)

All submit endpoints require auth. Status/result are public for polling.

### 1) Merge PDFs
Submit
```
POST {{api_base}}/pdf/edit/merge
```
Body (JSON):
```json
{
  "file_ids": ["{{file_id}}", "{{file_id_2}}"],
  "params": {
    "page_order": "as_uploaded",
    "remove_blank_pages": false,
    "add_page_numbers": false
  }
}
```
Status/Result
```
GET {{api_base}}/pdf/edit/merge/status?job_id={{job_id}}
GET {{api_base}}/pdf/edit/merge/result?job_id={{job_id}}
```

### 2) Split PDF
Submit
```
POST {{api_base}}/pdf/edit/split
```
Body:
```json
{
  "file_id": "{{file_id}}",
  "params": { "split_points": "1,3,5", "title_prefix": "Chapter", "author": "Author Name" }
}
```
Status/Result
```
GET {{api_base}}/pdf/edit/split/status?job_id={{job_id}}
GET {{api_base}}/pdf/edit/split/result?job_id={{job_id}}
```

### 3) Compress PDF
Submit
```
POST {{api_base}}/pdf/edit/compress
```
Body:
```json
{
  "file_id": "{{file_id}}",
  "params": { "compression_level": "medium", "quality": 85 }
}
```
Status/Result
```
GET {{api_base}}/pdf/edit/compress/status?job_id={{job_id}}
GET {{api_base}}/pdf/edit/compress/result?job_id={{job_id}}
```

### 4) Watermark
Submit
```
POST {{api_base}}/pdf/edit/watermark
```
Body:
```json
{
  "file_id": "{{file_id}}",
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
Status/Result
```
GET {{api_base}}/pdf/edit/watermark/status?job_id={{job_id}}
GET {{api_base}}/pdf/edit/watermark/result?job_id={{job_id}}
```

### 5) Page Numbers
Submit
```
POST {{api_base}}/pdf/edit/page_numbers
```
Body:
```json
{
  "file_id": "{{file_id}}",
  "params": {
    "position": "bottom_right",
    "format_type": "arabic",
    "font_size": 12,
    "page_ranges": [{"start":1,"end":3}]
  }
}
```
Status/Result
```
GET {{api_base}}/pdf/edit/page_numbers/status?job_id={{job_id}}
GET {{api_base}}/pdf/edit/page_numbers/result?job_id={{job_id}}
```

### 6) Annotate
Submit
```
POST {{api_base}}/pdf/edit/annotate
```
Body:
```json
{
  "file_id": "{{file_id}}",
  "params": {
    "annotations": [
      {"type":"note","page_number":1,"x":60,"y":60,"text":"Visible note"},
      {"type":"highlight","page_number":1,"x":60,"y":100,"width":350,"height":35,"color":[1,1,0]}
    ]
  }
}
```
Status/Result
```
GET {{api_base}}/pdf/edit/annotate/status?job_id={{job_id}}
GET {{api_base}}/pdf/edit/annotate/result?job_id={{job_id}}
```

### 7) Protect (password)
Submit
```
POST {{api_base}}/pdf/edit/protect
```
Body:
```json
{
  "file_id": "{{file_id}}",
  "params": { "password": "your_password", "permissions": ["print","copy"] }
}
```
Status/Result
```
GET {{api_base}}/pdf/edit/protect/status?job_id={{job_id}}
GET {{api_base}}/pdf/edit/protect/result?job_id={{job_id}}
```

### 8) Unlock (remove password)
Submit
```
POST {{api_base}}/pdf/edit/unlock
```
Body:
```json
{
  "file_id": "{{file_id}}",
  "params": { "password": "current_password" }
}
```
Status/Result
```
GET {{api_base}}/pdf/edit/unlock/status?job_id={{job_id}}
GET {{api_base}}/pdf/edit/unlock/result?job_id={{job_id}}
```

### 9) Preview/Thumbnails
Submit
```
POST {{api_base}}/pdf/edit/preview
```
Body:
```json
{
  "file_id": "{{file_id}}",
  "params": { "page_numbers": "1,2", "thumbnail_width": 200, "thumbnail_height": 200, "zoom": 2.0 }
}
```
Status/Result
```
GET {{api_base}}/pdf/edit/preview/status?job_id={{job_id}}
GET {{api_base}}/pdf/edit/preview/result?job_id={{job_id}}
```

### 10) Batch (multi-file)
Submit
```
POST {{api_base}}/pdf/edit/batch
```
Body:
```json
{
  "file_ids": ["{{file_id}}","{{file_id_2}}"],
  "params": { "operation": "compress", "options": { "compression_level": "medium" } }
}
```
Status/Result
```
GET {{api_base}}/pdf/edit/batch/status?job_id={{job_id}}
GET {{api_base}}/pdf/edit/batch/result?job_id={{job_id}}
```

### 11) Edit PDF (reorder/remove pages)
Submit
```
POST {{api_base}}/pdf/edit/edit_pdf
```
Body:
```json
{
  "file_id": "{{file_id}}",
  "params": { "page_order": "reverse", "remove_blank_pages": true, "remove_pages": "" }
}
```
Status/Result
```
GET {{api_base}}/pdf/edit/edit_pdf/status?job_id={{job_id}}
GET {{api_base}}/pdf/edit/edit_pdf/result?job_id={{job_id}}
```

---

## Example Tests (Postman Tests tab)

Status endpoint tests
```javascript
pm.test("Status OK", function () { pm.response.to.have.status(200); });
pm.test("Has job_id", function () { pm.expect(pm.response.json()).to.have.property('job_id'); });
pm.test("Has status", function () { pm.expect(pm.response.json()).to.have.property('status'); });
if (pm.response.code === 200) { pm.environment.set("job_id", pm.response.json().job_id); }
```

Submission endpoint tests
```javascript
pm.test("Submitted", function () { pm.response.to.have.status(202); });
if (pm.response.code === 202) { pm.environment.set("job_id", pm.response.json().job_id); }
```



