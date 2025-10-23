@echo off
echo Starting Laravel Queue Worker...
echo.
echo Queue worker will process Redis queue jobs
echo Press Ctrl+C to stop the queue worker
echo.

php artisan queue:work redis --timeout=300 --tries=3

echo.
echo Queue Worker stopped.
pause




