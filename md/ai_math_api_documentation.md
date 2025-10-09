# AI Math API Documentation

## Overview
The AI Math API provides endpoints for generating, solving, and managing math problems using artificial intelligence. This API is designed for educational applications and supports various math topics and difficulty levels.

## Base URL
```
http://localhost:8000/api/client/math
```

## Authentication
All endpoints require authentication using Laravel Sanctum. Include the authentication token in the request headers:
```
Authorization: Bearer {your-token}
```

## Endpoints

### 1. Generate Math Problem
**POST** `/api/client/math/generate`

Creates a new math problem based on specified topic and difficulty level.

**Request Body:**
```json
{
  "topic": "algebra",
  "difficulty": "medium"
}
```

**Parameters:**
- `topic` (string, required): Math topic (e.g., "algebra", "geometry", "calculus", "trigonometry")
- `difficulty` (string, required): Difficulty level ("easy", "medium", "hard")

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "problem": "Solve for x: 2x + 5 = 13",
    "topic": "algebra",
    "difficulty": "medium",
    "created_at": "2025-01-07T20:06:22Z"
  }
}
```

### 2. Solve Math Problem
**POST** `/api/client/math/solve`

Validates user's solution against the correct answer and provides feedback.

**Request Body:**
```json
{
  "problem_id": 123,
  "user_solution": "x = 4"
}
```

**Parameters:**
- `problem_id` (integer, required): ID of the math problem
- `user_solution` (string, required): User's solution attempt

**Response:**
```json
{
  "success": true,
  "data": {
    "correct": true,
    "user_solution": "x = 4",
    "correct_solution": "x = 4",
    "explanation": "Correct! You solved the equation by subtracting 5 from both sides and then dividing by 2.",
    "points_earned": 10
  }
}
```

### 3. Get Math History
**GET** `/api/client/math/history`

Retrieves all previously generated and solved math problems for the authenticated user.

**Query Parameters:**
- `page` (optional): Page number for pagination
- `per_page` (optional): Number of items per page (default: 10)
- `topic` (optional): Filter by topic
- `difficulty` (optional): Filter by difficulty

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "problem": "Solve for x: 2x + 5 = 13",
      "topic": "algebra",
      "difficulty": "medium",
      "solved": true,
      "correct": true,
      "created_at": "2025-01-07T20:06:22Z",
      "solved_at": "2025-01-07T20:08:15Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 5,
    "total_items": 50
  }
}
```

### 4. Get Math Topics
**GET** `/api/client/math/topics`

Returns list of available math topics for problem generation.

**Response:**
```json
{
  "success": true,
  "data": [
    "algebra",
    "geometry",
    "calculus",
    "trigonometry",
    "statistics",
    "probability",
    "linear_algebra",
    "differential_equations"
  ]
}
```

### 5. Get Math Difficulty Levels
**GET** `/api/client/math/difficulties`

Returns available difficulty levels for math problems.

**Response:**
```json
{
  "success": true,
  "data": [
    "easy",
    "medium",
    "hard"
  ]
}
```

### 6. Get Math Problem by ID
**GET** `/api/client/math/problem/{id}`

Retrieves a specific math problem by its unique identifier.

**Parameters:**
- `id` (integer, required): Problem ID

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "problem": "Solve for x: 2x + 5 = 13",
    "topic": "algebra",
    "difficulty": "medium",
    "solution": "x = 4",
    "explanation": "Subtract 5 from both sides: 2x = 8, then divide by 2: x = 4",
    "created_at": "2025-01-07T20:06:22Z"
  }
}
```

### 7. Delete Math Problem
**DELETE** `/api/client/math/problem/{id}`

Removes a math problem from the user's history.

**Parameters:**
- `id` (integer, required): Problem ID

**Response:**
```json
{
  "success": true,
  "message": "Problem deleted successfully"
}
```

### 8. Update Math Problem
**PUT** `/api/client/math/problem/{id}`

Modifies an existing math problem's topic or difficulty level.

**Request Body:**
```json
{
  "topic": "geometry",
  "difficulty": "hard"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "problem": "Find the area of a circle with radius 5",
    "topic": "geometry",
    "difficulty": "hard",
    "updated_at": "2025-01-07T20:10:30Z"
  }
}
```

### 9. Get Math Statistics
**GET** `/api/client/math/stats`

Provides user's math learning progress and performance metrics.

**Response:**
```json
{
  "success": true,
  "data": {
    "total_problems": 50,
    "solved_problems": 30,
    "correct_answers": 25,
    "accuracy": 0.833,
    "points_earned": 250,
    "streak_days": 7,
    "topics_stats": {
      "algebra": {
        "total": 20,
        "solved": 15,
        "accuracy": 0.8
      },
      "geometry": {
        "total": 15,
        "solved": 10,
        "accuracy": 0.7
      }
    },
    "difficulty_stats": {
      "easy": {
        "total": 20,
        "solved": 18,
        "accuracy": 0.9
      },
      "medium": {
        "total": 20,
        "solved": 10,
        "accuracy": 0.8
      },
      "hard": {
        "total": 10,
        "solved": 2,
        "accuracy": 0.5
      }
    }
  }
}
```

### 10. Get Math Help
**POST** `/api/client/math/help`

Provides hints and step-by-step guidance for solving a specific math problem.

**Request Body:**
```json
{
  "problem_id": 123,
  "question": "How do I solve this equation?"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "help": "To solve this equation, follow these steps: 1) Isolate the variable term, 2) Perform the same operation on both sides, 3) Simplify",
    "hint": "Try subtracting 5 from both sides first",
    "next_step": "Now divide both sides by 2"
  }
}
```

## Error Responses

### 400 Bad Request
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "topic": ["The topic field is required."],
    "difficulty": ["The difficulty must be one of: easy, medium, hard."]
  }
}
```

### 401 Unauthorized
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Problem not found"
}
```

### 422 Unprocessable Entity
```json
{
  "success": false,
  "message": "The given data was invalid",
  "errors": {
    "user_solution": ["The solution format is invalid."]
  }
}
```

### 500 Internal Server Error
```json
{
  "success": false,
  "message": "Internal server error"
}
```

## Rate Limiting
- Generate Problem: 10 requests per minute
- Solve Problem: 20 requests per minute
- Other endpoints: 60 requests per minute

## Response Headers
All responses include standard HTTP headers plus:
- `X-RateLimit-Limit`: Maximum requests allowed
- `X-RateLimit-Remaining`: Remaining requests in current window
- `X-RateLimit-Reset`: Time when rate limit resets

## Example Usage

### JavaScript/TypeScript
```javascript
// Generate a math problem
const response = await fetch('/api/client/math/generate', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    topic: 'algebra',
    difficulty: 'medium'
  })
});

const data = await response.json();
console.log(data.data.problem);
```

### cURL
```bash
# Generate a problem
curl -X POST http://localhost:8000/api/client/math/generate \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer your-token" \
  -d '{"topic": "algebra", "difficulty": "medium"}'

# Solve a problem
curl -X POST http://localhost:8000/api/client/math/solve \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer your-token" \
  -d '{"problem_id": 123, "user_solution": "x = 4"}'
```

## Notes
- All timestamps are in ISO 8601 format (UTC)
- Problem IDs are unique across all users
- Solutions are case-sensitive
- The API supports LaTeX formatting for mathematical expressions
- Rate limits are per authenticated user
- Problems are automatically archived after 30 days of inactivity
