# YouTube Summarization Data Flow Trace

## Complete Data Flow Path

### 1. **Entry Point: API Route**
```
POST /api/summarize/async/youtube
├── routes/api.php (line 1246)
├── Validates YouTube URL
├── Creates Request with:
│   ├── content_type: 'link'
│   ├── source: { type: 'url', data: 'https://youtube.com/...' }
│   └── options: { language, format, focus, model }
└── Calls: SummarizeController::summarizeAsync()
```

### 2. **Controller: SummarizeController**
```
app/Http/Controllers/Api/Client/SummarizeController.php
├── summarizeAsync() (line 1259)
├── Validates request
├── Creates Universal Job:
│   ├── tool_type: 'summarize'
│   ├── input: { content_type, source }
│   └── options: { language, format, focus, model, ... }
└── Queues job for processing
```

### 3. **Job Processing: UniversalJobService**
```
app/Services/UniversalJobService.php
├── processJob() (line 399)
├── processByToolTypeWithStages() (line 459)
│   └── case 'summarize': processSummarizeJobWithStages()
│
└── processSummarizeJobWithStages() (line 1150)
    ├── Detects content_type: 'link'
    ├── Analyzes URL → Detects YouTube
    └── Calls: processYouTubeVideoSummarizationWithStages()
```

### 4. **YouTube Processing: Transcription**
```
processYouTubeVideoSummarizationWithStages() (line 1190)
├── Stage 1: Initialization
├── Stage 2: URL Validation
├── Stage 3: Transcription
│   ├── TranscriberModule::transcribeVideo()
│   ├── Returns: {
│   │     success: true,
│   │     transcript: "...",
│   │     article_text: "...",  // ⚠️ Could be string OR array
│   │     video_id: "...",
│   │     language: "auto" or "en",  // ⚠️ Could be string
│   │     json_items: [...],
│   │     transcript_json: [...]
│   │   }
│   └── Handles article_text conversion (lines 1301-1327)
│       ├── Checks if array → converts to string
│       └── Falls back to transcript if needed
│
└── Stage 4: Document Intelligence Ingestion (line 1371)
```

### 5. **Document Intelligence Ingestion: Preparation**
```
Lines 1381-1412
├── Creates metadata:
│   ├── source: 'youtube' (string)
│   ├── video_id: (string)
│   └── user_id: (string) - if valid
│
├── Determines lang:
│   ├── Default: 'eng'
│   └── Uses transcriptionResult['language'] if valid (not 'auto')
│
└── Calls: DocumentIntelligenceModule::ingestText()
    ├── text: $articleTextTrimmed (string)
    ├── filename: 'summary.txt' (hardcoded)
    ├── lang: 'eng' or detected language
    ├── metadata: { source, video_id, user_id }
    ├── force_fallback: true (hardcoded)
    └── llm_model: 'deepseek-chat' (hardcoded)
```

### 6. **DocumentIntelligenceModule**
```
app/Services/Modules/DocumentIntelligenceModule.php
├── ingestText() (line 355)
├── Health check
├── Logs options (line 367) ⚠️ Logs show metadata with "language":"auto"
├── Ensures force_fallback: true
└── Calls: DocumentIntelligenceService::ingestText()
```

### 7. **DocumentIntelligenceService: Payload Creation**
```
app/Services/DocumentIntelligenceService.php
├── ingestText() (line 240)
├── Extracts options:
│   ├── filename: 'summary.txt'
│   ├── lang: 'eng'
│   ├── metadata: { source, video_id, user_id }
│   ├── force_fallback: true
│   └── llm_model: 'deepseek-chat'
│
├── Creates payload (line 255):
│   ├── text: (string)
│   ├── filename: (string)
│   ├── lang: (string)
│   ├── force_fallback: (boolean)
│   └── llm_model: (string)
│
├── Metadata Cleaning (lines 264-277) ⚠️ NEW CODE
│   ├── Loops through metadata
│   ├── Converts scalars to strings
│   ├── Keeps arrays as-is
│   └── Skips null/objects
│
├── Debug Logging (lines 279-288) ⚠️ NEW CODE
│   └── Logs exact payload JSON
│
└── HTTP Request (line 290):
    ├── POST /v1/ingest/text
    ├── Headers: Content-Type: application/json
    └── Body: JSON payload
```

### 8. **Document Intelligence Microservice**
```
External Service: https://doc.akmicroservice.com
├── Receives POST /v1/ingest/text
├── Validates payload
├── Processes text
└── Returns: { doc_id, job_id, checksum, message }
    OR
    Error: "Array to string conversion" ⚠️ ERROR OCCURS HERE
```

## 🔍 Problem Analysis

### Where the Error Occurs:
The error "Array to string conversion" happens **inside the Document Intelligence microservice**, not in our Laravel code.

### Why It's Happening:
1. **Metadata Structure**: The service might be expecting a specific metadata format
2. **Type Mismatch**: Even though we're converting to strings, something in the payload might still be an array
3. **Service-Side Processing**: The microservice might be trying to process metadata in a way that expects strings but receives arrays

### Current Metadata Being Sent:
```json
{
  "source": "youtube",
  "video_id": "dQw4w9WgXcQ",
  "user_id": "17"
}
```

### Working Example Metadata:
```json
{
  "tags": ["summary", "external"],
  "business_unit": "ops",
  "date": "2024-06-01"
}
```

### Key Differences:
1. Working example has `tags` as an **array** - this works fine
2. Our metadata has only **strings** - should work, but doesn't
3. The error suggests the service is trying to convert something to a string that's an array

## 🐛 Potential Issues

1. **Logs Show Old Metadata**: The logs might be showing cached/old metadata that still includes `"language":"auto"`
2. **Metadata Cleaning Not Applied**: The new metadata cleaning code might not be running
3. **Service Expects Different Format**: The service might require specific metadata fields or structure
4. **Text Content Issue**: The `text` field itself might contain something that causes the error

## ✅ Next Steps to Debug

1. **Check Debug Logs**: Look for the new debug log entry showing exact payload JSON
2. **Compare Payloads**: Compare the logged payload with the working direct test
3. **Test Without Metadata**: Try sending empty metadata `{}` to see if that works
4. **Test With Working Format**: Try sending metadata in the exact format of the working example

