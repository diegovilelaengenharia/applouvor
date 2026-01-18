@echo off
echo ========================================================
echo   Iniciando Servidor Local do App Louvor
echo ========================================================
echo.
echo 1. O servidor estara disponivel em: http://localhost:8000
echo 2. Para resetar o banco, acesse: http://localhost:8000/reset_db_v2.php
echo 3. Mantenha essa janela aberta enquanto usa o App.
echo.
echo Pressione Ctrl+C para encerrar o servidor.
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
    echo [AVISO] O script de inicio do MySQL nao foi encontrado.
    echo Certifique-se de iniciar o 'MySQL' pelo XAMPP Control Panel.
)

REM Abre o navegador automaticamente após 2 segundos
timeout /t 2 >nul
start http://localhost:8000

REM Inicia o servidor usando o PHP do XAMPP
echo.
echo Iniciando PHP Server...
%PHP_BIN% -S localhost:8000
