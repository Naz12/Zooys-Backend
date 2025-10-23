# Specialized Summarize API Endpoints

## 🎯 Overview

The API now supports specialized endpoints for different input types, making it easier to use and more intuitive for developers.

## 📋 Available Endpoints

### 1. YouTube Video Summarization
**Endpoint**: `POST /api/summarize/async/youtube`

**Purpose**: Summarize YouTube videos with automatic transcription and AI summarization.

**Request Body**:
```json
{
  "url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
  "options": {
    "language": "en",
    "format": "bundle",
    "focus": "summary"
  }
}
```

**Validation**:
- `url`: Required, must be a valid YouTube URL
- `options`: Optional array of processing options

**Response**:
```json
{
  "success": true,
  "message": "Summarization job started",
  "job_id": "uuid-here",
  "status": "pending",
  "poll_url": "http://localhost:8000/api/summarize/status/{job_id}"
}
```

---

### 2. Text Summarization
**Endpoint**: `POST /api/summarize/async/text`

**Purpose**: Summarize plain text content using AI.

**Request Body**:
```json
{
  "text": "Your long text content here...",
  "options": {
    "language": "en",
    "format": "detailed",
    "focus": "summary"
  }
}
```

**Validation**:
- `text`: Required, minimum 10 characters
- `options`: Optional array of processing options

**Response**: Same as YouTube endpoint

---

### 3. Audio/Video File Summarization
**Endpoint**: `POST /api/summarize/async/audiovideo`

**Purpose**: Summarize audio or video files with transcription and AI summarization.

**Request**: Multipart form data
```
file: [audio/video file]
options: [optional JSON string]
```

**Supported Formats**:
- Audio: `mp3`, `wav`, `m4a`
- Video: `mp4`, `avi`, `mov`
- Max size: 50MB

**Response**: Same as YouTube endpoint

---

### 4. General File Upload Summarization
**Endpoint**: `POST /api/summarize/async/file`

**Purpose**: Summarize various file types including documents, audio, and video.

**Request**: Multipart form data
```
file: [any supported file]
options: [optional JSON string]
```

**Supported Formats**:
- Documents: `pdf`, `doc`, `docx`, `txt`
- Audio: `mp3`, `wav`, `m4a`
- Video: `mp4`, `avi`, `mov`
- Max size: 50MB

**Response**: Same as YouTube endpoint

---

### 5. Link Summarization
**Endpoint**: `POST /api/summarize/link`

**Purpose**: Summarize any URL content (web pages, articles, etc.).

**Request Body**:
```json
{
  "url": "https://example.com/article",
  "options": {
    "language": "en",
    "format": "bundle",
    "focus": "summary"
  }
}
```

**Validation**:
- `url`: Required, must be a valid URL
- `options`: Optional array of processing options

**Response**: Same as YouTube endpoint

---

### 6. Image Summarization
**Endpoint**: `POST /api/summarize/async/image`

**Purpose**: Analyze and summarize image content using AI vision.

**Request**: Multipart form data
```
file: [image file]
options: [optional JSON string]
```

**Supported Formats**:
- Images: `jpg`, `jpeg`, `png`, `gif`, `bmp`, `webp`
- Max size: 10MB

**Response**: Same as YouTube endpoint

---

## 🔄 Status and Result Endpoints

All endpoints use the same status and result endpoints:

### Check Job Status
**Endpoint**: `GET /api/summarize/status/{job_id}`

**Response**:
```json
{
  "job_id": "uuid-here",
  "status": "completed",
  "progress": 100,
  "stage": "completed",
  "error": null
}
```

### Get Job Result
**Endpoint**: `GET /api/summarize/result/{job_id}`

**Response**:
```json
{
  "success": true,
  "data": {
    "summary": "Generated summary text...",
    "key_points": [
      "Key point 1",
      "Key point 2",
      "Key point 3"
    ],
    "confidence_score": 0.8,
    "model_used": "ollama:phi3:mini"
  }
}
```

---

## 🚀 Usage Examples

### YouTube Video
```javascript
const response = await fetch('/api/summarize/async/youtube', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer your-token',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    options: {
      language: 'en',
      format: 'bundle'
    }
  })
});
```

### Text Content
```javascript
const response = await fetch('/api/summarize/async/text', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer your-token',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    text: 'Your long text content here...',
    options: {
      language: 'en',
      format: 'detailed'
    }
  })
});
```

### File Upload
```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('options', JSON.stringify({
  language: 'en',
  format: 'detailed'
}));

const response = await fetch('/api/summarize/async/file', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer your-token'
  },
  body: formData
});
```

---

## 🔧 Options Parameters

All endpoints support the following options:

```json
{
  "language": "en",           // Language for processing
  "format": "detailed",      // Output format: "detailed", "bundle", "simple"
  "focus": "summary",         // Focus area: "summary", "key_points", "analysis"
  "max_length": 500,         // Maximum summary length
  "temperature": 0.7         // AI creativity level (0.0-1.0)
}
```

---

## 📊 Performance Metrics

- **Text Processing**: ~10-30 seconds
- **YouTube Videos**: ~30-60 seconds
- **Audio/Video Files**: ~60-120 seconds
- **Images**: ~10-20 seconds
- **Web Links**: ~30-90 seconds

---

## 🔐 Authentication

All endpoints require Bearer token authentication:

```javascript
headers: {
  'Authorization': 'Bearer your-token-here',
  'Content-Type': 'application/json'
}
```

---

## 🎯 Benefits of Specialized Endpoints

1. **Clearer API**: Each endpoint has a specific purpose
2. **Better Validation**: Input-specific validation rules
3. **Easier Integration**: Frontend can use appropriate endpoint
4. **Better Documentation**: Clear usage examples for each type
5. **Optimized Processing**: Each endpoint can be optimized for its content type

---

## 🔄 Migration from Generic Endpoint

If you're currently using the generic `/api/summarize/async` endpoint, you can easily migrate:

**Before**:
```javascript
// Generic endpoint
POST /api/summarize/async
{
  "content_type": "text",
  "source": {
    "type": "text",
    "data": "text content"
  }
}
```

**After**:
```javascript
// Specialized endpoint
POST /api/summarize/async/text
{
  "text": "text content"
}
```

The specialized endpoints are more intuitive and easier to use! 🚀


