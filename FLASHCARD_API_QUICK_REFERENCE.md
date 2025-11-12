# Flashcard API - Quick Reference

**Base URL:** `http://your-domain.com/api`  
**Authentication:** `Authorization: Bearer {token}`

---

## 📝 Generate Flashcards

**POST** `/api/flashcards/generate`

**Request:**
```json
{
  "input": "Your content here",
  "input_type": "text",  // "text" | "url" | "youtube" | "file"
  "file_id": "uuid",     // Required if input_type is "file"
  "count": 5,            // 1-40, default: 5
  "difficulty": "intermediate",  // "beginner" | "intermediate" | "advanced"
  "style": "mixed",      // "definition" | "application" | "analysis" | "comparison" | "mixed"
  "model": "deepseek-chat"  // Optional
}
```

**Response (202 Accepted):**
```json
{
  "success": true,
  "message": "Flashcard generation job started",
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "pending",
  "poll_url": "/api/status/flashcards/text?job_id=550e8400-e29b-41d4-a716-446655440000",
  "result_url": "/api/result/flashcards/text?job_id=550e8400-e29b-41d4-a716-446655440000",
  "data": {
    "job_id": "550e8400-e29b-41d4-a716-446655440000",
    "status": "pending",
    "flashcards": []
  }
}
```

---

## 📊 Check Job Status

**GET** `/api/status/flashcards/{type}?job_id={jobId}`

**Types:** `text` (for text/url/youtube) or `file` (for file uploads)

**Response (Processing):**
```json
{
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "tool_type": "flashcards",
  "input_type": "text",
  "status": "processing",  // "pending" | "processing" | "completed" | "failed"
  "progress": 60.0,        // 0-100
  "stage": "generating_flashcards",
  "error": null,
  "created_at": "2024-01-01T12:00:00Z",
  "updated_at": "2024-01-01T12:01:00Z"
}
```

**Response (Failed):**
```json
{
  "success": false,
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "tool_type": "flashcards",
  "input_type": "text",
  "status": "failed",
  "progress": 60,
  "stage": "failed",
  "error": "Error message here",
  "created_at": "2024-01-01T12:00:00Z",
  "updated_at": "2024-01-01T12:01:00Z"
}
```

**Stages:**
- `initializing` → `analyzing_content` → `validating_input` → `extracting_content`
- `validating_content` → `generating_flashcards` → `saving_flashcards` → `finalizing`

---

## ✅ Get Result

**GET** `/api/result/flashcards/{type}?job_id={jobId}`

**Types:** `text` (for text/url/youtube) or `file` (for file uploads)

**Response (Completed):**
```json
{
  "success": true,
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "tool_type": "flashcards",
  "input_type": "text",
  "data": {
    "flashcards": [
      {
        "front": "What is Java?",
        "back": "Java is a high-level programming language...",
        "question": "What is Java?",
        "answer": "Java is a high-level programming language..."
      }
    ],
    "flashcard_set": {
      "id": 123,
      "title": "Java Programming",
      "description": "Flashcards about Java",
      "total_cards": 5,
      "created_at": "2024-01-01T12:00:00Z"
    },
    "ai_result": {
      "id": 456,
      "title": "Java Programming",
      "file_url": null,
      "created_at": "2024-01-01T12:00:00Z"
    }
  }
}
```

**Response (Not Completed - 202 Accepted):**
```json
{
  "error": "Job not completed",
  "status": "processing",
  "progress": 60.0
}
```

---

## 📋 List Flashcard Sets

**GET** `/api/flashcards?page=1&per_page=15`

**Response:**
```json
{
  "data": [
    {
      "id": 123,
      "title": "Java Programming",
      "description": "Flashcards about Java",
      "total_cards": 5,
      "difficulty": "intermediate",
      "style": "mixed",
      "input_type": "text",
      "is_public": false,
      "created_at": "2024-01-01T12:00:00Z",
      "updated_at": "2024-01-01T12:00:00Z"
    }
  ],
  "current_page": 1,
  "per_page": 15,
  "total": 1
}
```

---

## 🔍 Get Flashcard Set

**GET** `/api/flashcards/{id}`

**Response:**
```json
{
  "success": true,
  "id": 123,
  "title": "Java Programming",
  "description": "Flashcards about Java",
  "total_cards": 5,
  "difficulty": "intermediate",
  "style": "mixed",
  "input_type": "text",
  "is_public": false,
  "flashcards": [
    {
      "id": 1,
      "question": "What is Java?",
      "answer": "Java is a high-level programming language...",
      "front": "What is Java?",
      "back": "Java is a high-level programming language...",
      "order_index": 0
    }
  ],
  "created_at": "2024-01-01T12:00:00Z",
  "updated_at": "2024-01-01T12:00:00Z"
}
```

---

## ✏️ Update Flashcard Set

**PUT** `/api/flashcards/{id}`

**Request:**
```json
{
  "title": "Updated Title",
  "description": "Updated description",
  "is_public": true
}
```

**Response:**
```json
{
  "success": true,
  "message": "Flashcard set updated successfully",
  "data": {
    "id": 123,
    "title": "Updated Title",
    "description": "Updated description",
    "is_public": true
  }
}
```

---

## 🗑️ Delete Flashcard Set

**DELETE** `/api/flashcards/{id}`

**Response:**
```json
{
  "success": true,
  "message": "Flashcard set deleted successfully"
}
```

---

## 📚 Public Flashcard Sets

**GET** `/api/flashcards/public?page=1&per_page=15`

**Response:** Same format as List Flashcard Sets

---

## 🔄 Frontend Workflow

```javascript
// 1. Generate flashcards
const generateResponse = await fetch('/api/flashcards/generate', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    input: 'Your content here',
    input_type: 'text',
    count: 5,
    difficulty: 'intermediate',
    style: 'mixed'
  })
});

const { job_id, poll_url, result_url } = await generateResponse.json();

// 2. Poll for status (every 2-3 seconds)
const pollStatus = async () => {
  const statusResponse = await fetch(poll_url, {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  const status = await statusResponse.json();
  
  if (status.status === 'completed') {
    // 3. Get result
    const resultResponse = await fetch(result_url, {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const result = await resultResponse.json();
    
    // 4. Use flashcards
    const flashcards = result.data.flashcards;
    const flashcardSetId = result.data.flashcard_set.id;
    
    // Display flashcards
    flashcards.forEach(card => {
      console.log(`Q: ${card.front || card.question}`);
      console.log(`A: ${card.back || card.answer}`);
    });
    
  } else if (status.status === 'failed') {
    console.error('Error:', status.error);
  } else if (status.status === 'processing') {
    // Continue polling
    setTimeout(pollStatus, 2000);
  }
};

pollStatus();
```

---

## ⚠️ Error Codes

| Status | Description |
|--------|-------------|
| `400` | Bad Request - Invalid parameters |
| `401` | Unauthorized - Missing/invalid token |
| `404` | Not Found - Job or flashcard set not found |
| `422` | Validation Error - Check error details |
| `202` | Accepted - Job still processing |
| `500` | Server Error - Retry later |

---

## 📌 Quick Notes

- **All generation is async** - Returns `job_id`, poll for status
- **Poll interval:** 2-3 seconds
- **Flashcard format:** Each card has both `front`/`back` and `question`/`answer`
- **Count range:** 1-40 flashcards
- **Content minimum:** 5 words
- **Status values:** `pending` → `processing` → `completed` or `failed`

