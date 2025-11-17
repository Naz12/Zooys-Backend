# Google Sign-In - Frontend API Documentation

## Overview

This document provides complete frontend integration guide for Google Sign-In authentication. The backend uses Laravel Socialite to handle OAuth flow and returns a Sanctum token that works identically to email/password authentication.

**Base URL:** `http://localhost:8000/api` (development)  
**Production URL:** `https://yourdomain.com/api`

---

## Table of Contents

1. [Authentication Flow](#authentication-flow)
2. [API Endpoints](#api-endpoints)
3. [Frontend Integration](#frontend-integration)
4. [Callback Handling](#callback-handling)
5. [Error Handling](#error-handling)
6. [Examples](#examples)
7. [Best Practices](#best-practices)

---

## Authentication Flow

### Complete Flow Diagram

```
1. User clicks "Sign in with Google" button
   ↓
2. Frontend redirects to: GET /api/auth/google/redirect
   ↓
3. Backend redirects to Google OAuth consent screen
   ↓
4. User authorizes on Google
   ↓
5. Google redirects to: GET /api/auth/google/callback
   ↓
6. Backend processes OAuth, creates/updates user, generates token
   ↓
7. Backend redirects to: {FRONTEND_URL}/auth/callback?token=...&user=...
   ↓
8. Frontend extracts token, stores it, redirects to dashboard
```

### Step-by-Step Flow

1. **User Initiates Sign-In**
   - User clicks "Sign in with Google" button
   - Frontend redirects browser to backend endpoint

2. **Backend Redirects to Google**
   - Backend generates OAuth URL
   - User is redirected to Google's consent screen

3. **User Authorizes**
   - User logs into Google (if not already)
   - User grants permissions to your app
   - Google redirects back to your callback URL

4. **Backend Processes**
   - Backend receives authorization code
   - Exchanges code for user info
   - Creates/updates user in database
   - Generates Sanctum token

5. **Frontend Receives Token**
   - Backend redirects to frontend callback URL
   - Token and user info passed as URL parameters
   - Frontend stores token and authenticates user

---

## API Endpoints

### 1. Initiate Google Sign-In

Redirects user to Google OAuth consent screen.

**Endpoint:** `GET /api/auth/google/redirect`

**Authentication:** Not required (public endpoint)

**Request:**
```http
GET /api/auth/google/redirect
```

**Response:**
- **Status:** `302 Redirect`
- **Location:** Google OAuth consent screen URL

**Example:**
```javascript
// Simply redirect the browser
window.location.href = 'http://localhost:8000/api/auth/google/redirect';
```

**Error Response (500):**
```json
{
  "error": "Google authentication is not configured. Please contact support.",
  "message": "Client ID not found"
}
```

---

### 2. Google OAuth Callback

Handles Google's redirect after user authorization. This endpoint processes the OAuth callback and redirects to your frontend.

**Endpoint:** `GET /api/auth/google/callback`

**Authentication:** Not required (handled by Google OAuth)

**Query Parameters (from Google):**
- `code` - Authorization code from Google
- `state` - CSRF protection state parameter

**Response:**
- **Status:** `302 Redirect`
- **Location:** `{FRONTEND_URL}/auth/callback?token=...&user=...`

**Success Redirect URL:**
```
http://localhost:3000/auth/callback?token=1|abc123...&user=eyJpZCI6MSwibmFtZSI6IkpvaG4gRG9lIiwiZW1haWwiOiJqb2huQGdtYWlsLmNvbSIsImF2YXRhciI6Imh0dHBzOi8vLi4uIiwicHJvdmlkZXIiOiJnb29nbGUifQ==
```

**URL Parameters:**
- `token` - Sanctum authentication token (use this for API requests)
- `user` - Base64 encoded JSON string with user information

**Decoded User Object:**
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@gmail.com",
  "avatar": "https://lh3.googleusercontent.com/...",
  "provider": "google"
}
```

**Error Redirect URL:**
```
http://localhost:3000/auth/callback?error=Authentication%20failed.%20Please%20try%20again.
```

---

## Frontend Integration

### Option 1: Simple Redirect (Recommended)

The simplest approach - just redirect to the backend endpoint.

```javascript
// React example
const handleGoogleSignIn = () => {
  window.location.href = `${API_BASE_URL}/auth/google/redirect`;
};

// Vue example
const handleGoogleSignIn = () => {
  window.location.href = `${API_BASE_URL}/auth/google/redirect`;
};

// Vanilla JavaScript
function handleGoogleSignIn() {
  window.location.href = 'http://localhost:8000/api/auth/google/redirect';
}
```

### Option 2: Using Google Identity Services (Advanced)

For more control, you can use Google's Identity Services library directly.

```html
<!-- Include Google Identity Services -->
<script src="https://accounts.google.com/gsi/client" async defer></script>
```

```javascript
// Initialize Google Sign-In
window.onload = function () {
  google.accounts.id.initialize({
    client_id: 'YOUR_GOOGLE_CLIENT_ID',
    callback: handleCredentialResponse
  });

  google.accounts.id.renderButton(
    document.getElementById("google-signin-button"),
    { theme: "outline", size: "large" }
  );
};

function handleCredentialResponse(response) {
  // Send credential to your backend
  fetch(`${API_BASE_URL}/auth/google/verify`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ credential: response.credential })
  })
  .then(res => res.json())
  .then(data => {
    // Store token and redirect
    localStorage.setItem('token', data.token);
    window.location.href = '/dashboard';
  });
}
```

**Note:** Option 2 requires additional backend endpoint to verify the credential. Option 1 is simpler and already implemented.

---

## Callback Handling

### Create Callback Page

Create a page/route at `/auth/callback` to handle the redirect from backend.

**React Router Example:**
```jsx
// src/pages/AuthCallback.jsx
import { useEffect } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';

function AuthCallback() {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();

  useEffect(() => {
    const token = searchParams.get('token');
    const userParam = searchParams.get('user');
    const error = searchParams.get('error');

    if (error) {
      // Handle error
      console.error('Authentication error:', error);
      navigate('/login?error=' + encodeURIComponent(error));
      return;
    }

    if (token && userParam) {
      try {
        // Decode user info
        const user = JSON.parse(atob(userParam));
        
        // Store token
        localStorage.setItem('auth_token', token);
        localStorage.setItem('user', JSON.stringify(user));
        
        // Update API client with token
        // (your API client setup)
        
        // Redirect to dashboard
        navigate('/dashboard');
      } catch (err) {
        console.error('Failed to parse user data:', err);
        navigate('/login?error=Invalid response');
      }
    } else {
      navigate('/login?error=Missing authentication data');
    }
  }, [searchParams, navigate]);

  return (
    <div className="flex items-center justify-center min-h-screen">
      <div className="text-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
        <p className="mt-4 text-gray-600">Completing sign in...</p>
      </div>
    </div>
  );
}
```

**Next.js Example:**
```tsx
// pages/auth/callback.tsx
import { useEffect } from 'react';
import { useRouter } from 'next/router';

export default function AuthCallback() {
  const router = useRouter();
  const { token, user: userParam, error } = router.query;

  useEffect(() => {
    if (error) {
      router.push(`/login?error=${encodeURIComponent(error as string)}`);
      return;
    }

    if (token && userParam) {
      try {
        const user = JSON.parse(Buffer.from(userParam as string, 'base64').toString());
        
        // Store in localStorage or cookies
        localStorage.setItem('auth_token', token as string);
        localStorage.setItem('user', JSON.stringify(user));
        
        router.push('/dashboard');
      } catch (err) {
        router.push('/login?error=Invalid response');
      }
    }
  }, [token, userParam, error, router]);

  return (
    <div className="flex items-center justify-center min-h-screen">
      <div className="text-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
        <p className="mt-4 text-gray-600">Completing sign in...</p>
      </div>
    </div>
  );
}
```

**Vue 3 Example:**
```vue
<!-- src/views/AuthCallback.vue -->
<template>
  <div class="flex items-center justify-center min-h-screen">
    <div class="text-center">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
      <p class="mt-4 text-gray-600">Completing sign in...</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

onMounted(() => {
  const token = route.query.token;
  const userParam = route.query.user;
  const error = route.query.error;

  if (error) {
    router.push(`/login?error=${encodeURIComponent(error)}`);
    return;
  }

  if (token && userParam) {
    try {
      const user = JSON.parse(atob(userParam));
      
      localStorage.setItem('auth_token', token);
      localStorage.setItem('user', JSON.stringify(user));
      
      router.push('/dashboard');
    } catch (err) {
      router.push('/login?error=Invalid response');
    }
  } else {
    router.push('/login?error=Missing authentication data');
  }
});
</script>
```

---

## Error Handling

### Common Errors

**1. Google Not Configured**
```json
{
  "error": "Google authentication is not configured. Please contact support."
}
```
**Solution:** Backend needs Google OAuth credentials in `.env`

**2. Authentication Failed**
```
/auth/callback?error=Authentication%20failed.%20Please%20try%20again.
```
**Possible Causes:**
- User denied permissions
- Network error
- Invalid OAuth configuration
- Backend error

**3. Missing Token/User Data**
```
/auth/callback (no parameters)
```
**Solution:** Check backend logs, verify callback URL is correct

### Error Handling in Frontend

```javascript
function handleAuthCallback() {
  const urlParams = new URLSearchParams(window.location.search);
  const token = urlParams.get('token');
  const userParam = urlParams.get('user');
  const error = urlParams.get('error');

  if (error) {
    // Show error message to user
    showError(decodeURIComponent(error));
    redirectToLogin();
    return;
  }

  if (!token || !userParam) {
    showError('Authentication failed. Missing token or user data.');
    redirectToLogin();
    return;
  }

  try {
    const user = JSON.parse(atob(userParam));
    storeAuthData(token, user);
    redirectToDashboard();
  } catch (err) {
    showError('Failed to process authentication data.');
    redirectToLogin();
  }
}
```

---

## Examples

### Complete React Component

```jsx
// src/components/GoogleSignInButton.jsx
import React from 'react';

const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

function GoogleSignInButton() {
  const handleGoogleSignIn = () => {
    // Redirect to backend Google OAuth endpoint
    window.location.href = `${API_BASE_URL}/auth/google/redirect`;
  };

  return (
    <button
      onClick={handleGoogleSignIn}
      className="flex items-center justify-center w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
    >
      <svg className="w-5 h-5 mr-2" viewBox="0 0 24 24">
        <path
          fill="#4285F4"
          d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
        />
        <path
          fill="#34A853"
          d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
        />
        <path
          fill="#FBBC05"
          d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
        />
        <path
          fill="#EA4335"
          d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
        />
      </svg>
      Sign in with Google
    </button>
  );
}

export default GoogleSignInButton;
```

### Complete Vue Component

```vue
<!-- src/components/GoogleSignInButton.vue -->
<template>
  <button
    @click="handleGoogleSignIn"
    class="flex items-center justify-center w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50"
  >
    <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
      <!-- Google logo SVG paths -->
    </svg>
    Sign in with Google
  </button>
</template>

<script setup>
const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

const handleGoogleSignIn = () => {
  window.location.href = `${API_BASE_URL}/auth/google/redirect`;
};
</script>
```

### API Client Integration

```javascript
// src/utils/apiClient.js
const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

class ApiClient {
  constructor() {
    this.token = localStorage.getItem('auth_token');
  }

  setToken(token) {
    this.token = token;
    localStorage.setItem('auth_token', token);
  }

  async request(endpoint, options = {}) {
    const url = `${API_BASE_URL}${endpoint}`;
    const config = {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(this.token && { 'Authorization': `Bearer ${this.token}` }),
        ...options.headers,
      },
    };

    const response = await fetch(url, config);
    
    if (response.status === 401) {
      // Token expired or invalid
      this.clearToken();
      window.location.href = '/login';
      throw new Error('Unauthorized');
    }

    return response.json();
  }

  clearToken() {
    this.token = null;
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user');
  }
}

export default new ApiClient();
```

### Using Token in API Requests

```javascript
// After successful Google Sign-In
const token = new URLSearchParams(window.location.search).get('token');

if (token) {
  // Store token
  localStorage.setItem('auth_token', token);
  
  // Use token in API requests
  fetch('http://localhost:8000/api/user', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    },
  })
  .then(res => res.json())
  .then(data => {
    console.log('User data:', data);
  });
}
```

---

## Best Practices

### 1. Environment Variables

Create `.env` file for your frontend:

```env
REACT_APP_API_URL=http://localhost:8000/api
# or for production
REACT_APP_API_URL=https://yourdomain.com/api
```

### 2. Token Storage

**Recommended:** Use `localStorage` for web apps, `secureStorage` for mobile apps.

```javascript
// Store token
localStorage.setItem('auth_token', token);

// Retrieve token
const token = localStorage.getItem('auth_token');

// Remove token (on logout)
localStorage.removeItem('auth_token');
```

### 3. Token Validation

Always validate token on app load:

```javascript
useEffect(() => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    // Verify token is still valid
    apiClient.request('/user')
      .then(user => {
        setUser(user);
      })
      .catch(() => {
        // Token invalid, clear it
        localStorage.removeItem('auth_token');
        router.push('/login');
      });
  }
}, []);
```

### 4. Loading States

Show loading indicator during OAuth flow:

```jsx
const [isLoading, setIsLoading] = useState(false);

const handleGoogleSignIn = () => {
  setIsLoading(true);
  window.location.href = `${API_BASE_URL}/auth/google/redirect`;
};
```

### 5. Error Messages

Display user-friendly error messages:

```jsx
const [error, setError] = useState(null);

// In callback handler
if (error) {
  const errorMessage = decodeURIComponent(error);
  setError(errorMessage);
  // Show error toast/alert
}
```

### 6. Redirect After Login

Store intended destination before redirecting:

```javascript
// Before Google Sign-In
const returnUrl = window.location.pathname;
sessionStorage.setItem('return_url', returnUrl);
window.location.href = `${API_BASE_URL}/auth/google/redirect`;

// After successful login
const returnUrl = sessionStorage.getItem('return_url') || '/dashboard';
router.push(returnUrl);
sessionStorage.removeItem('return_url');
```

### 7. Security Considerations

- **HTTPS in Production:** Always use HTTPS for OAuth in production
- **Token Expiration:** Handle token expiration gracefully
- **CSRF Protection:** Backend handles state parameter automatically
- **XSS Prevention:** Don't store sensitive data in localStorage if vulnerable to XSS

---

## Testing

### Local Development

1. **Start Backend:** `php artisan serve` (runs on `http://localhost:8000`)
2. **Start Frontend:** Your frontend dev server (e.g., `npm start`)
3. **Test Flow:**
   - Click "Sign in with Google"
   - Should redirect to Google (if configured)
   - After authorization, redirects back to frontend
   - Token should be stored and user authenticated

### Without Google Credentials

If Google OAuth is not configured, you'll get:
```json
{
  "error": "Google authentication is not configured. Please contact support."
}
```

This is expected until you add Google OAuth credentials in production.

---

## Troubleshooting

### Issue: Redirect Loop

**Cause:** Callback URL mismatch  
**Solution:** Verify `GOOGLE_REDIRECT_URI` in backend matches Google Cloud Console settings

### Issue: Token Not Received

**Cause:** Frontend callback URL not configured  
**Solution:** Set `FRONTEND_URL` in backend `.env` file

### Issue: CORS Errors

**Cause:** Frontend domain not allowed  
**Solution:** Add frontend domain to `SANCTUM_STATEFUL_DOMAINS` in backend

### Issue: User Data Not Parsing

**Cause:** Base64 encoding issue  
**Solution:** Use `atob()` for decoding, handle errors gracefully

---

## Production Checklist

- [ ] Add Google OAuth credentials to `.env`
- [ ] Set `APP_URL` to production domain
- [ ] Set `FRONTEND_URL` to production frontend URL
- [ ] Configure Google Cloud Console redirect URI
- [ ] Test complete flow in production
- [ ] Set up error monitoring
- [ ] Configure HTTPS (required for OAuth)
- [ ] Test account linking scenarios
- [ ] Test error handling

---

## Support

For issues:
1. Check backend logs: `storage/logs/laravel.log`
2. Verify Google OAuth credentials are correct
3. Check browser console for frontend errors
4. Verify callback URL matches Google Cloud Console

---

**Last Updated:** 2024-11-14  
**API Version:** 1.0.0

