# Math Solver Microservice API Reference

## Base URL
```
http://localhost:8002
```

## Authentication
Currently no authentication is required. In production, consider implementing API keys or JWT tokens.

## Response Format

All responses follow a consistent JSON structure:

### Success Response
```json
{
  "success": true,
  "timestamp": "2024-01-15T10:30:00Z",
  "request_id": "uuid-string",
  "data": { ... }
}
```

### Error Response
```json
{
  "success": false,
  "timestamp": "2024-01-15T10:30:00Z",
  "request_id": "uuid-string",
  "error": {
    "message": "Error description",
    "type": "error_type"
  }
}
```

## Endpoints

### 1. Health Check

**GET** `/health`

Check the health status of the microservice and its dependencies.

#### Response
```json
{
  "success": true,
  "timestamp": "2024-01-15T10:30:00Z",
  "request_id": "health_check",
  "status": "healthy",
  "services": {
    "solvers": {
      "algebra": true,
      "calculus": true,
      "geometry": true,
      "statistics": true,
      "arithmetic": true
    },
    "external_services": {
      "openai": true,
      "tesseract": true,
      "problem_parser": true,
      "image_processor": true,
      "solution_formatter": true
    }
  },
  "version": "1.0.0"
}
```

#### Example
```bash
curl http://localhost:8002/health
```

---

### 2. Get Available Solvers

**GET** `/solvers`

Get information about available mathematical solvers and their capabilities.

#### Response
```json
{
  "success": true,
  "timestamp": "2024-01-15T10:30:00Z",
  "request_id": "solvers_info",
  "available_solvers": [
    "algebra",
    "calculus", 
    "geometry",
    "statistics",
    "arithmetic"
  ],
  "capabilities": {
    "algebra": [
      "linear_equations",
      "quadratic_equations",
      "polynomial_equations",
      "expression_simplification"
    ],
    "calculus": [
      "derivatives",
      "integrals",
      "limits",
      "differential_equations"
    ]
  },
  "solver_details": {
    "algebra": {
      "name": "AlgebraSolver",
      "timeout": 5,
      "capabilities": [...]
    }
  }
}
```

#### Example
```bash
curl http://localhost:8002/solvers
```

---

### 3. Solve Mathematical Problem

**POST** `/solve`

Solve a mathematical problem and return the solution with step-by-step breakdown.

#### Request Body
```json
{
  "problem_text": "2x + 5 = 13",
  "subject_area": "algebra",
  "difficulty_level": "intermediate",
  "timeout_ms": 30000,
  "options": {}
}
```

#### Parameters
- `problem_text` (string, optional): Mathematical problem as text
- `problem_image` (string, optional): Base64 encoded image or image data
- `image_type` (string, optional): "printed", "handwritten", or "auto"
- `subject_area` (string, optional): "algebra", "calculus", "geometry", "statistics", "arithmetic"
- `difficulty_level` (string, optional): "beginner", "intermediate", "advanced"
- `timeout_ms` (integer, optional): Timeout in milliseconds (1000-30000)
- `options` (object, optional): Additional solver-specific options

#### Response
```json
{
  "success": true,
  "timestamp": "2024-01-15T10:30:00Z",
  "request_id": "uuid-string",
  "classification": {
    "subject": "algebra",
    "confidence": 0.95,
    "method": "multi_layer_classification",
    "fallback_subjects": []
  },
  "solution": {
    "answer": "x = 4",
    "method": "algebraic_solving",
    "confidence": 0.95,
    "steps": [
      {
        "step_number": 1,
        "operation": "subtract",
        "description": "Subtract 5 from both sides",
        "expression": "2x = 8",
        "latex": "2x = 8",
        "confidence": 1.0
      },
      {
        "step_number": 2,
        "operation": "divide",
        "description": "Divide by 2",
        "expression": "x = 4",
        "latex": "x = 4",
        "confidence": 1.0
      }
    ],
    "verification": "Solution verified by substitution",
    "metadata": {
      "equation_type": "linear",
      "variable_count": 1,
      "degree": 1
    }
  },
  "metadata": {
    "processing_time": 1.2,
    "solver_used": "algebra",
    "timestamp": "2024-01-15T10:30:00Z"
  }
}
```

#### Example
```bash
curl -X POST http://localhost:8002/solve \
  -H "Content-Type: application/json" \
  -d '{
    "problem_text": "2x + 5 = 13",
    "subject_area": "algebra",
    "difficulty_level": "intermediate"
  }'
```

---

### 4. Solve with AI Explanation

**POST** `/explain`

Solve a mathematical problem and provide an AI-generated educational explanation.

#### Request Body
```json
{
  "problem_text": "2x + 5 = 13",
  "subject_area": "algebra",
  "difficulty_level": "intermediate",
  "include_explanation": true,
  "explanation_style": "educational",
  "timeout_ms": 60000
}
```

#### Additional Parameters
- `include_explanation` (boolean, optional): Whether to include AI explanation (default: true)
- `explanation_style` (string, optional): Style of explanation (default: "educational")

#### Response
```json
{
  "success": true,
  "timestamp": "2024-01-15T10:30:00Z",
  "request_id": "uuid-string",
  "classification": {
    "subject": "algebra",
    "confidence": 0.95,
    "method": "multi_layer_classification"
  },
  "solution": {
    "answer": "x = 4",
    "method": "algebraic_solving",
    "confidence": 0.95,
    "steps": [...],
    "verification": "Solution verified by substitution"
  },
  "explanation": {
    "content": "To solve the equation 2x + 5 = 13, we need to isolate the variable x...",
    "method": "openai_generated",
    "success": true,
    "tokens_used": 150,
    "error": null
  },
  "metadata": {
    "processing_time": 8.5,
    "solver_used": "algebra",
    "image_processed": false,
    "explanation_requested": true
  }
}
```

#### Example
```bash
curl -X POST http://localhost:8002/explain \
  -H "Content-Type: application/json" \
  -d '{
    "problem_text": "2x + 5 = 13",
    "subject_area": "algebra",
    "difficulty_level": "intermediate",
    "include_explanation": true
  }'
```

---

### 5. Convert to LaTeX

**POST** `/latex`

Convert mathematical expressions to LaTeX format.

#### Request Body
```json
{
  "problem_text": "x^2 + 2x + 1 = 0",
  "solution": "x = -1",
  "render_solution": true
}
```

#### Parameters
- `problem_text` (string, required): Mathematical problem as text
- `solution` (string, optional): Solution to convert to LaTeX
- `render_solution` (boolean, optional): Whether to render solution in LaTeX (default: true)

#### Response
```json
{
  "success": true,
  "timestamp": "2024-01-15T10:30:00Z",
  "request_id": "uuid-string",
  "latex": {
    "input": "x^{2} + 2x + 1 = 0",
    "solution": "x = -1"
  },
  "metadata": {
    "processing_time": 0.1,
    "input_length": 18,
    "timestamp": "2024-01-15T10:30:00Z"
  }
}
```

#### Example
```bash
curl -X POST http://localhost:8002/latex \
  -H "Content-Type: application/json" \
  -d '{
    "problem_text": "x^2 + 2x + 1 = 0",
    "solution": "x = -1"
  }'
```

---

### 6. Upload and Process Image

**POST** `/upload-image`

Upload a mathematical image and extract the mathematical content.

#### Request
- **Content-Type**: `multipart/form-data`
- **Body**: Image file

#### Response
```json
{
  "success": true,
  "text": "2x + 5 = 13",
  "confidence": 0.95,
  "method": "tesseract_ocr",
  "metadata": {
    "image_type": "printed",
    "character_count": 12,
    "word_count": 3
  }
}
```

#### Example
```bash
curl -X POST http://localhost:8002/upload-image \
  -F "file=@math_problem.jpg"
```

---

### 7. Validate Problem

**POST** `/validate-problem`

Validate if a mathematical problem can be processed.

#### Request Body
- **Content-Type**: `application/x-www-form-urlencoded`
- **Body**: `problem_text=2x + 5 = 13`

#### Response
```json
{
  "valid": true,
  "error": null,
  "message": "Problem is valid for processing"
}
```

#### Example
```bash
curl -X POST http://localhost:8002/validate-problem \
  -d "problem_text=2x + 5 = 13"
```

---

### 8. Validate Image

**POST** `/validate-image`

Validate if an image can be processed.

#### Request
- **Content-Type**: `multipart/form-data`
- **Body**: Image file

#### Response
```json
{
  "valid": true,
  "error": null,
  "file_size": 1024000,
  "format": "jpeg"
}
```

#### Example
```bash
curl -X POST http://localhost:8002/validate-image \
  -F "file=@math_problem.jpg"
```

---

### 9. Get Service Information

**GET** `/info`

Get detailed information about the microservice.

#### Response
```json
{
  "service": "Math Solver Microservice",
  "version": "1.0.0",
  "description": "A comprehensive mathematical problem solver with AI explanations",
  "endpoints": {
    "solve": "POST /solve - Solve mathematical problems",
    "explain": "POST /explain - Solve with AI explanation",
    "latex": "POST /latex - Convert to LaTeX",
    "health": "GET /health - Health check",
    "solvers": "GET /solvers - Available solvers"
  },
  "capabilities": {
    "subjects": ["algebra", "calculus", "geometry", "statistics", "arithmetic"],
    "image_processing": true,
    "ai_explanations": true,
    "latex_support": true,
    "step_by_step_solutions": true
  },
  "openai": {
    "available": true,
    "model": "gpt-3.5-turbo",
    "vision_model": "gpt-4o",
    "max_tokens": 1500
  },
  "image_processor": {
    "tesseract_available": true,
    "openai_vision_available": true,
    "supported_formats": [".jpg", ".jpeg", ".png", ".bmp", ".tiff", ".webp"]
  }
}
```

#### Example
```bash
curl http://localhost:8002/info
```

---

## Error Codes

### HTTP Status Codes
- `200` - Success
- `400` - Bad Request (invalid input)
- `422` - Unprocessable Entity (validation error)
- `500` - Internal Server Error
- `503` - Service Unavailable (microservice down)

### Error Types
- `validation_error` - Input validation failed
- `processing_error` - Problem solving failed
- `openai_error` - OpenAI API error
- `image_processing_error` - Image processing failed
- `timeout_error` - Request timeout
- `internal_error` - Internal server error

## Rate Limiting

Currently no rate limiting is implemented. In production, consider implementing:
- Request rate limiting per IP
- Token-based rate limiting
- Usage quotas

## Examples

### Basic Algebra Problem
```bash
curl -X POST http://localhost:8002/solve \
  -H "Content-Type: application/json" \
  -d '{
    "problem_text": "3x - 7 = 14",
    "subject_area": "algebra"
  }'
```

### Geometry Problem
```bash
curl -X POST http://localhost:8002/solve \
  -H "Content-Type: application/json" \
  -d '{
    "problem_text": "Find the area of a circle with radius 5",
    "subject_area": "geometry"
  }'
```

### Statistics Problem
```bash
curl -X POST http://localhost:8002/solve \
  -H "Content-Type: application/json" \
  -d '{
    "problem_text": "Find the mean of: 1, 2, 3, 4, 5",
    "subject_area": "statistics"
  }'
```

### Calculus Problem
```bash
curl -X POST http://localhost:8002/solve \
  -H "Content-Type: application/json" \
  -d '{
    "problem_text": "Find the derivative of x^2 + 3x + 2",
    "subject_area": "calculus"
  }'
```

### Image Problem
```bash
curl -X POST http://localhost:8002/explain \
  -H "Content-Type: application/json" \
  -d '{
    "problem_image": "base64_encoded_image_data",
    "image_type": "handwritten",
    "include_explanation": true
  }'
```

## Interactive API Documentation

Visit `http://localhost:8002/docs` in your browser to access the interactive Swagger UI documentation where you can test all endpoints directly.







