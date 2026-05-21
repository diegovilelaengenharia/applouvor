# ============================================
# setup_local.ps1 - Setup do Ambiente Local
# App Louvor - XAMPP + MySQL local + porta 8080
# EXECUTE COMO ADMINISTRADOR!
# ============================================

$AppPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$MySqlBin = "C:\xampp\mysql\bin\mysql.exe"
$DBName   = "louvor_pib"
$SchemaFile = Join-Path $AppPath "schema.sql"

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "   App Louvor - Setup Local XAMPP" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# --- 1. Arquivo hosts ---
Write-Host "[1/3] Adicionando applouvor.local ao arquivo hosts..." -ForegroundColor Yellow
$hostsFile = "C:\Windows\System32\drivers\etc\hosts"
$entry = "127.0.0.1`tapplouvor.local"
$hostsContent = Get-Content $hostsFile -Raw

if ($hostsContent -notmatch "applouvor\.local") {
    Add-Content -Path $hostsFile -Value "`n$entry"
    Write-Host "      OK - Entrada adicionada!" -ForegroundColor Green
} else {
    Write-Host "      OK - Entrada ja existe, pulando." -ForegroundColor Green
}

# --- 2. Criar banco de dados ---
Write-Host "[2/3] Criando banco de dados '$DBName' no MySQL local..." -ForegroundColor Yellow

if (-not (Test-Path $MySqlBin)) {
    Write-Host "      ERRO: MySQL nao encontrado em $MySqlBin" -ForegroundColor Red
    Write-Host "      Certifique-se de que o XAMPP esta instalado e o MySQL iniciado." -ForegroundColor Red
} else {
    $createDB = "CREATE DATABASE IF NOT EXISTS $DBName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    & $MySqlBin -u root -e $createDB 2>&1

    if ($LASTEXITCODE -eq 0) {
        Write-Host "      OK - Banco '$DBName' criado!" -ForegroundColor Green

        # --- 3. Importar schema ---
        Write-Host "[3/3] Importando schema.sql..." -ForegroundColor Yellow
        if (Test-Path $SchemaFile) {
            # Ajusta o schema para usar o banco certo
            $schemaTmp = Join-Path $env:TEMP "schema_local.sql"
            $schemaContent = Get-Content $SchemaFile -Raw
            # Substitui pibo_louvor por louvor_pib se necessário
            $schemaContent = $schemaContent -replace "CREATE DATABASE IF NOT EXISTS pibo_louvor;", ""
            $schemaContent = $schemaContent -replace "USE pibo_louvor;", "USE louvor_pib;"
            Set-Content -Path $schemaTmp -Value $schemaContent -Encoding UTF8

            & $MySqlBin -u root $DBName `"< $schemaTmp`" 2>&1
            Get-Content $schemaTmp | & $MySqlBin -u root $DBName 2>&1

            if ($LASTEXITCODE -eq 0) {
                Write-Host "      OK - Schema importado com sucesso!" -ForegroundColor Green
            } else {
                Write-Host "      AVISO: Verifique erros acima. Talvez as tabelas ja existam." -ForegroundColor Yellow
            }
            Remove-Item $schemaTmp -Force
        } else {
            Write-Host "      AVISO: schema.sql nao encontrado em: $SchemaFile" -ForegroundColor Yellow
        }
    } else {
        Write-Host "      ERRO: Nao foi possivel conectar ao MySQL." -ForegroundColor Red
        Write-Host "      Inicie o MySQL pelo XAMPP Control Panel e rode este script novamente." -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host " CONFIGURACAO CONCLUIDA!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host " Acesse o app em:" -ForegroundColor White
Write-Host "   http://localhost:8080     (XAMPP htdocs)" -ForegroundColor Yellow
Write-Host "   http://applouvor.local:8080  (App Louvor)" -ForegroundColor Yellow
Write-Host ""
Write-Host " Lembre-se de:" -ForegroundColor White
Write-Host "   1. Iniciar Apache (porta 8080) pelo XAMPP Control Panel" -ForegroundColor White
Write-Host "   2. Iniciar MySQL pelo XAMPP Control Panel" -ForegroundColor White
Write-Host ""
pause
