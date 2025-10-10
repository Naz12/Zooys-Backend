@echo off
echo Starting PowerPoint Microservice...
echo.

REM Check if Python is available
py --version >nul 2>&1
if errorlevel 1 (
    echo Python not found. Please install Python 3.11+ and add it to PATH.
    pause
    exit /b 1
)

REM Install dependencies if needed
if not exist "venv" (
    echo Creating virtual environment...
    py -m venv venv
)

echo Activating virtual environment...
call venv\Scripts\activate.bat

echo Installing dependencies...
pip install -r requirements.txt

echo.
echo Starting FastAPI server on http://localhost:8001
echo Press Ctrl+C to stop the server
echo.

py main.py
