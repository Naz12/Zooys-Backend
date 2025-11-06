@echo off
title Stripe Webhook Listener - Fixed
echo ========================================
echo    Stripe Webhook Listener - Fixed
echo ========================================
echo.
echo Starting Stripe webhook forwarding...
echo Endpoint: http://localhost:8000/api/stripe/webhook
echo.
echo IMPORTANT: 
echo - Make sure Laravel server is running on port 8000
echo - Keep this window open while developing
echo - Press Ctrl+C to stop the webhook listener
echo.
echo ========================================
echo.

REM Start Stripe CLI webhook listener
"C:\Program Files\stripe\stripe.exe" listen --forward-to localhost:8000/api/stripe/webhook --print-secret

echo.
echo ========================================
echo Webhook listener stopped.
echo ========================================
pause












