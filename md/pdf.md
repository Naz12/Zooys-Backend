## PDF Edit & Processing API

Base path: `/api`

Authentication:
- Submit jobs: `auth:sanctum` required
- Polling status/result: public endpoints (`/api/pdf/edit/{operation}/status|result`)

File handling:
- Always upload files via `/api/files/upload` to get `file_id`
- All PDF endpoints use `file_id` (no direct file upload)

Microservice:
- Configured via `config/services.php` (`DOCUMENT_CONVERTER_URL`, `DOCUMENT_CONVERTER_API_KEY`)
- Backed by `http://localhost:8004`

---

### Submit PDF Operation

POST `/api/pdf/edit/{operation}`

Path params:
- `operation`: one of `merge|split|compress|watermark|page_numbers|annotate|protect|unlock|preview|batch|edit_pdf`

Body (JSON):
- For single-file ops: `{ "file_id": "<uuid>", "params": { ... } }`
- For multi-file ops (merge, batch): `{ "file_ids": ["<uuid>", "<uuid>"], "params": { ... } }`

Response (202):
```json
{ "success": true, "job_id": "uuid", "status": "pending", "message": "PDF job queued successfully" }
```

Examples (params map directly to microservice):
- merge: `{ "file_ids": ["id1","id2"], "params": { "page_order": "as_uploaded", "remove_blank_pages": false, "add_page_numbers": false } }`
- split: `{ "file_id": "id", "params": { "split_points": "1,3,5", "title_prefix": "Chapter", "author": "Name" } }`
- compress: `{ "file_id": "id", "params": { "compression_level": "medium", "quality": 85 } }`
- watermark: `{ "file_id": "id", "params": { "watermark_type": "text", "watermark_content": "CONFIDENTIAL", "position_x": 50, "position_y": 50, "rotation": -45, "opacity": 0.3, "color": "#000000", "font_family": "Arial", "font_size": 48, "apply_to_all": true, "selected_pages": "1,2,3" } }`
- page_numbers: `{ "file_id": "id", "params": { "position": "bottom_right", "format_type": "arabic", "font_size": 12, "page_ranges": [{"start":1,"end":3}] } }`
- annotate: `{ "file_id": "id", "params": { "annotations": [{"type":"note","page_number":1,"x":60,"y":60,"text":"Visible note"}] } }`
- protect: `{ "file_id": "id", "params": { "password": "secret", "permissions": ["print","copy"] } }`
- unlock: `{ "file_id": "id", "params": { "password": "current_password" } }`
- preview: `{ "file_id": "id", "params": { "page_numbers": "1,2", "thumbnail_width": 200, "thumbnail_height": 200, "zoom": 2.0 } }`
- batch: `{ "file_ids": ["id1","id2"], "params": { "operation": "compress", "options": { "compression_level": "medium" } } }`
- edit_pdf: `{ "file_id": "id", "params": { "page_order": "reverse|as_is|3,1,2", "remove_blank_pages": true, "remove_pages": "1,5" } }`

---

### Check Status

GET `/api/pdf/edit/{operation}/status?job_id={job_id}`

Response:
```json
{ "job_id": "uuid", "status": "queued|running|completed|failed", "progress": 0, "stage": "...", "error": null }
```

### Get Result

GET `/api/pdf/edit/{operation}/result?job_id={job_id}`

Response (completed):
```json
{ "success": true, "job_id": "uuid", "operation": "merge", "data": { "remote_job_id": "...", "result": { "download_urls": ["http://localhost:8004/v1/files/{job_id}/file.pdf"], "files": ["file.pdf"] } }, "metadata": {"file_count": 2} }
```

Notes:
- Use `data.result.download_urls` to fetch generated files directly from the microservice.
- Jobs and files may expire in the microservice (see its docs).

---

### Convert & Extract (updated)

Submit (existing):
- POST `/api/file-processing/convert` — uses microservice `POST /v1/convert`
- POST `/api/file-processing/extract` — uses microservice `POST /v1/extract`

Polling (handled internally):
- Conversion polls `GET /v1/conversion/status|result`
- Extraction polls `GET /v1/extraction/status|result`



