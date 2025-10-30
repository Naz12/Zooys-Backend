@echo off
echo ========================================
echo    Stripe Webhook Listener
echo ========================================
echo.
echo Starting Stripe webhook forwarding...
echo Endpoint: http://localhost:8000/api/stripe/webhook
echo.
echo IMPORTANT: Keep this window open while developing!
echo Press Ctrl+C to stop the webhook listener.
echo.
echo ========================================
echo.

"C:\Program Files\stripe\stripe.exe" listen --forward-to localhost:8000/api/stripe/webhook

echo.
echo ========================================
echo Webhook listener stopped.
echo ========================================
pause

