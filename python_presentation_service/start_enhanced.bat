@echo off
title Enhanced Presentation Microservice

echo Starting Enhanced Presentation Microservice...
echo.

REM Check if virtual environment exists
if not exist "venv\Scripts\activate.bat" (
    echo Error: Virtual environment not found!
    echo Please run: python -m venv venv
    echo Then run: venv\Scripts\activate
    echo Then run: pip install -r requirements.txt
    pause
    exit /b 1
)

REM Activate virtual environment
echo Activating virtual environment...
call venv\Scripts\activate.bat

REM Check if dependencies are installed
echo Checking dependencies...
python -c "import openai, fastapi, uvicorn" 2>nul
if errorlevel 1 (
    echo Installing dependencies...
    pip install -r requirements.txt
    if errorlevel 1 (
        echo Error: Failed to install dependencies!
        pause
        exit /b 1
    )
)

REM Start the microservice
echo Starting Enhanced Presentation Microservice on port 8001...
echo.
echo Available endpoints:
echo - GET  /health - Health check
echo - POST /generate-outline - Generate presentation outline
echo - POST /generate-content - Generate slide content
echo - POST /export - Export to PowerPoint
echo - GET  /progress/{operation_id} - Get progress status
echo - GET  /templates - Get available templates
echo.
echo Press Ctrl+C to stop the service
echo.

python main.py