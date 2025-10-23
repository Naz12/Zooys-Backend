## Summarize Async API - Full Spec (All Input Types)

### Endpoint
- URL: `/api/summarize/async`
- Method: POST
- Auth: Bearer token (Sanctum)
- Content-Type: `application/json`
- Accept: `application/json`

### Shared Request Shape
```json
{
  "content_type": "text | link | pdf | image | audio | video",
  "source": {
    "type": "text | url | file",
    "data": "<string value>"
  },
  "options": {
    "summary_length": "short | medium | long",
    "language": "en",
    "mode": "concise | detailed"
  }
}
```

Notes:
- For file-based inputs (`pdf`, `image`, `audio`, `video`), `source.type` must be `file` and `source.data` is the numeric `file_id` returned by `/api/files/upload`.
- For links, `source.type` is `url`. YouTube links are also handled under `link`.

---

## Request Examples (7 Input Types)

### 1) Text
```json
{
  "content_type": "text",
  "source": { "type": "text", "data": "Your raw text to summarize" },
  "options": { "summary_length": "medium", "language": "en" }
}
```

### 2) Web Link (URL)
```json
{
  "content_type": "link",
  "source": { "type": "url", "data": "https://example.com/article" },
  "options": { "summary_length": "short" }
}
```

### 3) YouTube Link
```json
{
  "content_type": "link",
  "source": { "type": "url", "data": "https://www.youtube.com/watch?v=VIDEO_ID" },
  "options": { "summary_length": "medium", "language": "en" }
}
```
Notes:
- Videos longer than 5 minutes are rejected with a user-facing error.
- On success, response includes transcriber `bundle` and `merged_result`.

### 4) PDF (file_id)
```json
{
  "content_type": "pdf",
  "source": { "type": "file", "data": "<file_id>" },
  "options": { "summary_length": "medium" }
}
```

### 5) Image (file_id)
```json
{
  "content_type": "image",
  "source": { "type": "file", "data": "<file_id>" },
  "options": { "summary_length": "short" }
}
```

### 6) Audio (file_id)
```json
{
  "content_type": "audio",
  "source": { "type": "file", "data": "<file_id>" },
  "options": { "summary_length": "short" }
}
```

### 7) Video (file_id)
```json
{
  "content_type": "video",
  "source": { "type": "file", "data": "<file_id>" },
  "options": { "summary_length": "medium" }
}
```

---

## Responses

### 202 Accepted (job created)
```json
{
  "success": true,
  "message": "Summarization job started",
  "job_id": "<uuid>",
  "status": "pending",
  "poll_url": "http://localhost:8000/api/summarize/status/<uuid>",
  "result_url": "http://localhost:8000/api/summarize/result/<uuid>"
}
```

### Poll Status
- GET `/api/summarize/status/{jobId}`
- Returns: `{ job_id, status: pending|processing|completed|failed, progress, error? }`
```json
{
  "job_id": "<uuid>",
  "status": "processing",
  "progress": 35,
  "stage": "extracting",
  "error": null
}
```

### Get Result
- GET `/api/summarize/result/{jobId}`
- Returns on success:
```json
{
  "success": true,
  "data": {
    "summary": "...",
    "metadata": { "tokens_used": 123, "source_type": "text|web|youtube|pdf|image|audio|video" },
    "bundle": { "article": "...", "json": { "segments": [] } },
    "merged_result": { "summary": "...", "bundle": { /* ... */ } }
  }
}
```
Notes:
- `bundle`/`merged_result` only present for YouTube inputs.

---

## Data Flows by Input Type

### Text
1. Client → `/api/summarize/async` (content_type=text, source.type=text)
2. UniversalJobService → `processTextSummarization`
3. AIProcessingModule → AI Manager `/api/process-text`
4. Result stored → AIResult → Return job result

### Web Link (URL)
1. Client → `/api/summarize/async` (content_type=link, source.type=url)
2. UniversalJobService → `processWebLinkSummarization`
3. WebScrapingService → extract content → AIProcessingModule
4. Result stored → AIResult → Return job result

### YouTube Link
1. Client → `/api/summarize/async` (content_type=link, source.type=url)
2. UniversalJobService → `processYouTubeSummarization`
3. UnifiedProcessingService → YouTubeTranscriberService (duration check, bundle fetch)
4. AIProcessingModule → summarize article → merge with bundle
5. Result stored → AIResult → Return job result (includes bundle + merged_result)

### PDF (file_id)
1. Client uploads file → `/api/files/upload` → gets `file_id`
2. Client → `/api/summarize/async` (content_type=pdf, source.type=file, data=file_id)
3. UniversalJobService → `processFileSummarization`
4. UniversalFileManagementModule → extract → AIProcessingModule
5. Result stored → AIResult → Return job result

### Image (file_id)
1. Upload → get `file_id` → call `/api/summarize/async`
2. UniversalFileManagementModule → OCR/extract → AIProcessingModule
3. Store → Return

### Audio (file_id)
1. Upload → get `file_id` → call `/api/summarize/async`
2. UniversalFileManagementModule → Transcription (ASR) → AIProcessingModule
3. Store → Return

### Video (file_id)
1. Upload → get `file_id` → call `/api/summarize/async`
2. UniversalFileManagementModule → Transcription (ASR) → AIProcessingModule
3. Store → Return

---

## File Upload (to obtain file_id)
- POST `/api/files/upload`
- Form-data: `file` (binary), `tool_type=summarize`
- Returns: `file_upload.id` → use as `source.data`
```bash
curl -X POST http://localhost:8000/api/files/upload \
  -H "Authorization: Bearer <token>" \
  -H "Accept: application/json" \
  -F "file=@/path/to/file.pdf" \
  -F "tool_type=summarize"
```

---

## Validation & Errors
- 422: Validation failed (missing `source.type`, invalid `content_type`, etc.)
- 401: Unauthenticated (missing/invalid bearer token)
- 500: Internal error (transcriber/AI manager failure)
  - Web pages blocking requests
  - Transcriber microservice errors
  - Oversized/unsupported file types

---

## Frontend Quick Start

Create job (fetch):
```ts
const res = await fetch('/api/summarize/async', {
  method: 'POST',
  headers: {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  body: JSON.stringify({
    content_type: 'text',
    source: { type: 'text', data: 'Your text' },
    options: { summary_length: 'medium' }
  })
});
const { job_id } = await res.json();
```

Poll and get result:
```ts
const status = await fetch(`/api/summarize/status/${job_id}`, { headers: { Authorization: `Bearer ${token}` } }).then(r => r.json());
if (status.status === 'completed') {
  const result = await fetch(`/api/summarize/result/${job_id}`, { headers: { Authorization: `Bearer ${token}` } }).then(r => r.json());
  console.log(result.data.summary);
}
```

---

## Notes
- Use `/api/summarize/async` for ALL inputs including PDF; legacy `/api/pdf/summarize` is removed.
- Always poll `status` then fetch `result`.


