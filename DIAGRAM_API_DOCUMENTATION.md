# Diagram Module - Complete API Documentation

**Version:** 1.0  
**Last Updated:** November 2025  
**Base URL:** `http://your-domain.com/api`  
**Status:** ✅ Production Ready

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Endpoints](#endpoints)
4. [Supported Diagram Types](#supported-diagram-types)
5. [Request/Response Formats](#requestresponse-formats)
6. [Error Handling](#error-handling)
7. [Examples](#examples)
8. [Workflows](#workflows)
9. [Image Management](#image-management)
10. [Technical Details](#technical-details)

---

## 🎯 Overview

The Diagram Module provides a complete API for generating various types of diagrams using AI. It acts as a gateway between the frontend and the Diagram Microservice, handling job management, image downloads, and lifecycle management.

### **Key Features**

- Generate 13+ types of diagrams (flowcharts, charts, timelines, etc.)
- Async job processing with status tracking via **Universal Job Scheduler**
- Automatic image download and storage
- Public URL generation for frontend access
- Complete lifecycle management
- Support for multiple languages
- **Queue Worker Integration** for background processing

### **Architecture**

The Diagram Module uses the **Universal Job Scheduler** and **Queue Worker** system:

1. **Job Creation:** Creates a universal job via `UniversalJobService`
2. **Queue Processing:** Jobs are queued and processed asynchronously by queue workers
3. **Stage Tracking:** Detailed progress tracking through multiple stages
4. **Microservice Integration:** Communicates with Diagram Microservice (port 8005)
5. **Image Management:** Downloads and stores images from microservice

### **Processing Mode**

- **All requests** → Asynchronous processing via **Queue Workers** (returns `job_id`, poll for results)
- Uses **Universal Job Scheduler** for job management and status tracking
- Jobs are processed in the background, allowing immediate HTTP response

---

## 🔐 Authentication

All endpoints require Bearer token authentication:

```http
Authorization: Bearer {your-token}
```

---

## 🌐 Endpoints

### **1. Generate Diagram**

Generate a diagram from a text prompt.

**Endpoint:** `POST /api/diagram/generate`

**Authentication:** Required

**Request Body:**

```json
{
  "prompt": "User login process: Start -> Enter credentials -> Validate -> Success or Error -> End",
  "diagram_type": "flowchart",
  "language": "en"
}
```

**Request Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `prompt` | string | Yes | Description/instruction for the diagram (max: 2000 chars) |
| `diagram_type` | string | Yes | Type of diagram to generate (see [Supported Diagram Types](#supported-diagram-types)) |
| `language` | string | No | Language code (default: `"en"`) |

**Response:**

```json
{
  "success": true,
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "pending",
  "message": "Diagram generation job created",
  "poll_url": "http://your-domain.com/api/diagram/status?job_id=550e8400-e29b-41d4-a716-446655440000",
  "result_url": "http://your-domain.com/api/diagram/result?job_id=550e8400-e29b-41d4-a716-446655440000"
}
```

**Example Request:**

```bash
curl -X POST http://your-domain.com/api/diagram/generate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "User login process: Start -> Enter credentials -> Validate -> Success or Error -> End",
    "diagram_type": "flowchart",
    "language": "en"
  }'
```

---

### **2. Get Job Status**

Get the current status of a diagram generation job.

**Endpoint:** `GET /api/diagram/status?job_id={jobId}`

**Authentication:** Required

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `job_id` | string | Yes | Job ID returned from generate endpoint |

**Response:**

```json
{
  "success": true,
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "tool_type": "diagram",
  "status": "processing",
  "progress": 65.0,
  "stage": "polling_microservice",
  "error": null,
  "created_at": "2024-01-01T12:00:00Z",
  "updated_at": "2024-01-01T12:01:00Z"
}
```

**Status Values:**

- `pending` - Job created, waiting to be processed
- `processing` - Job is currently being processed
- `completed` - Job completed successfully
- `failed` - Job failed with an error

**Stages:**

- `initializing` - Job created and queued for processing
- `generating_diagram` - Creating job on microservice (20% progress)
- `polling_microservice` - Waiting for microservice to complete (40-80% progress)
- `downloading_image` - Downloading image from microservice (85% progress)
- `finalizing` - Finalizing and storing image (95% progress)

**Example Request:**

```bash
curl -X GET "http://your-domain.com/api/diagram/status?job_id=550e8400-e29b-41d4-a716-446655440000" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### **3. Get Job Result**

Get the result of a completed diagram generation job.

**Endpoint:** `GET /api/diagram/result?job_id={jobId}`

**Authentication:** Required

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `job_id` | string | Yes | Job ID returned from generate endpoint |

**Response (Completed):**

```json
{
  "success": true,
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "tool_type": "diagram",
  "data": {
    "ai_result_id": 123,
    "image_url": "http://your-domain.com/storage/diagrams/2024-01-01/diagram_550e8400_123.png",
    "image_path": "diagrams/2024-01-01/diagram_550e8400_123.png",
    "image_filename": "diagram_550e8400_123.png",
    "diagram_type": "flowchart",
    "prompt": "User login process: Start -> Enter credentials -> Validate -> Success or Error -> End"
  },
  "metadata": {
    "diagram_type": "flowchart",
    "language": "en",
    "microservice_job_id": "a95d58fc-6e56-4576-a0aa-206b35c25de6",
    "processing_stages": ["generating_diagram", "polling_microservice", "downloading_image", "finalizing"]
  }
}
```

**Response (Not Completed - 202 Accepted):**

```json
{
  "success": false,
  "error": "Job not completed yet",
  "status": "processing",
  "progress": 65.0
}
```

**Example Request:**

```bash
curl -X GET "http://your-domain.com/api/diagram/result?job_id=550e8400-e29b-41d4-a716-446655440000" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### **4. List Diagrams**

Get list of user's diagrams.

**Endpoint:** `GET /api/diagram`

**Authentication:** Required

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
      "title": "Diagram Generation",
      "result_data": {
        "image_url": "http://your-domain.com/storage/diagrams/2024-01-01/diagram_550e8400_123.png",
        "diagram_type": "flowchart"
      },
      "created_at": "2024-01-01T12:00:00Z",
      "updated_at": "2024-01-01T12:05:00Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "total": 45
  }
}
```

---

### **5. Get Diagram**

Get specific diagram by ID.

**Endpoint:** `GET /api/diagram/{aiResultId}`

**Authentication:** Required

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `aiResultId` | integer | Diagram result ID |

**Response:**

```json
{
  "success": true,
  "data": {
    "id": 123,
    "title": "Diagram Generation",
    "result_data": {
      "image_url": "http://your-domain.com/storage/diagrams/2024-01-01/diagram_550e8400_123.png",
      "image_path": "diagrams/2024-01-01/diagram_550e8400_123.png",
      "image_filename": "diagram_550e8400_123.png",
      "diagram_type": "flowchart",
      "prompt": "User login process...",
      "status": "completed"
    },
    "metadata": {
      "diagram_type": "flowchart",
      "language": "en",
      "microservice_job_id": "a95d58fc-6e56-4576-a0aa-206b35c25de6"
    },
    "created_at": "2024-01-01T12:00:00Z",
    "updated_at": "2024-01-01T12:05:00Z"
  }
}
```

---

### **6. Delete Diagram**

Delete a diagram and its associated image.

**Endpoint:** `DELETE /api/diagram/{aiResultId}`

**Authentication:** Required

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `aiResultId` | integer | Diagram result ID |

**Response:**

```json
{
  "success": true,
  "message": "Diagram deleted successfully"
}
```

---

### **7. Get Diagram Types**

Get list of supported diagram types.

**Endpoint:** `GET /api/diagram/types`

**Authentication:** Required

**Response:**

```json
{
  "success": true,
  "data": {
    "graph_based": [
      "flowchart",
      "sequence",
      "class",
      "state",
      "er",
      "user_journey",
      "block",
      "mindmap"
    ],
    "chart_based": [
      "pie",
      "quadrant",
      "timeline",
      "sankey",
      "xy"
    ],
    "all": [
      "flowchart",
      "sequence",
      "class",
      "state",
      "er",
      "user_journey",
      "block",
      "mindmap",
      "pie",
      "quadrant",
      "timeline",
      "sankey",
      "xy"
    ]
  }
}
```

---

### **8. Check Health**

Check if diagram microservice is available.

**Endpoint:** `GET /api/diagram/health`

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

### **9. Get Job Status (Alternative Endpoint)**

Alternative endpoint for job status.

**Endpoint:** `GET /api/status/diagram?job_id={jobId}`

**Authentication:** Required

**Response:** Same format as `/api/diagram/status`

---

### **10. Get Job Result (Alternative Endpoint)**

Alternative endpoint for job result.

**Endpoint:** `GET /api/result/diagram?job_id={jobId}`

**Authentication:** Required

**Response:** Same format as `/api/diagram/result`

---

## 📊 Supported Diagram Types

### **Graph-based Diagrams** (Graphviz)

These diagrams use JSON-based approach for reliable syntax.

| Type | Description | Example Prompt |
|------|-------------|----------------|
| `flowchart` | Flow charts showing processes | "User login: Start -> Login -> Validate -> Success" |
| `sequence` | Sequence diagrams showing interactions | "User -> Frontend -> Backend -> Database" |
| `class` | Class diagrams showing object relationships | "Book class with title, author, ISBN" |
| `state` | State diagrams showing state transitions | "Order: New -> Processing -> Shipped -> Delivered" |
| `er` | Entity-relationship diagrams | "Customer, Product, Order entities with relationships" |
| `user_journey` | User journey maps | "Shopping: Browse -> Cart -> Checkout -> Payment" |
| `block` | Block diagrams showing system architecture | "Frontend -> API -> Services -> Database" |
| `mindmap` | Mind maps with central topic and branches | "Project Management: Planning, Development, Testing" |

### **Chart-based Diagrams** (matplotlib/plotly)

| Type | Description | Example Prompt |
|------|-------------|----------------|
| `pie` | Pie charts showing proportions | "Market share: Apple 30%, Samsung 25%, Others 45%" |
| `quadrant` | Quadrant charts (2D scatter with quadrants) | "Products: X-axis (Price), Y-axis (Quality)" |
| `timeline` | Timeline charts showing events over time | "Project: Q1 Planning, Q2 Development, Q3 Testing" |
| `sankey` | Sankey diagrams showing flow | "Energy: Source -> Production -> Distribution" |
| `xy` | XY scatter/line charts | "Sales: Jan 100, Feb 150, Mar 200" |

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
| `202` | Accepted | Job still processing (for result endpoint) |

### **Error Response Format**

```json
{
  "success": false,
  "error": "Validation failed",
  "details": {
    "prompt": ["The prompt field is required."],
    "diagram_type": ["The diagram type field is required."]
  }
}
```

---

## 💻 Examples

### **Example 1: Generate Flowchart**

```bash
# Step 1: Generate diagram
curl -X POST http://your-domain.com/api/diagram/generate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "User login process: Start -> Enter credentials -> Validate -> Success or Error -> End",
    "diagram_type": "flowchart",
    "language": "en"
  }'

# Response:
# {
#   "success": true,
#   "job_id": "550e8400-e29b-41d4-a716-446655440000",
#   "status": "pending"
# }

# Step 2: Poll for status (every 2-3 seconds)
curl -X GET "http://your-domain.com/api/diagram/status?job_id=550e8400-e29b-41d4-a716-446655440000" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Step 3: Get result when completed
curl -X GET "http://your-domain.com/api/diagram/result?job_id=550e8400-e29b-41d4-a716-446655440000" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Response includes image_url:
# {
#   "success": true,
#   "data": {
#     "image_url": "http://your-domain.com/storage/diagrams/2024-01-01/diagram_550e8400_123.png"
#   }
# }
```

### **Example 2: Generate Pie Chart**

```bash
curl -X POST http://your-domain.com/api/diagram/generate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "Market share: Apple 30%, Samsung 25%, Others 45%",
    "diagram_type": "pie",
    "language": "en"
  }'
```

### **Example 3: Generate Sequence Diagram**

```bash
curl -X POST http://your-domain.com/api/diagram/generate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "User -> Frontend -> Backend -> Database",
    "diagram_type": "sequence",
    "language": "en"
  }'
```

### **Example 4: List All Diagrams**

```bash
curl -X GET "http://your-domain.com/api/diagram?page=1&per_page=15" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 🔄 Workflows

### **Standard Workflow**

1. **Generate Diagram** → `POST /api/diagram/generate`
   - Creates job in **Universal Job Scheduler**
   - Queues job for **Queue Worker** processing
   - Returns `job_id` immediately (non-blocking)
   - Job is processed asynchronously in background

2. **Poll Status** → `GET /api/diagram/status?job_id={id}`
   - Check every 2-3 seconds
   - Monitor `progress` (0-100) and `stage`
   - Wait until `status` is `completed`
   - Status tracked by Universal Job Scheduler

3. **Get Result** → `GET /api/diagram/result?job_id={id}`
   - Returns `image_url` (public Laravel URL)
   - Includes `ai_result_id` for future reference
   - Frontend can directly access image

4. **Access Image** → Use `image_url` from result
   - Image is stored in Laravel public storage
   - URL is publicly accessible
   - No authentication required for image URLs

### **Processing Architecture**

```
Frontend Request
    ↓
POST /api/diagram/generate
    ↓
UniversalJobService.createJob()
    ↓
UniversalJobService.queueJob()
    ↓
Queue Worker (Background)
    ↓
UniversalJobService.processDiagramJobWithStages()
    ↓
Stage 1: AIDiagramService.generateDiagram()
    → Calls Diagram Microservice
    → Gets microservice_job_id
    ↓
Stage 2: Poll Microservice Status
    → Checks every 2 seconds
    → Updates progress (40-80%)
    ↓
Stage 3: AIDiagramService.getJobResult()
    → Downloads image from microservice
    → Stores in Laravel storage
    ↓
Stage 4: Complete Job
    → Updates job status to 'completed'
    → Returns image_url to frontend
```

### **Complete Example Flow**

```javascript
// 1. Generate diagram
const generateResponse = await fetch('/api/diagram/generate', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    prompt: 'User login process: Start -> Login -> Validate -> Success',
    diagram_type: 'flowchart',
    language: 'en'
  })
});

const { job_id } = await generateResponse.json();

// 2. Poll for status
const pollStatus = async () => {
  const statusResponse = await fetch(`/api/diagram/status?job_id=${job_id}`, {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  const status = await statusResponse.json();
  
  if (status.status === 'completed') {
    // 3. Get result
    const resultResponse = await fetch(`/api/diagram/result?job_id=${job_id}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const result = await resultResponse.json();
    
    // 4. Display image
    const imageUrl = result.data.image_url;
    document.getElementById('diagram').src = imageUrl;
  } else if (status.status === 'processing') {
    // Continue polling
    setTimeout(pollStatus, 2000);
  }
};

pollStatus();
```

---

## 🖼️ Image Management

### **Storage Location**

- **Path:** `storage/app/public/diagrams/{YYYY-MM-DD}/diagram_{job_id}_{ai_result_id}.png`
- **Public URL:** `http://your-domain.com/storage/diagrams/{date}/{filename}.png`

### **Image Lifecycle**

1. **Generation:** Image is downloaded from microservice when job completes
2. **Storage:** Image is stored in Laravel public storage
3. **Access:** Public URL is generated and returned in result
4. **Deletion:** Image is automatically deleted when diagram is deleted via API

### **Public Access**

- Images are stored in `public` disk
- Public URLs are automatically generated
- Frontend can directly access images via `<img src="{image_url}">`
- No additional authentication required for image URLs

---

## 📝 Notes

1. **Async Processing:** All diagram generation is asynchronous via **Queue Workers**. Use polling to check job status.

2. **Queue Worker:** Jobs are processed by Laravel queue workers. Ensure queue workers are running:
   ```bash
   php artisan queue:work
   ```

3. **Universal Job Scheduler:** The module uses `UniversalJobService` for:
   - Job creation and tracking
   - Status management
   - Progress updates
   - Error handling

4. **Polling Interval:** Poll status endpoint every 2-3 seconds until completed.

5. **Maximum Wait Time:** Jobs typically complete within 30-120 seconds depending on diagram complexity.

6. **Image Format:** All diagrams are generated as PNG files.

7. **Image Retention:** Images are stored permanently until explicitly deleted via API.

8. **Language Support:** Supported languages: `en`, `es`, `fr`, `de`, `it`, `pt`, `zh`, `ja`

9. **Prompt Guidelines:**
   - Be specific and descriptive
   - Include relationships and flow for graph diagrams
   - Include data values for chart diagrams
   - Maximum length: 2000 characters

10. **Job Processing Flow:**
    - Request received → Job created in Universal Job Scheduler
    - Job queued → Processed by queue worker
    - Microservice called → Diagram generation started
    - Status polled → Until microservice completes
    - Image downloaded → Stored in Laravel storage
    - Public URL generated → Returned to frontend

---

## 🔍 Diagram Type Examples

### **Flowchart**
```json
{
  "prompt": "User login process: Start -> Enter credentials -> Validate -> Success or Error -> End",
  "diagram_type": "flowchart"
}
```

### **Sequence Diagram**
```json
{
  "prompt": "User -> Frontend -> Backend -> Database",
  "diagram_type": "sequence"
}
```

### **Pie Chart**
```json
{
  "prompt": "Market share: Apple 30%, Samsung 25%, Others 45%",
  "diagram_type": "pie"
}
```

### **Timeline**
```json
{
  "prompt": "Project timeline: Q1 Planning, Q2 Development, Q3 Testing, Q4 Launch",
  "diagram_type": "timeline"
}
```

---

## 🔧 Technical Details

### **Universal Job Scheduler Integration**

The Diagram Module is fully integrated with the Universal Job Scheduler:

- **Job Creation:** `UniversalJobService::createJob('diagram', ...)`
- **Job Queuing:** `UniversalJobService::queueJob($jobId)`
- **Job Processing:** `UniversalJobService::processDiagramJobWithStages()`
- **Status Tracking:** Real-time progress and stage updates
- **Error Handling:** Automatic job failure handling

### **Queue Worker Requirements**

For optimal performance, ensure queue workers are running:

```bash
# Start queue worker
php artisan queue:work

# Or use supervisor/systemd for production
```

**Queue Configuration:**
- Default: Uses Laravel queue system
- Fallback: Background process if queue is 'sync'
- Timeout: 120 seconds for diagram generation

### **Microservice Communication**

- **Base URL:** `http://localhost:8005` (configurable via `.env`)
- **Authentication:** `X-API-Key` header
- **API Key:** `DIAGRAM_MICROSERVICE_API_KEY` environment variable
- **Timeout:** 120 seconds (configurable)

### **Job Storage**

- **Storage:** Laravel Cache (2 hour TTL)
- **Key Format:** `universal_job_{jobId}`
- **Status Updates:** Real-time via cache

---

**Last Updated:** November 2025  
**Documentation Version:** 1.1  
**Status:** ✅ Production Ready  
**Queue Worker:** ✅ Integrated  
**Universal Job Scheduler:** ✅ Integrated

