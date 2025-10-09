# 🎯 Frontend Integration - Final Guide

## ✅ **Problem Solved!**

The CORS issue has been completely resolved. The math API is now working correctly with proper authentication and CORS headers.

## 🔧 **What Was Fixed**

1. **Authentication Middleware**: Modified `app/Http/Middleware/Authenticate.php` to return JSON responses for API requests instead of redirects
2. **CORS Headers**: Added proper CORS headers to all math endpoints
3. **Route Structure**: Both `/api/math/*` and `/api/client/math/*` endpoints are working

## 🚀 **How to Use the Math API**

### **1. Authentication Required**
All math endpoints require a Bearer token in the Authorization header:

```javascript
const headers = {
  'Authorization': 'Bearer YOUR_TOKEN_HERE',
  'Content-Type': 'application/json',
  'Accept': 'application/json',
  'Origin': 'http://localhost:3000'
};
```

### **2. Available Endpoints**

#### **Main API Endpoints:**
- `POST /api/math/solve` - Solve math problems
- `GET /api/math/problems` - Get math problems list
- `GET /api/math/history` - Get user's math history
- `GET /api/math/stats` - Get math statistics

#### **Client API Endpoints (for frontend compatibility):**
- `POST /api/client/math/generate` - Alias for solve
- `POST /api/client/math/help` - Alias for solve
- `GET /api/client/math/history` - Get user's math history
- `GET /api/client/math/stats` - Get math statistics

### **3. Complete Next.js Example**

#### **API Client (lib/math-api.ts)**
```typescript
const API_BASE_URL = 'http://localhost:8000/api';

export interface MathProblem {
  problem_text: string;
  subject_area: string;
  difficulty_level: string;
  problem_type?: string;
}

export interface MathSolution {
  id: number;
  solution_method: string;
  step_by_step_solution: string;
  final_answer: string;
  explanation: string;
  verification: string;
  created_at: string;
}

export interface MathApiResponse {
  math_problem: {
    id: number;
    problem_text: string;
    problem_image: string | null;
    subject_area: string;
    difficulty_level: string;
    created_at: string;
  };
  math_solution: MathSolution;
  ai_result: {
    id: number;
    title: string;
    file_url: string;
    created_at: string;
  };
}

class MathApiClient {
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
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Origin': 'http://localhost:3000',
        ...options.headers,
      },
    };

    console.log('API Request URL:', url);
    console.log('API Request Token:', this.token ? 'Present' : 'Missing');
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

  async solveMathProblem(problem: MathProblem): Promise<MathApiResponse> {
    return this.request<MathApiResponse>('/math/solve', {
      method: 'POST',
      body: JSON.stringify(problem),
    });
  }

  async getMathHistory(): Promise<any[]> {
    return this.request<any[]>('/math/history');
  }

  async getMathStats(): Promise<any> {
    return this.request<any>('/math/stats');
  }

  async getMathProblems(): Promise<any> {
    return this.request<any>('/math/problems');
  }
}

export default MathApiClient;
```

#### **React Component (components/MathSolver.tsx)**
```typescript
import React, { useState, useEffect } from 'react';
import MathApiClient, { MathProblem, MathApiResponse } from '../lib/math-api';

interface MathSolverProps {
  token: string;
}

const MathSolver: React.FC<MathSolverProps> = ({ token }) => {
  const [problem, setProblem] = useState('');
  const [subject, setSubject] = useState('general');
  const [difficulty, setDifficulty] = useState('intermediate');
  const [solution, setSolution] = useState<MathApiResponse | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [history, setHistory] = useState<any[]>([]);

  const apiClient = new MathApiClient('http://localhost:8000/api', token);

  useEffect(() => {
    loadHistory();
  }, []);

  const loadHistory = async () => {
    try {
      const historyData = await apiClient.getMathHistory();
      setHistory(historyData);
    } catch (err) {
      console.error('Failed to load history:', err);
    }
  };

  const handleSolve = async () => {
    if (!problem.trim()) return;

    setLoading(true);
    setError(null);
    setSolution(null);

    try {
      const mathProblem: MathProblem = {
        problem_text: problem,
        subject_area: subject,
        difficulty_level: difficulty,
        problem_type: 'text'
      };

      console.log('Attempting to solve math problem:', problem);
      const result = await apiClient.solveMathProblem(mathProblem);
      setSolution(result);
      loadHistory(); // Refresh history
    } catch (err) {
      console.error('API Error:', err);
      setError(err instanceof Error ? err.message : 'Failed to solve problem');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="math-solver">
      <h2>Math Problem Solver</h2>
      
      <div className="input-section">
        <textarea
          value={problem}
          onChange={(e) => setProblem(e.target.value)}
          placeholder="Enter your math problem..."
          rows={3}
        />
        
        <select value={subject} onChange={(e) => setSubject(e.target.value)}>
          <option value="general">General</option>
          <option value="algebra">Algebra</option>
          <option value="geometry">Geometry</option>
          <option value="calculus">Calculus</option>
        </select>
        
        <select value={difficulty} onChange={(e) => setDifficulty(e.target.value)}>
          <option value="beginner">Beginner</option>
          <option value="intermediate">Intermediate</option>
          <option value="advanced">Advanced</option>
        </select>
        
        <button onClick={handleSolve} disabled={loading || !problem.trim()}>
          {loading ? 'Solving...' : 'Solve Problem'}
        </button>
      </div>

      {error && (
        <div className="error">
          <h3>Error:</h3>
          <p>{error}</p>
        </div>
      )}

      {solution && (
        <div className="solution">
          <h3>Solution:</h3>
          <div className="problem-info">
            <h4>Problem: {solution.math_problem.problem_text}</h4>
            <p><strong>Subject:</strong> {solution.math_problem.subject_area}</p>
            <p><strong>Difficulty:</strong> {solution.math_problem.difficulty_level}</p>
          </div>
          
          <div className="solution-details">
            <h4>Solution Method:</h4>
            <p>{solution.math_solution.solution_method}</p>
            
            <h4>Step-by-Step Solution:</h4>
            <div dangerouslySetInnerHTML={{ __html: solution.math_solution.step_by_step_solution }} />
            
            <h4>Final Answer:</h4>
            <p><strong>{solution.math_solution.final_answer}</strong></p>
            
            <h4>Explanation:</h4>
            <p>{solution.math_solution.explanation}</p>
          </div>
        </div>
      )}

      <div className="history">
        <h3>Recent Problems</h3>
        {history.length === 0 ? (
          <p>No problems solved yet.</p>
        ) : (
          <ul>
            {history.map((item, index) => (
              <li key={index}>
                <strong>{item.problem_text}</strong> - {item.subject_area}
                <br />
                <small>Solved: {new Date(item.created_at).toLocaleString()}</small>
              </li>
            ))}
          </ul>
        )}
      </div>
    </div>
  );
};

export default MathSolver;
```

#### **Usage in App (pages/index.tsx)**
```typescript
import React from 'react';
import MathSolver from '../components/MathSolver';

const HomePage: React.FC = () => {
  // Get token from your auth system
  const token = 'YOUR_AUTH_TOKEN_HERE'; // Replace with actual token

  return (
    <div>
      <h1>Math Problem Solver</h1>
      <MathSolver token={token} />
    </div>
  );
};

export default HomePage;
```

## 🔑 **Authentication Setup**

### **1. Get Authentication Token**
You need to authenticate with the API first:

```typescript
// Login to get token
const login = async (email: string, password: string) => {
  const response = await fetch('http://localhost:8000/api/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({ email, password }),
  });
  
  const data = await response.json();
  return data.token; // Save this token
};
```

### **2. Store Token Securely**
```typescript
// Store token in localStorage or secure storage
localStorage.setItem('auth_token', token);

// Retrieve token
const token = localStorage.getItem('auth_token');
```

## 🎯 **Key Points**

1. **Authentication Required**: All math endpoints require a valid Bearer token
2. **CORS Headers**: Properly configured for `http://localhost:3000`
3. **Error Handling**: Handle authentication errors gracefully
4. **Token Management**: Store and refresh tokens as needed

## ✅ **Testing**

The API is now fully functional with:
- ✅ Proper CORS headers
- ✅ Authentication handling
- ✅ JSON responses instead of redirects
- ✅ Both main and client endpoints working

Your frontend should now work without CORS errors! 🎉
