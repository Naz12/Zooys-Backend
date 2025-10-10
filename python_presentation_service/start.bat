@echo off
echo Starting FastAPI Presentation Microservice...
echo.

REM Activate virtual environment
echo Activating virtual environment...
call venv\Scripts\activate.bat

REM Create output directory
if not exist "generated_presentations" (
    echo Creating output directory...
    mkdir generated_presentations
)

echo.
echo Starting FastAPI server on http://localhost:8001
echo Press Ctrl+C to stop the server
echo.

REM Start the server
py main.py
