@echo off
echo Starting Minimal FastAPI Presentation Microservice...
echo.

REM Check if virtual environment exists
if not exist "venv" (
    echo Creating virtual environment...
    python -m venv venv
)

REM Activate virtual environment
echo Activating virtual environment...
call venv\Scripts\activate.bat

REM Install minimal dependencies
echo Installing minimal dependencies...
pip install -r requirements_minimal.txt

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
python main_minimal.py


