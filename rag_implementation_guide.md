# 🚀 **RAG Implementation Guide**

## **📋 Overview**

This document provides a comprehensive guide to the RAG (Retrieval-Augmented Generation) implementation in the Laravel project. The RAG system enables intelligent document summarization by chunking documents, generating embeddings, and retrieving relevant content for context-aware summaries.

## **🏗️ Architecture**

### **Core Components:**

1. **DocumentChunk Model** - Stores document chunks with embeddings
2. **EmbeddingService** - Generates OpenAI embeddings for text
3. **SimilaritySearchService** - Performs cosine similarity search
4. **RAGService** - Orchestrates the entire RAG pipeline
5. **Enhanced SummarizeController** - Integrates RAG with existing summarization

### **Database Schema:**

```sql
-- Document chunks table
CREATE TABLE document_chunks (
    id BIGINT PRIMARY KEY,
    upload_id BIGINT,
    chunk_index INT,
    content TEXT,
    embedding JSON,
    page_start INT,
    page_end INT,
    metadata JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (upload_id) REFERENCES content_uploads(id)
);

-- Enhanced content_uploads table
ALTER TABLE content_uploads ADD COLUMN rag_processed_at TIMESTAMP NULL;
ALTER TABLE content_uploads ADD COLUMN chunk_count INT DEFAULT 0;
ALTER TABLE content_uploads ADD COLUMN rag_enabled BOOLEAN DEFAULT FALSE;
```

## **🔧 API Endpoints**

### **1. Process Document for RAG**
```http
POST /api/rag/process
Authorization: Bearer {token}
Content-Type: application/json

{
  "upload_id": 123
}
```

**Response:**
```json
{
  "message": "Document processed for RAG successfully",
  "upload_id": 123,
  "status": "processed"
}
```

### **2. Get RAG Summary**
```http
POST /api/rag/summary
Authorization: Bearer {token}
Content-Type: application/json

{
  "upload_id": 123,
  "query": "What are the main benefits?",
  "max_chunks": 5,
  "mode": "detailed",
  "language": "en"
}
```

**Response:**
```json
{
  "summary": "Based on the relevant content...",
  "metadata": {
    "content_type": "rag",
    "processing_time": "3.5s",
    "tokens_used": 1250,
    "confidence": 0.95,
    "chunks_used": 5,
    "query": "What are the main benefits?"
  },
  "source_info": {
    "upload_id": 123,
    "chunks": [...],
    "total_chunks": 15
  }
}
```

### **3. Get RAG Status**
```http
GET /api/rag/status/{uploadId}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "upload_id": 123,
  "rag_enabled": true,
  "processed_at": "2025-10-03T13:30:00Z",
  "chunk_count": 15,
  "total_chunks": 15,
  "total_content_length": 45000,
  "page_range": {
    "min": 1,
    "max": 10
  }
}
```

### **4. Delete RAG Data**
```http
DELETE /api/rag/delete/{uploadId}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "RAG data deleted successfully",
  "upload_id": 123
}
```

## **🔄 RAG Workflow**

### **Step 1: Document Processing**
1. **Upload Document** - User uploads PDF/text file
2. **Extract Text** - Extract text content from document
3. **Chunk Document** - Split into 2000-character chunks with 200-character overlap
4. **Generate Embeddings** - Create OpenAI embeddings for each chunk
5. **Store in Database** - Save chunks and embeddings

### **Step 2: Query Processing**
1. **User Query** - User asks specific question or requests summary
2. **Generate Query Embedding** - Create embedding for user query
3. **Similarity Search** - Find most relevant chunks using cosine similarity
4. **Retrieve Content** - Get top-K relevant chunks
5. **Generate Summary** - Use retrieved content for context-aware summary

## **⚙️ Configuration**

### **Environment Variables:**
```env
OPENAI_API_KEY=your_openai_api_key
OPENAI_URL=https://api.openai.com/v1/chat/completions
OPENAI_EMBEDDING_URL=https://api.openai.com/v1/embeddings
```

### **Service Configuration:**
```php
// config/services.php
'openai' => [
    'api_key' => env('OPENAI_API_KEY'),
    'url' => env('OPENAI_URL'),
    'embedding_url' => env('OPENAI_EMBEDDING_URL', 'https://api.openai.com/v1/embeddings'),
],
```

## **📊 Performance Optimizations**

### **1. Caching Strategy:**
- **Embedding Cache** - Cache embeddings for 24 hours
- **Query Cache** - Cache similar queries
- **Document Cache** - Cache processed documents

### **2. Background Processing:**
- **Queue Jobs** - Process documents asynchronously
- **Progress Tracking** - Monitor processing status
- **Error Handling** - Retry failed operations

### **3. Database Optimization:**
- **Indexes** - Optimize query performance
- **JSON Columns** - Efficient vector storage
- **Batch Operations** - Process multiple chunks

## **🎯 Usage Examples**

### **Frontend Integration:**

#### **1. Process Document for RAG:**
```javascript
const processDocument = async (uploadId) => {
  const response = await fetch('/api/rag/process', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ upload_id: uploadId })
  });
  
  return await response.json();
};
```

#### **2. Get RAG Summary:**
```javascript
const getRAGSummary = async (uploadId, query) => {
  const response = await fetch('/api/rag/summary', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      upload_id: uploadId,
      query: query,
      max_chunks: 5
    })
  });
  
  return await response.json();
};
```

#### **3. Check RAG Status:**
```javascript
const checkRAGStatus = async (uploadId) => {
  const response = await fetch(`/api/rag/status/${uploadId}`, {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  return await response.json();
};
```

## **🔍 Advanced Features**

### **1. Query-Specific Summaries:**
- **Custom Queries** - Ask specific questions about documents
- **Context-Aware** - Retrieves relevant content for queries
- **Multi-Chunk** - Combines information from multiple chunks

### **2. Document Statistics:**
- **Chunk Count** - Number of document chunks
- **Content Length** - Total document size
- **Page Range** - Document page coverage
- **Processing Time** - RAG processing duration

### **3. Error Handling:**
- **Graceful Fallbacks** - Falls back to regular summarization
- **User-Friendly Messages** - Clear error descriptions
- **Logging** - Comprehensive error tracking

## **📈 Benefits**

### **1. Quality Improvements:**
- **Better Summaries** - Context-aware and relevant
- **Query-Specific** - Answers specific questions
- **Comprehensive** - Covers entire document
- **Accurate** - Based on relevant content

### **2. Performance Benefits:**
- **Faster Processing** - Cached embeddings
- **Scalable** - Database-based storage
- **Efficient** - Similarity search optimization
- **Background** - Non-blocking processing

### **3. User Experience:**
- **Interactive** - Ask questions about documents
- **Comprehensive** - Full document coverage
- **Relevant** - Context-aware responses
- **Fast** - Quick retrieval and summarization

## **🚀 Getting Started**

### **1. Enable RAG for Document:**
```bash
curl -X POST http://localhost:8000/api/rag/process \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"upload_id": 123}'
```

### **2. Get RAG Summary:**
```bash
curl -X POST http://localhost:8000/api/rag/summary \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "upload_id": 123,
    "query": "What are the key findings?",
    "max_chunks": 5
  }'
```

### **3. Check Status:**
```bash
curl -X GET http://localhost:8000/api/rag/status/123 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## **🔧 Troubleshooting**

### **Common Issues:**

1. **Document Not Processed:**
   - Check if document exists
   - Verify file type is supported
   - Ensure text extraction worked

2. **Embedding Generation Failed:**
   - Check OpenAI API key
   - Verify API limits
   - Check network connectivity

3. **Similarity Search Issues:**
   - Verify embeddings are stored
   - Check chunk count
   - Ensure query embedding generation

### **Debug Commands:**
```bash
# Check RAG status
php artisan tinker
>>> $upload = App\Models\ContentUpload::find(1);
>>> $upload->rag_enabled;

# Check chunks
>>> $upload->chunks()->count();

# Check embeddings
>>> $chunk = $upload->chunks()->first();
>>> count($chunk->embedding);
```

## **📚 Next Steps**

### **1. Enhanced Features:**
- **Multi-Document RAG** - Search across multiple documents
- **Advanced Chunking** - Semantic chunking strategies
- **Vector Indexing** - Optimized similarity search
- **Real-Time Processing** - Live document updates

### **2. Performance Improvements:**
- **Vector Database** - Dedicated vector storage
- **Caching Layer** - Redis for embeddings
- **Batch Processing** - Bulk operations
- **Async Processing** - Background jobs

### **3. Integration:**
- **Frontend Components** - RAG UI components
- **API Documentation** - Comprehensive docs
- **Testing Suite** - Automated tests
- **Monitoring** - Performance metrics

---

**🎉 RAG Implementation Complete!**

The RAG system is now fully integrated into your Laravel project, providing intelligent document summarization with context-aware responses and query-specific summaries.
