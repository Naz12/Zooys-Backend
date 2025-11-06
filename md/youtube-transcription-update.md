# YouTube Transcription Service Update

## Overview
The YouTube transcription service has been updated to use the new BrightData endpoint instead of the old Smartproxy endpoint.

## Configuration

### Environment Variables
Add these to your `.env` file:

```env
YOUTUBE_TRANSCRIBER_URL=https://transcriber.akmicroservice.com
YOUTUBE_TRANSCRIBER_CLIENT_KEY=dev-local
YOUTUBE_TRANSCRIBER_TIMEOUT=600
YOUTUBE_TRANSCRIBER_DEFAULT_FORMAT=bundle
```

### Config File
Configuration is now centralized in `config/services.php`:

```php
'youtube_transcriber' => [
    'url' => env('YOUTUBE_TRANSCRIBER_URL', 'https://transcriber.akmicroservice.com'),
    'client_key' => env('YOUTUBE_TRANSCRIBER_CLIENT_KEY', 'dev-local'),
    'timeout' => env('YOUTUBE_TRANSCRIBER_TIMEOUT', 600),
    'default_format' => env('YOUTUBE_TRANSCRIBER_DEFAULT_FORMAT', 'bundle'),
],
```

## New Endpoint

### BrightData Scrape Endpoint

**URL:** `POST https://transcriber.akmicroservice.com/brightdata/scrape`

**Headers:**
- `X-Client-Key: dev-local`
- `Content-Type: application/json`
- `Accept: application/json`

**Query Parameters:**
- `dataset_id`: `gd_lk56epmy2i5g7lzu0k` (required)
- `format`: `bundle` (default) or `plain` or `json`
- `headings`: `1` (default, include headings)
- `max_paragraph_sentences`: `7` (default)
- `include_meta`: `1` (default, include metadata)

**Request Body:**
```json
{
  "input": [
    {
      "url": "https://www.youtube.com/watch?v=OCaQUWTrNn8"
    }
  ]
}
```

**Response Example:**
```json
{
  "video_id": "OCaQUWTrNn8",
  "language": "auto",
  "format": "bundle",
  "article_text": "## All Right We Got Some New...",
  "json_items": [
    {
      "text": "All right, we got some new Zoron stuff",
      "start": 400.0,
      "duration": 2400.0
    },
    ...
  ],
  "meta": {
    "title": "Cuomo Threatens Dem Civil War As Zohran Surges",
    "url": "https://www.youtube.com/watch?v=OCaQUWTrNn8",
    "youtuber": "@breakingpoints",
    "views": 25750,
    "likes": 1539,
    "date_posted": "2025-10-30T18:30:28.000Z"
  }
}
```

## Usage in Code

### Basic Usage

```php
use App\Services\YouTubeTranscriberService;

$transcriber = new YouTubeTranscriberService();

// Transcribe a YouTube video
$result = $transcriber->transcribe('https://www.youtube.com/watch?v=OCaQUWTrNn8');

if ($result['success']) {
    $videoId = $result['video_id'];
    $language = $result['language'];
    $articleText = $result['article_text'];
    $jsonItems = $result['json_items']; // Timestamped segments
    $meta = $result['meta']; // Video metadata
}
```

### With Options

```php
$result = $transcriber->transcribe('https://www.youtube.com/watch?v=OCaQUWTrNn8', [
    'format' => 'bundle',
    'headings' => 1,
    'max_paragraph_sentences' => 7,
    'include_meta' => 1
]);
```

## Response Fields

### Main Response
- `success`: Boolean indicating if the transcription was successful
- `video_id`: YouTube video ID (e.g., "OCaQUWTrNn8")
- `language`: Detected language (e.g., "auto")
- `format`: Format of the transcription (e.g., "bundle")
- `subtitle_text`: Full transcription text (same as `article_text`)
- `article_text`: Full transcription text with headings
- `json_items`: Array of timestamped segments
- `meta`: Video metadata object

### JSON Items (Timestamped Segments)
Each item in `json_items` contains:
- `text`: The transcribed text segment
- `start`: Start time in milliseconds
- `duration`: Duration in milliseconds

### Metadata Object
The `meta` object contains:
- `title`: Video title
- `url`: Video URL
- `youtuber`: Channel name
- `views`: View count
- `likes`: Like count
- `date_posted`: Publication date (ISO 8601 format)

## Changes Summary

### Old Endpoint (Deprecated)
- **URL:** `GET /scraper/smartproxy/subtitles?url=...&format=article`
- **Method:** GET with query parameters
- **Headers:** `X-Client-Key`

### New Endpoint (Current)
- **URL:** `POST /brightdata/scrape?dataset_id=...&format=bundle&headings=1&max_paragraph_sentences=7&include_meta=1`
- **Method:** POST with JSON body
- **Headers:** `X-Client-Key`, `Content-Type: application/json`

## Benefits of New Endpoint

1. **Structured Data**: Returns timestamped segments (`json_items`) in addition to the article text
2. **Enhanced Metadata**: Includes video metadata (title, channel, views, likes, date)
3. **Better Control**: More options for formatting (headings, paragraph sentences)
4. **Consistent Response**: Always returns the same structured format
5. **POST Method**: More flexible for future enhancements

## Migration Notes

- The service automatically uses the new BrightData endpoint
- Falls back to the old Smartproxy endpoint if BrightData fails
- No changes required to existing API consumers
- Response format remains backward compatible

## Testing

### Test with cURL
```bash
curl -X POST "https://transcriber.akmicroservice.com/brightdata/scrape?dataset_id=gd_lk56epmy2i5g7lzu0k&format=bundle&headings=1&max_paragraph_sentences=7&include_meta=1" \
  -H "X-Client-Key: dev-local" \
  -H "Content-Type: application/json" \
  -d '{"input": [{"url": "https://www.youtube.com/watch?v=OCaQUWTrNn8"}]}'
```

### Test via Laravel
```php
use App\Services\YouTubeTranscriberService;

$transcriber = new YouTubeTranscriberService();
$result = $transcriber->transcribe('https://www.youtube.com/watch?v=OCaQUWTrNn8');

dd($result);
```

## Troubleshooting

### Common Issues

**Issue: Timeout errors**
- Increase `YOUTUBE_TRANSCRIBER_TIMEOUT` in `.env` (default: 600 seconds)
- Long videos may take more time to process

**Issue: Authentication errors**
- Verify `YOUTUBE_TRANSCRIBER_CLIENT_KEY` is set to `dev-local`
- Check if the API key is valid for your environment

**Issue: Invalid response format**
- Ensure `format` parameter is one of: `bundle`, `plain`, or `json`
- Check if the video URL is valid and accessible

## Support

For issues or questions, check the Laravel logs at `storage/logs/laravel.log` for detailed error messages.

