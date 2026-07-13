# Copyright (c) 2026 Diego Vilela — ecossistema Vilela (lado Deiso: igreja).
# Wrapper da tarefa do Agendador "Vilela - Louvor Imagem Semanal" (segunda 07h).
# Gera a imagem da escala/jejum do proximo domingo e registra um log. Lê o louvor.db (SSOT) AO VIVO.
$ErrorActionPreference = "Stop"
$ferramentas = Split-Path -Parent $MyInvocation.MyCommand.Path
$gerador = Join-Path $ferramentas "gerar_imagem_louvor.py"
$log = Join-Path $ferramentas "_louvor-imagem-semanal.log"

$stamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
try {
    $saida = & py $gerador 2>&1
    Add-Content -Path $log -Value "[$stamp] OK  $saida" -Encoding UTF8
    Write-Output $saida
} catch {
    Add-Content -Path $log -Value "[$stamp] ERRO  $($_.Exception.Message)" -Encoding UTF8
    throw
}
