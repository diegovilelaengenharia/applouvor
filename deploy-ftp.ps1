# FTP Deploy Script for Hostinger
# Uploads modified files to production server

$ftpServer = "ftp://147.93.64.217"
$ftpUsername = "Diego"
$ftpPassword = "6&vHwaN]8/*k||rD"
$ftpBasePath = "/public_html/applouvor"

# Files to upload (relative paths)
$filesToUpload = @(
    @{ Local = "admin\escala.php"; Remote = "/public_html/applouvor/admin/escala.php" },
    @{ Local = "admin\index.php"; Remote = "/public_html/applouvor/admin/index.php" },
    @{ Local = "assets\css\pages\escala.css"; Remote = "/public_html/applouvor/assets/css/pages/escala.css" },
    @{ Local = "assets\css\pages\dashboard.css"; Remote = "/public_html/applouvor/assets/css/pages/dashboard.css" },
    @{ Local = "assets\css\pages\shared-pages.css"; Remote = "/public_html/applouvor/assets/css/pages/shared-pages.css" }
)

Write-Host "=== FTP Deploy to Hostinger ===" -ForegroundColor Cyan
Write-Host "Server: $ftpServer" -ForegroundColor Gray
Write-Host "Files to upload: $($filesToUpload.Count)" -ForegroundColor Gray
Write-Host ""

$successCount = 0
$errorCount = 0

foreach ($file in $filesToUpload) {
    $localPath = $file.Local
    $remotePath = $file.Remote
    
    if (-not (Test-Path $localPath)) {
        Write-Host "[SKIP] $localPath (file not found)" -ForegroundColor Yellow
        continue
    }
    
    try {
        Write-Host "[UPLOAD] $localPath -> $remotePath" -ForegroundColor Cyan
        
        # Create WebClient
        $webclient = New-Object System.Net.WebClient
        $webclient.Credentials = New-Object System.Net.NetworkCredential($ftpUsername, $ftpPassword)
        
        # Upload file
        $uri = New-Object System.Uri("$ftpServer$remotePath")
        $webclient.UploadFile($uri, $localPath)
        
        Write-Host "[OK] Upload successful!" -ForegroundColor Green
        $successCount++
    }
    catch {
        Write-Host "[ERROR] Failed to upload: $_" -ForegroundColor Red
        $errorCount++
    }
}

Write-Host ""
Write-Host "=== Deploy Summary ===" -ForegroundColor Cyan
Write-Host "Success: $successCount" -ForegroundColor Green
Write-Host "Errors: $errorCount" -ForegroundColor $(if ($errorCount -gt 0) { "Red" } else { "Gray" })
Write-Host ""

if ($successCount -gt 0) {
    Write-Host "Deploy completed! Files are now live on production." -ForegroundColor Green
    Write-Host "URL: https://vilela.eng.br/applouvor/" -ForegroundColor Cyan
}
