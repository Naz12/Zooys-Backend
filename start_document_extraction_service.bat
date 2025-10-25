@echo off
echo Starting Document Extraction Microservice...
echo.

cd python_document_extractors

echo Installing dependencies...
pip install -r requirements_fastapi.txt

echo.
echo Starting FastAPI service on port 8003...
echo Service will be available at: http://localhost:8003
echo API Documentation: http://localhost:8003/docs
echo Health Check: http://localhost:8003/health
echo.

python start_service.py

pause


