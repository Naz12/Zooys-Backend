@echo off
echo Starting Redis Server...
echo.
echo Redis will start on port 6379
echo Press Ctrl+C to stop Redis
echo.

"C:\Program Files\Redis\redis-server.exe" "C:\Program Files\Redis\redis.windows.conf"

echo.
echo Redis Server stopped.
pause
