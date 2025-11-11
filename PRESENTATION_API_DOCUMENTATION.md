# Presentation Module - Complete API Documentation

**Version:** 1.0  
**Last Updated:** November 2025  
**Base URL:** `http://your-domain.com/api`  
**Status:** ✅ Production Ready

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Endpoints](#endpoints)
4. [Request/Response Formats](#requestresponse-formats)
5. [Error Handling](#error-handling)
6. [Examples](#examples)
7. [Workflows](#workflows)

---

## 🎯 Overview

The Presentation Module provides a complete API for generating, managing, and exporting PowerPoint presentations. It supports multiple input types (text, file, URL, YouTube) and provides async job-based processing for scalable operations.

### **Key Features**

- Generate presentation outlines from various sources
- Generate detailed slide content
- Export to PowerPoint format
- Manage presentations (list, get, update, delete)
- Multiple templates and customization options
- Async job processing with status tracking

---

## 🔐 Authentication

### **Public Endpoints** (No Authentication Required)

The following endpoints are public and don't require authentication:
- `GET /presentations/templates`
- `POST /presentations/generate-outline`
- `POST /presentations/{aiResultId}/generate-content`
- `POST /presentations/{aiResultId}/export`
- `GET /presentations/{aiResultId}/data`
- `POST /presentations/{aiResultId}/save`
- `GET /presentations`
- `DELETE /presentations/{aiResultId}`
- `GET /files/download/{filename}`

### **Authenticated Endpoints** (Bearer Token Required)

All other endpoints require Bearer token authentication:

```http
Authorization: Bearer {your-token}
```

---

## 🌐 Endpoints

### **1. Get Templates**

Get available presentation templates.

**Endpoint:** `GET /presentations/templates`

**Authentication:** Not required

**Response:**

```json
{
  "success": true,
  "templates": [
    {
      "id": "corporate_blue",
      "name": "Corporate Blue",
      "description": "Professional business theme",
      "colors": ["#003366", "#0066CC", "#FFFFFF"],
      "preview": "corporate_blue_preview.png"
    },
    {
      "id": "modern_white",
      "name": "Modern White",
      "description": "Clean minimalist theme",
      "colors": ["#FFFFFF", "#F8F9FA", "#6C757D"]
    },
    {
      "id": "creative_colorful",
      "name": "Creative Colorful",
      "description": "Vibrant energetic theme",
      "colors": ["#FF6B6B", "#4ECDC4", "#45B7D1"]
    },
    {
      "id": "minimalist_gray",
      "name": "Minimalist Gray",
      "description": "Simple elegant theme",
      "colors": ["#2C3E50", "#95A5A6", "#ECF0F1"]
    },
    {
      "id": "academic_formal",
      "name": "Academic Formal",
      "description": "Formal academic theme",
      "colors": ["#1A1A1A", "#4A4A4A", "#FFFFFF"]
    }
  ]
}
```

---

### **2. Generate Outline**

Generate a presentation outline from user input.

**Endpoint:** `POST /presentations/generate-outline`

**Authentication:** Not required

**Request Body:**

```json
{
  "input_type": "text|file|url|youtube",
  "topic": "Your presentation topic (required if input_type is text)",
  "file_id": "file-id-here (required if input_type is file)",
  "url": "https://example.com (required if input_type is url)",
  "youtube_url": "https://www.youtube.com/watch?v=... (required if input_type is youtube)",
  "language": "English|Spanish|French|German|Italian|Portuguese|Chinese|Japanese",
  "tone": "Professional|Casual|Academic|Creative|Formal",
  "length": "Short|Medium|Long",
  "model": "Basic Model|Advanced Model|Premium Model|gpt-3.5-turbo|gpt-4"
}
```

**Request Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `input_type` | string | Yes | Type of input: `text`, `file`, `url`, or `youtube` |
| `topic` | string | Conditional | Required if `input_type` is `text` |
| `file_id` | string | Conditional | Required if `input_type` is `file` |
| `url` | string | Conditional | Required if `input_type` is `url` |
| `youtube_url` | string | Conditional | Required if `input_type` is `youtube` |
| `language` | string | No | Default: `English` |
| `tone` | string | No | Default: `Professional` |
| `length` | string | No | Default: `Medium` |
| `model` | string | No | AI model to use |

**Response:**

```json
{
  "success": true,
  "data": {
    "outline": {
      "title": "Introduction to Machine Learning",
      "slides": [
        {
          "slide_number": 1,
          "header": "Introduction",
          "subheaders": ["What is ML?", "Why ML?"],
          "slide_type": "title"
        },
        {
          "slide_number": 2,
          "header": "Overview",
          "subheaders": ["Definition", "Applications"],
          "slide_type": "content"
        }
      ]
    },
    "ai_result_id": 123
  }
}
```

**Example Requests:**

**Text Input:**
```bash
curl -X POST http://your-domain.com/api/presentations/generate-outline \
  -H "Content-Type: application/json" \
  -d '{
    "input_type": "text",
    "topic": "Introduction to Machine Learning",
    "language": "English",
    "tone": "Professional",
    "length": "Medium"
  }'
```

**File Input:**
```bash
curl -X POST http://your-domain.com/api/presentations/generate-outline \
  -H "Content-Type: application/json" \
  -d '{
    "input_type": "file",
    "file_id": "abc123",
    "language": "English",
    "tone": "Professional"
  }'
```

---

### **3. Update Outline**

Update an existing presentation outline.

**Endpoint:** `PUT /presentations/{aiResultId}/update-outline`

**Authentication:** Required

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `aiResultId` | integer | Presentation result ID |

**Request Body:**

```json
{
  "outline": {
    "title": "Updated Presentation Title",
    "slides": [
      {
        "slide_number": 1,
        "header": "Introduction",
        "subheaders": ["What is ML?", "Why ML?"],
        "slide_type": "title"
      }
    ]
  }
}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "outline": {
      "title": "Updated Presentation Title",
      "slides": [...]
    }
  }
}
```

---

### **4. Generate Content**

Generate detailed content for presentation slides.

**Endpoint:** `POST /presentations/{aiResultId}/generate-content`

**Authentication:** Not required

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `aiResultId` | integer | Presentation result ID |

**Request Body:**

```json
{
  "language": "English",
  "tone": "Professional",
  "detail_level": "brief|detailed|comprehensive"
}
```

**Request Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `language` | string | No | Default: `English` |
| `tone` | string | No | Default: `Professional` |
| `detail_level` | string | No | Default: `detailed` |

**Response:**

```json
{
  "success": true,
  "data": {
    "slides": [
      {
        "slide_number": 1,
        "header": "Introduction",
        "content": "• Machine learning is a subset of AI...\n• It enables systems to learn from data...",
        "slide_type": "title"
      },
      {
        "slide_number": 2,
        "header": "Overview",
        "content": "• Definition: ML algorithms learn patterns...\n• Applications: Image recognition, NLP...",
        "slide_type": "content"
      }
    ],
    "ai_result_id": 123
  },
  "message": "Content generated successfully"
}
```

---

### **5. Export Presentation**

Export presentation to PowerPoint format.

**Endpoint:** `POST /presentations/{aiResultId}/export`

**Authentication:** Not required

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `aiResultId` | integer | Presentation result ID |

**Request Body:**

```json
{
  "template": "corporate_blue",
  "color_scheme": "blue",
  "font_style": "modern",
  "generate_missing_content": true
}
```

**Request Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `template` | string | No | Default: `corporate_blue` |
| `color_scheme` | string | No | Default: `blue` |
| `font_style` | string | No | Default: `modern` |
| `generate_missing_content` | boolean | No | Default: `true` |

**Response:**

```json
{
  "success": true,
  "data": {
    "powerpoint_file": "presentations/presentation_123.pptx",
    "powerpoint_filename": "presentation_123.pptx",
    "download_url": "http://your-domain.com/storage/presentations/presentation_123.pptx",
    "file_size": 12345,
    "slides_count": 10,
    "ai_result_id": 123
  },
  "message": "Presentation exported successfully"
}
```

---

### **6. Generate PowerPoint**

Generate PowerPoint file (authenticated endpoint).

**Endpoint:** `POST /presentations/{aiResultId}/generate-powerpoint`

**Authentication:** Required

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `aiResultId` | integer | Presentation result ID |

**Request Body:** Same as Export Presentation

**Response:** Same as Export Presentation

---

### **7. Get Presentation Data**

Get presentation data for editing.

**Endpoint:** `GET /presentations/{aiResultId}/data`

**Authentication:** Not required

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `aiResultId` | integer | Presentation result ID |

**Response:**

```json
{
  "success": true,
  "data": {
    "title": "Introduction to Machine Learning",
    "slides": [
      {
        "slide_number": 1,
        "header": "Introduction",
        "subheaders": ["What is ML?", "Why ML?"],
        "content": "• Machine learning is...",
        "slide_type": "title"
      }
    ]
  },
  "metadata": {
    "created_at": "2024-01-01T12:00:00Z",
    "updated_at": "2024-01-01T12:30:00Z"
  }
}
```

---

### **8. Save Presentation**

Save presentation changes.

**Endpoint:** `POST /presentations/{aiResultId}/save`

**Authentication:** Not required

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `aiResultId` | integer | Presentation result ID |

**Request Body:**

```json
{
  "title": "Updated Presentation Title",
  "slides": [
    {
      "slide_number": 1,
      "header": "Introduction",
      "content": "Updated content here",
      "subheaders": ["Updated subheader"]
    }
  ]
}
```

**Response:**

```json
{
  "success": true,
  "message": "Presentation saved successfully"
}
```

---

### **9. List Presentations**

Get list of all presentations.

**Endpoint:** `GET /presentations`

**Authentication:** Not required

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `page` | integer | No | Page number (default: 1) |
| `per_page` | integer | No | Items per page (default: 15) |

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "title": "Introduction to Machine Learning",
      "created_at": "2024-01-01T12:00:00Z",
      "updated_at": "2024-01-01T12:30:00Z",
      "slides_count": 10
    },
    {
      "id": 124,
      "title": "Advanced AI Techniques",
      "created_at": "2024-01-02T10:00:00Z",
      "updated_at": "2024-01-02T10:15:00Z",
      "slides_count": 15
    }
  ],
  "pagination": {
    "current_page": 1,
    "total": 25,
    "per_page": 15
  }
}
```

---

### **10. Get Presentation**

Get specific presentation details.

**Endpoint:** `GET /presentations/{aiResultId}`

**Authentication:** Required

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `aiResultId` | integer | Presentation result ID |

**Response:**

```json
{
  "success": true,
  "data": {
    "id": 123,
    "title": "Introduction to Machine Learning",
    "slides": [...],
    "metadata": {
      "language": "English",
      "tone": "Professional",
      "length": "Medium"
    }
  }
}
```

---

### **11. Delete Presentation**

Delete a presentation.

**Endpoint:** `DELETE /presentations/{aiResultId}`

**Authentication:** Not required

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `aiResultId` | integer | Presentation result ID |

**Response:**

```json
{
  "success": true,
  "message": "Presentation deleted successfully"
}
```

---

### **12. Download Presentation**

Download a presentation file.

**Endpoint:** `GET /files/download/{filename}`

**Authentication:** Not required

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `filename` | string | Presentation filename |

**Response:** Binary file (PowerPoint file)

---

### **13. Get Progress Status**

Get presentation generation progress.

**Endpoint:** `GET /presentations/{aiResultId}/status`

**Authentication:** Required

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `aiResultId` | integer | Presentation result ID |

**Response:**

```json
{
  "success": true,
  "data": {
    "status": "processing|completed|failed",
    "progress": 75.0,
    "stage": "generating_content",
    "message": "Generating slide content..."
  }
}
```

---

### **14. Check Microservice Status**

Check if presentation microservice is available.

**Endpoint:** `GET /presentations/microservice-status`

**Authentication:** Required

**Response:**

```json
{
  "success": true,
  "available": true,
  "message": "Microservice is available"
}
```

---

### **15. Get Text Job Status**

Get status of text-based presentation job.

**Endpoint:** `GET /status/presentations/text?job_id={jobId}`

**Authentication:** Required

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `job_id` | string | Yes | Job ID from async operation |

**Response:**

```json
{
  "success": true,
  "job": {
    "id": "job-id-123",
    "status": "pending|processing|completed|failed",
    "progress": 45.0,
    "stage": "analyzing_content",
    "created_at": "2024-01-01T12:00:00Z",
    "updated_at": "2024-01-01T12:01:00Z"
  }
}
```

---

### **16. Get Text Job Result**

Get result of text-based presentation job.

**Endpoint:** `GET /result/presentations/text?job_id={jobId}`

**Authentication:** Required

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `job_id` | string | Yes | Job ID from async operation |

**Response:**

```json
{
  "success": true,
  "data": {
    "title": "Introduction to Machine Learning",
    "slides": [...],
    "ai_result_id": 123
  }
}
```

---

### **17. Get File Job Status**

Get status of file-based presentation job.

**Endpoint:** `GET /status/presentations/file?job_id={jobId}`

**Authentication:** Required

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `job_id` | string | Yes | Job ID from async operation |

**Response:** Same format as Text Job Status

---

### **18. Get File Job Result**

Get result of file-based presentation job.

**Endpoint:** `GET /result/presentations/file?job_id={jobId}`

**Authentication:** Required

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `job_id` | string | Yes | Job ID from async operation |

**Response:** Same format as Text Job Result

---

## 📊 Request/Response Formats

### **Standard Success Response**

```json
{
  "success": true,
  "data": {...},
  "message": "Optional success message"
}
```

### **Standard Error Response**

```json
{
  "success": false,
  "error": "Error message",
  "details": {
    "field": ["Validation error message"]
  }
}
```

---

## ⚠️ Error Handling

### **Common Error Codes**

| Status Code | Description | Solution |
|-------------|-------------|----------|
| `400` | Bad Request | Check request format and parameters |
| `401` | Unauthorized | Verify authentication token |
| `404` | Not Found | Check endpoint URL and resource ID |
| `422` | Validation Error | Check request body validation errors |
| `500` | Internal Server Error | Service issue, retry later |

### **Error Response Format**

```json
{
  "success": false,
  "error": "Validation failed",
  "details": {
    "input_type": ["The input type field is required."],
    "topic": ["The topic field is required when input type is text."]
  }
}
```

---

## 💻 Examples

### **Example 1: Complete Workflow**

```bash
# 1. Get available templates
curl -X GET http://your-domain.com/api/presentations/templates

# 2. Generate outline from text
curl -X POST http://your-domain.com/api/presentations/generate-outline \
  -H "Content-Type: application/json" \
  -d '{
    "input_type": "text",
    "topic": "Introduction to Machine Learning",
    "language": "English",
    "tone": "Professional",
    "length": "Medium"
  }'

# 3. Generate content (use ai_result_id from step 2)
curl -X POST http://your-domain.com/api/presentations/123/generate-content \
  -H "Content-Type: application/json" \
  -d '{
    "language": "English",
    "tone": "Professional",
    "detail_level": "detailed"
  }'

# 4. Export to PowerPoint
curl -X POST http://your-domain.com/api/presentations/123/export \
  -H "Content-Type: application/json" \
  -d '{
    "template": "corporate_blue",
    "color_scheme": "blue",
    "font_style": "modern"
  }'

# 5. Download the file
curl -X GET http://your-domain.com/api/files/download/presentation_123.pptx \
  -o presentation.pptx
```

### **Example 2: Generate from File**

```bash
# First upload a file (use /api/files/upload endpoint)
# Then use the file_id to generate outline

curl -X POST http://your-domain.com/api/presentations/generate-outline \
  -H "Content-Type: application/json" \
  -d '{
    "input_type": "file",
    "file_id": "abc123",
    "language": "English",
    "tone": "Professional"
  }'
```

### **Example 3: Generate from YouTube**

```bash
curl -X POST http://your-domain.com/api/presentations/generate-outline \
  -H "Content-Type: application/json" \
  -d '{
    "input_type": "youtube",
    "youtube_url": "https://www.youtube.com/watch?v=example",
    "language": "English",
    "tone": "Professional",
    "length": "Long"
  }'
```

---

## 🔄 Workflows

### **Standard Workflow**

1. **Get Templates** → View available templates
2. **Generate Outline** → Create presentation structure
3. **Generate Content** → Add detailed content to slides
4. **Save Presentation** → Save any edits
5. **Export Presentation** → Create PowerPoint file
6. **Download Presentation** → Download the file

### **Quick Workflow**

1. **Generate Outline** → Create outline with content
2. **Export Presentation** → Direct export (generates missing content automatically)

---

## 📝 Notes

1. **Async Processing**: All presentation operations are processed asynchronously. Use job status endpoints to track progress.

2. **File Uploads**: Use `/api/files/upload` endpoint to upload files first, then use `file_id` in requests.

3. **CORS**: Public endpoints support CORS for `http://localhost:3000` by default.

4. **Templates**: Available templates include: `corporate_blue`, `modern_white`, `creative_colorful`, `minimalist_gray`, `academic_formal`.

5. **Languages**: Supported languages: English, Spanish, French, German, Italian, Portuguese, Chinese, Japanese.

6. **Tones**: Supported tones: Professional, Casual, Academic, Creative, Formal.

7. **Lengths**: Supported lengths: Short (5-7 slides), Medium (10-12 slides), Long (15+ slides).

---

**Last Updated:** November 2025  
**Documentation Version:** 1.0  
**Status:** ✅ Production Ready

