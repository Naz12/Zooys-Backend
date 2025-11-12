# AI Manager Custom Prompt - Test Request & Response Structure

## **Endpoint**
```
POST {AI_MANAGER_URL}/api/custom-prompt
```

## **Headers**
```
Accept: application/json
Content-Type: application/json
X-API-KEY: {YOUR_AI_MANAGER_API_KEY}
```

## **Request Body Format**

### **Example Request (for flashcard generation):**

```json
{
  "system_prompt": "You are an expert educational flashcard generator. Your task is to create high-quality flashcards that help students learn effectively.\n\nRules:\n1. Each flashcard MUST have exactly two fields: 'front' and 'back'\n2. 'front' contains the question, prompt, or term\n3. 'back' contains the answer, explanation, or definition\n4. Flashcards should be clear, concise, and educational\n5. Return ONLY valid JSON array format, no additional text or explanations\n\nDifficulty Levels:\n- beginner: Simple, basic concepts with straightforward questions\n- intermediate: Moderate complexity, requires understanding of relationships\n- advanced: Complex concepts, requires deep analysis and synthesis\n\nStyle Types:\n- definition: Focus on definitions and key terms\n- application: Focus on practical applications and examples\n- analysis: Focus on analysis and critical thinking\n- comparison: Focus on comparing and contrasting concepts\n- mixed: Combine different question types\n\nOutput format: A JSON array of flashcard objects.\nExample:\n[\n  {\"front\": \"What is X?\", \"back\": \"X is...\"},\n  {\"front\": \"How does Y work?\", \"back\": \"Y works by...\"}\n]",
  "prompt": "create json only flash card about flash card about the programming language java card count 5, difficulty intermediate, style mixed, front and back",
  "response_format": "json",
  "model": "deepseek-chat",
  "max_tokens": 512
}
```

### **Request Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `system_prompt` | string | Yes | Static prompt that defines the AI's role and output format |
| `prompt` | string | Yes | User's actual request (dynamically constructed from user input) |
| `response_format` | string | Yes | Set to `"json"` for structured responses |
| `model` | string | Optional | AI model to use (default: `"deepseek-chat"`) |
| `max_tokens` | integer | Optional | Maximum tokens in response (default: `512`) |

### **Dynamic Prompt Construction:**

The `prompt` field is constructed from user input:
```
"create json only flash card about {content} card count {count}, difficulty {difficulty}, style {style}, front and back"
```

**Example with actual values:**
- `content` = "flash card about the programming language java"
- `count` = 5
- `difficulty` = "intermediate"
- `style` = "mixed"

**Result:**
```
"create json only flash card about flash card about the programming language java card count 5, difficulty intermediate, style mixed, front and back"
```

---

## **Response Structure**

### **Success Response:**

```json
{
  "status": "success",
  "model_used": "deepseek-chat",
  "model_display": "deepseek-chat",
  "format": "json",
  "data": {
    "format": "json",
    "raw": "[\n  {\"front\": \"What is Java?\", \"back\": \"Java is a high-level, object-oriented programming language...\"},\n  {\"front\": \"What is JVM?\", \"back\": \"JVM (Java Virtual Machine) is...\"}\n]",
    "content": [
      {
        "front": "What is Java?",
        "back": "Java is a high-level, object-oriented programming language..."
      },
      {
        "front": "What is JVM?",
        "back": "JVM (Java Virtual Machine) is..."
      }
    ]
  },
  "tokens_used": 245,
  "processing_time": 1.23
}
```

### **Response Structure Breakdown:**

#### **Top Level:**
```json
{
  "status": "success",              // Response status
  "model_used": "deepseek-chat",    // Model that processed the request
  "model_display": "deepseek-chat", // Display name for the model
  "format": "json",                  // Response format
  "data": { ... },                   // Main data object
  "tokens_used": 245,                // Tokens consumed
  "processing_time": 1.23            // Processing time in seconds
}
```

#### **Data Object:**
```json
{
  "format": "json",
  "raw": "...",                      // Raw JSON string (if available)
  "content": [                       // Parsed JSON array (PREFERRED)
    {
      "front": "...",
      "back": "..."
    }
  ]
}
```

**OR** (alternative structure):
```json
{
  "format": "json",
  "json": [                          // Alternative location for parsed JSON
    {
      "front": "...",
      "back": "..."
    }
  ]
}
```

### **Error Response:**

```json
{
  "status": "error",
  "message": "Error description here",
  "available_models": ["deepseek-chat", "gpt-4", ...]
}
```

---

## **How the Code Extracts Flashcards:**

The code checks for flashcards in this order:

1. **`data.content`** (array) - Most common format from custom-prompt
2. **`data.json`** (array) - Alternative location
3. **`data.raw_output`** (array) - Fallback if content/json not found
4. **`data.raw_output.cards`** (array) - If raw_output is an object with 'cards' key

### **Expected Flashcard Format:**

Each flashcard object should have:
```json
{
  "front": "Question or term",
  "back": "Answer or definition"
}
```

**OR** (from old `/api/process-text` endpoint):
```json
{
  "question": "Question or term",
  "answer": "Answer or definition"
}
```

The code normalizes both formats to `front`/`back`.

---

## **Postman Test Example:**

### **Request:**
```http
POST https://aimanager.akmicroservice.com/api/custom-prompt
Content-Type: application/json
X-API-KEY: 8eebab3587a5719950dfb3ee348737c6e244c13a5d6b3d35161071ee6a9d8c43

{
  "system_prompt": "You are an expert educational flashcard generator. Your task is to create high-quality flashcards that help students learn effectively.\n\nRules:\n1. Each flashcard MUST have exactly two fields: 'front' and 'back'\n2. 'front' contains the question, prompt, or term\n3. 'back' contains the answer, explanation, or definition\n4. Flashcards should be clear, concise, and educational\n5. Return ONLY valid JSON array format, no additional text or explanations\n\nDifficulty Levels:\n- beginner: Simple, basic concepts with straightforward questions\n- intermediate: Moderate complexity, requires understanding of relationships\n- advanced: Complex concepts, requires deep analysis and synthesis\n\nStyle Types:\n- definition: Focus on definitions and key terms\n- application: Focus on practical applications and examples\n- analysis: Focus on analysis and critical thinking\n- comparison: Focus on comparing and contrasting concepts\n- mixed: Combine different question types\n\nOutput format: A JSON array of flashcard objects.\nExample:\n[\n  {\"front\": \"What is X?\", \"back\": \"X is...\"},\n  {\"front\": \"How does Y work?\", \"back\": \"Y works by...\"}\n]",
  "prompt": "create json only flash card about the programming language java card count 5, difficulty intermediate, style mixed, front and back",
  "response_format": "json",
  "model": "deepseek-chat",
  "max_tokens": 512
}
```

### **Expected Response:**
```json
{
  "status": "success",
  "model_used": "deepseek-chat",
  "model_display": "deepseek-chat",
  "format": "json",
  "data": {
    "format": "json",
    "content": [
      {
        "front": "What is Java?",
        "back": "Java is a high-level, object-oriented programming language developed by Sun Microsystems (now Oracle)."
      },
      {
        "front": "What is the JVM?",
        "back": "JVM (Java Virtual Machine) is a virtual machine that enables Java programs to run on any device or operating system."
      },
      {
        "front": "What is the difference between JDK and JRE?",
        "back": "JDK (Java Development Kit) includes development tools like compiler and debugger, while JRE (Java Runtime Environment) only includes the runtime needed to run Java applications."
      },
      {
        "front": "What is object-oriented programming in Java?",
        "back": "Object-oriented programming in Java is a programming paradigm based on the concept of objects, which contain data (fields) and code (methods), and supports concepts like inheritance, encapsulation, and polymorphism."
      },
      {
        "front": "What is the main method in Java?",
        "back": "The main method is the entry point of a Java application. It must be declared as 'public static void main(String[] args)' and is called by the JVM when the program starts."
      }
    ]
  },
  "tokens_used": 245,
  "processing_time": 1.23
}
```

---

## **Notes:**

1. The `system_prompt` is **static** and defines the AI's behavior
2. The `prompt` is **dynamically constructed** from user input (content, count, difficulty, style)
3. The response format is always `"json"` for structured output
4. Flashcards are extracted from `data.content` (array) in the response
5. Each flashcard should have `front` and `back` fields
6. The code normalizes `question`/`answer` format to `front`/`back` for consistency

