# 🔐 Frontend Authentication Fix - Complete Solution

## 🎯 **Root Cause Identified**

The CORS error is happening because your frontend is **not including a valid authentication token** in the requests. The Laravel API requires authentication for all math endpoints.

## ✅ **The Fix**

Your frontend needs to include the `Authorization` header with a valid Bearer token. Here's the complete solution:

### **1. Update Your API Client**

In your `lib/api-client.ts` or `lib/math-api-client.ts`, make sure you're including the authentication token:

```typescript
class ApiClient {
  private baseUrl: string;
  private token: string;

  constructor(baseUrl: string, token: string) {
    this.baseUrl = baseUrl;
    this.token = token;
  }

  private async request<T>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<T> {
    const url = `${this.baseUrl}${endpoint}`;
    
    const config: RequestInit = {
      ...options,
      headers: {
        'Authorization': `Bearer ${this.token}`, // ← This is CRITICAL!
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Origin': 'http://localhost:3000',
        ...options.headers,
      },
    };

    console.log('API Request URL:', url);
    console.log('API Request Token:', this.token ? 'Present' : 'Missing'); // ← Check this!
    console.log('API Request Config:', config);

    try {
      const response = await fetch(url, config);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      return await response.json();
    } catch (error) {
      console.error('API Request Error:', error);
      throw error;
    }
  }
}
```

### **2. Get Authentication Token**

You need to authenticate with the API first to get a token:

```typescript
// Login function
const login = async (email: string, password: string) => {
  try {
    const response = await fetch('http://localhost:8000/api/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ email, password }),
    });
    
    if (!response.ok) {
      throw new Error(`Login failed: ${response.status}`);
    }
    
    const data = await response.json();
    
    // Store the token
    localStorage.setItem('auth_token', data.token);
    return data.token;
  } catch (error) {
    console.error('Login error:', error);
    throw error;
  }
};

// Register function
const register = async (name: string, email: string, password: string) => {
  try {
    const response = await fetch('http://localhost:8000/api/register', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ name, email, password }),
    });
    
    if (!response.ok) {
      throw new Error(`Registration failed: ${response.status}`);
    }
    
    const data = await response.json();
    
    // Store the token
    localStorage.setItem('auth_token', data.token);
    return data.token;
  } catch (error) {
    console.error('Registration error:', error);
    throw error;
  }
};
```

### **3. Complete Math API Client**

```typescript
class MathApiClient {
  private apiClient: ApiClient;

  constructor(baseUrl: string, token: string) {
    this.apiClient = new ApiClient(baseUrl, token);
  }

  async solveMathProblem(problem: {
    problem_text: string;
    subject_area: string;
    difficulty_level: string;
    problem_type?: string;
  }) {
    return this.apiClient.request('/math/solve', {
      method: 'POST',
      body: JSON.stringify(problem),
    });
  }

  async getMathHistory() {
    return this.apiClient.request('/math/history');
  }

  async getMathStats() {
    return this.apiClient.request('/math/stats');
  }
}
```

### **4. Usage in React Component**

```typescript
import React, { useState, useEffect } from 'react';
import MathApiClient from '../lib/math-api-client';

const MathSolver: React.FC = () => {
  const [token, setToken] = useState<string | null>(null);
  const [problem, setProblem] = useState('');
  const [solution, setSolution] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    // Get token from localStorage or authenticate
    const storedToken = localStorage.getItem('auth_token');
    if (storedToken) {
      setToken(storedToken);
    } else {
      // Redirect to login or show login form
      console.log('No authentication token found');
    }
  }, []);

  const handleSolve = async () => {
    if (!token) {
      setError('Please authenticate first');
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const apiClient = new MathApiClient('http://localhost:8000/api', token);
      const result = await apiClient.solveMathProblem({
        problem_text: problem,
        subject_area: 'general',
        difficulty_level: 'intermediate',
        problem_type: 'text'
      });
      
      setSolution(result);
    } catch (err) {
      console.error('API Error:', err);
      setError(err instanceof Error ? err.message : 'Failed to solve problem');
    } finally {
      setLoading(false);
    }
  };

  if (!token) {
    return (
      <div>
        <h2>Authentication Required</h2>
        <p>Please log in to use the math solver.</p>
        {/* Add login form here */}
      </div>
    );
  }

  return (
    <div>
      <h2>Math Problem Solver</h2>
      <textarea
        value={problem}
        onChange={(e) => setProblem(e.target.value)}
        placeholder="Enter your math problem..."
      />
      <button onClick={handleSolve} disabled={loading}>
        {loading ? 'Solving...' : 'Solve Problem'}
      </button>
      
      {error && <div className="error">{error}</div>}
      {solution && <div className="solution">{/* Display solution */}</div>}
    </div>
  );
};

export default MathSolver;
```

## 🔧 **Quick Test**

To test if your authentication is working, check the browser console for:

```
API Request Token: Present  // ← Should show "Present", not "Missing"
```

If it shows "Missing", then your frontend is not including the authentication token.

## 🎯 **Summary**

The CORS error is actually an **authentication error**. Your frontend needs to:

1. ✅ **Authenticate first** - Get a token from `/api/login` or `/api/register`
2. ✅ **Include the token** - Add `Authorization: Bearer <token>` to all requests
3. ✅ **Store the token** - Save it in localStorage or secure storage
4. ✅ **Handle token expiration** - Refresh or re-authenticate when needed

Once you include the authentication token, the CORS errors will disappear! 🎉
