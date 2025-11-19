# PowerShell script to test Document Intelligence ingest endpoint
# Usage: .\test-ingest.ps1

# Configuration - UPDATE THESE VALUES FROM YOUR .env FILE
$baseUrl = "https://doc.akmicroservice.com"
$tenantId = "dagu"
$clientId = "dev"
$keyId = "local"
$secret = "YOUR_SECRET_HERE"  # Replace with actual secret from .env

# Request data
$timestamp = [int][double]::Parse((Get-Date -UFormat %s))
$resource = "/v1/ingest/text"
$method = "POST"

# Generate signature
$baseString = "$method|$resource||$timestamp|$clientId|$keyId"
$hmac = New-Object System.Security.Cryptography.HMACSHA256
$hmac.Key = [System.Text.Encoding]::UTF8.GetBytes($secret)
$signature = [System.BitConverter]::ToString($hmac.ComputeHash([System.Text.Encoding]::UTF8.GetBytes($baseString))).Replace("-", "").ToLower()

Write-Host "Testing Document Intelligence /v1/ingest/text endpoint..." -ForegroundColor Cyan
Write-Host ""
Write-Host "Configuration:" -ForegroundColor Yellow
Write-Host "  Base URL: $baseUrl"
Write-Host "  Tenant ID: $tenantId"
Write-Host "  Client ID: $clientId"
Write-Host "  Key ID: $keyId"
Write-Host "  Timestamp: $timestamp"
Write-Host "  Signature: $signature"
Write-Host ""

# Request body
$body = @{
    text = "This is a test ingestion from YouTube transcript. Summarized PDF text or any prepared content goes here."
    filename = "summary.txt"
    lang = "eng"
    force_fallback = $true
    llm_model = "llama3"
    metadata = @{
        tags = @("test", "manual", "youtube")
        source = "youtube"
        video_id = "srbTzkSYfXE"
        date = Get-Date -Format "yyyy-MM-dd"
    }
} | ConvertTo-Json -Depth 10

# Headers
$headers = @{
    "Content-Type" = "application/json"
    "X-Tenant-Id" = $tenantId
    "X-Client-Id" = $clientId
    "X-Key-Id" = $keyId
    "X-Timestamp" = $timestamp.ToString()
    "X-Signature" = $signature
}

Write-Host "Request Body:" -ForegroundColor Yellow
Write-Host $body
Write-Host ""

# Make the request
try {
    Write-Host "Sending request..." -ForegroundColor Cyan
    $response = Invoke-RestMethod -Uri "$baseUrl$resource" -Method Post -Headers $headers -Body $body -ContentType "application/json"
    
    Write-Host "✅ SUCCESS!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Response:" -ForegroundColor Yellow
    $response | ConvertTo-Json -Depth 10
    
    if ($response.doc_id) {
        Write-Host ""
        Write-Host "Document ID: $($response.doc_id)" -ForegroundColor Green
    }
    if ($response.job_id) {
        Write-Host "Job ID: $($response.job_id)" -ForegroundColor Green
    }
} catch {
    Write-Host "❌ FAILED!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
    
    if ($_.Exception.Response) {
        $statusCode = $_.Exception.Response.StatusCode.value__
        Write-Host "Status Code: $statusCode" -ForegroundColor Red
        
        try {
            $errorStream = $_.Exception.Response.GetResponseStream()
            $reader = New-Object System.IO.StreamReader($errorStream)
            $errorBody = $reader.ReadToEnd()
            Write-Host "Error Body: $errorBody" -ForegroundColor Red
        } catch {
            Write-Host "Could not read error response body" -ForegroundColor Yellow
        }
    }
    
    if ($statusCode -eq 404) {
        Write-Host ""
        Write-Host "⚠️  404 Error - Endpoint not found. Possible causes:" -ForegroundColor Yellow
        Write-Host "  1. The endpoint /v1/ingest/text does not exist on the service"
        Write-Host "  2. The service URL is incorrect"
        Write-Host "  3. The service is not running"
        Write-Host "  4. The endpoint path is different (e.g., /api/v1/ingest/text)"
    }
}

