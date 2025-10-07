# Enhanced PDF Summarizer API Documentation

## 🚀 **Improved Backend API for Better Frontend Integration**

This document outlines the enhanced API endpoints for the PDF summarizer that provide better data structure and UI helpers for frontend development.

---

## 📋 **API Endpoints Overview**

### **1. File Validation (NEW)**
```http
POST /api/summarize/validate
Authorization: Bearer {token}
Content-Type: multipart/form-data

FormData:
- file: [File to validate]
- content_type: "pdf" | "image" | "audio" | "video"
```

**Response:**
```json
{
  "success": true,
  "validation": {
    "is_valid": true,
    "errors": [],
    "warnings": ["Large file detected. Processing may take longer."],
    "file_info": {
      "name": "document.pdf",
      "size": 1024.5,
      "human_size": "1.0 MB",
      "type": "application/pdf",
      "extension": "pdf"
    }
  },
  "can_upload": true,
  "message": "File is valid and ready for upload"
}
```

### **2. Enhanced File Upload**
```http
POST /api/summarize/upload
Authorization: Bearer {token}
Content-Type: multipart/form-data

FormData:
- file: [File to upload]
- content_type: "pdf" | "image" | "audio" | "video"
```

**Response:**
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "data": {
    "file_upload": {
      "id": 123,
      "original_name": "document.pdf",
      "file_type": "pdf",
      "file_size": 1048576,
      "human_file_size": "1.0 MB",
      "file_url": "http://localhost:8000/storage/uploads/files/uuid.pdf",
      "created_at": "2025-01-06T10:30:00Z",
      "status": "uploaded"
    },
    "next_steps": {
      "can_summarize": true,
      "endpoint": "/api/summarize",
      "method": "POST"
    }
  }
}
```

### **3. Enhanced Summarization**
```http
POST /api/summarize
Authorization: Bearer {token}
Content-Type: application/json

{
  "content_type": "pdf",
  "source": {
    "type": "file",
    "data": "123"
  },
  "options": {
    "mode": "detailed",
    "language": "en",
    "focus": "summary"
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Content summarized successfully",
  "data": {
    "summary": "AI-generated summary content...",
    "metadata": {
      "content_type": "pdf",
      "processing_time": "4.2s",
      "tokens_used": 1500,
      "confidence": 0.95
    },
    "source_info": {
      "pages": 10,
      "word_count": 2500,
      "file_size": "2.5MB",
      "title": "Document Title"
    },
    "ai_result": {
      "id": 456,
      "title": "Document Summary",
      "file_url": "http://localhost:8000/storage/uploads/files/uuid.pdf",
      "created_at": "2025-01-06T10:30:00Z"
    }
  },
  "ui_helpers": {
    "summary_length": 1250,
    "word_count": 200,
    "estimated_read_time": "1 minutes",
    "can_download": true,
    "can_share": true
  }
}
```

---

## 🎨 **Frontend Integration Guide**

### **1. File Upload Flow**

```javascript
// Step 1: Validate file before upload
const validateFile = async (file, contentType) => {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('content_type', contentType);
  
  const response = await fetch('/api/summarize/validate', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`
    },
    body: formData
  });
  
  const result = await response.json();
  
  if (!result.success || !result.validation.is_valid) {
    // Show validation errors
    result.validation.errors.forEach(error => {
      showError(error);
    });
    return false;
  }
  
  // Show warnings if any
  result.validation.warnings.forEach(warning => {
    showWarning(warning);
  });
  
  return true;
};

// Step 2: Upload file
const uploadFile = async (file, contentType) => {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('content_type', contentType);
  
  const response = await fetch('/api/summarize/upload', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`
    },
    body: formData
  });
  
  const result = await response.json();
  
  if (result.success) {
    // Show success message
    showSuccess(result.message);
    
    // Enable summarize button
    enableSummarizeButton(result.data.file_upload.id);
    
    return result.data.file_upload;
  } else {
    showError(result.message);
    return null;
  }
};
```

### **2. Summarization Flow**

```javascript
const summarizeContent = async (fileUploadId, options = {}) => {
  const response = await fetch('/api/summarize', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      content_type: 'pdf',
      source: {
        type: 'file',
        data: fileUploadId
      },
      options: {
        mode: 'detailed',
        language: 'en',
        focus: 'summary',
        ...options
      }
    })
  });
  
  const result = await response.json();
  
  if (result.success) {
    // Display summary
    displaySummary(result.data.summary);
    
    // Show UI helpers
    showReadTime(result.ui_helpers.estimated_read_time);
    showWordCount(result.ui_helpers.word_count);
    
    // Enable download/share buttons
    if (result.ui_helpers.can_download) {
      enableDownloadButton(result.data.ai_result.file_url);
    }
    
    if (result.ui_helpers.can_share) {
      enableShareButton(result.data);
    }
    
    return result.data;
  } else {
    showError(result.message);
    return null;
  }
};
```

### **3. Error Handling**

```javascript
const handleApiError = (error, response) => {
  if (response.status === 422) {
    // Validation errors
    if (response.details) {
      Object.values(response.details).forEach(errors => {
        errors.forEach(error => showError(error));
      });
    }
  } else if (response.status === 400) {
    // Bad request
    showError(response.message || 'Invalid request');
  } else if (response.status === 500) {
    // Server error
    showError('Server error. Please try again later.');
  } else {
    // Other errors
    showError('An unexpected error occurred.');
  }
};
```

---

## 🎯 **UI Enhancement Suggestions**

### **1. File Upload Area**
- **Drag & Drop Zone**: Large, clearly marked area for file drops
- **File Validation**: Real-time validation with visual feedback
- **Progress Indicator**: Show upload progress
- **File Preview**: Display file info (name, size, type) before upload
- **Error Display**: Clear error messages with suggestions

### **2. Upload States**
```javascript
const uploadStates = {
  IDLE: 'idle',
  VALIDATING: 'validating',
  UPLOADING: 'uploading',
  PROCESSING: 'processing',
  COMPLETED: 'completed',
  ERROR: 'error'
};
```

### **3. Visual Feedback**
- **Loading States**: Spinners, progress bars
- **Success States**: Checkmarks, success messages
- **Error States**: Red borders, error icons
- **Warning States**: Yellow borders, warning icons

### **4. File Type Support**
- **PDF**: Primary focus, full support
- **Images**: JPEG, PNG, GIF, WebP
- **Audio**: MP3, WAV, OGG
- **Video**: MP4, AVI, MOV

---

## 🔧 **Backend Improvements Made**

1. **Enhanced Response Structure**: All responses now include `success`, `message`, and structured `data`
2. **File Validation Endpoint**: Pre-upload validation with detailed feedback
3. **UI Helpers**: Additional data for frontend UI enhancement
4. **Better Error Handling**: Consistent error response format
5. **File Size Formatting**: Human-readable file sizes
6. **Progress Tracking**: Upload status and next steps

---

## 📱 **Frontend Implementation Tips**

1. **Use the validation endpoint** before upload to provide immediate feedback
2. **Implement drag & drop** with visual feedback
3. **Show file information** (size, type, name) before upload
4. **Handle different file types** with appropriate icons
5. **Provide clear error messages** with actionable suggestions
6. **Use the UI helpers** to enhance the user experience
7. **Implement progress indicators** for long operations

The backend is now optimized for better frontend integration! 🚀
