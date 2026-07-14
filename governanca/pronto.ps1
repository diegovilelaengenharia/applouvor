#requires -Version 7
<#
 pronto.ps1 — Gate de "Definição de Pronto" do ecossistema ⛪ IGREJA (applouvor).

 Até a F8 (2026-07-13) os hooks deste repo chamavam o `pronto.ps1` DA ENGENHARIA: um gate
 que roda as 4 suítes da engenharia e não sabe nada do louvor. Efeito duplo e ruim — a
 engenharia vermelha impedia commit aqui, e nada daqui era de fato testado.

 O risco específico deste repo é o INVERSO do da Prefeitura: aqui não há PII de cidadão,
 mas há **DEPLOY EM PRODUÇÃO**. Um push que toca `site/**` publica o PWA em
 louvor.vilela.eng.br na hora, sem staging. O `gestao/` (app do líder, 8020) não deploya.

 O que este gate verifica POR EXECUÇÃO:
   1. O commit NÃO mistura `site/` (deploya) com `gestao/` (não deploya) — misturar é
      publicar sem querer, ou segurar uma publicação que devia sair.
   2. Se toca `site/`: AVISA em vermelho que aquele push vai ao ar.
   3. Nada indevido (.db/.env/credenciais) — o `louvor.db` tem dados de membros da igreja.
   4. Suíte do gestao/ (com -Full).

 Uso:
   .\governanca\pronto.ps1          -> rápido (git + mistura de lados)
   .\governanca\pronto.ps1 -Full    -> completo (+ pytest do gestao/)
   .\governanca\pronto.ps1 -Hook    -> pre-commit (só o staged; instantâneo)
#>
[CmdletBinding()]
param([switch]$Full, [switch]$Hook)

try { [Console]::OutputEncoding = [Text.Encoding]::UTF8 } catch {}

$repo = Split-Path -Parent $PSScriptRoot
$arriscado = '\.(db|sqlite3?|exe|dll|env|pfx|key|pem)$|db_credentials'

function Split-Lados([string[]]$arquivos) {
  $site = @($arquivos | Where-Object { $_ -match '^site/' })
  $gestao = @($arquivos | Where-Object { $_ -match '^gestao/' })
  return @{ Site = $site; Gestao = $gestao }
}

# ───────────────────── MODO HOOK (pre-commit) ─────────────────────
if ($Hook) {
  $staged = @(git diff --cached --name-only 2>$null)
  Write-Host ("DoD Igreja (pre-commit) — {0} arquivo(s) staged" -f $staged.Count) -ForegroundColor White

  $indevidos = @($staged | Where-Object { $_ -match $arriscado })
  if ($indevidos.Count -gt 0) {
    Write-Host "  [X] arquivo INDEVIDO (banco/credencial — dados de membros):" -ForegroundColor Red
    foreach ($f in $indevidos) { Write-Host "        $f" -ForegroundColor Red }
    Write-Host ">> BLOQUEADO. git restore --staged <arquivo> ; ajuste o .gitignore." -ForegroundColor Red
    exit 1
  }

  $lados = Split-Lados $staged
  if ($lados.Site.Count -gt 0 -and $lados.Gestao.Count -gt 0) {
    Write-Host "  [X] o commit MISTURA os dois lados do repo:" -ForegroundColor Red
    Write-Host ("        site/   ({0}) -> DEPLOYA em producao" -f $lados.Site.Count) -ForegroundColor Red
    Write-Host ("        gestao/ ({0}) -> nao deploya" -f $lados.Gestao.Count) -ForegroundColor Red
    Write-Host ">> BLOQUEADO. Separe em dois commits: o que publica e o que nao publica." -ForegroundColor Red
    exit 1
  }

  if ($lados.Site.Count -gt 0) {
    Write-Host "  [!] este commit toca site/ -> o PUSH VAI PUBLICAR EM PRODUCAO (louvor.vilela.eng.br)" -ForegroundColor Yellow
  }
  Write-Host "  [OK] nada indevido, lados nao misturados." -ForegroundColor Green
  exit 0
}

# ───────────────────────────── MODO COMPLETO ─────────────────────────────
Write-Host "DEFINICAO DE PRONTO — ecossistema Igreja (⛪ applouvor)`n" -ForegroundColor White
$falhaAuto = $false

function Auto($ok, $txt, $detalhe) {
  $mark = if ($ok) { '[OK]  ' } else { '[X]   ' }
  $cor = if ($ok) { 'Green' } else { 'Red' }
  Write-Host ("  {0}{1}" -f $mark, $txt) -ForegroundColor $cor
  if ($detalhe) { Write-Host ("         {0}" -f $detalhe) -ForegroundColor DarkGray }
}
function Manual($txt) { Write-Host ("  [ ? ] {0}" -f $txt) -ForegroundColor Cyan }

Write-Host "Itens automaticos (verificados por execucao):" -ForegroundColor White

$sujos = @(git -C $repo status --porcelain -uno 2>$null |
  ForEach-Object { ($_ -replace '^.{2,3}', '').Trim().Trim('"') } | Where-Object { $_ })

$indevidos = @($sujos | Where-Object { $_ -match $arriscado })
if ($indevidos.Count -gt 0) { $falhaAuto = $true }
Auto ($indevidos.Count -eq 0) "Nada indevido no git (.db/.env/credenciais)" `
  $(if ($indevidos.Count) { ($indevidos -join '; ') } else { "$($sujos.Count) arquivo(s) alterado(s)" })

$lados = Split-Lados $sujos
$misturou = ($lados.Site.Count -gt 0 -and $lados.Gestao.Count -gt 0)
if ($misturou) { $falhaAuto = $true }
Auto (-not $misturou) "Lados nao misturados (site/ publica · gestao/ nao)" `
  ("site/: $($lados.Site.Count) · gestao/: $($lados.Gestao.Count)")

$py = (Get-Command py -ErrorAction SilentlyContinue) ?? (Get-Command python -ErrorAction SilentlyContinue)
if ($Full -and $py) {
  Push-Location (Join-Path $repo 'gestao')
  $saida = & $py.Source -m pytest tests -q 2>&1 | Out-String
  $rc = $LASTEXITCODE
  Pop-Location
  $resumo = ($saida -split "`r?`n" | Where-Object { $_ -match 'passed|failed|error|no tests' } | Select-Object -Last 1)
  if ($rc -ne 0) { $falhaAuto = $true }
  Auto ($rc -eq 0) "Suite do gestao/ (app do lider, 8020)" $resumo.Trim()
}
elseif ($Full) {
  Auto $false "Python nao encontrado" "instale o launcher 'py'"
  $falhaAuto = $true
}
else {
  Write-Host "  [...] Suite do gestao/ — rode 'pronto.ps1 -Full'" -ForegroundColor DarkYellow
}

if ($lados.Site.Count -gt 0) {
  Write-Host "`n  *** ATENCAO: ha alteracao em site/ — o PUSH PUBLICA EM PRODUCAO ***" -ForegroundColor Yellow
  Write-Host "      louvor.vilela.eng.br (GitHub Actions -> FTPS Hostinger). Nao existe staging." -ForegroundColor Yellow
}

Write-Host "`nItens para o humano confirmar:" -ForegroundColor White
Manual "Escala/imagem gerada foi ABERTA e conferida (nome certo na posicao certa)"
Manual "Se tocou site/: o Diego confirmou a PUBLICACAO em producao"
Manual "Dados de membros (louvor.db) nao entraram no commit"

Write-Host ""
if ($falhaAuto) {
  Write-Host ">> AINDA NAO: um item automatico falhou — resolva antes de concluir." -ForegroundColor Red
  exit 1
}
Write-Host ">> Itens automaticos OK." -ForegroundColor Green
exit 0
