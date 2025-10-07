# 🃏 Flashcard Management Guide

## 🎯 **Overview**

This guide covers how to fetch and delete saved flashcards using the Laravel backend API. The system stores flashcards in two related tables: `flashcard_sets` (collections) and `flashcards` (individual cards).

## 📊 **Database Structure**

### **Flashcard Sets Table (`flashcard_sets`)**
```sql
- id (Primary Key)
- user_id (Foreign Key to users)
- title (Set title)
- description (Set description)
- input_type (text, url, youtube, file)
- input_content (Original input)
- difficulty (beginner, intermediate, advanced)
- style (definition, application, analysis, comparison, mixed)
- total_cards (Number of cards in set)
- source_metadata (JSON - Source information)
- is_public (Boolean - Public visibility)
- created_at, updated_at
```

### **Flashcards Table (`flashcards`)**
```sql
- id (Primary Key)
- flashcard_set_id (Foreign Key to flashcard_sets)
- question (Card question)
- answer (Card answer)
- order_index (Order within set)
- created_at, updated_at
```

## 🔍 **Fetching Saved Flashcards**

### **1. Get All Flashcard Sets**
```http
GET /api/flashcards
Authorization: Bearer {token}
```

**Query Parameters:**
- `per_page` - Results per page (default: 15)
- `page` - Page number (default: 1)
- `search` - Search in title and description

**Example Request:**
```http
GET /api/flashcards?per_page=10&search=machine learning
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

**Response:**
```json
{
  "flashcard_sets": [
    {
      "id": 1,
      "user_id": 1,
      "title": "Machine Learning Algorithms",
      "description": "Flashcards generated from text input",
      "input_type": "text",
      "input_content": "Machine learning algorithms...",
      "difficulty": "intermediate",
      "style": "mixed",
      "total_cards": 10,
      "source_metadata": {
        "source_type": "text",
        "word_count": 1250
      },
      "is_public": false,
      "created_at": "2025-01-06T10:30:00Z",
      "updated_at": "2025-01-06T10:30:00Z",
      "flashcards": [
        {
          "id": 1,
          "flashcard_set_id": 1,
          "question": "What is supervised learning?",
          "answer": "Supervised learning uses labeled data to train models.",
          "order_index": 0,
          "created_at": "2025-01-06T10:30:00Z",
          "updated_at": "2025-01-06T10:30:00Z"
        },
        {
          "id": 2,
          "flashcard_set_id": 1,
          "question": "What is unsupervised learning?",
          "answer": "Unsupervised learning finds patterns in data without labels.",
          "order_index": 1,
          "created_at": "2025-01-06T10:30:00Z",
          "updated_at": "2025-01-06T10:30:00Z"
        }
      ]
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "total": 45
  }
}
```

### **2. Get Specific Flashcard Set**
```http
GET /api/flashcards/{id}
Authorization: Bearer {token}
```

**Example Request:**
```http
GET /api/flashcards/1
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

**Response:**
```json
{
  "flashcard_set": {
    "id": 1,
    "user_id": 1,
    "title": "Machine Learning Algorithms",
    "description": "Flashcards generated from text input",
    "input_type": "text",
    "input_content": "Machine learning algorithms...",
    "difficulty": "intermediate",
    "style": "mixed",
    "total_cards": 10,
    "source_metadata": {
      "source_type": "text",
      "word_count": 1250
    },
    "is_public": false,
    "created_at": "2025-01-06T10:30:00Z",
    "updated_at": "2025-01-06T10:30:00Z",
    "flashcards": [
      {
        "id": 1,
        "flashcard_set_id": 1,
        "question": "What is supervised learning?",
        "answer": "Supervised learning uses labeled data to train models.",
        "order_index": 0,
        "created_at": "2025-01-06T10:30:00Z",
        "updated_at": "2025-01-06T10:30:00Z"
      }
    ]
  }
}
```

### **3. Get Public Flashcard Sets**
```http
GET /api/flashcards/public
Authorization: Bearer {token}
```

**Query Parameters:**
- `per_page` - Results per page (default: 15)
- `search` - Search in title and description

**Example Request:**
```http
GET /api/flashcards/public?search=biology&per_page=5
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

**Response:**
```json
{
  "flashcard_sets": [
    {
      "id": 5,
      "user_id": 2,
      "title": "Biology Basics",
      "description": "Basic biology concepts",
      "input_type": "text",
      "difficulty": "beginner",
      "style": "definition",
      "total_cards": 8,
      "is_public": true,
      "created_at": "2025-01-05T14:20:00Z",
      "user": {
        "id": 2,
        "name": "John Doe"
      },
      "flashcards": [
        {
          "id": 15,
          "question": "What is photosynthesis?",
          "answer": "The process by which plants convert light into energy.",
          "order_index": 0
        }
      ]
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 2,
    "per_page": 15,
    "total": 25
  }
}
```

## 🗑️ **Deleting Saved Flashcards**

### **1. Delete Flashcard Set**
```http
DELETE /api/flashcards/{id}
Authorization: Bearer {token}
```

**Example Request:**
```http
DELETE /api/flashcards/1
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

**Response:**
```json
{
  "message": "Flashcard set deleted successfully"
}
```

**Error Response:**
```json
{
  "message": "No query results for model [App\\Models\\FlashcardSet] 1"
}
```

### **2. Automatic Card Deletion**
When a flashcard set is deleted:
- All associated flashcards are automatically deleted (cascade delete)
- The deletion is permanent and cannot be undone
- User can only delete their own flashcard sets

## 🔧 **Additional Operations**

### **1. Update Flashcard Set**
```http
PUT /api/flashcards/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Updated Title",
  "description": "Updated description",
  "is_public": true
}
```

**Response:**
```json
{
  "message": "Flashcard set updated successfully",
  "flashcard_set": {
    "id": 1,
    "title": "Updated Title",
    "description": "Updated description",
    "is_public": true,
    "updated_at": "2025-01-06T11:00:00Z"
  }
}
```

### **2. Generate New Flashcards**
```http
POST /api/flashcards/generate
Authorization: Bearer {token}
Content-Type: application/json

{
  "input": "Your content here",
  "input_type": "text",
  "count": 5,
  "difficulty": "intermediate",
  "style": "mixed"
}
```

## 📝 **Frontend Integration Examples**

### **JavaScript/Fetch API**
```javascript
// Fetch all flashcard sets
async function fetchFlashcardSets() {
  const response = await fetch('/api/flashcards', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  const data = await response.json();
  return data.flashcard_sets;
}

// Fetch specific flashcard set
async function fetchFlashcardSet(setId) {
  const response = await fetch(`/api/flashcards/${setId}`, {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  const data = await response.json();
  return data.flashcard_set;
}

// Delete flashcard set
async function deleteFlashcardSet(setId) {
  const response = await fetch(`/api/flashcards/${setId}`, {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  const data = await response.json();
  return data;
}

// Update flashcard set
async function updateFlashcardSet(setId, updates) {
  const response = await fetch(`/api/flashcards/${setId}`, {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(updates)
  });
  
  const data = await response.json();
  return data;
}

// Search flashcard sets
async function searchFlashcardSets(searchTerm) {
  const response = await fetch(`/api/flashcards?search=${encodeURIComponent(searchTerm)}`, {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  const data = await response.json();
  return data.flashcard_sets;
}
```

### **Axios Example**
```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Authorization': `Bearer ${token}`
  }
});

// Fetch flashcard sets with pagination
const fetchFlashcardSets = async (page = 1, search = '') => {
  const response = await api.get('/flashcards', {
    params: { 
      page, 
      search,
      per_page: 15 
    }
  });
  return response.data;
};

// Delete flashcard set
const deleteFlashcardSet = async (setId) => {
  const response = await api.delete(`/flashcards/${setId}`);
  return response.data;
};

// Update flashcard set
const updateFlashcardSet = async (setId, updates) => {
  const response = await api.put(`/flashcards/${setId}`, updates);
  return response.data;
};
```

### **React Hook Example**
```javascript
import { useState, useEffect } from 'react';

const useFlashcardSets = () => {
  const [flashcardSets, setFlashcardSets] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const fetchSets = async (search = '') => {
    try {
      setLoading(true);
      const response = await fetch(`/api/flashcards?search=${encodeURIComponent(search)}`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });
      
      if (!response.ok) throw new Error('Failed to fetch');
      
      const data = await response.json();
      setFlashcardSets(data.flashcard_sets);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const deleteSet = async (setId) => {
    try {
      const response = await fetch(`/api/flashcards/${setId}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });
      
      if (!response.ok) throw new Error('Failed to delete');
      
      setFlashcardSets(prev => prev.filter(set => set.id !== setId));
    } catch (err) {
      setError(err.message);
    }
  };

  useEffect(() => {
    fetchSets();
  }, []);

  return { flashcardSets, loading, error, fetchSets, deleteSet };
};
```

## 🛡️ **Security & Permissions**

### **User Isolation**
- Users can only access their own flashcard sets
- All queries are automatically filtered by `user_id`
- Public sets are visible to all authenticated users

### **Authentication Required**
- All endpoints require Bearer token authentication
- Invalid or expired tokens return 401 Unauthorized

### **Rate Limiting**
- API calls are subject to usage limits based on subscription plan
- Rate limiting is enforced by the `check.usage` middleware

## 📊 **Response Data Structure**

### **Flashcard Set Object**
```typescript
interface FlashcardSet {
  id: number;
  user_id: number;
  title: string;
  description: string | null;
  input_type: 'text' | 'url' | 'youtube' | 'file';
  input_content: string;
  difficulty: 'beginner' | 'intermediate' | 'advanced';
  style: 'definition' | 'application' | 'analysis' | 'comparison' | 'mixed';
  total_cards: number;
  source_metadata: object;
  is_public: boolean;
  created_at: string;
  updated_at: string;
  flashcards: Flashcard[];
}
```

### **Flashcard Object**
```typescript
interface Flashcard {
  id: number;
  flashcard_set_id: number;
  question: string;
  answer: string;
  order_index: number;
  created_at: string;
  updated_at: string;
}
```

## 🚨 **Error Handling**

### **Common Error Responses**
```json
// 401 Unauthorized
{
  "message": "Unauthenticated."
}

// 404 Not Found
{
  "message": "No query results for model [App\\Models\\FlashcardSet] 1"
}

// 422 Validation Error
{
  "message": "The given data was invalid.",
  "errors": {
    "title": ["The title field is required."]
  }
}

// 500 Server Error
{
  "error": "AI service is currently unavailable. Please try again later."
}
```

## 🔄 **Best Practices**

1. **Pagination**: Always use pagination for large result sets
2. **Search**: Implement search functionality for better UX
3. **Error Handling**: Always handle API errors gracefully
4. **Loading States**: Show loading indicators during API calls
5. **Confirmation**: Ask for confirmation before deleting sets
6. **Caching**: Consider caching frequently accessed sets

## 📈 **Performance Considerations**

- Database queries are optimized with proper indexing
- Pagination prevents memory issues with large datasets
- Eager loading reduces N+1 query problems
- Cascade deletes are handled efficiently

## 🎯 **Use Cases**

### **Study Management**
- Organize flashcards by subject/topic
- Set difficulty levels for progressive learning
- Make sets public for sharing with others

### **Content Types**
- **Text**: Direct text input
- **URL**: Web page content
- **YouTube**: Video transcripts
- **File**: Document uploads (PDF, DOC, etc.)

### **Learning Styles**
- **Definition**: Term and definition pairs
- **Application**: Problem and solution pairs
- **Analysis**: Question and explanation pairs
- **Comparison**: Contrasting concepts
- **Mixed**: Combination of all styles

---

**Note**: All API endpoints require authentication and are subject to usage limits based on the user's subscription plan.

