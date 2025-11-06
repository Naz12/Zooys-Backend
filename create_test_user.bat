@echo off
echo ========================================
echo    Create Test User for Checkout Testing
echo ========================================
echo.
echo This will create a test user with Free tier subscription
echo for testing the checkout functionality.
echo.
echo Requirements:
echo - Laravel server must be running
echo - Database must be seeded with plans
echo.
echo Press any key to continue...
pause
echo.

php create_test_user.php

echo.
echo ========================================
echo Test user creation completed!
echo ========================================
pause












