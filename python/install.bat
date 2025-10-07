@echo off
echo Installing Python dependencies for YouTube caption extraction...
echo.

REM Check if Python is available
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo Python is not installed or not in PATH.
    echo Please install Python from https://www.python.org/downloads/
    echo Make sure to check "Add Python to PATH" during installation.
    pause
    exit /b 1
)

echo Python found. Installing dependencies...
pip install -r requirements.txt

if %errorlevel% equ 0 (
    echo.
    echo Installation completed successfully!
    echo You can now use the YouTube caption extractor.
) else (
    echo.
    echo Installation failed. Please check the error messages above.
)

pause
