@echo off
echo Restarting all Laravel services...

echo.
echo 1. Checking Redis...
netstat -an | findstr :6379
if %errorlevel% neq 0 (
    echo Starting Redis...
    start "Redis Server" cmd /k "& \"C:\Program Files\Redis\redis-server.exe\" \"C:\Program Files\Redis\redis.windows.conf\""
    timeout /t 3 /nobreak > nul
) else (
    echo Redis is already running
)

echo.
echo 2. Starting Laravel Server...
start "Laravel Server" cmd /k "php artisan serve"

echo.
echo 3. Starting Queue Worker...
start "Queue Worker" cmd /k "php artisan queue:work redis --timeout=300 --tries=3"

echo.
echo All services restarted!
echo - Redis Server: Running on port 6379
echo - Laravel Server: Running on http://localhost:8000
echo - Queue Worker: Processing Redis queue jobs
echo.
echo Press any key to exit...
pause > nul






