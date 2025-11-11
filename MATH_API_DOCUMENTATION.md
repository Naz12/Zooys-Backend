# Math Module - Complete API Documentation

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

The Math Module provides a comprehensive API for solving mathematical problems with step-by-step explanations. It supports both text and image inputs, with automatic routing to synchronous (text) or asynchronous (image) processing.

### **Key Features**

- Solve mathematical problems from text or images
- Step-by-step solutions with explanations
- Support for word problems (natural language)
- Multiple subject areas (algebra, geometry, calculus, etc.)
- Problem history and statistics
- Async job processing for image problems

### **Processing Modes**

- **Text Input** → Synchronous processing (immediate response)
- **Image Input** → Asynchronous processing (returns job_id, poll for results)

---

## 🔐 Authentication

All endpoints require Bearer token authentication:

```http
Authorization: Bearer {your-token}
```

**Exception:** Job status and result endpoints may work with manual token validation.

---

## 🌐 Endpoints

### **1. Solve Math Problem**

Solve a mathematical problem from text or image.

**Endpoint:** `POST /math/solve`

**Authentication:** Required

**Request Body:**

```json
{
  "problem_text": "2x + 5 = 13",
  "file_id": "file-id-here (optional, if image)",
  "subject_area": "algebra|geometry|calculus|statistics|trigonometry|arithmetic|maths",
  "difficulty_level": "beginner|intermediate|advanced"
}
```

**Request Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `problem_text` | string | Conditional | Required if `file_id` is not provided |
| `file_id` | string | Conditional | Required if `problem_text` is not provided |
| `subject_area` | string | No | Default: `general` |
| `difficulty_level` | string | No | Default: `intermediate` |

**Response (Text Input - Synchronous):**

```json
{
  "success": true,
  "data": {
    "solution": {
      "answer": "4",
      "steps": [
        {
          "step_number": 1,
          "description": "Subtract 5 from both sides",
          "expression": "2x = 8"
        },
        {
          "step_number": 2,
          "description": "Divide both sides by 2",
          "expression": "x = 4"
        }
      ],
      "explanation": "To solve this equation, we first isolate the variable by subtracting 5 from both sides, then divide by 2 to get x = 4.",
      "method": "algebraic_solver"
    },
    "math_problem_id": 123,
    "math_solution_id": 456,
    "ai_result": {
      "id": 789,
      "result_data": {...}
    }
  }
}
```

**Response (Image Input - Asynchronous):**

```json
{
  "success": true,
  "data": {
    "job_id": "550e8400-e29b-41d4-a716-446655440000",
    "status": "pending",
    "message": "Image processing job submitted. Use /status/math/image?job_id={job_id} to check status."
  }
}
```

**Example Requests:**

**Text Problem:**
```bash
curl -X POST http://your-domain.com/api/math/solve \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "problem_text": "2x + 5 = 13",
    "subject_area": "algebra",
    "difficulty_level": "intermediate"
  }'
```

**Word Problem:**
```bash
curl -X POST http://your-domain.com/api/math/solve \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "problem_text": "what is two plus two",
    "subject_area": "arithmetic",
    "difficulty_level": "beginner"
  }'
```

**Image Problem:**
```bash
curl -X POST http://your-domain.com/api/math/solve \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "file_id": "abc123",
    "subject_area": "algebra",
    "difficulty_level": "intermediate"
  }'
```

---

### **2. List Math Problems**

Get list of user's math problems with pagination and filters.

**Endpoint:** `GET /math/problems`

**Authentication:** Required

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `page` | integer | No | Page number (default: 1) |
| `per_page` | integer | No | Items per page (default: 15) |
| `subject` | string | No | Filter by subject area |
| `difficulty` | string | No | Filter by difficulty level |

**Response:**

```json
{
  "data": [
    {
      "id": 123,
      "problem_text": "2x + 5 = 13",
      "problem_type": "text",
      "subject_area": "algebra",
      "difficulty_level": "intermediate",
      "created_at": "2024-01-01T12:00:00Z",
      "solutions": [
        {
          "id": 456,
          "final_answer": "4",
          "solution_method": "algebraic_solver",
          "created_at": "2024-01-01T12:00:01Z"
        }
      ]
    },
    {
      "id": 124,
      "problem_text": "what is 15 + 27",
      "problem_type": "text",
      "subject_area": "arithmetic",
      "difficulty_level": "beginner",
      "created_at": "2024-01-01T13:00:00Z",
      "solutions": [...]
    }
  ],
  "current_page": 1,
  "last_page": 3,
  "per_page": 15,
  "total": 45
}
```

**Example Request:**

```bash
curl -X GET "http://your-domain.com/api/math/problems?page=1&per_page=15&subject=algebra" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### **3. Get Math Problem**

Get specific math problem with all solutions.

**Endpoint:** `GET /math/problems/{id}`

**Authentication:** Required

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Math problem ID |

**Response:**

```json
{
  "id": 123,
  "user_id": 1,
  "problem_text": "2x + 5 = 13",
  "problem_type": "text",
  "subject_area": "algebra",
  "difficulty_level": "intermediate",
  "created_at": "2024-01-01T12:00:00Z",
  "solutions": [
    {
      "id": 456,
      "math_problem_id": 123,
      "solution_method": "algebraic_solver",
      "step_by_step_solution": "Step 1: Subtract 5 from both sides\n2x = 8\n\nStep 2: Divide both sides by 2\nx = 4",
      "final_answer": "4",
      "explanation": "To solve this equation, we first isolate the variable by subtracting 5 from both sides, then divide by 2 to get x = 4.",
      "verification": "Solution verified by microservice",
      "created_at": "2024-01-01T12:00:01Z",
      "metadata": {
        "solver_used": "microservice",
        "processing_time": 0.15,
        "processing_mode": "sync"
      }
    }
  ]
}
```

**Example Request:**

```bash
curl -X GET http://your-domain.com/api/math/problems/123 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### **4. Delete Math Problem**

Delete a math problem and its solutions.

**Endpoint:** `DELETE /math/problems/{id}`

**Authentication:** Required

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Math problem ID |

**Response:**

```json
{
  "success": true,
  "message": "Math problem deleted successfully"
}
```

**Example Request:**

```bash
curl -X DELETE http://your-domain.com/api/math/problems/123 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### **5. Get Math History**

Get user's math problem solving history.

**Endpoint:** `GET /math/history`

**Authentication:** Required

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `limit` | integer | No | Number of results (default: 20) |

**Response:**

```json
{
  "data": [
    {
      "id": 123,
      "problem_text": "2x + 5 = 13",
      "subject_area": "algebra",
      "difficulty_level": "intermediate",
      "created_at": "2024-01-01T12:00:00Z",
      "solution": {
        "final_answer": "4",
        "explanation": "..."
      }
    },
    {
      "id": 124,
      "problem_text": "what is 15 + 27",
      "subject_area": "arithmetic",
      "difficulty_level": "beginner",
      "created_at": "2024-01-01T13:00:00Z",
      "solution": {
        "final_answer": "42",
        "explanation": "..."
      }
    }
  ]
}
```

**Example Request:**

```bash
curl -X GET http://your-domain.com/api/math/history \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### **6. Get Math Statistics**

Get user's math solving statistics.

**Endpoint:** `GET /math/stats`

**Authentication:** Required

**Response:**

```json
{
  "total_problems": 50,
  "total_solutions": 48,
  "success_rate": 96.0,
  "subject_stats": {
    "algebra": 20,
    "geometry": 15,
    "calculus": 10,
    "arithmetic": 5
  },
  "difficulty_stats": {
    "beginner": 10,
    "intermediate": 30,
    "advanced": 10
  },
  "recent_problems": [
    {
      "id": 123,
      "problem_text": "2x + 5 = 13",
      "created_at": "2024-01-01T12:00:00Z"
    }
  ]
}
```

**Example Request:**

```bash
curl -X GET http://your-domain.com/api/math/stats \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### **7. Generate Math Solution (Client Alias)**

Alias for `/math/solve` endpoint.

**Endpoint:** `POST /client/math/generate`

**Authentication:** Required

**Request Body:** Same as `/math/solve`

**Response:** Same as `/math/solve`

---

### **8. Get Math Help (Client Alias)**

Alias for `/math/solve` endpoint.

**Endpoint:** `POST /client/math/help`

**Authentication:** Required

**Request Body:** Same as `/math/solve`

**Response:** Same as `/math/solve`

---

### **9. Get Math History (Client)**

Get math history (client endpoint).

**Endpoint:** `GET /client/math/history`

**Authentication:** Required

**Response:** Same as `/math/history`

---

### **10. Get Math Statistics (Client)**

Get math statistics (client endpoint).

**Endpoint:** `GET /client/math/stats`

**Authentication:** Required

**Response:** Same as `/math/stats`

---

### **11. Get Text Job Status**

Get status of text-based math job.

**Endpoint:** `GET /status/math/text?job_id={jobId}`

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
    "progress": 75.0,
    "stage": "solving_problem",
    "created_at": "2024-01-01T12:00:00Z",
    "updated_at": "2024-01-01T12:01:00Z"
  }
}
```

**Example Request:**

```bash
curl -X GET "http://your-domain.com/api/status/math/text?job_id=abc123" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### **12. Get Text Job Result**

Get result of text-based math job.

**Endpoint:** `GET /result/math/text?job_id={jobId}`

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
    "solution": {
      "answer": "4",
      "steps": [...],
      "explanation": "..."
    },
    "math_problem_id": 123,
    "math_solution_id": 456
  }
}
```

---

### **13. Get Image Job Status**

Get status of image-based math job.

**Endpoint:** `GET /status/math/image?job_id={jobId}`

**Authentication:** Required

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `job_id` | string | Yes | Job ID from async operation |

**Response:** Same format as Text Job Status

**Example Request:**

```bash
curl -X GET "http://your-domain.com/api/status/math/image?job_id=abc123" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### **14. Get Image Job Result**

Get result of image-based math job.

**Endpoint:** `GET /result/math/image?job_id={jobId}`

**Authentication:** Required

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `job_id` | string | Yes | Job ID from async operation |

**Response:** Same format as Text Job Result

**Example Request:**

```bash
curl -X GET "http://your-domain.com/api/result/math/image?job_id=abc123" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

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
    "problem_text": ["The problem text field is required when file id is not present."]
  }
}
```

---

## 💻 Examples

### **Example 1: Solve Simple Equation**

```bash
curl -X POST http://your-domain.com/api/math/solve \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "problem_text": "2x + 5 = 13",
    "subject_area": "algebra",
    "difficulty_level": "intermediate"
  }'
```

### **Example 2: Solve Word Problem**

```bash
curl -X POST http://your-domain.com/api/math/solve \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "problem_text": "what is two plus two",
    "subject_area": "arithmetic",
    "difficulty_level": "beginner"
  }'
```

### **Example 3: Solve from Image (Async)**

```bash
# Step 1: Upload image file (use /api/files/upload)
# Step 2: Solve using file_id
curl -X POST http://your-domain.com/api/math/solve \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "file_id": "abc123",
    "subject_area": "algebra",
    "difficulty_level": "intermediate"
  }'

# Response includes job_id:
# {
#   "success": true,
#   "data": {
#     "job_id": "550e8400-e29b-41d4-a716-446655440000",
#     "status": "pending"
#   }
# }

# Step 3: Check job status
curl -X GET "http://your-domain.com/api/status/math/image?job_id=550e8400-e29b-41d4-a716-446655440000" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Step 4: Get result when completed
curl -X GET "http://your-domain.com/api/result/math/image?job_id=550e8400-e29b-41d4-a716-446655440000" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### **Example 4: Get Problem History**

```bash
curl -X GET http://your-domain.com/api/math/history \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### **Example 5: Get Statistics**

```bash
curl -X GET http://your-domain.com/api/math/stats \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 🔄 Workflows

### **Text Problem Workflow (Synchronous)**

1. **Solve Math Problem** → Submit text problem
2. **Get Immediate Solution** → Receive solution with steps and explanation
3. **View in History** → Problem saved to history automatically

### **Image Problem Workflow (Asynchronous)**

1. **Upload Image** → Use `/api/files/upload` to upload image
2. **Solve Math Problem** → Submit with `file_id` (returns `job_id`)
3. **Check Job Status** → Poll `/status/math/image?job_id={id}` until completed
4. **Get Result** → Retrieve solution from `/result/math/image?job_id={id}`
5. **View in History** → Problem saved to history automatically

### **Problem Management Workflow**

1. **List Problems** → Get all problems with filters
2. **Get Problem** → View specific problem with solutions
3. **Delete Problem** → Remove problem and solutions

---

## 📝 Subject Areas

Supported subject areas:

| Subject | Description |
|---------|-------------|
| `algebra` | Algebraic equations and expressions |
| `geometry` | Shapes, areas, volumes, angles |
| `calculus` | Derivatives, integrals, limits |
| `statistics` | Mean, median, probability, distributions |
| `trigonometry` | Trigonometric functions, identities |
| `arithmetic` | Basic math operations |
| `maths` | General mathematics (default) |

---

## 📊 Difficulty Levels

| Level | Description |
|-------|-------------|
| `beginner` | Simple problems suitable for beginners |
| `intermediate` | Moderate complexity problems (default) |
| `advanced` | Complex problems requiring advanced knowledge |

---

## 🔍 Solution Format

### **Solution Structure**

```json
{
  "answer": "4",
  "steps": [
    {
      "step_number": 1,
      "description": "Subtract 5 from both sides",
      "expression": "2x = 8"
    },
    {
      "step_number": 2,
      "description": "Divide both sides by 2",
      "expression": "x = 4"
    }
  ],
  "method": "algebraic_solver",
  "explanation": "Detailed explanation of the solution process",
  "verification": "Solution verified by microservice"
}
```

---

## 📌 Notes

1. **Processing Modes:**
   - **Text problems** → Processed synchronously (immediate response)
   - **Image problems** → Processed asynchronously (returns job_id, poll for results)

2. **Word Problems:**
   - Supports natural language math problems
   - Examples: "what is two plus two", "what is the area of a rectangle with length 8 and width 5"

3. **File Uploads:**
   - Use `/api/files/upload` to upload image files first
   - Then use `file_id` in solve requests

4. **Job Polling:**
   - For image problems, poll status endpoint every 2-3 seconds
   - Maximum wait time: 2 minutes (120 seconds)
   - Job results are available for 24 hours

5. **History:**
   - All solved problems are automatically saved to history
   - Access via `/math/history` endpoint

6. **Statistics:**
   - Statistics are calculated in real-time
   - Includes success rate, subject distribution, difficulty breakdown

---

**Last Updated:** November 2025  
**Documentation Version:** 1.0  
**Status:** ✅ Production Ready

