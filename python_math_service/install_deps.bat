@echo off
echo Installing Math Solver Microservice Dependencies...
cd /d "%~dp0"

REM Check if Python is installed
python --version >nul 2>&1
if errorlevel 1 (
    echo Python is not installed or not in PATH
    echo Please install Python 3.8+ and try again
    pause
    exit /b 1
)

REM Create virtual environment
echo Creating virtual environment...
python -m venv venv
if errorlevel 1 (
    echo Failed to create virtual environment
    pause
    exit /b 1
)

REM Activate virtual environment
echo Activating virtual environment...
call venv\Scripts\activate
if errorlevel 1 (
    echo Failed to activate virtual environment
    pause
    exit /b 1
)

REM Upgrade pip
echo Upgrading pip...
python -m pip install --upgrade pip

REM Install dependencies
echo Installing dependencies...
pip install -r requirements.txt
if errorlevel 1 (
    echo Failed to install dependencies
    pause
    exit /b 1
)

REM Install Tesseract OCR (optional)
echo.
echo Tesseract OCR is optional but recommended for image processing.
echo You can install it from: https://github.com/UB-Mannheim/tesseract/wiki
echo.

echo Installation completed successfully!
echo.
echo Next steps:
echo 1. Copy .env.example to .env
echo 2. Configure your OpenAI API key in .env
echo 3. Run start.bat to start the microservice
echo.

pause




