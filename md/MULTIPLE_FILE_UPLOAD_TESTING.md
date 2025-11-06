# Multiple File Upload Testing Guide

## 🔍 Diagnosing the Issue

If you uploaded 3 files but only received 1 file ID, it's because the files weren't sent with the correct array notation.

## 🧪 Step 1: Test What Laravel is Receiving

I've created a test endpoint to help diagnose the issue.

**Test Endpoint:** `POST http://localhost:8000/api/files/test-upload`

This endpoint shows exactly what files Laravel is receiving without actually uploading them.

### Test in Postman:

1. **Method:** POST
2. **URL:** `http://localhost:8000/api/files/test-upload`
3. **Headers:** `Authorization: Bearer {your_token}`
4. **Body:** form-data

**Response Example:**
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
  "request_keys": ["files", "metadata"],
  "file_keys": ["files"]
}
```

## 📋 Step 2: Correct Postman Setup for Multiple Files

### ❌ WRONG WAY (Only Last File Will Upload)

**DON'T do this:**
```
Body → form-data:
- Key: file | Type: File | Value: [Select file 1]
- Key: file | Type: File | Value: [Select file 2]
- Key: file | Type: File | Value: [Select file 3]
```
**Problem:** Using the same key name `file` multiple times means only the last file is received.

---

### ✅ CORRECT WAY #1: Using Array Notation with Index

```
Body → form-data:
- Key: files[0] | Type: File | Value: [Select file 1]
- Key: files[1] | Type: File | Value: [Select file 2]
- Key: files[2] | Type: File | Value: [Select file 3]
```

**Screenshot Instructions:**
1. Click "Body" tab
2. Select "form-data"
3. For first file: Type `files[0]` in Key, change dropdown from "Text" to "File", click "Select Files"
4. For second file: Type `files[1]` in Key, change dropdown to "File", click "Select Files"
5. For third file: Type `files[2]` in Key, change dropdown to "File", click "Select Files"

---

### ✅ CORRECT WAY #2: Using Array Notation (Simpler)

```
Body → form-data:
- Key: files[] | Type: File | Value: [Select file 1]
- Key: files[] | Type: File | Value: [Select file 2]
- Key: files[] | Type: File | Value: [Select file 3]
```

**Screenshot Instructions:**
1. Click "Body" tab
2. Select "form-data"
3. For each file: Type `files[]` in Key, change dropdown from "Text" to "File", click "Select Files"
4. Repeat for each file you want to upload

---

## 🧪 Step 3: Test with the Test Endpoint First

Before doing actual uploads, test with the test endpoint:

### Test Request:
```
POST http://localhost:8000/api/files/test-upload
Authorization: Bearer {your_token}

Body (form-data):
- files[]: [file1.pdf]
- files[]: [file2.pdf]
- files[]: [file3.pdf]
```

### Expected Response:
```json
{
  "message": "File upload test endpoint",
  "has_file": false,
  "has_files": true,
  "file_count": 3,
  "files_is_array": true,
  "all_files": [
    {"name": "file1.pdf", "size": 238820, "mime": "application/pdf"},
    {"name": "file2.pdf", "size": 680366, "mime": "application/pdf"},
    {"name": "file3.pdf", "size": 450000, "mime": "application/pdf"}
  ],
  "request_keys": ["files"],
  "file_keys": ["files"]
}
```

**✅ If you see `"file_count": 3` and `"files_is_array": true`, you're doing it correctly!**

**❌ If you see `"file_count": 1`, your files are not being sent as an array.**

---

## 🚀 Step 4: Actual Upload with Multiple Files

Once the test endpoint confirms 3 files, use the actual upload endpoint:

### Upload Request:
```
POST http://localhost:8000/api/files/upload
Authorization: Bearer {your_token}

Body (form-data):
- files[]: [file1.pdf]
- files[]: [file2.pdf]
- files[]: [file3.pdf]
```

### Expected Response:
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
        "original_name": "file1.pdf",
        "stored_name": "uuid1.pdf",
        ...
      },
      "file_url": "http://localhost:8000/storage/uploads/files/uuid1.pdf"
    },
    {
      "file_upload": {
        "id": 209,
        "original_name": "file2.pdf",
        "stored_name": "uuid2.pdf",
        ...
      },
      "file_url": "http://localhost:8000/storage/uploads/files/uuid2.pdf"
    },
    {
      "file_upload": {
        "id": 210,
        "original_name": "file3.pdf",
        "stored_name": "uuid3.pdf",
        ...
      },
      "file_url": "http://localhost:8000/storage/uploads/files/uuid3.pdf"
    }
  ],
  "errors": []
}
```

**Extract File IDs:**
- File 1 ID: `208`
- File 2 ID: `209`
- File 3 ID: `210`

---

## 🐛 Troubleshooting

### Issue: Still Only Getting 1 File

**Check Laravel Logs:**
```bash
tail -f storage/logs/laravel.log
```

Look for:
```
File upload request received
{
  "has_file": false,
  "has_files": true,
  "files_is_array": true,
  "all_files": {...}
}
```

If you see `"files_is_array": false` or `"has_files": false`, the files are not being sent correctly.

---

### Issue: Postman Not Sending Files as Array

**Solution 1: Clear and Recreate Request**
1. Create a new Postman request from scratch
2. Carefully use `files[]` notation
3. Ensure each file has Type set to "File" (not "Text")

**Solution 2: Use Postman Collection**
Import this collection:

```json
{
  "info": {
    "name": "Multiple File Upload Test",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Test Multiple File Upload",
      "request": {
        "method": "POST",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{token}}",
            "type": "text"
          }
        ],
        "body": {
          "mode": "formdata",
          "formdata": [
            {
              "key": "files[]",
              "type": "file",
              "src": []
            },
            {
              "key": "files[]",
              "type": "file",
              "src": []
            },
            {
              "key": "files[]",
              "type": "file",
              "src": []
            }
          ]
        },
        "url": {
          "raw": "http://localhost:8000/api/files/test-upload",
          "protocol": "http",
          "host": ["localhost"],
          "port": "8000",
          "path": ["api", "files", "test-upload"]
        }
      }
    },
    {
      "name": "Upload Multiple Files",
      "request": {
        "method": "POST",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{token}}",
            "type": "text"
          }
        ],
        "body": {
          "mode": "formdata",
          "formdata": [
            {
              "key": "files[]",
              "type": "file",
              "src": []
            },
            {
              "key": "files[]",
              "type": "file",
              "src": []
            },
            {
              "key": "files[]",
              "type": "file",
              "src": []
            }
          ]
        },
        "url": {
          "raw": "http://localhost:8000/api/files/upload",
          "protocol": "http",
          "host": ["localhost"],
          "port": "8000",
          "path": ["api", "files", "upload"]
        }
      }
    }
  ]
}
```

---

### Issue: Getting Validation Errors

**Error:**
```json
{
  "error": "Validation failed",
  "messages": {
    "files": ["The files field is required."]
  }
}
```

**Solution:**
- Make sure you're using `files[]` not `file`
- Make sure Type is set to "File" not "Text"

---

## 📸 Postman Screenshots Guide

### Correct Setup Visual Guide

**Step 1:** Open Body tab and select form-data
```
[Body] [form-data selected]
```

**Step 2:** Add first file
```
Key: files[]
Type dropdown: [File] ← Must be File, not Text
Value: [Select Files] button → Select your first PDF
```

**Step 3:** Add second file
```
Key: files[]
Type dropdown: [File]
Value: [Select Files] button → Select your second PDF
```

**Step 4:** Add third file
```
Key: files[]
Type dropdown: [File]
Value: [Select Files] button → Select your third PDF
```

**Your form-data should look like:**
```
┌─────────┬──────┬────────────────────────────┐
│ KEY     │ TYPE │ VALUE                      │
├─────────┼──────┼────────────────────────────┤
│ files[] │ File │ test 4 pages.pdf           │
│ files[] │ File │ 1 page test.pdf            │
│ files[] │ File │ another-document.pdf       │
└─────────┴──────┴────────────────────────────┘
```

---

## 🧪 cURL Testing

If Postman is still having issues, test with cURL:

### Test Endpoint:
```bash
curl -X POST http://localhost:8000/api/files/test-upload \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -F "files[]=@C:/path/to/file1.pdf" \
  -F "files[]=@C:/path/to/file2.pdf" \
  -F "files[]=@C:/path/to/file3.pdf"
```

### Actual Upload:
```bash
curl -X POST http://localhost:8000/api/files/upload \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -F "files[]=@C:/path/to/file1.pdf" \
  -F "files[]=@C:/path/to/file2.pdf" \
  -F "files[]=@C:/path/to/file3.pdf"
```

**Expected Output:**
```json
{
  "success": true,
  "message": "3 file(s) uploaded successfully",
  "uploaded_count": 3,
  ...
}
```

---

## ✅ Success Checklist

- [ ] Test endpoint shows `"file_count": 3`
- [ ] Test endpoint shows `"files_is_array": true`
- [ ] Actual upload returns `"uploaded_count": 3`
- [ ] Response has `"file_uploads"` array (plural) with 3 items
- [ ] Each item has a unique `file_upload.id`
- [ ] Laravel logs show correct file detection

---

## 📝 Quick Reference

| Scenario | Field Name | Postman Type | Expected Result |
|----------|------------|--------------|-----------------|
| Single file | `file` | File | 1 file uploaded |
| Multiple files | `files[]` | File (repeated) | 3 files uploaded |
| Multiple files | `files[0]`, `files[1]`, `files[2]` | File | 3 files uploaded |
| ❌ Wrong | `file`, `file`, `file` | File | Only 1 file (last one) |

---

## 🎯 Testing Steps Summary

1. **Test with test endpoint** (`/api/files/test-upload`)
2. **Verify `file_count` is 3**
3. **Use correct field name** (`files[]` not `file`)
4. **Upload to actual endpoint** (`/api/files/upload`)
5. **Verify `uploaded_count` is 3**
6. **Extract all file IDs** from `file_uploads` array

---

## 📞 Still Having Issues?

Check Laravel logs at `storage/logs/laravel.log` for detailed debugging information. The upload endpoint now logs exactly what it receives.

**Log Example:**
```
[2025-10-31 12:49:24] local.INFO: File upload request received
{
  "has_file": false,
  "has_files": true,
  "files_is_array": true,
  "all_files": {
    "files": [
      {"name": "file1.pdf", ...},
      {"name": "file2.pdf", ...},
      {"name": "file3.pdf", ...}
    ]
  }
}
```

If you see this in the logs, the endpoint is working correctly and will process all 3 files!

---

**Last Updated:** October 31, 2025  
**Test Endpoint:** `POST /api/files/test-upload`  
**Upload Endpoint:** `POST /api/files/upload`

