# FTP Deploy Script for Hostinger
# Uploads modified files to production server

$ftpServer = "ftp://147.93.64.217"
$ftpUsername = "u884436813.Diego"
$ftpPassword = "0w;G7y#lXe:MtC&k"
$remoteBasePath = "/applouvor"

# Files to upload (relative paths)
$filesToUpload = @(
    @{ Local = "includes\layout.php"; Remote = "$remoteBasePath/includes/layout.php" },
    @{ Local = "admin\sidebar.php"; Remote = "$remoteBasePath/admin/sidebar.php" },
    @{ Local = "assets\css\app-main.css"; Remote = "$remoteBasePath/assets/css/app-main.css" },
    @{ Local = "assets\css\components\sidebar.css"; Remote = "$remoteBasePath/assets/css/components/sidebar.css" },
    @{ Local = "assets\css\legacy-style.css"; Remote = "$remoteBasePath/assets/css/legacy-style.css" },
    @{ Local = "admin\index.php"; Remote = "$remoteBasePath/admin/index.php" },
    @{ Local = "includes\dashboard_render.php"; Remote = "$remoteBasePath/includes/dashboard_render.php" },
    @{ Local = "assets\css\components\dashboard-cards.css"; Remote = "$remoteBasePath/assets/css/components/dashboard-cards.css" },
    @{ Local = "assets\css\components\page-headers.css"; Remote = "$remoteBasePath/assets/css/components/page-headers.css" },
    @{ Local = "assets\js\main.js"; Remote = "$remoteBasePath/assets/js/main.js" },
    @{ Local = "admin\update_engagement_schema.php"; Remote = "$remoteBasePath/admin/update_engagement_schema.php" },
    @{ Local = "admin\escala_detalhe.php"; Remote = "$remoteBasePath/admin/escala_detalhe.php" },
    @{ Local = "admin\avisos.php"; Remote = "$remoteBasePath/admin/avisos.php" },
    @{ Local = "assets\css\pages\escala-detalhe.css"; Remote = "$remoteBasePath/assets/css/pages/escala-detalhe.css" },
    @{ Local = "assets\css\pages\avisos.css"; Remote = "$remoteBasePath/assets/css/pages/avisos.css" }
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
