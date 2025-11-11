# Presentation & Math Microservices - Complete API Documentation

**Last Updated:** November 2025  
**Status:** ✅ Production Ready

---

## 📋 Table of Contents

1. [Presentation Microservice](#presentation-microservice)
2. [Math Solver Microservice](#math-solver-microservice)
3. [Environment Configuration](#environment-configuration)
4. [Integration Guide](#integration-guide)

---

# Presentation Microservice API Documentation

**Version:** 3.1.0  
**Base URL:** `http://localhost:8001`  
**Authentication:** API Key via `X-API-Key` header

## Overview

The Presentation Microservice provides async job-based processing for generating PowerPoint presentations. All operations are handled asynchronously through a Redis-backed job scheduler. The service supports separate worker processes for scalable job processing.

## Authentication

All endpoints (except `/health`, `/`, and `/debug/api-key`) require API key authentication via the `X-API-Key` header.

```
X-API-Key: your-secret-api-key-here
```

## Endpoints

### 1. Health Check

**GET** `/health`

Check service health and status.

**Authentication:** Not required

**Response:**

```json
{
  "status": "healthy",
  "message": "Enhanced Presentation microservice is running",
  "timestamp": "2024-01-01 12:00:00",
  "services": {
    "ai_manager": true,
    "outline_generator": true,
    "content_generator": true,
    "powerpoint_generator": true,
    "job_scheduler": true,
    "redis": true,
    "file_storage": true
  }
}
```

---

### 2. Root Endpoint

**GET** `/`

Get service information and available endpoints.

**Authentication:** Not required

**Response:**

```json
{
  "success": true,
  "data": {
    "service": "Enhanced Presentation Microservice",
    "version": "3.1.0",
    "status": "running",
    "endpoints": {
      "health": "/health",
      "debug_api_key": "/debug/api-key",
      "generate_outline": "/generate-outline",
      "generate_content": "/generate-content",
      "export": "/export",
      "generate": "/generate",
      "templates": "/templates",
      "job_status": "/jobs/{job_id}/status",
      "job_result": "/jobs/{job_id}/result",
      "cancel_job": "/jobs/{job_id}/cancel"
    }
  },
  "timestamp": 1704110400.0
}
```

---

### 3. Generate Outline

**POST** `/generate-outline`

Submit a job to generate a presentation outline from content.

**Authentication:** Required

**Request Body:**

```json
{
  "content": "Your content here. This will be used to generate a presentation outline.",
  "language": "English",
  "tone": "Professional",
  "length": "Medium",
  "model": "deepseek-chat"
}
```

**Parameters:**

- `content` (string, required): The content to create outline from
- `language` (string, optional): Language for the presentation. Default: "English"
- `tone` (string, optional): Tone of the presentation. Default: "Professional"
- `length` (string, optional): Length of presentation. Options: "Short", "Medium", "Long". Default: "Medium"
- `model` (string, optional): AI model to use. Default: "deepseek-chat"

**Response:**

```json
{
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "queued",
  "message": "Outline generation job submitted"
}
```

---

### 4. Generate Content

**POST** `/generate-content`

Submit a job to generate detailed content for presentation slides.

**Authentication:** Required

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
      }
    ]
  },
  "language": "English",
  "tone": "Professional",
  "detail_level": "detailed",
  "model": "deepseek-chat"
}
```

**Response:**

```json
{
  "job_id": "550e8400-e29b-41d4-a716-446655440001",
  "status": "queued",
  "message": "Content generation job submitted"
}
```

---

### 5. Export Presentation

**POST** `/export`

Submit a job to export presentation to PowerPoint format.

**Authentication:** Required

**Request Body:**

```json
{
  "presentation_data": {
    "title": "Introduction to Machine Learning",
    "slides": [...]
  },
  "user_id": 123,
  "ai_result_id": 456,
  "template": "corporate_blue",
  "color_scheme": "blue",
  "font_style": "modern",
  "generate_missing_content": true
}
```

**Response:**

```json
{
  "job_id": "550e8400-e29b-41d4-a716-446655440002",
  "status": "queued",
  "message": "Export presentation job submitted"
}
```

---

### 6. Get Job Status

**GET** `/jobs/{job_id}/status`

Get the current status of a job.

**Authentication:** Required

**Response:**

```json
{
  "success": true,
  "data": {
    "job_id": "550e8400-e29b-41d4-a716-446655440000",
    "status": "running",
    "created_at": "2024-01-01T12:00:00",
    "started_at": "2024-01-01T12:00:01",
    "completed_at": null,
    "progress": 45.0,
    "message": "Generating presentation outline",
    "result": null,
    "error": null
  }
}
```

**Job Statuses:**

- `pending`: Job created but not yet queued
- `queued`: Job in queue waiting to be processed
- `running`: Job is currently being processed
- `completed`: Job completed successfully
- `failed`: Job failed with an error
- `cancelled`: Job was cancelled

---

### 7. Get Job Result

**GET** `/jobs/{job_id}/result`

Get the result of a completed job.

**Authentication:** Required

**Response (Success):**

```json
{
  "success": true,
  "data": {
    "success": true,
    "data": {
      "title": "Introduction to Machine Learning",
      "slides": [...]
    },
    "metadata": {
      "model": "deepseek-chat",
      "language": "English",
      "tone": "Professional"
    }
  }
}
```

---

### 8. Get Templates

**GET** `/templates`

Get available presentation templates.

**Authentication:** Required

**Response:**

```json
{
  "success": true,
  "templates": [
    {
      "id": "corporate_blue",
      "name": "Corporate Blue",
      "description": "Professional business theme",
      "colors": ["#003366", "#0066CC", "#FFFFFF"]
    }
  ]
}
```

---

## Available AI Models

All endpoints that generate content support the `model` parameter. If not provided, the service defaults to `deepseek-chat`.

**Available Models:**

- `deepseek-chat` (default)
- `gpt-4o`
- `gpt-3.5-turbo`
- `ollama:mistral`
- `ollama:llama3`
- `ollama:phi3:mini`

---

# Math Solver Microservice API Documentation

## Overview

The Math Solver Microservice is a comprehensive API for solving mathematical problems with AI-powered explanations. It supports both synchronous and asynchronous processing:

- **Text-only requests** (`problem_text`) → Processed **synchronously** and return results immediately
- **Image requests** (`problem_image`) → Automatically routed to **job queue** for async processing and return `job_id`

**Base URL:** `http://localhost:8002`  
**API Version:** 1.1.0

## Authentication

All endpoints except `/health` and `/` require API key authentication via the `X-API-Key` header.

```
X-API-Key: your_math_service_api_key_here
```

## Endpoints

### 1. Health Check

**GET** `/health`

Check the health status of the service.

**Headers:** None required

**Response:**

```json
{
  "status": "healthy",
  "services": {
    "ai_manager": {
      "available": true
    },
    "tesseract": {
      "available": false
    }
  },
  "version": "1.0.0"
}
```

---

### 2. Root Endpoint

**GET** `/`

Get basic service information.

**Response:**

```json
{
  "service": "Math Solver Microservice",
  "version": "1.0.0",
  "status": "running",
  "documentation": "/docs",
  "health": "/health"
}
```

---

### 3. Solve Problem

**POST** `/solve`

Solve a mathematical problem. **Behavior depends on input type:**

- **Text input** (`problem_text`) → Synchronous processing, returns solution immediately
- **Image input** (`problem_image`) → Automatically routes to job queue, returns `job_id`

**Headers:**

```
X-API-Key: your_math_service_api_key_here
Content-Type: application/json
```

**Request Body (Text Input - Synchronous):**

```json
{
  "problem_text": "2x + 5 = 13",
  "subject_area": "algebra",
  "difficulty_level": "intermediate",
  "timeout_ms": 30000
}
```

**Request Body (Image Input - Async):**

```json
{
  "problem_image": "base64_encoded_image_data",
  "image_type": "auto",
  "subject_area": "algebra",
  "timeout_ms": 30000
}
```

**Response (Text Input - Synchronous):**

```json
{
  "success": true,
  "processing_mode": "sync",
  "classification": {
    "subject": "algebra",
    "confidence": 0.9
  },
  "solution": {
    "answer": "4",
    "steps": [
      {
        "step_number": 1,
        "operation": "subtract",
        "description": "Subtract 5 from both sides",
        "expression": "2x = 8"
      }
    ],
    "method": "algebraic_solver",
    "confidence": 1.0
  }
}
```

**Response (Image Input - Async):**

```json
{
  "success": true,
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "pending",
  "message": "Image processing job submitted. Use /jobs/{job_id}/status to check status and /jobs/{job_id}/result to get results.",
  "estimated_wait_time": 10,
  "processing_mode": "async"
}
```

---

### 4. Explain Problem

**POST** `/explain`

Solve a mathematical problem and provide an AI-powered explanation.

**Request Body (Text Input - Synchronous):**

```json
{
  "problem_text": "2x + 5 = 13",
  "subject_area": "algebra",
  "difficulty_level": "intermediate",
  "include_explanation": true,
  "explanation_style": "educational"
}
```

**Response (Text Input - Synchronous):**

```json
{
  "success": true,
  "processing_mode": "sync",
  "solution": {
    "answer": "4",
    "steps": [...]
  },
  "explanation": {
    "content": "To solve this, we subtract 5 from both sides to isolate x. This gives us 2x = 8. Therefore, x = 4.",
    "model": "deepseek-chat"
  }
}
```

---

### 5. Get Job Status

**GET** `/jobs/{job_id}/status`

Get the current status of a job.

**Response:**

```json
{
  "success": true,
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "processing",
  "created_at": 1705312200.123,
  "updated_at": 1705312205.456,
  "error": null,
  "queue_position": null
}
```

**Status Values:**

- `pending` - Job is waiting in queue
- `processing` - Job is currently being processed
- `completed` - Job completed successfully
- `failed` - Job failed with an error
- `cancelled` - Job was cancelled

---

### 6. Get Job Result

**GET** `/jobs/{job_id}/result`

Get the result of a completed job.

**Response (Completed Job):**

```json
{
  "success": true,
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "completed",
  "result": {
    "success": true,
    "answer": "4",
    "steps": [...],
    "classification": {
      "subject": "algebra",
      "confidence": 0.9
    }
  },
  "processing_time": 1.25
}
```

---

## Subject Areas

Supported subject areas:

- `algebra` - Algebraic equations and expressions
- `calculus` - Derivatives, integrals, limits
- `geometry` - Shapes, areas, volumes
- `statistics` - Mean, median, probability
- `arithmetic` - Basic math operations
- `trigonometry` - Trigonometric functions
- `maths` - General mathematics

---

## Difficulty Levels

- `beginner` - Simple problems suitable for beginners
- `intermediate` - Moderate complexity problems
- `advanced` - Complex problems requiring advanced knowledge

---

## Processing Modes

Responses include a `processing_mode` field:

- `"sync"` - Synchronous processing (text-only requests)
- `"async"` - Asynchronous processing (image requests via job queue)

---

# Environment Configuration

## Required Environment Variables

Add these to your `.env` file:

```env
# ===========================================
# Presentation Microservice Configuration
# ===========================================
PRESENTATION_MICROSERVICE_URL=http://localhost:8001
PRESENTATION_MICROSERVICE_API_KEY=your-secret-api-key-here
PRESENTATION_MICROSERVICE_TIMEOUT=300

# ===========================================
# Math Solver Microservice Configuration
# ===========================================
MATH_MICROSERVICE_URL=http://localhost:8002
MATH_MICROSERVICE_API_KEY=your_math_service_api_key_here
MATH_MICROSERVICE_TIMEOUT=60

# ===========================================
# Redis Configuration (Required for async jobs)
# ===========================================
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_DB=0
REDIS_PASSWORD=
```

---

## Configuration Details

### Presentation Microservice

- **PRESENTATION_MICROSERVICE_URL**: Base URL of the presentation microservice (default: `http://localhost:8001`)
- **PRESENTATION_MICROSERVICE_API_KEY**: API key for authentication (required)
- **PRESENTATION_MICROSERVICE_TIMEOUT**: Request timeout in seconds (default: 300)

### Math Solver Microservice

- **MATH_MICROSERVICE_URL**: Base URL of the math solver microservice (default: `http://localhost:8002`)
- **MATH_MICROSERVICE_API_KEY**: API key for authentication (required)
- **MATH_MICROSERVICE_TIMEOUT**: Request timeout in seconds (default: 60)

### Redis Configuration

- **REDIS_HOST**: Redis server hostname (default: `localhost`)
- **REDIS_PORT**: Redis server port (default: `6379`)
- **REDIS_DB**: Redis database number (default: `0`)
- **REDIS_PASSWORD**: Redis password (leave empty if no password)

**Note:** Redis is required for async job processing in both microservices. Text-only math requests work without Redis, but image processing requires it.

---

# Integration Guide

## Laravel Service Configuration

Update `config/services.php`:

```php
'presentation_microservice' => [
    'url' => env('PRESENTATION_MICROSERVICE_URL', 'http://localhost:8001'),
    'api_key' => env('PRESENTATION_MICROSERVICE_API_KEY'),
    'timeout' => env('PRESENTATION_MICROSERVICE_TIMEOUT', 300),
],

'math_microservice' => [
    'url' => env('MATH_MICROSERVICE_URL', 'http://localhost:8002'),
    'api_key' => env('MATH_MICROSERVICE_API_KEY'),
    'timeout' => env('MATH_MICROSERVICE_TIMEOUT', 60),
],
```

## Usage Examples

### Presentation Service

```php
use App\Services\AIPresentationService;

$presentationService = app(AIPresentationService::class);

$result = $presentationService->generateOutline([
    'input_type' => 'text',
    'content' => 'Your content here',
    'language' => 'English',
    'tone' => 'Professional',
    'length' => 'Medium'
], $userId);
```

### Math Service

```php
use App\Services\AIMathService;

$mathService = app(AIMathService::class);

$result = $mathService->solveMathProblem([
    'problem_text' => '2x + 5 = 13',
    'problem_type' => 'text',
    'subject_area' => 'algebra',
    'difficulty_level' => 'intermediate'
], $userId);
```

---

## Worker Setup

### Presentation Microservice

To process jobs asynchronously, run worker processes:

```bash
# Start a worker
python worker.py

# Or use batch file
start_worker.bat
```

### Math Solver Microservice

Workers are only needed for image processing. Text-only requests work synchronously.

```bash
# Start a worker (for image processing)
python worker.py
```

---

## Troubleshooting

### Common Issues

1. **401 Unauthorized**
   - Verify API keys are correct in `.env`
   - Check that `X-API-Key` header is being sent

2. **503 Service Unavailable**
   - Check if Redis is running
   - Verify Redis connection settings

3. **Timeout Errors**
   - Increase timeout values in `.env`
   - Check network connectivity

4. **Job Not Processing**
   - Ensure worker processes are running
   - Check Redis queue status

---

**Last Updated:** November 2025  
**Documentation Version:** 1.0  
**Status:** ✅ Production Ready

