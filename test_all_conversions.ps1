# Comprehensive Document Conversion Test
# Tests all input/output format combinations

$BASE_URL = "http://localhost:8000/api"
$TOKEN = "207|vhs65UeUfk2kpYoTpJX3zIClf4zQyixWZdkbfPYXf7ae0dfe"  # Replace with your token

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  Document Conversion Test Suite" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

$headers = @{
    "Authorization" = "Bearer $TOKEN"
    "Accept" = "application/json"
}

# Define all target formats
$targetFormats = @("pdf", "png", "jpg", "jpeg", "docx", "txt", "html")

# Test file IDs (you need to upload these files first)
$testFiles = @{
    "pdf" = @{ "file_id" = "207"; "name" = "test.pdf"; "description" = "PDF file" }
    "docx" = @{ "file_id" = ""; "name" = "test.docx"; "description" = "Word document" }
    "txt" = @{ "file_id" = ""; "name" = "test.txt"; "description" = "Text file" }
    "image_jpg" = @{ "file_id" = "204"; "name" = "test.jpg"; "description" = "JPEG image" }
    "image_png" = @{ "file_id" = ""; "name" = "test.png"; "description" = "PNG image" }
    "html" = @{ "file_id" = ""; "name" = "test.html"; "description" = "HTML file" }
}

# Results tracking
$results = @()
$successCount = 0
$failCount = 0
$totalTests = 0

# Helper function to wait for job completion
function Wait-JobCompletion {
    param (
        [string]$jobId,
        [int]$maxAttempts = 30
    )
    
    for ($i = 0; $i -lt $maxAttempts; $i++) {
        Start-Sleep -Seconds 2
        
        try {
            $statusResponse = Invoke-WebRequest -Uri "$BASE_URL/status?job_id=$jobId" `
                -Method GET -Headers $headers -ErrorAction Stop
            $statusData = $statusResponse.Content | ConvertFrom-Json
            
            if ($statusData.status -eq "completed") {
                return @{ "success" = $true; "data" = $statusData }
            }
            
            if ($statusData.status -eq "failed") {
                $errorMsg = if ($statusData.error) { $statusData.error } else { "Job failed" }
                return @{ "success" = $false; "error" = $errorMsg }
            }
        } catch {
            return @{ "success" = $false; "error" = $_.Exception.Message }
        }
    }
    
    return @{ "success" = $false; "error" = "Timeout" }
}

# Helper function to test conversion
function Test-Conversion {
    param (
        [string]$inputType,
        [string]$fileId,
        [string]$targetFormat,
        [string]$inputDescription
    )
    
    if ([string]::IsNullOrEmpty($fileId)) {
        return @{
            "input" = $inputType
            "input_desc" = $inputDescription
            "output" = $targetFormat
            "status" = "SKIPPED"
            "message" = "No test file uploaded"
            "job_id" = "N/A"
            "processing_time" = 0
        }
    }
    
    Write-Host "Testing: " -NoNewline
    Write-Host "$inputDescription → $targetFormat" -NoNewline -ForegroundColor Yellow
    Write-Host " ... " -NoNewline
    
    try {
        # Submit conversion job
        $body = @{
            file_id = $fileId
            target_format = $targetFormat
            options = @{}
        } | ConvertTo-Json
        
        $response = Invoke-WebRequest -Uri "$BASE_URL/file-processing/convert" `
            -Method POST -Headers $headers -Body $body `
            -ContentType "application/json" -ErrorAction Stop
        
        $data = $response.Content | ConvertFrom-Json
        $jobId = $data.job_id
        
        # Wait for completion
        $startTime = Get-Date
        $result = Wait-JobCompletion -jobId $jobId
        $endTime = Get-Date
        $processingTime = ($endTime - $startTime).TotalSeconds
        
        if ($result.success) {
            # Try to get result
            try {
                $resultResponse = Invoke-WebRequest -Uri "$BASE_URL/result?job_id=$jobId" `
                    -Method GET -Headers $headers -ErrorAction Stop
                $resultData = $resultResponse.Content | ConvertFrom-Json
                
                if ($resultData.success) {
                    Write-Host "[OK] SUCCESS" -ForegroundColor Green
                    Write-Host "    Time: $([math]::Round($processingTime, 2))s" -ForegroundColor Gray
                    
                    return @{
                        "input" = $inputType
                        "input_desc" = $inputDescription
                        "output" = $targetFormat
                        "status" = "SUCCESS"
                        "message" = "Conversion completed"
                        "job_id" = $jobId
                        "processing_time" = [math]::Round($processingTime, 2)
                        "download_url" = $(if ($resultData.data.result.download_urls -and $resultData.data.result.download_urls[0]) { $resultData.data.result.download_urls[0] } else { "N/A" })
                    }
                } else {
                    Write-Host "[X] FAILED (Result)" -ForegroundColor Red
                    Write-Host "    Error: $($resultData.error)" -ForegroundColor DarkRed
                    
                    return @{
                        "input" = $inputType
                        "input_desc" = $inputDescription
                        "output" = $targetFormat
                        "status" = "FAILED"
                        "message" = "Result retrieval failed: $($resultData.error)"
                        "job_id" = $jobId
                        "processing_time" = [math]::Round($processingTime, 2)
                    }
                }
            } catch {
                Write-Host "[X] FAILED (Get Result)" -ForegroundColor Red
                Write-Host "    Error: $($_.Exception.Message)" -ForegroundColor DarkRed
                
                return @{
                    "input" = $inputType
                    "input_desc" = $inputDescription
                    "output" = $targetFormat
                    "status" = "FAILED"
                    "message" = "Result retrieval error: $($_.Exception.Message)"
                    "job_id" = $jobId
                    "processing_time" = [math]::Round($processingTime, 2)
                }
            }
        } else {
            Write-Host "[X] FAILED (Job)" -ForegroundColor Red
            Write-Host "    Error: $($result.error)" -ForegroundColor DarkRed
            
            return @{
                "input" = $inputType
                "input_desc" = $inputDescription
                "output" = $targetFormat
                "status" = "FAILED"
                "message" = "Job failed: $($result.error)"
                "job_id" = $jobId
                "processing_time" = [math]::Round($processingTime, 2)
            }
        }
    } catch {
        Write-Host "[!] ERROR" -ForegroundColor Red
        Write-Host "    Error: $($_.Exception.Message)" -ForegroundColor DarkRed
        
        return @{
            "input" = $inputType
            "input_desc" = $inputDescription
            "output" = $targetFormat
            "status" = "ERROR"
            "message" = $_.Exception.Message
            "job_id" = "N/A"
            "processing_time" = 0
        }
    }
}

# Display test file configuration
Write-Host "Test Files Configuration:" -ForegroundColor Cyan
Write-Host "-------------------------" -ForegroundColor Cyan
foreach ($key in $testFiles.Keys) {
    $file = $testFiles[$key]
    $statusText = if ([string]::IsNullOrEmpty($file.file_id)) { "[X] NOT UPLOADED" } else { "[OK] Ready (ID: $($file.file_id))" }
    Write-Host "$($file.description): " -NoNewline
    Write-Host $statusText -ForegroundColor $(if ([string]::IsNullOrEmpty($file.file_id)) { "Red" } else { "Green" })
}
Write-Host ""

# Ask user to continue
$continue = Read-Host "Press Enter to start testing (or Ctrl+C to cancel)"

Write-Host ""
Write-Host "Starting conversion tests..." -ForegroundColor Cyan
Write-Host "=============================" -ForegroundColor Cyan
Write-Host ""

# Run tests for each input file type
foreach ($inputKey in $testFiles.Keys) {
    $inputFile = $testFiles[$inputKey]
    
    if ([string]::IsNullOrEmpty($inputFile.file_id)) {
        Write-Host "Skipping $($inputFile.description) (no file uploaded)" -ForegroundColor DarkGray
        Write-Host ""
        continue
    }
    
    Write-Host "=" * 50 -ForegroundColor Cyan
    Write-Host "Testing Input: $($inputFile.description)" -ForegroundColor Cyan
    Write-Host "=" * 50 -ForegroundColor Cyan
    Write-Host ""
    
    foreach ($targetFormat in $targetFormats) {
        $totalTests++
        $result = Test-Conversion -inputType $inputKey `
            -fileId $inputFile.file_id `
            -targetFormat $targetFormat `
            -inputDescription $inputFile.description
        
        $results += $result
        
        if ($result.status -eq "SUCCESS") {
            $successCount++
        } elseif ($result.status -eq "FAILED" -or $result.status -eq "ERROR") {
            $failCount++
        }
        
        Start-Sleep -Milliseconds 500
    }
    
    Write-Host ""
}

# Generate summary report
Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "        TEST SUMMARY REPORT" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "Total Tests: $totalTests" -ForegroundColor White
Write-Host "Successful: " -NoNewline
Write-Host "$successCount" -ForegroundColor Green
Write-Host "Failed: " -NoNewline
Write-Host "$failCount" -ForegroundColor Red
Write-Host "Skipped: " -NoNewline
Write-Host "$($totalTests - $successCount - $failCount)" -ForegroundColor Gray
Write-Host ""

# Detailed results table
Write-Host "Detailed Results:" -ForegroundColor Cyan
Write-Host "-" * 100 -ForegroundColor Gray
Write-Host ("{0,-15} {1,-10} {2,-10} {3,-40} {4,-10}" -f "Input", "Output", "Status", "Message", "Time (s)")
Write-Host "-" * 100 -ForegroundColor Gray

foreach ($result in $results) {
    $color = switch ($result.status) {
        "SUCCESS" { "Green" }
        "FAILED" { "Red" }
        "ERROR" { "DarkRed" }
        "SKIPPED" { "Gray" }
        default { "White" }
    }
    
    $message = $result.message
    if ($message.Length -gt 38) {
        $message = $message.Substring(0, 35) + "..."
    }
    
    Write-Host ("{0,-15} {1,-10} {2,-10} {3,-40} {4,-10}" -f `
        $result.input_desc, `
        $result.output, `
        $result.status, `
        $message, `
        $result.processing_time) -ForegroundColor $color
}

Write-Host "-" * 100 -ForegroundColor Gray
Write-Host ""

# Generate conversion matrix
Write-Host "Conversion Matrix ([OK] = Success, [X] = Failed, [ ] = Skipped):" -ForegroundColor Cyan
Write-Host ""

# Matrix header
Write-Host "Input Format    │ " -NoNewline
foreach ($format in $targetFormats) {
    Write-Host ("{0,6}" -f $format.ToUpper()) -NoNewline -ForegroundColor Yellow
}
Write-Host ""
Write-Host ("─" * 17 + "┼" + ("─" * 7 * $targetFormats.Count)) -ForegroundColor Gray

# Matrix rows
foreach ($inputKey in $testFiles.Keys) {
    $inputFile = $testFiles[$inputKey]
    Write-Host ("{0,-16}" -f $inputFile.description) -NoNewline
    Write-Host "│ " -NoNewline
    
    foreach ($targetFormat in $targetFormats) {
        $result = $results | Where-Object { $_.input -eq $inputKey -and $_.output -eq $targetFormat } | Select-Object -First 1
        
        if ($result) {
            $symbol = switch ($result.status) {
                "SUCCESS" { "  [OK] " }
                "FAILED" { "  [X]  " }
                "ERROR" { "  [!!] " }
                "SKIPPED" { "  [ ]  " }
                default { "  [ ]  " }
            }
            Write-Host $symbol -NoNewline
        } else {
            Write-Host "  [ ]  " -NoNewline
        }
    }
    Write-Host ""
}

Write-Host ""

# Export results to JSON
$jsonOutput = @{
    "timestamp" = (Get-Date).ToString("yyyy-MM-dd HH:mm:ss")
    "summary" = @{
        "total_tests" = $totalTests
        "successful" = $successCount
        "failed" = $failCount
        "skipped" = $totalTests - $successCount - $failCount
    }
    "results" = $results
} | ConvertTo-Json -Depth 10

$jsonOutput | Out-File -FilePath "conversion_test_results.json" -Encoding UTF8
Write-Host "Results exported to: conversion_test_results.json" -ForegroundColor Green
Write-Host ""

# Show supported combinations
Write-Host "Supported Conversion Combinations:" -ForegroundColor Cyan
$successfulCombos = $results | Where-Object { $_.status -eq "SUCCESS" }
if ($successfulCombos.Count -gt 0) {
    foreach ($combo in $successfulCombos) {
        Write-Host "  [OK] $($combo.input_desc) -> $($combo.output.ToUpper())" -ForegroundColor Green
    }
} else {
    Write-Host "  No successful conversions found." -ForegroundColor Red
}

Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "         TEST COMPLETE" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan

