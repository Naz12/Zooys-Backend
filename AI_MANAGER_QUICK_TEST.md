# AI Manager Microservice - Quick Test Guide

**Updated:** November 4, 2025  
**Authentication:** Bearer Token (Updated from X-API-KEY)

---

## 🎯 Quick Summary

- ✅ **Authentication Format Changed:** Now using `Authorization: Bearer <token>`
- 🔴 **Current Status:** Microservice has server-side permission errors (500)
- 📝 **Action Needed:** Server admin must fix file permissions

---

## 🔧 Testing with PowerShell

### Test 1: Get Available Models
```powershell
$headers = @{
    "Authorization" = "Bearer 8eebab3587a5719950dfb3ee348737c6e244c13a5d6b3d35161071ee6a9d8c43"
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

$response = Invoke-WebRequest -Uri "https://aimanager.akmicroservice.com/api/models" -Method GET -Headers $headers
$response.Content | ConvertFrom-Json | ConvertTo-Json
```

### Test 2: Summarize Text
```powershell
$headers = @{
    "Authorization" = "Bearer 8eebab3587a5719950dfb3ee348737c6e244c13a5d6b3d35161071ee6a9d8c43"
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

$body = @{
    text = "Artificial intelligence is revolutionizing the way we interact with technology."
    task = "summarize"
    options = @{
        model = "ollama:llama3"
        length = "brief"
    }
} | ConvertTo-Json

$response = Invoke-WebRequest -Uri "https://aimanager.akmicroservice.com/api/process" -Method POST -Headers $headers -Body $body
$response.Content | ConvertFrom-Json | ConvertTo-Json
```

### Test 3: Translate Text
```powershell
$headers = @{
    "Authorization" = "Bearer 8eebab3587a5719950dfb3ee348737c6e244c13a5d6b3d35161071ee6a9d8c43"
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

$body = @{
    text = "Hello, how are you?"
    task = "translate"
    options = @{
        model = "gpt-4o"
        target_language = "Spanish"
    }
} | ConvertTo-Json

$response = Invoke-WebRequest -Uri "https://aimanager.akmicroservice.com/api/process" -Method POST -Headers $headers -Body $body
$response.Content | ConvertFrom-Json | ConvertTo-Json
```

---

## 📮 Updated Postman Collection

**File:** `AI_Manager.postman_collection.json`

**Changes Made:**
- ✅ Changed from `X-API-KEY` header to `Authorization: Bearer` token
- ✅ Updated collection-level authentication
- ✅ All 11 endpoints now use Bearer token by default

**Authentication Configuration:**
```json
{
  "auth": {
    "type": "bearer",
    "bearer": [
      {
        "key": "token",
        "value": "{{api_key}}"
      }
    ]
  }
}
```

---

## 🐛 Current Issue

**Error Response:**
```json
{
  "message": "The stream or file '/home/deploy_user_dagi/services/ai_api_manager/storage/logs/laravel.log' could not be opened in append mode: Failed to open stream: Permission denied",
  "exception": "UnexpectedValueException"
}
```

**Root Cause:** Server file permissions

**Fix Required (Server Admin):**
```bash
# Fix storage permissions
cd /home/deploy_user_dagi/services/ai_api_manager
sudo chown -R deploy_user_dagi:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## ✅ Expected Response (After Fix)

### Models Endpoint:
```json
{
  "status": "success",
  "data": [
    {
      "key": "ollama:llama3",
      "vendor": "ollama",
      "model": "llama3",
      "display": "Llama 3 (Ollama)"
    },
    {
      "key": "gpt-4o",
      "vendor": "openai",
      "model": "gpt-4o",
      "display": "GPT-4 Omni"
    }
  ]
}
```

### Process Endpoint (Summarize):
```json
{
  "status": "success",
  "data": {
    "summary": "AI is transforming technology interaction.",
    "model_used": "ollama:llama3",
    "processing_time": 1.23
  }
}
```

---

## 📊 All Available Endpoints

| # | Endpoint | Method | Description |
|---|----------|--------|-------------|
| 1 | `/api/models` | GET | Get available AI models |
| 2 | `/api/process` | POST | Process text (summarize) |
| 3 | `/api/process` | POST | Code review |
| 4 | `/api/process` | POST | Q&A |
| 5 | `/api/process` | POST | Translation |
| 6 | `/api/process` | POST | Sentiment analysis |
| 7 | `/api/process` | POST | Generate text |
| 8 | `/api/presentations/generate` | POST | Generate presentation |
| 9 | `/api/flashcards/generate` | POST | Generate flashcards |
| 10 | `/api/topic-chat` | POST | Topic-based chat |
| 11 | `/api/health` | GET | Health check |

---

## 🔄 Laravel Backend Integration

**Updated Service Configuration:**

```php
// config/services.php
'ai_manager' => [
    'url' => env('AI_MANAGER_URL', 'https://aimanager.akmicroservice.com'),
    'api_key' => env('AI_MANAGER_API_KEY', '8eebab3587a5719950dfb3ee348737c6e244c13a5d6b3d35161071ee6a9d8c43'),
    'timeout' => env('AI_MANAGER_TIMEOUT', 180),
    'default_model' => env('AI_MANAGER_DEFAULT_MODEL', 'ollama:llama3'),
],
```

**Service Implementation:**
```php
// app/Services/AIManagerService.php
private function makeRequest($endpoint, $method, $data = [])
{
    $headers = [
        'Authorization' => 'Bearer ' . $this->apiKey, // Changed from X-API-KEY
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ];

    $response = Http::withHeaders($headers)
        ->timeout($this->timeout)
        ->$method($this->baseUrl . $endpoint, $data);

    return $response;
}
```

---

## 📝 Testing Checklist

Once server permissions are fixed:

- [ ] Test `/api/models` endpoint
- [ ] Test `/api/process` with summarize task
- [ ] Test `/api/process` with translate task
- [ ] Test `/api/process` with code review
- [ ] Test `/api/topic-chat` endpoint
- [ ] Test `/api/presentations/generate`
- [ ] Test `/api/flashcards/generate`
- [ ] Verify all responses have correct structure
- [ ] Verify authentication works consistently
- [ ] Test error handling (invalid model, invalid task)

---

## 🎉 Conclusion

**Authentication:** ✅ Now using standard `Authorization: Bearer` format  
**Postman Collection:** ✅ Updated and ready  
**Current Blocker:** 🔴 Server permission issue (not your code)  
**Next Step:** Contact server admin to fix file permissions

The microservice **accepts the authentication**, but **cannot process requests** due to server-side file permission errors.









