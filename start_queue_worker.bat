@echo off
echo Starting Laravel Queue Worker...
echo.
echo Queue worker will process Redis queue jobs
echo Press Ctrl+C to stop the queue worker
echo.

php -d memory_limit=1024M artisan queue:work redis --timeout=0 --tries=3 --max-jobs=50 --max-time=3600

echo.
echo Queue Worker stopped.
pause


























