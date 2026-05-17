@echo off
echo ========================================================
echo        PIB OLIVEIRA - APP LOUVOR PREMIUM 2026
echo            MODO DE DIAGNOSTICO DE ACESSO
echo ========================================================
echo.

REM 1. Configurar Caminhos
set PHP_BIN="C:\xampp\php\php.exe"
set SERVER_PORT=8080

echo [1/3] Verificando PHP...
if not exist %PHP_BIN% (
    echo [ERRO] PHP nao encontrado em: %PHP_BIN%
    pause
    exit /b
)
echo [OK] PHP encontrado.

echo [2/3] Verificando Banco de Dados...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="1" (
    echo [AVISO] MySQL nao detectado. Tentando ligar...
    if exist "C:\xampp\mysql_start.bat" (
        start "" "C:\xampp\mysql_start.bat"
        timeout /t 5 >nul
    )
)
echo [OK] Banco de Dados verificado.

echo [3/3] Iniciando Servidor...
echo.
echo --------------------------------------------------------
echo ATENCAO: Se aparecer uma mensagem do Windows, 
echo clique em "PERMITIR ACESSO".
echo --------------------------------------------------------
echo.
echo ACESSE O LINK ABAIXO:
echo http://127.0.0.1:8080/admin
echo.

REM Abre o navegador
start http://127.0.0.1:8080/admin

REM Inicia o servidor SEM ser minimizado para vermos erros
%PHP_BIN% -S 127.0.0.1:%SERVER_PORT%

echo.
echo O servidor foi encerrado.
pause
