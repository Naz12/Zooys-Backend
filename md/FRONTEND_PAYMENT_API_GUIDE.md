# 💳 Frontend Payment API Guide

## 📋 Overview

Complete API documentation for payment and subscription management in the frontend client application.

## 🔐 Authentication

All payment endpoints require authentication via Bearer token.

```javascript
Headers: {
  'Authorization': 'Bearer your_user_token',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}
```

---

## 📊 Plan Management APIs

### 1. Get Available Plans

**Endpoint:** `GET /api/plans`

**Authentication:** Not required (Public endpoint)

**Request:**
```javascript
// No request body needed
fetch('http://localhost:8000/api/plans', {
  method: 'GET',
  headers: {
    'Accept': 'application/json'
  }
})
```

**Response:**
```javascript
// Success (200 OK)
[
  {
    "id": 1,
    "name": "Free",
    "price": 0.00,
    "currency": "USD",
    "limit": 20,
    "is_active": true,
    "created_at": "2025-10-27T00:00:00.000000Z",
    "updated_at": "2025-10-27T00:00:00.000000Z"
  },
  {
    "id": 2,
    "name": "Pro",
    "price": 9.99,
    "currency": "USD",
    "limit": 500,
    "is_active": true,
    "created_at": "2025-10-27T00:00:00.000000Z",
    "updated_at": "2025-10-27T00:00:00.000000Z"
  },
  {
    "id": 3,
    "name": "Unlimited",
    "price": 29.99,
    "currency": "USD",
    "limit": 10000,
    "is_active": true,
    "created_at": "2025-10-27T00:00:00.000000Z",
    "updated_at": "2025-10-27T00:00:00.000000Z"
  }
]
```

**Error Responses:**
```javascript
// Server Error (500)
{
  "error": "Failed to fetch plans"
}
```

---

## 💳 Payment Processing APIs

### 2. Create Checkout Session

**Endpoint:** `POST /api/checkout`

**Authentication:** Required

**Request:**
```javascript
fetch('http://localhost:8000/api/checkout', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer your_user_token',
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    plan_id: 2  // ID of the selected plan
  })
})
```

**Response:**
```javascript
// Success (200 OK)
{
  "checkout_url": "https://checkout.stripe.com/pay/cs_test_1234567890abcdef",
  "session_id": "cs_test_1234567890abcdef"
}

// Error (400 Bad Request) - Invalid plan
{
  "error": "Plan is not available"
}

// Error (400 Bad Request) - Already subscribed
{
  "error": "User already has an active subscription"
}

// Error (500 Internal Server Error) - Stripe error
{
  "error": "Failed to create checkout session"
}
```

**Data Flow:**
```
Frontend → POST /api/checkout → Laravel validates → Creates Stripe session → Returns checkout URL
```

---

## 📋 Subscription Management APIs

### 3. Get Current Subscription

**Endpoint:** `GET /api/subscription`

**Authentication:** Required

**Request:**
```javascript
fetch('http://localhost:8000/api/subscription', {
  method: 'GET',
  headers: {
    'Authorization': 'Bearer your_user_token',
    'Accept': 'application/json'
  }
})
```

**Response:**
```javascript
// Success (200 OK) - Active subscription
{
  "id": 25,
  "status": "active",
  "active": true,
  "plan": {
    "id": 3,
    "name": "Unlimited",
    "price": 29.99,
    "currency": "USD",
    "limit": 10000
  },
  "current_usage": 3,
  "usage_reset_date": "2025-11-27T11:42:18.000000Z",
  "billing_cycle_start": "2025-10-27T11:42:18.000000Z",
  "starts_at": "2025-10-27T11:42:18.000000Z",
  "ends_at": "2025-11-27T11:42:18.000000Z",
  "grace_period_ends_at": null,
  "in_grace_period": false
}

// Success (200 OK) - No active subscription
{
  "status": "none",
  "message": "No active subscription found"
}

// Error (401 Unauthorized)
{
  "error": "Unauthenticated"
}
```

### 4. Get Subscription History

**Endpoint:** `GET /api/subscription/history`

**Authentication:** Required

**Request:**
```javascript
fetch('http://localhost:8000/api/subscription/history', {
  method: 'GET',
  headers: {
    'Authorization': 'Bearer your_user_token',
    'Accept': 'application/json'
  }
})
```

**Response:**
```javascript
// Success (200 OK)
[
  {
    "id": 25,
    "plan": {
      "id": 3,
      "name": "Unlimited",
      "price": 29.99
    },
    "active": true,
    "starts_at": "2025-10-27T11:42:18.000000Z",
    "ends_at": "2025-11-27T11:42:18.000000Z",
    "created_at": "2025-10-27T11:42:18.000000Z"
  },
  {
    "id": 24,
    "plan": {
      "id": 2,
      "name": "Pro",
      "price": 9.99
    },
    "active": false,
    "starts_at": "2025-09-27T11:42:18.000000Z",
    "ends_at": "2025-10-27T11:42:18.000000Z",
    "created_at": "2025-09-27T11:42:18.000000Z"
  }
]
```

---

## 📊 Usage Tracking APIs

### 5. Get Usage Statistics

**Endpoint:** `GET /api/usage`

**Authentication:** Required

**Request:**
```javascript
fetch('http://localhost:8000/api/usage', {
  method: 'GET',
  headers: {
    'Authorization': 'Bearer your_user_token',
    'Accept': 'application/json'
  }
})
```

**Response:**
```javascript
// Success (200 OK)
{
  "current_usage": 3,
  "usage_limit": 10000,
  "remaining_usage": 9997,
  "usage_percentage": 0.03,
  "reset_date": "2025-11-27T11:42:18.000000Z",
  "days_until_reset": 15,
  "subscription": {
    "id": 25,
    "plan": {
      "id": 3,
      "name": "Unlimited",
      "limit": 10000
    },
    "active": true
  },
  "usage_history": [
    {
      "date": "2025-10-27",
      "count": 3
    },
    {
      "date": "2025-10-26",
      "count": 5
    }
  ]
}

// Error (401 Unauthorized)
{
  "error": "Unauthenticated"
}

// Error (403 Forbidden) - No subscription
{
  "error": "No active subscription found"
}
```

---

## 🔄 Complete Payment Flow

### Frontend Payment Flow

```
1. User Login
   ↓
2. Fetch Plans (GET /api/plans)
   ↓
3. Display Plan Selection
   ↓
4. User Selects Plan
   ↓
5. Create Checkout Session (POST /api/checkout)
   ↓
6. Redirect to Stripe Checkout
   ↓
7. User Completes Payment
   ↓
8. Stripe Redirects to Success Page
   ↓
9. Verify Subscription (GET /api/subscription)
   ↓
10. Update UI with New Subscription
```

### Data Flow Diagram

```
Frontend                    Backend                     Stripe
   |                          |                          |
   |-- GET /api/plans ------->|                          |
   |<-- Plans Data -----------|                          |
   |                          |                          |
   |-- POST /api/checkout --->|                          |
   |                          |-- Create Session ------>|
   |                          |<-- Session Data --------|
   |<-- Checkout URL ---------|                          |
   |                          |                          |
   |-- Redirect to Stripe --->|                          |
   |                          |                          |
   |                          |<-- Webhook Event --------|
   |                          |-- Process Payment ------>|
   |                          |                          |
   |-- GET /api/subscription->|                          |
   |<-- Subscription Data ---|                          |
```

---

## 🚨 Error Handling

### Common Error Scenarios

#### 1. Authentication Errors
```javascript
// 401 Unauthorized
{
  "error": "Unauthenticated"
}

// Handle by redirecting to login
if (response.status === 401) {
  window.location.href = '/login';
}
```

#### 2. Plan Validation Errors
```javascript
// 400 Bad Request - Plan not available
{
  "error": "Plan is not available"
}

// 400 Bad Request - Already subscribed
{
  "error": "User already has an active subscription"
}
```

#### 3. Stripe API Errors
```javascript
// 500 Internal Server Error
{
  "error": "Failed to create checkout session"
}

// Handle by showing error message and retry option
```

#### 4. Network Errors
```javascript
// Handle network failures
try {
  const response = await fetch('/api/checkout', options);
  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }
} catch (error) {
  console.error('Network error:', error);
  // Show user-friendly error message
}
```

---

## 📱 Frontend Implementation Examples

### 1. Plan Selection Component

```javascript
// Fetch and display plans
const fetchPlans = async () => {
  try {
    const response = await fetch('/api/plans');
    const plans = await response.json();
    setPlans(plans);
  } catch (error) {
    console.error('Failed to fetch plans:', error);
  }
};
```

### 2. Checkout Session Creation

```javascript
// Create checkout session
const createCheckoutSession = async (planId) => {
  try {
    const response = await fetch('/api/checkout', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${userToken}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ plan_id: planId })
    });
    
    const result = await response.json();
    
    if (response.ok) {
      // Redirect to Stripe checkout
      window.location.href = result.checkout_url;
    } else {
      console.error('Checkout failed:', result.error);
    }
  } catch (error) {
    console.error('Failed to create checkout session:', error);
  }
};
```

### 3. Subscription Status Check

```javascript
// Get current subscription
const getCurrentSubscription = async () => {
  try {
    const response = await fetch('/api/subscription', {
      headers: {
        'Authorization': `Bearer ${userToken}`
      }
    });
    
    const subscription = await response.json();
    
    if (subscription.status === 'active') {
      setSubscription(subscription);
    } else {
      setSubscription(null);
    }
  } catch (error) {
    console.error('Failed to fetch subscription:', error);
  }
};
```

### 4. Usage Statistics

```javascript
// Get usage statistics
const getUsageStats = async () => {
  try {
    const response = await fetch('/api/usage', {
      headers: {
        'Authorization': `Bearer ${userToken}`
      }
    });
    
    const usage = await response.json();
    setUsageStats(usage);
  } catch (error) {
    console.error('Failed to fetch usage stats:', error);
  }
};
```

---

## 🔧 Environment Configuration

### Frontend Environment Variables (.env.local)

```bash
# API Configuration
NEXT_PUBLIC_API_URL=http://localhost:8000/api
NEXT_PUBLIC_FRONTEND_URL=http://localhost:3000

# Stripe Configuration
NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY=pk_test_your_stripe_key
```

### API Base Configuration

```javascript
// API service configuration
const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL;

// Stripe configuration
const STRIPE_PUBLISHABLE_KEY = process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY;
```

---

## 📊 Testing Scenarios

### 1. Plan Selection Testing
- Test plan fetching
- Test plan display
- Test plan selection
- Test invalid plan handling

### 2. Checkout Flow Testing
- Test checkout session creation
- Test Stripe redirect
- Test payment success
- Test payment failure
- Test cancellation

### 3. Subscription Management Testing
- Test subscription status fetching
- Test subscription history
- Test usage statistics
- Test subscription updates

### 4. Error Handling Testing
- Test authentication errors
- Test network failures
- Test API errors
- Test validation errors

---

## 🚀 Production Considerations

### 1. Environment Variables
- Switch to live Stripe keys
- Update API URLs to production
- Configure production webhook URLs

### 2. Error Monitoring
- Implement error logging
- Add performance monitoring
- Set up alerting for critical errors

### 3. Security
- Validate all inputs
- Sanitize user data
- Implement rate limiting
- Use HTTPS in production

### 4. Performance
- Implement caching
- Optimize API calls
- Add loading states
- Implement offline support

This guide provides complete API documentation for implementing payment functionality in your frontend application.
























