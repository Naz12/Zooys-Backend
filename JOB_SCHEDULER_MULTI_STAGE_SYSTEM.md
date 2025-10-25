# 🚀 Multi-Stage Job Scheduler System

## **📋 Overview**

The enhanced job scheduler now provides **detailed stage tracking** for all 7 input types, allowing you to monitor exactly where each process is in the pipeline.

## **🎯 The 7 Input Types & Their Stages**

### **1. 📝 Text Summarization**
**Stages:** `analyzing_content` → `processing_text` → `ai_processing` → `finalizing`
- **Stage 1:** Analyzing content type and length
- **Stage 2:** Processing text content (word count, character analysis)
- **Stage 3:** Sending to AI Manager for summarization
- **Stage 4:** Finalizing results with confidence scores

### **2. 🎥 YouTube Video Summarization**
**Stages:** `analyzing_content` → `analyzing_url` → `processing_video` → `transcribing` → `ai_processing` → `finalizing`
- **Stage 1:** Analyzing content type
- **Stage 2:** Analyzing YouTube URL
- **Stage 3:** Processing video with Smartproxy
- **Stage 4:** Transcribing video content
- **Stage 5:** AI summarization
- **Stage 6:** Finalizing with video metadata

### **3. 🌐 Web Link Summarization**
**Stages:** `analyzing_content` → `analyzing_url` → `scraping_content` → `ai_processing` → `finalizing`
- **Stage 1:** Analyzing content type
- **Stage 2:** Analyzing web URL
- **Stage 3:** Scraping web content
- **Stage 4:** AI processing of scraped content
- **Stage 5:** Finalizing results

### **4. 📄 File Summarization (PDF, DOC, etc.)**
**Stages:** `analyzing_content` → `processing_file` → `extracting_content` → `ai_processing` → `finalizing`
- **Stage 1:** Analyzing content type
- **Stage 2:** Processing uploaded file
- **Stage 3:** Extracting content from file
- **Stage 4:** AI processing
- **Stage 5:** Finalizing with file metadata

### **5. 🧮 Math Problem Solving**
**Stages:** `analyzing_problem` → `processing_image`/`solving_problem` → `finalizing`
- **Stage 1:** Analyzing math problem type
- **Stage 2:** Processing image or solving text problem
- **Stage 3:** Finalizing with solution

### **6. 🃏 Flashcard Generation**
**Stages:** `analyzing_content` → `processing_file`/`generating_flashcards` → `finalizing`
- **Stage 1:** Analyzing content for flashcard generation
- **Stage 2:** Processing file or generating from text
- **Stage 3:** Finalizing with flashcard count

### **7. 📊 Presentation Generation**
**Stages:** `analyzing_content` → `processing_file`/`generating_outline` → `finalizing`
- **Stage 1:** Analyzing content for presentation
- **Stage 2:** Processing file or generating outline
- **Stage 3:** Finalizing with slide count

## **📊 Job Status Structure**

```json
{
  "job_id": "uuid-string",
  "status": "running|completed|failed|pending",
  "stage": "current_stage_name",
  "progress": 0-100,
  "logs": [
    {
      "timestamp": "2025-10-22T15:30:00Z",
      "level": "info|warning|error",
      "message": "Stage description",
      "data": {
        "additional_info": "value"
      }
    }
  ],
  "metadata": {
    "processing_stages": ["stage1", "stage2", "stage3"],
    "processing_started_at": "2025-10-22T15:30:00Z",
    "processing_completed_at": "2025-10-22T15:32:00Z",
    "total_processing_time": 120,
    "file_count": 1,
    "tokens_used": 1500,
    "confidence_score": 0.85
  }
}
```

## **🔍 Stage Names & Descriptions**

### **Common Stages:**
- `initializing` - Job is being set up
- `analyzing_content` - Analyzing input content type
- `ai_processing` - AI Manager is processing
- `finalizing` - Completing the job

### **YouTube-Specific:**
- `analyzing_url` - Analyzing YouTube URL
- `processing_video` - Processing video with Smartproxy
- `transcribing` - Transcribing video content

### **Web-Specific:**
- `analyzing_url` - Analyzing web URL
- `scraping_content` - Scraping web content

### **File-Specific:**
- `processing_file` - Processing uploaded file
- `extracting_content` - Extracting content from file

### **Math-Specific:**
- `analyzing_problem` - Analyzing math problem
- `processing_image` - Processing math problem image
- `solving_problem` - Solving text-based math problem

### **Flashcard-Specific:**
- `generating_flashcards` - Generating flashcards from content

### **Presentation-Specific:**
- `generating_outline` - Generating presentation outline

### **Document Chat-Specific:**
- `validating_file` - Validating document file
- `processing_document` - Processing document for chat
- `extracting_content` - Extracting content from document

## **📈 Progress Tracking**

Each stage has specific progress percentages:

- **0-10%:** Initializing and content analysis
- **10-30%:** Content processing (text, file, URL analysis)
- **30-50%:** Content extraction/transcription
- **50-80%:** AI processing
- **80-95%:** Finalizing results
- **95-100%:** Job completion

## **🔧 Implementation Details**

### **Enhanced Methods:**
- `processByToolTypeWithStages()` - Main orchestrator with stage tracking
- `processSummarizeJobWithStages()` - Summarization with stages
- `processTextSummarizationWithStages()` - Text processing with stages
- `processLinkSummarizationWithStages()` - Link processing with stages
- `processYouTubeSummarizationWithStages()` - YouTube processing with stages
- `processWebLinkSummarizationWithStages()` - Web processing with stages
- `processFileSummarizationWithStages()` - File processing with stages
- `processMathJobWithStages()` - Math processing with stages
- `processFlashcardsJobWithStages()` - Flashcard processing with stages
- `processPresentationsJobWithStages()` - Presentation processing with stages
- `processDocumentChatJobWithStages()` - Document chat processing with stages

### **Stage Updates:**
```php
$this->updateJob($jobId, [
    'stage' => 'current_stage_name',
    'progress' => 25
]);
```

### **Logging:**
```php
$this->addLog($jobId, "Stage description", 'info', [
    'additional_data' => 'value'
]);
```

## **🎯 Benefits**

1. **Real-time Progress:** Know exactly where each job is in the pipeline
2. **Detailed Logging:** Track every step with timestamps and data
3. **Error Isolation:** Identify exactly where failures occur
4. **Performance Monitoring:** Track processing times for each stage
5. **User Experience:** Provide meaningful progress updates to frontend
6. **Debugging:** Easy troubleshooting with detailed stage information

## **📱 Frontend Integration**

The frontend can now display:
- Current stage name (e.g., "Transcribing video content")
- Progress percentage (0-100)
- Detailed logs with timestamps
- Processing metadata (tokens used, confidence scores, etc.)
- Stage-specific information (video duration, word count, etc.)

## **🚀 Usage Example**

```javascript
// Frontend polling
const response = await fetch(`/api/summarize/status/${jobId}`);
const jobData = await response.json();

console.log(`Current Stage: ${jobData.stage}`);
console.log(`Progress: ${jobData.progress}%`);
console.log(`Latest Log: ${jobData.logs[jobData.logs.length - 1].message}`);
```

This multi-stage system provides complete visibility into the job processing pipeline, making it easy to track progress and debug issues for all 7 input types.



