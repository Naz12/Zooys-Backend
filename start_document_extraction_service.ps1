# Document Extraction Microservice Startup Script
Write-Host "Starting Document Extraction Microservice..." -ForegroundColor Green
Write-Host ""

# Change to the python_document_extractors directory
Set-Location python_document_extractors

# Install dependencies
Write-Host "Installing dependencies..." -ForegroundColor Yellow
pip install -r requirements_fastapi.txt

Write-Host ""
Write-Host "Starting FastAPI service on port 8003..." -ForegroundColor Green
Write-Host "Service will be available at: http://localhost:8003" -ForegroundColor Cyan
Write-Host "API Documentation: http://localhost:8003/docs" -ForegroundColor Cyan
Write-Host "Health Check: http://localhost:8003/health" -ForegroundColor Cyan
Write-Host ""

# Start the service
python start_service.py


