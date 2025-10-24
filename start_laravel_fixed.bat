@echo off
echo Starting Laravel Development Server with Extended Timeout...

REM Set PHP execution time to unlimited for development
set PHP_INI_SCAN_DIR=C:\xampp\php
php -d max_execution_time=0 artisan serve --host=0.0.0.0 --port=8000

echo.
echo Laravel server started on http://localhost:8000
echo Press Ctrl+C to stop the server
pause


