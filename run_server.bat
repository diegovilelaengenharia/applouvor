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

php -S localhost:8000
if %errorlevel% neq 0 (
    echo.
    echo [ERRO] O PHP nao foi encontrado no seu sistema.
    echo Verifique se o PHP esta instalado e nas Variaveis de Ambiente (PATH).
    echo Ou use o XAMPP e mova a pasta do projeto para 'htdocs'.
    pause
)
