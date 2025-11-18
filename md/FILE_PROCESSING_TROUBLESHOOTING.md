# File Processing Endpoint Troubleshooting

## ✅ Correct Endpoint Format

**URL:** `POST http://localhost:8000/api/file-processing/convert`

**Required Headers:**
```
Authorization: Bearer {your_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "file_id": "string (required)",
  "target_format": "pdf|png|jpg|jpeg|docx|txt|html (required)",
  "options": {} // optional
}
```

---

## ❌ Common Issues

### 1. "Route not found" Error

**Possible Causes:**
- ❌ Missing `/api` prefix: `/file-processing/convert` → ✅ `/api/file-processing/convert`
- ❌ Wrong HTTP method: Using GET instead of POST
- ❌ Missing authentication token
- ❌ Invalid or expired token
- ❌ Trailing slash: `/api/file-processing/convert/` (should not have trailing slash)

**Solution:**
1. Verify the full URL: `http://localhost:8000/api/file-processing/convert`
2. Ensure you're using POST method
3. Include valid Bearer token in Authorization header
4. Check token hasn't expired

---

### 2. Authentication Issues

**Error:** `401 Unauthenticated`

**Solution:**
- Get a valid token by logging in: `POST /api/login`
- Include token in header: `Authorization: Bearer {token}`
- Token format: `1|abc123def456...` (from Laravel Sanctum)

---

### 3. Validation Errors

**Error:** `422 Validation failed`

**Common Issues:**
- `file_id` doesn't exist in database
- `target_format` not in allowed list
- Missing required fields

**Solution:**
- Upload file first: `POST /api/files/upload` → Get `file_id`
- Use valid format: `pdf`, `png`, `jpg`, `jpeg`, `docx`, `txt`, `html`
- Check request body structure

---

## 🔍 Testing Steps

### Step 1: Verify Route Exists
```bash
php artisan route:list --path=file-processing/convert
```

Should show:
```
POST  api/file-processing/convert  Api\Client\FileExtractionController@convertDocument
```

### Step 2: Test Authentication
```bash
# Login first
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'

# Use token from response
```

### Step 3: Test Convert Endpoint
```bash
curl -X POST http://localhost:8000/api/file-processing/convert \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "file_id": "123",
    "target_format": "pdf"
  }'
```

---

## 📋 Complete Example Request

```http
POST /api/file-processing/convert HTTP/1.1
Host: localhost:8000
Authorization: Bearer 1|abc123def456ghi789jkl012mno345pqr678stu901vwx234yz
Content-Type: application/json

{
  "file_id": "204",
  "target_format": "pdf",
  "options": {}
}
```

---

## 🔧 Quick Fixes

1. **Clear route cache:**
   ```bash
   php artisan route:clear
   php artisan config:clear
   ```

2. **Verify route registration:**
   ```bash
   php artisan route:list | findstr convert
   ```

3. **Check authentication:**
   ```bash
   # Test auth endpoint
   curl -X GET http://localhost:8000/api/user \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```

---

## 📝 All File Processing Endpoints

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/api/file-processing/convert` | POST | ✅ | Convert document |
| `/api/file-processing/extract` | POST | ✅ | Extract content |
| `/api/file-processing/conversion-capabilities` | GET | ✅ | Get formats |
| `/api/file-processing/extraction-capabilities` | GET | ✅ | Get extraction options |
| `/api/file-processing/health` | GET | ✅ | Health check |

**All endpoints require:** `Authorization: Bearer {token}` header

