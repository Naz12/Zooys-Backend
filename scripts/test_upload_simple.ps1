# Simple Multiple File Upload Test
$bearerToken = "207|vhs65UeUfk2kpYoTpJX3zIClf4zQyixWZdkbfPYXf7ae0dfe"
$baseUrl = "http://localhost:8000/api"

Write-Host "Testing Multiple File Upload" -ForegroundColor Cyan
Write-Host "=" * 50 -ForegroundColor Cyan
Write-Host ""

# Find or create test files
$file1 = "test_upload_1.pdf"
$file2 = "test_upload_2.pdf"

if (-not (Test-Path $file1)) {
    $pdfContent = "%PDF-1.4`n1 0 obj`n<</Type/Catalog/Pages 2 0 R>>endobj`n2 0 obj`n<</Type/Pages/Count 1/Kids[3 0 R]>>endobj`n3 0 obj`n<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]>>endobj`nxref`n0 4`n0000000000 65535 f`n0000000009 00000 n`n0000000058 00000 n`n0000000115 00000 n`ntrailer`n<</Size 4/Root 1 0 R>>startxref`n175`n%%EOF"
    [System.IO.File]::WriteAllText($file1, $pdfContent)
    [System.IO.File]::WriteAllText($file2, $pdfContent)
    Write-Host "Created test files: $file1, $file2" -ForegroundColor Green
}

Write-Host "Test files ready" -ForegroundColor Green
Write-Host ""

# Test 1: Using test endpoint with array notation
Write-Host "Test 1: Test endpoint with files[] notation" -ForegroundColor Yellow
Write-Host "-" * 50 -ForegroundColor Gray

try {
    $uri = "$baseUrl/files/test-upload"
    $boundary = [System.Guid]::NewGuid().ToString()
    $LF = "`r`n"
    
    $bodyLines = @()
    
    # Add first file
    $bodyLines += "--$boundary"
    $bodyLines += 'Content-Disposition: form-data; name="files[]"; filename="test1.pdf"'
    $bodyLines += "Content-Type: application/pdf"
    $bodyLines += ""
    $bodyLines += [System.IO.File]::ReadAllText($file1)
    
    # Add second file
    $bodyLines += "--$boundary"
    $bodyLines += 'Content-Disposition: form-data; name="files[]"; filename="test2.pdf"'
    $bodyLines += "Content-Type: application/pdf"
    $bodyLines += ""
    $bodyLines += [System.IO.File]::ReadAllText($file2)
    
    $bodyLines += "--$boundary--"
    
    $body = $bodyLines -join $LF
    
    $response = Invoke-RestMethod -Uri $uri -Method POST `
        -Headers @{ "Authorization" = "Bearer $bearerToken" } `
        -ContentType "multipart/form-data; boundary=$boundary" `
        -Body ([System.Text.Encoding]::UTF8.GetBytes($body))
    
    Write-Host "Response:" -ForegroundColor Green
    Write-Host "  file_count: $($response.file_count)"
    Write-Host "  files_is_array: $($response.files_is_array)"
    Write-Host "  has_file: $($response.has_file)"
    Write-Host "  has_files: $($response.has_files)"
    
    if ($response.file_count -eq 2 -and $response.files_is_array -eq $true) {
        Write-Host "`nSUCCESS: Array notation working correctly!" -ForegroundColor Green
    } else {
        Write-Host "`nWARNING: Array notation NOT working" -ForegroundColor Yellow
    }
    
} catch {
    Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# Test 2: Actual upload with array notation
Write-Host "Test 2: Actual upload with files[] notation" -ForegroundColor Yellow
Write-Host "-" * 50 -ForegroundColor Gray

try {
    $uri = "$baseUrl/files/upload"
    $boundary = [System.Guid]::NewGuid().ToString()
    $LF = "`r`n"
    
    $bodyLines = @()
    
    # Add first file
    $bodyLines += "--$boundary"
    $bodyLines += 'Content-Disposition: form-data; name="files[]"; filename="test1.pdf"'
    $bodyLines += "Content-Type: application/pdf"
    $bodyLines += ""
    $bodyLines += [System.IO.File]::ReadAllText($file1)
    
    # Add second file
    $bodyLines += "--$boundary"
    $bodyLines += 'Content-Disposition: form-data; name="files[]"; filename="test2.pdf"'
    $bodyLines += "Content-Type: application/pdf"
    $bodyLines += ""
    $bodyLines += [System.IO.File]::ReadAllText($file2)
    
    $bodyLines += "--$boundary--"
    
    $body = $bodyLines -join $LF
    
    $response = Invoke-RestMethod -Uri $uri -Method POST `
        -Headers @{ "Authorization" = "Bearer $bearerToken" } `
        -ContentType "multipart/form-data; boundary=$boundary" `
        -Body ([System.Text.Encoding]::UTF8.GetBytes($body))
    
    Write-Host "Response:" -ForegroundColor Green
    
    if ($response.uploaded_count) {
        # Multiple files response
        Write-Host "  message: $($response.message)"
        Write-Host "  uploaded_count: $($response.uploaded_count)"
        Write-Host "  error_count: $($response.error_count)"
        
        Write-Host "`n  Uploaded files:"
        foreach ($upload in $response.file_uploads) {
            Write-Host "    - ID: $($upload.file_upload.id) | Name: $($upload.file_upload.original_name)"
        }
        
        if ($response.uploaded_count -eq 2) {
            Write-Host "`nSUCCESS: 2 files uploaded!" -ForegroundColor Green
        } else {
            Write-Host "`nWARNING: Expected 2 files but got $($response.uploaded_count)" -ForegroundColor Yellow
        }
    } else {
        # Single file response
        Write-Host "  message: $($response.message)"
        Write-Host "  file_id: $($response.file_upload.id)"
        Write-Host "  file_name: $($response.file_upload.original_name)"
        Write-Host "`nWARNING: Only 1 file uploaded (array notation not working)" -ForegroundColor Yellow
    }
    
} catch {
    Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.ErrorDetails.Message) {
        Write-Host "Details: $($_.ErrorDetails.Message)" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "=" * 50 -ForegroundColor Cyan
Write-Host "Test Complete!" -ForegroundColor Cyan

# Cleanup
if (Test-Path $file1) { Remove-Item $file1 -Force }
if (Test-Path $file2) { Remove-Item $file2 -Force }

