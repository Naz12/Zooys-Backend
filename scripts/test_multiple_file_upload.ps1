# Multiple File Upload Test Script
# Tests the file upload endpoint with multiple files

$ErrorActionPreference = "Continue"

# Configuration
$baseUrl = "http://localhost:8000/api"
$bearerToken = "207|vhs65UeUfk2kpYoTpJX3zIClf4zQyixWZdkbfPYXf7ae0dfe"

# Create results directory
$resultsDir = "scripts/results/file_upload"
if (-not (Test-Path $resultsDir)) {
    New-Item -ItemType Directory -Path $resultsDir -Force | Out-Null
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Multiple File Upload Test" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Function to make API calls
function Invoke-ApiCall {
    param(
        [string]$Endpoint,
        [string]$Method = "GET",
        [hashtable]$Headers = @{},
        [object]$Body = $null,
        [string]$ContentType = $null
    )
    
    try {
        $params = @{
            Uri = "$baseUrl/$Endpoint"
            Method = $Method
            Headers = $Headers
        }
        
        if ($Body) {
            $params['Body'] = $Body
        }
        
        if ($ContentType) {
            $params['ContentType'] = $ContentType
        }
        
        $response = Invoke-RestMethod @params
        return @{
            success = $true
            data = $response
        }
    } catch {
        $errorDetails = $_.ErrorDetails.Message
        if ($errorDetails) {
            try {
                $errorJson = $errorDetails | ConvertFrom-Json
                return @{
                    success = $false
                    error = $errorJson
                    status_code = $_.Exception.Response.StatusCode.value__
                }
            } catch {
                return @{
                    success = $false
                    error = $errorDetails
                    status_code = $_.Exception.Response.StatusCode.value__
                }
            }
        }
        return @{
            success = $false
            error = $_.Exception.Message
            status_code = $_.Exception.Response.StatusCode.value__
        }
    }
}

# Function to upload files using multipart/form-data
function Upload-Files {
    param(
        [string]$Endpoint,
        [string]$Token,
        [array]$FilePaths,
        [bool]$UseArrayNotation = $true
    )
    
    try {
        # Create boundary for multipart form data
        $boundary = [System.Guid]::NewGuid().ToString()
        
        # Build multipart form data
        $bodyLines = @()
        
        foreach ($filePath in $FilePaths) {
            if (Test-Path $filePath) {
                $fileName = [System.IO.Path]::GetFileName($filePath)
                $fileContent = [System.IO.File]::ReadAllBytes($filePath)
                $fileContentBase64 = [System.Convert]::ToBase64String($fileContent)
                
                # Determine field name
                $fieldName = if ($UseArrayNotation) { "files[]" } else { "file" }
                
                $bodyLines += "--$boundary"
                $bodyLines += "Content-Disposition: form-data; name=`"$fieldName`"; filename=`"$fileName`""
                $bodyLines += "Content-Type: application/pdf"
                $bodyLines += ""
                $bodyLines += [System.Text.Encoding]::UTF8.GetString($fileContent)
            } else {
                Write-Host "Warning: File not found: $filePath" -ForegroundColor Yellow
            }
        }
        
        $bodyLines += "--$boundary--"
        
        $body = $bodyLines -join "`r`n"
        
        # Make request using Invoke-WebRequest for better multipart support
        $response = Invoke-WebRequest -Uri "$baseUrl/$Endpoint" `
            -Method POST `
            -Headers @{
                "Authorization" = "Bearer $Token"
            } `
            -ContentType "multipart/form-data; boundary=$boundary" `
            -Body ([System.Text.Encoding]::UTF8.GetBytes($body))
        
        return @{
            success = $true
            data = ($response.Content | ConvertFrom-Json)
        }
    } catch {
        $errorDetails = $_.ErrorDetails.Message
        if ($errorDetails) {
            try {
                $errorJson = $errorDetails | ConvertFrom-Json
                return @{
                    success = $false
                    error = $errorJson
                    status_code = $_.Exception.Response.StatusCode.value__
                }
            } catch {
                return @{
                    success = $false
                    error = $errorDetails
                    status_code = $_.Exception.Response.StatusCode.value__
                }
            }
        }
        return @{
            success = $false
            error = $_.Exception.Message
        }
    }
}

# Find test PDF files in the project
Write-Host "Looking for test PDF files..." -ForegroundColor Yellow

$testFiles = @()
$possibleLocations = @(
    "test 4 pages.pdf",
    "1 page test.pdf",
    "test final.pdf",
    "storage/app/public/uploads/files/*.pdf"
)

# Try to find at least 2 PDF files
foreach ($location in $possibleLocations) {
    if (Test-Path $location) {
        $testFiles += (Resolve-Path $location).Path
        if ($testFiles.Count -ge 2) {
            break
        }
    }
}

# If no files found, use existing uploaded files from storage
if ($testFiles.Count -eq 0) {
    $uploadedFiles = Get-ChildItem -Path "storage/app/public/uploads/files" -Filter "*.pdf" -ErrorAction SilentlyContinue | Select-Object -First 2
    if ($uploadedFiles) {
        foreach ($file in $uploadedFiles) {
            $testFiles += $file.FullName
        }
    }
}

if ($testFiles.Count -eq 0) {
    Write-Host "ERROR: No test PDF files found!" -ForegroundColor Red
    Write-Host "Please place at least 2 PDF files in the project root or they will be created." -ForegroundColor Yellow
    
    # Create dummy PDF files for testing
    Write-Host "`nCreating test PDF files..." -ForegroundColor Yellow
    
    $pdf1Content = "%PDF-1.4`n1 0 obj`n<< /Type /Catalog /Pages 2 0 R >>`nendobj`n2 0 obj`n<< /Type /Pages /Kids [3 0 R] /Count 1 >>`nendobj`n3 0 obj`n<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >> /MediaBox [0 0 612 792] /Contents 4 0 R >>`nendobj`n4 0 obj`n<< /Length 44 >>`nstream`nBT /F1 24 Tf 100 700 Td (Test PDF 1) Tj ET`nendstream`nendobj`nxref`n0 5`n0000000000 65535 f`n0000000009 00000 n`n0000000058 00000 n`n0000000115 00000 n`n0000000315 00000 n`ntrailer`n<< /Size 5 /Root 1 0 R >>`nstartxref`n407`n%%EOF"
    $pdf2Content = "%PDF-1.4`n1 0 obj`n<< /Type /Catalog /Pages 2 0 R >>`nendobj`n2 0 obj`n<< /Type /Pages /Kids [3 0 R] /Count 1 >>`nendobj`n3 0 obj`n<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >> /MediaBox [0 0 612 792] /Contents 4 0 R >>`nendobj`n4 0 obj`n<< /Length 44 >>`nstream`nBT /F1 24 Tf 100 700 Td (Test PDF 2) Tj ET`nendstream`nendobj`nxref`n0 5`n0000000000 65535 f`n0000000009 00000 n`n0000000058 00000 n`n0000000115 00000 n`n0000000315 00000 n`ntrailer`n<< /Size 5 /Root 1 0 R >>`nstartxref`n407`n%%EOF"
    
    $testFile1 = "test_upload_1.pdf"
    $testFile2 = "test_upload_2.pdf"
    
    [System.IO.File]::WriteAllText($testFile1, $pdf1Content)
    [System.IO.File]::WriteAllText($testFile2, $pdf2Content)
    
    $testFiles = @($testFile1, $testFile2)
    Write-Host "Created: $testFile1" -ForegroundColor Green
    Write-Host "Created: $testFile2" -ForegroundColor Green
}

Write-Host "`nFound $($testFiles.Count) test file(s):" -ForegroundColor Green
foreach ($file in $testFiles) {
    $fileName = [System.IO.Path]::GetFileName($file)
    $fileSize = (Get-Item $file).Length
    Write-Host "  - $fileName ($fileSize bytes)" -ForegroundColor Cyan
}

Write-Host ""

# Test 1: Test endpoint with multiple files (array notation)
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Test 1: Test Endpoint (Array Notation)" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Endpoint: POST /files/test-upload" -ForegroundColor Yellow
Write-Host "Files: $($testFiles.Count) files with 'files[]' notation" -ForegroundColor Yellow
Write-Host ""

$testResult = Upload-Files -Endpoint "files/test-upload" -Token $bearerToken -FilePaths $testFiles -UseArrayNotation $true

if ($testResult.success) {
    Write-Host "✓ Test endpoint response:" -ForegroundColor Green
    Write-Host "  - File count: $($testResult.data.file_count)" -ForegroundColor $(if ($testResult.data.file_count -eq $testFiles.Count) { "Green" } else { "Red" })
    Write-Host "  - Files is array: $($testResult.data.files_is_array)" -ForegroundColor $(if ($testResult.data.files_is_array) { "Green" } else { "Red" })
    Write-Host "  - Has 'file': $($testResult.data.has_file)" -ForegroundColor Cyan
    Write-Host "  - Has 'files': $($testResult.data.has_files)" -ForegroundColor Cyan
    
    if ($testResult.data.all_files) {
        Write-Host "`n  Files received:" -ForegroundColor Cyan
        foreach ($file in $testResult.data.all_files) {
            $fileName = $file.name
            $fileSize = $file.size
            $fileMime = $file.mime
            Write-Host "    - $fileName ($fileSize bytes, $fileMime)" -ForegroundColor Gray
        }
    }
    
    $testResult.data | ConvertTo-Json -Depth 10 | Set-Content "$resultsDir/test_endpoint_array.json"
    Write-Host "`n✓ Saved to: $resultsDir/test_endpoint_array.json" -ForegroundColor Green
} else {
    Write-Host "✗ Test endpoint failed!" -ForegroundColor Red
    Write-Host "  Error: $($testResult.error)" -ForegroundColor Red
    $testResult | ConvertTo-Json -Depth 10 | Set-Content "$resultsDir/test_endpoint_array_error.json"
}

Write-Host ""

# Test 2: Actual upload with multiple files (array notation)
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Test 2: Actual Upload (Array Notation)" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Endpoint: POST /files/upload" -ForegroundColor Yellow
Write-Host "Files: $($testFiles.Count) files with 'files[]' notation" -ForegroundColor Yellow
Write-Host ""

$uploadResult = Upload-Files -Endpoint "files/upload" -Token $bearerToken -FilePaths $testFiles -UseArrayNotation $true

if ($uploadResult.success) {
    Write-Host "✓ Upload successful!" -ForegroundColor Green
    
    if ($uploadResult.data.uploaded_count) {
        # Multiple files response
        Write-Host "  - Message: $($uploadResult.data.message)" -ForegroundColor Green
        Write-Host "  - Uploaded count: $($uploadResult.data.uploaded_count)" -ForegroundColor $(if ($uploadResult.data.uploaded_count -eq $testFiles.Count) { "Green" } else { "Yellow" })
        Write-Host "  - Error count: $($uploadResult.data.error_count)" -ForegroundColor $(if ($uploadResult.data.error_count -eq 0) { "Green" } else { "Red" })
        
        if ($uploadResult.data.file_uploads) {
            Write-Host "`n  Uploaded files:" -ForegroundColor Cyan
            foreach ($upload in $uploadResult.data.file_uploads) {
                $fileUpload = $upload.file_upload
                Write-Host "    - ID: $($fileUpload.id) | Name: $($fileUpload.original_name) | Size: $($fileUpload.file_size) bytes" -ForegroundColor Gray
            }
        }
        
        if ($uploadResult.data.errors -and $uploadResult.data.errors.Count -gt 0) {
            Write-Host "`n  Errors:" -ForegroundColor Red
            foreach ($error in $uploadResult.data.errors) {
                Write-Host "    - [$($error.index)] $($error.filename): $($error.error)" -ForegroundColor Red
            }
        }
    } else {
        # Single file response (means array notation didn't work)
        Write-Host "  ⚠ WARNING: Received SINGLE file response!" -ForegroundColor Yellow
        Write-Host "  This means array notation didn't work properly." -ForegroundColor Yellow
        Write-Host "  - File ID: $($uploadResult.data.file_upload.id)" -ForegroundColor Cyan
        Write-Host "  - File name: $($uploadResult.data.file_upload.original_name)" -ForegroundColor Cyan
    }
    
    $uploadResult.data | ConvertTo-Json -Depth 10 | Set-Content "$resultsDir/upload_array.json"
    Write-Host "`n✓ Saved to: $resultsDir/upload_array.json" -ForegroundColor Green
} else {
    Write-Host "✗ Upload failed!" -ForegroundColor Red
    Write-Host "  Status: $($uploadResult.status_code)" -ForegroundColor Red
    Write-Host "  Error: $($uploadResult.error)" -ForegroundColor Red
    $uploadResult | ConvertTo-Json -Depth 10 | Set-Content "$resultsDir/upload_array_error.json"
}

Write-Host ""

# Test 3: Test endpoint with single file notation (for comparison)
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Test 3: Test Endpoint (Single File Notation)" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Endpoint: POST /files/test-upload" -ForegroundColor Yellow
Write-Host "Files: $($testFiles.Count) files with 'file' notation (should only get last one)" -ForegroundColor Yellow
Write-Host ""

$testResultSingle = Upload-Files -Endpoint "files/test-upload" -Token $bearerToken -FilePaths $testFiles -UseArrayNotation $false

if ($testResultSingle.success) {
    Write-Host "✓ Test endpoint response:" -ForegroundColor Green
    Write-Host "  - File count: $($testResultSingle.data.file_count) $(if ($testResultSingle.data.file_count -eq 1) { '(Expected: only last file received)' } else { '(Unexpected!)' })" -ForegroundColor $(if ($testResultSingle.data.file_count -eq 1) { "Yellow" } else { "Red" })
    Write-Host "  - Files is array: $($testResultSingle.data.files_is_array)" -ForegroundColor Cyan
    Write-Host "  - Has 'file': $($testResultSingle.data.has_file)" -ForegroundColor Cyan
    Write-Host "  - Has 'files': $($testResultSingle.data.has_files)" -ForegroundColor Cyan
    
    $testResultSingle.data | ConvertTo-Json -Depth 10 | Set-Content "$resultsDir/test_endpoint_single.json"
    Write-Host "`n✓ Saved to: $resultsDir/test_endpoint_single.json" -ForegroundColor Green
} else {
    Write-Host "✗ Test endpoint failed!" -ForegroundColor Red
    Write-Host "  Error: $($testResultSingle.error)" -ForegroundColor Red
}

Write-Host ""

# Summary
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Summary" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "Test files used: $($testFiles.Count)" -ForegroundColor Cyan
Write-Host ""

Write-Host "Results:" -ForegroundColor Cyan
Write-Host "  1. Test endpoint (array): " -NoNewline
if ($testResult.success -and $testResult.data.file_count -eq $testFiles.Count -and $testResult.data.files_is_array) {
    Write-Host "✓ PASS (Detected $($testFiles.Count) files correctly)" -ForegroundColor Green
} else {
    Write-Host "✗ FAIL" -ForegroundColor Red
}

Write-Host "  2. Actual upload (array): " -NoNewline
if ($uploadResult.success -and $uploadResult.data.uploaded_count -eq $testFiles.Count) {
    Write-Host "✓ PASS (Uploaded $($testFiles.Count) files successfully)" -ForegroundColor Green
} elseif ($uploadResult.success -and $uploadResult.data.file_upload) {
    Write-Host "⚠ PARTIAL (Only 1 file uploaded, array notation may not be working)" -ForegroundColor Yellow
} else {
    Write-Host "✗ FAIL" -ForegroundColor Red
}

Write-Host "  3. Test endpoint (single): " -NoNewline
if ($testResultSingle.success -and $testResultSingle.data.file_count -eq 1) {
    Write-Host "PASS (Correctly received only 1 file with single notation)" -ForegroundColor Green
} else {
    Write-Host "UNEXPECTED RESULT" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "All results saved to: $resultsDir" -ForegroundColor Cyan
Write-Host ""

# Cleanup created test files if any
if ($testFiles -contains "test_upload_1.pdf" -or $testFiles -contains "test_upload_2.pdf") {
    Write-Host "Cleaning up created test files..." -ForegroundColor Yellow
    if (Test-Path "test_upload_1.pdf") { Remove-Item "test_upload_1.pdf" -Force }
    if (Test-Path "test_upload_2.pdf") { Remove-Item "test_upload_2.pdf" -Force }
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Testing Complete!" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

