# Async Summarize API Response Structure

## Overview
This document provides the complete response structure for the Async Summarize API, including the merged bundle + summary structure that the frontend receives.

## API Endpoints

### 1. Start Async Job
**POST** `/api/summarize/async`

#### Request Structure
```json
{
  "content_type": "link",
  "source": {
    "type": "url",
    "data": "https://www.youtube.com/watch?v=VIDEO_ID"
  },
  "options": {
    "mode": "detailed",
    "language": "en",
    "format": "bundle",
    "focus": "summary"
  }
}
```

#### Response Structure (202 Accepted)
```json
{
  "success": true,
  "message": "Summarization job started",
  "job_id": "c23606b4-d41a-4dd6-addd-1818fb13683f",
  "status": "pending",
  "poll_url": "http://localhost:8000/api/summarize/status/c23606b4-d41a-4dd6-addd-1818fb13683f",
  "result_url": "http://localhost:8000/api/summarize/result/c23606b4-d41a-4dd6-addd-1818fb13683f"
}
```

#### Error Response (422/500)
```json
{
  "success": false,
  "error": "Validation failed",
  "details": {
    "source.type": ["The source.type field is required."]
  }
}
```

---

### 2. Check Job Status
**GET** `/api/summarize/status/{jobId}`

#### Response Structure (200 OK)
```json
{
  "success": true,
  "data": {
    "id": "c23606b4-d41a-4dd6-addd-1818fb13683f",
    "tool_type": "summarize",
    "input": {
      "content_type": "link",
      "source": {
        "type": "url",
        "data": "https://www.youtube.com/watch?v=VIDEO_ID"
      }
    },
    "options": {
      "mode": "detailed",
      "language": "en"
    },
    "user_id": 1,
    "status": "running",
    "stage": "processing",
    "progress": 25,
    "created_at": "2025-10-21T12:13:24.000Z",
    "updated_at": "2025-10-21T12:13:42.000Z",
    "logs": [
      {
        "timestamp": "2025-10-21T12:13:24.000Z",
        "level": "info",
        "message": "Starting summarize processing",
        "data": {}
      }
    ],
    "result": null,
    "error": null,
    "metadata": {
      "processing_started_at": "2025-10-21T12:13:25.000Z",
      "processing_completed_at": null,
      "total_processing_time": null,
      "file_count": 0,
      "tokens_used": 0,
      "confidence_score": 0.0
    }
  }
}
```

#### Status Values
- `pending` - Job created, waiting to start
- `running` - Job in progress  
- `completed` - Job finished successfully
- `failed` - Job failed with error

#### Stage Values
- `initializing` - Setting up the job
- `processing` - Running the summarization
- `completed` - Job finished
- `failed` - Job failed

---

### 3. Get Job Result
**GET** `/api/summarize/result/{jobId}`

#### Response Structure (200 OK) - Job Completed
```json
{
  "success": true,
  "data": {
    "success": true,
    "summary": "This is a comprehensive summary of the YouTube video content. The video discusses important topics including...",
    "metadata": {
      "content_type": "youtube",
      "processing_time": "5-10 minutes",
      "tokens_used": 2500,
      "confidence": 0.95,
      "video_id": "XDNeGenHIM0",
      "title": "Video Title",
      "total_words": 1200,
      "language": "en"
    },
    "source_info": {
      "url": "https://www.youtube.com/watch?v=XDNeGenHIM0",
      "title": "Video Title",
      "description": "Video content extracted via transcription",
      "author": "Channel Name",
      "published_date": "2025-01-01",
      "word_count": 1200
    },
    "ai_result": {
      "id": 123,
      "title": "Generated Summary Title",
      "file_url": "https://example.com/download/summary.pdf",
      "created_at": "2025-10-21T12:15:00.000Z"
    }
  }
}
```

#### Response Structure (202 Accepted) - Job Not Completed
```json
{
  "success": true,
  "status": "running",
  "message": "Job not completed yet",
  "data": {
    "id": "c23606b4-d41a-4dd6-addd-1818fb13683f",
    "status": "running",
    "progress": 50,
    "stage": "processing",
    "error": null
  }
}
```

#### Error Response (404/500)
```json
{
  "success": false,
  "error": "Job not found"
}
```

---

## Merged Bundle + Summary Structure

### Complete Response Structure
When a job is completed, the frontend receives a merged response that combines:

1. **Transcriber Bundle Data** (from YouTube Transcriber microservice)
2. **AI Summary** (from AI Manager microservice)
3. **Metadata** (processing information)

### Detailed Structure
```json
{
  "success": true,
  "data": {
    "success": true,
    "summary": "AI-generated summary of the video content...",
    "metadata": {
      "content_type": "youtube",
      "processing_time": "5-10 minutes",
      "tokens_used": 2500,
      "confidence": 0.95,
      "video_id": "XDNeGenHIM0",
      "title": "Video Title",
      "total_words": 1200,
      "language": "en"
    },
    "source_info": {
      "url": "https://www.youtube.com/watch?v=XDNeGenHIM0",
      "title": "Video Title",
      "description": "Video content extracted via transcription",
      "author": "Channel Name",
      "published_date": "2025-01-01",
      "word_count": 1200
    },
    "ai_result": {
      "id": 123,
      "title": "Generated Summary Title",
      "file_url": "https://example.com/download/summary.pdf",
      "created_at": "2025-10-21T12:15:00.000Z"
    },
    "bundle": {
      "video_id": "XDNeGenHIM0",
      "language": "en",
      "format": "bundle_with_summary",
      "article": "Full article text from transcriber...",
      "summary": "AI-generated summary...",
      "json": {
        "segments": [
          {
            "text": "Why don't we get to AIPAC?",
            "start": 0.0,
            "duration": 1.12
          },
          {
            "text": "Yeah, so some extraordinary political moments...",
            "start": 1.12,
            "duration": 4.72
          }
        ]
      },
      "srt": "1\n00:00:00,000 --> 00:00:01,120\nWhy don't we get to AIPAC?\n\n2\n00:00:01,120 --> 00:00:05,840\nYeah, so some extraordinary political moments...",
      "meta": {
        "ai_summary": "AI-generated summary...",
        "ai_model_used": "gpt-4",
        "ai_tokens_used": 2500,
        "ai_confidence_score": 0.95,
        "processing_time": "5-10 minutes",
        "merged_at": "2025-10-21T12:15:00.000Z"
      }
    }
  }
}
```

### Bundle Structure Breakdown

#### `bundle.article`
- **Type**: String
- **Description**: Full formatted article text from transcriber
- **Length**: ~11,000+ characters for typical videos
- **Content**: Clean, readable article format of video content

#### `bundle.json.segments`
- **Type**: Array of Objects
- **Description**: Timestamped segments with text and timing
- **Structure**:
  ```json
  {
    "text": "Segment text content",
    "start": 0.0,
    "duration": 1.12
  }
  ```

#### `bundle.srt`
- **Type**: String
- **Description**: SRT subtitle format
- **Format**: Standard SRT with timestamps and text

#### `bundle.meta`
- **Type**: Object
- **Description**: Combined metadata from transcriber and AI processing
- **Fields**:
  - `ai_summary`: AI-generated summary
  - `ai_model_used`: AI model used for summarization
  - `ai_tokens_used`: Tokens consumed by AI
  - `ai_confidence_score`: AI confidence in summary
  - `processing_time`: Total processing time
  - `merged_at`: Timestamp when bundle was merged with summary

---

## Frontend Error Fix

### Common Frontend Error
If you're getting errors like:
```
Cannot read properties of undefined (reading 'logs')
Cannot read properties of undefined (reading 'progress')
```

This is because the frontend is trying to access properties on the wrong object. The correct way to access job status properties is:

```javascript
// ❌ WRONG - This will cause errors
const { data } = await response.json();
console.log(data.logs); // undefined
console.log(data.progress); // undefined

// ✅ CORRECT - Access properties from the job data
const { data: jobData } = await response.json();
console.log(jobData.logs); // Array of log entries
console.log(jobData.progress); // Number (0-100)
console.log(jobData.status); // String (pending/running/completed/failed)
console.log(jobData.stage); // String (initializing/processing/completed/failed)
```

### Fixed Frontend Implementation
```javascript
const pollJobStatus = async (pollUrl) => {
  const response = await fetch(pollUrl, {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  
  const { data: jobData } = await response.json();
  
  // Access properties correctly
  console.log('Job Status:', jobData.status);
  console.log('Progress:', jobData.progress);
  console.log('Stage:', jobData.stage);
  console.log('Logs:', jobData.logs);
  
  if (jobData.status === 'completed') {
    return jobData.result;
  } else if (jobData.status === 'failed') {
    throw new Error(jobData.error);
  }
  
  return null; // Still processing
};
```

---

## Frontend Implementation Example

### JavaScript Implementation
```javascript
class AsyncSummarizer {
  constructor(apiBaseUrl, authToken) {
    this.apiBaseUrl = apiBaseUrl;
    this.authToken = authToken;
  }

  async startJob(videoUrl, options = {}) {
    const response = await fetch(`${this.apiBaseUrl}/api/summarize/async`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.authToken}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        content_type: 'link',
        source: {
          type: 'url',
          data: videoUrl
        },
        options: {
          mode: 'detailed',
          language: 'en',
          ...options
        }
      })
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    return await response.json();
  }

  async pollStatus(pollUrl) {
    const response = await fetch(pollUrl, {
      headers: {
        'Authorization': `Bearer ${this.authToken}`
      }
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    return await response.json();
  }

  async getResult(resultUrl) {
    const response = await fetch(resultUrl, {
      headers: {
        'Authorization': `Bearer ${this.authToken}`
      }
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    return await response.json();
  }

  async summarizeVideo(videoUrl, options = {}) {
    try {
      // 1. Start async job
      const startResult = await this.startJob(videoUrl, options);
      console.log('Job started:', startResult);

      // 2. Poll for completion
      const pollInterval = 3000; // 3 seconds
      const maxAttempts = 100; // 5 minutes max
      let attempts = 0;

      while (attempts < maxAttempts) {
        await new Promise(resolve => setTimeout(resolve, pollInterval));
        
        const statusResult = await this.pollStatus(startResult.poll_url);
        const { data: jobData } = statusResult;

        console.log(`Status: ${jobData.status} (${jobData.progress}%)`);

        if (jobData.status === 'completed') {
          // 3. Get final result
          const result = await this.getResult(startResult.result_url);
          return result.data;
        } else if (jobData.status === 'failed') {
          throw new Error(`Job failed: ${jobData.error}`);
        }

        attempts++;
      }

      throw new Error('Job timeout - exceeded maximum polling attempts');

    } catch (error) {
      console.error('Summarization failed:', error);
      throw error;
    }
  }
}

// Usage Example
const summarizer = new AsyncSummarizer('http://localhost:8000', 'your-auth-token');

summarizer.summarizeVideo('https://www.youtube.com/watch?v=VIDEO_ID')
  .then(result => {
    console.log('Summary:', result.summary);
    console.log('Bundle:', result.bundle);
    console.log('Metadata:', result.metadata);
  })
  .catch(error => {
    console.error('Error:', error);
  });
```

### React Hook Example
```javascript
import { useState, useEffect } from 'react';

const useAsyncSummarizer = (apiBaseUrl, authToken) => {
  const [status, setStatus] = useState('idle');
  const [progress, setProgress] = useState(0);
  const [result, setResult] = useState(null);
  const [error, setError] = useState(null);

  const summarizeVideo = async (videoUrl, options = {}) => {
    try {
      setStatus('starting');
      setProgress(0);
      setError(null);

      // Start job
      const startResponse = await fetch(`${apiBaseUrl}/api/summarize/async`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authToken}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          content_type: 'link',
          source: { type: 'url', data: videoUrl },
          options: { mode: 'detailed', language: 'en', ...options }
        })
      });

      const startData = await startResponse.json();
      setStatus('processing');

      // Poll for status
      const pollStatus = async () => {
        const statusResponse = await fetch(startData.poll_url, {
          headers: { 'Authorization': `Bearer ${authToken}` }
        });
        const statusData = await statusResponse.json();
        
        setProgress(statusData.data.progress);

        if (statusData.data.status === 'completed') {
          const resultResponse = await fetch(startData.result_url, {
            headers: { 'Authorization': `Bearer ${authToken}` }
          });
          const resultData = await resultResponse.json();
          
          setResult(resultData.data);
          setStatus('completed');
        } else if (statusData.data.status === 'failed') {
          setError(new Error(statusData.data.error));
          setStatus('failed');
        } else {
          setTimeout(pollStatus, 3000);
        }
      };

      pollStatus();

    } catch (err) {
      setError(err);
      setStatus('failed');
    }
  };

  return { status, progress, result, error, summarizeVideo };
};

// Usage in Component
const SummarizerComponent = () => {
  const { status, progress, result, error, summarizeVideo } = useAsyncSummarizer(
    'http://localhost:8000',
    'your-auth-token'
  );

  const handleSummarize = () => {
    summarizeVideo('https://www.youtube.com/watch?v=VIDEO_ID');
  };

  return (
    <div>
      <button onClick={handleSummarize} disabled={status === 'processing'}>
        {status === 'processing' ? `Processing... ${progress}%` : 'Summarize Video'}
      </button>
      
      {result && (
        <div>
          <h3>Summary</h3>
          <p>{result.summary}</p>
          
          <h3>Bundle Info</h3>
          <p>Video ID: {result.bundle?.video_id}</p>
          <p>Language: {result.bundle?.language}</p>
          <p>Segments: {result.bundle?.json?.segments?.length}</p>
        </div>
      )}
      
      {error && <div style={{color: 'red'}}>Error: {error.message}</div>}
    </div>
  );
};
```

---

## Error Handling

### Common Error Responses

#### Validation Errors (422)
```json
{
  "success": false,
  "error": "Validation failed",
  "details": {
    "source.type": ["The source.type field is required."],
    "source.data": ["The source.data field is required."]
  }
}
```

#### Authentication Errors (401)
```json
{
  "success": false,
  "error": "Invalid token format"
}
```

#### Job Processing Errors (500)
```json
{
  "success": false,
  "error": "Failed to start summarization job: Connection timeout"
}
```

#### Job Not Found (404)
```json
{
  "success": false,
  "error": "Job not found"
}
```

---

## Rate Limiting and Best Practices

### Rate Limits
- **Job Creation**: 10 requests per minute per user
- **Status Polling**: 60 requests per minute per user
- **Result Retrieval**: 30 requests per minute per user

### Best Practices
1. **Polling Interval**: Use 3-5 second intervals for status polling
2. **Timeout**: Set maximum polling time of 10 minutes
3. **Error Handling**: Always handle network errors and timeouts
4. **Caching**: Cache results to avoid unnecessary API calls
5. **User Feedback**: Show progress indicators and estimated completion times

### Recommended Polling Strategy
```javascript
const pollWithBackoff = async (pollUrl, maxAttempts = 100) => {
  let attempts = 0;
  let interval = 1000; // Start with 1 second
  
  while (attempts < maxAttempts) {
    try {
      const response = await fetch(pollUrl);
      const data = await response.json();
      
      if (data.data.status === 'completed') {
        return data;
      } else if (data.data.status === 'failed') {
        throw new Error(data.data.error);
      }
      
      // Exponential backoff with jitter
      interval = Math.min(interval * 1.5, 10000);
      const jitter = Math.random() * 1000;
      
      await new Promise(resolve => setTimeout(resolve, interval + jitter));
      attempts++;
      
    } catch (error) {
      if (attempts >= maxAttempts - 1) throw error;
      await new Promise(resolve => setTimeout(resolve, 5000));
      attempts++;
    }
  }
  
  throw new Error('Polling timeout');
};
```

---

## Testing

### Test with cURL
```bash
# Start job
curl -X POST http://localhost:8000/api/summarize/async \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "content_type": "link",
    "source": {
      "type": "url",
      "data": "https://www.youtube.com/watch?v=VIDEO_ID"
    },
    "options": {
      "mode": "detailed",
      "language": "en"
    }
  }'

# Check status
curl -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/summarize/status/JOB_ID

# Get result
curl -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/summarize/result/JOB_ID
```

This comprehensive structure provides everything needed to implement the async summarize API in any frontend framework! 🚀
