# Frontend Update Guide - File Summarization API

## 🚨 Critical Fix Required

The frontend is currently using the **wrong URL format** for status checking, causing 404 errors. Here's the fix:

## 1. Status Endpoint URL Fix

### ❌ Current (Broken) Code:
```javascript
// This causes 404 errors
const statusUrl = `/api/status/${jobId}`;
```

### ✅ Fixed Code:
```javascript
// Use query parameters instead of path parameters
const statusUrl = `/api/status?job_id=${jobId}`;
```

## 2. Complete Frontend Implementation

### Updated File Summarization Service

```javascript
class FileSummarizationService {
  constructor(baseUrl = 'http://localhost:8000/api', token) {
    this.baseUrl = baseUrl;
    this.token = token;
  }

  // 1. Upload File
  async uploadFile(file) {
    const formData = new FormData();
    formData.append('file', file);

    const response = await fetch(`${this.baseUrl}/files/upload`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json'
      },
      body: formData
    });

    if (!response.ok) {
      throw new Error(`Upload failed: ${response.status}`);
    }

    const result = await response.json();
    return result.file_upload.id; // Return file_id
  }

  // 2. Start Summarization
  async summarizeFile(fileId, options = {}) {
    const requestData = {
      file_id: fileId.toString(),
      options: JSON.stringify({
        language: options.language || 'en',
        format: options.format || 'detailed',
        focus: options.focus || 'summary',
        include_formatting: options.include_formatting || true,
        max_pages: options.max_pages || 10
      })
    };

    const response = await fetch(`${this.baseUrl}/summarize/async/file`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error(`Summarization failed: ${response.status}`);
    }

    const result = await response.json();
    return result.job_id;
  }

  // 3. Check Job Status (FIXED URL)
  async checkStatus(jobId) {
    // ✅ FIXED: Use query parameter instead of path parameter
    const response = await fetch(`${this.baseUrl}/status?job_id=${jobId}`, {
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json'
      }
    });

    if (!response.ok) {
      throw new Error(`Status check failed: ${response.status}`);
    }

    return await response.json();
  }

  // 4. Get Result
  async getResult(jobId) {
    const response = await fetch(`${this.baseUrl}/result?job_id=${jobId}`, {
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json'
      }
    });

    if (!response.ok) {
      throw new Error(`Result fetch failed: ${response.status}`);
    }

    return await response.json();
  }

  // 5. Complete Workflow
  async summarizeFileComplete(file, options = {}) {
    try {
      // Step 1: Upload file
      console.log('📤 Uploading file...');
      const fileId = await this.uploadFile(file);
      console.log('✅ File uploaded, ID:', fileId);

      // Step 2: Start summarization
      console.log('🤖 Starting summarization...');
      const jobId = await this.summarizeFile(fileId, options);
      console.log('✅ Summarization started, Job ID:', jobId);

      // Step 3: Poll for completion
      console.log('⏳ Waiting for completion...');
      const result = await this.pollForCompletion(jobId);
      console.log('✅ Summarization completed!');

      return result;
    } catch (error) {
      console.error('❌ Summarization failed:', error);
      throw error;
    }
  }

  // 6. Poll for Completion
  async pollForCompletion(jobId, maxAttempts = 60, interval = 2000) {
    for (let attempt = 0; attempt < maxAttempts; attempt++) {
      const status = await this.checkStatus(jobId);
      
      console.log(`Status check ${attempt + 1}: ${status.status} (${status.progress}%)`);
      
      if (status.status === 'completed') {
        return await this.getResult(jobId);
      }
      
      if (status.status === 'failed') {
        throw new Error(`Summarization failed: ${status.error}`);
      }
      
      // Wait before next check
      await new Promise(resolve => setTimeout(resolve, interval));
    }
    
    throw new Error('Summarization timed out');
  }
}
```

## 3. React Hook Implementation

```javascript
import { useState, useCallback } from 'react';

export const useFileSummarization = (token) => {
  const [isLoading, setIsLoading] = useState(false);
  const [progress, setProgress] = useState(0);
  const [status, setStatus] = useState(null);
  const [result, setResult] = useState(null);
  const [error, setError] = useState(null);

  const summarizeFile = useCallback(async (file, options = {}) => {
    setIsLoading(true);
    setError(null);
    setProgress(0);
    setStatus('uploading');

    try {
      const service = new FileSummarizationService('http://localhost:8000/api', token);
      
      // Upload file
      setStatus('uploading');
      const fileId = await service.uploadFile(file);
      setProgress(25);

      // Start summarization
      setStatus('processing');
      const jobId = await service.summarizeFile(fileId, options);
      setProgress(50);

      // Poll for completion
      const result = await service.pollForCompletion(jobId, 60, 2000, (status) => {
        setProgress(50 + (status.progress * 0.5));
        setStatus(status.stage);
      });

      setResult(result);
      setProgress(100);
      setStatus('completed');
      
      return result;
    } catch (err) {
      setError(err.message);
      setStatus('failed');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, [token]);

  return {
    summarizeFile,
    isLoading,
    progress,
    status,
    result,
    error
  };
};
```

## 4. React Component Example

```jsx
import React, { useState } from 'react';
import { useFileSummarization } from './hooks/useFileSummarization';

const FileSummarizationComponent = () => {
  const [file, setFile] = useState(null);
  const [token] = useState('YOUR_TOKEN_HERE'); // Replace with actual token
  
  const {
    summarizeFile,
    isLoading,
    progress,
    status,
    result,
    error
  } = useFileSummarization(token);

  const handleFileChange = (event) => {
    setFile(event.target.files[0]);
  };

  const handleSummarize = async () => {
    if (!file) return;

    try {
      await summarizeFile(file, {
        language: 'en',
        format: 'detailed',
        focus: 'summary'
      });
    } catch (err) {
      console.error('Summarization failed:', err);
    }
  };

  return (
    <div className="file-summarization">
      <h2>File Summarization</h2>
      
      <div className="upload-section">
        <input
          type="file"
          onChange={handleFileChange}
          accept=".pdf,.doc,.docx,.txt,.html,.htm"
        />
        <button 
          onClick={handleSummarize} 
          disabled={!file || isLoading}
        >
          {isLoading ? 'Processing...' : 'Summarize File'}
        </button>
      </div>

      {isLoading && (
        <div className="progress-section">
          <div className="progress-bar">
            <div 
              className="progress-fill" 
              style={{ width: `${progress}%` }}
            />
          </div>
          <p>Status: {status} ({Math.round(progress)}%)</p>
        </div>
      )}

      {error && (
        <div className="error">
          <p>Error: {error}</p>
        </div>
      )}

      {result && (
        <div className="result">
          <h3>Summarization Result</h3>
          <div className="result-content">
            <h4>Summary:</h4>
            <p>{result.summary}</p>
            
            {result.key_points && result.key_points.length > 0 && (
              <>
                <h4>Key Points:</h4>
                <ul>
                  {result.key_points.map((point, index) => (
                    <li key={index}>{point}</li>
                  ))}
                </ul>
              </>
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default FileSummarizationComponent;
```

## 5. CSS Styles

```css
.file-summarization {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
}

.upload-section {
  margin-bottom: 20px;
}

.upload-section input[type="file"] {
  margin-right: 10px;
}

.upload-section button {
  padding: 10px 20px;
  background-color: #007bff;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.upload-section button:disabled {
  background-color: #6c757d;
  cursor: not-allowed;
}

.progress-section {
  margin: 20px 0;
}

.progress-bar {
  width: 100%;
  height: 20px;
  background-color: #e9ecef;
  border-radius: 10px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background-color: #28a745;
  transition: width 0.3s ease;
}

.error {
  background-color: #f8d7da;
  color: #721c24;
  padding: 10px;
  border-radius: 4px;
  margin: 10px 0;
}

.result {
  background-color: #d4edda;
  padding: 20px;
  border-radius: 4px;
  margin-top: 20px;
}

.result-content h4 {
  margin-top: 15px;
  margin-bottom: 5px;
}

.result-content ul {
  margin-left: 20px;
}
```

## 6. Key Changes Made

### ✅ Fixed Issues:
1. **Status URL Format**: Changed from `/api/status/{jobId}` to `/api/status?job_id={jobId}`
2. **Result URL Format**: Changed from `/api/result/{jobId}` to `/api/result?job_id={jobId}`
3. **File ID Handling**: Ensure file_id is converted to string
4. **Options Handling**: Properly stringify options for the API
5. **Error Handling**: Comprehensive error handling throughout the workflow

### 🔧 Implementation Notes:
- The service now properly handles the universal file management system
- Status polling uses the correct query parameter format
- Progress tracking shows real-time updates
- Error states are properly handled and displayed

### 📋 API Endpoints Used:
- `POST /api/files/upload` - Upload file
- `POST /api/summarize/async/file` - Start summarization
- `GET /api/status?job_id={jobId}` - Check status (FIXED)
- `GET /api/result?job_id={jobId}` - Get result

This implementation should resolve the 404 errors and provide a complete file summarization workflow! 🚀


