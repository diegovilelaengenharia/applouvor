#requires -Version 7
<#
 harness.ps1 — o "doctor" do ecossistema ⛪ IGREJA (applouvor).

 Nasceu no fechamento pós-F9 (2026-07-14). Não substitui o gate (pronto.ps1): o gate
 barra commit ruim; o harness diz se a MÁQUINA está saudável.
 ⚠️ Lembrete permanente: push em main DISPARA DEPLOY (GitHub Actions → FTPS Hostinger).

 Uso:
   .\governanca\harness.ps1          -> rápido (caminhos + git + banco)
   .\governanca\harness.ps1 -Full    -> completo (+ suíte via pronto -Full)
#>
[CmdletBinding()]
param([switch]$Full)

try { [Console]::OutputEncoding = [Text.Encoding]::UTF8 } catch {}
$repo = Split-Path -Parent $PSScriptRoot
$ok = 0; $warn = 0; $fail = 0
function P($m) { $script:ok++;   Write-Host "  [OK]   $m" -ForegroundColor Green }
function W($m) { $script:warn++; Write-Host "  [AVISO] $m" -ForegroundColor Yellow }
function F($m) { $script:fail++; Write-Host "  [FALHA] $m" -ForegroundColor Red }

Write-Host ("HARNESS APPLOUVOR  ({0})" -f (Get-Date -Format 'yyyy-MM-dd HH:mm')) -ForegroundColor White

# ---- 1. Caminhos críticos ----
Write-Host "`n[1] Caminhos críticos" -ForegroundColor Cyan
$paths = [ordered]@{
  'app de gestão (8020)'      = Join-Path $repo 'gestao'
  'site público (PWA)'        = Join-Path $repo 'site'
  'skill local (.claude)'     = Join-Path $repo '.claude\skills'
  'gate próprio (pronto.ps1)' = Join-Path $repo 'governanca\pronto.ps1'
}
foreach ($k in $paths.Keys) { if (Test-Path -LiteralPath $paths[$k]) { P $k } else { F "$k -> $($paths[$k])" } }

# ---- 2. Banco louvor.db (SSOT do ministério — mora no lar Vilela Igreja) ----
Write-Host "`n[2] Banco (louvor.db)" -ForegroundColor Cyan
$db = 'C:\vilela\Vilela Igreja\0. Máquina\louvor.db'
if (Test-Path $db) {
  $chk = py -c "import sqlite3;c=sqlite3.connect(r'file:$db?mode=ro',uri=True);print(c.execute('PRAGMA integrity_check').fetchone()[0])" 2>$null
  if ($chk -eq 'ok') { P "louvor.db íntegro ($([math]::Round((Get-Item $db).Length/1MB,1)) MB)" } else { F "louvor.db: integrity_check = $chk" }
} else { W 'louvor.db ausente nesta máquina' }

# ---- 3. Git (branch + pendências + trava de deploy) ----
Write-Host "`n[3] Git (⚠️ push em main = DEPLOY produção)" -ForegroundColor Cyan
$b = git -C $repo rev-parse --abbrev-ref HEAD 2>$null
if ($LASTEXITCODE -ne 0) { F 'não é repo git' }
else {
  $dirty = git -C $repo status --porcelain -uno 2>$null
  if ([string]::IsNullOrWhiteSpace($dirty)) { P "[$b] limpo (rastreados)" } else { W "[$b] tem alterações não commitadas" }
  $naoPushado = [int](git -C $repo rev-list --count '@{u}..HEAD' 2>$null)
  if ($naoPushado -gt 0) { W "$naoPushado commit(s) local(is) — LEMBRE: push dispara deploy" } else { P 'sem commit pendente de push' }
}

# ---- 4. Suíte (só com -Full; delega ao gate) ----
Write-Host "`n[4] Testes" -ForegroundColor Cyan
if ($Full) {
  & (Join-Path $repo 'governanca\pronto.ps1') -Full
  if ($LASTEXITCODE -eq 0) { P 'gate -Full verde' } else { F 'gate -Full falhou' }
} else { W 'pulado — rode com -Full para a suíte completa' }

Write-Host ("`n>> OK={0} AVISO={1} FALHA={2}" -f $ok, $warn, $fail) -ForegroundColor $(if ($fail) { 'Red' } elseif ($warn) { 'Yellow' } else { 'Green' })
exit $(if ($fail) { 1 } else { 0 })
