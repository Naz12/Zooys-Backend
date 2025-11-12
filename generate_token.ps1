# ============================================
# Token Generator for Zooys Backend API
# ============================================
# This script logs in and generates a new authentication token
# Usage: .\generate_token.ps1 [email] [password] [base_url]
# ============================================

param(
    [string]$Email = "test-subscription@example.com",
    [string]$Password = "password",
    [string]$BaseUrl = "http://localhost:8000"
)

$ErrorActionPreference = 'Stop'

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "Zooys Backend - Token Generator" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Email: $Email" -ForegroundColor White
Write-Host "Base URL: $BaseUrl" -ForegroundColor White
Write-Host ""

try {
    $body = @{
        email = $Email
        password = $Password
    } | ConvertTo-Json

    $response = Invoke-RestMethod -Uri "$BaseUrl/api/login" -Method Post -Body $body -ContentType 'application/json'
    
    $token = $response.token
    $user = $response.user

    Write-Host ""
    Write-Host "============================================" -ForegroundColor Green
    Write-Host "SUCCESS: Token Generated!" -ForegroundColor Green
    Write-Host "============================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "User ID: $($user.id)" -ForegroundColor Cyan
    Write-Host "Name: $($user.name)" -ForegroundColor Cyan
    Write-Host "Email: $($user.email)" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Token:" -ForegroundColor Yellow
    Write-Host $token -ForegroundColor White -BackgroundColor DarkGray
    Write-Host ""
    Write-Host "============================================" -ForegroundColor Green
    Write-Host ""

    # Save token to file
    $token | Out-File -FilePath 'token.txt' -Encoding utf8 -NoNewline
    Write-Host "Token saved to: token.txt" -ForegroundColor Green
    Write-Host ""

    # Save full response to JSON file
    $response | ConvertTo-Json -Depth 10 | Out-File -FilePath 'token_response.json' -Encoding utf8
    Write-Host "Full response saved to: token_response.json" -ForegroundColor Green
    Write-Host ""

    # Copy token to clipboard
    $token | Set-Clipboard
    Write-Host "Token copied to clipboard!" -ForegroundColor Green
    Write-Host ""

    exit 0
} catch {
    Write-Host ""
    Write-Host "============================================" -ForegroundColor Red
    Write-Host "ERROR: Failed to generate token" -ForegroundColor Red
    Write-Host "============================================" -ForegroundColor Red
    Write-Host ""
    Write-Host "Error Message:" -ForegroundColor Yellow
    Write-Host $_.Exception.Message -ForegroundColor White
    Write-Host ""
    
    if ($_.Exception.Response) {
        try {
            $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
            $responseBody = $reader.ReadToEnd()
            Write-Host "Response Body:" -ForegroundColor Yellow
            Write-Host $responseBody -ForegroundColor White
            Write-Host ""
        } catch {
            # Ignore errors reading response stream
        }
    }
    
    exit 1
}

