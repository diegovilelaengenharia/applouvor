# Phase 1: Git Cleanup - Context

**Gathered:** 2026-05-16
**Status:** Ready for planning

<domain>
## Phase Boundary

Commitar o estado atual do projeto (33+ arquivos modificados pelo Gemini CLI) de forma organizada com commits semânticos por área/módulo, remover desktop.ini do rastreamento git e adicioná-lo ao .gitignore, mover restore_db.php para maintenance/, adicionar scripts de setup à raiz versionados, e ignorar a pasta de backup da versão antiga.

</domain>

<decisions>
## Implementation Decisions

### Estratégia de Commits
- **D-01:** Commits agrupados por área/módulo, não por tipo de mudança. Usar prefixos semânticos (feat/fix/chore/docs) com escopo da área (ex: `feat(admin): ...`, `chore(assets): ...`).
- **D-02:** `admin/` recebe um único commit para todos os 8 arquivos modificados (dashboard_data, escalas, index, leitura, membros, repertorio, sidebar, sql/).
- **D-03:** Revisar o `git diff` antes de commitar cada área — confirmar que não há lixo ou mudanças indesejadas do Gemini CLI.
- **D-04:** `api/confirm_scale.php` e `assets/css/components/pib-cards.css` (novos, não rastreados) são commitados no Phase 1 como parte do estado atual do projeto.

### Limpeza de desktop.ini
- **D-05:** Commit separado exclusivo para remoção de desktop.ini: `chore(git): remove desktop.ini tracking`. Separa claramente limpeza de git de mudanças de código.
- **D-06:** Remover do tracking com `git rm --cached` todos os desktop.ini encontrados nos diretórios: raiz, admin/, assets/, assets/css/, assets/images/, assets/js/, includes/, "banco de dados/".
- **D-07:** Adicionar `desktop.ini` ao `.gitignore` para evitar rastreamento futuro.

### Scripts e Arquivos na Raiz
- **D-08:** Scripts de setup de ambiente local (ACESSAR_SISTEMA.bat, adicionar_hosts.bat, setup_local.ps1) ficam na raiz e são versionados no git — facilitam setup para devs.
- **D-09:** `restore_db.php` (script de manutenção pontual) vai para `maintenance/` junto com os outros scripts já organizados lá.

### Pasta de Backup Antiga
- **D-10:** `App louvor 23.01.2026/` (versão antiga de janeiro/2026) é adicionada ao `.gitignore` — não será versionada no repo. Permanece no disco local para consulta eventual.

### Claude's Discretion
- Ordenação dos commits dentro de cada área (o executor pode escolher a ordem mais lógica)
- Mensagem exata de cada commit (seguir o padrão semântico definido acima)
- Se encontrar arquivos duvidosos durante o diff review, pode ignorar ou perguntar

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Projeto e Requisitos
- `.planning/REQUIREMENTS.md` — Requisitos GIT-01, GIT-02, GIT-03 que esta fase deve cobrir
- `.planning/ROADMAP.md` — Fase 1 com os 3 planos: 1A (commits semânticos), 1B (desktop.ini), 1C (maintenance/)
- `.planning/STATE.md` — Estado atual do projeto: 33 arquivos modificados, contexto das mudanças do Gemini

### Git State (ler antes de qualquer commit)
- Executar `git status` para ver estado atual atualizado
- Executar `git diff HEAD -- <area>` por área antes de commitar

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `run_server.bat` — já existente na raiz, modificado. Padrão para scripts de setup na raiz.
- `maintenance/` — já contém: debug_auth.php, debug_roles.php, debug_vocals.php, exemplos_uso.php, fix_mariana.php, migrate_passwords.php, temp_backup.txt, temp_gen_keys.php (movidos das deleções da raiz).

### Established Patterns
- `desktop.ini` aparece em múltiplos diretórios rastreados: raiz, admin/, assets/, assets/css/, assets/images/, assets/js/, includes/, "banco de dados/" — todos devem ser removidos do tracking com `git rm --cached`.
- Commits semânticos já usados no projeto: feat/fix/refactor/docs/chore com scopes como (ui), (db), (fase2).

### Integration Points
- `.gitignore` — deve receber duas entradas novas: `desktop.ini` e `App louvor 23.01.2026/`
- `maintenance/` — recebe `restore_db.php` movido da raiz

</code_context>

<specifics>
## Specific Ideas

- O executor DEVE fazer `git diff` antes de cada commit para confirmar o que está sendo commitado — não commitar às cegas as mudanças do Gemini.
- Commit de maintenance/ deve incluir: mover restore_db.php (novo arquivo em maintenance/) + registrar a deleção da raiz (já deletado).
- Scripts de setup (ACESSAR_SISTEMA.bat, adicionar_hosts.bat, setup_local.ps1) ficam na raiz versionados — não vão para maintenance/.

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 1-git-cleanup*
*Context gathered: 2026-05-16*
