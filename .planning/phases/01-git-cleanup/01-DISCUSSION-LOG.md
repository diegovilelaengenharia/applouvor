# Phase 1: Git Cleanup - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-05-16
**Phase:** 1-git-cleanup
**Areas discussed:** Arquivos soltos na raiz, Estratégia de commits, Pasta App louvor 23.01.2026/, api/confirm_scale.php e pib-cards.css, Granularidade dos commits de admin/, Scripts de setup no git

---

## Arquivos Soltos na Raiz

### Scripts de setup local

| Option | Description | Selected |
|--------|-------------|----------|
| Ficam na raiz | Scripts usados diariamente pelo dev ficam visíveis na raiz. run_server.bat já fica lá. | ✓ |
| Vão para maintenance/ | Consolida tudo em maintenance/. Raiz fica mais limpa, mas é menos conveniente para uso diário. | |

**User's choice:** Ficam na raiz
**Notes:** run_server.bat já existe na raiz como precedente para esse padrão.

### restore_db.php

| Option | Description | Selected |
|--------|-------------|----------|
| Maintenance/ | Script de manutenção pontual. Já tem outros scripts lá. | ✓ |
| Raiz | Mantém acessível rapidamente. | |
| Gitignore | Script sensível (acessa banco) — ignorar e não versionar. | |

**User's choice:** Maintenance/
**Notes:** Consistente com os outros scripts de manutenção já em maintenance/.

---

## Estratégia de Commits

### Agrupamento

| Option | Description | Selected |
|--------|-------------|----------|
| Por área/módulo | 3–4 commits: feat(admin), feat(includes), chore(assets), docs(.planning). Histórico legível. | ✓ |
| Por tipo de mudança | feat:, fix:, refactor:, chore: separados. | |
| Commit único de sync | chore: sync gemini changes. Rápido, perde granularidade. | |

**User's choice:** Por área/módulo

### desktop.ini

| Option | Description | Selected |
|--------|-------------|----------|
| Commit separado chore(git) | Separa a limpeza de git da mudança de código. Fica claro no histórico. | ✓ |
| Junto com os outros arquivos | Menos commits, mas mistura limpeza com mudanças de código. | |

**User's choice:** Commit separado

### Revisar diff

| Option | Description | Selected |
|--------|-------------|----------|
| Sim, revisar antes de commitar | Garante que não vai commitar lixo. Executa git diff por área. | ✓ |
| Não, commitar diretamente | As mudanças já foram validadas visualmente. | |

**User's choice:** Sim, revisar diff
**Notes:** Executor deve rodar git diff por área antes de cada commit.

---

## Pasta App louvor 23.01.2026/

| Option | Description | Selected |
|--------|-------------|----------|
| Adicionar ao .gitignore | Não versiona a pasta, mantém local para consulta eventual. | ✓ |
| Deletar definitivamente | Remove do disco também. Histórico do git preserva o código antigo. | |
| Commitar como arquivo | Versiona a pasta no repo como referência histórica. Pode aumentar o repo. | |

**User's choice:** Adicionar ao .gitignore
**Notes:** Pasta permanece no disco local mas não entra no repositório.

---

## api/confirm_scale.php e pib-cards.css

### api/confirm_scale.php

| Option | Description | Selected |
|--------|-------------|----------|
| Commitar no Phase 1 | O arquivo já existe e funciona. Phase 1 é sobre commitar o estado atual. | ✓ |
| Reservar para Phase 2 | Só commitado quando a UI for criada. | |

**User's choice:** Commitar no Phase 1

### assets/css/components/pib-cards.css

| Option | Description | Selected |
|--------|-------------|----------|
| Commitar no Phase 1 | Parte do estado atual do projeto. Evita deixar arquivos esquecidos. | ✓ |
| Reservar para Phase 5 | Commitar junto com a fase que vai usar o componente. | |

**User's choice:** Commitar no Phase 1

---

## Granularidade dos Commits de admin/

| Option | Description | Selected |
|--------|-------------|----------|
| Um commit para todo admin/ | feat(admin): sync gemini changes. Simples e rápido. | ✓ |
| Agrupar por sub-área | feat(admin/escalas):..., feat(admin/membros):... Mais granular, mais commits. | |

**User's choice:** Um único commit para todo admin/

---

## Scripts de Setup no Git

| Option | Description | Selected |
|--------|-------------|----------|
| Sim, commitar no git | Facilita setup do ambiente para outros devs. | ✓ |
| Adicionar ao .gitignore | Scripts locais de cada dev não deveriam estar no repo. | |

**User's choice:** Sim, commitar no git

---

## Claude's Discretion

- Ordenação dos commits dentro de cada área
- Mensagem exata de cada commit (seguindo o padrão semântico)
- Se encontrar arquivos duvidosos durante o diff review, pode ignorar ou perguntar

## Deferred Ideas

None — discussion stayed within phase scope.
