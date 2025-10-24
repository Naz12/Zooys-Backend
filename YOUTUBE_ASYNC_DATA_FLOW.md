# YouTube Async Summarization Data Flow

## Complete Step-by-Step Data Flow for `/summarize/async/youtube`

### **1. API Request Entry Point**
```
POST /api/summarize/async/youtube
Headers: Authorization: Bearer <token>
Body: {
  "url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
  "options": {
    "format": "bundle",
    "mode": "detailed",
    "language": "en"
  }
}
```

### **2. Route Handler (`routes/api.php`)**
```php
Route::post('/summarize/async/youtube', function (Request $request) {
    // Step 2.1: Extract and validate Bearer token
    $token = $request->bearerToken();
    $parts = explode('|', $token);
    
    // Step 2.2: Authenticate user
    $tokenRecord = PersonalAccessToken::where('token', hash('sha256', $parts[1]))->first();
    $user = $tokenRecord->tokenable;
    auth()->login($user);
    
    // Step 2.3: Validate YouTube URL
    $request->validate(['url' => 'required|url']);
    if (!preg_match('/^https?:\/\/(www\.)?(youtube\.com|youtu\.be)\/.+/', $url)) {
        return response()->json(['error' => 'Invalid YouTube URL'], 422);
    }
    
    // Step 2.4: Create standardized request
    $youtubeRequest = new Request([
        'content_type' => 'link',
        'source' => ['type' => 'url', 'data' => $request->url],
        'options' => array_merge([...], $request->options ?? [])
    ]);
    
    // Step 2.5: Delegate to SummarizeController
    return $controller->summarizeAsync($youtubeRequest);
});
```

### **3. SummarizeController (`app/Http/Controllers/Api/Client/SummarizeController.php`)**
```php
public function summarizeAsync(Request $request) {
    // Step 3.1: Validate request format
    $validator = $this->validateRequest($request);
    
    // Step 3.2: Get authenticated user
    $user = auth()->user();
    
    // Step 3.3: Create universal job
    $job = $this->universalJobService->createJob('summarize', [
        'content_type' => 'link',
        'source' => ['type' => 'url', 'data' => $videoUrl]
    ], $options, $user->id);
    
    // Step 3.4: Queue background processing
    Artisan::queue('universal:process-job', ['jobId' => $job['id']]);
    
    // Step 3.5: Return job info immediately
    return response()->json([
        'success' => true,
        'job_id' => $job['id'],
        'status' => 'pending',
        'poll_url' => '/api/summarize/status/' . $job['id'],
        'result_url' => '/api/summarize/result/' . $job['id']
    ], 202);
}
```

### **4. UniversalJobService (`app/Services/UniversalJobService.php`)**
```php
public function createJob($toolType, $input, $options = [], $userId = null) {
    // Step 4.1: Generate unique job ID
    $jobId = Str::uuid()->toString();
    
    // Step 4.2: Create job structure
    $job = [
        'id' => $jobId,
        'tool_type' => 'summarize',
        'input' => $input,
        'options' => $options,
        'user_id' => $userId,
        'status' => 'pending',
        'stage' => 'initializing',
        'progress' => 0,
        'created_at' => now()->toISOString(),
        'logs' => [],
        'result' => null,
        'error' => null,
        'metadata' => [...]
    ];
    
    // Step 4.3: Store job in cache
    Cache::put("universal_job_{$jobId}", $job, 3600);
    
    return $job;
}
```

### **5. Queue Worker Processing**
```php
// Step 5.1: Queue worker picks up job
php artisan queue:work

// Step 5.2: ProcessUniversalJob command executes
class ProcessUniversalJob extends Command {
    public function handle() {
        $jobId = $this->argument('jobId');
        $universalJobService = app(UniversalJobService::class);
        $universalJobService->processJob($jobId);
    }
}
```

### **6. Job Processing (`UniversalJobService::processJob`)**
```php
public function processJob($jobId) {
    // Step 6.1: Get job from cache
    $job = $this->getJob($jobId);
    
    // Step 6.2: Update job status
    $this->updateJob($jobId, [
        'status' => 'running',
        'stage' => 'processing',
        'progress' => 25
    ]);
    
    // Step 6.3: Process by tool type
    $result = $this->processByToolType($job);
    
    // Step 6.4: Complete or fail job
    if ($result['success']) {
        $this->completeJob($jobId, $result['data']);
    } else {
        $this->failJob($jobId, $result['error']);
    }
}
```

### **7. Tool Type Processing (`processByToolType`)**
```php
private function processByToolType($job) {
    switch ($job['tool_type']) {
        case 'summarize':
            return $this->processSummarizeJob($input, $options);
    }
}

private function processSummarizeJob($input, $options) {
    $contentType = $input['content_type']; // 'link'
    $source = $input['source'];
    
    switch ($contentType) {
        case 'link':
            return $this->processLinkSummarization($source['data'], $options);
    }
}

private function processLinkSummarization($url, $options) {
    // Check if YouTube URL
    if (strpos($url, 'youtube.com') !== false) {
        return $this->processYouTubeSummarization($url, $options);
    }
}
```

### **8. YouTube Processing (`processYouTubeSummarization`)**
```php
private function processYouTubeSummarization($url, $options) {
    // Step 8.1: Use UnifiedProcessingService
    $unifiedService = app(UnifiedProcessingService::class);
    $result = $unifiedService->processYouTubeVideo($url, $options);
    
    return [
        'success' => true,
        'data' => $result,
        'metadata' => [...]
    ];
}
```

### **9. UnifiedProcessingService (`processYouTubeVideo`)**
```php
public function processYouTubeVideo($videoUrl, $options = [], $userId = null) {
    // Step 9.1: Get transcription from YouTubeTranscriberService
    $transcriptionResult = $this->youtubeTranscriberService->transcribe($videoUrl, [
        'format' => 'bundle',
        'language' => $options['language'] ?? 'auto',
        'meta' => true
    ]);
    
    // Step 9.2: Extract article text
    $articleText = $transcriptionResult['article'] ?? '';
    
    // Step 9.3: Send to AI Manager for summarization
    $summaryResult = $this->aiManagerService->processText($articleText, 'summarize', [
        'mode' => $options['mode'] ?? 'detailed',
        'language' => $options['language'] ?? 'en',
        'max_tokens' => 1000,
        'temperature' => 0.7
    ]);
    
    // Step 9.4: Merge bundle with summary
    $mergedResult = $this->mergeBundleWithSummary($transcriptionResult, $summaryResult, $options);
    
    // Step 9.5: Save AI result
    $aiResult = $this->aiResultService->createResult([
        'user_id' => $userId,
        'tool_type' => 'summarize',
        'title' => "YouTube Video Summary ({$videoId})",
        'input_data' => $articleText,
        'result_data' => $mergedResult,
        'metadata' => [...]
    ]);
    
    return [
        'success' => true,
        'summary' => $summaryResult['insights'],
        'bundle' => $mergedResult,
        'ai_result' => $aiResult,
        'metadata' => [...]
    ];
}
```

### **10. YouTubeTranscriberService (Smartproxy Integration)**
```php
public function transcribe($videoUrl, $options = []) {
    // Step 10.1: Try Smartproxy endpoint first
    $smartproxyResult = $this->transcribeWithSmartproxy($videoUrl, $options);
    if ($smartproxyResult['success']) {
        return $smartproxyResult;
    }
    
    // Step 10.2: Fallback to original method
    return $this->transcribeOriginal($videoUrl, $options);
}

public function transcribeWithSmartproxy($videoUrl, $options = []) {
    // Step 10.3: Call Smartproxy endpoint
    $response = Http::timeout(600)
        ->withHeaders(['X-Client-Key' => $this->clientKey])
        ->get($this->apiUrl . '/scraper/smartproxy/subtitles', [
            'url' => $videoUrl,
            'format' => 'bundle'
        ]);
    
    // Step 10.4: Process response
    if ($response->successful()) {
        $data = $response->json();
        return [
            'success' => true,
            'video_id' => $data['video_id'],
            'article' => $data['article_text'],
            'json' => ['segments' => $data['json_items']],
            'language' => $data['language'],
            'format' => $data['format']
        ];
    }
}
```

### **11. AI Manager Service**
```php
public function processText($text, $task, $options = []) {
    // Step 11.1: Check service availability
    if (!$this->isServiceAvailable()) {
        throw new \Exception("AI Manager service is currently unavailable");
    }
    
    // Step 11.2: Send request to AI Manager
    $response = Http::timeout(60)
        ->withHeaders(['X-API-KEY' => $this->apiKey])
        ->post($this->apiUrl . '/api/process-text', [
            'text' => $text,
            'task' => $task,
            'options' => $options
        ]);
    
    // Step 11.3: Return processed result
    return ['success' => true, 'data' => $response->json()];
}
```

### **12. Job Completion**
```php
private function completeJob($jobId, $data, $metadata = []) {
    // Step 12.1: Update job status
    $this->updateJob($jobId, [
        'status' => 'completed',
        'stage' => 'completed',
        'progress' => 100,
        'result' => $data,
        'metadata' => array_merge($job['metadata'], $metadata)
    ]);
}
```

### **13. Status Polling**
```
GET /api/summarize/status/{jobId}
Response: {
  "job_id": "2f124176-bee6-4c4b-82ee-6ad01b105c26",
  "status": "completed",
  "progress": 100,
  "stage": "completed"
}
```

### **14. Result Retrieval**
```
GET /api/summarize/result/{jobId}
Response: {
  "success": true,
  "data": {
    "success": true,
    "summary": "Generated summary text...",
    "bundle": {
      "video_id": "dQw4w9WgXcQ",
      "article": "Full article text...",
      "summary": "Generated summary...",
      "json": {"segments": [...]},
      "meta": {...}
    },
    "ai_result": {...}
  }
}
```

## **Key Components:**

1. **Route Handler**: Authentication, validation, request formatting
2. **SummarizeController**: Job creation, queue dispatch
3. **UniversalJobService**: Job management, processing orchestration
4. **UnifiedProcessingService**: YouTube-specific processing logic
5. **YouTubeTranscriberService**: Smartproxy integration for transcription
6. **AIManagerService**: AI summarization processing
7. **Queue Worker**: Background job processing
8. **Cache System**: Job state management

## **Data Flow Summary:**
`Request` → `Route` → `Controller` → `JobService` → `Queue` → `Worker` → `UnifiedService` → `Transcriber` → `AI Manager` → `Result` → `Cache` → `Response`


