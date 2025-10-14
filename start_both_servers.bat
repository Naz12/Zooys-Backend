@echo off
echo Starting Laravel Server...
echo.

REM Start Laravel in a new window
echo Starting Laravel server...
start "Laravel Server" cmd /k "cd /d C:\xampp\htdocs\zooys_backend_laravel-main && php artisan serve"

echo.
echo Laravel server is starting:
echo - Laravel: http://localhost:8000
echo.
echo Note: Math and Presentation microservices are now external.
echo Make sure they are running on their respective ports:
echo - Math Microservice: http://localhost:8002
echo - Presentation Microservice: http://localhost:8001
echo.
echo Press any key to exit this launcher...
pause >nul


