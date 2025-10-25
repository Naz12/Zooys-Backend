# File Endpoint API Documentation

## 📋 **File Endpoint Request & Response**

### **🔗 Endpoint:**
```
POST /api/summarize/async/file
```

### **📤 Request Structure:**

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
Accept: application/json
```

**Body (FormData):**
```
file: [File] - PDF, DOC, DOCX, TXT, MP3, MP4, AVI, MOV, WAV, M4A files
options: [JSON String] - Optional processing options
```

**Example Request:**
```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]); // Your file
formData.append('options', JSON.stringify({
    mode: 'detailed',
    language: 'en',
    focus: 'summary'
}));

fetch('/api/summarize/async/file', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer 180|oxYCHaS6ppHnnZPvyMqnQ6w4sL4LhKrJXyD6fO3X20f02505',
        'Accept': 'application/json'
    },
    body: formData
});
```

### **📥 Response Structure:**

**✅ Success Response (HTTP 202):**
```json
{
  "success": true,
  "message": "Summarization job started",
  "job_id": "ddd6438d-4350-461e-9d13-546ba4ced711",
  "status": "pending",
  "poll_url": "http://localhost:8000/api/summarize/status/ddd6438d-4350-461e-9d13-546ba4ced711",
  "result_url": "http://localhost:8000/api/summarize/result/ddd6438d-4350-461e-9d13-546ba4ced711"
}
```

**❌ Error Responses:**

**Validation Error (HTTP 422):**
```json
{
  "error": "Validation failed",
  "details": {
    "file": ["The file field is required."],
    "options": ["The options field must be an array."]
  }
}
```

**Authentication Error (HTTP 401):**
```json
{
  "error": "Invalid token format"
}
```

**File Type Error (HTTP 400):**
```json
{
  "error": "Unsupported file type",
  "details": "File type 'xyz' is not supported for text extraction"
}
```

### **🔄 Job Status Polling:**

**Status Endpoint:**
```
GET /api/summarize/status/{jobId}
```

**Status Response:**
```json
{
  "job_id": "ddd6438d-4350-461e-9d13-546ba4ced711",
  "status": "pending|running|completed|failed",
  "progress": 0-100,
  "stage": "initializing|processing|completed|failed",
  "error": null,
  "logs": [
    {
      "timestamp": "2025-10-21T19:38:41.910Z",
      "message": "Job started"
    }
  ]
}
```

### **📄 Job Result:**

**Result Endpoint:**
```
GET /api/summarize/result/{jobId}
```

**Result Response (Success):**
```json
{
  "success": true,
  "data": {
    "summary": "AI-generated summary of the document...",
    "key_points": [
      "Key point 1",
      "Key point 2",
      "Key point 3"
    ],
    "confidence_score": 0.85,
    "model_used": "ollama:phi3:mini"
  },
  "file_name": "document.pdf",
  "file_size": 238820,
  "extracted_text_length": 1234
}
```

**Result Response (Failed):**
```json
{
  "success": false,
  "error": "Processing failed",
  "details": "AI Manager service error: Connection timeout"
}
```

### **📋 Supported File Types:**

- **Documents:** PDF, DOC, DOCX, TXT
- **Audio:** MP3, WAV, M4A
- **Video:** MP4, AVI, MOV
- **Images:** JPG, JPEG, PNG, GIF, BMP, WEBP

### **🔧 Frontend Implementation Example:**

```javascript
async function uploadAndSummarizeFile(file) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('options', JSON.stringify({
        mode: 'detailed',
        language: 'en',
        focus: 'summary'
    }));

    // Start job
    const response = await fetch('/api/summarize/async/file', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        },
        body: formData
    });

    const jobData = await response.json();
    
    if (jobData.success) {
        // Poll for status
        const jobId = jobData.job_id;
        const pollUrl = jobData.poll_url;
        
        // Poll every 3 seconds
        const pollInterval = setInterval(async () => {
            const statusResponse = await fetch(pollUrl, {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const status = await statusResponse.json();
            
            if (status.status === 'completed') {
                clearInterval(pollInterval);
                // Get result
                const resultResponse = await fetch(`/api/summarize/result/${jobId}`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const result = await resultResponse.json();
                console.log('Summary:', result.data.summary);
            } else if (status.status === 'failed') {
                clearInterval(pollInterval);
                console.error('Job failed:', status.error);
            }
        }, 3000);
    }
}
```

### **📊 Postman Testing:**

**Request Configuration:**
- **Method:** POST
- **URL:** `http://localhost:8000/api/summarize/async/file`
- **Headers:**
  - `Authorization: Bearer 180|oxYCHaS6ppHnnZPvyMqnQ6w4sL4LhKrJXyD6fO3X20f02505`
  - `Accept: application/json`
- **Body:** Form-data
  - `file`: [Select your file]
  - `options`: `{"mode":"detailed","language":"en","focus":"summary"}`

### **🔄 Complete Workflow:**

1. **Upload File** → Get job ID
2. **Poll Status** → Check job progress
3. **Get Result** → Retrieve summary when completed

### **📝 Notes:**

- **Job Scheduling:** All file processing is now asynchronous
- **File Storage:** Files are stored in `storage/app/uploads/files/`
- **Text Extraction:** Automatic text extraction from supported file types
- **AI Processing:** Uses AI Manager service for summarization
- **Error Handling:** Comprehensive error handling for all scenarios

---

**Last Updated:** October 21, 2025  
**Version:** 1.0  
**Status:** ✅ Production Ready




