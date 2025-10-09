# 🧮 **Math AI Tool - Frontend Implementation Guide**

## 📋 **Overview**

Complete guide for implementing the Math AI tool in your frontend application. The Math AI tool supports both text-based and image-based mathematical problem solving with a unified file upload system.

---

## 🔗 **API Endpoints**

### **Base URL:** `http://localhost:8000/api`

| **Endpoint** | **Method** | **Description** |
|--------------|------------|-----------------|
| `/math/solve` | POST | Solve mathematical problems (text or image) |
| `/math/history` | GET | Get user's math problem history |
| `/math/problems` | GET | Get paginated math problems |
| `/math/problems/{id}` | GET | Get specific math problem with solutions |
| `/math/problems/{id}` | DELETE | Delete a math problem |
| `/math/stats` | GET | Get user's math statistics |

---

## 🔐 **Authentication**

All endpoints require authentication using Bearer token:

```javascript
const headers = {
  'Authorization': `Bearer ${token}`,
  'Accept': 'application/json',
  'Content-Type': 'application/json', // For text problems
  'Origin': 'http://localhost:3000'
};
```

---

## 📝 **API Implementation Examples**

### **1. Solve Text-Based Math Problem**

```javascript
async function solveTextMathProblem(problemText, subjectArea = 'arithmetic', difficultyLevel = 'beginner') {
  try {
    const response = await fetch('http://localhost:8000/api/math/solve', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Origin': 'http://localhost:3000'
      },
      body: JSON.stringify({
        problem_text: problemText,
        subject_area: subjectArea,
        difficulty_level: difficultyLevel
      })
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Error solving math problem:', error);
    throw error;
  }
}

// Usage
const result = await solveTextMathProblem('What is 2 + 2?', 'arithmetic', 'beginner');
console.log('Solution:', result.math_solution.final_answer);
```

### **2. Solve Image-Based Math Problem**

```javascript
async function solveImageMathProblem(imageFile, subjectArea = 'maths', difficultyLevel = 'intermediate') {
  try {
    const formData = new FormData();
    formData.append('problem_image', imageFile);
    formData.append('subject_area', subjectArea);
    formData.append('difficulty_level', difficultyLevel);

    const response = await fetch('http://localhost:8000/api/math/solve', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Origin': 'http://localhost:3000'
        // Don't set Content-Type for FormData - let browser set it
      },
      body: formData
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Error solving image math problem:', error);
    throw error;
  }
}

// Usage
const fileInput = document.getElementById('math-image-input');
const imageFile = fileInput.files[0];
const result = await solveImageMathProblem(imageFile, 'maths', 'intermediate');
console.log('Solution:', result.math_solution.final_answer);
```

### **3. Get Math History**

```javascript
async function getMathHistory(perPage = 15, subject = null, difficulty = null) {
  try {
    const params = new URLSearchParams({
      per_page: perPage.toString()
    });
    
    if (subject) params.append('subject', subject);
    if (difficulty) params.append('difficulty', difficulty);

    const response = await fetch(`http://localhost:8000/api/math/history?${params}`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Origin': 'http://localhost:3000'
      }
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const data = await response.json();
    return data; // Array of math problems
  } catch (error) {
    console.error('Error fetching math history:', error);
    throw error;
  }
}

// Usage
const history = await getMathHistory(15, 'maths', 'intermediate');
console.log('Math history:', history);
```

### **4. Get Math Problems (Paginated)**

```javascript
async function getMathProblems(page = 1, perPage = 15, subject = null, difficulty = null) {
  try {
    const params = new URLSearchParams({
      page: page.toString(),
      per_page: perPage.toString()
    });
    
    if (subject) params.append('subject', subject);
    if (difficulty) params.append('difficulty', difficulty);

    const response = await fetch(`http://localhost:8000/api/math/problems?${params}`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Origin': 'http://localhost:3000'
      }
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const data = await response.json();
    return data; // { math_problems: [], pagination: {} }
  } catch (error) {
    console.error('Error fetching math problems:', error);
    throw error;
  }
}

// Usage
const problems = await getMathProblems(1, 15, 'maths');
console.log('Problems:', problems.math_problems);
console.log('Pagination:', problems.pagination);
```

### **5. Get Specific Math Problem**

```javascript
async function getMathProblem(problemId) {
  try {
    const response = await fetch(`http://localhost:8000/api/math/problems/${problemId}`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Origin': 'http://localhost:3000'
      }
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const data = await response.json();
    return data; // { math_problem: {} }
  } catch (error) {
    console.error('Error fetching math problem:', error);
    throw error;
  }
}

// Usage
const problem = await getMathProblem(123);
console.log('Problem:', problem.math_problem);
```

### **6. Get Math Statistics**

```javascript
async function getMathStats() {
  try {
    const response = await fetch('http://localhost:8000/api/math/stats', {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Origin': 'http://localhost:3000'
      }
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Error fetching math stats:', error);
    throw error;
  }
}

// Usage
const stats = await getMathStats();
console.log('Total problems:', stats.total_problems);
console.log('Success rate:', stats.success_rate);
```

### **7. Delete Math Problem**

```javascript
async function deleteMathProblem(problemId) {
  try {
    const response = await fetch(`http://localhost:8000/api/math/problems/${problemId}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Origin': 'http://localhost:3000'
      }
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const data = await response.json();
    return data; // { message: "Math problem deleted successfully" }
  } catch (error) {
    console.error('Error deleting math problem:', error);
    throw error;
  }
}

// Usage
const result = await deleteMathProblem(123);
console.log('Delete result:', result.message);
```

---

## 📊 **Response Formats**

### **Solve Math Problem Response**

```json
{
  "math_problem": {
    "id": 32,
    "problem_text": "What is 2 + 2?",
    "problem_image": null,
    "file_url": "http://localhost:8000/storage/uploads/files/uuid.jpg",
    "subject_area": "arithmetic",
    "difficulty_level": "beginner",
    "created_at": "2025-10-09T08:33:23.000000Z"
  },
  "math_solution": {
    "id": 45,
    "solution_method": "basic addition",
    "step_by_step_solution": "Step 1: Identify the numbers...",
    "final_answer": "4",
    "explanation": "This is a basic addition problem...",
    "verification": "We can verify by counting...",
    "created_at": "2025-10-09T08:33:23.000000Z"
  },
  "ai_result": {
    "id": 78,
    "title": "Mathematical Problem Solution",
    "file_url": "http://localhost:8000/storage/uploads/files/uuid.jpg",
    "created_at": "2025-10-09T08:33:23.000000Z"
  }
}
```

### **Math History Response**

```json
[
  {
    "id": 32,
    "problem_text": "What is 2 + 2?",
    "problem_image": null,
    "subject_area": "arithmetic",
    "difficulty_level": "beginner",
    "problem_type": "text",
    "created_at": "2025-10-09T08:33:23.000000Z"
  },
  {
    "id": 31,
    "problem_text": null,
    "problem_image": "uploads/files/uuid.jpg",
    "subject_area": "maths",
    "difficulty_level": "intermediate",
    "problem_type": "image",
    "created_at": "2025-10-09T08:32:10.000000Z"
  }
]
```

### **Math Problems Response (Paginated)**

```json
{
  "math_problems": [
    {
      "id": 32,
      "problem_text": "What is 2 + 2?",
      "problem_image": null,
      "subject_area": "arithmetic",
      "difficulty_level": "beginner",
      "problem_type": "text",
      "created_at": "2025-10-09T08:33:23.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 5
  }
}
```

### **Math Statistics Response**

```json
{
  "total_problems": 5,
  "problems_by_subject": {
    "arithmetic": 2,
    "maths": 3
  },
  "problems_by_difficulty": {
    "beginner": 2,
    "intermediate": 3
  },
  "recent_activity": [
    {
      "date": "2025-10-09",
      "count": 3
    }
  ],
  "success_rate": 80
}
```

---

## 🎯 **Subject Areas & Difficulty Levels**

### **Subject Areas:**
- `algebra` - Algebraic problems
- `geometry` - Geometric problems
- `calculus` - Calculus problems
- `statistics` - Statistical problems
- `trigonometry` - Trigonometric problems
- `arithmetic` - Basic arithmetic
- `maths` - General mathematics (default for images)

### **Difficulty Levels:**
- `beginner` - Basic problems
- `intermediate` - Moderate problems (default)
- `advanced` - Complex problems

---

## 🖼️ **Image Upload Requirements**

### **Supported Formats:**
- JPEG (.jpg, .jpeg)
- PNG (.png)
- GIF (.gif)
- WebP (.webp)

### **File Size Limits:**
- Maximum: 10MB (10,240 KB)

### **Image Validation:**
```javascript
function validateImageFile(file) {
  const maxSize = 10 * 1024 * 1024; // 10MB
  const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
  
  if (!allowedTypes.includes(file.type)) {
    throw new Error('Invalid file type. Please select an image file.');
  }
  
  if (file.size > maxSize) {
    throw new Error('File too large. Please select an image smaller than 10MB.');
  }
  
  return true;
}
```

---

## 🔧 **React Component Example**

```jsx
import React, { useState } from 'react';

const MathAISolver = () => {
  const [problemText, setProblemText] = useState('');
  const [selectedImage, setSelectedImage] = useState(null);
  const [isImageMode, setIsImageMode] = useState(false);
  const [subjectArea, setSubjectArea] = useState('arithmetic');
  const [difficultyLevel, setDifficultyLevel] = useState('beginner');
  const [solution, setSolution] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const handleImageUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
      validateImageFile(file);
      setSelectedImage(file);
    }
  };

  const handleSolve = async () => {
    setLoading(true);
    setError(null);
    setSolution(null);

    try {
      let result;
      
      if (isImageMode && selectedImage) {
        result = await solveImageMathProblem(selectedImage, subjectArea, difficultyLevel);
      } else if (!isImageMode && problemText.trim()) {
        result = await solveTextMathProblem(problemText, subjectArea, difficultyLevel);
      } else {
        throw new Error('Please provide either text or image input');
      }

      setSolution(result);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="math-ai-solver">
      <h2>Math AI Solver</h2>
      
      {/* Mode Toggle */}
      <div className="mode-toggle">
        <button 
          className={!isImageMode ? 'active' : ''}
          onClick={() => setIsImageMode(false)}
        >
          Text Input
        </button>
        <button 
          className={isImageMode ? 'active' : ''}
          onClick={() => setIsImageMode(true)}
        >
          Image Upload
        </button>
      </div>

      {/* Input Fields */}
      <div className="input-section">
        {!isImageMode ? (
          <textarea
            value={problemText}
            onChange={(e) => setProblemText(e.target.value)}
            placeholder="Enter your math problem here..."
            rows={4}
          />
        ) : (
          <input
            type="file"
            accept="image/*"
            onChange={handleImageUpload}
          />
        )}

        <select 
          value={subjectArea} 
          onChange={(e) => setSubjectArea(e.target.value)}
        >
          <option value="arithmetic">Arithmetic</option>
          <option value="algebra">Algebra</option>
          <option value="geometry">Geometry</option>
          <option value="calculus">Calculus</option>
          <option value="statistics">Statistics</option>
          <option value="trigonometry">Trigonometry</option>
          <option value="maths">General Math</option>
        </select>

        <select 
          value={difficultyLevel} 
          onChange={(e) => setDifficultyLevel(e.target.value)}
        >
          <option value="beginner">Beginner</option>
          <option value="intermediate">Intermediate</option>
          <option value="advanced">Advanced</option>
        </select>
      </div>

      {/* Solve Button */}
      <button 
        onClick={handleSolve} 
        disabled={loading || (!isImageMode && !problemText.trim()) || (isImageMode && !selectedImage)}
      >
        {loading ? 'Solving...' : 'Solve Problem'}
      </button>

      {/* Error Display */}
      {error && (
        <div className="error">
          <p>Error: {error}</p>
        </div>
      )}

      {/* Solution Display */}
      {solution && (
        <div className="solution">
          <h3>Solution</h3>
          <div className="problem-info">
            <p><strong>Subject:</strong> {solution.math_problem.subject_area}</p>
            <p><strong>Difficulty:</strong> {solution.math_problem.difficulty_level}</p>
            <p><strong>Method:</strong> {solution.math_solution.solution_method}</p>
          </div>
          
          <div className="step-by-step">
            <h4>Step-by-Step Solution:</h4>
            <p>{solution.math_solution.step_by_step_solution}</p>
          </div>
          
          <div className="final-answer">
            <h4>Final Answer:</h4>
            <p><strong>{solution.math_solution.final_answer}</strong></p>
          </div>
          
          <div className="explanation">
            <h4>Explanation:</h4>
            <p>{solution.math_solution.explanation}</p>
          </div>
        </div>
      )}
    </div>
  );
};

export default MathAISolver;
```

---

## 🎨 **CSS Styling Example**

```css
.math-ai-solver {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
  font-family: Arial, sans-serif;
}

.mode-toggle {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}

.mode-toggle button {
  padding: 10px 20px;
  border: 2px solid #ddd;
  background: white;
  cursor: pointer;
  border-radius: 5px;
}

.mode-toggle button.active {
  background: #007bff;
  color: white;
  border-color: #007bff;
}

.input-section {
  margin-bottom: 20px;
}

.input-section textarea,
.input-section input,
.input-section select {
  width: 100%;
  padding: 10px;
  margin-bottom: 10px;
  border: 1px solid #ddd;
  border-radius: 5px;
}

button {
  background: #007bff;
  color: white;
  padding: 12px 24px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 16px;
}

button:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.error {
  background: #f8d7da;
  color: #721c24;
  padding: 10px;
  border-radius: 5px;
  margin: 20px 0;
}

.solution {
  background: #f8f9fa;
  padding: 20px;
  border-radius: 5px;
  margin-top: 20px;
}

.solution h3 {
  color: #007bff;
  margin-bottom: 15px;
}

.problem-info {
  background: white;
  padding: 15px;
  border-radius: 5px;
  margin-bottom: 15px;
}

.step-by-step,
.final-answer,
.explanation {
  background: white;
  padding: 15px;
  border-radius: 5px;
  margin-bottom: 15px;
}

.final-answer {
  background: #d4edda;
  border: 1px solid #c3e6cb;
}

.final-answer h4 {
  color: #155724;
  margin-bottom: 10px;
}
```

---

## 🚨 **Error Handling**

### **Common Error Responses:**

```json
// 401 Unauthorized
{
  "message": "Unauthenticated.",
  "error": "Authentication required"
}

// 403 Forbidden (No subscription)
{
  "error": "No active subscription"
}

// 422 Validation Error
{
  "message": "The problem text field is required when problem image is not present.",
  "errors": {
    "problem_text": ["The problem text field is required when problem image is not present."],
    "problem_image": ["The problem image field is required when problem text is not present."]
  }
}

// 500 Server Error
{
  "error": "Unable to solve mathematical problem at this time"
}
```

### **Error Handling Function:**

```javascript
function handleMathAPIError(error, response) {
  if (response?.status === 401) {
    return 'Authentication required. Please log in first.';
  } else if (response?.status === 403) {
    return 'No active subscription. Please upgrade your plan.';
  } else if (response?.status === 422) {
    return 'Invalid input. Please check your problem text or image.';
  } else if (response?.status === 500) {
    return 'Server error. Please try again later.';
  } else if (error.message === 'Failed to fetch') {
    return 'Backend server is not running. Please start the Laravel backend on port 8000.';
  } else {
    return error.message || 'An unexpected error occurred.';
  }
}
```

---

## 🔄 **Loading States & UX**

### **Loading Indicators:**

```jsx
const LoadingSpinner = () => (
  <div className="loading-spinner">
    <div className="spinner"></div>
    <p>Solving your math problem...</p>
  </div>
);

// CSS for spinner
.loading-spinner {
  text-align: center;
  padding: 20px;
}

.spinner {
  border: 4px solid #f3f3f3;
  border-top: 4px solid #007bff;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin: 0 auto 10px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
```

---

## 📱 **Mobile Responsiveness**

```css
@media (max-width: 768px) {
  .math-ai-solver {
    padding: 10px;
  }
  
  .mode-toggle {
    flex-direction: column;
  }
  
  .mode-toggle button {
    width: 100%;
  }
  
  .input-section textarea {
    min-height: 120px;
  }
}
```

---

## 🎯 **Best Practices**

### **1. Input Validation:**
- Always validate file types and sizes before upload
- Sanitize text input to prevent XSS
- Check for empty inputs before submission

### **2. Error Handling:**
- Provide specific error messages to users
- Handle network errors gracefully
- Show loading states during API calls

### **3. Performance:**
- Implement debouncing for text input
- Use proper loading states
- Cache frequently accessed data

### **4. Accessibility:**
- Add proper ARIA labels
- Ensure keyboard navigation works
- Provide alt text for images

### **5. Security:**
- Never expose API tokens in client-side code
- Validate all inputs on both client and server
- Use HTTPS in production

---

## 🚀 **Quick Start Checklist**

- [ ] Set up authentication token management
- [ ] Implement text-based math problem solving
- [ ] Add image upload functionality
- [ ] Create math history display
- [ ] Add math statistics dashboard
- [ ] Implement error handling
- [ ] Add loading states
- [ ] Test all endpoints
- [ ] Add responsive design
- [ ] Implement proper validation

---

## 📞 **Support**

For issues or questions about the Math AI tool implementation:

1. Check the API response for specific error messages
2. Verify authentication token is valid
3. Ensure backend server is running on port 8000
4. Check network connectivity
5. Review browser console for JavaScript errors

The Math AI tool is now fully integrated with the universal file upload system and ready for production use! 🎉
