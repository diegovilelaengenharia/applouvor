param (
    [Parameter(Mandatory=$true)]
    [string]$Mensagem,

    [Parameter(Mandatory=$false)]
    [int]$Issue = 0,

    [Parameter(Mandatory=$false)]
    [ValidateSet("Todo", "In Progress", "Done")]
    [string]$Status = "Done"
)

# 1. Identificar o Repositório Git Atual
$RemoteUrl = git config --get remote.origin.url
if (-not $RemoteUrl) {
    Write-Error "Este diretório não é um repositório Git ou não possui remote configurado."
    exit 1
}

$RepoName = ""
if ($RemoteUrl -match "github.com[:/]([^/]+/[^.]+)") {
    $RepoName = $Matches[1].Replace(".git", "")
} else {
    Write-Error "Não foi possível extrair o nome do repositório do remote: $RemoteUrl"
    exit 1
}

Write-Host "Repositório detectado: $RepoName" -ForegroundColor Cyan

# 2. Executar Git Add e Commit
Write-Host "Adicionando alterações ao Git..." -ForegroundColor Yellow
git add -A

$CommitMsg = $Mensagem
if ($Issue -gt 0) {
    $CommitMsg = "$Mensagem (closes #$Issue)"
}

Write-Host "Executando commit: '$CommitMsg'..." -ForegroundColor Yellow
git commit -m $CommitMsg

# 3. Detectar Branch Atual e Fazer Push
$Branch = git branch --show-current
Write-Host "Enviando alterações para origin/$Branch..." -ForegroundColor Yellow
git push origin $Branch

# 4. Atualizar o Kanban no GitHub Projects
if ($Issue -gt 0) {
    Write-Host "Sincronizando com o Quadro Kanban (Projeto 2)..." -ForegroundColor Yellow
    
    $ProjectId = "PVT_kwHODfnvUM4BZ1AZ"
    $FieldId = "PVTSSF_lAHODfnvUM4BZ1AZzhUw-c4"
    
    $OptionId = ""
    switch ($Status) {
        "Todo"        { $OptionId = "f75ad846" }
        "In Progress" { $OptionId = "47fc9ee4" }
        "Done"        { $OptionId = "98236657" }
    }
    
    try {
        $ProjectData = gh project item-list 2 --owner diegovilelaengenharia --format json | ConvertFrom-Json
        $RepoSearchName = $RepoName.Split('/')[-1]
        $Item = $ProjectData.items | Where-Object { $_.content.number -eq $Issue -and $_.content.repository -match $RepoSearchName }
        
        if ($Item) {
            $ItemId = $Item.id
            Write-Host "Cartão encontrado no Kanban! ID: $ItemId. Atualizando para '$Status'..." -ForegroundColor Green
            gh project item-edit --id $ItemId --field-id $FieldId --project-id $ProjectId --single-select-option-id $OptionId | Out-Null
            Write-Host "Status do Kanban atualizado com sucesso!" -ForegroundColor Green
        } else {
            Write-Warning "Não foi possível encontrar um cartão no Kanban correspondente à Issue #$Issue no repositório $RepoName."
            Write-Host "Dica: Verifique se a Issue está vinculada ao projeto unificado no GitHub." -ForegroundColor Yellow
        }
    } catch {
        Write-Error "Falha ao atualizar o Kanban via GitHub CLI: $_"
    }
} else {
    Write-Host "Nenhum número de Issue fornecido. O commit foi enviado, mas o Kanban não foi alterado." -ForegroundColor Yellow
}
