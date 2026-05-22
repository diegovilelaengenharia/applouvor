# FTP Deploy Script for React SPA (Dashboard) to Hostinger
# Compiles and uploads all static assets from dashboard/dist to the server

$ftpServer = "ftp://147.93.64.217"
$ftpUsername = "u884436813.Diego"
$ftpPassword = "0w;G7y#lXe:MtC&k"
$remoteBasePath = "/applouvor/dashboard"

Write-Host "=== Iniciando Compilação do React SPA ===" -ForegroundColor Cyan
# Executar a build local do React
Push-Location "dashboard"
try {
    npm run build
    if ($LASTEXITCODE -ne 0) {
        Write-Host "[ERRO] A compilação do React falhou!" -ForegroundColor Red
        Pop-Location
        exit 1
    }
} catch {
    Write-Host "[ERRO] Erro ao executar npm run build: $_" -ForegroundColor Red
    Pop-Location
    exit 1
}
Pop-Location

Write-Host "`n=== Iniciando FTP Deploy da pasta dashboard/dist ===" -ForegroundColor Cyan
Write-Host "Servidor: $ftpServer" -ForegroundColor Gray
Write-Host "Destino remoto: $remoteBasePath" -ForegroundColor Gray
Write-Host ""

$localDistPath = Join-Path (Get-Location) "dashboard\dist"
if (-not (Test-Path $localDistPath)) {
    Write-Host "[ERRO] Pasta local 'dashboard/dist' não encontrada!" -ForegroundColor Red
    exit 1
}

$creds = New-Object System.Net.NetworkCredential($ftpUsername, $ftpPassword)

# Função auxiliar para garantir diretório remoto
function Ensure-RemoteDir($dirPath) {
    $cleanDir = $dirPath.Replace("\", "/")
    # Criar subpastas recursivamente
    $parts = $cleanDir.Split('/')
    $currentPath = ""
    foreach ($part in $parts) {
        if (-not $part) { continue }
        $currentPath = "$currentPath/$part"
        try {
            $dirRequest = [System.Net.WebRequest]::Create("$ftpServer$currentPath")
            $dirRequest.Credentials = $creds
            $dirRequest.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
            $response = $dirRequest.GetResponse()
            $response.Close()
            Write-Host "Diretório criado: $currentPath" -ForegroundColor Gray
        } catch {
            # Ignora erro 550 se o diretório já existir
        }
    }
}

# Garantir a pasta raiz do dashboard no servidor
Ensure-RemoteDir $remoteBasePath
Ensure-RemoteDir "$remoteBasePath/assets"

$successCount = 0
$errorCount = 0

# Buscar todos os arquivos em dist
$files = Get-ChildItem -Path $localDistPath -Recurse | Where-Object { -not $_.PSIsContainer }

foreach ($file in $files) {
    # Obter caminho relativo
    $relative = Resolve-Path $file.FullName -Relative
    # Substituir dashboard/dist por nada para ter o caminho interno
    $relativeSub = $relative -replace "^\.\\dashboard\\dist\\", "" -replace "^\.\\dashboard\\dist", ""
    
    $localFileFullName = $file.FullName
    $remoteFilePath = "$remoteBasePath/$relativeSub".Replace("\", "/")
    
    Write-Host "Enviando: $relativeSub" -NoNewline
    
    try {
        $webclient = New-Object System.Net.WebClient
        $webclient.Credentials = $creds
        $uri = New-Object System.Uri("$ftpServer$remoteFilePath")
        
        $webclient.UploadFile($uri, $localFileFullName)
        Write-Host " -> [OK]" -ForegroundColor Green
        $successCount++
    } catch {
        Write-Host " -> [ERRO]" -ForegroundColor Red
        Write-Host $_.Exception.Message -ForegroundColor Red
        $errorCount++
    }
}

Write-Host "`n=== Resumo do Deploy ===" -ForegroundColor Cyan
Write-Host "Sucesso: $successCount arquivos" -ForegroundColor Green
Write-Host "Erros: $errorCount arquivos" -ForegroundColor $(if ($errorCount -gt 0) { "Red" } else { "Gray" })
Write-Host ""

if ($successCount -gt 0 -and $errorCount -eq 0) {
    Write-Host "Deploy do React SPA concluído com absoluto sucesso!" -ForegroundColor Green
    Write-Host "Painel online em: https://vilela.eng.br/applouvor/dashboard/" -ForegroundColor Cyan
} else {
    Write-Host "Deploy finalizado com alguns avisos/erros. Verifique o log." -ForegroundColor Yellow
}
