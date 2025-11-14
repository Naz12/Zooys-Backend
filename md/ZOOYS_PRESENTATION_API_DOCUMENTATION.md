# Zooys Presentation API Documentation

## Overview

The Zooys Presentation API allows you to generate, edit, and export professional presentations. The API follows an asynchronous job-based workflow where operations return a `job_id` that you can poll for status and results.

**Base URL:** `http://localhost:8000/api`

**Authentication:** Bearer token (optional for public routes, required for authenticated routes)

---

## Table of Contents

1. [Workflow Overview](#workflow-overview)
2. [Endpoints](#endpoints)
   - [Get Templates](#1-get-templates)
   - [Generate Outline](#2-generate-outline)
   - [Generate Content](#3-generate-content)
   - [Export Presentation](#4-export-presentation)
   - [Get Job Status](#5-get-job-status)
   - [Get Job Result](#6-get-job-result)
   - [List Presentation Files](#7-list-presentation-files)
   - [Delete Presentation File](#8-delete-presentation-file)
   - [Download Presentation File](#9-download-presentation-file)
3. [Data Structures](#data-structures)
4. [Error Handling](#error-handling)
5. [Examples](#examples)

---

## Workflow Overview

The presentation generation process follows these steps:

1. **Generate Outline** → Returns `job_id` → Poll for result → Get outline
2. **Generate Content** → Send outline → Returns `job_id` → Poll for result → Get content
3. **Export Presentation** → Send content + styling → Returns `job_id` → Poll for result → Get file URL

All operations are asynchronous and use the Universal Job Scheduler for status tracking.

---

## Endpoints

### 1. Get Templates

Get available presentation templates and styling options.

**Endpoint:** `GET /presentations/templates`

**Authentication:** Not required

**Response:**

```json
{
  "success": true,
  "templates": {
    "corporate_blue": {
      "name": "Corporate Blue",
      "description": "Professional corporate template",
      "color_scheme": "blue",
      "category": "corporate"
    },
    "modern_red": {
      "name": "Modern Red",
      "description": "Modern red template",
      "color_scheme": "red",
      "category": "modern"
    }
  }
}
```

---

### 2. Generate Outline

Generate a presentation outline from user input (text, file, URL, or YouTube).

**Endpoint:** `POST /presentations/generate-outline`

**Authentication:** Not required (public route)

**Request Body:**

```json
{
  "input_type": "text",
  "topic": "Introduction to Machine Learning",
  "language": "English",
  "tone": "Professional",
  "length": "Medium",
  "model": "deepseek-chat"
}
```

**Input Types:**

- `text`: Requires `topic` field
- `file`: Requires `file_id` field
- `url`: Requires `url` field
- `youtube`: Requires `youtube_url` field

**Parameters:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `input_type` | string | Yes | One of: `text`, `file`, `url`, `youtube` |
| `topic` | string | Conditional | Required if `input_type` is `text` |
| `file_id` | string | Conditional | Required if `input_type` is `file` |
| `url` | string | Conditional | Required if `input_type` is `url` |
| `youtube_url` | string | Conditional | Required if `input_type` is `youtube` |
| `language` | string | No | Default: `English`. Options: `English`, `Spanish`, `French`, `German`, `Italian`, `Portuguese`, `Chinese`, `Japanese` |
| `tone` | string | No | Default: `Professional`. Options: `Professional`, `Casual`, `Academic`, `Creative`, `Formal` |
| `length` | string | No | Default: `Medium`. Options: `Short`, `Medium`, `Long` |
| `model` | string | No | AI model to use. Options: `Basic Model`, `Advanced Model`, `Premium Model`, `gpt-3.5-turbo`, `gpt-4`, `gpt-4o`, `deepseek-chat`, `ollama:mistral`, `ollama:llama3`, `ollama:phi3:mini` |

**Response:**

```json
{
  "success": true,
  "job_id": "a863102f-9146-44f6-8340-d1d112e9daf0",
  "message": "Outline generation job created successfully"
}
```

**Next Steps:**

1. Use the `job_id` to poll the status endpoint
2. Once status is `completed`, get the result using the result endpoint
3. The result will contain the outline structure

---

### 3. Generate Content

Generate full content for a presentation based on an outline.

**Endpoint:** `POST /presentations/generate-content`

**Authentication:** Not required (public route)

**Request Body:**

```json
{
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
        "header": "Types of Machine Learning",
        "subheaders": ["Supervised Learning", "Unsupervised Learning"],
        "slide_type": "content"
      }
    ]
  }
}
```

**Parameters:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `outline` | object | Yes | The presentation outline structure |
| `outline.title` | string | Yes | Presentation title |
| `outline.slides` | array | Yes | Array of slide objects |
| `outline.slides[].slide_number` | integer | Yes | Slide number (1-based) |
| `outline.slides[].header` | string | Yes | Slide header/title |
| `outline.slides[].subheaders` | array | Yes | Array of subheader strings |
| `outline.slides[].slide_type` | string | Yes | One of: `title`, `content`, `conclusion` |

**Response:**

```json
{
  "success": true,
  "job_id": "b863102f-9146-44f6-8340-d1d112e9daf1",
  "message": "Content generation job created successfully"
}
```

**Next Steps:**

1. Use the `job_id` to poll the status endpoint
2. Once status is `completed`, get the result using the result endpoint
3. The result will contain the full content with generated text for each slide

**Result Format:**

```json
{
  "content": {
    "title": "Introduction to Machine Learning",
    "slides": [
      {
        "slide_number": 1,
        "header": "Introduction to Machine Learning",
        "subheaders": ["A Foundational Overview"],
        "slide_type": "title",
        "content": "A Foundational Overview\nMachine Learning is a transformative branch..."
      },
      {
        "slide_number": 2,
        "header": "What is Machine Learning?",
        "subheaders": ["A Subset of AI"],
        "slide_type": "content",
        "content": "Machine learning is a specialized branch of artificial intelligence..."
      }
    ]
  }
}
```

**Note:** The `content` field in each slide is a **string** (not an array) containing the generated text.

---

### 4. Export Presentation

Export a presentation to PowerPoint format (.pptx).

**Endpoint:** `POST /presentations/export`

**Authentication:** Not required (public route)

**Request Body:**

```json
{
  "content": {
    "title": "Introduction to Machine Learning",
    "slides": [
      {
        "slide_number": 1,
        "header": "Introduction to Machine Learning",
        "subheaders": ["A Foundational Overview"],
        "slide_type": "title",
        "content": "A Foundational Overview\nMachine Learning is a transformative branch..."
      },
      {
        "slide_number": 2,
        "header": "What is Machine Learning?",
        "subheaders": ["A Subset of AI"],
        "slide_type": "content",
        "content": "Machine learning is a specialized branch..."
      }
    ]
  },
  "template": "corporate_blue",
  "color_scheme": "blue",
  "font_style": "modern"
}
```

**Alternative Format (Legacy):**

You can also use `presentation_data` instead of `content`:

```json
{
  "presentation_data": {
    "title": "Introduction to Machine Learning",
    "slides": [
      {
        "slide_number": 1,
        "header": "Introduction",
        "subheaders": ["What is ML?"],
        "slide_type": "title",
        "content": ["Machine Learning is a subset of AI", "It enables systems to learn"]
      }
    ]
  },
  "template": "corporate_blue",
  "color_scheme": "blue",
  "font_style": "modern"
}
```

**Parameters:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `content` or `presentation_data` | object | Yes | Presentation content (see format above) |
| `template` | string | No | Template name (default: `corporate_blue`) |
| `color_scheme` | string | No | Color scheme (default: `blue`) |
| `font_style` | string | No | Font style (default: `modern`) |

**Content Format:**

- **New Format (`content`)**: The `content` field in slides is a **string** (as returned by content generation)
- **Legacy Format (`presentation_data`)**: The `content` field in slides is an **array of strings**
- The API automatically converts string content to array format if needed

**Response:**

```json
{
  "success": true,
  "job_id": "c863102f-9146-44f6-8340-d1d112e9daf2",
  "message": "Export job created successfully"
}
```

**Next Steps:**

1. Use the `job_id` to poll the status endpoint
2. Once status is `completed`, get the result using the result endpoint
3. The result will contain the file URL and metadata

**Result Format:**

```json
{
  "file_id": 123,
  "filename": "presentation_5_c863102f-9146-44f6-8340-d1d112e9daf2.pptx",
  "download_url": "http://localhost:8000/storage/presentations/5/presentation_5_c863102f-9146-44f6-8340-d1d112e9daf2.pptx",
  "file_size": 46523,
  "slides_count": 12,
  "title": "Introduction to Machine Learning"
}
```

---

### 5. Get Job Status

Get the status of a presentation job (outline, content, or export).

**Endpoint:** `GET /presentations/status?job_id={job_id}`

**Authentication:** Not required (public route)

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `job_id` | string | Yes | The job ID returned from outline/content/export endpoints |

**Response:**

```json
{
  "success": true,
  "job_id": "a863102f-9146-44f6-8340-d1d112e9daf0",
  "tool_type": "presentation_export",
  "status": "processing",
  "progress": 50,
  "stage": "polling_microservice",
  "stage_message": "Generating PowerPoint file",
  "stage_description": "The microservice is creating your presentation file. This may take a few moments.",
  "created_at": "2025-11-13T20:54:43.226120Z",
  "updated_at": "2025-11-13T20:54:45.256432Z",
  "logs": [
    {
      "timestamp": "2025-11-13T20:54:43.715450Z",
      "level": "info",
      "message": "Starting presentation_export processing",
      "data": {}
    }
  ]
}
```

**Status Values:**

- `pending`: Job is queued but not yet started
- `processing`: Job is currently being processed
- `completed`: Job completed successfully
- `failed`: Job failed with an error

**Tool Types:**

- `presentation_outline`: Outline generation job
- `presentation_content`: Content generation job
- `presentation_export`: Export job

**Stages:**

**For Outline Generation:**
- `validating`: Validating input data
- `extracting_content`: Extracting content from source
- `calling_microservice`: Submitting to microservice
- `polling_microservice`: Waiting for microservice response
- `completed`: Outline generated successfully

**For Content Generation:**
- `validating`: Validating outline structure
- `calling_microservice`: Submitting to microservice
- `polling_microservice`: Waiting for microservice response
- `completed`: Content generated successfully

**For Export:**
- `validating`: Validating content structure
- `calling_microservice`: Submitting to microservice
- `polling_microservice`: Waiting for microservice response
- `saving_file`: Saving file to storage
- `completed`: Export completed successfully

---

### 6. Get Job Result

Get the result of a completed presentation job.

**Endpoint:** `GET /presentations/result?job_id={job_id}`

**Authentication:** Not required (public route)

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `job_id` | string | Yes | The job ID returned from outline/content/export endpoints |

**Response (Completed Job):**

```json
{
  "success": true,
  "job_id": "a863102f-9146-44f6-8340-d1d112e9daf0",
  "tool_type": "presentation_export",
  "result": {
    "file_id": 123,
    "filename": "presentation_5_c863102f-9146-44f6-8340-d1d112e9daf2.pptx",
    "download_url": "http://localhost:8000/storage/presentations/5/presentation_5_c863102f-9146-44f6-8340-d1d112e9daf2.pptx",
    "file_size": 46523,
    "slides_count": 12,
    "title": "Introduction to Machine Learning"
  },
  "metadata": {
    "processing_time": 15.5,
    "exported_at": "2025-11-13T20:54:45.150802Z"
  }
}
```

**Response (Not Yet Completed):**

```json
{
  "success": false,
  "error": "Job is not yet completed. Current status: processing"
}
```

**Status Code:** `202 Accepted`

**Result Formats by Tool Type:**

**Outline Generation Result:**
```json
{
  "outline": {
    "title": "Introduction to Machine Learning",
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

**Content Generation Result:**
```json
{
  "content": {
    "title": "Introduction to Machine Learning",
    "slides": [
      {
        "slide_number": 1,
        "header": "Introduction to Machine Learning",
        "subheaders": ["A Foundational Overview"],
        "slide_type": "title",
        "content": "A Foundational Overview\nMachine Learning is..."
      }
    ]
  }
}
```

**Export Result:**
```json
{
  "file_id": 123,
  "filename": "presentation_5_c863102f-9146-44f6-8340-d1d112e9daf2.pptx",
  "download_url": "http://localhost:8000/storage/presentations/5/presentation_5_c863102f-9146-44f6-8340-d1d112e9daf2.pptx",
  "file_size": 46523,
  "slides_count": 12,
  "title": "Introduction to Machine Learning"
}
```

---

### 7. List Presentation Files

Get a list of all presentation files for the authenticated user.

**Endpoint:** `GET /presentations/files?per_page=15&search=`

**Authentication:** Not required (public route)

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `per_page` | integer | No | Number of files per page (default: 15) |
| `search` | string | No | Search term to filter files by title or filename |

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "user_id": 5,
      "title": "Introduction to Machine Learning",
      "filename": "presentation_5_c863102f-9146-44f6-8340-d1d112e9daf2.pptx",
      "file_url": "http://localhost:8000/storage/presentations/5/presentation_5_c863102f-9146-44f6-8340-d1d112e9daf2.pptx",
      "file_size": 46523,
      "human_file_size": "45.4 KB",
      "template": "corporate_blue",
      "color_scheme": "blue",
      "font_style": "modern",
      "slides_count": 12,
      "metadata": {
        "exported_at": "2025-11-13T20:54:45.150802Z",
        "exported_by": "fastapi_microservice"
      },
      "expires_at": "2025-12-13T20:54:45.000000Z",
      "created_at": "2025-11-13T20:54:45.000000Z",
      "updated_at": "2025-11-13T20:54:45.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 1,
    "last_page": 1
  }
}
```

---

### 8. Get Presentation File Content (for Editing)

Get the editable content data for a presentation file. This allows you to retrieve the original content structure so you can edit and re-export the presentation.

**Endpoint:** `GET /presentations/files/{fileId}/content`

**Authentication:** Not required (public route)

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `fileId` | integer | Yes | The file ID to get content for |

**Response:**

```json
{
  "success": true,
  "content": {
    "title": "Introduction to Machine Learning",
    "slides": [
      {
        "slide_number": 1,
        "header": "Introduction to Machine Learning",
        "subheaders": ["A Foundational Overview"],
        "slide_type": "title",
        "content": "A Foundational Overview\nMachine Learning is..."
      },
      {
        "slide_number": 2,
        "header": "What is Machine Learning?",
        "subheaders": ["A Subset of AI"],
        "slide_type": "content",
        "content": "Machine learning is a specialized branch..."
      }
    ]
  },
  "file_id": 123,
  "title": "Introduction to Machine Learning",
  "template": "corporate_blue",
  "color_scheme": "blue",
  "font_style": "modern"
}
```

**Error Response:**

```json
{
  "success": false,
  "error": "File not found or access denied"
}
```

Or if content data is not available:

```json
{
  "success": false,
  "error": "Content data not available for this file. This file may have been created before the edit feature was added."
}
```

**Note:** 
- Only files exported after this feature was added will have content data available
- Use this content with the export endpoint to re-export with changes
- You can modify the content structure and styling before re-exporting

---

### 9. Delete Presentation File

Delete a presentation file.

**Endpoint:** `DELETE /presentations/files/{fileId}`

**Authentication:** Not required (public route)

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `fileId` | integer | Yes | The file ID to delete |

**Response:**

```json
{
  "success": true,
  "message": "File deleted successfully"
}
```

**Error Response:**

```json
{
  "success": false,
  "error": "File not found or access denied"
}
```

---

### 10. Download Presentation File

Download a presentation file.

**Endpoint:** `GET /presentations/files/{fileId}/download`

**Authentication:** Not required (public route)

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `fileId` | integer | Yes | The file ID to download |

**Response:**

Returns the file as a download (binary .pptx file).

**Error Response:**

```json
{
  "success": false,
  "error": "File not found or access denied"
}
```

Or if file has expired:

```json
{
  "success": false,
  "error": "File has expired"
}
```

**Note:** Files are automatically deleted after 1 month (expiration date).

---

## Data Structures

### Slide Object

```json
{
  "slide_number": 1,
  "header": "Introduction to Machine Learning",
  "subheaders": [
    "A Foundational Overview",
    "Presented by: [Your Name/Organization]"
  ],
  "slide_type": "title",
  "content": "A Foundational Overview\nMachine Learning is a transformative branch..."
}
```

**Fields:**

- `slide_number` (integer, required): Slide number (1-based)
- `header` (string, required): Slide header/title
- `subheaders` (array of strings, required): Array of subheader strings
- `slide_type` (string, required): One of `title`, `content`, `conclusion`
- `content` (string or array, optional): Slide content. Can be:
  - String format (as returned by content generation)
  - Array format (legacy format, array of bullet point strings)

### Outline Structure

```json
{
  "title": "Introduction to Machine Learning",
  "slides": [
    {
      "slide_number": 1,
      "header": "Introduction",
      "subheaders": ["What is ML?", "Why ML?"],
      "slide_type": "title"
    }
  ]
}
```

### Content Structure

```json
{
  "title": "Introduction to Machine Learning",
  "slides": [
    {
      "slide_number": 1,
      "header": "Introduction to Machine Learning",
      "subheaders": ["A Foundational Overview"],
      "slide_type": "title",
      "content": "A Foundational Overview\nMachine Learning is..."
    }
  ]
}
```

---

## Error Handling

### Error Response Format

```json
{
  "success": false,
  "error": "Error message here",
  "details": {
    "field_name": ["Validation error message"]
  }
}
```

### Common Error Codes

| Status Code | Description |
|-------------|-------------|
| `400` | Bad Request - Invalid input data |
| `422` | Unprocessable Entity - Validation errors |
| `404` | Not Found - Resource not found |
| `500` | Internal Server Error - Server-side error |
| `202` | Accepted - Job not yet completed (for result endpoint) |

### Error Examples

**Validation Error:**
```json
{
  "success": false,
  "error": "Validation failed",
  "details": {
    "topic": ["The topic field is required when input_type is text."]
  }
}
```

**Job Not Found:**
```json
{
  "success": false,
  "error": "Job not found"
}
```

**Job Failed:**
```json
{
  "success": false,
  "error": "Export failed: Microservice error: Connection timeout"
}
```

---

## Examples

### Complete Workflow Example

#### Step 1: Generate Outline

```bash
curl -X POST http://localhost:8000/api/presentations/generate-outline \
  -H "Content-Type: application/json" \
  -d '{
    "input_type": "text",
    "topic": "Introduction to Machine Learning",
    "language": "English",
    "tone": "Professional",
    "length": "Medium"
  }'
```

**Response:**
```json
{
  "success": true,
  "job_id": "outline-job-123",
  "message": "Outline generation job created successfully"
}
```

#### Step 2: Poll for Outline Status

```bash
curl http://localhost:8000/api/presentations/status?job_id=outline-job-123
```

**Response (Processing):**
```json
{
  "success": true,
  "job_id": "outline-job-123",
  "status": "processing",
  "progress": 50,
  "stage": "polling_microservice"
}
```

#### Step 3: Get Outline Result

```bash
curl http://localhost:8000/api/presentations/result?job_id=outline-job-123
```

**Response:**
```json
{
  "success": true,
  "job_id": "outline-job-123",
  "tool_type": "presentation_outline",
  "result": {
    "outline": {
      "title": "Introduction to Machine Learning",
      "slides": [...]
    }
  }
}
```

#### Step 4: Generate Content

```bash
curl -X POST http://localhost:8000/api/presentations/generate-content \
  -H "Content-Type: application/json" \
  -d '{
    "outline": {
      "title": "Introduction to Machine Learning",
      "slides": [...]
    }
  }'
```

**Response:**
```json
{
  "success": true,
  "job_id": "content-job-456",
  "message": "Content generation job created successfully"
}
```

#### Step 5: Get Content Result

```bash
curl http://localhost:8000/api/presentations/result?job_id=content-job-456
```

**Response:**
```json
{
  "success": true,
  "job_id": "content-job-456",
  "tool_type": "presentation_content",
  "result": {
    "content": {
      "title": "Introduction to Machine Learning",
      "slides": [
        {
          "slide_number": 1,
          "header": "Introduction to Machine Learning",
          "content": "A Foundational Overview\nMachine Learning is..."
        }
      ]
    }
  }
}
```

#### Step 6: Export Presentation

```bash
curl -X POST http://localhost:8000/api/presentations/export \
  -H "Content-Type: application/json" \
  -d '{
    "content": {
      "title": "Introduction to Machine Learning",
      "slides": [...]
    },
    "template": "corporate_blue",
    "color_scheme": "blue",
    "font_style": "modern"
  }'
```

**Response:**
```json
{
  "success": true,
  "job_id": "export-job-789",
  "message": "Export job created successfully"
}
```

#### Step 7: Get Export Result

```bash
curl http://localhost:8000/api/presentations/result?job_id=export-job-789
```

**Response:**
```json
{
  "success": true,
  "job_id": "export-job-789",
  "tool_type": "presentation_export",
  "result": {
    "file_id": 123,
    "filename": "presentation_5_export-job-789.pptx",
    "download_url": "http://localhost:8000/storage/presentations/5/presentation_5_export-job-789.pptx",
    "file_size": 46523,
    "slides_count": 12
  }
}
```

---

## Notes

1. **Asynchronous Processing**: All operations (outline, content, export) are asynchronous. Always poll the status endpoint until the job is completed.

2. **Content Format**: The content generation endpoint returns content as **strings**, but the export endpoint accepts both string and array formats. The API automatically converts formats as needed.

3. **File Lifecycle**: Generated presentation files are automatically deleted after 1 month. The `expires_at` field indicates when the file will be deleted.

4. **Polling Interval**: Recommended polling interval is 2-3 seconds for status checks. Don't poll too frequently to avoid rate limiting.

5. **Error Recovery**: If a job fails, you can retry the same operation with the same data. The error message will indicate what went wrong.

6. **File Access**: Files are protected and can only be accessed by the user who generated them (based on `user_id`).

---

## Frontend Integration Examples

### Fetching Generated PowerPoint Files

#### 1. List All Presentation Files

```javascript
// React/Next.js Example
async function fetchPresentationFiles(page = 1, search = '') {
  try {
    const params = new URLSearchParams({
      per_page: '15',
      page: page.toString(),
      ...(search && { search })
    });

    const response = await fetch(
      `http://localhost:8000/api/presentations/files?${params}`,
      {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          // 'Authorization': `Bearer ${token}` // Optional
        }
      }
    );

    if (!response.ok) {
      throw new Error('Failed to fetch files');
    }

    const data = await response.json();
    
    if (data.success) {
      return {
        files: data.data,
        pagination: data.pagination
      };
    } else {
      throw new Error(data.error || 'Unknown error');
    }
  } catch (error) {
    console.error('Error fetching presentation files:', error);
    throw error;
  }
}

// Usage
const { files, pagination } = await fetchPresentationFiles(1, 'machine learning');
console.log('Files:', files);
console.log('Total:', pagination.total);
```

#### 2. Download a Specific File

```javascript
// Download file by ID
async function downloadPresentationFile(fileId, filename) {
  try {
    const response = await fetch(
      `http://localhost:8000/api/presentations/files/${fileId}/download`,
      {
        method: 'GET',
        headers: {
          'Accept': 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
          // 'Authorization': `Bearer ${token}` // Optional
        }
      }
    );

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.error || 'Download failed');
    }

    // Get the blob
    const blob = await response.blob();
    
    // Create download link
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename || 'presentation.pptx';
    document.body.appendChild(link);
    link.click();
    
    // Cleanup
    window.URL.revokeObjectURL(url);
    document.body.removeChild(link);
    
    return { success: true };
  } catch (error) {
    console.error('Error downloading file:', error);
    throw error;
  }
}

// Usage
await downloadPresentationFile(123, 'my-presentation.pptx');
```

#### 3. Edit Presentation (Get Content, Edit, Re-export)

```javascript
// Get editable content for a file
async function getFileContent(fileId) {
  try {
    const response = await fetch(
      `http://localhost:8000/api/presentations/files/${fileId}/content`
    );
    const data = await response.json();
    
    if (data.success) {
      return {
        content: data.content,
        template: data.template,
        color_scheme: data.color_scheme,
        font_style: data.font_style
      };
    } else {
      throw new Error(data.error || 'Failed to get content');
    }
  } catch (error) {
    console.error('Error fetching file content:', error);
    throw error;
  }
}

// Edit and re-export
async function editAndReExport(fileId, editedContent, newTemplate = null, newColorScheme = null, newFontStyle = null) {
  try {
    // Get original file info
    const fileInfo = await getFileContent(fileId);
    
    // Prepare export data with edited content
    const exportData = {
      content: editedContent, // Use edited content
      template: newTemplate || fileInfo.template,
      color_scheme: newColorScheme || fileInfo.color_scheme,
      font_style: newFontStyle || fileInfo.font_style
    };
    
    // Re-export (this creates a new file)
    const exportResponse = await fetch(
      'http://localhost:8000/api/presentations/export',
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(exportData)
      }
    );
    
    const exportResult = await exportResponse.json();
    
    if (exportResult.success) {
      return exportResult.job_id; // Return job_id for polling
    } else {
      throw new Error(exportResult.error);
    }
  } catch (error) {
    console.error('Error editing and re-exporting:', error);
    throw error;
  }
}

// Usage example
const fileId = 123;

// Step 1: Get editable content
const fileInfo = await getFileContent(fileId);

// Step 2: Edit the content
const editedContent = { ...fileInfo.content };
editedContent.title = "Updated Title";
editedContent.slides[0].content = "Updated content for first slide";
editedContent.slides[1].header = "Updated Header";

// Step 3: Re-export with changes (and optionally new template)
const jobId = await editAndReExport(fileId, editedContent, 'modern_white', 'red', 'modern');

// Step 4: Poll for export completion
// ... use job_id to poll status and get result
```

#### 4. Using Direct File URL

If you have the `file_url` from the list endpoint, you can also download directly:

```javascript
// Direct download using file_url from list response
function downloadFromUrl(fileUrl, filename) {
  const link = document.createElement('a');
  link.href = fileUrl;
  link.download = filename;
  link.target = '_blank'; // Open in new tab as fallback
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

// Usage
const file = {
  file_url: 'http://localhost:8000/storage/presentations/5/presentation_5_abc123.pptx',
  filename: 'presentation_5_abc123.pptx'
};
downloadFromUrl(file.file_url, file.filename);
```

#### 5. Complete React Component Example

```jsx
import React, { useState, useEffect } from 'react';

function PresentationFilesList() {
  const [files, setFiles] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [pagination, setPagination] = useState(null);

  useEffect(() => {
    fetchFiles();
  }, []);

  const fetchFiles = async () => {
    try {
      setLoading(true);
      const response = await fetch(
        'http://localhost:8000/api/presentations/files?per_page=15'
      );
      const data = await response.json();
      
      if (data.success) {
        setFiles(data.data);
        setPagination(data.pagination);
      } else {
        setError(data.error);
      }
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleDownload = async (fileId, filename) => {
    try {
      const response = await fetch(
        `http://localhost:8000/api/presentations/files/${fileId}/download`
      );
      
      if (!response.ok) {
        throw new Error('Download failed');
      }
      
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(link);
    } catch (err) {
      alert('Failed to download file: ' + err.message);
    }
  };

  const handleEdit = async (fileId) => {
    try {
      const response = await fetch(
        `http://localhost:8000/api/presentations/files/${fileId}/content`
      );
      const data = await response.json();
      
      if (data.success) {
        // Navigate to edit page with content
        // Or open edit modal with content
        console.log('Editable content:', data.content);
        // You can now edit this content and re-export
      } else {
        alert('Cannot edit: ' + data.error);
      }
    } catch (err) {
      alert('Failed to load content for editing: ' + err.message);
    }
  };

  const handleDelete = async (fileId) => {
    if (!confirm('Are you sure you want to delete this presentation?')) {
      return;
    }
    
    try {
      const response = await fetch(
        `http://localhost:8000/api/presentations/files/${fileId}`,
        { method: 'DELETE' }
      );
      const data = await response.json();
      
      if (data.success) {
        // Refresh file list
        fetchFiles();
      } else {
        alert('Failed to delete: ' + data.error);
      }
    } catch (err) {
      alert('Failed to delete file: ' + err.message);
    }
  };

  const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
  };

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;

  return (
    <div>
      <h2>My Presentations</h2>
      {files.length === 0 ? (
        <p>No presentations found</p>
      ) : (
        <ul>
          {files.map((file) => (
            <li key={file.id}>
              <div>
                <h3>{file.title}</h3>
                <p>Filename: {file.filename}</p>
                <p>Size: {formatFileSize(file.file_size)}</p>
                <p>Slides: {file.slides_count}</p>
                <p>Created: {new Date(file.created_at).toLocaleDateString()}</p>
                <div>
                  <button onClick={() => handleDownload(file.id, file.filename)}>
                    Download
                  </button>
                  <button onClick={() => handleEdit(file.id)}>
                    Edit
                  </button>
                  <button onClick={() => handleDelete(file.id)}>
                    Delete
                  </button>
                </div>
              </div>
            </li>
          ))}
        </ul>
      )}
      {pagination && (
        <div>
          Page {pagination.current_page} of {pagination.last_page}
          (Total: {pagination.total} files)
        </div>
      )}
    </div>
  );
}

export default PresentationFilesList;
```

#### 5. After Export Completion

When you get the export result, you can immediately fetch the file:

```javascript
// After export job completes
async function handleExportComplete(exportJobId) {
  // Get export result
  const resultResponse = await fetch(
    `http://localhost:8000/api/presentations/result?job_id=${exportJobId}`
  );
  const resultData = await resultResponse.json();
  
  if (resultData.success && resultData.result) {
    const { file_id, filename, download_url } = resultData.result;
    
    // Option 1: Download immediately
    await downloadPresentationFile(file_id, filename);
    
    // Option 2: Show download link
    console.log('Download URL:', download_url);
    
    // Option 3: Refresh file list
    const files = await fetchPresentationFiles();
    // Update UI with new file
  }
}
```

### Important Notes

1. **File URLs**: The `file_url` in the list response is a direct link to the file. You can use it directly in `<a>` tags or download it programmatically.

2. **File Expiration**: Files are automatically deleted after 1 month. Check the `expires_at` field before attempting to download.

3. **Authentication**: While the endpoints are public, you may want to add authentication headers for production use.

4. **Error Handling**: Always check the `success` field in responses and handle errors appropriately.

5. **File Size**: Use the `human_file_size` field for display, or format `file_size` (bytes) yourself.

---

## Support

For issues or questions, please contact the development team or refer to the main Zooys API documentation.

