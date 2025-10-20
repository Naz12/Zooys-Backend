@echo off
echo ========================================
echo    Starting Laravel Backend Server
echo ========================================
echo.
echo Starting Laravel development server...
echo Server will be available at: http://localhost:8000
echo.
echo Press Ctrl+C to stop the server
echo.

cd /d C:\xampp\htdocs\zooys_backend_laravel-main
php artisan serve

echo.
echo Laravel server has been stopped.
pause














