# Presentation & Math Modules - API Endpoints

**Base URL:** `http://your-domain.com/api`  
**Last Updated:** November 2025

---

## 📊 Presentation Module Endpoints

### **Public Endpoints** (No Authentication Required)

#### 1. Get Templates
```
GET /presentations/templates
```
Get available presentation templates.

**Response:**
```json
{
  "success": true,
  "templates": [
    {
      "id": "corporate_blue",
      "name": "Corporate Blue",
      "description": "Professional business theme"
    }
  ]
}
```

---

#### 2. Generate Outline
```
POST /presentations/generate-outline
```
Generate a presentation outline from user input.

**Request Body:**
```json
{
  "input_type": "text|file|url|youtube",
  "topic": "Your presentation topic (required if input_type is text)",
  "file_id": "file-id-here (required if input_type is file)",
  "url": "https://example.com (required if input_type is url)",
  "youtube_url": "https://youtube.com/... (required if input_type is youtube)",
  "language": "English|Spanish|French|German|Italian|Portuguese|Chinese|Japanese",
  "tone": "Professional|Casual|Academic|Creative|Formal",
  "length": "Short|Medium|Long",
  "model": "Basic Model|Advanced Model|Premium Model|gpt-3.5-turbo|gpt-4"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "outline": {
      "title": "Presentation Title",
      "slides": [...]
    },
    "ai_result_id": 123
  }
}
```

---

#### 3. Generate Content
```
POST /presentations/{aiResultId}/generate-content
```
Generate detailed content for presentation slides.

**Request Body:**
```json
{
  "language": "English",
  "tone": "Professional",
  "detail_level": "brief|detailed|comprehensive"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "slides": [...]
  }
}
```

---

#### 4. Export Presentation
```
POST /presentations/{aiResultId}/export
```
Export presentation to PowerPoint format.

**Request Body:**
```json
{
  "template": "corporate_blue",
  "color_scheme": "blue",
  "font_style": "modern"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "powerpoint_file": "presentations/filename.pptx",
    "download_url": "http://...",
    "file_size": 12345
  }
}
```

---

#### 5. Get Presentation Data
```
GET /presentations/{aiResultId}/data
```
Get presentation data for editing.

**Response:**
```json
{
  "success": true,
  "data": {
    "title": "Presentation Title",
    "slides": [...]
  }
}
```

---

#### 6. Save Presentation
```
POST /presentations/{aiResultId}/save
```
Save presentation changes.

**Request Body:**
```json
{
  "title": "Updated Title",
  "slides": [...]
}
```

---

#### 7. List Presentations
```
GET /presentations
```
Get list of all presentations.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "title": "Presentation Title",
      "created_at": "2024-01-01T12:00:00Z"
    }
  ]
}
```

---

#### 8. Delete Presentation
```
DELETE /presentations/{aiResultId}
```
Delete a presentation.

---

#### 9. Download Presentation
```
GET /files/download/{filename}
```
Download a presentation file.

---

### **Authenticated Endpoints** (Requires Bearer Token)

#### 10. Update Outline
```
PUT /presentations/{aiResultId}/update-outline
```
Update presentation outline.

**Request Body:**
```json
{
  "outline": {
    "title": "Updated Title",
    "slides": [...]
  }
}
```

---

#### 11. Generate PowerPoint
```
POST /presentations/{aiResultId}/generate-powerpoint
```
Generate PowerPoint file.

---

#### 12. Get Presentation
```
GET /presentations/{aiResultId}
```
Get specific presentation details.

---

#### 13. Get Progress Status
```
GET /presentations/{aiResultId}/status
```
Get presentation generation progress.

---

#### 14. Check Microservice Status
```
GET /presentations/microservice-status
```
Check if presentation microservice is available.

---

### **Job Status & Result Endpoints** (Requires Bearer Token)

#### 15. Get Presentation Text Job Status
```
GET /status/presentations/text?job_id={jobId}
```
Get status of text-based presentation job.

**Response:**
```json
{
  "success": true,
  "job": {
    "id": "job-id",
    "status": "pending|processing|completed|failed",
    "progress": 45.0,
    "stage": "generating_content"
  }
}
```

---

#### 16. Get Presentation Text Job Result
```
GET /result/presentations/text?job_id={jobId}
```
Get result of text-based presentation job.

---

#### 17. Get Presentation File Job Status
```
GET /status/presentations/file?job_id={jobId}
```
Get status of file-based presentation job.

---

#### 18. Get Presentation File Job Result
```
GET /result/presentations/file?job_id={jobId}
```
Get result of file-based presentation job.

---

## 🧮 Math Module Endpoints

### **Authenticated Endpoints** (Requires Bearer Token)

#### 1. Solve Math Problem
```
POST /math/solve
```
Solve a mathematical problem (text or image).

**Request Body:**
```json
{
  "problem_text": "2x + 5 = 13",
  "file_id": "file-id-here (optional, if image)",
  "subject_area": "algebra|geometry|calculus|statistics|trigonometry|arithmetic|maths",
  "difficulty_level": "beginner|intermediate|advanced"
}
```

**Response:**
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
        }
      ],
      "explanation": "To solve this..."
    },
    "math_problem_id": 123,
    "math_solution_id": 456
  }
}
```

---

#### 2. List Math Problems
```
GET /math/problems
```
Get list of user's math problems.

**Query Parameters:**
- `page` (optional): Page number
- `per_page` (optional): Items per page
- `subject` (optional): Filter by subject
- `difficulty` (optional): Filter by difficulty

**Response:**
```json
{
  "data": [
    {
      "id": 123,
      "problem_text": "2x + 5 = 13",
      "subject_area": "algebra",
      "difficulty_level": "intermediate",
      "solutions": [...]
    }
  ],
  "current_page": 1,
  "total": 10
}
```

---

#### 3. Get Math Problem
```
GET /math/problems/{id}
```
Get specific math problem with solutions.

**Response:**
```json
{
  "id": 123,
  "problem_text": "2x + 5 = 13",
  "subject_area": "algebra",
  "solutions": [
    {
      "id": 456,
      "final_answer": "4",
      "step_by_step_solution": "...",
      "explanation": "..."
    }
  ]
}
```

---

#### 4. Delete Math Problem
```
DELETE /math/problems/{id}
```
Delete a math problem and its solutions.

---

#### 5. Get Math History
```
GET /math/history
```
Get user's math problem solving history.

**Response:**
```json
{
  "data": [
    {
      "id": 123,
      "problem_text": "2x + 5 = 13",
      "created_at": "2024-01-01T12:00:00Z",
      "solution": {...}
    }
  ]
}
```

---

#### 6. Get Math Statistics
```
GET /math/stats
```
Get user's math solving statistics.

**Response:**
```json
{
  "total_problems": 50,
  "total_solutions": 48,
  "success_rate": 96.0,
  "subject_stats": {
    "algebra": 20,
    "geometry": 15,
    "calculus": 10
  },
  "difficulty_stats": {
    "beginner": 10,
    "intermediate": 30,
    "advanced": 10
  }
}
```

---

### **Client API Endpoints** (Aliases, Requires Bearer Token)

#### 7. Generate Math Solution (Alias)
```
POST /client/math/generate
```
Alias for `/math/solve`.

---

#### 8. Get Math Help (Alias)
```
POST /client/math/help
```
Alias for `/math/solve`.

---

#### 9. Get Math History (Client)
```
GET /client/math/history
```
Get math history (client endpoint).

---

#### 10. Get Math Stats (Client)
```
GET /client/math/stats
```
Get math statistics (client endpoint).

---

### **Job Status & Result Endpoints** (Requires Bearer Token)

#### 11. Get Math Text Job Status
```
GET /status/math/text?job_id={jobId}
```
Get status of text-based math job.

**Response:**
```json
{
  "success": true,
  "job": {
    "id": "job-id",
    "status": "pending|processing|completed|failed",
    "progress": 75.0,
    "stage": "solving_problem"
  }
}
```

---

#### 12. Get Math Text Job Result
```
GET /result/math/text?job_id={jobId}
```
Get result of text-based math job.

**Response:**
```json
{
  "success": true,
  "data": {
    "solution": {
      "answer": "4",
      "steps": [...],
      "explanation": "..."
    }
  }
}
```

---

#### 13. Get Math Image Job Status
```
GET /status/math/image?job_id={jobId}
```
Get status of image-based math job.

---

#### 14. Get Math Image Job Result
```
GET /result/math/image?job_id={jobId}
```
Get result of image-based math job.

---

## 🔐 Authentication

### **Bearer Token Authentication**

Most endpoints require authentication via Bearer token:

```http
Authorization: Bearer {your-token}
```

### **Public Endpoints**

The following presentation endpoints are public (no authentication):
- `GET /presentations/templates`
- `POST /presentations/generate-outline`
- `POST /presentations/{aiResultId}/generate-content`
- `POST /presentations/{aiResultId}/export`
- `GET /presentations/{aiResultId}/data`
- `POST /presentations/{aiResultId}/save`
- `GET /presentations`
- `DELETE /presentations/{aiResultId}`
- `GET /files/download/{filename}`

---

## 📝 Request/Response Formats

### **Standard Success Response**
```json
{
  "success": true,
  "data": {...},
  "message": "Optional message"
}
```

### **Standard Error Response**
```json
{
  "success": false,
  "error": "Error message",
  "details": {...}
}
```

---

## 🚀 Usage Examples

### **Example 1: Solve Math Problem (Text)**
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

### **Example 2: Generate Presentation Outline**
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

### **Example 3: Check Job Status**
```bash
curl -X GET "http://your-domain.com/api/status/math/text?job_id=abc123" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 📌 Notes

1. **Math Module:**
   - Text problems are processed **synchronously** (immediate response)
   - Image problems are processed **asynchronously** (returns job_id, poll for results)

2. **Presentation Module:**
   - All operations are **asynchronous** (returns job_id, poll for results)
   - Use job status endpoints to check progress
   - Use job result endpoints to get final results

3. **File Uploads:**
   - Use `/api/files/upload` to upload files first
   - Then use `file_id` in requests

4. **CORS:**
   - Public presentation endpoints support CORS for `http://localhost:3000`
   - OPTIONS requests are handled automatically

---

**Last Updated:** November 2025  
**Status:** ✅ Production Ready

