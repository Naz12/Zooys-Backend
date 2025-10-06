# 🤖 Chat AI API Documentation

## 🎯 **Overview**
Complete API documentation for the Chat AI system with session management, message handling, and conversation history.

---

## 🔐 **Authentication**
All endpoints require Bearer token authentication:
```http
Authorization: Bearer YOUR_TOKEN_HERE
```

---

## 📡 **API Endpoints**

### **1. General AI Chat (Backward Compatible)**
```
POST /api/chat
```

**Description:** Main chat endpoint with optional session support.

**Request Body:**
```json
{
  "message": "Hello, can you help me with programming?",
  "session_id": 123,
  "conversation_history": [
    {
      "role": "user",
      "content": "Previous message"
    },
    {
      "role": "assistant", 
      "content": "Previous response"
    }
  ],
  "model": "gpt-3.5-turbo",
  "temperature": 0.7,
  "max_tokens": 1000
}
```

**Response:**
```json
{
  "response": "I'd be happy to help you with programming!",
  "session_id": 123,
  "model_used": "gpt-3.5-turbo",
  "timestamp": "2025-10-03T10:30:00Z",
  "metadata": {
    "tokens_used": 150,
    "processing_time": "1.2s"
  }
}
```

### **2. Create Session and Chat**
```
POST /api/chat/create-and-chat
```

**Description:** Create a new chat session and send the first message.

**Request Body:**
```json
{
  "message": "Help me with React development",
  "name": "React Development Help",
  "description": "Getting assistance with React programming"
}
```

**Response:**
```json
{
  "response": "I'd be happy to help you with React development!",
  "session_id": 456,
  "model_used": "gpt-3.5-turbo",
  "timestamp": "2025-10-03T10:30:00Z",
  "metadata": {
    "tokens_used": 200,
    "processing_time": "1.5s"
  }
}
```

### **3. Get Chat History**
```
GET /api/chat/history
```

**Description:** Get general chat history for the user.

**Query Parameters:**
- `per_page` (optional): Number of items per page (default: 10)
- `page` (optional): Page number (default: 1)

**Response:**
```json
{
  "data": [
    {
      "id": 789,
      "user_id": 1,
      "tool_id": 1,
      "input": "Hello, can you help me?",
      "output": "I'd be happy to help you!",
      "created_at": "2025-10-03T10:30:00Z"
    }
  ],
  "total": 25,
  "per_page": 10,
  "current_page": 1,
  "last_page": 3
}
```

---

## 🗂️ **Chat Sessions Management**

### **4. List Chat Sessions**
```
GET /api/chat/sessions
```

**Description:** Get all chat sessions for the authenticated user.

**Query Parameters:**
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 20)

**Response:**
```json
{
  "sessions": [
    {
      "id": 123,
      "name": "Python Programming Help",
      "description": "Getting help with Python programming",
      "is_active": true,
      "message_count": 8,
      "last_activity": "2025-10-03T10:30:00Z",
      "created_at": "2025-10-03T09:00:00Z",
      "updated_at": "2025-10-03T10:30:00Z"
    },
    {
      "id": 124,
      "name": "Business Strategy Discussion",
      "description": "Planning business strategies",
      "is_active": true,
      "message_count": 12,
      "last_activity": "2025-10-03T09:45:00Z",
      "created_at": "2025-10-03T08:30:00Z",
      "updated_at": "2025-10-03T09:45:00Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 2,
    "per_page": 20,
    "total": 35
  }
}
```

### **5. Create Chat Session**
```
POST /api/chat/sessions
```

**Description:** Create a new chat session.

**Request Body:**
```json
{
  "name": "Machine Learning Discussion",
  "description": "Learning about machine learning concepts"
}
```

**Response:**
```json
{
  "session": {
    "id": 125,
    "name": "Machine Learning Discussion",
    "description": "Learning about machine learning concepts",
    "is_active": true,
    "created_at": "2025-10-03T11:00:00Z",
    "updated_at": "2025-10-03T11:00:00Z"
  }
}
```

### **6. Get Specific Session**
```
GET /api/chat/sessions/{sessionId}
```

**Description:** Get details of a specific chat session with all messages.

**Response:**
```json
{
  "session": {
    "id": 123,
    "name": "Python Programming Help",
    "description": "Getting help with Python programming",
    "is_active": true,
    "message_count": 8,
    "last_activity": "2025-10-03T10:30:00Z",
    "created_at": "2025-10-03T09:00:00Z",
    "updated_at": "2025-10-03T10:30:00Z",
    "messages": [
      {
        "id": 1001,
        "role": "user",
        "content": "How do I create a list in Python?",
        "metadata": {
          "tokens_used": 0,
          "processing_time": null
        },
        "created_at": "2025-10-03T09:00:00Z"
      },
      {
        "id": 1002,
        "role": "assistant",
        "content": "You can create a list in Python using square brackets...",
        "metadata": {
          "tokens_used": 150,
          "processing_time": "1.2s"
        },
        "created_at": "2025-10-03T09:00:05Z"
      }
    ]
  }
}
```

### **7. Update Chat Session**
```
PUT /api/chat/sessions/{sessionId}
```

**Description:** Update a chat session (rename, change description).

**Request Body:**
```json
{
  "name": "Advanced Python Programming",
  "description": "Advanced Python concepts and best practices"
}
```

**Response:**
```json
{
  "session": {
    "id": 123,
    "name": "Advanced Python Programming",
    "description": "Advanced Python concepts and best practices",
    "is_active": true,
    "updated_at": "2025-10-03T11:15:00Z"
  }
}
```

### **8. Delete Chat Session**
```
DELETE /api/chat/sessions/{sessionId}
```

**Description:** Delete a chat session permanently.

**Response:**
```json
{
  "message": "Chat session deleted successfully"
}
```

### **9. Archive Chat Session**
```
POST /api/chat/sessions/{sessionId}/archive
```

**Description:** Archive a chat session (soft delete).

**Response:**
```json
{
  "message": "Chat session archived successfully"
}
```

### **10. Restore Chat Session**
```
POST /api/chat/sessions/{sessionId}/restore
```

**Description:** Restore an archived chat session.

**Response:**
```json
{
  "message": "Chat session restored successfully"
}
```

---

## 💬 **Chat Messages**

### **11. Send Message to Session**
```
POST /api/chat/sessions/{sessionId}/messages
```

**Description:** Send a message to a specific chat session.

**Request Body:**
```json
{
  "content": "What are the best practices for error handling in Python?",
  "conversation_history": [
    {
      "role": "user",
      "content": "Previous message"
    },
    {
      "role": "assistant",
      "content": "Previous response"
    }
  ]
}
```

**Response:**
```json
{
  "user_message": {
    "id": 1003,
    "role": "user",
    "content": "What are the best practices for error handling in Python?",
    "created_at": "2025-10-03T11:30:00Z"
  },
  "ai_message": {
    "id": 1004,
    "role": "assistant",
    "content": "Here are the best practices for error handling in Python...",
    "metadata": {
      "tokens_used": 300,
      "processing_time": "2.1s"
    },
    "created_at": "2025-10-03T11:30:05Z"
  },
  "session": {
    "id": 123,
    "name": "Python Programming Help",
    "message_count": 10
  }
}
```

### **12. Get Session Messages**
```
GET /api/chat/sessions/{sessionId}/messages
```

**Description:** Get all messages for a specific chat session.

**Query Parameters:**
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 50)

**Response:**
```json
{
  "messages": [
    {
      "id": 1001,
      "role": "user",
      "content": "How do I create a list in Python?",
      "metadata": {
        "tokens_used": 0,
        "processing_time": null
      },
      "created_at": "2025-10-03T09:00:00Z"
    },
    {
      "id": 1002,
      "role": "assistant",
      "content": "You can create a list in Python using square brackets...",
      "metadata": {
        "tokens_used": 150,
        "processing_time": "1.2s"
      },
      "created_at": "2025-10-03T09:00:05Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 50,
    "total": 2
  }
}
```

### **13. Get Conversation History**
```
GET /api/chat/sessions/{sessionId}/history
```

**Description:** Get the complete conversation history for a session.

**Response:**
```json
{
  "session_id": 123,
  "session_name": "Python Programming Help",
  "conversation": [
    {
      "id": 1001,
      "role": "user",
      "content": "How do I create a list in Python?",
      "metadata": {
        "tokens_used": 0,
        "processing_time": null
      },
      "created_at": "2025-10-03T09:00:00Z"
    },
    {
      "id": 1002,
      "role": "assistant",
      "content": "You can create a list in Python using square brackets...",
      "metadata": {
        "tokens_used": 150,
        "processing_time": "1.2s"
      },
      "created_at": "2025-10-03T09:00:05Z"
    }
  ],
  "total_messages": 2
}
```

---

## 🚨 **Error Responses**

### **Validation Errors (422)**
```json
{
  "error": "Validation failed",
  "details": {
    "message": ["The message field is required."],
    "session_id": ["The session_id field must be a valid session ID."]
  }
}
```

### **Authentication Errors (401)**
```json
{
  "error": "Unauthenticated"
}
```

### **Not Found Errors (404)**
```json
{
  "error": "Chat session not found"
}
```

### **Server Errors (500)**
```json
{
  "error": "Unable to process chat request at this time"
}
```

---

## 🔧 **Frontend Integration Examples**

### **JavaScript/TypeScript**
```javascript
class ChatAI {
  constructor(baseURL, token) {
    this.baseURL = baseURL;
    this.token = token;
  }

  // Create new session and chat
  async createAndChat(message, name = null, description = null) {
    return this.request('/api/chat/create-and-chat', {
      message,
      name,
      description
    });
  }

  // Chat with existing session
  async chatWithSession(sessionId, message, conversationHistory = []) {
    return this.request('/api/chat', {
      message,
      session_id: sessionId,
      conversation_history: conversationHistory
    });
  }

  // Get user sessions
  async getSessions(page = 1, perPage = 20) {
    return this.request(`/api/chat/sessions?page=${page}&per_page=${perPage}`);
  }

  // Create new session
  async createSession(name, description = null) {
    return this.request('/api/chat/sessions', {
      name,
      description
    });
  }

  // Send message to session
  async sendMessage(sessionId, content, conversationHistory = []) {
    return this.request(`/api/chat/sessions/${sessionId}/messages`, {
      content,
      conversation_history: conversationHistory
    });
  }

  // Get session messages
  async getSessionMessages(sessionId, page = 1, perPage = 50) {
    return this.request(`/api/chat/sessions/${sessionId}/messages?page=${page}&per_page=${perPage}`);
  }

  // Update session
  async updateSession(sessionId, name, description = null) {
    return this.request(`/api/chat/sessions/${sessionId}`, {
      name,
      description
    }, 'PUT');
  }

  // Delete session
  async deleteSession(sessionId) {
    return this.request(`/api/chat/sessions/${sessionId}`, null, 'DELETE');
  }

  async request(endpoint, data = null, method = 'POST') {
    const url = `${this.baseURL}${endpoint}`;
    const options = {
      method,
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json'
      }
    };

    if (data) {
      options.body = JSON.stringify(data);
    }

    const response = await fetch(url, options);
    
    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(`HTTP error! status: ${response.status} - ${JSON.stringify(errorData)}`);
    }

    return await response.json();
  }
}

// Usage
const chatAI = new ChatAI('http://localhost:8000', 'your_token');

// Create session and start chatting
const result = await chatAI.createAndChat('Help me with Python', 'Python Help');

// Continue chatting
const response = await chatAI.sendMessage(result.session_id, 'What are decorators?');
```

### **React Hook Example**
```javascript
import { useState, useCallback } from 'react';

export const useChatAI = (token) => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const chatAI = new ChatAI('http://localhost:8000', token);

  const createAndChat = useCallback(async (message, name, description) => {
    setLoading(true);
    setError(null);

    try {
      const result = await chatAI.createAndChat(message, name, description);
      return result;
    } catch (err) {
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  }, [token]);

  const sendMessage = useCallback(async (sessionId, content, history = []) => {
    setLoading(true);
    setError(null);

    try {
      const result = await chatAI.sendMessage(sessionId, content, history);
      return result;
    } catch (err) {
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  }, [token]);

  return { createAndChat, sendMessage, loading, error };
};
```

---

## 📈 **Implementation Status**

### **✅ Fully Implemented**
- **Session Management** - Create, read, update, delete sessions
- **Message Handling** - Send messages, get conversation history
- **Auto-naming** - Smart session names from first message
- **Backward Compatibility** - Existing chat functionality preserved
- **Metadata Tracking** - Token usage and processing time
- **Pagination** - Efficient data loading for large conversations

### **✅ Features Available**
- **Multiple Sessions** - Users can have many chat sessions
- **Session Organization** - Group conversations by topic
- **Message History** - Complete conversation tracking
- **Session Statistics** - Message counts and activity tracking
- **Archive/Restore** - Session lifecycle management
- **Rich Metadata** - Performance and usage tracking

---

## 🚀 **Quick Start**

### **1. Test Session Creation:**
```bash
curl -X POST http://localhost:8000/api/chat/sessions \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "name": "Test Session",
    "description": "Testing the chat API"
  }'
```

### **2. Test Message Sending:**
```bash
curl -X POST http://localhost:8000/api/chat/sessions/123/messages \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "content": "Hello, can you help me with programming?"
  }'
```

### **3. Test Backward Compatible Chat:**
```bash
curl -X POST http://localhost:8000/api/chat \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "message": "Hello, how are you?",
    "session_id": 123
  }'
```

---

## 🎯 **Ready for Production**

The Chat AI system is **production-ready** with:
- ✅ **Complete Session Management** - Full CRUD operations
- ✅ **Message Handling** - Send, receive, and track messages
- ✅ **Backward Compatibility** - Existing functionality preserved
- ✅ **Rich Metadata** - Performance and usage tracking
- ✅ **Error Handling** - Comprehensive error responses
- ✅ **Pagination** - Efficient data loading
- ✅ **Authentication** - Secure API access

**Your Chat AI system is ready for frontend integration!** 🚀
