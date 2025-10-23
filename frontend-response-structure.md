# Frontend Response Structure for Async Summarize API

## Complete Response Object Structure

Based on the backend code analysis, here's the exact structure the frontend receives:

### 1. Start Async Job Response
**POST** `/api/summarize/async`

```json
{
  "success": true,
  "message": "Summarization job started",
  "job_id": "b649feef-4e19-4753-8251-5dbbc3da50c1",
  "status": "pending",
  "poll_url": "http://localhost:8000/api/summarize/status/b649feef-4e19-4753-8251-5dbbc3da50c1",
  "result_url": "http://localhost:8000/api/summarize/result/b649feef-4e19-4753-8251-5dbbc3da50c1"
}
```

### 2. Job Status Response
**GET** `/api/summarize/status/{jobId}`

```json
{
  "success": true,
  "data": {
    "id": "b649feef-4e19-4753-8251-5dbbc3da50c1",
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
      "language": "en",
      "format": "bundle",
      "focus": "summary"
    },
    "user_id": 1,
    "status": "running",
    "stage": "processing",
    "progress": 25,
    "created_at": "2025-10-21T12:41:30.000Z",
    "updated_at": "2025-10-21T12:41:45.000Z",
    "logs": [
      {
        "timestamp": "2025-10-21T12:41:30.000Z",
        "level": "info",
        "message": "Starting summarize processing",
        "data": {}
      }
    ],
    "result": null,
    "error": null,
    "metadata": {
      "processing_started_at": "2025-10-21T12:41:32.000Z",
      "processing_completed_at": null,
      "total_processing_time": null,
      "file_count": 0,
      "tokens_used": 0,
      "confidence_score": 0.0
    }
  }
}
```

### 3. Job Result Response (SUCCESS)
**GET** `/api/summarize/result/{jobId}`

```json
{
  "success": true,
  "data": {
    "success": true,
    "summary": "AI-generated summary of the video content...",
    "ai_result": {
      "id": 123,
      "title": "Generated Summary Title",
      "file_url": "https://example.com/download/summary.pdf",
      "created_at": "2025-10-21T12:45:00.000Z"
    },
    "metadata": [
      {
        "content_type": "youtube",
        "processing_time": "5-10 minutes",
        "tokens_used": 2500,
        "confidence": 0.95,
        "video_id": "XDNeGenHIM0",
        "title": "Video Title",
        "total_words": 1200,
        "language": "en"
      }
    ],
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
        "merged_at": "2025-10-21T12:45:00.000Z"
      }
    }
  }
}
```

### 4. Job Result Response (ERROR)
**GET** `/api/summarize/result/{jobId}`

```json
{
  "success": true,
  "data": {
    "success": false,
    "error": "Undefined array key \"json\"",
    "summary": null,
    "ai_result": null,
    "metadata": []
  }
}
```

### 5. Job Not Completed Response
**GET** `/api/summarize/result/{jobId}` (when job is still running)

```json
{
  "success": true,
  "status": "running",
  "message": "Job not completed yet",
  "data": {
    "id": "b649feef-4e19-4753-8251-5dbbc3da50c1",
    "status": "running",
    "progress": 50,
    "stage": "processing",
    "error": null
  }
}
```

## Frontend Implementation

### TypeScript Interfaces

```typescript
interface JobStartResponse {
  success: boolean;
  message: string;
  job_id: string;
  status: string;
  poll_url: string;
  result_url: string;
}

interface JobStatusData {
  id: string;
  tool_type: string;
  input: {
    content_type: string;
    source: {
      type: string;
      data: string;
    };
  };
  options: {
    mode: string;
    language: string;
    format: string;
    focus: string;
  };
  user_id: number;
  status: 'pending' | 'running' | 'completed' | 'failed';
  stage: 'initializing' | 'processing' | 'completed' | 'failed';
  progress: number;
  created_at: string;
  updated_at: string;
  logs: Array<{
    timestamp: string;
    level: string;
    message: string;
    data: any;
  }>;
  result: any;
  error: string | null;
  metadata: {
    processing_started_at: string | null;
    processing_completed_at: string | null;
    total_processing_time: number | null;
    file_count: number;
    tokens_used: number;
    confidence_score: number;
  };
}

interface JobStatusResponse {
  success: boolean;
  data: JobStatusData;
}

interface BundleSegment {
  text: string;
  start: number;
  duration: number;
}

interface BundleData {
  video_id: string;
  language: string;
  format: string;
  article: string;
  summary: string;
  json: {
    segments: BundleSegment[];
  };
  srt: string;
  meta: {
    ai_summary: string;
    ai_model_used: string;
    ai_tokens_used: number;
    ai_confidence_score: number;
    processing_time: string;
    merged_at: string;
  };
}

interface JobResultData {
  success: boolean;
  summary?: string;
  ai_result?: {
    id: number;
    title: string;
    file_url: string;
    created_at: string;
  };
  metadata?: any[];
  bundle?: BundleData;
  error?: string;
}

interface JobResultResponse {
  success: boolean;
  data: JobResultData;
}
```

### React Hook Implementation

```typescript
import { useState, useEffect, useCallback } from 'react';

interface UseAsyncSummarizerReturn {
  status: 'idle' | 'starting' | 'processing' | 'completed' | 'failed';
  progress: number;
  stage: string;
  result: JobResultData | null;
  error: string | null;
  logs: Array<{ timestamp: string; level: string; message: string; data: any }>;
  startJob: (videoUrl: string, options?: any) => Promise<void>;
  reset: () => void;
}

export const useAsyncSummarizer = (apiBaseUrl: string, authToken: string): UseAsyncSummarizerReturn => {
  const [status, setStatus] = useState<'idle' | 'starting' | 'processing' | 'completed' | 'failed'>('idle');
  const [progress, setProgress] = useState(0);
  const [stage, setStage] = useState('');
  const [result, setResult] = useState<JobResultData | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [logs, setLogs] = useState<Array<{ timestamp: string; level: string; message: string; data: any }>>([]);

  const startJob = useCallback(async (videoUrl: string, options: any = {}) => {
    try {
      setStatus('starting');
      setProgress(0);
      setError(null);
      setResult(null);
      setLogs([]);

      // Start async job
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

      if (!startResponse.ok) {
        throw new Error(`HTTP ${startResponse.status}: ${startResponse.statusText}`);
      }

      const startData: JobStartResponse = await startResponse.json();
      setStatus('processing');

      // Poll for completion
      const pollInterval = 3000; // 3 seconds
      const maxAttempts = 100; // 5 minutes max
      let attempts = 0;

      const pollStatus = async (): Promise<void> => {
        try {
          const statusResponse = await fetch(startData.poll_url, {
            headers: { 'Authorization': `Bearer ${authToken}` }
          });

          if (!statusResponse.ok) {
            throw new Error(`HTTP ${statusResponse.status}: ${statusResponse.statusText}`);
          }

          const statusData: JobStatusResponse = await statusResponse.json();
          const jobData = statusData.data;

          setProgress(jobData.progress);
          setStage(jobData.stage);
          setLogs(jobData.logs);

          if (jobData.status === 'completed') {
            // Get final result
            const resultResponse = await fetch(startData.result_url, {
              headers: { 'Authorization': `Bearer ${authToken}` }
            });

            if (!resultResponse.ok) {
              throw new Error(`HTTP ${resultResponse.status}: ${resultResponse.statusText}`);
            }

            const resultData: JobResultResponse = await resultResponse.json();
            setResult(resultData.data);
            setStatus('completed');
          } else if (jobData.status === 'failed') {
            setError(jobData.error || 'Job failed');
            setStatus('failed');
          } else if (attempts < maxAttempts) {
            // Continue polling
            setTimeout(pollStatus, pollInterval);
            attempts++;
          } else {
            throw new Error('Job timeout - exceeded maximum polling attempts');
          }
        } catch (err) {
          setError(err instanceof Error ? err.message : 'Unknown error');
          setStatus('failed');
        }
      };

      pollStatus();

    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unknown error');
      setStatus('failed');
    }
  }, [apiBaseUrl, authToken]);

  const reset = useCallback(() => {
    setStatus('idle');
    setProgress(0);
    setStage('');
    setResult(null);
    setError(null);
    setLogs([]);
  }, []);

  return {
    status,
    progress,
    stage,
    result,
    error,
    logs,
    startJob,
    reset
  };
};
```

### Usage Example

```typescript
import React from 'react';
import { useAsyncSummarizer } from './hooks/useAsyncSummarizer';

const YouTubeSummarizer: React.FC = () => {
  const { status, progress, stage, result, error, logs, startJob, reset } = useAsyncSummarizer(
    'http://localhost:8000',
    'your-auth-token'
  );

  const handleSummarize = async () => {
    await startJob('https://www.youtube.com/watch?v=VIDEO_ID', {
      mode: 'detailed',
      language: 'en'
    });
  };

  return (
    <div>
      <button onClick={handleSummarize} disabled={status === 'processing'}>
        {status === 'processing' ? `Processing... ${progress}%` : 'Summarize Video'}
      </button>

      {status === 'processing' && (
        <div>
          <p>Stage: {stage}</p>
          <div className="progress-bar">
            <div style={{ width: `${progress}%` }}></div>
          </div>
        </div>
      )}

      {result && (
        <div>
          <h3>Summary</h3>
          <p>{result.summary}</p>
          
          {result.bundle && (
            <div>
              <h3>Bundle Info</h3>
              <p>Video ID: {result.bundle.video_id}</p>
              <p>Language: {result.bundle.language}</p>
              <p>Segments: {result.bundle.json.segments.length}</p>
              <p>Article Length: {result.bundle.article.length} characters</p>
            </div>
          )}
        </div>
      )}

      {error && <div style={{ color: 'red' }}>Error: {error}</div>}

      {logs.length > 0 && (
        <div>
          <h3>Processing Logs</h3>
          <ul>
            {logs.map((log, index) => (
              <li key={index}>
                [{log.level}] {log.message} - {new Date(log.timestamp).toLocaleTimeString()}
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  );
};

export default YouTubeSummarizer;
```

## Key Points for Frontend Implementation

1. **Status Polling**: Poll every 3 seconds until job is completed or failed
2. **Progress Tracking**: Use `jobData.progress` (0-100) and `jobData.stage` for UI updates
3. **Error Handling**: Check `jobData.status` for 'failed' and `jobData.error` for error message
4. **Result Access**: When completed, access `resultData.data` for the final result
5. **Bundle Data**: The `bundle` object contains the merged transcriber + AI summary data
6. **Logs**: Use `jobData.logs` array for processing logs and debugging

This structure provides everything needed to implement a complete async summarize frontend! 🚀
