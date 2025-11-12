@echo off
REM ============================================
REM Token Generator for Zooys Backend API
REM ============================================
REM This script logs in and generates a new authentication token
REM Usage: generate_token.bat [email] [password] [base_url]
REM ============================================

setlocal enabledelayedexpansion

REM Set default values
set "EMAIL=test-subscription@example.com"
set "PASSWORD=password"
set "BASE_URL=http://localhost:8000"

REM Override with command line arguments if provided
if not "%~1"=="" set "EMAIL=%~1"
if not "%~2"=="" set "PASSWORD=%~2"
if not "%~3"=="" set "BASE_URL=%~3"

echo ============================================
echo Zooys Backend - Token Generator
echo ============================================
echo.
echo Email: %EMAIL%
echo Base URL: %BASE_URL%
echo.

REM Execute PowerShell script
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0generate_token.ps1" -Email "%EMAIL%" -Password "%PASSWORD%" -BaseUrl "%BASE_URL%"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ============================================
    echo Token generation completed successfully!
    echo ============================================
    echo.
    echo You can now use this token in your API requests:
    echo   Authorization: Bearer [token]
    echo.
    echo Token is also saved in: token.txt
    echo.
) else (
    echo.
    echo ============================================
    echo Token generation failed!
    echo ============================================
    echo.
    echo Please check:
    echo   1. The server is running at %BASE_URL%
    echo   2. The email and password are correct
    echo   3. The user exists in the database
    echo.
    exit /b 1
)

endlocal
