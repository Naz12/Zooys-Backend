@echo off
echo Checking Redis Server status...
echo.

REM Check if Redis is already running
redis-cli ping >nul 2>&1
if %errorlevel% == 0 (
    echo Redis is already running on port 6379
    echo.
    echo To stop Redis, run: taskkill /F /IM redis-server.exe
    echo Or use: redis-cli shutdown
    echo.
    pause
    exit /b 0
)

echo Starting Redis Server...
echo.
echo Redis will start on port 6379
echo Press Ctrl+C to stop Redis
echo.

"C:\Program Files\Redis\redis-server.exe" "C:\Program Files\Redis\redis.windows.conf"

echo.
echo Redis Server stopped.
pause
