@echo off
echo Starting Both Laravel and FastAPI Microservice...
echo.

REM Start Laravel in a new window
echo Starting Laravel server...
start "Laravel Server" cmd /k "cd /d C:\xampp\htdocs\zooys_backend_laravel-main && php artisan serve"

REM Wait a moment
timeout /t 3 /nobreak >nul

REM Start FastAPI in a new window
echo Starting FastAPI microservice...
start "FastAPI Microservice" cmd /k "cd /d C:\xampp\htdocs\zooys_backend_laravel-main\python_presentation_service && venv\Scripts\activate.bat && py main.py"

echo.
echo Both servers are starting in separate windows:
echo - Laravel: http://localhost:8000
echo - FastAPI: http://localhost:8001
echo.
echo Press any key to exit this launcher...
pause >nul


