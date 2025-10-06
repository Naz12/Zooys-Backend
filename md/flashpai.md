# 🎴 AI Flashcard API Documentation

## Overview
The AI Flashcard API generates educational flashcards from various content sources using artificial intelligence. It supports text input, URLs, YouTube videos, and file uploads to create personalized study materials.

---

## 🚀 Quick Start

### Base URL
```
https://your-domain.com/api
```

### Authentication
All requests require Bearer token authentication:
```
Authorization: Bearer {your_token}
```

---

## 📋 API Endpoints

### Generate Flashcards
**POST** `/api/flashcards/generate`

Generates educational flashcards from various content sources.

#### Request Body
```json
{
    "input": "string (required)",
    "input_type": "string (optional)",
    "count": "integer (optional)",
    "difficulty": "string (optional)",
    "style": "string (optional)"
}
```

#### Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `input` | string | ✅ | - | Content source (text, URL, YouTube link, or file path) |
| `input_type` | string | ❌ | auto-detected | Type of input: `text`, `url`, `youtube`, `file` |
| `count` | integer | ❌ | 5 | Number of flashcards to generate (1-40) |
| `difficulty` | string | ❌ | intermediate | Difficulty level: `beginner`, `intermediate`, `advanced` |
| `style` | string | ❌ | mixed | Question style: `definition`, `application`, `analysis`, `comparison`, `mixed` |

#### Input Types Supported

| Type | Description | Example |
|------|-------------|---------|
| `text` | Direct text input | "Machine learning algorithms" |
| `url` | Web page content | "https://en.wikipedia.org/wiki/Machine_learning" |
| `youtube` | YouTube video | "https://www.youtube.com/watch?v=dQw4w9WgXcQ" |
| `file` | File path | "/uploads/document.pdf" |

---

## 📝 Request Examples

### 1. Text Input
```json
{
    "input": "Photosynthesis is the process by which plants convert light energy into chemical energy using chlorophyll and other pigments.",
    "input_type": "text",
    "count": 8,
    "difficulty": "intermediate"
}
```

### 2. YouTube Video
```json
{
    "input": "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
    "input_type": "youtube",
    "count": 12,
    "difficulty": "beginner"
}
```

### 3. Web Page
```json
{
    "input": "https://en.wikipedia.org/wiki/Machine_learning",
    "input_type": "url",
    "count": 15,
    "style": "mixed"
}
```

### 4. File Upload
```json
{
    "input": "/uploads/biology_textbook.pdf",
    "input_type": "file",
    "count": 20,
    "difficulty": "advanced"
}
```

---

## 📤 Response Format

### Success Response
```json
{
    "flashcards": [
        {
            "question": "What is the main difference between supervised and unsupervised learning?",
            "answer": "Supervised learning uses labeled data to train models, while unsupervised learning finds patterns in unlabeled data."
        },
        {
            "question": "How does the k-means clustering algorithm work?",
            "answer": "K-means clustering groups data points into k clusters by minimizing the sum of squared distances between points and their cluster centroids."
        }
    ],
    "flashcard_set": {
        "id": 1,
        "title": "Machine Learning Algorithms",
        "description": "Flashcards generated from text input",
        "total_cards": 10,
        "created_at": "2024-01-15T10:30:00Z"
    },
    "metadata": {
        "total_generated": 10,
        "requested_count": 10,
        "input_type": "text",
        "source_metadata": {
            "source_type": "text",
            "word_count": 1250,
            "character_count": 7500
        }
    }
}
```

### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `flashcards` | array | Array of flashcard objects |
| `flashcards[].question` | string | The question/prompt |
| `flashcards[].answer` | string | The detailed answer |
| `metadata.total_generated` | integer | Number of flashcards actually generated |
| `metadata.requested_count` | integer | Number of flashcards requested |
| `metadata.input_type` | string | Type of input processed |
| `metadata.source_metadata` | object | Additional metadata about the source content |

---

## ❌ Error Responses

### 400 Bad Request
```json
{
    "error": "Content is too short. Please provide more detailed content (at least 50 words)."
}
```

### 400 Invalid Input
```json
{
    "error": "Invalid URL format"
}
```

### 400 Unsupported File Type
```json
{
    "error": "Unsupported file type: {extension}"
}
```

### 503 Service Unavailable
```json
{
    "error": "AI service is currently unavailable. Please try again later."
}
```

---

## 🎯 Frontend Integration Examples

### JavaScript/React Example
```javascript
const generateFlashcards = async (input, options = {}) => {
    try {
        const response = await fetch('/api/flashcards/generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({
                input: input,
                input_type: options.inputType || 'text',
                count: options.count || 5,
                difficulty: options.difficulty || 'intermediate',
                style: options.style || 'mixed'
            })
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error);
        }

        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Flashcard generation failed:', error.message);
        throw error;
    }
};

// Usage
const flashcards = await generateFlashcards(
    "Machine learning algorithms",
    {
        count: 10,
        difficulty: "intermediate",
        style: "mixed"
    }
);
```

### Vue.js Example
```javascript
// In your Vue component
methods: {
    async generateFlashcards() {
        this.loading = true;
        try {
            const response = await this.$http.post('/api/flashcards/generate', {
                input: this.inputText,
                input_type: this.inputType,
                count: this.flashcardCount,
                difficulty: this.difficulty,
                style: this.questionStyle
            });
            
            this.flashcards = response.data.flashcards;
            this.metadata = response.data.metadata;
        } catch (error) {
            this.error = error.response?.data?.error || 'Failed to generate flashcards';
        } finally {
            this.loading = false;
        }
    }
}
```

### Angular Example
```typescript
// In your Angular service
@Injectable()
export class FlashcardService {
    constructor(private http: HttpClient) {}

    generateFlashcards(input: string, options: FlashcardOptions): Observable<FlashcardResponse> {
        return this.http.post<FlashcardResponse>('/api/flashcards/generate', {
            input,
            input_type: options.inputType || 'text',
            count: options.count || 5,
            difficulty: options.difficulty || 'intermediate',
            style: options.style || 'mixed'
        });
    }
}

// Usage in component
this.flashcardService.generateFlashcards(input, options).subscribe({
    next: (response) => {
        this.flashcards = response.flashcards;
        this.metadata = response.metadata;
    },
    error: (error) => {
        this.error = error.error?.error || 'Failed to generate flashcards';
    }
});
```

---

## 🎨 UI Component Examples

### Flashcard Display Component
```jsx
const FlashcardComponent = ({ flashcard, isFlipped, onFlip }) => {
    return (
        <div className="flashcard" onClick={onFlip}>
            <div className={`flashcard-inner ${isFlipped ? 'flipped' : ''}`}>
                <div className="flashcard-front">
                    <h3>Question</h3>
                    <p>{flashcard.question}</p>
                </div>
                <div className="flashcard-back">
                    <h3>Answer</h3>
                    <p>{flashcard.answer}</p>
                </div>
            </div>
        </div>
    );
};
```

### Flashcard Generator Form
```jsx
const FlashcardGenerator = () => {
    const [input, setInput] = useState('');
    const [inputType, setInputType] = useState('text');
    const [count, setCount] = useState(5);
    const [difficulty, setDifficulty] = useState('intermediate');
    const [style, setStyle] = useState('mixed');
    const [flashcards, setFlashcards] = useState([]);
    const [loading, setLoading] = useState(false);

    const handleGenerate = async () => {
        setLoading(true);
        try {
            const response = await generateFlashcards(input, {
                inputType,
                count,
                difficulty,
                style
            });
            setFlashcards(response.flashcards);
        } catch (error) {
            alert(error.message);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="flashcard-generator">
            <div className="form-group">
                <label>Content Input:</label>
                <textarea
                    value={input}
                    onChange={(e) => setInput(e.target.value)}
                    placeholder="Enter text, URL, or YouTube link..."
                    rows={4}
                />
            </div>
            
            <div className="form-group">
                <label>Input Type:</label>
                <select value={inputType} onChange={(e) => setInputType(e.target.value)}>
                    <option value="text">Text</option>
                    <option value="url">Web Page</option>
                    <option value="youtube">YouTube Video</option>
                    <option value="file">File</option>
                </select>
            </div>

            <div className="form-group">
                <label>Number of Cards:</label>
                <input
                    type="number"
                    value={count}
                    onChange={(e) => setCount(parseInt(e.target.value))}
                    min="1"
                    max="40"
                />
            </div>

            <div className="form-group">
                <label>Difficulty:</label>
                <select value={difficulty} onChange={(e) => setDifficulty(e.target.value)}>
                    <option value="beginner">Beginner</option>
                    <option value="intermediate">Intermediate</option>
                    <option value="advanced">Advanced</option>
                </select>
            </div>

            <div className="form-group">
                <label>Question Style:</label>
                <select value={style} onChange={(e) => setStyle(e.target.value)}>
                    <option value="mixed">Mixed</option>
                    <option value="definition">Definition</option>
                    <option value="application">Application</option>
                    <option value="analysis">Analysis</option>
                    <option value="comparison">Comparison</option>
                </select>
            </div>

            <button onClick={handleGenerate} disabled={loading}>
                {loading ? 'Generating...' : 'Generate Flashcards'}
            </button>

            {flashcards.length > 0 && (
                <div className="flashcards-container">
                    {flashcards.map((card, index) => (
                        <FlashcardComponent key={index} flashcard={card} />
                    ))}
                </div>
            )}
        </div>
    );
};
```

---

## 🎨 CSS Styling

### Basic Flashcard Styles
```css
.flashcard {
    width: 300px;
    height: 200px;
    perspective: 1000px;
    margin: 20px;
}

.flashcard-inner {
    position: relative;
    width: 100%;
    height: 100%;
    text-align: center;
    transition: transform 0.6s;
    transform-style: preserve-3d;
}

.flashcard.flipped .flashcard-inner {
    transform: rotateY(180deg);
}

.flashcard-front, .flashcard-back {
    position: absolute;
    width: 100%;
    height: 100%;
    backface-visibility: hidden;
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.flashcard-front {
    background-color: #f8f9fa;
}

.flashcard-back {
    background-color: #e9ecef;
    transform: rotateY(180deg);
}

.flashcard h3 {
    margin-top: 0;
    color: #333;
}

.flashcard p {
    margin: 10px 0;
    line-height: 1.5;
}
```

---

## 📊 Rate Limits & Best Practices

### Rate Limits
- **Maximum flashcards per request**: 40
- **Minimum content length**: 50 words
- **Maximum content length**: 50,000 words
- **Request timeout**: 30 seconds

### Best Practices
1. **Content Quality**: Provide detailed, educational content for better flashcards
2. **Input Validation**: Validate input on frontend before sending requests
3. **Error Handling**: Always handle API errors gracefully
4. **Loading States**: Show loading indicators during generation
5. **Caching**: Consider caching generated flashcards for better UX

### Performance Tips
- Use appropriate `count` values (5-15 is optimal)
- Provide focused content rather than very broad topics
- Implement retry logic for failed requests
- Consider pagination for large flashcard sets

---

## 🔧 Testing

### Test Cases
```javascript
// Test different input types
const testCases = [
    {
        input: "Machine learning is a subset of artificial intelligence",
        input_type: "text",
        count: 5
    },
    {
        input: "https://en.wikipedia.org/wiki/Machine_learning",
        input_type: "url",
        count: 10
    },
    {
        input: "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
        input_type: "youtube",
        count: 8
    }
];

// Test error handling
const errorCases = [
    { input: "", expected: "Content is too short" },
    { input: "invalid-url", expected: "Invalid URL format" },
    { count: 50, expected: "Count exceeds maximum" }
];
```

---

## 📞 Support

For technical support or questions about the API:
- **Documentation**: Check this file for detailed examples
- **Error Codes**: Refer to the error response section
- **Rate Limits**: Ensure you're within the specified limits
- **Authentication**: Verify your Bearer token is valid

---

## 📚 Flashcard Set Management

### Get User's Flashcard Sets
**GET** `/api/flashcards`
**Headers:** `Authorization: Bearer {token}`

Retrieves all flashcard sets for the authenticated user with pagination and search.

**Query Parameters:**
- `per_page` (optional): Number of sets per page (default: 15)
- `search` (optional): Search term for title or description

**Response:**
```json
{
    "flashcard_sets": [
        {
            "id": 1,
            "title": "Machine Learning Algorithms",
            "description": "Flashcards generated from text input",
            "input_type": "text",
            "difficulty": "intermediate",
            "style": "mixed",
            "total_cards": 10,
            "is_public": false,
            "created_at": "2024-01-15T10:30:00Z",
            "flashcards": [
                {
                    "id": 1,
                    "question": "What is supervised learning?",
                    "answer": "Supervised learning uses labeled data to train models.",
                    "order_index": 0
                }
            ]
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 3,
        "per_page": 15,
        "total": 42
    }
}
```

### Get Specific Flashcard Set
**GET** `/api/flashcards/{id}`
**Headers:** `Authorization: Bearer {token}`

Retrieves a specific flashcard set with all its cards.

**Response:**
```json
{
    "flashcard_set": {
        "id": 1,
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
        "created_at": "2024-01-15T10:30:00Z",
        "flashcards": [
            {
                "id": 1,
                "question": "What is supervised learning?",
                "answer": "Supervised learning uses labeled data to train models.",
                "order_index": 0
            }
        ]
    }
}
```

### Update Flashcard Set
**PUT** `/api/flashcards/{id}`
**Headers:** `Authorization: Bearer {token}`

Updates a flashcard set's metadata (title, description, public status).

**Payload:**
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
    "message": "Flashcard set updated successfully",
    "flashcard_set": {
        "id": 1,
        "title": "Updated Title",
        "description": "Updated description",
        "is_public": true,
        "updated_at": "2024-01-15T11:00:00Z"
    }
}
```

### Delete Flashcard Set
**DELETE** `/api/flashcards/{id}`
**Headers:** `Authorization: Bearer {token}`

Deletes a flashcard set and all its cards.

**Response:**
```json
{
    "message": "Flashcard set deleted successfully"
}
```

### Get Public Flashcard Sets
**GET** `/api/flashcards/public`
**Headers:** `Authorization: Bearer {token}`

Retrieves all public flashcard sets from all users.

**Query Parameters:**
- `per_page` (optional): Number of sets per page (default: 15)
- `search` (optional): Search term for title or description

**Response:**
```json
{
    "flashcard_sets": [
        {
            "id": 5,
            "title": "Biology Basics",
            "description": "Flashcards generated from YouTube video: Biology 101",
            "input_type": "youtube",
            "difficulty": "beginner",
            "style": "mixed",
            "total_cards": 8,
            "is_public": true,
            "created_at": "2024-01-15T09:00:00Z",
            "user": {
                "id": 2,
                "name": "John Doe"
            },
            "flashcards": [
                {
                    "id": 15,
                    "question": "What is photosynthesis?",
                    "answer": "The process by which plants convert light energy into chemical energy.",
                    "order_index": 0
                }
            ]
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 67
    }
}
```

---

*Last updated: [Current Date]*
*API Version: 1.0*
