#requires -Version 7
<#
 instalar-hooks.ps1 — planta o gate deste ecossistema no git local.

 Até a F8 os hooks daqui chamavam o pronto.ps1 DA ENGENHARIA (que não sabe nada do louvor
 e ainda barrava commits quando a engenharia estava vermelha). Agora cada casa tem a sua.

 pre-commit -> pronto.ps1 -Hook   (staged: banco/credencial + mistura site/ x gestao/)
 pre-push   -> pronto.ps1 -Full   (+ a suíte do gestao/)
#>
[CmdletBinding()]
param()

try { [Console]::OutputEncoding = [Text.Encoding]::UTF8 } catch {}

$repo = Split-Path -Parent $PSScriptRoot
$hooks = Join-Path $repo '.git\hooks'
$pronto = Join-Path $repo 'governanca\pronto.ps1'

if (-not (Test-Path $hooks)) { Write-Host "ERRO: nao achei $hooks" -ForegroundColor Red; exit 1 }
if (-not (Test-Path $pronto)) { Write-Host "ERRO: nao achei $pronto" -ForegroundColor Red; exit 1 }

foreach ($p in @(@{ Nome = 'pre-commit'; Flag = '-Hook' }, @{ Nome = 'pre-push'; Flag = '-Full' })) {
  $alvo = Join-Path $hooks $p.Nome
  $conteudo = @"
#!/bin/sh
# Gate do ecossistema Igreja — gerado por governanca/instalar-hooks.ps1.
# Lembre: push que toca site/** PUBLICA em louvor.vilela.eng.br.
pwsh -NoProfile -NonInteractive -File "$pronto" $($p.Flag)
"@
  Set-Content -LiteralPath $alvo -Value $conteudo -Encoding UTF8 -NoNewline
  Write-Host ("  [OK] {0,-12} -> pronto.ps1 {1}" -f $p.Nome, $p.Flag) -ForegroundColor Green
}

Write-Host "`n>> Gate instalado (era o da engenharia; agora e' o desta casa)." -ForegroundColor Green
exit 0
