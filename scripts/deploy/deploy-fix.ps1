# Deploy Fix Script
$ftpServer = "ftp://147.93.64.217"
$ftpUsername = "u884436813.Diego"
$ftpPassword = "0w;G7y#lXe:MtC&k"
$remoteBasePath = "/applouvor"
$file = "assets\css\pages\escala-detalhe.css"
$remotePath = "$remoteBasePath/assets/css/pages/escala-detalhe.css"

Write-Host "--- Uploading Single File Fix ---"
$webclient = New-Object System.Net.WebClient
$webclient.Credentials = New-Object System.Net.NetworkCredential($ftpUsername, $ftpPassword)
$uri = New-Object System.Uri($ftpServer + $remotePath)
$webclient.UploadFile($uri, (Get-Item $file).FullName)
Write-Host "Done."
