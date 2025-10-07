# Frontend API Debug Guide

## 🔍 **Problem Identified**

Your frontend is getting an empty error response `{}` from the API client. The backend is working correctly, so the issue is in the frontend API client implementation.

## 🚀 **Solution: Enhanced API Client**

Here's an improved API client that will provide better error handling and debugging:

### **Enhanced ApiClient Implementation**

```typescript
// lib/api-client.ts
class ApiClient {
  private baseURL: string;
  private token: string | null = null;

  constructor(baseURL: string = 'http://localhost:8000') {
    this.baseURL = baseURL;
  }

  setToken(token: string) {
    this.token = token;
  }

  private async request<T>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<T> {
    const url = `${this.baseURL}${endpoint}`;
    
    const headers: HeadersInit = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...options.headers,
    };

    if (this.token) {
      headers['Authorization'] = `Bearer ${this.token}`;
    }

    console.log('API Request:', {
      url,
      method: options.method || 'GET',
      headers,
      body: options.body
    });

    try {
      const response = await fetch(url, {
        ...options,
        headers,
      });

      console.log('API Response:', {
        status: response.status,
        statusText: response.statusText,
        url: response.url,
        headers: Object.fromEntries(response.headers.entries())
      });

      // Handle different response types
      if (!response.ok) {
        let errorData;
        const contentType = response.headers.get('content-type');
        
        try {
          if (contentType?.includes('application/json')) {
            errorData = await response.json();
          } else {
            errorData = await response.text();
          }
        } catch (parseError) {
          errorData = { error: 'Failed to parse error response' };
        }

        console.error('API Error Response:', {
          status: response.status,
          statusText: response.statusText,
          url: url,
          errorData
        });

        throw new Error(`HTTP error! status: ${response.status}`, {
          cause: {
            status: response.status,
            statusText: response.statusText,
            url: url,
            errorData
          }
        });
      }

      // Parse successful response
      const contentType = response.headers.get('content-type');
      if (contentType?.includes('application/json')) {
        return await response.json();
      } else {
        return await response.text() as T;
      }

    } catch (error) {
      console.error('API Request Failed:', {
        url,
        error: error instanceof Error ? error.message : 'Unknown error',
        cause: error instanceof Error ? error.cause : undefined
      });
      
      throw error;
    }
  }

  // Enhanced methods with better error handling
  async post<T>(endpoint: string, data: any): Promise<T> {
    return this.request<T>(endpoint, {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  async get<T>(endpoint: string): Promise<T> {
    return this.request<T>(endpoint, {
      method: 'GET',
    });
  }

  // File upload method
  async uploadFile<T>(endpoint: string, formData: FormData): Promise<T> {
    const url = `${this.baseURL}${endpoint}`;
    
    const headers: HeadersInit = {
      'Accept': 'application/json',
    };

    if (this.token) {
      headers['Authorization'] = `Bearer ${this.token}`;
    }

    console.log('File Upload Request:', {
      url,
      method: 'POST',
      headers,
      formData: Array.from(formData.entries()).map(([key, value]) => ({
        key,
        value: value instanceof File ? `File: ${value.name}` : value
      }))
    });

    try {
      const response = await fetch(url, {
        method: 'POST',
        headers,
        body: formData,
      });

      console.log('File Upload Response:', {
        status: response.status,
        statusText: response.statusText,
        url: response.url
      });

      if (!response.ok) {
        let errorData;
        try {
          errorData = await response.json();
        } catch {
          errorData = await response.text();
        }

        console.error('File Upload Error:', {
          status: response.status,
          statusText: response.statusText,
          errorData
        });

        throw new Error(`File upload failed! status: ${response.status}`, {
          cause: {
            status: response.status,
            statusText: response.statusText,
            errorData
          }
        });
      }

      return await response.json();

    } catch (error) {
      console.error('File Upload Failed:', {
        url,
        error: error instanceof Error ? error.message : 'Unknown error'
      });
      
      throw error;
    }
  }
}

export default new ApiClient();
```

### **Enhanced PDF Summarizer Implementation**

```typescript
// app/(dashboard)/pdf-summarizer/create/page.tsx
import ApiClient from '@/lib/api-client';

const handleSummarize = async (data: any) => {
  try {
    console.log('Starting summarization with data:', data);
    
    // Validate required fields
    if (!data.content_type || !data.source) {
      throw new Error('Missing required fields: content_type and source');
    }

    // Make API call with enhanced error handling
    const response = await ApiClient.post('/api/summarize', {
      content_type: data.content_type,
      source: data.source,
      options: data.options || {
        mode: 'detailed',
        language: 'en',
        focus: 'summary'
      }
    });

    console.log('Summarization successful:', response);
    
    if (response.success) {
      // Handle successful response
      setSummary(response.data.summary);
      setMetadata(response.data.metadata);
      setAiResult(response.data.ai_result);
      
      // Use UI helpers if available
      if (response.ui_helpers) {
        setReadTime(response.ui_helpers.estimated_read_time);
        setWordCount(response.ui_helpers.word_count);
      }
    } else {
      throw new Error(response.message || 'Summarization failed');
    }

  } catch (error) {
    console.error('Summarization error:', error);
    
    // Enhanced error handling
    if (error instanceof Error) {
      if (error.cause) {
        console.error('Error details:', error.cause);
        setError(`API Error: ${error.cause.status} - ${error.cause.statusText}`);
      } else {
        setError(error.message);
      }
    } else {
      setError('An unexpected error occurred');
    }
  }
};

const handleFileUpload = async (file: File, contentType: string) => {
  try {
    console.log('Starting file upload:', { fileName: file.name, contentType });
    
    // Validate file first
    const formData = new FormData();
    formData.append('file', file);
    formData.append('content_type', contentType);
    
    // Upload file
    const response = await ApiClient.uploadFile('/api/summarize/upload', formData);
    
    console.log('File upload successful:', response);
    
    if (response.success) {
      setFileUpload(response.data.file_upload);
      setUploadStatus('completed');
      
      // Enable summarization
      setCanSummarize(true);
    } else {
      throw new Error(response.message || 'File upload failed');
    }

  } catch (error) {
    console.error('File upload error:', error);
    
    if (error instanceof Error) {
      setError(error.message);
    } else {
      setError('File upload failed');
    }
    
    setUploadStatus('error');
  }
};
```

### **Debugging Steps**

1. **Check Network Tab**: Open browser dev tools → Network tab to see actual HTTP requests
2. **Verify Authentication**: Ensure token is being sent in Authorization header
3. **Check Request Format**: Verify the request body matches the expected format
4. **Test API Directly**: Use curl or Postman to test the API directly

### **Common Issues & Solutions**

1. **Empty Error Response**: Usually means the response isn't JSON or the request failed before reaching the server
2. **CORS Issues**: Check if CORS headers are properly set
3. **Authentication**: Verify the token is valid and properly formatted
4. **Request Format**: Ensure Content-Type and request body are correct

### **Testing Commands**

```bash
# Test API directly with curl
curl -X POST http://localhost:8000/api/summarize \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "content_type": "text",
    "source": {
      "type": "text", 
      "data": "Test content"
    }
  }'
```

The backend is working perfectly - the issue is in your frontend API client implementation! 🚀
