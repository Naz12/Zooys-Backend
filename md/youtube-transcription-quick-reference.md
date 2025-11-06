# YouTube Transcription - Quick Reference

## 🚀 Quick Start

### 1. Add to `.env`
```env
YOUTUBE_TRANSCRIBER_URL=https://transcriber.akmicroservice.com
YOUTUBE_TRANSCRIBER_CLIENT_KEY=dev-local
```

### 2. Use in Code
```php
use App\Services\YouTubeTranscriberService;

$transcriber = new YouTubeTranscriberService();
$result = $transcriber->transcribe('https://www.youtube.com/watch?v=OCaQUWTrNn8');

if ($result['success']) {
    $articleText = $result['article_text'];  // Full text with headings
    $segments = $result['json_items'];       // Timestamped segments
    $meta = $result['meta'];                 // Video metadata
}
```

## 📡 API Endpoint

**New BrightData Endpoint:**
```
POST https://transcriber.akmicroservice.com/brightdata/scrape
```

**Query Parameters:**
- `dataset_id=gd_lk56epmy2i5g7lzu0k` (required)
- `format=bundle` (bundle, plain, or json)
- `headings=1` (include headings)
- `max_paragraph_sentences=7`
- `include_meta=1` (include video metadata)

**Headers:**
```
X-Client-Key: dev-local
Content-Type: application/json
```

**Body:**
```json
{
  "input": [
    {"url": "https://www.youtube.com/watch?v=OCaQUWTrNn8"}
  ]
}
```

## 📦 Response Format

```json
{
  "video_id": "OCaQUWTrNn8",
  "language": "auto",
  "format": "bundle",
  "article_text": "## Heading\nTranscript content...",
  "json_items": [
    {
      "text": "Transcribed segment",
      "start": 400.0,
      "duration": 2400.0
    }
  ],
  "meta": {
    "title": "Video Title",
    "url": "https://www.youtube.com/watch?v=...",
    "youtuber": "@channel",
    "views": 25750,
    "likes": 1539,
    "date_posted": "2025-10-30T18:30:28.000Z"
  }
}
```

## 🎯 Common Use Cases

### Get Full Transcript
```php
$result = $transcriber->transcribe($youtubeUrl);
$transcript = $result['article_text'];
```

### Get Timestamped Segments
```php
$result = $transcriber->transcribe($youtubeUrl);
foreach ($result['json_items'] as $segment) {
    echo "[{$segment['start']}ms] {$segment['text']}\n";
}
```

### Get Video Metadata
```php
$result = $transcriber->transcribe($youtubeUrl);
$title = $result['meta']['title'];
$channel = $result['meta']['youtuber'];
$views = $result['meta']['views'];
```

### Custom Options
```php
$result = $transcriber->transcribe($youtubeUrl, [
    'format' => 'bundle',
    'headings' => 1,
    'max_paragraph_sentences' => 10,
    'include_meta' => 1
]);
```

## ⚠️ Error Handling

```php
$result = $transcriber->transcribe($youtubeUrl);

if (!$result['success']) {
    Log::error('Transcription failed: ' . $result['error']);
    return response()->json(['error' => $result['error']], 500);
}

// Process successful result
$transcript = $result['article_text'];
```

## 🔧 Troubleshooting

**Timeout Issues:**
```env
YOUTUBE_TRANSCRIBER_TIMEOUT=900  # Increase to 15 minutes
```

**Check Service Health:**
```php
$health = $transcriber->isBrightDataAvailable();
if (!$health) {
    Log::error('BrightData service is unavailable');
}
```

**Check Logs:**
```bash
tail -f storage/logs/laravel.log
```

## 📊 Response Fields Reference

| Field | Type | Description |
|-------|------|-------------|
| `success` | boolean | Whether transcription succeeded |
| `video_id` | string | YouTube video ID |
| `language` | string | Detected language (e.g., "auto") |
| `format` | string | Response format (bundle, plain, json) |
| `article_text` | string | Full transcript with headings |
| `subtitle_text` | string | Same as article_text (for compatibility) |
| `json_items` | array | Timestamped segments |
| `meta` | object | Video metadata |

## 🕐 Timestamp Format

JSON items contain timestamps in **milliseconds**:

```php
$segment = $result['json_items'][0];
$startSeconds = $segment['start'] / 1000;  // Convert to seconds
$durationSeconds = $segment['duration'] / 1000;
```

## 🔄 Fallback Behavior

The service automatically falls back to the old Smartproxy endpoint if BrightData fails:
1. Try BrightData (new endpoint)
2. If fails, try Smartproxy (old endpoint)
3. If fails, try original async method

## 📝 Example: AI Summary Integration

```php
// Get transcript
$result = $transcriber->transcribe($youtubeUrl);

if ($result['success']) {
    // Use for AI summary
    $aiInput = [
        'title' => $result['meta']['title'],
        'channel' => $result['meta']['youtuber'],
        'transcript' => $result['article_text'],
        'segments' => $result['json_items']
    ];
    
    // Send to AI service
    $summary = $aiService->generateSummary($aiInput);
}
```

## 🧪 Testing

### Test Script
```php
use App\Services\YouTubeTranscriberService;

$transcriber = new YouTubeTranscriberService();

// Test video
$url = 'https://www.youtube.com/watch?v=OCaQUWTrNn8';
$result = $transcriber->transcribe($url);

// Assertions
assert($result['success'] === true);
assert(!empty($result['article_text']));
assert(is_array($result['json_items']));
assert(isset($result['meta']['title']));

echo "✅ All tests passed!\n";
```

## 🌐 cURL Example

```bash
curl -X POST \
  "https://transcriber.akmicroservice.com/brightdata/scrape?dataset_id=gd_lk56epmy2i5g7lzu0k&format=bundle&headings=1&max_paragraph_sentences=7&include_meta=1" \
  -H "X-Client-Key: dev-local" \
  -H "Content-Type: application/json" \
  -d '{"input": [{"url": "https://www.youtube.com/watch?v=OCaQUWTrNn8"}]}'
```

## 📚 Documentation

- Full API Documentation: `md/youtube-transcription-update.md`
- Migration Summary: `md/youtube-transcription-migration-summary.md`
- Quick Reference: This file

---

**Last Updated:** October 31, 2025  
**Version:** 2.0 (BrightData Endpoint)

