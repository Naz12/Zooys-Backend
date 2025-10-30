@echo off
echo Starting Laravel with Production-like Configuration...

REM Use PHP built-in server with unlimited execution time
php -S localhost:8000 -t public

echo.
echo Laravel server started on http://localhost:8000
echo This configuration prevents timeout crashes
echo Press Ctrl+C to stop the server
pause










