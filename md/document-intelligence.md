# 🧠 Document Intelligence API Documentation

## Overview

The Document Intelligence module provides **semantic search, RAG-powered Q&A, and conversational chat** capabilities for uploaded documents. Built on a cloud-based microservice with vector embeddings and LLM integration.

### **Key Features**
- 📄 **Document Ingestion** - Upload and index documents with OCR support
- 🔍 **Semantic Search** - Find relevant content using natural language queries
- 💬 **RAG-Powered Q&A** - Ask questions and get cited answers
- 🗨️ **Multi-Turn Chat** - Conversational interactions with **conversation memory**
- 🔐 **HMAC Authentication** - Secure communication with microservice
- ⚡ **Async Processing** - Background job processing with status tracking
- 🚀 **Force Fallback** - Skip local LLM for faster responses
- 🎯 **Advanced Filters** - Page ranges, metadata filtering

### **✨ What's New (Latest Update)**

#### **1. Conversation Memory** 🧠
Multi-turn chat now maintains context across questions using `conversation_id`:
```javascript
// First question
{ "query": "What is the contract value?", "conversation_id": "conv_123" }

// Follow-up (remembers context!)
{ "query": "Can you break that down?", "conversation_id": "conv_123" }
```

#### **2. Force Fallback Option** ⚡
Skip local LLM wait time, go straight to remote model:
```javascript
{ "query": "...", "force_fallback": true }  // Faster responses
```

#### **3. Advanced Filtering** 🎯
Filter searches by page ranges:
```javascript
{ "query": "...", "filters": { "page_range": [1, 10] } }
```

#### **4. Enhanced Health Monitoring** 🏥
Health endpoint now returns:
- `vector_status` - Vector database health
- `cache_status` - Cache system health
- `uptime` - Service uptime

---

## 🏗️ Architecture

### **Integration Points**
1. **Microservice**: `https://doc.akmicroservice.com`
2. **Laravel Backend**: Document Intelligence Service
3. **Universal Job Service**: Async job management
4. **File Upload System**: Existing file management

### **Data Flow**
```
User Upload → File System → Doc Intelligence Service
                                        ↓
                              Microservice (HMAC Auth)
                                        ↓
                        Vector DB + Embedding Model
                                        ↓
                              Search/Answer/Chat
                                        ↓
                            Job Result → User
```

---

## 📡 API Endpoints

All endpoints require `Bearer {token}` authentication.

### **Base URL**
```
http://localhost:8000/api
```

---

## 1️⃣ Document Ingestion

**Ingest a document for semantic indexing**

### **Endpoint**
```http
POST /documents/ingest
```

### **Headers**
```
Authorization: Bearer {token}
Content-Type: application/json
```

### **Request Body**
```json
{
  "file_id": 123,
  "ocr": "auto",
  "language": "eng",
  "metadata": {
    "tags": ["contract", "legal"],
    "source": "client_upload",
    "category": "business"
  }
}
```

### **Request Parameters**

| Parameter  | Type   | Required | Default | Description                           |
|------------|--------|----------|---------|---------------------------------------|
| `file_id`  | int    | Yes      | -       | ID from file_uploads table            |
| `ocr`      | string | No       | `auto`  | OCR mode: `off`, `auto`, `force`      |
| `language` | string | No       | `eng`   | Document language code                |
| `metadata` | object | No       | `{}`    | Custom metadata (tags, source, etc.)  |

### **Response (202 Accepted)**
```json
{
  "success": true,
  "message": "Document ingestion started",
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "pending"
}
```

### **Polling Status**
```http
GET /documents/jobs/{job_id}/status
```

**Response:**
```json
{
  "success": true,
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "completed",
  "stage": "completed",
  "progress": 100,
  "metadata": {
    "remote_job_id": "abc123",
    "doc_id": "doc_2e91c396cb"
  }
}
```

### **Getting Result**
```http
GET /documents/jobs/{job_id}/result
```

**Response:**
```json
{
  "success": true,
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "result": {
    "doc_id": "doc_2e91c396cb",
    "job_id": "abc123",
    "status": "completed"
  },
  "metadata": {
    "processing_time": 12.5,
    "completed_at": "2025-10-31T12:00:00.000000Z"
  }
}
```

---

## 2️⃣ Semantic Search

**Search documents using natural language**

### **Endpoint**
```http
POST /documents/search
```

### **Request Body**
```json
{
  "query": "What is the contract value and payment terms?",
  "doc_ids": ["doc_2e91c396cb", "doc_abc123"],
  "top_k": 5,
  "filters": {
    "page_range": [1, 10]
  }
}
```

### **Request Parameters**

| Parameter  | Type     | Required | Default | Description                              |
|------------|----------|----------|---------|------------------------------------------|
| `query`    | string   | Yes      | -       | Natural language search query            |
| `doc_ids`  | array    | No       | `[]`    | Filter by specific document IDs          |
| `top_k`    | int      | No       | `5`     | Number of results (1-20)                 |
| `filters`  | object   | No       | `{}`    | Additional filters (page_range, etc.)    |

### **Response (202 Accepted)**
```json
{
  "success": true,
  "message": "Search started",
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "pending"
}
```

### **Search Result**
```json
{
  "success": true,
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "result": {
    "mode": "semantic",
    "results": [
      {
        "text": "The contract value is $500,000 with quarterly payments",
        "doc_id": "doc_2e91c396cb",
        "page": 3,
        "score": 0.92
      },
      {
        "text": "Payment terms: Net 30 days from invoice date",
        "doc_id": "doc_2e91c396cb",
        "page": 5,
        "score": 0.87
      }
    ]
  }
}
```

---

## 3️⃣ Q&A (One-Shot Answer)

**Ask a question and get a RAG-powered answer with citations**

### **Endpoint**
```http
POST /documents/answer
```

### **Request Body**
```json
{
  "query": "What are the key contract terms and conditions?",
  "doc_ids": ["doc_2e91c396cb"],
  "llm_model": "llama3",
  "max_tokens": 512,
  "top_k": 3,
  "temperature": 0.7
}
```

### **Request Parameters**

| Parameter        | Type    | Required | Default   | Description                               |
|------------------|---------|----------|-----------|-------------------------------------------|
| `query`          | string  | Yes      | -         | Question to ask                           |
| `doc_ids`        | array   | Yes      | -         | Documents to search (required)            |
| `llm_model`      | string  | No       | `llama3`  | LLM: `llama3`, `mistral:latest`, `gpt-4`  |
| `max_tokens`     | int     | No       | `512`     | Max response tokens (50-2000)             |
| `top_k`          | int     | No       | `3`       | Context chunks (1-10)                     |
| `temperature`    | float   | No       | `0.7`     | LLM temperature (0-2)                     |
| `force_fallback` | bool    | No       | `false`   | Skip local LLM, use remote (faster)       |
| `filters`        | object  | No       | `{}`      | Additional filters (page_range, etc.)     |

### **Response (202 Accepted)**
```json
{
  "success": true,
  "message": "Answer generation started",
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "pending"
}
```

### **Answer Result**
```json
{
  "success": true,
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "result": {
    "query": "What are the key contract terms?",
    "llm_model": "llama3",
    "answer": "The key contract terms include: 1) Contract value of $500,000, 2) Quarterly payment schedule, 3) Net 30 payment terms, 4) Two-year agreement period, 5) Standard termination clause with 90-day notice.",
    "sources": [
      {
        "doc_id": "doc_2e91c396cb",
        "page": 3,
        "score": 0.91
      },
      {
        "doc_id": "doc_2e91c396cb",
        "page": 5,
        "score": 0.89
      }
    ]
  }
}
```

---

## 4️⃣ Conversational Chat

**Multi-turn conversation with document context**

### **Endpoint**
```http
POST /documents/chat
```

### **Request Body**
```json
{
  "query": "Can you summarize section 3 in simple terms?",
  "doc_ids": ["doc_2e91c396cb"],
  "conversation_id": "conv_xyz",
  "llm_model": "mistral:latest",
  "max_tokens": 512,
  "top_k": 3
}
```

### **Request Parameters**

| Parameter         | Type   | Required | Default   | Description                               |
|-------------------|--------|----------|-----------|-------------------------------------------|
| `query`           | string | Yes      | -         | Current message/question                  |
| `doc_ids`         | array  | Yes      | -         | Documents to chat with                    |
| `conversation_id` | string | No       | auto      | ID to maintain context (auto-gen)         |
| `llm_model`       | string | No       | `llama3`  | LLM: `llama3`, `mistral:latest`, `gpt-4`  |
| `max_tokens`      | int    | No       | `512`     | Max response tokens                       |
| `top_k`           | int    | No       | `3`       | Context chunks                            |
| `force_fallback`  | bool   | No       | `false`   | Skip local LLM, use remote (faster)       |
| `filters`         | object | No       | `{}`      | Additional filters (page_range, etc.)     |

### **Response (202 Accepted)**
```json
{
  "success": true,
  "message": "Chat started",
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "pending"
}
```

### **Chat Result**
```json
{
  "success": true,
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "result": {
    "conversation_id": "conv_xyz",
    "answer": "Section 3 discusses the payment structure. In simple terms: You'll pay $125,000 every quarter for two years, totaling $500,000. Invoices are due within 30 days of receipt.",
    "sources": [
      {
        "doc_id": "doc_2e91c396cb",
        "page": 3,
        "score": 0.93
      }
    ]
  }
}
```

### **Continuing the Conversation**
```json
{
  "query": "What happens if payment is late?",
  "doc_ids": ["doc_2e91c396cb"],
  "conversation_id": "conv_xyz"
}
```

---

## 5️⃣ Service Health Check

**Check microservice availability**

### **Endpoint**
```http
GET /documents/health
```

### **Response**
```json
{
  "success": true,
  "service": "document-intelligence",
  "ok": true,
  "uptime": 123456,
  "vector_status": "healthy",
  "cache_status": "healthy",
  "raw_health": {
    "ok": true,
    "uptime": 123456,
    "vector_status": "healthy",
    "cache_status": "healthy"
  }
}
```

### **Health Status Values**

| Field            | Type   | Description                                  |
|------------------|--------|----------------------------------------------|
| `ok`             | bool   | Overall health status                        |
| `uptime`         | int    | Service uptime in seconds                    |
| `vector_status`  | string | Vector DB status: `healthy`, `degraded`, `error` |
| `cache_status`   | string | Cache status: `healthy`, `degraded`, `error`     |

---

## 🔧 Internal Module Usage

Other AI modules can use Document Intelligence internally:

### **Example: Use from Another Service**

```php
use App\Services\DocumentIntelligenceService;

class MyAIModule
{
    public function processDocument($fileId)
    {
        $docService = app(DocumentIntelligenceService::class);
        
        // Ingest document
        $result = $docService->ingestFromFileId($fileId, [
            'ocr' => 'auto',
            'metadata' => ['source' => 'my_module']
        ]);
        
        $docId = $result['doc_id'];
        
        // Wait for completion
        $status = $docService->pollJobCompletion($result['job_id']);
        
        // Search the document
        $searchResult = $docService->search('find key insights', [
            'doc_ids' => [$docId],
            'top_k' => 5
        ]);
        
        // Ask a question
        $answer = $docService->answer('What are the main points?', [
            'doc_ids' => [$docId],
            'llm_model' => 'llama3'
        ]);
        
        return $answer;
    }
}
```

### **Register in ModuleRegistry**

```php
// Already registered as 'document_intelligence'
$docService = \App\Services\Modules\ModuleRegistry::getModule('document_intelligence');
```

---

## ⚙️ Configuration

### **Environment Variables**

Add to `.env`:

```env
# Document Intelligence Service
DOC_INTELLIGENCE_URL=https://doc.akmicroservice.com
DOC_INTELLIGENCE_TENANT=dagu
DOC_INTELLIGENCE_CLIENT_ID=dev
DOC_INTELLIGENCE_KEY_ID=local
DOC_INTELLIGENCE_SECRET=your_hmac_secret_here
DOC_INTELLIGENCE_TIMEOUT=120
```

### **Configuration File**

In `config/services.php`:

```php
'document_intelligence' => [
    'url' => env('DOC_INTELLIGENCE_URL', 'https://doc.akmicroservice.com'),
    'tenant' => env('DOC_INTELLIGENCE_TENANT', 'dagu'),
    'client_id' => env('DOC_INTELLIGENCE_CLIENT_ID', 'dev'),
    'key_id' => env('DOC_INTELLIGENCE_KEY_ID', 'local'),
    'secret' => env('DOC_INTELLIGENCE_SECRET', 'change_me'),
    'timeout' => env('DOC_INTELLIGENCE_TIMEOUT', 120),
],
```

---

## 🔐 Authentication

### **HMAC-SHA256 Signature**

The service uses HMAC authentication:

```
Signature = HMAC-SHA256(
    "METHOD|RESOURCE|QUERY|TIMESTAMP|CLIENT_ID|KEY_ID",
    SECRET
)
```

**Headers sent to microservice:**
```
X-Tenant-Id: dagu
X-Client-Id: dev
X-Key-Id: local
X-Timestamp: 1730400000
X-Signature: abc123def456...
```

---

## 📊 Job Status Flow

```
pending → processing → completed
                    → failed
                    → timeout
```

### **Job Stages**

| Stage                   | Description                          |
|-------------------------|--------------------------------------|
| `initializing`          | Job created                          |
| `validating`            | Validating input                     |
| `preparing_file`        | Preparing file for ingestion         |
| `starting_ingestion`    | Sending to microservice              |
| `monitoring`            | Polling microservice status          |
| `searching`             | Performing semantic search           |
| `generating_answer`     | Generating RAG answer                |
| `chatting`              | Processing chat message              |
| `completed`             | Job finished successfully            |
| `failed`                | Job failed with error                |

---

## 💡 Usage Examples

### **1. Ingest and Search**

```javascript
// 1. Upload file
POST /api/files/upload
Body: FormData with file

Response:
{
  "file_upload": { "id": 123 }
}

// 2. Ingest document
POST /api/documents/ingest
Body:
{
  "file_id": 123,
  "ocr": "auto",
  "metadata": { "tags": ["legal"] }
}

Response:
{
  "job_id": "job-123"
}

// 3. Poll status
GET /api/documents/jobs/job-123/status

// 4. Get result (doc_id)
GET /api/documents/jobs/job-123/result

Response:
{
  "result": { "doc_id": "doc_abc123" }
}

// 5. Search document
POST /api/documents/search
Body:
{
  "query": "contract value",
  "doc_ids": ["doc_abc123"]
}
```

### **2. Multi-Turn Chat with Conversation Memory**

```javascript
// First message - no conversation_id (auto-generated)
POST /api/documents/chat
Body:
{
  "query": "What is this document about?",
  "doc_ids": ["doc_abc123"]
}

Response (after job completes):
{
  "job_id": "chat-1",
  "result": {
    "conversation_id": "conv_xyz",  // ⚡ Save this!
    "answer": "This is a service agreement between Company A and Company B...",
    "sources": [...]
  }
}

// Follow-up message - reuse conversation_id for context
POST /api/documents/chat
Body:
{
  "query": "What are the payment terms?",  // Refers to "this" from previous
  "doc_ids": ["doc_abc123"],
  "conversation_id": "conv_xyz"  // ⚡ Maintains context!
}

Response:
{
  "result": {
    "conversation_id": "conv_xyz",
    "answer": "The payment terms in this agreement are quarterly...",
    "sources": [...]
  }
}

// Third message - even more context
POST /api/documents/chat
Body:
{
  "query": "Can you break that down further?",  // Refers to payment terms
  "doc_ids": ["doc_abc123"],
  "conversation_id": "conv_xyz"  // ⚡ Remembers all previous messages!
}
```

### **💡 Conversation Memory Tips**

1. **Save `conversation_id`**: Store it client-side for multi-turn conversations
2. **Auto-Generation**: If you don't provide one, the microservice generates a unique ID
3. **Context Window**: The microservice remembers the entire conversation history
4. **New Topics**: Start a new `conversation_id` for unrelated questions
5. **Unique Per User**: Use different conversation IDs for different users/sessions

---

## 🚀 Performance Considerations

### **Latency**

| Operation   | Typical Time | Notes                              |
|-------------|--------------|-------------------------------------|
| Ingest      | 5-60s        | Depends on file size and OCR        |
| Search      | < 1s         | Fast vector search                  |
| Answer      | 2-10s        | LLM generation time                 |
| Chat        | 2-10s        | Same as answer                      |

### **Cost Optimization**

- **Token Usage**: Set `max_tokens` appropriately (default: 512)
- **Context Size**: Use `top_k` to control context chunks (default: 3)
- **OCR**: Set `ocr: 'off'` for text-based PDFs to save processing time

### **Rate Limits**

- **Polling**: Polls every 2 seconds for up to 60 attempts (2 minutes)
- **Timeout**: Microservice timeout set to 120 seconds
- **Concurrency**: Multiple jobs can run simultaneously

---

## ❗ Error Handling

### **Common Errors**

| Error                           | Cause                              | Solution                           |
|---------------------------------|------------------------------------|------------------------------------|
| `File not found`                | Invalid `file_id`                  | Check file exists in database      |
| `doc_ids are required`          | Missing `doc_ids` in answer/chat   | Include at least one `doc_id`      |
| `Polling timeout exceeded`      | Job took too long                  | Retry with manual status checks    |
| `Unauthenticated`               | Missing/invalid bearer token       | Check authentication               |
| `HMAC signature failed`         | Wrong secret or timestamp          | Verify `.env` credentials          |

### **Error Response Format**

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "file_id": ["The file_id field is required."]
  }
}
```

---

## 📝 Best Practices

### **1. Document Management**

- **Save `doc_id`**: Store it in your database for future queries
- **Metadata**: Add meaningful tags and categories
- **OCR**: Use `auto` for best results

### **2. Search Optimization**

- **Specific Queries**: "contract payment terms section 3" > "payment"
- **Top K**: Start with 5, adjust based on results
- **Filters**: Use page ranges for large documents

### **3. Q&A/Chat**

- **Context**: Provide clear, specific questions
- **Conversation ID**: Reuse for follow-ups to maintain memory
- **Temperature**: Lower (0.3-0.5) for factual, higher (0.7-1.0) for creative
- **Force Fallback**: Use `force_fallback: true` when speed > accuracy
- **Filters**: Use `page_range` to limit search scope in large documents

### **4. Internal Module Integration**

- **Check Availability**: Use `isAvailable()` before operations
- **Error Handling**: Wrap calls in try-catch
- **Async Operations**: Use job polling for long operations

---

## 🔄 Workflow Example: Contract Analysis

```php
// 1. User uploads contract
$fileId = $request->input('file_id');

// 2. Ingest with metadata
$jobService = app(\App\Services\UniversalJobService::class);
$job = $jobService->createJob('document_intelligence', [
    'action' => 'ingest',
    'file_id' => $fileId,
    'params' => [
        'ocr' => 'auto',
        'metadata' => [
            'type' => 'contract',
            'client' => 'ACME Corp',
            'date' => now()->toDateString()
        ]
    ]
], [], auth()->id());

$jobService->queueJob($job['id']);

// 3. Poll until complete
$result = $jobService->getJob($job['id']);
$docId = $result['result']['doc_id'];

// 4. Ask questions
$questions = [
    'What is the contract value?',
    'What are the payment terms?',
    'What is the termination clause?'
];

foreach ($questions as $question) {
    $answerJob = $jobService->createJob('document_intelligence', [
        'action' => 'answer',
        'query' => $question,
        'doc_ids' => [$docId],
        'params' => ['llm_model' => 'llama3']
    ], [], auth()->id());
    
    $jobService->queueJob($answerJob['id']);
}
```

---

## ✅ Summary

✨ **Complete document intelligence integration** with:
- Async job processing
- HMAC authentication
- Multiple LLM models
- Semantic search
- RAG-powered Q&A
- Multi-turn conversations

🔌 **Fully integrated** with:
- Universal Job Service
- File Upload System
- Module Registry
- Existing architecture

🛠️ **Ready for internal use** by other AI modules!

---

## 📚 Additional Resources

- **Microservice Docs**: See original cURL test suite
- **Module Registry**: `app/Services/Modules/ModuleRegistry.php`
- **Universal Jobs**: `app/Services/UniversalJobService.php`
- **Service Code**: `app/Services/DocumentIntelligenceService.php`

