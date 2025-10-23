# Postman Test for Async YouTube Endpoint

## **🔧 Setup Instructions**

### **1. Authentication Token**
First, get a valid authentication token:

**POST** `http://localhost:8000/api/register`
```json
{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password",
    "password_confirmation": "password"
}
```

**Response:**
```json
{
    "user": {
        "id": 1,
        "name": "Test User",
        "email": "test@example.com"
    },
    "token": "1|abc123def456ghi789..."
}
```

### **2. YouTube Async Endpoint Test**

**POST** `http://localhost:8000/api/summarize/async/youtube`

**Headers:**
```
Content-Type: application/json
Authorization: Bearer 1|abc123def456ghi789...
Accept: application/json
```

**Body (JSON):**
```json
{
    "url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
    "options": {
        "language": "en",
        "format": "detailed",
        "focus": "summary"
    }
}
```

**Expected Response:**
```json
{
    "success": true,
    "data": {
        "job_id": "e5c85a20-411b-4126-b281-4fa25e4b3a75",
        "status": "pending",
        "progress": 0,
        "stage": "initializing",
        "error": null,
        "created_at": "2025-10-22T10:15:30.000Z"
    }
}
```

## **📊 Job Status Check**

**GET** `http://localhost:8000/api/summarize/status/{job_id}`

**Headers:**
```
Authorization: Bearer 1|abc123def456ghi789...
Accept: application/json
```

**Example:**
```
GET http://localhost:8000/api/summarize/status/e5c85a20-411b-4126-b281-4fa25e4b3a75
```

**Expected Response:**
```json
{
    "success": true,
    "data": {
        "job_id": "e5c85a20-411b-4126-b281-4fa25e4b3a75",
        "status": "running",
        "progress": 25,
        "stage": "processing",
        "error": null,
        "logs": [
            {
                "timestamp": 1698064530,
                "message": "Starting YouTube transcription"
            }
        ]
    }
}
```

## **📋 Job Result Check**

**GET** `http://localhost:8000/api/summarize/result/{job_id}`

**Headers:**
```
Authorization: Bearer 1|abc123def456ghi789...
Accept: application/json
```

**Example:**
```
GET http://localhost:8000/api/summarize/result/e5c85a20-411b-4126-b281-4fa25e4b3a75
```

**Expected Response (When Completed):**
```json
{
    "success": true,
    "data": {
        "success": true,
        "summary": "This video is a classic internet meme...",
        "ai_result": {
            "id": 123,
            "title": "YouTube Video Summary (dQw4w9WgXcQ)",
            "file_url": "https://example.com/download/summary.pdf",
            "created_at": "2025-10-22T10:15:45.000Z"
        },
        "bundle": {
            "video_id": "dQw4w9WgXcQ",
            "language": "auto",
            "format": "bundle_with_summary",
            "article": "Full transcription text...",
            "summary": "Generated summary...",
            "json": {
                "segments": [
                    {
                        "text": "Never gonna give you up",
                        "start": 0.0,
                        "duration": 2.5
                    }
                ]
            },
            "meta": {
                "ai_summary": "Generated summary...",
                "ai_model_used": "ollama:phi3:mini",
                "processing_time": 15.2,
                "merged_at": "2025-10-22T10:15:45.000Z"
            }
        },
        "metadata": {
            "video_id": "dQw4w9WgXcQ",
            "title": "Rick Astley - Never Gonna Give You Up",
            "total_characters": 1200,
            "total_words": 240,
            "processing_method": "youtube_transcriber_ai_manager"
        }
    }
}
```

## **🎯 Test Videos (Recommended)**

### **Short Videos (Under 2 minutes):**
- `https://www.youtube.com/watch?v=dQw4w9WgXcQ` (Rick Roll - 3:33)
- `https://www.youtube.com/watch?v=9bZkp7q19f0` (Gangnam Style - 4:12)
- `https://www.youtube.com/watch?v=kJQP7kiw5Fk` (Despacito - 4:41)

### **Very Short Videos (Under 1 minute):**
- `https://www.youtube.com/watch?v=YQHsXMglC9A` (Hello - 4:55)
- `https://www.youtube.com/watch?v=09R8_2nJtjg` (Shape of You - 3:53)

## **⚡ Quick Test Steps**

1. **Register/Login** → Get token
2. **Submit YouTube URL** → Get job_id
3. **Poll Status** → Wait for completion
4. **Get Result** → View summary and bundle

## **🔍 Status Values**

- `pending` - Job created, waiting to start
- `running` - Job is processing
- `completed` - Job finished successfully
- `failed` - Job failed with error

## **⏱️ Expected Processing Times**

- **Short videos (1-3 min):** 30-60 seconds
- **Medium videos (3-10 min):** 1-3 minutes
- **Long videos (10+ min):** 3-10 minutes

## **🚨 Troubleshooting**

### **Common Issues:**
1. **401 Unauthorized** - Check token format: `Bearer 1|abc123...`
2. **422 Validation Error** - Ensure URL is valid YouTube link
3. **Job Stuck in Pending** - Check if queue worker is running
4. **Timeout Errors** - Try shorter videos first

### **Queue Worker Check:**
```bash
# Start queue worker
php artisan queue:work --daemon

# Or run once
php artisan queue:work --once
```

## **📱 Frontend Integration**

The frontend should:
1. Submit YouTube URL to `/summarize/async/youtube`
2. Poll `/summarize/status/{job_id}` every 2-3 seconds
3. Get final result from `/summarize/result/{job_id}`
4. Display the `bundle` data with `summary` and `article`

