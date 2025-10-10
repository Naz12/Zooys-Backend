@echo off
echo Starting FastAPI Presentation Microservice...
echo.
cd /d "C:\xampp\htdocs\zooys_backend_laravel-main\python_presentation_service"
echo Activating virtual environment...
call venv\Scripts\activate.bat
echo.
echo Starting FastAPI on http://localhost:8001
echo Press Ctrl+C to stop the server
echo.
py main.py


