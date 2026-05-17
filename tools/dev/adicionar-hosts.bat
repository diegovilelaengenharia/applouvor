@echo off
:: Adiciona applouvor.local no arquivo hosts
:: EXECUTE COMO ADMINISTRADOR (botao direito -> Executar como administrador)

echo 127.0.0.1   applouvor.local >> "C:\Windows\System32\drivers\etc\hosts"

echo.
echo [OK] Entrada adicionada com sucesso!
echo Agora acesse: http://applouvor.local:8080
echo.
pause
