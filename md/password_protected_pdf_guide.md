# 🔐 Password-Protected PDF Processing Guide

## 🎯 **Overview**
This guide explains how to process password-protected PDFs using the enhanced PDF processing system.

---

## 🚀 **How to Use Password-Protected PDFs**

### **1. Upload PDF with Password**
```json
POST /api/summarize/upload
Content-Type: multipart/form-data

{
  "file": [PDF_FILE],
  "content_type": "pdf"
}
```

### **2. Summarize with Password**
```json
POST /api/summarize
Content-Type: application/json

{
  "content_type": "pdf",
  "source": {
    "type": "file",
    "data": "UPLOAD_ID"
  },
  "options": {
    "mode": "detailed",
    "language": "en",
    "password": "YOUR_PDF_PASSWORD"
  }
}
```

---

## 📋 **API Examples**

### **Frontend Integration:**
```javascript
// Upload PDF
const formData = new FormData();
formData.append('file', pdfFile);
formData.append('content_type', 'pdf');

const uploadResponse = await fetch('/api/summarize/upload', {
  method: 'POST',
  headers: { 'Authorization': 'Bearer ' + token },
  body: formData
});

const uploadData = await uploadResponse.json();

// Summarize with password
const summarizeResponse = await fetch('/api/summarize', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ' + token
  },
  body: JSON.stringify({
    content_type: 'pdf',
    source: {
      type: 'file',
      data: uploadData.upload_id
    },
    options: {
      mode: 'detailed',
      language: 'en',
      password: 'user_provided_password'
    }
  })
});

const result = await summarizeResponse.json();
```

---

## 🔧 **Technical Implementation**

### **Enhanced PDF Processing Service:**
```php
class EnhancedPDFProcessingService
{
    public function extractTextFromPDF($filePath, $password = null)
    {
        // Try smalot/pdfparser first (faster for unprotected PDFs)
        $result = $this->extractWithSmalot($filePath);
        if ($result['success']) {
            return $result;
        }
        
        // If password provided, try FPDI
        if ($password) {
            return $this->extractWithFPDI($filePath, $password);
        }
        
        return $result;
    }
}
```

### **Password Detection:**
```php
public function isPasswordProtected($filePath)
{
    try {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($filePath);
        return false; // Not password protected
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), 'Secured pdf') !== false) {
            return true; // Password protected
        }
        return false;
    }
}
```

---

## 📊 **Response Examples**

### **Password-Protected PDF (No Password Provided):**
```json
{
  "error": "This PDF is password-protected. Please provide the password in the options.",
  "metadata": {
    "content_type": "pdf",
    "processing_time": "0.5s",
    "tokens_used": 0,
    "confidence": 0.0
  },
  "source_info": {
    "pages": 0,
    "word_count": 0,
    "file_size": "2.3MB",
    "password_protected": true
  }
}
```

### **Password-Protected PDF (With Correct Password):**
```json
{
  "summary": "Summary: This document discusses...",
  "metadata": {
    "content_type": "pdf",
    "processing_time": "4.2s",
    "tokens_used": 1200,
    "confidence": 0.95
  },
  "source_info": {
    "pages": 5,
    "word_count": 2500,
    "character_count": 15000,
    "file_size": "2.3MB",
    "title": "Document Title",
    "author": "Document Author",
    "password_protected": true
  }
}
```

### **Password-Protected PDF (Wrong Password):**
```json
{
  "error": "Unable to process PDF: Invalid password provided.",
  "metadata": {
    "content_type": "pdf",
    "processing_time": "0.5s",
    "tokens_used": 0,
    "confidence": 0.0
  },
  "source_info": {
    "pages": 0,
    "word_count": 0,
    "file_size": "2.3MB"
  }
}
```

---

## 🛠️ **Error Handling**

### **Common Error Messages:**
- **"This PDF is password-protected. Please provide the password in the options."**
  - Solution: Add `"password": "your_password"` to options

- **"Invalid password provided."**
  - Solution: Check the password is correct

- **"This PDF is encrypted and cannot be processed."**
  - Solution: Use a different PDF or contact the document owner

- **"This PDF appears to be scanned (image-based)."**
  - Solution: Use OCR processing instead

### **Frontend Error Handling:**
```javascript
try {
  const response = await fetch('/api/summarize', { ... });
  const data = await response.json();
  
  if (data.error) {
    if (data.error.includes('password-protected')) {
      // Show password input dialog
      const password = prompt('This PDF is password-protected. Enter password:');
      if (password) {
        // Retry with password
        return await summarizeWithPassword(uploadId, password);
      }
    } else if (data.error.includes('Invalid password')) {
      alert('Incorrect password. Please try again.');
    } else {
      alert('Error: ' + data.error);
    }
  } else {
    // Success - show summary
    displaySummary(data.summary);
  }
} catch (error) {
  console.error('Network error:', error);
}
```

---

## 🔒 **Security Considerations**

### **Password Handling:**
- Passwords are not stored in the database
- Passwords are only used during processing
- Passwords are not logged in plain text
- Passwords are transmitted securely over HTTPS

### **Best Practices:**
- Always use HTTPS for password transmission
- Don't store passwords in frontend code
- Clear password fields after use
- Use secure password input fields

---

## 🎯 **Supported PDF Types**

### **✅ Supported:**
- Unprotected PDFs
- Password-protected PDFs (with correct password)
- PDFs with user permissions
- PDFs with metadata

### **❌ Not Supported:**
- PDFs with strong encryption (AES-256)
- Scanned PDFs (image-based)
- Corrupted PDF files
- PDFs with digital signatures

---

## 🚀 **Quick Start**

### **1. Test with Unprotected PDF:**
```bash
curl -X POST http://localhost:8000/api/summarize \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "content_type": "pdf",
    "source": {"type": "file", "data": "UPLOAD_ID"},
    "options": {"mode": "detailed", "language": "en"}
  }'
```

### **2. Test with Password-Protected PDF:**
```bash
curl -X POST http://localhost:8000/api/summarize \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "content_type": "pdf",
    "source": {"type": "file", "data": "UPLOAD_ID"},
    "options": {
      "mode": "detailed",
      "language": "en",
      "password": "your_password"
    }
  }'
```

---

## 📈 **Performance Notes**

- **Unprotected PDFs**: Fast processing with smalot/pdfparser
- **Password-Protected PDFs**: Slower processing with FPDI
- **Large PDFs**: May take longer to process
- **Complex PDFs**: May require more memory

---

## 🎉 **Ready to Use!**

The enhanced PDF processing system now supports:
- ✅ **Unprotected PDFs** - Fast processing
- ✅ **Password-Protected PDFs** - With password support
- ✅ **Error Handling** - User-friendly messages
- ✅ **Security** - Secure password handling
- ✅ **Frontend Integration** - Easy to implement

**Your PDF summarization system is now fully equipped to handle password-protected documents!** 🚀
