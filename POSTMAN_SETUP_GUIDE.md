# 📮 Postman Collections - Setup Guide

Complete Postman collections for testing both microservices manually.

---

## 📦 Files Created

### **AI Manager:**
1. `AI_Manager.postman_collection.json` - 11 endpoints
2. `AI_Manager.postman_environment.json` - Environment variables

### **Document Intelligence:**
1. `Document_Intelligence.postman_collection.json` - 7 endpoints with HMAC auth
2. `Document_Intelligence.postman_environment.json` - Environment variables

---

## 🚀 Quick Setup

### **Step 1: Import Collections**

1. Open Postman
2. Click **Import** button (top left)
3. Drag and drop these 4 files:
   - `AI_Manager.postman_collection.json`
   - `AI_Manager.postman_environment.json`
   - `Document_Intelligence.postman_collection.json`
   - `Document_Intelligence.postman_environment.json`

### **Step 2: Select Environment**

In Postman, top-right corner:
- **For AI Manager**: Select "AI Manager Environment"
- **For Document Intelligence**: Select "Document Intelligence Environment"

---

## 🤖 AI Manager Testing

### **Environment Variables**
```
base_url: https://aimanager.akmicroservice.com
api_key: 8eebab3587a5719950dfb3ee348737c6e244c13a5d6b3d35161071ee6a9d8c43
```

### **Available Endpoints (11 total)**

| # | Endpoint | Task | Model Example |
|---|----------|------|---------------|
| 1 | GET /api/models | Discover available models | - |
| 2 | POST /api/process-text | Summarize | ollama:llama3 |
| 3 | POST /api/process-text | Code Review | gpt-4o |
| 4 | POST /api/process-text | Question Answering | ollama:llama3 |
| 5 | POST /api/process-text | Translation | gpt-4o |
| 6 | POST /api/process-text | Sentiment Analysis | ollama:llama3 |
| 7 | POST /api/process-text | Generate Ideas | ollama:mistral |
| 8 | POST /api/process-text | PowerPoint | gpt-4o |
| 9 | POST /api/process-text | Flashcards | ollama:llama3 |
| 10 | POST /api/topic-chat | Topic Chat (start) | ollama:llama3 |
| 11 | POST /api/topic-chat | Topic Chat (continue) | ollama:llama3 |

### **Authentication**
✅ **Automatic** - Uses collection-level auth with `X-API-KEY` header

### **Test Order**
1. **Start with**: "1. Get Available Models" - See what's available
2. **Try any task**: All work independently
3. **For Topic Chat**: Run #10 first, then #11 to continue conversation

---

## 🧠 Document Intelligence Testing

### **Environment Variables**
```
base_url: https://doc.akmicroservice.com
tenant_id: dagu
client_id: dev
key_id: local
secret: change_me
timestamp: (auto-generated)
signature: (auto-generated)
job_id: (auto-saved)
doc_id: (auto-saved)
conversation_id: (auto-saved)
```

### **Available Endpoints (7 total)**

| # | Endpoint | Purpose | Auto-Save |
|---|----------|---------|-----------|
| 1 | GET /health | Check service status | - |
| 2 | POST /v1/ingest | Upload document | job_id, doc_id |
| 3 | GET /v1/jobs/{job_id} | Check ingestion status | - |
| 4 | POST /v1/search | Semantic search | - |
| 5 | POST /v1/answer | Ask questions (RAG) | - |
| 6 | POST /v1/chat | Start conversation | conversation_id |
| 7 | POST /v1/chat | Continue conversation | - |

### **Authentication**
✅ **Automatic HMAC-SHA256** - Pre-request scripts handle signature generation

### **Test Order (Recommended)**

#### **Full Flow:**
1. **"1. Health Check"** - Verify service is up
2. **"2. Ingest Document"** - Upload a PDF (job_id and doc_id auto-saved)
3. **"3. Check Job Status"** - Poll until status = "completed"
4. **"4. Search Documents"** - Test semantic search
5. **"5. Ask Question"** - Test RAG Q&A
6. **"6. Chat (Start)"** - Start conversation (conversation_id auto-saved)
7. **"7. Chat (Continue)"** - Continue with context

---

## 🔧 How HMAC Authentication Works

### **Document Intelligence uses HMAC-SHA256:**

**Pre-request Script** (runs automatically):
```javascript
// Generates signature for each request
const method = 'GET'; // or POST
const resource = '/health'; // or /v1/ingest, etc.
const timestamp = Math.floor(Date.now() / 1000);
const baseString = `${method}|${resource}||${timestamp}|${clientId}|${keyId}`;
const signature = CryptoJS.HmacSHA256(baseString, secret);
```

**Headers sent automatically:**
```
X-Tenant-Id: dagu
X-Client-Id: dev
X-Key-Id: local
X-Timestamp: 1762172714
X-Signature: 4c56cd7607ba44d5...
```

✅ **You don't need to do anything** - Scripts handle it!

---

## 💡 Smart Features

### **AI Manager:**
- ✅ Collection-level authentication (applies to all requests)
- ✅ Each request has example data pre-filled
- ✅ Model selection per request

### **Document Intelligence:**
- ✅ Auto-generates HMAC signatures (no manual calculation!)
- ✅ Auto-saves `job_id` after ingestion
- ✅ Auto-saves `doc_id` after ingestion
- ✅ Auto-saves `conversation_id` after first chat
- ✅ Variables automatically used in subsequent requests

---

## 📝 Usage Examples

### **Example 1: Test AI Manager Summarization**

1. Select "AI Manager Environment"
2. Open "AI Manager Microservice" collection
3. Click "2. Summarize Text"
4. Click **Send**
5. View response with summary and key points

**Response:**
```json
{
  "status": "success",
  "model_used": "ollama:llama3",
  "model_display": "llama3",
  "data": {
    "insights": "Software update enhances...",
    "key_points": [
      "Improved performance and security",
      "Redesigned user interface"
    ]
  }
}
```

---

### **Example 2: Complete Document Intelligence Flow**

1. Select "Document Intelligence Environment"
2. Open "Document Intelligence Microservice" collection

**Step 1: Health Check**
3. Click "1. Health Check" → Send
4. Verify `"ok": true`

**Step 2: Ingest Document**
5. Click "2. Ingest Document"
6. In Body → form-data → file → Select a PDF file
7. Click **Send**
8. Notice `job_id` and `doc_id` auto-saved in environment!

**Step 3: Check Status**
9. Click "3. Check Job Status" → Send
10. Repeat until `"status": "completed"` (every few seconds)

**Step 4: Search**
11. Click "4. Search Documents" → Send
12. View search results with scores

**Step 5: Ask Questions**
13. Click "5. Ask Question (RAG)" → Send
14. Get AI-generated answer with sources

**Step 6: Chat**
15. Click "6. Chat (Start)" → Send
16. Notice `conversation_id` auto-saved!
17. Click "7. Chat (Continue)" → Send
18. Context maintained from previous message!

---

## 🎯 Testing Tips

### **AI Manager:**

1. **Try different models:**
   - `ollama:llama3` - Fast, free, good quality
   - `ollama:mistral` - Creative tasks
   - `gpt-4o` - Premium quality (if server issue fixed)

2. **Adjust parameters:**
   - `slides_count` for presentations
   - `card_count` for flashcards
   - `tone` for generation

3. **Topic Chat:**
   - Use for presentation prep
   - `supporting_points` are ready-to-use bullets!

### **Document Intelligence:**

1. **OCR Settings:**
   - `off` - Text-based PDFs (fast)
   - `auto` - Auto-detect (recommended)
   - `force` - Always use OCR (scanned docs)

2. **Search Parameters:**
   - `top_k`: Number of results (1-20)
   - `page_range`: Limit search to specific pages
   - `filters`: Add metadata filters

3. **LLM Models:**
   - `llama3` - Fast, local
   - `mistral:latest` - Alternative
   - Experiment with `max_tokens` and `temperature`

4. **Conversation:**
   - First chat returns `conversation_id`
   - Use same ID for follow-ups
   - Maintains context across messages

---

## ⚠️ Known Issues

### **AI Manager:**
⚠️ **Server has permission issues** (as of last test)
- Endpoints reach the server
- Authentication is correct
- Server logs can't be written (admin needs to fix)
- Once fixed, all requests will work perfectly

### **Document Intelligence:**
✅ **Fully operational**
- All dependencies healthy
- HMAC auth working
- Ready to use immediately

---

## 🔍 Troubleshooting

### **"Invalid signature" error:**
- Check that environment variables are correct
- Ensure timestamp is being generated (check Console tab)
- Verify `secret` value is exactly: `change_me`

### **"Unauthorized" error:**
- For AI Manager: Check `api_key` in environment
- For Document Intelligence: Check all 5 auth headers

### **"Job not found" error:**
- Make sure you ran "2. Ingest Document" first
- Check that `job_id` was auto-saved (View environment)
- Manually set `job_id` in environment if needed

### **"Doc not found" error:**
- Run ingestion first
- Wait for `status: completed`
- Check `doc_id` in environment

---

## 📊 Response Examples

### **AI Manager - Summarize**
```json
{
  "status": "success",
  "model_used": "ollama:llama3",
  "model_display": "llama3",
  "data": {
    "insights": "Summary text...",
    "key_points": ["Point 1", "Point 2"]
  }
}
```

### **AI Manager - Topic Chat**
```json
{
  "status": "success",
  "model_used": "ollama:llama3",
  "data": {
    "reply": "The biggest reductions came from...",
    "supporting_points": [
      "Fleet electrification cut emissions by 28%",
      "Renewable energy covered 92% of data centers"
    ],
    "follow_up_questions": [
      "Should we brief the sales team?"
    ],
    "suggested_resources": [
      "intranet://sustainability/2024-report"
    ]
  }
}
```

### **Document Intelligence - Health**
```json
{
  "ok": true,
  "dependencies": {
    "qdrant": true,
    "meilisearch": true,
    "redis": true,
    "deepseek": true,
    "converter": true
  }
}
```

### **Document Intelligence - Search**
```json
{
  "mode": "semantic",
  "results": [
    {
      "text": "The contract value is $500,000...",
      "doc_id": "doc_abc123",
      "page": 3,
      "score": 0.92
    }
  ]
}
```

---

## ✅ Checklist

### **Setup:**
- [ ] Imported all 4 files into Postman
- [ ] Can see both collections in left sidebar
- [ ] Can see both environments in top-right dropdown

### **AI Manager:**
- [ ] Selected "AI Manager Environment"
- [ ] Tested "1. Get Available Models"
- [ ] Tried at least one task (summarize, code review, etc.)
- [ ] Tested "10. Topic Chat"

### **Document Intelligence:**
- [ ] Selected "Document Intelligence Environment"
- [ ] Tested "1. Health Check" - got `"ok": true`
- [ ] Uploaded a document with "2. Ingest Document"
- [ ] Waited for `"status": "completed"` with "3. Check Job Status"
- [ ] Tested "4. Search Documents"
- [ ] Tested "5. Ask Question"
- [ ] Started chat with "6. Chat (Start)"
- [ ] Continued with "7. Chat (Continue)"

---

## 🎉 You're Ready!

Both collections are fully configured with:
- ✅ Correct authentication
- ✅ Pre-filled example data
- ✅ Auto-generated signatures (Document Intelligence)
- ✅ Auto-saved variables (job_id, doc_id, conversation_id)
- ✅ All endpoints documented

**Start testing and exploring the microservices!** 🚀

---

## 📞 Need Help?

- **Check Console tab** in Postman for signature generation logs
- **Check environment variables** after each request
- **Review MICROSERVICES_TEST_RESULTS.md** for service status
- **Read response body** for detailed error messages



















