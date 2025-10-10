@echo off
echo Starting Simple FastAPI Presentation Microservice...
echo.

REM Check if virtual environment exists
if not exist "venv" (
    echo Creating virtual environment...
    python -m venv venv
)

REM Activate virtual environment
echo Activating virtual environment...
call venv\Scripts\activate.bat

REM Install dependencies
echo Installing dependencies...
pip install -r requirements_simple.txt

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
python main_simple.py

