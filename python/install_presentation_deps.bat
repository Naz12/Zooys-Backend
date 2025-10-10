@echo off
echo Installing AI Presentation Generator Dependencies...
echo ==================================================

echo.
echo Installing Python packages...
pip install -r requirements.txt

echo.
echo Testing Python script...
python generate_presentation.py

echo.
echo Installation complete!
echo.
echo Next steps:
echo 1. Test the API endpoints using: php test/test_presentation_api.php
echo 2. Start generating presentations!
echo.
pause
