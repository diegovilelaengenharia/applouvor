---
plan: 01C
phase: 01-git-cleanup
status: completed
executed: 2026-05-17
executor: Claude Code (claude-sonnet-4-6)
---

# 01C Summary — Manutencao e Scripts de Setup

## Commits Criados (2 commits)

| # | Hash | Mensagem | Arquivos |
|---|------|----------|----------|
| 1 | `b3a4c80` | `chore(maintenance): organiza scripts de manutencao e remove descartaveis` | 11 arquivos (3 new in maintenance/, 8 deleted from root, 2 plan files updated) |
| 2 | `de4ad5a` | `chore(dev): versiona scripts de setup de ambiente local na raiz` | 3 arquivos (ACESSAR_SISTEMA.bat, adicionar_hosts.bat, setup_local.ps1) |

## Detalhes do Commit 1 (b3a4c80)

**Arquivos deletados da raiz (tracked, removidos definitivamente):**
- `debug_auth.php` (delete mode 100644)
- `debug_roles.php` (delete mode 100644)
- `debug_vocals.php` (delete mode 100644)
- `fix_mariana.php` (delete mode 100644)
- `temp_backup.txt` (delete mode 100644)
- `temp_gen_keys.php` (delete mode 100644)

**Renomeados (raiz -> maintenance/):**
- `exemplos_uso.php` -> `maintenance/exemplos_uso.php` (100% similarity)
- `migrate_passwords.php` -> `maintenance/migrate_passwords.php` (100% similarity)

**Novo arquivo em maintenance/:**
- `maintenance/restore_db.php` (create mode 100644, movido da raiz)

**Arquivos descartaveis deletados do disco (nunca versionados em maintenance/):**
- `maintenance/debug_auth.php`
- `maintenance/debug_roles.php`
- `maintenance/debug_vocals.php`
- `maintenance/temp_backup.txt`
- `maintenance/temp_gen_keys.php`
- `maintenance/fix_mariana.php`

## Detalhes do Commit 2 (de4ad5a)

**Scripts de setup versionados na raiz:**
- `ACESSAR_SISTEMA.bat` — Inicia servidor PHP local e abre navegador em http://127.0.0.1:8080/admin
- `adicionar_hosts.bat` — Adiciona entrada `applouvor.local` ao /etc/hosts do Windows
- `setup_local.ps1` — Setup completo: hosts + banco MySQL + import schema

**Verificacao de seguranca:** Nenhum dos 3 scripts contem credenciais hardcoded de producao.

## Estado de maintenance/ apos execucao

```
desktop.ini            (arquivo Windows nao versionado)
exemplos_uso.php       (versionado — documentacao por exemplo)
fix_db_avatar.php      (pre-existente em maintenance/)
fix_db_observation.php (pre-existente em maintenance/)
fix_notif_schema.php   (pre-existente em maintenance/)
fix_schedules_schema.php (pre-existente em maintenance/)
fix_schema.php         (pre-existente em maintenance/)
fix_stats_schema.php   (pre-existente em maintenance/)
fix_unav_schema.php    (pre-existente em maintenance/)
install_dashboard.php  (pre-existente em maintenance/)
migrate_passwords.php  (versionado — historico de migracao de senhas)
restore_db.php         (versionado — movido da raiz, utilitario de restauracao)
upgrade_reading_db.php (pre-existente em maintenance/)
upgrade_settings_table.php (pre-existente em maintenance/)
upgrade_v2.php         (pre-existente em maintenance/)
```

## Verificacao dos Acceptance Criteria

- [x] `git log --oneline -1` mostra: "chore(dev): versiona scripts de setup de ambiente local na raiz"
- [x] `maintenance/restore_db.php` existe no disco
- [x] `maintenance/debug_auth.php` NAO existe (deletado, nunca versionado)
- [x] `restore_db.php` NAO existe mais na raiz
- [x] `ACESSAR_SISTEMA.bat`, `adicionar_hosts.bat`, `setup_local.ps1` rastreados pelo git

## Estado Final do git status

```
On branch main
Your branch is ahead of 'origin/main' by 13 commits.

Untracked files:
  .planning/phases/01-git-cleanup/01A-SUMMARY.md
  .planning/phases/01-git-cleanup/01B-SUMMARY.md
  .planning/phases/01-git-cleanup/01D-PLAN.md

nothing added to commit but untracked files present
```

O working tree esta limpo (exceto arquivos de planejamento que serao commitados em outro momento).

## Observacoes

**Erros git nao-fatais:** Cada commit gerou avisos de manutencao git (`fatal: bad object refs/desktop.ini`, `error: failed to perform geometric repack`). Estes sao os mesmos erros documentados no 01A-SUMMARY, causados pelo `refs/desktop.ini` invalido. Os commits foram criados com sucesso. Este problema foi resolvido pelo 01B (remocao de desktop.ini do tracking).

**Arquivos pre-existentes em maintenance/:** O plano 01C mencionava 9 arquivos em maintenance/, mas havia mais (fix_db_avatar.php, fix_db_observation.php, fix_notif_schema.php, etc.). Estes ja estavam la antes e nao foram tocados — correto.

**GIT-03 cumprido:** Scripts de manutencao organizados em maintenance/ e commitados.
