$ErrorActionPreference = 'Stop'

$apiBase = 'http://localhost:8000/api'
$token = '207|vhs65UeUfk2kpYoTpJX3zIClf4zQyixWZdkbfPYXf7ae0dfe'
$fileId1 = '204'
$fileId2 = '202'

$headersAuth = @{ 'Authorization' = "Bearer $token" }

function Submit-JobJson {
    param(
        [string]$Operation,
        [hashtable]$Body
    )
    $url = "$apiBase/pdf/edit/$Operation"
    $json = ($Body | ConvertTo-Json -Depth 10)
    return Invoke-RestMethod -Method Post -Uri $url -Headers $headersAuth -ContentType 'application/json' -Body $json
}

function Wait-Status {
    param(
        [string]$Operation,
        [string]$JobId,
        [int]$MaxAttempts = 60,
        [int]$DelaySeconds = 5
    )
    for ($i=0; $i -lt $MaxAttempts; $i++) {
        $statusUrl = "$apiBase/pdf/edit/$Operation/status?job_id=$JobId"
        $status = Invoke-RestMethod -Method Get -Uri $statusUrl
        if ($status.status -eq 'completed') { return $status }
        if ($status.status -eq 'failed') { throw "Job failed: $($status.error)" }
        Start-Sleep -Seconds $DelaySeconds
    }
    throw "Timeout waiting for job $JobId ($Operation)"
}

function Get-Result {
    param(
        [string]$Operation,
        [string]$JobId
    )
    $url = "$apiBase/pdf/edit/$Operation/result?job_id=$JobId"
    return Invoke-RestMethod -Method Get -Uri $url
}

function Ensure-Dir($path) { if (-not (Test-Path $path)) { New-Item -ItemType Directory -Path $path | Out-Null } }

Ensure-Dir -path 'scripts/results'

function Run-Op {
    param(
        [string]$Operation,
        [hashtable]$Body
    )
    Write-Host "Submitting $Operation ..."
    $submit = Submit-JobJson -Operation $Operation -Body $Body
    $jobId = $submit.job_id
    Write-Host "$Operation job: $jobId"
    $status = Wait-Status -Operation $Operation -JobId $jobId
    $result = Get-Result -Operation $Operation -JobId $jobId
    $out = [pscustomobject]@{ submit = $submit; status = $status; result = $result }
    ($out | ConvertTo-Json -Depth 20) | Out-File -Encoding utf8 "scripts/results/$Operation.json"
    Write-Host "$Operation completed. Saved to scripts/results/$Operation.json"
}

function Try-Run {
    param([string]$Operation, [hashtable]$Body)
    try { Run-Op -Operation $Operation -Body $Body } catch { Write-Host "[$Operation] ERROR: $_"; }
}

# Merge (needs two files)
Try-Run -Operation 'merge' -Body @{ file_ids = @($fileId1, $fileId2); params = @{ page_order = 'as_uploaded'; remove_blank_pages = $false; add_page_numbers = $false } }

# Split
Try-Run -Operation 'split' -Body @{ file_id = $fileId1; params = @{ split_points = '1'; title_prefix = 'Part'; author = 'Test' } }

# Compress
Try-Run -Operation 'compress' -Body @{ file_id = $fileId1; params = @{ compression_level = 'medium'; quality = 85 } }

# Watermark
Try-Run -Operation 'watermark' -Body @{ file_id = $fileId1; params = @{ watermark_type = 'text'; watermark_content = 'CONFIDENTIAL'; position_x = 50; position_y = 50; rotation = -45; opacity = 0.3; color = '#000000'; font_family = 'Arial'; font_size = 24; apply_to_all = $true } }

# Page numbers (omit page_ranges entirely to avoid microservice 400)
Try-Run -Operation 'page_numbers' -Body @{ file_id = $fileId1; params = @{ position = 'bottom_right'; format_type = 'arabic'; font_size = 12 } }

# Annotate
Try-Run -Operation 'annotate' -Body @{ file_id = $fileId1; params = @{ annotations = @(@{ type='note'; page_number=1; x=60; y=60; text='Visible note' }) } }

# Protect (send only password first)
Try-Run -Operation 'protect' -Body @{ file_id = $fileId1; params = @{ password = 'secret123' } }

# Unlock (uses the protected file; still uses same file id for demo)
Try-Run -Operation 'unlock' -Body @{ file_id = $fileId1; params = @{ password = 'secret123' } }

# Preview
Try-Run -Operation 'preview' -Body @{ file_id = $fileId1; params = @{ page_numbers = '1'; thumbnail_width = 200; thumbnail_height = 200; zoom = 2.0 } }

# Batch (multi-file)
Try-Run -Operation 'batch' -Body @{ file_ids = @($fileId1, $fileId2); params = @{ operation = 'compress'; options = @{ compression_level = 'low' } } }

# Edit PDF (reorder/remove) - omit remove_pages when none
Try-Run -Operation 'edit_pdf' -Body @{ file_id = $fileId1; params = @{ page_order = 'as_is'; remove_blank_pages = $true } }

try {
    Write-Host 'Submitting conversion ...'
    $convertBody = @{ file_id = $fileId1; target_format = 'pdf'; options = @{} } | ConvertTo-Json -Depth 10
    $convSubmit = Invoke-RestMethod -Method Post -Uri "$apiBase/file-processing/convert" -Headers $headersAuth -ContentType 'application/json' -Body $convertBody
    $convJob = $convSubmit.job_id
    for ($i=0; $i -lt 60; $i++) {
        $st = Invoke-RestMethod -Method Get -Uri "$apiBase/status?job_id=$convJob" -Headers $headersAuth
        if ($st.status -eq 'completed') { break } elseif ($st.status -eq 'failed') { throw "conversion failed: $($st | ConvertTo-Json -Depth 5)" } else { Start-Sleep -Seconds 5 }
    }
    $convRes = Invoke-RestMethod -Method Get -Uri "$apiBase/result?job_id=$convJob" -Headers $headersAuth
    ([pscustomobject]@{ submit=$convSubmit; result=$convRes } | ConvertTo-Json -Depth 20) | Out-File -Encoding utf8 "scripts/results/conversion.json"
} catch {
    ([pscustomobject]@{ error = $_.ToString() } | ConvertTo-Json -Depth 20) | Out-File -Encoding utf8 "scripts/results/conversion_error.json"
    Write-Host "[conversion] ERROR: $_"
}

try {
    Write-Host 'Submitting extraction ...'
    $extractBody = @{ file_id = $fileId1; extraction_type = 'text'; language = 'eng'; include_formatting = $false; max_pages = 5; options = @{ request = @{ content = $true; metadata = $true; images = $false } } } | ConvertTo-Json -Depth 10
    $extSubmit = Invoke-RestMethod -Method Post -Uri "$apiBase/file-processing/extract" -Headers $headersAuth -ContentType 'application/json' -Body $extractBody
    $extJob = $extSubmit.job_id
    for ($i=0; $i -lt 60; $i++) {
        $st = Invoke-RestMethod -Method Get -Uri "$apiBase/status?job_id=$extJob" -Headers $headersAuth
        if ($st.status -eq 'completed') { break } elseif ($st.status -eq 'failed') { throw "extraction failed: $($st | ConvertTo-Json -Depth 5)" } else { Start-Sleep -Seconds 5 }
    }
    $extRes = Invoke-RestMethod -Method Get -Uri "$apiBase/result?job_id=$extJob" -Headers $headersAuth
    ([pscustomobject]@{ submit=$extSubmit; result=$extRes } | ConvertTo-Json -Depth 20) | Out-File -Encoding utf8 "scripts/results/extraction.json"
} catch {
    ([pscustomobject]@{ error = $_.ToString() } | ConvertTo-Json -Depth 20) | Out-File -Encoding utf8 "scripts/results/extraction_error.json"
    Write-Host "[extraction] ERROR: $_"
}

Write-Host 'All tests finished. See scripts/results/*.json'


