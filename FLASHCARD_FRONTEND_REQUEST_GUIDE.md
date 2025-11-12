# Flashcard Frontend Request Guide

## ✅ Correct Request Format

**Endpoint:** `POST /api/flashcards/generate`

**Headers:**
```http
Content-Type: application/json
Authorization: Bearer {your-token}
Accept: application/json
```

**Request Body (JSON):**
```json
{
  "input": "Your content here",
  "input_type": "text",
  "count": 5,
  "difficulty": "intermediate",
  "style": "mixed",
  "model": "deepseek-chat"
}
```

---

## ❌ Common Frontend Mistakes

### 1. **Wrong Content-Type Header**
```javascript
// ❌ WRONG - Missing Content-Type
fetch('/api/flashcards/generate', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify(data)
});

// ✅ CORRECT
fetch('/api/flashcards/generate', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  },
  body: JSON.stringify(data)
});
```

### 2. **Sending FormData Instead of JSON**
```javascript
// ❌ WRONG - Using FormData
const formData = new FormData();
formData.append('input', 'content');
formData.append('count', 5);

// ✅ CORRECT - Use JSON
const data = {
  input: 'content',
  count: 5
};
```

### 3. **Missing Required Fields**
```javascript
// ❌ WRONG - Missing 'input' field
{
  "content": "text here",  // Wrong field name
  "count": 5
}

// ✅ CORRECT
{
  "input": "text here",  // Must be 'input'
  "count": 5
}
```

### 4. **Invalid Field Values**
```javascript
// ❌ WRONG - Invalid values
{
  "input": "content",
  "count": 50,  // Max is 40
  "difficulty": "hard",  // Must be: beginner, intermediate, advanced
  "style": "custom"  // Must be: definition, application, analysis, comparison, mixed
}

// ✅ CORRECT
{
  "input": "content",
  "count": 5,  // 1-40
  "difficulty": "intermediate",  // beginner | intermediate | advanced
  "style": "mixed"  // definition | application | analysis | comparison | mixed
}
```

### 5. **Not Trimming Input**
```javascript
// ❌ WRONG - May have leading/trailing whitespace
const input = "  content  ";

// ✅ CORRECT - Backend trims, but frontend should too
const input = userInput.trim();
```

---

## 🔍 Debugging Frontend Requests

### Check Request in Browser DevTools

1. **Network Tab:**
   - Check the request URL
   - Check request headers (especially `Content-Type`)
   - Check request payload
   - Check response status and body

2. **Console Logs:**
```javascript
const requestData = {
  input: 'Your content',
  input_type: 'text',
  count: 5,
  difficulty: 'intermediate',
  style: 'mixed'
};

console.log('Request Data:', JSON.stringify(requestData, null, 2));

fetch('/api/flashcards/generate', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  },
  body: JSON.stringify(requestData)
})
.then(response => {
  console.log('Response Status:', response.status);
  console.log('Response Headers:', response.headers);
  return response.json();
})
.then(data => {
  console.log('Response Data:', data);
})
.catch(error => {
  console.error('Request Error:', error);
});
```

---

## 📋 Complete Frontend Example

```javascript
async function generateFlashcards(content, options = {}) {
  const token = localStorage.getItem('auth_token');
  
  const requestData = {
    input: content.trim(),  // Required: content text
    input_type: options.input_type || 'text',  // text | url | youtube | file
    count: options.count || 5,  // 1-40
    difficulty: options.difficulty || 'intermediate',  // beginner | intermediate | advanced
    style: options.style || 'mixed',  // definition | application | analysis | comparison | mixed
    model: options.model || 'deepseek-chat'  // Optional
  };
  
  // Validate before sending
  if (!requestData.input || requestData.input.length < 3) {
    throw new Error('Input must be at least 3 characters');
  }
  
  if (requestData.count < 1 || requestData.count > 40) {
    throw new Error('Count must be between 1 and 40');
  }
  
  try {
    const response = await fetch('http://your-domain.com/api/flashcards/generate', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      },
      body: JSON.stringify(requestData)
    });
    
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.error || `HTTP ${response.status}`);
    }
    
    const data = await response.json();
    
    if (data.success) {
      return {
        job_id: data.job_id,
        status: data.status,
        poll_url: data.poll_url,
        result_url: data.result_url
      };
    } else {
      throw new Error(data.error || 'Unknown error');
    }
    
  } catch (error) {
    console.error('Flashcard generation error:', error);
    throw error;
  }
}

// Usage
generateFlashcards('Java is a programming language', {
  count: 5,
  difficulty: 'intermediate',
  style: 'mixed'
})
.then(result => {
  console.log('Job started:', result.job_id);
  // Poll for status using result.poll_url
})
.catch(error => {
  console.error('Failed to generate flashcards:', error);
});
```

---

## 🐛 Troubleshooting

### Error: "Failed to parse flashcards from AI response"

**Possible Causes:**
1. Content is too short (minimum 5 words)
2. AI Manager returned invalid response
3. Request format is incorrect

**Solution:**
- Check backend logs for detailed error
- Verify request format matches examples above
- Ensure content has at least 5 words
- Try with simpler content first

### Error: "Validation failed"

**Check:**
- All required fields are present
- Field values are within allowed ranges
- Field names match exactly (case-sensitive)
- Content-Type header is `application/json`

### Error: "Unauthorized"

**Check:**
- Token is valid and not expired
- Authorization header format: `Bearer {token}`
- Token has proper permissions

---

## 📝 Request Validation Rules

| Field | Type | Required | Valid Values |
|-------|------|----------|--------------|
| `input` | string | Yes* | Min 3 chars, max 50000 words |
| `file_id` | string | Yes* | Valid UUID from file_uploads table |
| `input_type` | string | No | `text`, `url`, `youtube`, `file` |
| `count` | integer | No | 1-40 (default: 5) |
| `difficulty` | string | No | `beginner`, `intermediate`, `advanced` (default: `intermediate`) |
| `style` | string | No | `definition`, `application`, `analysis`, `comparison`, `mixed` (default: `mixed`) |
| `model` | string | No | AI model name (default: `deepseek-chat`) |

*Either `input` OR `file_id` is required, not both.

