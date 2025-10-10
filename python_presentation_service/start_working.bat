@echo off
echo Starting Working Presentation Microservice...
echo.

REM Check if virtual environment exists
if not exist "venv" (
    echo Creating virtual environment...
    python -m venv venv
)

REM Activate virtual environment
echo Activating virtual environment...
call venv\Scripts\activate.bat

REM Try to install FastAPI, if it fails, we'll use simple HTTP server
echo Installing dependencies...
pip install fastapi uvicorn pydantic python-multipart 2>nul || echo FastAPI installation failed, will use simple HTTP server

REM Create output directory
if not exist "generated_presentations" (
    echo Creating output directory...
    mkdir generated_presentations
)

echo.
echo Starting microservice on http://localhost:8001
echo Press Ctrl+C to stop the server
echo.

REM Start the server
python main_working.py


