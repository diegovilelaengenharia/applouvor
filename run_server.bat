@echo off
echo ========================================================
echo   App Louvor - Iniciador XAMPP (porta 8080)
echo ========================================================
echo.
echo OPCAO 1: Use o XAMPP Control Panel (RECOMENDADO)
echo   - Inicie Apache e MySQL pelo XAMPP Control Panel
echo   - Acesse: http://applouvor.local:8080
echo   - Ou: http://localhost:8080/applouvor (via htdocs)
echo.
echo OPCAO 2: PHP Built-in Server (sem XAMPP Apache)
echo   - O servidor estara disponivel em: http://localhost:8080
echo.
echo Pressione Ctrl+C para encerrar o servidor (Opcao 2).
echo.

REM Caminho do PHP no XAMPP
set PHP_BIN="C:\xampp\php\php.exe"

REM Verifica se o PHP existe
if not exist %PHP_BIN% (
    echo [ERRO] Nao foi possivel encontrar o PHP em: %PHP_BIN%
    echo Verifique se o XAMPP esta instalado corretamente.
    pause
    exit /b
)

REM Tenta iniciar o MySQL do XAMPP (se existir e nao estiver rodando)
if exist "C:\xampp\mysql_start.bat" (
    echo [INFO] Tentando iniciar o MySQL do XAMPP...
    start /min "MySQL XAMPP" "C:\xampp\mysql_start.bat"
    timeout /t 3 >nul
) else (
    echo [AVISO] Inicie o MySQL pelo XAMPP Control Panel.
)

REM Abre o navegador automaticamente apos 2 segundos
timeout /t 2 >nul
start http://localhost:8080

REM Inicia o servidor usando o PHP do XAMPP
echo.
echo Iniciando PHP Built-in Server na porta 8080...
%PHP_BIN% -S localhost:8080
