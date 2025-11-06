# Multiple File Upload Feature

## Overview
The file upload endpoint now supports uploading multiple files in a single request while maintaining backward compatibility with single file uploads.

## Endpoints

### Single File Upload (Existing - Backward Compatible)

**Endpoint:** `POST /api/files/upload`

**Form Data:**
- `file` - The file to upload (required)
- `metadata` - Optional metadata object

**Example Request (Postman):**
```
POST http://localhost:8000/api/files/upload
Headers:
  Authorization: Bearer {token}
Body (form-data):
  file: [Select file]
  metadata[key]: value
```

**Example Response:**
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "file_upload": {
    "id": 207,
    "user_id": 17,
    "original_name": "test 4 pages.pdf",
    "stored_name": "931e2ff0-fb53-4eb7-a03c-73db65521fb7.pdf",
    "file_path": "uploads/files/931e2ff0-fb53-4eb7-a03c-73db65521fb7.pdf",
    "mime_type": "application/pdf",
    "file_size": 680366,
    "file_type": "pdf",
    "metadata": {
      "uploaded_at": "2025-10-31T12:43:52.797191Z",
      "client_ip": "::1",
      "user_agent": "PostmanRuntime/7.49.0"
    },
    "is_processed": false,
    "created_at": "2025-10-31T12:43:52.000000Z",
    "updated_at": "2025-10-31T12:43:52.000000Z"
  },
  "file_url": "http://localhost:8000/storage/uploads/files/931e2ff0-fb53-4eb7-a03c-73db65521fb7.pdf"
}
```

---

### Multiple File Upload (New)

**Endpoint:** `POST /api/files/upload`

**Form Data:**
- `files[]` - Array of files to upload (required)
- `metadata` - Optional metadata object (applies to all files)

**Example Request (Postman):**
```
POST http://localhost:8000/api/files/upload
Headers:
  Authorization: Bearer {token}
Body (form-data):
  files[0]: [Select file 1]
  files[1]: [Select file 2]
  files[2]: [Select file 3]
  metadata[key]: value
```

**Example Response:**
```json
{
  "success": true,
  "message": "3 file(s) uploaded successfully",
  "uploaded_count": 3,
  "error_count": 0,
  "file_uploads": [
    {
      "file_upload": {
        "id": 208,
        "user_id": 17,
        "original_name": "document1.pdf",
        "stored_name": "abc123.pdf",
        "file_path": "uploads/files/abc123.pdf",
        "mime_type": "application/pdf",
        "file_size": 123456,
        "file_type": "pdf",
        "metadata": {...},
        "is_processed": false,
        "created_at": "2025-10-31T12:50:00.000000Z",
        "updated_at": "2025-10-31T12:50:00.000000Z"
      },
      "file_url": "http://localhost:8000/storage/uploads/files/abc123.pdf"
    },
    {
      "file_upload": {
        "id": 209,
        "user_id": 17,
        "original_name": "document2.pdf",
        "stored_name": "def456.pdf",
        "file_path": "uploads/files/def456.pdf",
        "mime_type": "application/pdf",
        "file_size": 234567,
        "file_type": "pdf",
        "metadata": {...},
        "is_processed": false,
        "created_at": "2025-10-31T12:50:01.000000Z",
        "updated_at": "2025-10-31T12:50:01.000000Z"
      },
      "file_url": "http://localhost:8000/storage/uploads/files/def456.pdf"
    },
    {
      "file_upload": {
        "id": 210,
        "user_id": 17,
        "original_name": "document3.pdf",
        "stored_name": "ghi789.pdf",
        "file_path": "uploads/files/ghi789.pdf",
        "mime_type": "application/pdf",
        "file_size": 345678,
        "file_type": "pdf",
        "metadata": {...},
        "is_processed": false,
        "created_at": "2025-10-31T12:50:02.000000Z",
        "updated_at": "2025-10-31T12:50:02.000000Z"
      },
      "file_url": "http://localhost:8000/storage/uploads/files/ghi789.pdf"
    }
  ],
  "errors": []
}
```

**Example Response (with some errors):**
```json
{
  "success": true,
  "message": "2 file(s) uploaded successfully",
  "uploaded_count": 2,
  "error_count": 1,
  "file_uploads": [
    {
      "file_upload": {...},
      "file_url": "..."
    },
    {
      "file_upload": {...},
      "file_url": "..."
    }
  ],
  "errors": [
    {
      "index": 2,
      "filename": "invalid-file.xyz",
      "error": "Unsupported file type"
    }
  ]
}
```

## Validation Rules

### Single File Upload
- `file`: Required, must be a file, max 50MB (51,200 KB)
- `metadata`: Optional array

### Multiple File Upload
- `files`: Required array
- `files.*`: Each file is required, must be a file, max 50MB per file
- `metadata`: Optional array (applies to all files)

## How It Works

The endpoint automatically detects whether you're uploading a single file or multiple files:

1. **Single File**: Use `file` as the form field name
2. **Multiple Files**: Use `files[]` as the form field name (array notation)

The endpoint maintains full backward compatibility - existing single file upload code will continue to work unchanged.

## Usage Examples

### JavaScript (Fetch API)

**Single File:**
```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('metadata[key]', 'value');

const response = await fetch('http://localhost:8000/api/files/upload', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`
  },
  body: formData
});

const result = await response.json();
console.log('Uploaded file ID:', result.file_upload.id);
```

**Multiple Files:**
```javascript
const formData = new FormData();
const files = fileInput.files; // Multiple files from input

// Append all files
for (let i = 0; i < files.length; i++) {
  formData.append('files[]', files[i]);
}
formData.append('metadata[key]', 'value');

const response = await fetch('http://localhost:8000/api/files/upload', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`
  },
  body: formData
});

const result = await response.json();
console.log(`Uploaded ${result.uploaded_count} files`);
result.file_uploads.forEach(upload => {
  console.log('File ID:', upload.file_upload.id);
});
```

### cURL

**Single File:**
```bash
curl -X POST http://localhost:8000/api/files/upload \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@/path/to/document.pdf" \
  -F "metadata[key]=value"
```

**Multiple Files:**
```bash
curl -X POST http://localhost:8000/api/files/upload \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "files[]=@/path/to/document1.pdf" \
  -F "files[]=@/path/to/document2.pdf" \
  -F "files[]=@/path/to/document3.pdf" \
  -F "metadata[key]=value"
```

### Postman

**Single File:**
1. Method: POST
2. URL: `http://localhost:8000/api/files/upload`
3. Headers: `Authorization: Bearer {token}`
4. Body: form-data
   - Key: `file` (Type: File)
   - Value: Select your file

**Multiple Files:**
1. Method: POST
2. URL: `http://localhost:8000/api/files/upload`
3. Headers: `Authorization: Bearer {token}`
4. Body: form-data
   - Key: `files[0]` (Type: File) - Select file 1
   - Key: `files[1]` (Type: File) - Select file 2
   - Key: `files[2]` (Type: File) - Select file 3
   - Or use key: `files[]` multiple times (Postman will handle the array)

## Response Fields

### Single File Upload Response

| Field | Type | Description |
|-------|------|-------------|
| `success` | boolean | Whether upload was successful |
| `message` | string | Success message |
| `file_upload` | object | Uploaded file details |
| `file_upload.id` | integer | File ID (use this for operations) |
| `file_upload.original_name` | string | Original filename |
| `file_upload.stored_name` | string | Stored filename (UUID) |
| `file_upload.file_path` | string | Relative path to file |
| `file_upload.mime_type` | string | MIME type |
| `file_upload.file_size` | integer | File size in bytes |
| `file_upload.file_type` | string | File type (pdf, docx, etc.) |
| `file_url` | string | Full URL to access the file |

### Multiple File Upload Response

| Field | Type | Description |
|-------|------|-------------|
| `success` | boolean | Whether upload was successful |
| `message` | string | Summary message |
| `uploaded_count` | integer | Number of files uploaded successfully |
| `error_count` | integer | Number of files that failed |
| `file_uploads` | array | Array of uploaded file objects |
| `file_uploads[].file_upload` | object | Uploaded file details |
| `file_uploads[].file_url` | string | Full URL to access the file |
| `errors` | array | Array of error objects (if any) |
| `errors[].index` | integer | Index of failed file |
| `errors[].filename` | string | Filename that failed |
| `errors[].error` | string | Error message |

## Error Handling

### Single File Errors
```json
{
  "error": "Validation failed",
  "messages": {
    "file": ["The file field is required."]
  }
}
```

### Multiple File Errors
If some files succeed and others fail, the response will include both:
```json
{
  "success": true,
  "message": "2 file(s) uploaded successfully",
  "uploaded_count": 2,
  "error_count": 1,
  "file_uploads": [...],
  "errors": [
    {
      "index": 1,
      "filename": "large-file.pdf",
      "error": "File size exceeds maximum allowed size"
    }
  ]
}
```

## Best Practices

1. **Check `uploaded_count`**: Always verify how many files were uploaded successfully
2. **Handle Errors**: Check the `errors` array for any failed uploads
3. **Extract File IDs**: Loop through `file_uploads` array to get all file IDs
4. **File Size Limits**: Keep individual files under 50MB
5. **Batch Size**: For better performance, upload files in batches (e.g., 10-20 files at a time)

## Frontend Integration Example

### React Component

```jsx
import React, { useState } from 'react';

function MultiFileUpload() {
  const [files, setFiles] = useState([]);
  const [uploading, setUploading] = useState(false);
  const [result, setResult] = useState(null);

  const handleFileChange = (e) => {
    setFiles(Array.from(e.target.files));
  };

  const handleUpload = async () => {
    setUploading(true);
    const formData = new FormData();
    
    files.forEach((file) => {
      formData.append('files[]', file);
    });

    try {
      const response = await fetch('http://localhost:8000/api/files/upload', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        },
        body: formData
      });

      const data = await response.json();
      setResult(data);
      
      if (data.success) {
        console.log(`Uploaded ${data.uploaded_count} files`);
        data.file_uploads.forEach(upload => {
          console.log('File ID:', upload.file_upload.id);
        });
      }
    } catch (error) {
      console.error('Upload failed:', error);
    } finally {
      setUploading(false);
    }
  };

  return (
    <div>
      <input 
        type="file" 
        multiple 
        onChange={handleFileChange}
      />
      <button onClick={handleUpload} disabled={uploading || files.length === 0}>
        {uploading ? 'Uploading...' : `Upload ${files.length} file(s)`}
      </button>
      
      {result && (
        <div>
          <p>{result.message}</p>
          {result.errors && result.errors.length > 0 && (
            <div style={{color: 'red'}}>
              <h4>Errors:</h4>
              {result.errors.map((err, i) => (
                <p key={i}>{err.filename}: {err.error}</p>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  );
}
```

## Migration Guide

If you're updating existing code that uses the file upload endpoint:

### ✅ No Changes Needed
Single file upload code continues to work as before.

### ✅ To Add Multiple File Support
Just change from `file` to `files[]` in your form data:

**Before (Single):**
```javascript
formData.append('file', file);
```

**After (Multiple):**
```javascript
files.forEach(file => {
  formData.append('files[]', file);
});
```

And update response handling:

**Before (Single):**
```javascript
const fileId = response.file_upload.id;
```

**After (Multiple):**
```javascript
const fileIds = response.file_uploads.map(u => u.file_upload.id);
```

## Testing & Debugging

### Test Endpoint

A test endpoint is available to help diagnose file upload issues without actually uploading files.

**Endpoint:** `POST /api/files/test-upload`

**Purpose:** Shows exactly what files Laravel is receiving

**Example Request (Postman):**
```
POST http://localhost:8000/api/files/test-upload
Headers:
  Authorization: Bearer {token}
Body (form-data):
  files[]: [Select file 1]
  files[]: [Select file 2]
  files[]: [Select file 3]
```

**Example Response:**
```json
{
  "message": "File upload test endpoint",
  "has_file": false,
  "has_files": true,
  "file_count": 3,
  "files_is_array": true,
  "all_files": [
    {
      "name": "document1.pdf",
      "size": 123456,
      "mime": "application/pdf"
    },
    {
      "name": "document2.pdf",
      "size": 234567,
      "mime": "application/pdf"
    },
    {
      "name": "document3.pdf",
      "size": 345678,
      "mime": "application/pdf"
    }
  ],
  "request_keys": ["files"],
  "file_keys": ["files"]
}
```

**What to Check:**
- ✅ `"file_count"` should match the number of files you uploaded
- ✅ `"files_is_array"` should be `true` for multiple files
- ✅ `"all_files"` array should contain all your files

**Common Issues:**
- If `"file_count": 1` but you uploaded 3 files → Not using array notation correctly
- If `"files_is_array": false` → Files not sent as array
- If `"has_file": true` and `"has_files": false` → Using `file` instead of `files[]`

### Debug Logging

The upload endpoint now logs all incoming file information to help with debugging.

**Check Laravel Logs:**
```bash
tail -f storage/logs/laravel.log
```

**Example Log Entry:**
```
[2025-10-31 12:49:24] local.INFO: File upload request received
{
  "has_file": false,
  "has_files": true,
  "files_is_array": true,
  "all_files": {
    "files": [...]
  }
}
```

### Troubleshooting Steps

1. **Test with test endpoint first** (`/api/files/test-upload`)
2. **Verify file count** matches expected
3. **Check field name** is `files[]` (not `file`)
4. **Verify Type is "File"** in Postman (not "Text")
5. **Check Laravel logs** for detailed information
6. **Try actual upload** only after test endpoint confirms correct setup

### Postman Tips

**✅ CORRECT:**
```
Key: files[]    Type: File    Value: [Select Files]
Key: files[]    Type: File    Value: [Select Files]
Key: files[]    Type: File    Value: [Select Files]
```

**❌ WRONG:**
```
Key: file       Type: File    Value: [Select Files]
Key: file       Type: File    Value: [Select Files]
Key: file       Type: File    Value: [Select Files]
```
☝️ This will only upload the LAST file

**📚 Detailed Testing Guide:** See `md/MULTIPLE_FILE_UPLOAD_TESTING.md` for comprehensive testing instructions and troubleshooting.

---

## Summary

- ✅ **Backward Compatible**: Single file uploads work unchanged
- ✅ **Multiple File Support**: Upload multiple files in one request
- ✅ **Error Handling**: Gracefully handles partial failures
- ✅ **Easy Integration**: Simple field name change (`file` → `files[]`)
- ✅ **Flexible**: Supports optional metadata for all files
- ✅ **Test Endpoint**: Debug file uploads without actually uploading
- ✅ **Debug Logging**: Detailed logs for troubleshooting

---

**Updated:** October 31, 2025  
**Upload Endpoint:** `POST /api/files/upload`  
**Test Endpoint:** `POST /api/files/test-upload`  
**Controller:** `FileUploadController.php`  
**Testing Guide:** `md/MULTIPLE_FILE_UPLOAD_TESTING.md`

