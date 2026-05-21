# Deploy Fix Script V2
$ftpServer = "ftp://147.93.64.217"
$ftpUsername = "u884436813.Diego"
$ftpPassword = "0w;G7y#lXe:MtC&k"
$remoteBasePath = "/applouvor"

# New File Name
$localFile = "assets\css\pages\detail_v3.css"
$remoteFile = "$remoteBasePath/assets/css/pages/detail_v3.css"

# PHP File (Link update)
$localPhp = "admin\escala_detalhe.php"
$remotePhp = "$remoteBasePath/admin/escala_detalhe.php"

Write-Host "--- Uploading detail_v3.css and PHP update ---"
$webclient = New-Object System.Net.WebClient
$webclient.Credentials = New-Object System.Net.NetworkCredential($ftpUsername, $ftpPassword)

# Upload CSS
$uriCss = New-Object System.Uri($ftpServer + $remoteFile)
Write-Host "Uploading CSS..."
$webclient.UploadFile($uriCss, (Get-Item $localFile).FullName)

# Upload PHP
$uriPhp = New-Object System.Uri($ftpServer + $remotePhp)
Write-Host "Uploading PHP..."
$webclient.UploadFile($uriPhp, (Get-Item $localPhp).FullName)

Write-Host "Done."
