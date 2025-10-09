# 🧮 **Math AI Tool - API Endpoints Documentation**

## 📋 **Overview**

The Math AI tool provides endpoints for solving mathematical problems using both text input and image uploads. All endpoints require authentication and return JSON responses.

---

## 🔗 **Base URL**
```
http://localhost:8000/api
```

---

## 🔐 **Authentication**
All endpoints require a Bearer token in the Authorization header:
```
Authorization: Bearer {your_token}
```

---

## 📝 **API Endpoints**

### **1. Solve Math Problem**
**Endpoint:** `POST /math/solve`

**Description:** Solve mathematical problems using text input or image upload.

**Request Body (Text Problem):**
```json
{
  "problem_text": "What is 2 + 2?",
  "subject_area": "arithmetic",
  "difficulty_level": "beginner"
}
```

**Request Body (Image Problem):**
```
Content-Type: multipart/form-data

problem_image: [image file]
subject_area: "maths"
difficulty_level: "intermediate"
```

**Response:**
```json
{
  "math_problem": {
    "id": 32,
    "problem_text": "What is 2 + 2?",
    "problem_image": null,
    "file_url": "http://localhost:8000/storage/uploads/files/uuid.jpg",
    "subject_area": "arithmetic",
    "difficulty_level": "beginner",
    "created_at": "2025-10-09T08:33:23.000000Z"
  },
  "math_solution": {
    "id": 45,
    "solution_method": "basic addition",
    "step_by_step_solution": "Step 1: Identify the numbers...",
    "final_answer": "4",
    "explanation": "This is a basic addition problem...",
    "verification": "We can verify by counting...",
    "created_at": "2025-10-09T08:33:23.000000Z"
  },
  "ai_result": {
    "id": 78,
    "title": "Mathematical Problem Solution",
    "file_url": "http://localhost:8000/storage/uploads/files/uuid.jpg",
    "created_at": "2025-10-09T08:33:23.000000Z"
  }
}
```

---

### **2. Get Math History**
**Endpoint:** `GET /math/history`

**Description:** Get user's math problem history.

**Query Parameters:**
- `per_page` (optional): Number of results per page (default: 15)
- `subject` (optional): Filter by subject area
- `difficulty` (optional): Filter by difficulty level

**Example:** `GET /math/history?per_page=10&subject=maths&difficulty=intermediate`

**Response:**
```json
[
  {
    "id": 32,
    "problem_text": "What is 2 + 2?",
    "problem_image": null,
    "subject_area": "arithmetic",
    "difficulty_level": "beginner",
    "problem_type": "text",
    "created_at": "2025-10-09T08:33:23.000000Z"
  },
  {
    "id": 31,
    "problem_text": null,
    "problem_image": "uploads/files/uuid.jpg",
    "subject_area": "maths",
    "difficulty_level": "intermediate",
    "problem_type": "image",
    "created_at": "2025-10-09T08:32:10.000000Z"
  }
]
```

---

### **3. Get Math Problems (Paginated)**
**Endpoint:** `GET /math/problems`

**Description:** Get paginated list of user's math problems.

**Query Parameters:**
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Number of results per page (default: 15)
- `subject` (optional): Filter by subject area
- `difficulty` (optional): Filter by difficulty level

**Example:** `GET /math/problems?page=1&per_page=15&subject=algebra`

**Response:**
```json
{
  "math_problems": [
    {
      "id": 32,
      "problem_text": "What is 2 + 2?",
      "problem_image": null,
      "subject_area": "arithmetic",
      "difficulty_level": "beginner",
      "problem_type": "text",
      "created_at": "2025-10-09T08:33:23.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 5
  }
}
```

---

### **4. Get Specific Math Problem**
**Endpoint:** `GET /math/problems/{id}`

**Description:** Get a specific math problem with its solutions.

**Path Parameters:**
- `id`: Math problem ID

**Example:** `GET /math/problems/32`

**Response:**
```json
{
  "math_problem": {
    "id": 32,
    "problem_text": "What is 2 + 2?",
    "problem_image": null,
    "subject_area": "arithmetic",
    "difficulty_level": "beginner",
    "problem_type": "text",
    "created_at": "2025-10-09T08:33:23.000000Z",
    "solutions": [
      {
        "id": 45,
        "solution_method": "basic addition",
        "step_by_step_solution": "Step 1: Identify the numbers...",
        "final_answer": "4",
        "explanation": "This is a basic addition problem...",
        "verification": "We can verify by counting...",
        "created_at": "2025-10-09T08:33:23.000000Z"
      }
    ]
  }
}
```

---

### **5. Delete Math Problem**
**Endpoint:** `DELETE /math/problems/{id}`

**Description:** Delete a specific math problem and its associated files.

**Path Parameters:**
- `id`: Math problem ID

**Example:** `DELETE /math/problems/32`

**Response:**
```json
{
  "message": "Math problem deleted successfully"
}
```

---

### **6. Get Math Statistics**
**Endpoint:** `GET /math/stats`

**Description:** Get user's math problem statistics.

**Response:**
```json
{
  "total_problems": 5,
  "problems_by_subject": {
    "arithmetic": 2,
    "maths": 3
  },
  "problems_by_difficulty": {
    "beginner": 2,
    "intermediate": 3
  },
  "recent_activity": [
    {
      "date": "2025-10-09",
      "count": 3
    }
  ],
  "success_rate": 80
}
```

---

## 🎯 **Subject Areas**

| **Value** | **Description** |
|-----------|-----------------|
| `algebra` | Algebraic problems |
| `geometry` | Geometric problems |
| `calculus` | Calculus problems |
| `statistics` | Statistical problems |
| `trigonometry` | Trigonometric problems |
| `arithmetic` | Basic arithmetic |
| `maths` | General mathematics (default for images) |

---

## 📊 **Difficulty Levels**

| **Value** | **Description** |
|-----------|-----------------|
| `beginner` | Basic problems |
| `intermediate` | Moderate problems (default) |
| `advanced` | Complex problems |

---

## 🖼️ **Image Upload Requirements**

### **Supported Formats:**
- JPEG (.jpg, .jpeg)
- PNG (.png)
- GIF (.gif)
- WebP (.webp)

### **File Size Limits:**
- Maximum: 10MB (10,240 KB)

### **Content-Type:**
- Use `multipart/form-data` for image uploads
- Field name: `problem_image`

---

## 🚨 **Error Responses**

### **401 Unauthorized**
```json
{
  "message": "Unauthenticated.",
  "error": "Authentication required"
}
```

### **403 Forbidden (No Subscription)**
```json
{
  "error": "No active subscription"
}
```

### **422 Validation Error**
```json
{
  "message": "The problem text field is required when problem image is not present.",
  "errors": {
    "problem_text": ["The problem text field is required when problem image is not present."],
    "problem_image": ["The problem image field is required when problem text is not present."]
  }
}
```

### **500 Server Error**
```json
{
  "error": "Unable to solve mathematical problem at this time"
}
```

---

## 📋 **Request Headers**

### **Required Headers:**
```
Authorization: Bearer {your_token}
Accept: application/json
Origin: http://localhost:3000
```

### **For Text Problems:**
```
Content-Type: application/json
```

### **For Image Problems:**
```
Content-Type: multipart/form-data
```

---

## 🔄 **Response Headers**

All responses include CORS headers:
```
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
```

---

## 📝 **Usage Examples**

### **Text Problem:**
```bash
curl -X POST http://localhost:8000/api/math/solve \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "problem_text": "What is 2 + 2?",
    "subject_area": "arithmetic",
    "difficulty_level": "beginner"
  }'
```

### **Image Problem:**
```bash
curl -X POST http://localhost:8000/api/math/solve \
  -H "Authorization: Bearer {token}" \
  -F "problem_image=@math_problem.jpg" \
  -F "subject_area=maths" \
  -F "difficulty_level=intermediate"
```

### **Get History:**
```bash
curl -X GET "http://localhost:8000/api/math/history?per_page=10" \
  -H "Authorization: Bearer {token}"
```

### **Get Statistics:**
```bash
curl -X GET http://localhost:8000/api/math/stats \
  -H "Authorization: Bearer {token}"
```

---

## 🎯 **Quick Reference**

| **Action** | **Method** | **Endpoint** | **Body Type** |
|------------|------------|--------------|---------------|
| Solve text problem | POST | `/math/solve` | JSON |
| Solve image problem | POST | `/math/solve` | FormData |
| Get history | GET | `/math/history` | - |
| Get problems | GET | `/math/problems` | - |
| Get specific problem | GET | `/math/problems/{id}` | - |
| Delete problem | DELETE | `/math/problems/{id}` | - |
| Get statistics | GET | `/math/stats` | - |

---

## ✅ **Status Codes**

| **Code** | **Description** |
|----------|-----------------|
| 200 | Success |
| 201 | Created (for new problems) |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden (no subscription) |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Server Error |

---

## 🚀 **Ready to Use**

The Math AI tool API is fully functional and ready for integration. All endpoints have been tested and are working correctly with the universal file upload system.
