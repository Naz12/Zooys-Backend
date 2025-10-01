# Client API Endpoints

## Authentication

### Register User
**POST** `/api/register`

Creates a new user account and returns authentication token.

**Payload:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123"
}
```

**Response:**
```json
{
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "is_active": true,
        "status": "active",
        "created_at": "2025-01-30T10:00:00.000000Z"
    },
    "token": "1|abc123def456ghi789..."
}
```

---

### Login User
**POST** `/api/login`

Authenticates user and returns authentication token.

**Payload:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Response:**
```json
{
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "is_active": true,
        "status": "active"
    },
    "token": "1|abc123def456ghi789..."
}
```

---

### Logout User
**POST** `/api/logout`
**Headers:** `Authorization: Bearer {token}`

Revokes all authentication tokens for the current user.

**Response:**
```json
{
    "message": "Logged out successfully"
}
```

---

### Get Current User
**GET** `/api/user`
**Headers:** `Authorization: Bearer {token}`

Returns the authenticated user's profile information.

**Response:**
```json
{
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "is_active": true,
    "status": "active",
    "suspended_at": null,
    "suspension_reason": null,
    "created_at": "2025-01-30T10:00:00.000000Z"
}
```

---

## Subscription & Plans

### Get Available Plans
**GET** `/api/plans`

Returns all active subscription plans available for purchase.

**Response:**
```json
[
    {
        "id": 1,
        "name": "Basic Plan",
        "price": "9.99",
        "currency": "USD",
        "limit": 100,
        "is_active": true
    },
    {
        "id": 2,
        "name": "Pro Plan",
        "price": "29.99",
        "currency": "USD",
        "limit": 1000,
        "is_active": true
    }
]
```

---

### Get Current Subscription
**GET** `/api/subscription`
**Headers:** `Authorization: Bearer {token}`

Returns the user's current active subscription details.

**Response (Active):**
```json
{
    "status": "active",
    "plan": "Pro Plan",
    "price": "29.99",
    "currency": "USD",
    "limit": 1000,
    "starts_at": "2025-01-01T00:00:00.000000Z",
    "ends_at": "2025-02-01T00:00:00.000000Z"
}
```

**Response (No Subscription):**
```json
{
    "status": "none",
    "message": "No active subscription found"
}
```

---

### Get Subscription History
**GET** `/api/subscription/history`
**Headers:** `Authorization: Bearer {token}`

Returns the user's complete subscription history.

**Response:**
```json
[
    {
        "plan": "Pro Plan",
        "price": "29.99",
        "currency": "USD",
        "limit": 1000,
        "active": true,
        "starts_at": "2025-01-01T00:00:00.000000Z",
        "ends_at": "2025-02-01T00:00:00.000000Z"
    }
]
```

---

### Get Usage Statistics
**GET** `/api/usage`
**Headers:** `Authorization: Bearer {token}`

Returns user's current usage statistics against their plan limits.

**Status:** ⚠️ Not implemented yet

**Expected Response:**
```json
{
    "current_usage": 45,
    "plan_limit": 1000,
    "usage_percentage": 4.5,
    "remaining_usage": 955,
    "reset_date": "2025-02-01T00:00:00.000000Z",
    "by_tool": {
        "YouTube Summarizer": 15,
        "AI Writer": 20,
        "PDF Summarizer": 5
    }
}
```

---

## Payment

### Create Checkout Session
**POST** `/api/checkout`
**Headers:** `Authorization: Bearer {token}`

Creates a Stripe checkout session for subscription purchase.

**Status:** ⚠️ Not implemented yet

**Payload:**
```json
{
    "plan_id": 2,
    "success_url": "https://yourapp.com/success",
    "cancel_url": "https://yourapp.com/cancel"
}
```

**Expected Response:**
```json
{
    "checkout_url": "https://checkout.stripe.com/c/pay/cs_test_123...",
    "session_id": "cs_test_123456789"
}
```

---

## AI Tools

### YouTube Video Summarizer
**POST** `/api/youtube/summarize`
**Headers:** `Authorization: Bearer {token}`

Summarizes YouTube video content using AI.

**Payload:**
```json
{
    "video_url": "https://youtube.com/watch?v=abc123",
    "language": "en",
    "mode": "detailed"
}
```

**Response:**
```json
{
    "summary": "This is a test summary for video: https://youtube.com/watch?v=abc123"
}
```

**Status:** ⚠️ Returns mock data

---

### PDF Document Summarizer
**POST** `/api/pdf/summarize`
**Headers:** `Authorization: Bearer {token}`

Summarizes PDF document content using AI.

**Payload:**
```json
{
    "file_path": "/path/to/document.pdf"
}
```

**Response:**
```json
{
    "summary": "This is a test summary for PDF: /path/to/document.pdf"
}
```

**Status:** ⚠️ Returns mock data

---

### AI Content Writer
**POST** `/api/writer/run`
**Headers:** `Authorization: Bearer {token}`

Generates written content based on user prompts using AI.

**Payload:**
```json
{
    "prompt": "Write a blog post about artificial intelligence",
    "mode": "creative"
}
```

**Response:**
```json
{
    "output": "Generated writing for: Write a blog post about artificial intelligence"
}
```

**Status:** ⚠️ Returns mock data

---

### Math Problem Solver
**POST** `/api/math/solve`
**Headers:** `Authorization: Bearer {token}`

Solves mathematical problems using AI.

**Payload:**
```json
{
    "problem": "2x + 5 = 15"
}
```

**Response:**
```json
{
    "solution": "Solved result for: 2x + 5 = 15"
}
```

**Status:** ⚠️ Returns mock data

---

### Flashcard Generator
**POST** `/api/flashcards/generate`
**Headers:** `Authorization: Bearer {token}`

Generates educational flashcards for any topic using AI.

**Payload:**
```json
{
    "topic": "Machine Learning"
}
```

**Response:**
```json
{
    "flashcards": [
        {
            "question": "What is AI?",
            "answer": "Artificial Intelligence"
        },
        {
            "question": "What is ML?",
            "answer": "Machine Learning"
        }
    ]
}
```

**Status:** ⚠️ Returns mock data

---

### Diagram Generator
**POST** `/api/diagram/generate`
**Headers:** `Authorization: Bearer {token}`

Generates diagrams and flowcharts based on descriptions using AI.

**Payload:**
```json
{
    "description": "Create a flowchart for user registration process"
}
```

**Response:**
```json
{
    "diagram": "Generated diagram for: Create a flowchart for user registration process"
}
```

**Status:** ⚠️ Returns mock data

---

## Status Legend

- ✅ **Fully Functional** - Ready for production use
- ⚠️ **Mock Data** - Framework ready, returns dummy data
- ❌ **Not Implemented** - Route exists but method missing
