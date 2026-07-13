# Copyright (c) 2026 Diego Vilela — applouvor (gestão do Ministério de Louvor).
<#
.SYNOPSIS
  Sobe o app da GESTÃO do Louvor (porta 8020) e abre o navegador.
.DESCRIPTION
  Centro de comando do ministério (escala/jejum/setlist/repertório/imagem do
  WhatsApp). Lê o SSOT louvor.db resolvido pelo caminhos.py — acha o local atual
  sozinho (3. Igreja/00. _Gestão), ou defina $env:LOUVOR_DB para apontar outro.
  Usa o .venv se existir; senão o Python do sistema (py/python).
#>
$ErrorActionPreference = 'Stop'
$GESTAO = $PSScriptRoot

$pyExe = Join-Path $GESTAO '.venv\Scripts\python.exe'
if (-not (Test-Path $pyExe)) {
  $pyExe = (Get-Command py -ErrorAction SilentlyContinue)?.Source
  if (-not $pyExe) { $pyExe = (Get-Command python -ErrorAction Stop).Source }
}

Write-Host "App Louvor - Gestao (lado lider)  ->  http://127.0.0.1:8020" -ForegroundColor Cyan
Start-Process "http://127.0.0.1:8020"
& $pyExe (Join-Path $GESTAO 'app\main.py')
