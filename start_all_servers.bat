@echo off
echo ========================================
echo    Starting All Zooys Backend Servers
echo ========================================
echo.

REM Set console title
title Zooys Backend - All Servers

REM Check if we're in the correct directory
if not exist "artisan" (
    echo ERROR: This script must be run from the Laravel project root directory
    echo Please navigate to the project root and run this script again.
    pause
    exit /b 1
)

echo Starting all three servers...
echo.
echo 1. Laravel Backend (Port 8000)
echo 2. Presentation Microservice (Port 8001) 
echo 3. Math Microservice (Port 8002)
echo.

REM Create a new command prompt window for Laravel
echo Starting Laravel server...
start "Laravel Backend" cmd /k "php artisan serve --host=0.0.0.0 --port=8000"

REM Wait a moment for Laravel to start
timeout /t 3 /nobreak >nul

REM Start Presentation Microservice
echo Starting Presentation Microservice...
cd python_presentation_service
if exist "start_enhanced.bat" (
    start "Presentation Microservice" cmd /k "start_enhanced.bat"
) else (
    start "Presentation Microservice" cmd /k "python main.py"
)
cd ..

REM Wait a moment for Presentation service to start
timeout /t 3 /nobreak >nul

REM Start Math Microservice
echo Starting Math Microservice...
cd python_math_service
if exist "start.bat" (
    start "Math Microservice" cmd /k "start.bat"
) else (
    start "Math Microservice" cmd /k "python main.py"
)
cd ..

echo.
echo ========================================
echo    All Servers Started Successfully!
echo ========================================
echo.
echo Server URLs:
echo - Laravel Backend: http://localhost:8000
echo - Presentation Microservice: http://localhost:8001
echo - Math Microservice: http://localhost:8002
echo.
echo Each server is running in its own command window.
echo Close the individual windows to stop each server.
echo.
echo Press any key to exit this launcher...
pause >nul
