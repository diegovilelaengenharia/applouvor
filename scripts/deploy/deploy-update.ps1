# Deploy Script Atualizado
# Faz upload dos arquivos modificados recentemente para o servidor de produção

$ftpServer = "ftp://147.93.64.217"
$ftpUsername = "u884436813.Diego"
$ftpPassword = "0w;G7y#lXe:MtC&k"
$remoteBasePath = "/applouvor"

# Lista completa de arquivos modificados e essenciais
$filesToUpload = @(
    "includes\layout.php",
    "admin\sidebar.php",
    "admin\index.php",
    "assets\css\app-main.css",
    "assets\css\components\sidebar.css",
    "assets\css\components\dashboard-cards.css",
    "assets\css\components\page-headers.css",
    "assets\css\components\modern-header.css",
    "assets\css\utilities\typography.css",
    "assets\js\notifications.js",
    "assets\js\profile-dropdown.js",
    "assets\js\main.js"
)

Write-Host "=== Iniciando Deploy de Atualização ===" -ForegroundColor Cyan

$creds = New-Object System.Net.NetworkCredential($ftpUsername, $ftpPassword)

foreach ($filePath in $filesToUpload) {
    if (Test-Path $filePath) {
        # Converter caminhos
        $cleanPath = $filePath.Replace("\", "/")
        $remoteFilePath = "$remoteBasePath/$cleanPath"
        $remoteDir = [System.IO.Path]::GetDirectoryName($remoteFilePath).Replace("\", "/")
        
        Write-Host "Processando: $cleanPath" -NoNewline
        
        # Tentar criar diretório remoto (caso seja novo, ex: css/utilities)
        try {
            $dirRequest = [System.Net.WebRequest]::Create("$ftpServer$remoteDir")
            $dirRequest.Credentials = $creds
            $dirRequest.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
            $dirRequest.GetResponse().Close()
        } catch {
            # Ignora erro se diretório já existe (erro 550)
        }

        # Upload do arquivo
        try {
            $webclient = New-Object System.Net.WebClient
            $webclient.Credentials = $creds
            $uri = New-Object System.Uri("$ftpServer$remoteFilePath")
            $webclient.UploadFile($uri, (Get-Item $filePath).FullName)
            Write-Host " -> [OK]" -ForegroundColor Green
        } catch {
            Write-Host " -> [ERRO]" -ForegroundColor Red
            Write-Host $_.Exception.Message -ForegroundColor Red
        }
    } else {
        Write-Host "Arquivo não encontrado: $filePath" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "Deploy Finalizado!" -ForegroundColor Cyan
Write-Host "Verifique em: https://vilela.eng.br/applouvor/" -ForegroundColor Cyan
