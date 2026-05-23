# Atalho rapido para executar o pipeline de Push & Deploy
# Uso: .\push-deploy

Write-Host "Iniciando orquestrador de publicacao do App Louvor..." -ForegroundColor Cyan
python scripts/deploy/push_deploy.py
