# ⚡ Quick Postman Test - PDF Merge

## ✅ What You Need

1. **Bearer Token**: `207|vhs65UeUfk2kpYoTpJX3zIClf4zQyixWZdkbfPYXf7ae0dfe`
2. **File IDs**: Upload 2+ PDF files first, get their IDs (e.g., `204`, `205`, `206`)
3. **Base URL**: `localhost:8000/api`

---

## 🧪 Test 1: Upload a File (Get File IDs)

### Request
```
POST localhost:8000/api/files/upload
```

### Headers
```
Authorization: Bearer 207|vhs65UeUfk2kpYoTpJX3zIClf4zQyixWZdkbfPYXf7ae0dfe
```

### Body (form-data)
- Key: `file`
- Type: File
- Value: [Select a PDF file]

### Expected Response (201)
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "file_upload": {
    "id": "204",
    "filename": "test.pdf",
    "original_name": "test.pdf",
    "mime_type": "application/pdf",
    "size": 12345,
    "path": "uploads/..."
  }
}
```

✅ **Save the `id` value** (e.g., `204`) - you'll need it for merge!

---

## 🧪 Test 2: Submit PDF Merge Job

### Request
```
POST localhost:8000/api/pdf/edit/merge
```

### Headers
```
Authorization: Bearer 207|vhs65UeUfk2kpYoTpJX3zIClf4zQyixWZdkbfPYXf7ae0dfe
Content-Type: application/json
```

### Body (raw JSON)
```json
{
  "file_ids": ["204", "205", "206"],
  "params": {
    "page_order": "as_uploaded",
    "remove_blank_pages": false,
    "add_page_numbers": false
  }
}
```

### Expected Response (202)
```json
{
  "success": true,
  "job_id": "abc-123-xyz-456",
  "status": "queued",
  "message": "PDF job queued successfully"
}
```

✅ **Save the `job_id` value** - you'll need it to check status!

---

## 🧪 Test 3: Check Merge Status

### Request
```
GET localhost:8000/api/pdf/edit/merge/status?job_id=abc-123-xyz-456
```

### Headers
```
Authorization: Bearer 207|vhs65UeUfk2kpYoTpJX3zIClf4zQyixWZdkbfPYXf7ae0dfe
```

### Expected Response (200)
```json
{
  "job_id": "abc-123-xyz-456",
  "status": "processing",
  "progress": 45,
  "stage": "processing",
  "error": null,
  "created_at": "2025-10-31T17:43:11Z",
  "updated_at": "2025-10-31T17:43:15Z"
}
```

**Status Values:**
- `queued` - Job is waiting to start
- `processing` - Job is running
- `completed` - Job finished successfully ✅
- `failed` - Job failed ❌

⏳ **Keep checking** every 2-3 seconds until `status` is `completed`!

---

## 🧪 Test 4: Get Merge Result

### Request
```
GET localhost:8000/api/pdf/edit/merge/result?job_id=abc-123-xyz-456
```

### Headers
```
Authorization: Bearer 207|vhs65UeUfk2kpYoTpJX3zIClf4zQyixWZdkbfPYXf7ae0dfe
```

### Expected Response (200)
```json
{
  "success": true,
  "job_id": "abc-123-xyz-456",
  "status": "completed",
  "result": {
    "files": ["merged.pdf"],
    "download_urls": [
      "http://localhost:8004/v1/files/abc-123-xyz-456/merged.pdf"
    ],
    "job_type": "merge"
  },
  "created_at": "2025-10-31T17:43:11Z",
  "completed_at": "2025-10-31T17:43:30Z"
}
```

✅ **Use the URLs in `download_urls`** to download your merged PDF!

---

## 🚨 Common Errors

### Error 1: Health Check Response
```json
{
  "status": "ok",
  "message": "Backend is running",
  "timestamp": "...",
  "version": "1.0.0"
}
```

**Problem:** You're hitting `localhost:8000` instead of `localhost:8000/api`  
**Fix:** Add `/api` to your base URL!

---

### Error 2: 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

**Problem:** Missing or invalid Bearer token  
**Fix:** Add the Authorization header with your token

---

### Error 3: 422 Validation Error
```json
{
  "success": false,
  "error": "At least two files are required for merge"
}
```

**Problem:** Not enough files or invalid file_ids  
**Fix:** Make sure you have 2+ valid file IDs in the `file_ids` array

---

### Error 4: 404 Not Found
```json
{
  "message": "Not Found"
}
```

**Problem:** Wrong endpoint URL  
**Fix:** Check the URL - should be `localhost:8000/api/pdf/edit/merge` (not `/api/api/...`)

---

## 📝 Checklist

Before testing merge:

- [ ] ✅ Queue worker is running: `php artisan queue:work`
- [ ] ✅ PDF microservice is running on port 8004
- [ ] ✅ You have your Bearer token
- [ ] ✅ You uploaded 2+ PDF files and have their IDs
- [ ] ✅ Base URL is set to `localhost:8000/api` (WITH `/api`)

---

## 🎯 Complete Test Flow in Postman

1. **Set Base URL**: `localhost:8000/api`
2. **Set Authorization**: Bearer Token = `207|vhs65UeUfk2kpYoTpJX3zIClf4zQyixWZdkbfPYXf7ae0dfe`
3. **Upload File**: POST `{{base_url}}/files/upload` → Get file_id
4. **Upload File**: POST `{{base_url}}/files/upload` → Get another file_id
5. **Submit Merge**: POST `{{base_url}}/pdf/edit/merge` with both file_ids → Get job_id
6. **Check Status**: GET `{{base_url}}/pdf/edit/merge/status?job_id={{job_id}}` → Wait for `completed`
7. **Get Result**: GET `{{base_url}}/pdf/edit/merge/result?job_id={{job_id}}` → Get download URLs

---

## 🔧 Debugging

If you get the "Backend is running" response:

1. Check your **actual URL** in Postman
2. Should be: `localhost:8000/api/pdf/edit/merge`
3. NOT: `localhost:8000/pdf/edit/merge` (missing `/api`)
4. NOT: `localhost:8000` (just root)

---

**Test with this and let me know what you get!** 🚀

