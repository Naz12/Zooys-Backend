# 🤖 AI Manager API - Complete Update Documentation

**Date:** October 31, 2025  
**Version:** 2.0 (Updated Integration)  
**Status:** ✅ **COMPLETE**

---

## 📋 Overview

The **AI Manager** microservice has been significantly upgraded with new features and capabilities. This document covers the complete integration and all available features.

---

## 🆕 What's New in This Update

### **1. Multi-Model Support** 🎯
- **Model Selection**: Choose specific AI models per request
- **Available Models**:
  - `ollama:llama3` (8GB RAM optimized, default)
  - `ollama:mistral` (Alternative local model)
  - `gpt-4o` (OpenAI premium model)
  - `auto` (Workload-aware routing)

### **2. Model Discovery** 🔍
- **New Endpoint**: `GET /api/models`
- Discover all available models dynamically
- Returns model keys, vendors, and display names

### **3. Topic Chat** 💬 (NEW!)
- **New Endpoint**: `POST /api/topic-chat`
- Multi-turn conversations grounded in a topic
- Returns **supporting_points** (ready-to-use bullet points)
- Follow-up questions and suggested resources
- Perfect for presentations and summaries

### **4. New Task Types** ✨
- **`ppt-generate`**: PowerPoint/presentation generation
- **`flashcard`**: Flashcard creation from content

### **5. Enhanced Response Format** 📊
- Both `model_used` (technical key) and `model_display` (friendly name)
- Better error handling with `available_models` list
- Structured `status` field (`success`/`error`)

### **6. Workload-Aware Routing** 🧠
- Automatically routes across **Ollama**, **OpenAI**, **DeepSeek**
- Intelligent fallbacks if primary model fails
- Optimized for 8GB RAM servers

---

## 🔧 Configuration

### **Environment Variables**

Add to `.env`:
```env
AI_MANAGER_URL=https://aimanager.akmicroservice.com
AI_MANAGER_API_KEY=8eebab3587a5719950dfb3ee348737c6e244c13a5d6b3d35161071ee6a9d8c43
AI_MANAGER_TIMEOUT=180
AI_MANAGER_DEFAULT_MODEL=ollama:llama3
```

### **Config File** (`config/services.php`)

```php
'ai_manager' => [
    'url' => env('AI_MANAGER_URL', 'https://aimanager.akmicroservice.com'),
    'api_key' => env('AI_MANAGER_API_KEY', '...'),
    'timeout' => env('AI_MANAGER_TIMEOUT', 180),
    'default_model' => env('AI_MANAGER_DEFAULT_MODEL', 'ollama:llama3'),
],
```

---

## 📡 API Endpoints

### **Base URL**
```
https://aimanager.akmicroservice.com
```

### **Authentication**
All requests require:
```
X-API-KEY: 8eebab3587a5719950dfb3ee348737c6e244c13a5d6b3d35161071ee6a9d8c43
```

---

## 🎯 Available Tasks

### **1. Code Review** (`code-review`)
Analyzes code for issues, security risks, and suggestions.

**Request:**
```json
{
  "text": "<?php\nclass UserController { ... }",
  "task": "code-review",
  "model": "ollama:llama3"
}
```

**Response:**
```json
{
  "status": "success",
  "model_used": "ollama:llama3",
  "model_display": "llama3",
  "data": {
    "insights": "Code review identified 3 suggestions and 2 issues.",
    "suggestions": [
      {"line": 3, "description": "Add input validation", "severity": "high"}
    ],
    "issues": [
      {"line": 7, "description": "Plain-text password risk", "severity": "high"}
    ]
  }
}
```

---

### **2. Summarize** (`summarize`)
Creates concise summaries with key points.

**Request:**
```json
{
  "text": "The new software update includes...",
  "task": "summarize"
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "insights": "Software update enhances performance...",
    "key_points": [
      "Improved performance and security",
      "Redesigned user interface"
    ]
  }
}
```

---

### **3. Question Answering** (`qa`)
Answers questions based on provided context.

**Request:**
```json
{
  "text": "Context: Laravel is a PHP framework...\\nQuestion: What is Eloquent ORM?",
  "task": "qa"
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "answer": "Eloquent ORM is Laravel's tool for database interaction...",
    "confidence": 0.95
  }
}
```

---

### **4. Translation** (`translate`)
Translates text between languages.

**Request:**
```json
{
  "text": "Bonjour, comment allez-vous?",
  "task": "translate",
  "model": "gpt-4o"
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "translated_text": "Hello, how are you?",
    "source_lang": "fr",
    "target_lang": "en"
  }
}
```

---

### **5. Sentiment Analysis** (`sentiment`)
Analyzes emotional tone of text.

**Request:**
```json
{
  "text": "I absolutely love the new dashboard!",
  "task": "sentiment"
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "sentiment": "positive",
    "score": 0.9,
    "explanation": "Positive sentiment due to praise..."
  }
}
```

---

### **6. Text Generation** (`generate`)
Generates ideas and content.

**Request:**
```json
{
  "text": "Ideas for a mobile app to improve team collaboration",
  "task": "generate"
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "generated_content": "App ideas include real-time chat...",
    "ideas": [
      "Real-time chat with threaded replies",
      "Task management with Kanban boards"
    ]
  }
}
```

---

### **7. PowerPoint Generation** (`ppt-generate`) ✨ NEW
Generates presentation outlines and content.

**Request:**
```json
{
  "text": "AI in Education: Transforming Learning",
  "task": "ppt-generate",
  "slides_count": 10,
  "tone": "professional"
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "outline": ["Introduction", "Key Benefits", "Case Studies"],
    "slides": [
      {
        "title": "AI in Education",
        "content": ["Personalized learning", "Adaptive assessments"]
      }
    ]
  }
}
```

---

### **8. Flashcard Generation** (`flashcard`) ✨ NEW
Creates flashcards from educational content.

**Request:**
```json
{
  "text": "Photosynthesis is the process by which plants...",
  "task": "flashcard",
  "card_count": 5
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "flashcards": [
      {
        "front": "What is photosynthesis?",
        "back": "The process by which plants convert light energy..."
      }
    ]
  }
}
```

---

## 💬 Topic Chat (NEW FEATURE!)

### **Endpoint**
```
POST /api/topic-chat
```

### **Purpose**
Multi-turn conversations anchored to a specific topic with structured outputs.

### **Request**
```json
{
  "topic": "Summarizing the 2024 sustainability report for internal Q&A",
  "message": "What were the biggest emission reductions?",
  "model": "ollama:llama3",
  "messages": []  // Optional: previous conversation history
}
```

### **Response**
```json
{
  "status": "success",
  "model_used": "ollama:llama3",
  "model_display": "llama3",
  "data": {
    "reply": "The largest reductions came from electrifying the delivery fleet...",
    "supporting_points": [
      "Fleet electrification cut transport emissions by 28%",
      "Renewable energy contracts covered 92% of data centers"
    ],
    "follow_up_questions": [
      "Should we brief the sales team on these achievements?",
      "Do you need regional breakdowns?"
    ],
    "suggested_resources": [
      "intranet://sustainability/2024-report"
    ]
  }
}
```

### **Key Features**
- ✅ **Supporting Points**: Ready-to-use bullet points for slides/summaries
- ✅ **Follow-up Questions**: AI suggests next questions
- ✅ **Suggested Resources**: Related links and documents
- ✅ **Multi-turn**: Continue conversation with `messages` array

---

## 🔍 Model Discovery

### **Endpoint**
```
GET /api/models
```

### **Response**
```json
{
  "status": "success",
  "data": {
    "count": 3,
    "available_models": [
      {"key": "ollama:llama3", "vendor": "ollama", "display": "llama3"},
      {"key": "ollama:mistral", "vendor": "ollama", "display": "mistral"},
      {"key": "gpt-4o", "vendor": "generic", "display": "gpt-4o"}
    ]
  }
}
```

---

## 🧑‍💻 Internal Module Usage

### **Get AI Processing Module**
```php
use App\Services\Modules\ModuleRegistry;

$aiModule = ModuleRegistry::getModule('ai_processing');
```

### **Example 1: Summarize**
```php
$result = $aiModule->summarize('Your long text here', [
    'model' => 'ollama:llama3',
    'max_length' => 200
]);

// Returns: ['summary' => '...', 'key_points' => [...], 'model_used' => '...']
```

### **Example 2: Get Available Models**
```php
$models = $aiModule->getAvailableModels();

/*
Returns:
[
  'success' => true,
  'count' => 3,
  'models' => [
    ['key' => 'ollama:llama3', 'vendor' => 'ollama', 'display' => 'llama3'],
    ...
  ]
]
*/
```

### **Example 3: Topic Chat**
```php
$chatResult = $aiModule->topicChat(
    'Discussing Q3 sales performance',  // topic
    'What were our best products?',     // message
    [],                                  // previous messages
    ['model' => 'ollama:llama3']        // options
);

/*
Returns:
[
  'success' => true,
  'reply' => '...',
  'supporting_points' => ['Point 1', 'Point 2'],
  'follow_up_questions' => ['Question 1?'],
  'suggested_resources' => ['link1', 'link2'],
  'model_used' => 'ollama:llama3',
  'model_display' => 'llama3'
]
*/
```

### **Example 4: Generate Presentation**
```php
$presentation = $aiModule->generatePresentation('AI in Healthcare', [
    'slides_count' => 12,
    'tone' => 'professional',
    'target_audience' => 'medical professionals',
    'model' => 'gpt-4o'
]);

// Returns: ['outline' => [...], 'slides' => [...], 'model_used' => '...']
```

### **Example 5: Generate Flashcards**
```php
$flashcards = $aiModule->generateFlashcards('Photosynthesis content...', [
    'card_count' => 10,
    'difficulty' => 'intermediate',
    'model' => 'ollama:llama3'
]);

// Returns: ['flashcards' => [{front: '...', back: '...'}], ...]
```

### **Example 6: Code Review**
```php
$review = $aiModule->reviewCode($phpCode, [
    'model' => 'gpt-4o'  // Use premium model for better analysis
]);

/*
Returns:
[
  'insights' => '...',
  'suggestions' => [
    ['line' => 5, 'description' => '...', 'severity' => 'high']
  ],
  'issues' => [...],
  'model_used' => 'gpt-4o'
]
*/
```

---

## 🎨 Model Selection Strategy

### **When to Use Each Model**

| Model              | Best For                          | RAM Usage | Speed    | Cost      |
|--------------------|-----------------------------------|-----------|----------|-----------|
| `ollama:llama3`    | General tasks, summaries          | 8GB       | Fast     | Free      |
| `ollama:mistral`   | Creative writing, generation      | 8GB       | Fast     | Free      |
| `gpt-4o`           | Complex analysis, code review     | Cloud     | Medium   | Paid      |
| `auto`             | Let AI Manager choose best model  | Varies    | Varies   | Varies    |

### **Examples**
```php
// Fast summary (use local model)
['model' => 'ollama:llama3']

// Creative content (use mistral)
['model' => 'ollama:mistral']

// Complex code review (use premium)
['model' => 'gpt-4o']

// Let AI decide (workload-aware)
['model' => 'auto']  // or omit model field
```

---

## ⚠️ Error Handling

### **Error Response Format**
```json
{
  "status": "error",
  "message": "Model 'gpt-5' not found",
  "available_models": [
    {"key": "ollama:llama3", ...},
    {"key": "gpt-4o", ...}
  ]
}
```

### **Common Errors**

| Error | Cause | Solution |
|-------|-------|----------|
| **422**: `unsupported_model` | Model not available | Use `/api/models` to get valid models |
| **401**: `Unauthorized` | Wrong API key | Check `AI_MANAGER_API_KEY` |
| **timeout** | Request took too long | Increase `AI_MANAGER_TIMEOUT` |
| **Circuit breaker open** | Service marked unavailable | Wait 2 minutes or call `resetCircuitBreaker()` |

---

## 📈 Performance Tips

1. **Use Local Models**: `ollama:llama3` for 90% of tasks (free, fast)
2. **Reserve Premium**: Use `gpt-4o` only for complex tasks
3. **Topic Chat**: Perfect for presentations (supporting_points ready to use)
4. **Model Discovery**: Cache available models to reduce API calls
5. **Batch Processing**: Group similar tasks together

---

## ✅ Integration Complete

### **Files Updated**
- ✅ `config/services.php` - New API key and config
- ✅ `app/Services/AIManagerService.php` - All new features
- ✅ `app/Services/Modules/AIProcessingModule.php` - New methods
- ✅ `app/Services/Modules/ModuleRegistry.php` - Updated config

### **New Features Available**
- ✅ Model selection per request
- ✅ Model discovery endpoint
- ✅ Topic chat with supporting points
- ✅ PowerPoint generation
- ✅ Flashcard generation
- ✅ Enhanced error handling
- ✅ Multi-backend routing

---

## 🚀 Next Steps

1. **Update `.env`** with new API key (see `AI_MANAGER_ENV_SETUP.md`)
2. **Clear config cache**: `php artisan config:clear`
3. **Test model discovery**: `$ai->getAvailableModels()`
4. **Try topic chat** for your next presentation!
5. **Explore PPT generation** for automated slides

---

## 📞 Support

- **Logs**: `storage/logs/laravel.log`
- **Test Connection**: `php artisan tinker` → `$ai->getAvailableModels()`
- **Circuit Breaker**: Auto-resets after 2 minutes

**Happy AI Processing!** 🤖✨









