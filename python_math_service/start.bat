@echo off
echo Starting Math Solver Microservice...
cd /d "%~dp0"

REM Check if virtual environment exists
if not exist venv (
    echo Creating virtual environment...
    python -m venv venv
    if errorlevel 1 (
        echo Failed to create virtual environment
        pause
        exit /b 1
    )
)

REM Activate virtual environment
echo Activating virtual environment...
call venv\Scripts\activate
if errorlevel 1 (
    echo Failed to activate virtual environment
    pause
    exit /b 1
)

REM Install dependencies
echo Installing dependencies...
pip install -r requirements.txt
if errorlevel 1 (
    echo Failed to install dependencies
    pause
    exit /b 1
)

REM Check if .env file exists
if not exist .env (
    echo Creating .env file...
    copy .env.example .env
    echo Please configure your .env file with OpenAI API key
    pause
)

REM Start the microservice
echo Starting Math Solver Microservice on port 8002...
uvicorn main:app --host 0.0.0.0 --port 8002 --reload

pause




