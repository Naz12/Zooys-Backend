# PowerShell script to restore Laravel .env file
# Run this script to restore your Laravel .env file

Write-Host "Restoring Laravel .env file..." -ForegroundColor Green

# Create the .env file content
$envContent = @"
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:your_app_key_here
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zooys_backend
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="`${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="`${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="`${PUSHER_HOST}"
VITE_PUSHER_PORT="`${PUSHER_PORT}"
VITE_PUSHER_SCHEME="`${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="`${PUSHER_APP_CLUSTER}"

# OpenAI Configuration
OPENAI_API_KEY=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
OPENAI_URL=https://api.openai.com/v1/chat/completions
OPENAI_MODEL=gpt-3.5-turbo
OPENAI_MAX_TOKENS=1000
OPENAI_TEMPERATURE=0.7

# YouTube API Configuration
YOUTUBE_API_KEY=your_youtube_api_key_here

# Microservice URLs
PRESENTATION_MICROSERVICE_URL=http://localhost:8001
MATH_MICROSERVICE_URL=http://localhost:8002
MATH_MICROSERVICE_TIMEOUT=60

# AI Configuration
AI_CHUNK_MAX_SIZE=3000
AI_CHUNK_OVERLAP_SIZE=200
AI_CHUNK_MIN_SIZE=500
AI_CHUNKING_ENABLED=true
AI_SUMMARY_MAX_TOKENS=1000
AI_SUMMARY_TEMPERATURE=0.7
AI_SUMMARIZATION_ENABLED=true
AI_MAX_FILE_SIZE=10MB
AI_EXTRACTION_TIMEOUT=30
AI_YOUTUBE_ENABLED=true
AI_YOUTUBE_PYTHON_ENABLED=true
AI_YOUTUBE_FALLBACK_ENABLED=true
AI_PDF_ENABLED=true
AI_PDF_MAX_FILE_SIZE=10MB
AI_WEB_SCRAPING_ENABLED=true
AI_WEB_SCRAPING_TIMEOUT=30
AI_UNIFIED_PROCESSING_ENABLED=true
AI_CHUNKING_THRESHOLD=8000
AI_PARALLEL_PROCESSING=false
"@

# Write the content to .env file
$envContent | Out-File -FilePath ".env" -Encoding UTF8

Write-Host "✅ .env file restored successfully!" -ForegroundColor Green
Write-Host ""
Write-Host "⚠️  IMPORTANT: You need to add your YouTube API key:" -ForegroundColor Yellow
Write-Host "   Replace 'your_youtube_api_key_here' with your actual YouTube API key" -ForegroundColor Yellow
Write-Host ""
Write-Host "🔑 To generate Laravel app key, run:" -ForegroundColor Cyan
Write-Host "   php artisan key:generate" -ForegroundColor Cyan
Write-Host ""
Write-Host "📝 Edit the .env file and add your YouTube API key, then run the key generation command." -ForegroundColor White














