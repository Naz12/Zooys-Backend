# AI Manager Microservice - Complete API Documentation

**Version:** 2.0  
**Last Updated:** November 10, 2025  
**Base URL:** `https://aimanager.akmicroservice.com`  
**Status:** ✅ Production Ready

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Base Configuration](#base-configuration)
4. [API Endpoints](#api-endpoints)
5. [Supported Tasks](#supported-tasks)
6. [Available Models](#available-models)
7. [Request/Response Formats](#requestresponse-formats)
8. [Error Handling](#error-handling)
9. [Code Examples](#code-examples)
10. [Integration Guide](#integration-guide)
11. [Best Practices](#best-practices)

---

## 🎯 Overview

The **AI Manager Microservice** is a unified API gateway for multiple AI backends (Ollama, OpenAI, DeepSeek) that provides:

- **Multi-model support** with automatic workload routing
- **8 task types**: summarize, generate, qa, translate, sentiment, code-review, ppt-generate, flashcard
- **Topic-based chat** with conversation context
- **Model discovery** to dynamically list available models
- **Intelligent fallbacks** when models are unavailable
- **Optimized for 8GB RAM servers** with efficient resource management

---

## 🔐 Authentication

### Authentication Method

The microservice supports **Bearer Token** authentication (recommended) and **X-API-KEY** header (legacy, still supported).

### Recommended: Bearer Token

```http
Authorization: Bearer YOUR_API_KEY
```

### Legacy: X-API-KEY Header

```http
X-API-KEY: YOUR_API_KEY
```

### API Key

**Current API Key:** `8eebab3587a5719950dfb3ee348737c6e244c13a5d6b3d35161071ee6a9d8c43`

**Note:** Both authentication methods work, but `Authorization: Bearer` is the industry standard and recommended for new integrations.

---

## ⚙️ Base Configuration

### Environment Variables

```env
AI_MANAGER_URL=https://aimanager.akmicroservice.com
AI_MANAGER_API_KEY=8eebab3587a5719950dfb3ee348737c6e244c13a5d6b3d35161071ee6a9d8c43
AI_MANAGER_TIMEOUT=180
AI_MANAGER_DEFAULT_MODEL=deepseek-chat
```

### Laravel Configuration (`config/services.php`)

```php
'ai_manager' => [
    'url' => env('AI_MANAGER_URL', 'https://aimanager.akmicroservice.com'),
    'api_key' => env('AI_MANAGER_API_KEY', '8eebab3587a5719950dfb3ee348737c6e244c13a5d6b3d35161071ee6a9d8c43'),
    'timeout' => env('AI_MANAGER_TIMEOUT', 180),
    'default_model' => env('AI_MANAGER_DEFAULT_MODEL', 'deepseek-chat'),
],
```

---

## 🌐 API Endpoints

### 1. Process Text

**Endpoint:** `POST /api/process-text`  
**Description:** Process text with various AI tasks (summarize, generate, qa, translate, sentiment, code-review, ppt-generate, flashcard)

**Request Headers:**
```http
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "text": "Your text content here",
  "task": "summarize",
  "model": "deepseek-chat"
}
```

**Response (Success):**
```json
{
  "status": "success",
  "model_used": "deepseek-chat",
  "model_display": "deepseek-chat",
  "data": {
    "insights": "Generated content or summary...",
    "confidence_score": 0.85,
    "tokens_used": 150,
    "processing_time": 2.3
  }
}
```

**Response (Error):**
```json
{
  "status": "error",
  "message": "unsupported_model",
  "available_models": [
    {"key": "ollama:llama3", "vendor": "ollama", "display": "llama3"},
    {"key": "deepseek-chat", "vendor": "deepseek", "display": "deepseek-chat"}
  ]
}
```

---

### 2. Get Available Models

**Endpoint:** `GET /api/models`  
**Description:** Retrieve list of all available AI models

**Request Headers:**
```http
Authorization: Bearer YOUR_API_KEY
Accept: application/json
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "count": 4,
    "available_models": [
      {
        "key": "ollama:llama3",
        "vendor": "ollama",
        "display": "llama3"
      },
      {
        "key": "ollama:mistral",
        "vendor": "ollama",
        "display": "mistral"
      },
      {
        "key": "gpt-4o",
        "vendor": "openai",
        "display": "gpt-4o"
      },
      {
        "key": "deepseek-chat",
        "vendor": "deepseek",
        "display": "deepseek-chat"
      }
    ]
  }
}
```

---

### 3. Topic-Based Chat

**Endpoint:** `POST /api/topic-chat`  
**Description:** Multi-turn conversations grounded in a topic/summary

**Request Headers:**
```http
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "topic": "2024 sustainability report summary",
  "message": "What were the biggest emission reductions?",
  "messages": [
    {
      "role": "user",
      "content": "Previous user message"
    },
    {
      "role": "assistant",
      "content": "Previous assistant response"
    }
  ],
  "model": "deepseek-chat"
}
```

**Response:**
```json
{
  "status": "success",
  "model_used": "deepseek-chat",
  "model_display": "deepseek-chat",
  "data": {
    "reply": "The 2024 sustainability report highlights significant emission reductions...",
    "supporting_points": [
      "Scope 1 and 2 emissions dropped by 25%...",
      "Scope 3 emissions decreased by 15%...",
      "Key initiatives like solar installations contributed..."
    ],
    "follow_up_questions": [
      "Which specific renewable energy projects had the most impact?",
      "How did the company address challenges in supply chain emissions?"
    ],
    "suggested_resources": [
      "The Emissions Reduction section (page 12)",
      "Case study on renewable energy initiatives (page 18)"
    ]
  }
}
```

---

## 📝 Supported Tasks

| Task Key | Description | Use Case |
|----------|-------------|----------|
| `summarize` | Generate concise summaries | Long documents, articles, reports |
| `generate` | Generate new text content | Creative writing, content creation |
| `qa` | Question answering | Q&A systems, knowledge bases |
| `translate` | Translate text | Multi-language support |
| `sentiment` | Sentiment analysis | Social media, reviews, feedback |
| `code-review` | Code review and suggestions | Development, code quality |
| `ppt-generate` | PowerPoint/presentation generation | Presentations, slides |
| `flashcard` | Generate flashcards | Education, learning materials |

---

## 🤖 Available Models

| Model Key | Vendor | Display Name | Use Case |
|-----------|--------|--------------|----------|
| `ollama:llama3` | Ollama | llama3 | General purpose, local |
| `ollama:mistral` | Ollama | mistral | General purpose, optimized |
| `gpt-4o` | OpenAI | gpt-4o | Premium, high quality |
| `deepseek-chat` | DeepSeek | deepseek-chat | **Default**, cost-effective |
| `auto` | System | auto | Automatic workload routing |

**Default Model:** `deepseek-chat` (cost-effective and reliable)

**Model Selection:**
- If `model` is not specified, uses default model
- If `model: "auto"`, microservice automatically routes to best available model
- If specific model is unavailable, microservice falls back to alternative

---

## 📊 Request/Response Formats

### Standard Request Format

```json
{
  "text": "Content to process",
  "task": "task_key",
  "model": "model_key"
}
```

### Standard Success Response

```json
{
  "status": "success",
  "model_used": "model_key",
  "model_display": "Display Name",
  "data": {
    "insights": "Main result",
    "confidence_score": 0.85,
    "tokens_used": 150,
    "processing_time": 2.3,
    "raw_output": {}
  }
}
```

### Standard Error Response

```json
{
  "status": "error",
  "message": "Error description",
  "available_models": []
}
```

---

## ⚠️ Error Handling

### Common Error Codes

| Status Code | Description | Solution |
|-------------|-------------|----------|
| `400` | Bad Request | Check request format and parameters |
| `401` | Unauthorized | Verify API key is correct |
| `404` | Not Found | Check endpoint URL |
| `500` | Internal Server Error | Service issue, retry later |

### Error Response Format

```json
{
  "status": "error",
  "message": "unsupported_model",
  "available_models": [
    {"key": "model1", "vendor": "vendor1", "display": "Model 1"}
  ]
}
```

### Best Practices

1. **Always check `status` field** before processing response
2. **Use `available_models`** from error responses to retry with valid model
3. **Implement retry logic** with exponential backoff
4. **Handle timeouts** gracefully (default timeout: 180 seconds)
5. **Use circuit breaker pattern** to prevent cascade failures

---

## 💻 Code Examples

### PHP/Laravel

```php
use App\Services\AIManagerService;

$aiManager = app(AIManagerService::class);

// Summarize text
$result = $aiManager->summarize($text, [
    'model' => 'deepseek-chat'
]);

if ($result['success']) {
    echo $result['insights'];
    echo "Model used: " . $result['model_used'];
} else {
    echo "Error: " . $result['error'];
}

// Get available models
$models = $aiManager->getAvailableModels();
if ($models['success']) {
    foreach ($models['models'] as $model) {
        echo $model['key'] . " - " . $model['display'];
    }
}

// Topic chat
$chatResult = $aiManager->topicChat(
    topic: 'Document summary',
    message: 'What are the key points?',
    messages: [],
    options: ['model' => 'deepseek-chat']
);
```

### cURL

```bash
# Process text
curl -X POST "https://aimanager.akmicroservice.com/api/process-text" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "text": "Your text here",
    "task": "summarize",
    "model": "deepseek-chat"
  }'

# Get models
curl -X GET "https://aimanager.akmicroservice.com/api/models" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Accept: application/json"

# Topic chat
curl -X POST "https://aimanager.akmicroservice.com/api/topic-chat" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "topic": "Topic summary",
    "message": "Your question",
    "model": "deepseek-chat"
  }'
```

### JavaScript/Node.js

```javascript
const axios = require('axios');

const API_URL = 'https://aimanager.akmicroservice.com';
const API_KEY = 'YOUR_API_KEY';

// Process text
async function processText(text, task, model = 'deepseek-chat') {
  try {
    const response = await axios.post(
      `${API_URL}/api/process-text`,
      { text, task, model },
      {
        headers: {
          'Authorization': `Bearer ${API_KEY}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        timeout: 180000
      }
    );
    
    if (response.data.status === 'success') {
      return {
        success: true,
        insights: response.data.data.insights,
        model: response.data.model_used
      };
    } else {
      return {
        success: false,
        error: response.data.message
      };
    }
  } catch (error) {
    return {
      success: false,
      error: error.message
    };
  }
}

// Get models
async function getModels() {
  const response = await axios.get(`${API_URL}/api/models`, {
    headers: {
      'Authorization': `Bearer ${API_KEY}`,
      'Accept': 'application/json'
    }
  });
  return response.data.data.available_models;
}
```

### Python

```python
import requests

API_URL = 'https://aimanager.akmicroservice.com'
API_KEY = 'YOUR_API_KEY'

def process_text(text, task, model='deepseek-chat'):
    """Process text with AI Manager"""
    headers = {
        'Authorization': f'Bearer {API_KEY}',
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
    
    data = {
        'text': text,
        'task': task,
        'model': model
    }
    
    response = requests.post(
        f'{API_URL}/api/process-text',
        json=data,
        headers=headers,
        timeout=180
    )
    
    if response.status_code == 200:
        result = response.json()
        if result.get('status') == 'success':
            return {
                'success': True,
                'insights': result['data']['insights'],
                'model': result['model_used']
            }
        else:
            return {
                'success': False,
                'error': result.get('message', 'Unknown error')
            }
    else:
        return {
            'success': False,
            'error': f'HTTP {response.status_code}'
        }

def get_models():
    """Get available models"""
    headers = {
        'Authorization': f'Bearer {API_KEY}',
        'Accept': 'application/json'
    }
    
    response = requests.get(
        f'{API_URL}/api/models',
        headers=headers,
        timeout=30
    )
    
    if response.status_code == 200:
        return response.json()['data']['available_models']
    return []
```

### PowerShell

```powershell
$API_URL = "https://aimanager.akmicroservice.com"
$API_KEY = "YOUR_API_KEY"

# Process text
$headers = @{
    "Authorization" = "Bearer $API_KEY"
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

$body = @{
    text = "Your text here"
    task = "summarize"
    model = "deepseek-chat"
} | ConvertTo-Json

$response = Invoke-RestMethod -Uri "$API_URL/api/process-text" `
    -Method POST `
    -Headers $headers `
    -Body $body

Write-Output $response

# Get models
$modelsResponse = Invoke-RestMethod -Uri "$API_URL/api/models" `
    -Method GET `
    -Headers @{"Authorization" = "Bearer $API_KEY"; "Accept" = "application/json"}

Write-Output $modelsResponse.data.available_models
```

---

## 🔌 Integration Guide

### Step 1: Configure Environment

Add to your `.env` file:

```env
AI_MANAGER_URL=https://aimanager.akmicroservice.com
AI_MANAGER_API_KEY=8eebab3587a5719950dfb3ee348737c6e244c13a5d6b3d35161071ee6a9d8c43
AI_MANAGER_TIMEOUT=180
AI_MANAGER_DEFAULT_MODEL=deepseek-chat
```

### Step 2: Use Service in Laravel

```php
use App\Services\AIManagerService;

// In your controller or service
$aiManager = app(AIManagerService::class);

// Process text
$result = $aiManager->processText($text, 'summarize', [
    'model' => 'deepseek-chat'
]);
```

### Step 3: Handle Responses

```php
if ($result['success']) {
    $insights = $result['insights'];
    $model = $result['model_used'];
    // Process success
} else {
    $error = $result['error'];
    $availableModels = $result['available_models'] ?? [];
    // Handle error, possibly retry with available model
}
```

### Step 4: Implement Error Handling

```php
try {
    $result = $aiManager->summarize($text);
    
    if (!$result['success']) {
        // Check if model issue
        if (isset($result['available_models'])) {
            // Retry with first available model
            $result = $aiManager->summarize($text, [
                'model' => $result['available_models'][0]['key']
            ]);
        }
    }
} catch (\Exception $e) {
    // Handle exception
    Log::error('AI Manager error: ' . $e->getMessage());
}
```

---

## ✅ Best Practices

### 1. Model Selection

- **Use default model** (`deepseek-chat`) for most cases
- **Specify model explicitly** when you need deterministic results
- **Use `auto`** for automatic workload routing
- **Check `available_models`** in error responses for fallback options

### 2. Timeout Management

- **Default timeout:** 180 seconds (3 minutes)
- **Increase timeout** for large text processing
- **Implement client-side timeout** to prevent hanging requests

### 3. Error Handling

- **Always check `status` field** before processing
- **Implement retry logic** with exponential backoff
- **Use circuit breaker pattern** to prevent cascade failures
- **Log errors** for debugging and monitoring

### 4. Performance Optimization

- **Batch requests** when possible
- **Cache model lists** (refresh every 5-10 minutes)
- **Use appropriate model** for task (don't use premium models for simple tasks)
- **Monitor response times** and adjust timeout accordingly

### 5. Security

- **Never expose API key** in client-side code
- **Use environment variables** for configuration
- **Rotate API keys** periodically
- **Monitor API usage** for unusual patterns

### 6. Topic Chat Best Practices

- **Provide comprehensive topic** (summary or context)
- **Maintain conversation history** for context
- **Use `supporting_points`** for presentations
- **Leverage `follow_up_questions`** for user engagement
- **Reference `suggested_resources`** for additional information

---

## 📚 Additional Resources

### Laravel Service Implementation

- **Service Class:** `app/Services/AIManagerService.php`
- **Module Wrapper:** `app/Services/Modules/AIProcessingModule.php`
- **Module Registry:** `app/Services/Modules/ModuleRegistry.php`
- **Configuration:** `config/services.php`

### Related Documentation

- `AI_MANAGER_AUTH_UPDATE_SUMMARY.md` - Authentication update details
- `md/AI_MANAGER_FULLY_FUNCTIONAL.md` - Integration status
- `AI_MANAGER_QUICK_TEST.md` - Quick testing guide

---

## 🆘 Support & Troubleshooting

### Common Issues

1. **401 Unauthorized**
   - Verify API key is correct
   - Check authentication header format
   - Ensure API key hasn't expired

2. **500 Internal Server Error**
   - Service may be temporarily unavailable
   - Check service health endpoint
   - Retry after a few seconds

3. **Timeout Errors**
   - Increase timeout value
   - Reduce text length
   - Check network connectivity

4. **Model Not Available**
   - Check `available_models` in error response
   - Use alternative model
   - Use `auto` for automatic routing

### Health Check

```php
$health = $aiManager->checkHealth();
if ($health['success']) {
    // Service is available
} else {
    // Service is unavailable
}
```

---

## 📝 Changelog

### Version 2.0 (November 2025)
- ✅ Migrated to Bearer Token authentication
- ✅ Added topic-based chat endpoint
- ✅ Enhanced error handling with available_models
- ✅ Improved model discovery
- ✅ Added flashcard and ppt-generate tasks
- ✅ Optimized for 8GB RAM servers

### Version 1.0 (October 2025)
- Initial release
- Basic process-text endpoint
- Model selection support

---

## 📄 License & Terms

This microservice is part of the Zooys backend infrastructure. API keys are provided for authorized use only.

---

**Last Updated:** November 10, 2025  
**Documentation Version:** 2.0  
**Status:** ✅ Production Ready

