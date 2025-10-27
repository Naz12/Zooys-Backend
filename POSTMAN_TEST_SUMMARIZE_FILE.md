# Postman Testing Guide - File Summarization API

## 🚀 Complete Testing Workflow for `/summarize/async/file`

### **Prerequisites**
- Laravel server running on `http://localhost:8000`
- Valid authentication token
- Test file ready for upload

---

## **Step 1: Get Authentication Token**

### 1.1 Login to get token
```http
POST http://localhost:8000/api/auth/login
Content-Type: application/json

{
    "email": "your-email@example.com",
    "password": "your-password"
}
```

**Expected Response:**
```json
{
    "success": true,
    "token": "207|vhs65UeUfk2kpYoTpJX3zIClf4zQyixWZdkbfPYXf7ae0dfe",
    "user": {
        "id": 17,
        "name": "Your Name",
        "email": "your-email@example.com"
    }
}
```

**Save the token for subsequent requests!**

---

## **Step 2: Upload File**

### 2.1 Upload a test file
```http
POST http://localhost:8000/api/files/upload
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: multipart/form-data

Form Data:
- file: [Select your test file (PDF, DOC, DOCX, TXT, etc.)]
- metadata: {} (optional)
```

**Expected Response:**
```json
{
    "success": true,
    "message": "File uploaded successfully",
    "file_upload": {
        "id": 187,
        "user_id": 17,
        "original_name": "test-document.pdf",
        "stored_name": "uuid-filename.pdf",
        "file_path": "uploads/files/uuid-filename.pdf",
        "mime_type": "application/pdf",
        "file_size": 1024000,
        "file_type": "pdf",
        "created_at": "2025-10-25T19:55:00.000000Z"
    }
}
```

**Save the `file_upload.id` for the next step!**

---

## **Step 3: Start File Summarization**

### 3.1 Submit file for summarization
```http
POST http://localhost:8000/api/summarize/async/file
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json

{
    "file_id": "187",
    "options": "{\"language\":\"en\",\"format\":\"detailed\",\"focus\":\"summary\",\"include_formatting\":true,\"max_pages\":10}"
}
```

**Expected Response:**
```json
{
    "success": true,
    "message": "Summarization job started",
    "job_id": "11018365-1366-490c-888f-ec487094791b",
    "status": "pending",
    "poll_url": "http://localhost:8000/api/status?job_id=11018365-1366-490c-888f-ec487094791b",
    "result_url": "http://localhost:8000/api/result?job_id=11018365-1366-490c-888f-ec487094791b"
}
```

**Save the `job_id` for status checking!**

---

## **Step 4: Check Job Status**

### 4.1 Poll job status
```http
GET http://localhost:8000/api/status?job_id=11018365-1366-490c-888f-ec487094791b
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Status Responses:**

**Pending:**
```json
{
    "job_id": "11018365-1366-490c-888f-ec487094791b",
    "status": "pending",
    "progress": 0,
    "stage": "initializing",
    "error": null,
    "tool_type": "summarize",
    "created_at": "2025-10-25T19:55:00.000000Z",
    "updated_at": "2025-10-25T19:55:00.000000Z"
}
```

**Processing:**
```json
{
    "job_id": "11018365-1366-490c-888f-ec487094791b",
    "status": "processing",
    "progress": 75,
    "stage": "ai_processing",
    "error": null,
    "tool_type": "summarize",
    "created_at": "2025-10-25T19:55:00.000000Z",
    "updated_at": "2025-10-25T19:55:30.000000Z"
}
```

**Completed:**
```json
{
    "job_id": "11018365-1366-490c-888f-ec487094791b",
    "status": "completed",
    "progress": 100,
    "stage": "completed",
    "error": null,
    "tool_type": "summarize",
    "created_at": "2025-10-25T19:55:00.000000Z",
    "updated_at": "2025-10-25T19:55:48.000000Z"
}
```

---

## **Step 5: Get Summarization Result**

### 5.1 Retrieve the completed result
```http
GET http://localhost:8000/api/result?job_id=11018365-1366-490c-888f-ec487094791b
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Expected Response:**
```json
{
    "success": true,
    "data": {
        "summary": "Ethiopia has a larger land mass and higher population compared to Eritrea.",
        "key_points": [
            {
                "country": "Ethiopia",
                "rank in Africa by size": "Second-largest"
            },
            {
                "land area of Ethiopia": "1.1 million square kilometers"
            },
            {
                "population rank of Ethiopia": "Tenth largest",
                "note on population size in relation to Eritrea": "Greater than that of the much smaller landlocked country, Eritrea."
            }
        ],
        "confidence_score": 0.8,
        "model_used": "ollama:phi3:mini"
    }
}
```

---

## **📋 Postman Collection Setup**

### **Environment Variables**
Create these variables in Postman:

| Variable | Value | Description |
|----------|-------|-------------|
| `base_url` | `http://localhost:8000/api` | Base API URL |
| `token` | `YOUR_TOKEN_HERE` | Authentication token |
| `file_id` | `187` | Uploaded file ID |
| `job_id` | `11018365-1366-490c-888f-ec487094791b` | Job ID for status/result |

### **Request Headers Template**
```http
Authorization: Bearer {{token}}
Accept: application/json
Content-Type: application/json
```

---

## **🔧 Advanced Testing Options**

### **Different File Types**
Test with various file formats:
- **PDF**: `.pdf` files
- **Word**: `.doc`, `.docx` files  
- **Text**: `.txt`, `.html`, `.htm` files
- **Images**: `.jpg`, `.jpeg`, `.png` files
- **Presentations**: `.ppt`, `.pptx` files
- **Spreadsheets**: `.xls`, `.xlsx` files

### **Custom Options**
```json
{
    "file_id": "187",
    "options": "{\"language\":\"en\",\"format\":\"detailed\",\"focus\":\"summary\",\"include_formatting\":true,\"max_pages\":5}"
}
```

**Option Parameters:**
- `language`: `"en"`, `"es"`, `"fr"`, `"de"`, etc.
- `format`: `"detailed"`, `"brief"`, `"bullet_points"`
- `focus`: `"summary"`, `"key_points"`, `"analysis"`
- `include_formatting`: `true` or `false`
- `max_pages`: `1` to `1000`

---

## **🚨 Common Issues & Solutions**

### **Issue 1: 401 Unauthorized**
**Solution:** Check your token is valid and properly formatted
```http
Authorization: Bearer YOUR_TOKEN_HERE
```

### **Issue 2: 404 Not Found on Status/Result**
**Solution:** Use query parameters, not path parameters
```http
❌ GET /api/status/11018365-1366-490c-888f-ec487094791b
✅ GET /api/status?job_id=11018365-1366-490c-888f-ec487094791b
```

### **Issue 3: File Not Found**
**Solution:** Ensure file_id exists and belongs to your user
```http
GET /api/files/{{file_id}}
Authorization: Bearer {{token}}
```

### **Issue 4: Job Stuck in Processing**
**Solution:** Check queue worker is running
```bash
php artisan queue:work
```

---

## **📊 Testing Checklist**

- [ ] **Authentication**: Token obtained and working
- [ ] **File Upload**: File uploaded successfully, got file_id
- [ ] **Job Creation**: Summarization job started, got job_id
- [ ] **Status Polling**: Can check job status (pending → processing → completed)
- [ ] **Result Retrieval**: Can get summary result with proper structure
- [ ] **Error Handling**: Test with invalid file_id, job_id, token
- [ ] **Different File Types**: Test PDF, DOC, TXT, etc.
- [ ] **Custom Options**: Test different language/format options

---

## **🎯 Expected Results**

### **Successful Flow:**
1. ✅ Upload file → Get `file_id`
2. ✅ Start summarization → Get `job_id` 
3. ✅ Poll status → See progress updates
4. ✅ Get result → Receive summary with key points

### **Result Structure:**
```json
{
    "success": true,
    "data": {
        "summary": "Your document summary here...",
        "key_points": ["Point 1", "Point 2", "Point 3"],
        "confidence_score": 0.8,
        "model_used": "ollama:phi3:mini"
    }
}
```

---

## **🔄 Complete Postman Collection**

Save this as a Postman collection:

```json
{
    "info": {
        "name": "File Summarization API",
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
    },
    "item": [
        {
            "name": "1. Upload File",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Authorization",
                        "value": "Bearer {{token}}"
                    }
                ],
                "body": {
                    "mode": "formdata",
                    "formdata": [
                        {
                            "key": "file",
                            "type": "file",
                            "src": []
                        }
                    ]
                },
                "url": {
                    "raw": "{{base_url}}/files/upload",
                    "host": ["{{base_url}}"],
                    "path": ["files", "upload"]
                }
            }
        },
        {
            "name": "2. Start Summarization",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Authorization",
                        "value": "Bearer {{token}}"
                    },
                    {
                        "key": "Content-Type",
                        "value": "application/json"
                    }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\n    \"file_id\": \"{{file_id}}\",\n    \"options\": \"{\\\"language\\\":\\\"en\\\",\\\"format\\\":\\\"detailed\\\",\\\"focus\\\":\\\"summary\\\"}\"\n}"
                },
                "url": {
                    "raw": "{{base_url}}/summarize/async/file",
                    "host": ["{{base_url}}"],
                    "path": ["summarize", "async", "file"]
                }
            }
        },
        {
            "name": "3. Check Status",
            "request": {
                "method": "GET",
                "header": [
                    {
                        "key": "Authorization",
                        "value": "Bearer {{token}}"
                    }
                ],
                "url": {
                    "raw": "{{base_url}}/status?job_id={{job_id}}",
                    "host": ["{{base_url}}"],
                    "path": ["status"],
                    "query": [
                        {
                            "key": "job_id",
                            "value": "{{job_id}}"
                        }
                    ]
                }
            }
        },
        {
            "name": "4. Get Result",
            "request": {
                "method": "GET",
                "header": [
                    {
                        "key": "Authorization",
                        "value": "Bearer {{token}}"
                    }
                ],
                "url": {
                    "raw": "{{base_url}}/result?job_id={{job_id}}",
                    "host": ["{{base_url}}"],
                    "path": ["result"],
                    "query": [
                        {
                            "key": "job_id",
                            "value": "{{job_id}}"
                        }
                    ]
                }
            }
        }
    ]
}
```

This guide provides everything you need to test the file summarization API with Postman! 🚀
