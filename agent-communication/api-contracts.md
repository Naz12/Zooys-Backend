# API Contracts

*Either agent writes here when asked by user*

**Last Updated:** January 9, 2025 - 9:25 PM

## 📋 Current API Definitions

### Presentation API Endpoints

#### Base URL: `http://localhost:8000/api/presentations`

#### 1. Generate Outline
- **Endpoint:** `POST /presentations/generate-outline`
- **Authentication:** Required (auth:sanctum)
- **Request Body:**
  ```json
  {
    "input_type": "text|url|file",
    "topic": "string",
    "language": "string",
    "tone": "string",
    "length": "string",
    "model": "string"
  }
  ```
- **Response:**
  ```json
  {
    "success": true,
    "data": {
      "ai_result_id": 123,
      "title": "string",
      "outline": [...],
      "metadata": {...}
    }
  }
  ```

#### 2. Get Templates
- **Endpoint:** `GET /presentations/templates`
- **Authentication:** Public (for testing)
- **Response:**
  ```json
  {
    "success": true,
    "data": {
      "templates": {
        "corporate_blue": {
          "name": "Corporate Blue",
          "description": "Professional blue theme",
          "color_scheme": "blue",
          "category": "business"
        }
      }
    }
  }
  ```

#### 3. Generate Content
- **Endpoint:** `POST /presentations/{aiResultId}/generate-content`
- **Authentication:** Required (auth:sanctum)
- **Request Body:** `{}`
- **Response:**
  ```json
  {
    "success": true,
    "data": {
      "slides": [...],
      "content_generated": true
    }
  }
  ```

#### 4. Export Presentation
- **Endpoint:** `POST /presentations/{aiResultId}/export`
- **Authentication:** Required (auth:sanctum)
- **Request Body:**
  ```json
  {
    "presentation_data": {
      "title": "string",
      "slides": [...]
    },
    "template": "string",
    "color_scheme": "string",
    "font_style": "string"
  }
  ```
- **Response:**
  ```json
  {
    "success": true,
    "data": {
      "file_path": "string",
      "download_url": "string"
    },
    "message": "Presentation exported successfully"
  }
  ```

### FastAPI Microservice Endpoints

#### Base URL: `http://localhost:8001`

#### 1. Health Check
- **Endpoint:** `GET /health`
- **Authentication:** None
- **Response:**
  ```json
  {
    "status": "healthy",
    "message": "FastAPI Presentation microservice is running",
    "timestamp": "2025-10-09 21:20:33"
  }
  ```

#### 2. Export Presentation
- **Endpoint:** `POST /export`
- **Authentication:** None
- **Request Body:**
  ```json
  {
    "presentation_data": {
      "title": "string",
      "slides": [...]
    },
    "user_id": 123,
    "ai_result_id": 456,
    "template": "corporate_blue",
    "color_scheme": "blue",
    "font_style": "modern"
  }
  ```
- **Response:**
  ```json
  {
    "success": true,
    "data": {
      "file_path": "string",
      "file_size": 12345,
      "download_url": "/download/123/456"
    },
    "message": "Presentation exported successfully via FastAPI"
  }
  ```

## 🔄 API Changes Log

### January 9, 2025 - 9:20 PM
- **Change:** Switched PowerPoint export from direct Python script to FastAPI microservice
- **Impact:** Laravel now calls FastAPI microservice instead of executing Python script directly
- **Endpoints Affected:** `POST /presentations/{aiResultId}/export`
- **Status:** ✅ Implemented

### January 9, 2025 - 9:15 PM
- **Change:** Moved templates endpoint to public access for testing
- **Impact:** Templates can now be accessed without authentication
- **Endpoints Affected:** `GET /presentations/templates`
- **Status:** ✅ Implemented

## 📊 API Statistics

- **Total Endpoints:** 8
- **Public Endpoints:** 2
- **Authenticated Endpoints:** 6
- **Microservice Endpoints:** 2
- **Last Updated:** January 9, 2025


